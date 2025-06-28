<?php
session_start();
require_once '../model/connectdb.php';

if (!isset($_SESSION['customer_id'])) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = $_POST['order_id'] ?? 0;
    $order_code = $_POST['order_code'] ?? '';

    if (empty($order_id) || empty($order_code)) {
        $_SESSION['error_message'] = 'Thông tin thanh toán không hợp lệ';
        header('Location: orders.php');
        exit();
    }

    try {
        $conn = connectdb();

        // Cập nhật trạng thái thanh toán
        $stmt = $conn->prepare("UPDATE payment_orders SET status = 'paid' WHERE order_code = ?");
        $stmt->execute([$order_code]);

        // Cập nhật ghi chú đơn hàng
        $stmt = $conn->prepare("UPDATE tbl_customer_orders SET notes = CONCAT(IFNULL(notes, ''), ' | Khách hàng đã xác nhận chuyển khoản') WHERE id = ?");
        $stmt->execute([$order_id]);

        // Thêm log nếu có bảng order_logs
        if (tableExists($conn, 'order_logs')) {
            $stmt = $conn->prepare("INSERT INTO order_logs (order_id, staff_id, action, notes, customer_id) VALUES (?, 0, 'payment_confirmed', 'Khách hàng đã xác nhận chuyển khoản', ?)");
            $stmt->execute([$order_id, $_SESSION['customer_id']]);
        }

        $_SESSION['success_message'] = 'Đã xác nhận thanh toán thành công! Chúng tôi sẽ xử lý đơn hàng của bạn sớm nhất.';
        header('Location: orders.php');
        exit();
    } catch (Exception $e) {
        $_SESSION['error_message'] = 'Có lỗi xảy ra: ' . $e->getMessage();
        header('Location: orders.php');
        exit();
    }
} else {
    header('Location: index.php');
    exit();
}

// Hàm kiểm tra bảng tồn tại
function tableExists($conn, $tableName)
{
    try {
        $result = $conn->query("SELECT 1 FROM $tableName LIMIT 1");
        return true;
    } catch (Exception $e) {
        return false;
    }
}