<?php
session_start();
require_once('../model/connectdb.php');
require_once('../model/customer_orders.php'); // Đúng tên file model

header('Content-Type: application/json');

if (!isset($_SESSION['customer_id'])) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Phương thức không hợp lệ']);
    exit;
}

$action = $_POST['action'] ?? '';
$order_id = $_POST['order_id'] ?? '';

if (empty($action) || empty($order_id)) {
    echo json_encode(['success' => false, 'message' => 'Thiếu thông tin']);
    exit;
}

try {
    $conn = connectdb();

    if ($action === 'cancel') {
        $stmt = $conn->prepare("SELECT status FROM tbl_customer_orders WHERE id = ? AND user_id = ? AND order_type = 'online'");
        $stmt->execute([$order_id, $_SESSION['customer_id']]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy đơn hàng']);
            exit;
        }
        if ($order['status'] !== 'pending') {
            echo json_encode(['success' => false, 'message' => 'Chỉ có thể hủy đơn hàng đang chờ xử lý']);
            exit;
        }
        $stmt = $conn->prepare("UPDATE tbl_customer_orders SET status = 'cancelled' WHERE id = ?");
        $stmt->execute([$order_id]);
        echo json_encode(['success' => true, 'message' => 'Đã hủy đơn hàng thành công']);
        exit;
    } elseif ($action === 'received') {
        // Debug: Kiểm tra thông tin đơn hàng
        $debug_info = [];
        $debug_info['customer_id'] = $_SESSION['customer_id'];
        $debug_info['order_id'] = $order_id;

        // Kiểm tra đơn hàng có tồn tại không
        $stmt = $conn->prepare("SELECT * FROM tbl_customer_orders WHERE id = ?");
        $stmt->execute([$order_id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        $debug_info['order'] = $order;

        if (!$order) {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy đơn hàng', 'debug' => $debug_info]);
            exit;
        }

        if ($order['status'] !== 'shipping') {
            echo json_encode(['success' => false, 'message' => 'Chỉ có thể xác nhận nhận hàng khi đơn hàng đang giao', 'debug' => $debug_info]);
            exit;
        }

        // Sử dụng hàm update_order_status với staff_id từ đơn hàng
        // Nếu đơn hàng không có staff_id, sử dụng staff_id mặc định (1 cho admin)
        $staff_id = $order['staff_id'] ?? 1;
        $notes = 'Khách hàng đã nhận hàng';

        $update_success = update_order_status($order_id, 'received', $staff_id, $notes);

        if (!$update_success) {
            echo json_encode(['success' => false, 'message' => 'Không thể cập nhật trạng thái đơn hàng', 'debug' => $debug_info]);
            exit;
        }

        // Thông báo cho admin và nhân viên
        if ($staff_id) {
            try {
                // Thông báo cho nhân viên xử lý đơn hàng
                $stmt = $conn->prepare("INSERT INTO notifications (user_id, message, order_id, is_read) VALUES (?, ?, ?, 0)");
                $notify_result = $stmt->execute([$staff_id, 'Đơn hàng #' . $order_id . ' đã được khách hàng xác nhận nhận hàng', $order_id]);
                $debug_info['notify_result'] = $notify_result;

                // Thông báo cho admin (giả sử admin có id = 1)
                if ($staff_id != 1) {
                    $stmt = $conn->prepare("INSERT INTO notifications (user_id, message, order_id, is_read) VALUES (1, ?, ?, 0)");
                    $admin_notify_result = $stmt->execute(['Đơn hàng #' . $order_id . ' đã được khách hàng xác nhận nhận hàng', $order_id]);
                    $debug_info['admin_notify_result'] = $admin_notify_result;
                }
            } catch (Exception $e) {
                // Lỗi gửi thông báo không ảnh hưởng đến việc cập nhật trạng thái đơn hàng
                error_log("Lỗi gửi thông báo: " . $e->getMessage());
            }
        }

        echo json_encode(['success' => true, 'message' => 'Đã xác nhận nhận hàng thành công', 'debug' => $debug_info]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Hành động không hợp lệ']);
    }
} catch (Exception $e) {
    $error = 'Có lỗi xảy ra: ' . $e->getMessage();
    echo json_encode(['success' => false, 'message' => $error]);
    exit;
}