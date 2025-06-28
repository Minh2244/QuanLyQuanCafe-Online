<?php
require_once '../model/connectdb.php';
session_start();

// Kiểm tra đăng nhập
if (!isset($_SESSION['customer_id'])) {
    header('Location: login.php');
    exit();
}

// Nếu có order_id trong URL, lấy thông tin thanh toán từ database
if (isset($_GET['order_id'])) {
    $order_id = $_GET['order_id'];
    $conn = connectdb();

    // Kiểm tra đơn hàng thuộc về khách hàng hiện tại
    $stmt = $conn->prepare("SELECT * FROM tbl_customer_orders WHERE id = ? AND user_id = ?");
    $stmt->execute([$order_id, $_SESSION['customer_id']]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order || $order['payment_method'] !== 'banking') {
        $_SESSION['error_message'] = 'Không tìm thấy thông tin thanh toán cho đơn hàng này';
        header('Location: orders.php');
        exit();
    }

    // Lấy thông tin thanh toán
    $stmt = $conn->prepare("
        SELECT * FROM payment_orders 
        WHERE order_code LIKE CONCAT('DH', ?, '%')
    ");
    $stmt->execute([$order_id]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$payment) {
        $_SESSION['error_message'] = 'Không tìm thấy thông tin thanh toán cho đơn hàng này';
        header('Location: orders.php');
        exit();
    }

    // Lưu thông tin thanh toán vào session
    $_SESSION['payment_info'] = [
        'order_id' => $order_id,
        'order_code' => $payment['order_code'],
        'amount' => $payment['amount'],
        'transfer_content' => $payment['transfer_content']
    ];
}

// Kiểm tra thông tin thanh toán trong session
if (!isset($_SESSION['payment_info'])) {
    $_SESSION['error_message'] = 'Không tìm thấy thông tin thanh toán';
    header('Location: orders.php');
    exit();
}

$payment_info = $_SESSION['payment_info'];
$order_id = $payment_info['order_id'];
$order_code = $payment_info['order_code'];
$amount = $payment_info['amount'];
$transfer_content = $payment_info['transfer_content'];

// Tạo URL cho mã QR VietQR
$qrUrl = "https://img.vietqr.io/image/VCB-1030549759-qr_only.png?amount=$amount&addInfo=$transfer_content&accountName=" . urlencode("MAI NHUT MINH");

include 'view/header.php';
?>

<link rel="stylesheet" href="css/payment_qr.css">

<div class="payment-container">
    <div class="payment-box">
        <h2>Thanh toán đơn hàng #<?php echo $order_id; ?></h2>

        <div class="qr-section">
            <h3>Quét mã QR để thanh toán</h3>
            <img src="<?php echo $qrUrl; ?>" alt="QR Vietcombank" class="qr-image">
        </div>

        <div class="payment-info">
            <p><strong>Ngân hàng:</strong> Vietcombank</p>
            <p><strong>Số tài khoản:</strong> 1030549759</p>
            <p><strong>Chủ tài khoản:</strong> MAI NHUT MINH</p>
            <p><strong>Số tiền:</strong> <?php echo number_format($amount, 0, ',', '.'); ?> VND</p>
            <p><strong>Nội dung chuyển khoản:</strong> <?php echo $transfer_content; ?></p>
        </div>

        <div class="payment-actions">
            <form action="confirm_payment.php" method="post">
                <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">
                <input type="hidden" name="order_code" value="<?php echo $order_code; ?>">
                <button type="submit" class="btn-confirm">Tôi đã chuyển khoản</button>
            </form>
            <a href="orders.php" class="btn-orders">Xem đơn hàng của tôi</a>
        </div>
    </div>
</div>

<?php include 'view/footer.php'; ?>