<?php
session_start();
include_once("../../../model/connectdb.php");
include_once("../../../model/tbl_customer_orders.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    $status = isset($_POST['status']) ? $_POST['status'] : '';
    $notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';

    if ($order_id <= 0) {
        $_SESSION['error_msg'] = "ID đơn hàng không hợp lệ";
        header("Location: ../../../index.php?act=staff_orders");
        exit();
    }

    $conn = connectdb();

    try {
        // Kiểm tra trạng thái hiện tại của đơn hàng
        $check_stmt = $conn->prepare("SELECT status FROM tbl_customer_orders WHERE id = ?");
        $check_stmt->execute([$order_id]);
        $current_order = $check_stmt->fetch(PDO::FETCH_ASSOC);

        if (!$current_order) {
            $_SESSION['error_msg'] = "Không tìm thấy đơn hàng";
            header("Location: ../../../index.php?act=staff_orders");
            exit();
        }

        // Kiểm tra nếu đơn hàng đã nhận thì không cho thay đổi trạng thái
        if ($current_order['status'] == ORDER_STATUS_RECEIVED) {
            $_SESSION['error_msg'] = "Không thể thay đổi trạng thái đơn hàng đã được giao và xác nhận";
            header("Location: ../../../index.php?act=staff_orders&view_order=" . $order_id);
            exit();
        }

        // Cập nhật trạng thái đơn hàng
        $sql = "UPDATE tbl_customer_orders SET 
                status = :status,
                updated_at = NOW()
                WHERE id = :order_id";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':status', $status, PDO::PARAM_STR);
        $stmt->bindParam(':order_id', $order_id, PDO::PARAM_INT);
        $stmt->execute();

        // Ghi log
        $sql = "INSERT INTO order_logs (order_id, staff_id, action, notes) 
                VALUES (:order_id, :staff_id, :action, :notes)";

        $action_text = "Cập nhật trạng thái: " . get_order_status_text($status);
        $staff_id = $_SESSION['iduser'];

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':order_id', $order_id, PDO::PARAM_INT);
        $stmt->bindParam(':staff_id', $staff_id, PDO::PARAM_INT);
        $stmt->bindParam(':action', $action_text, PDO::PARAM_STR);
        $stmt->bindParam(':notes', $notes, PDO::PARAM_STR);
        $stmt->execute();

        $_SESSION['success_msg'] = "Cập nhật trạng thái đơn hàng thành công";
    } catch (PDOException $e) {
        $_SESSION['error_msg'] = "Lỗi khi cập nhật đơn hàng: " . $e->getMessage();
    }

    // Chuyển hướng về trang chi tiết đơn hàng
    header("Location: ../../../index.php?act=staff_orders&view_order=" . $order_id);
    exit();
} else {
    header("Location: ../../../index.php?act=staff_orders");
    exit();
}
