<?php
require_once '../model/connectdb.php';
require_once '../model/customer_orders.php';
require_once '../model/khachhang.php';
session_start();

// Kiểm tra đăng nhập
if (!isset($_SESSION['customer_id'])) {
    // Lưu trang hiện tại để chuyển hướng lại sau khi đăng nhập
    $_SESSION['redirect_after_login'] = 'checkout.php';
    header('Location: login.php');
    exit();
}

$cart_items = [];
$total = 0;
$error = '';
$success = '';

// Lấy giỏ hàng từ session
if (isset($_SESSION['cart'])) {
    $conn = connectdb();
    foreach ($_SESSION['cart'] as $product_id => $quantity) {
        $stmt = $conn->prepare("SELECT * FROM tbl_sanpham WHERE id = ?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($product) {
            $subtotal = $product['gia'] * $quantity;
            $cart_items[] = [
                'product_id' => $product_id,
                'tensp' => $product['tensp'],
                'gia' => $product['gia'],
                'quantity' => $quantity,
                'subtotal' => $subtotal
            ];
            $total += $subtotal;
        }
    }
}

if (empty($cart_items)) {
    header('Location: cart.php');
    exit();
}

// Lấy thông tin khách hàng
$customer_id = $_SESSION['customer_id'];
$customer = get_khachhang_by_id($customer_id);

// Xử lý đặt hàng
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sử dụng thông tin từ tài khoản khách hàng
    $receiver_name = $customer['hoten'];
    $receiver_phone = $customer['sdt'];
    $receiver_address = $customer['diachi'];
    $notes = $_POST['notes'] ?? '';
    $payment_method = $_POST['payment_method'] ?? '';
    $customer_id = $_SESSION['customer_id'] ?? null;
    $error = '';
    // Kiểm tra thông tin khách hàng có đầy đủ không
    if (empty($receiver_name) || empty($receiver_phone) || empty($receiver_address)) {
        $error = 'Thông tin cá nhân của bạn không đầy đủ. Vui lòng cập nhật thông tin cá nhân trước khi thanh toán.';
    }
    if (empty($error)) {
        try {
            // Lưu đơn hàng vào tbl_customer_orders
            $conn = connectdb();
            $stmt = $conn->prepare("INSERT INTO tbl_customer_orders 
                (user_id, receiver_name, receiver_phone, receiver_address, payment_method, status, order_type, created_at, total_amount) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), ?)");
            $stmt->execute([
                $customer_id,
                $receiver_name,
                $receiver_phone,
                $receiver_address,
                $payment_method,
                'pending',
                'online',
                $total
            ]);
            $order_id = $conn->lastInsertId();
            $_SESSION['last_order_id'] = $order_id;

            // Lưu chi tiết đơn hàng
            foreach ($cart_items as $item) {
                $stmt = $conn->prepare("INSERT INTO tbl_customer_order_details 
                    (order_id, product_id, product_name, price, quantity) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$order_id, $item['product_id'], $item['tensp'], $item['gia'], $item['quantity']]);
            }

            // Nếu thanh toán bằng chuyển khoản, tạo mã QR và lưu thông tin thanh toán
            if ($payment_method === 'banking') {
                $order_code = "DH" . $order_id . rand(100, 999);
                $transfer_content = "ThanhToan_" . $order_code;

                // Lưu thông tin thanh toán
                $stmt = $conn->prepare("INSERT INTO payment_orders 
                    (customer_id, order_code, amount, transfer_content, status) 
                    VALUES (?, ?, ?, ?, 'pending')");
                $stmt->execute([$customer_id, $order_code, $total, $transfer_content]);

                // Lưu thông tin để hiển thị QR
                $_SESSION['payment_info'] = [
                    'order_id' => $order_id,
                    'order_code' => $order_code,
                    'amount' => $total,
                    'transfer_content' => $transfer_content
                ];

                unset($_SESSION['cart']);
                header('Location: payment_qr.php');
                exit();
            } else {
                // Thanh toán COD
                unset($_SESSION['cart']);
                header('Location: order_success.php');
                exit();
            }
        } catch (Exception $e) {
            $error = 'Có lỗi xảy ra, vui lòng thử lại sau: ' . $e->getMessage();
        }
    }
}

// Xử lý hủy đơn hàng (AJAX)
if (isset($_POST['action']) && $_POST['action'] === 'cancel_order') {
    $order_id = $_POST['order_id'] ?? 0;
    $conn = connectdb();
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
}

// Lấy đơn online cho nhân viên
$stmt = $conn->prepare("SELECT * FROM tbl_customer_orders WHERE order_type = 'online' AND (staff_id IS NULL OR staff_id = ?)");
$stmt->execute([$_SESSION['iduser']]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'view/header.php';
?>
<link rel="stylesheet" href="css/checkout.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<div class="checkout-wrapper">
    <form class="form-modern checkout-form" method="POST" id="checkout-form" action="">
        <h2 class="checkout-title">Thanh toán</h2>
        <div class="checkout-info">
            <p class="info-text"><i class="fas fa-info-circle"></i> Kiểm tra lại thông tin đặt hàng của bạn. Nếu có sai
                sót, vui lòng <a href="profile.php" class="profile-link">cập nhật thông tin cá nhân</a> trước khi
                thanh toán.</p>
        </div>
        <?php if ($error): ?>
        <div class="error-message alert-danger">
            <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
        </div>
        <?php endif; ?>
        <div class="customer-info-section">
            <h3><i class="fas fa-user-circle"></i> Thông tin người nhận</h3>
            <div class="info-group">
                <label class="info-label"><i class="fas fa-user"></i> Họ tên:</label>
                <div class="info-value"><?php echo htmlspecialchars($customer['hoten']); ?></div>
            </div>
            <div class="info-group">
                <label class="info-label"><i class="fas fa-phone"></i> Số điện thoại:</label>
                <div class="info-value"><?php echo htmlspecialchars($customer['sdt']); ?></div>
            </div>
            <div class="info-group">
                <label class="info-label"><i class="fas fa-map-marker-alt"></i> Địa chỉ:</label>
                <div class="info-value"><?php echo htmlspecialchars($customer['diachi']); ?></div>
            </div>
        </div>
        <div class="form-group">
            <label for="notes" class="form-label"><i class="fas fa-clipboard"></i> Ghi chú:</label>
            <textarea id="notes" name="notes" class="form-control"
                placeholder="Nhập ghi chú đặt hàng của bạn (nếu có)"></textarea>
        </div>
        <div class="form-group">
            <label for="payment_method" class="form-label required-field"><i class="fas fa-credit-card"></i> Phương thức
                thanh toán:</label>
            <select name="payment_method" class="form-control" required>
                <option value="cod">Thanh toán khi nhận hàng</option>
                <option value="banking">Chuyển khoản ngân hàng</option>
            </select>
        </div>
        <button type="submit" class="btn btn-order btn-main" id="submit-btn"><i class="fas fa-check"></i> Đặt
            hàng</button>
        <a href="index.php" class="btn-home form-link"><i class="fas fa-arrow-left"></i> Quay về trang chủ</a>
    </form>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    var form = document.getElementById("checkout-form");
    var submitBtn = document.getElementById("submit-btn");

    if (form) {
        form.addEventListener("submit", function(e) {
            // Không làm gì cả, để form tự submit
            console.log("Form đang được submit...");
        });
    }

    if (submitBtn) {
        submitBtn.addEventListener("click", function(e) {
            // Thêm để chắc chắn nút submit hoạt động
            form.submit();
        });
    }
});
</script>