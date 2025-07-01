<?php
require_once '../model/connectdb.php';
session_start();

if (!isset($_SESSION['customer_id'])) {
    $_SESSION['redirect_after_login'] = 'orders.php';
    header('Location: login.php');
    exit();
}

function get_order_details($order_id)
{
    $conn = connectdb();
    $stmt = $conn->prepare("SELECT * FROM tbl_customer_order_details WHERE order_id = ?");
    $stmt->execute([$order_id]);
    $details = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Lấy thông tin thanh toán nếu có
    $stmt = $conn->prepare("
        SELECT p.* FROM payment_orders p 
        JOIN tbl_customer_orders o ON p.order_code LIKE CONCAT('DH', o.id, '%') 
        WHERE o.id = ?
    ");
    $stmt->execute([$order_id]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);

    return [
        'items' => $details,
        'payment' => $payment
    ];
}

$conn = connectdb();
$stmt = $conn->prepare("SELECT * FROM tbl_customer_orders WHERE user_id = ? AND order_type = 'online' ORDER BY created_at DESC");
$stmt->execute([$_SESSION['customer_id']]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Hiển thị thông báo thành công/lỗi từ session nếu có
$success_message = $_SESSION['success_message'] ?? '';
$error_message = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

include 'view/header.php';
?>
<link rel="stylesheet" href="css/orders.css">

<div class="orders-container">
    <h2>Đơn hàng của tôi</h2>

    <?php if (!empty($success_message)): ?>
        <div class="alert alert-success">
            <?php echo $success_message; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger">
            <?php echo $error_message; ?>
        </div>
    <?php endif; ?>

    <?php if (empty($orders)): ?>
        <div class="no-orders">
            <p>Bạn chưa có đơn hàng nào</p>
            <a href="menu.php" class="btn-shop">Mua sắm ngay</a>
        </div>
    <?php else: ?>
        <div class="orders-list">
            <?php foreach ($orders as $order): ?>
                <?php
                $order_data = get_order_details($order['id']);
                $order_details = $order_data['items'];
                $payment_info = $order_data['payment'] ?? null;
                ?>
                <div class="order-item" data-id="<?php echo $order['id']; ?>">
                    <div class="order-header">
                        <div class="order-info">
                            <span class="order-id">Đơn hàng #<?php echo $order['id']; ?></span>
                            <span class="order-date"><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></span>
                        </div>
                        <div class="order-status-container">
                            <div class="order-status <?php echo $order['status']; ?>">
                                <?php
                                $status_text = [
                                    'pending' => 'Chờ xác nhận',
                                    'confirmed' => 'Đã xác nhận',
                                    'shipping' => 'Đang giao hàng',
                                    'received' => 'Đã nhận hàng',
                                    'cancelled' => 'Đã hủy'
                                ];
                                echo $status_text[$order['status']] ?? $order['status'];
                                ?>
                            </div>
                            <?php if ($order['payment_method'] === 'banking' && isset($payment_info)): ?>
                                <div class="payment-status <?php echo $payment_info['status'] === 'paid' ? 'paid' : 'pending'; ?>">
                                    <?php echo $payment_info['status'] === 'paid' ? 'Đã thanh toán' : 'Chờ thanh toán'; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="order-details">
                        <div class="receiver-info">
                            <p><strong>Người nhận:</strong> <?php echo htmlspecialchars($order['receiver_name']); ?></p>
                            <p><strong>Số điện thoại:</strong> <?php echo htmlspecialchars($order['receiver_phone']); ?></p>
                            <p><strong>Địa chỉ:</strong> <?php echo htmlspecialchars($order['receiver_address']); ?></p>
                        </div>
                        <div class="products-info">
                            <p><strong>Sản phẩm:</strong></p>
                            <ul>
                                <?php
                                foreach ($order_details as $item) {
                                    echo '<li>' . htmlspecialchars($item['product_name']) . ' x ' . $item['quantity'] . '</li>';
                                }
                                ?>
                            </ul>
                        </div>
                        <?php if (!empty($order['notes'])): ?>
                            <div class="order-notes">
                                <p><strong>Ghi chú:</strong></p>
                                <p><?php echo htmlspecialchars($order['notes']); ?></p>
                            </div>
                        <?php endif; ?>
                        <div class="order-total">
                            <p><strong>Tổng tiền:</strong>
                                <?php
                                $total = 0;
                                foreach ($order_details as $item) {
                                    $total += $item['price'] * $item['quantity'];
                                }
                                echo number_format($total, 0, ',', '.');
                                ?>đ</p>
                        </div>
                    </div>
                    <div class="order-actions">
                        <?php if ($order['status'] === 'pending'): ?>
                            <button class="btn-cancel" onclick="cancelOrder(<?php echo $order['id']; ?>)">Hủy đơn hàng</button>
                        <?php elseif ($order['status'] === 'shipping'): ?>
                            <button class="btn-received" onclick="confirmReceived(<?php echo $order['id']; ?>)">Đã nhận
                                hàng</button>
                        <?php endif; ?>

                        <?php if ($order['payment_method'] === 'banking' && isset($payment_info) && $payment_info['status'] === 'pending'): ?>
                            <a href="payment_qr.php?order_id=<?php echo $order['id']; ?>" class="btn-payment">Xem thông tin thanh toán</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
    function cancelOrder(orderId) {
        if (!confirm('Bạn có chắc muốn hủy đơn hàng này?')) return;
        fetch('order_actions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `action=cancel&order_id=${orderId}`
            })
            .then(response => response.json())
            .then(data => {
                alert(data.message || 'Có lỗi xảy ra');
                if (data.success) location.reload();
            })
            .catch(error => alert('Có lỗi xảy ra, vui lòng thử lại sau'));
    }

    function confirmReceived(orderId) {
        if (!confirm('Xác nhận đã nhận được hàng?')) return;
        fetch('order_actions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `action=received&order_id=${orderId}`
            })
            .then(response => response.json())
            .then(data => {
                alert(data.message || 'Có lỗi xảy ra');
                if (data.success) location.reload();
            })
            .catch(error => alert('Có lỗi xảy ra, vui lòng thử lại sau'));
    }
</script>
<?php include 'view/footer.php'; ?>