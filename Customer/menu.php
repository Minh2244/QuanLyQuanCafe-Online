<?php
include "../model/connectdb.php";
include "../model/danhmuc.php";
include "../model/sanpham.php";

// Lấy danh sách danh mục
$dsdm = getall_dm();

// Lấy dữ liệu sản phẩm theo danh mục
$iddm = isset($_GET['iddm']) ? $_GET['iddm'] : 0;
$dssp = [];

$conn = connectdb();
if ($iddm > 0) {
    $sql = "SELECT * FROM tbl_sanpham WHERE iddm = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$iddm]);
    $dssp = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $sql = "SELECT * FROM tbl_sanpham";
    $stmt = $conn->query($sql);
    $dssp = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

session_start();
include "view/header.php";
?>
<section class="menu-section">
    <div class="section-content">
        <!-- Link CSS đã được đặt trong header.php -->

        <!-- Thông báo thêm vào giỏ hàng thành công -->
        <div class="alert alert-success cart-alert">
            <span id="cart-message"></span>
            <button class="close-alert">&times;</button>
        </div>

        <div class="menu-container">
            <!-- Danh mục -->
            <div class="category-menu">
                <h3>Danh mục</h3>
                <a href="menu.php" class="category-item <?php echo ($iddm == 0) ? 'active' : ''; ?>">
                    Tất cả sản phẩm
                </a>
                <?php foreach ($dsdm as $dm): ?>
                    <a href="menu.php?iddm=<?php echo $dm['id']; ?>"
                        class="category-item <?php echo ($iddm == $dm['id']) ? 'active' : ''; ?>">
                        <?php echo $dm['tendm']; ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <!-- Danh sách sản phẩm -->
            <div class="product-list">
                <?php
                if (count($dssp) > 0) {
                    foreach ($dssp as $sp) {
                        // Kiểm tra và điều chỉnh đường dẫn ảnh
                        $img_path = $sp['img'];
                        if (!file_exists("../uploaded/" . $img_path)) {
                            $img_path = "images/no-image.jpg";
                        } else {
                            $img_path = "../uploaded/" . $img_path;
                        }
                        echo '<div class="product-card">';
                        echo '<img src="' . $img_path . '" alt="' . htmlspecialchars($sp['tensp']) . '">';
                        echo '<h3>' . htmlspecialchars($sp['tensp']) . '</h3>';
                        echo '<p class="price">' . number_format($sp['gia'], 0, ',', '.') . ' VND</p>';
                        echo '<button class="add-to-cart" data-id="' . $sp['id'] . '" data-name="' . htmlspecialchars($sp['tensp']) . '">Thêm vào giỏ</button>';
                        echo '</div>';
                    }
                } else {
                    echo '<p>Không có sản phẩm nào trong danh mục này.</p>';
                }
                ?>
            </div>
        </div>
    </div>
</section>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function() {
        // Xử lý sự kiện click vào nút "Thêm vào giỏ"
        $('.add-to-cart').click(function(e) {
            e.preventDefault();

            // Lấy ID và tên sản phẩm
            var productId = $(this).data('id');
            var productName = $(this).data('name');

            // Hiển thị thông báo thành công
            $('#cart-message').text('Đã thêm ' + productName + ' vào giỏ hàng!');
            $('.cart-alert').fadeIn();

            // Tự động ẩn thông báo sau 3 giây
            setTimeout(function() {
                $('.cart-alert').fadeOut('slow');
            }, 3000);

            // Gửi request AJAX để thêm vào giỏ hàng
            $.ajax({
                url: 'cart_actions.php',
                type: 'POST',
                data: {
                    action: 'add',
                    product_id: productId,
                    quantity: 1
                },
                dataType: 'json',
                success: function(response) {
                    // Cập nhật số lượng trong giỏ hàng
                    var currentCount = parseInt($('.cart-count').text().replace(/[()]/g, '') ||
                        0);
                    var newCount = response.cart_count || (currentCount + 1);

                    if ($('.cart-count').length === 0) {
                        $('.nav-menu .nav-item:has(a[href="cart.php"])').find('a')
                            .append('<span class="cart-count">(' + newCount + ')</span>');
                    } else {
                        $('.cart-count').text('(' + newCount + ')');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error:', error);
                    alert('Có lỗi xảy ra khi thêm vào giỏ hàng!');
                }
            });
        });

        // Đóng thông báo khi nhấn nút close
        $('.close-alert').click(function() {
            $(this).parent().fadeOut('slow');
        });
    });
</script>
<?php include 'view/footer.php'; ?>