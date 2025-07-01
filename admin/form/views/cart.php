<!-- Thêm link đến file CSS -->
<link rel="stylesheet" href="css/payment_modal.css">

<section class="cart-section">
    <div class="section-content">
        <h2 class="section-header">Giỏ hàng của bạn</h2>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger">
                <?php echo $_SESSION['error_message']; ?>
                <?php unset($_SESSION['error_message']); ?>
            </div>
        <?php endif; ?>

        <?php
        // Hiển thị thông báo thêm vào giỏ hàng thành công nếu có
        // Chỉ hiển thị nếu thông báo thuộc về phiên làm việc hiện tại
        // Và không phải là nhân viên (role = 0)
        if (
            isset($_SESSION['cart_message']) &&
            isset($_SESSION['current_session_id']) &&
            $_SESSION['current_session_id'] === session_id() &&
            (!isset($_SESSION['role']) || $_SESSION['role'] != 0)
        ) {
            echo '<div class="alert alert-success cart-notification">';
            echo $_SESSION['cart_message'];
            echo '</div>';
            // Xóa thông báo sau khi hiển thị
            unset($_SESSION['cart_message']);
        } elseif (isset($_SESSION['role']) && $_SESSION['role'] == 0 && isset($_SESSION['cart_message'])) {
            // Đảm bảo xóa thông báo giỏ hàng nếu là nhân viên
            unset($_SESSION['cart_message']);
        }
        ?>

        <?php
        if (isset($_SESSION['cart']) && is_array($_SESSION['cart']) && count($_SESSION['cart']) > 0) {
            $totalAmount = 0;
        ?>
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
                    <?php
                    foreach ($_SESSION['cart'] as $item) {
                        if (is_array($item) && isset($item['gia']) && isset($item['soluong']) && isset($item['img']) && isset($item['tensp']) && isset($item['id'])) {
                            $thanh_tien = $item['gia'] * $item['soluong'];
                            $totalAmount += $thanh_tien;

                            // Điều chỉnh đường dẫn ảnh
                            $img_path = $item['img'];
                            if (strpos($img_path, "../uploaded/") === 0) {
                                $img_path = "../../uploaded/" . basename($img_path);
                            }

                            // Rút gọn tên sản phẩm nếu quá dài
                            $ten_sp = $item['tensp'];
                            if (strlen($ten_sp) > 30) {
                                $ten_sp = substr($ten_sp, 0, 27) . '...';
                            }
                    ?>
                            <tr>
                                <td title="<?php echo $item['tensp']; ?>"><?php echo $ten_sp; ?></td>
                                <td><img src="<?php echo $img_path; ?>" alt="<?php echo $item['tensp']; ?>" width="60"
                                        style="display: block; margin: 0 auto;"></td>
                                <td><?php echo number_format($item['gia'], 0, ',', '.'); ?> VNĐ</td>
                                <td class="text-center">
                                    <div class="quantity-control">
                                        <button class="quantity-btn minus" data-id="<?php echo $item['id']; ?>">-</button>
                                        <span class="quantity-value"
                                            id="qty-<?php echo $item['id']; ?>"><?php echo $item['soluong']; ?></span>
                                        <button class="quantity-btn plus" data-id="<?php echo $item['id']; ?>">+</button>
                                    </div>
                                </td>
                                <td id="thanh-tien-<?php echo $item['id']; ?>">
                                    <?php echo number_format($thanh_tien, 0, ',', '.'); ?> VNĐ</td>
                                <td class="text-center">
                                    <a href="index.php?act=removefromcart&id=<?php echo $item['id']; ?>"
                                        onclick="return confirm('Bạn có chắc muốn xóa sản phẩm này?')" class="delete-btn">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                </td>
                            </tr>
                    <?php
                        }
                    }
                    ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="4" style="text-align: right;"><strong>Tổng tiền:</strong></td>
                        <td><strong class="cart-total"><?php echo number_format($totalAmount, 0, ',', '.'); ?> VNĐ</strong></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>

            <div class="cart-buttons">
                <a href="index.php?act=menu" class="btn">Tiếp tục mua hàng</a>
                <button type="button" id="showPaymentModal" class="btn checkout payment-btn">Thanh toán</button>
            </div>

        <?php } else { ?>
            <div class="empty-cart">
                <p style="text-align: center; padding: 30px 0;">Giỏ hàng của bạn đang trống. <a
                        href="index.php?act=menu">Tiếp tục mua sắm</a></p>
            </div>
        <?php } ?>
    </div>
</section>

<!-- Thêm modal thanh toán từ file riêng -->
<?php
if (isset($_SESSION['cart']) && is_array($_SESSION['cart']) && count($_SESSION['cart']) > 0) {
    include 'payment_modal.php';
}
?>

<div class="overlay" id="bill-overlay" style="display: none;">
    <div class="bill-container">
        <div class="bill-header">
            <h2>HÓA ĐƠN</h2>
            <p>Best Coffee ☕</p>
            <p>Địa chỉ: số 4, Đông Mỹ, Thới Lai, Cần Thơ</p>
            <p>Điện thoại: 0869378427</p>
        </div>
        <div class="bill-content">
            <p><strong>Mã hóa đơn:</strong> #<span id="bill-id"></span></p>
            <p><strong>Ngày tạo:</strong> <span id="bill-date"></span></p>
            <p><strong>Nhân viên:</strong> <span id="bill-staff"></span></p>

            <table class="bill-table">
                <thead>
                    <tr>
                        <th>STT</th>
                        <th>Sản phẩm</th>
                        <th>Đơn giá</th>
                        <th>SL</th>
                        <th>Thành tiền</th>
                    </tr>
                </thead>
                <tbody id="bill-items">
                </tbody>
            </table>

            <div class="bill-total">
                <p>Tổng tiền: <span id="bill-total"></span> VNĐ</p>
            </div>
        </div>
        <div class="bill-footer">
            <p>Cảm ơn quý khách đã sử dụng dịch vụ của chúng tôi!</p>
            <p>Thời gian hiển tại: <span id="bill-time"></span></p>
        </div>
        <div class="bill-actions">
            <button class="print-btn" onclick="printBill()">In hóa đơn</button>
            <button class="continue-btn" onclick="continueShopping()">Tiếp tục mua hàng</button>
        </div>
    </div>
</div>

<style>
    .text-center {
        text-align: center;
    }

    .delete-btn {
        color: #e74c3c;
        transition: color 0.3s;
    }

    .delete-btn:hover {
        color: #c0392b;
    }

    .empty-cart {
        background-color: #f9f9f9;
        border-radius: 8px;
        margin: 20px 0;
    }

    /* CSS cho nút tăng giảm số lượng */
    .quantity-control {
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .quantity-btn {
        width: 25px;
        height: 25px;
        background-color: #f0f0f0;
        border: 1px solid #ddd;
        border-radius: 3px;
        cursor: pointer;
        font-weight: bold;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s;
    }

    .quantity-btn:hover {
        background-color: #e0e0e0;
    }

    .quantity-value {
        margin: 0 8px;
        display: inline-block;
        min-width: 20px;
        text-align: center;
    }

    .overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        display: none;
        z-index: 999;
        overflow: auto;
    }

    .bill-container {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 0 20px rgba(0, 0, 0, 0.15);
        width: 90%;
        max-width: 500px;
        margin: 20px auto;
    }

    .bill-header {
        text-align: center;
        padding-bottom: 15px;
        border-bottom: 1px solid #eee;
    }

    .bill-header h2 {
        margin: 0 0 10px 0;
        color: #333;
        font-size: 24px;
    }

    .bill-header p {
        margin: 5px 0;
        color: #666;
        font-size: 14px;
    }

    .bill-content {
        margin: 15px 0;
    }

    .bill-content p {
        margin: 5px 0;
        font-size: 14px;
    }

    .bill-table {
        width: 100%;
        border-collapse: collapse;
        margin: 15px 0;
        font-size: 14px;
    }

    .bill-table th,
    .bill-table td {
        padding: 10px;
        text-align: left;
        border-bottom: 1px solid #ddd;
    }

    .bill-table th {
        background-color: #f8f9fa;
        font-weight: bold;
    }

    .bill-total {
        text-align: right;
        font-weight: bold;
        margin: 15px 0;
        font-size: 16px;
        padding: 10px;
        background-color: #f8f9fa;
        border-radius: 4px;
    }

    .bill-footer {
        text-align: center;
        margin-top: 20px;
        padding-top: 15px;
        border-top: 1px solid #eee;
        color: #666;
        font-size: 13px;
    }

    .bill-actions {
        margin-top: 20px;
        text-align: center;
    }

    .print-btn,
    .continue-btn {
        padding: 8px 15px;
        margin: 0 5px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-weight: bold;
    }

    .print-btn {
        background-color: #3498db;
        color: white;
    }

    .continue-btn {
        background-color: #2ecc71;
        color: white;
    }

    @media print {
        .overlay {
            background: none;
            overflow: visible;
        }

        .bill-container {
            position: absolute;
            left: 0;
            top: 0;
            transform: none;
            width: 100%;
            max-width: none;
            margin: 0;
            padding: 15px;
            box-shadow: none;
        }

        .bill-actions {
            display: none;
        }
    }

    @media screen and (max-width: 600px) {
        .bill-container {
            width: 95%;
            margin: 10px auto;
            padding: 15px;
        }

        .bill-header h2 {
            font-size: 20px;
        }

        .bill-table {
            font-size: 12px;
        }

        .bill-table th,
        .bill-table td {
            padding: 8px 5px;
        }

        .bill-total {
            font-size: 14px;
        }

        .bill-actions button {
            padding: 8px 16px;
            font-size: 13px;
        }
    }
</style>

<script>
    $(document).ready(function() {
        // Xử lý sự kiện click nút tăng/giảm số lượng
        $('.quantity-btn').click(function() {
            var $button = $(this);
            var productId = $button.data('id');
            var change = $button.hasClass('plus') ? 1 : -1;

            // Vô hiệu hóa nút trong khi xử lý
            $('.quantity-btn[data-id="' + productId + '"]').prop('disabled', true);

            $.ajax({
                url: 'views/update_cart.php',
                type: 'POST',
                data: {
                    id: productId,
                    change: change
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // Cập nhật số lượng
                        $('#qty-' + productId).text(response.quantity);

                        // Cập nhật thành tiền của sản phẩm
                        $('#thanh-tien-' + productId).text(response.item_total + ' VNĐ');

                        // Cập nhật tổng tiền giỏ hàng
                        $('.cart-total').text(response.cart_total + ' VNĐ');
                    } else {
                        alert(response.message || 'Có lỗi xảy ra khi cập nhật giỏ hàng');
                    }

                    // Kích hoạt lại nút
                    $('.quantity-btn[data-id="' + productId + '"]').prop('disabled', false);
                },
                error: function(xhr, status, error) {
                    console.error('Error:', error);
                    alert('Có lỗi xảy ra khi cập nhật giỏ hàng');

                    // Kích hoạt lại nút nếu có lỗi
                    $('.quantity-btn[data-id="' + productId + '"]').prop('disabled', false);
                }
            });
        });

        // Tự động ẩn thông báo
        setTimeout(function() {
            $('.cart-notification').fadeOut('slow');
        }, 3000);
    });

    function showBill(billData) {
        document.getElementById('bill-overlay').style.display = 'flex';
        // Cập nhật nội dung hóa đơn
        document.getElementById('bill-id').textContent = billData.id;
        document.getElementById('bill-date').textContent = billData.date;
        document.getElementById('bill-staff').textContent = billData.staff;
        document.getElementById('bill-time').textContent = billData.time;

        // Cập nhật danh sách sản phẩm
        let billItems = '';
        billData.items.forEach((item, index) => {
            billItems += `
                <tr>
                    <td>${index + 1}</td>
                    <td>${item.name}</td>
                    <td>${formatCurrency(item.price)}</td>
                    <td>${item.quantity}</td>
                    <td>${formatCurrency(item.total)}</td>
                </tr>
            `;
        });
        document.getElementById('bill-items').innerHTML = billItems;
        document.getElementById('bill-total').textContent = formatCurrency(billData.total);
    }

    function printBill() {
        window.print();
    }

    function continueShopping() {
        document.getElementById('bill-overlay').style.display = 'none';
        window.location.href = 'index.php?act=menu';
    }

    function formatCurrency(amount) {
        return new Intl.NumberFormat('vi-VN').format(amount);
    }

    // Đóng overlay khi click bên ngoài
    document.getElementById('bill-overlay').addEventListener('click', function(e) {
        if (e.target === this) {
            this.style.display = 'none';
        }
    });
</script>

<!-- Thêm script xử lý modal thanh toán -->
<script src="js/payment_modal.js"></script>