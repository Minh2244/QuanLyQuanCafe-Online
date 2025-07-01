<?php
session_start();
include "../../model/connectdb.php";
include "../../model/tbl_customer_orders.php";

// Kiểm tra đăng nhập và quyền truy cập
if (!isset($_SESSION['role']) || ($_SESSION['role'] != 0 && $_SESSION['role'] != 1)) {
    header('location: ../../admin/login.php');
    exit();
}

$conn = connectdb();

// Lấy đơn online cho nhân viên
$stmt = $conn->prepare("SELECT * FROM tbl_customer_orders WHERE order_type = 'online' AND (staff_id IS NULL OR staff_id = ?)");
$stmt->execute([$_SESSION['iduser']]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Lấy tất cả đơn cho admin
$stmt = $conn->prepare("SELECT * FROM tbl_customer_orders ORDER BY created_at DESC");
$stmt->execute();
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_order'])) {
    $order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
    $status = isset($_POST['status']) ? $_POST['status'] : '';
    $notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';
    $staff_id = $_SESSION['iduser'];

    if ($order_id <= 0) {
        $_SESSION['error_msg'] = "ID đơn hàng không hợp lệ";
        header('Location: ../../index.php?act=staff_orders');
        exit();
    }

    // Kiểm tra trạng thái hiện tại của đơn hàng
    $stmt = $conn->prepare("SELECT status FROM tbl_customer_orders WHERE id = ?");
    $stmt->execute([$order_id]);
    $current_order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$current_order) {
        $_SESSION['error_msg'] = "Không tìm thấy đơn hàng";
        header('Location: ../../index.php?act=staff_orders');
        exit();
    }

    // Kiểm tra nếu đơn hàng đã nhận thì không cho thay đổi trạng thái
    if ($current_order['status'] == ORDER_STATUS_RECEIVED) {
        $_SESSION['error_msg'] = "Không thể thay đổi trạng thái đơn hàng đã được giao và xác nhận";
        header('Location: ../../index.php?act=staff_orders&view_order=' . $order_id);
        exit();
    }

    // Kiểm tra trạng thái hợp lệ
    $valid_statuses = [
        ORDER_STATUS_CONFIRMED,
        ORDER_STATUS_SHIPPING,
        ORDER_STATUS_RECEIVED,
        ORDER_STATUS_CANCELLED
    ];

    if (!in_array($status, $valid_statuses)) {
        $_SESSION['error_msg'] = "Trạng thái không hợp lệ";
        header('Location: ../../index.php?act=staff_orders&view_order=' . $order_id);
        exit();
    }

    // Cập nhật trạng thái đơn hàng
    $stmt = $conn->prepare("UPDATE tbl_customer_orders SET status = ?, staff_id = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$status, $staff_id, $order_id]);

    if ($stmt) {
        $_SESSION['success_msg'] = "Cập nhật trạng thái đơn hàng thành công!";

        // Nếu đơn hàng đã hoàn thành hoặc hủy, chuyển về trang danh sách
        if ($status == ORDER_STATUS_RECEIVED || $status == ORDER_STATUS_CANCELLED) {
            header('Location: ../../index.php?act=staff_orders');
        } else {
            header('Location: ../../index.php?act=staff_orders&view_order=' . $order_id);
        }
    } else {
        $_SESSION['error_msg'] = "Có lỗi xảy ra khi cập nhật trạng thái đơn hàng";
        header('Location: ../../index.php?act=staff_orders&view_order=' . $order_id);
    }
    exit();
} else {
    header('Location: ../../index.php?act=staff_orders');
    exit();
}
