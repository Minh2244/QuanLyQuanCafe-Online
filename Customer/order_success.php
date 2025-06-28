<?php
require_once('../model/connectdb.php');
require_once('../model/customer_orders.php');
session_start();

// Kiểm tra có mã đơn hàng không
if (!isset($_SESSION['last_order_id'])) {
    header('Location: index.php');
    exit();
}

$order_id = $_SESSION['last_order_id'];
unset($_SESSION['last_order_id']); // Xóa để tránh truy cập lại

$conn = connectdb();
// Lấy thông tin đơn hàng
$order = get_order($order_id);
// Lấy chi tiết đơn hàng
$order_details = get_order_details($order_id);

include 'view/header.php';
?>
<link rel="stylesheet" href="css/order_success.css">

<div class="container">
    <div class="order-success">
        <div class="success-icon">
            <i class="fas fa-check-circle"></i>
        </div>

        <h2>Đặt Hàng Thành Công!</h2>
        <p>Cảm ơn bạn đã đặt hàng. Mã đơn hàng của bạn là: <strong>#<?php echo $order_id; ?></strong></p>

        <div class="order-info">
            <h3>Thông Tin Đơn Hàng</h3>
            <table class="order-info-table">
                <tr>
                    <td>Trạng thái đơn hàng:</td>
                    <td><?php echo get_order_status_text($order['status']); ?></td>
                </tr>
                <tr>
                    <td>Người nhận:</td>
                    <td><?php echo htmlspecialchars($order['receiver_name']); ?></td>
                </tr>
                <tr>
                    <td>Số điện thoại:</td>
                    <td><?php echo htmlspecialchars($order['receiver_phone']); ?></td>
                </tr>
                <tr>
                    <td>Địa chỉ:</td>
                    <td><?php echo htmlspecialchars($order['receiver_address']); ?></td>
                </tr>
                <tr>
                    <td>Phương thức thanh toán:</td>
                    <td><?php echo isset($order['payment_method']) ? htmlspecialchars($order['payment_method']) : ''; ?>
                    </td>
                </tr>
            </table>
        </div>

        <div class="order-items">
            <h3>Chi Tiết Đơn Hàng</h3>
            <div class="items-list">
                <?php foreach ($order_details as $item): ?>
                <div class="order-item">
                    <span class="item-name">
                        <?php echo htmlspecialchars($item['product_name']); ?> x <?php echo $item['quantity']; ?>
                    </span>
                    <span class="item-price">
                        <?php echo number_format($item['price'] * $item['quantity'], 0, ',', '.'); ?>đ
                    </span>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="order-total">
                <span>Tổng cộng:</span>
                <span class="total-amount">
                    <?php echo number_format($order['total_amount'], 0, ',', '.'); ?>đ
                </span>
            </div>
        </div>

        <?php if ($order['payment_method'] == 'banking'): ?>
        <div class="payment-info">
            <h3>Thông Tin Thanh Toán</h3>
            <p>Vui lòng chuyển khoản theo thông tin sau:</p>
            <div class="bank-info">
                <p><strong>Ngân hàng:</strong> VIETCOMBANK</p>
                <p><strong>Số tài khoản:</strong> 123456789</p>
                <p><strong>Chủ tài khoản:</strong> MAI NHUT MINH</p>
                <p><strong>Nội dung:</strong> DH<?php echo $order_id; ?></p>
            </div>
        </div>
        <?php endif; ?>

        <div class="success-actions">
            <a href="menu.php" class="btn btn-primary">Tiếp tục mua hàng</a>
        </div>
    </div>
</div>

<?php include 'view/footer.php'; ?>