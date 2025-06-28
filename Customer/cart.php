<?php
require_once '../model/connectdb.php';
session_start();

$cart_items = [];
$total = 0;

// Nếu đã đăng nhập, lấy giỏ hàng từ database (bỏ qua vì không còn class Customer)
// Nếu chưa đăng nhập, lấy giỏ hàng từ session
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
                'img' => $product['img'],
                'quantity' => $quantity,
                'subtotal' => $subtotal
            ];
            $total += $subtotal;
        }
    }
}

include 'view/header.php';
?>
<link rel="stylesheet" href="css/cart.css">

<div class="container">
    <h2>Giỏ hàng của bạn</h2>

    <?php if (empty($cart_items)): ?>
    <div class="alert">Giỏ hàng trống</div>
    <div class="cart-actions">
        <a href="menu.php" class="continue-shopping">Tiếp tục mua hàng</a>
    </div>
    <?php else: ?>
    <div class="cart-page">
        <table class="cart-table">
            <thead>
                <tr>
                    <th>Sản phẩm</th>
                    <th>Hình ảnh</th>
                    <th>Đơn giá</th>
                    <th>SL</th>
                    <th>Thành tiền</th>
                    <th>Xóa</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cart_items as $item):
                        $img_path = isset($item['img']) ? $item['img'] : '';
                        if (empty($img_path) || !file_exists("../uploaded/" . $img_path)) {
                            $img_path = "images/default.jpg";
                        } else {
                            $img_path = "../uploaded/" . $img_path;
                        }
                    ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['tensp']); ?></td>
                    <td>
                        <img src="<?php echo $img_path; ?>" alt="<?php echo htmlspecialchars($item['tensp']); ?>">
                    </td>
                    <td><?php echo number_format($item['gia'], 0, ',', '.'); ?> VND</td>
                    <td>
                        <div class="quantity-cell">
                            <button class="quantity-btn minus" data-id="<?php echo $item['product_id']; ?>">-</button>
                            <input type="number" class="quantity-input" value="<?php echo $item['quantity']; ?>" min="1"
                                data-id="<?php echo $item['product_id']; ?>">
                            <button class="quantity-btn plus" data-id="<?php echo $item['product_id']; ?>">+</button>
                        </div>
                    </td>
                    <td><?php echo number_format($item['subtotal'], 0, ',', '.'); ?> VND</td>
                    <td>
                        <button class="remove-btn" data-id="<?php echo $item['product_id']; ?>">×</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="6" class="text-right">
                        <strong>Tổng tiền: <?php echo number_format($total, 0, ',', '.'); ?> VND</strong>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="cart-actions">
        <a href="menu.php" class="continue-shopping">Tiếp tục mua hàng</a>
        <a href="checkout.php" class="checkout">Thanh toán</a>
    </div>
    <?php endif; ?>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="script.js"></script>

<?php include 'view/footer.php'; ?>