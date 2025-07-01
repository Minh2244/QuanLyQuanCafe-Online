<?php
// Đảm bảo không có output nào trước khi sử dụng header()
session_start();
include_once("../../../model/connectdb.php");

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    $conn = connectdb();

    // Lấy dữ liệu từ request
    $billCode = $_POST['bill_code'] ?? '';
    $cartData = json_decode($_POST['cart_data'] ?? '[]', true);
    $totalAmount = floatval($_POST['total_amount'] ?? 0);
    $paymentMethod = $_POST['payment_method'] ?? '';
    $staffId = $_SESSION['iduser'] ?? 0;

    if (empty($cartData) || $totalAmount <= 0) {
        echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
        exit;
    }

    // Tính tổng số lượng sản phẩm
    $totalProducts = 0;
    foreach ($cartData as $item) {
        $totalProducts += intval($item['quantity']);
    }

    // Bắt đầu transaction
    $conn->beginTransaction();

    try {
        // Thêm hóa đơn mới
        $sql = "INSERT INTO tbl_hoadon (
            user_id,
            ngay_tao,
            tong_tien,
            payment_method,
            trang_thai
        ) VALUES (
            :user_id,
            NOW(),
            :tong_tien,
            :payment_method,
            'Đã thanh toán'
        )";

        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':user_id' => $staffId,
            ':tong_tien' => $totalAmount,
            ':payment_method' => $paymentMethod
        ]);

        $billId = $conn->lastInsertId();

        // Thêm chi tiết hóa đơn
        $sql = "INSERT INTO tbl_hoadon_chitiet (
            hoadon_id,
            sanpham_id,
            ten_sp,
            gia,
            so_luong
        ) VALUES (
            :hoadon_id,
            :sanpham_id,
            :ten_sp,
            :gia,
            :so_luong
        )";

        $stmt = $conn->prepare($sql);

        foreach ($cartData as $item) {
            $stmt->execute([
                ':hoadon_id' => $billId,
                ':sanpham_id' => $item['id'],
                ':ten_sp' => $item['name'],
                ':gia' => $item['price'],
                ':so_luong' => $item['quantity']
            ]);
        }

        // Kiểm tra và tạo/cập nhật thống kê ngày
        $today = date('Y-m-d');
        $sql = "INSERT INTO tbl_thongke_ngay (
            ngay,
            tong_doanh_thu,
            tong_don_hang,
            tong_san_pham
        ) VALUES (
            :ngay,
            :tong_doanh_thu,
            1,
            :tong_san_pham
        ) ON DUPLICATE KEY UPDATE
            tong_doanh_thu = tong_doanh_thu + :tong_doanh_thu,
            tong_don_hang = tong_don_hang + 1,
            tong_san_pham = tong_san_pham + :tong_san_pham";

        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':ngay' => $today,
            ':tong_doanh_thu' => $totalAmount,
            ':tong_san_pham' => $totalProducts
        ]);

        // Lấy ID của thống kê ngày
        $stmt = $conn->prepare("SELECT id FROM tbl_thongke_ngay WHERE ngay = :ngay");
        $stmt->execute([':ngay' => $today]);
        $thongkeId = $stmt->fetchColumn();

        // Cập nhật thống kê sản phẩm
        $sql = "INSERT INTO tbl_thongke_sanpham (
            thongke_id,
            sanpham_id,
            ten_sp,
            so_luong,
            doanh_thu
        ) VALUES (
            :thongke_id,
            :sanpham_id,
            :ten_sp,
            :so_luong,
            :doanh_thu
        ) ON DUPLICATE KEY UPDATE
            so_luong = so_luong + VALUES(so_luong),
            doanh_thu = doanh_thu + VALUES(doanh_thu)";

        $stmt = $conn->prepare($sql);

        foreach ($cartData as $item) {
            $doanh_thu = $item['price'] * $item['quantity'];
            $stmt->execute([
                ':thongke_id' => $thongkeId,
                ':sanpham_id' => $item['id'],
                ':ten_sp' => $item['name'],
                ':so_luong' => $item['quantity'],
                ':doanh_thu' => $doanh_thu
            ]);
        }

        // Nếu là thanh toán chuyển khoản, thêm vào bảng payment_orders
        if ($paymentMethod === 'bank') {
            $sql = "INSERT INTO payment_orders (
                order_code,
                amount,
                transfer_content,
                status,
                created_at
            ) VALUES (
                :order_code,
                :amount,
                :transfer_content,
                'pending',
                NOW()
            )";

            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':order_code' => $billCode,
                ':amount' => $totalAmount,
                ':transfer_content' => 'TT' . $billCode
            ]);
        }

        // Commit transaction
        $conn->commit();

        // Xóa giỏ hàng sau khi đặt hàng thành công
        unset($_SESSION['cart']);

        echo json_encode([
            'success' => true,
            'message' => 'Đơn hàng đã được lưu thành công',
            'bill_id' => $billId
        ]);
    } catch (Exception $e) {
        // Rollback nếu có lỗi
        $conn->rollBack();
        throw $e;
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi khi lưu đơn hàng: ' . $e->getMessage()
    ]);
}