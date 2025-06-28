<?php
session_start();
require_once '../model/connectdb.php';
require_once '../model/khachhang.php';
require_once '../model/customer_orders.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['customer_id'])) {
    header('Location: login.php');
    exit();
}

$customer_id = $_SESSION['customer_id'];
$customer = get_khachhang_by_id($customer_id);

// Lấy thông tin tổng quan về đơn hàng của khách hàng
$order_stats = get_customer_order_stats($customer_id);
$favorite_products = get_customer_favorite_products($customer_id);

$success_message = '';
$error_message = '';

// Xử lý cập nhật thông tin
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $hoten = trim($_POST['hoten'] ?? '');
        $sdt = trim($_POST['sdt'] ?? '');
        $diachi = trim($_POST['diachi'] ?? '');

        // Kiểm tra dữ liệu
        if (empty($hoten) || empty($sdt) || empty($diachi)) {
            $error_message = 'Vui lòng điền đầy đủ thông tin!';
        } else {
            // Cập nhật thông tin cơ bản
            $result = update_khachhang($customer_id, $hoten, $sdt, $diachi);

            // Xử lý upload ảnh đại diện nếu có
            if (!empty($_FILES['avatar']['name'])) {
                $target_dir = "uploads/avatars/";

                // Tạo thư mục nếu chưa tồn tại
                if (!file_exists($target_dir)) {
                    mkdir($target_dir, 0777, true);
                }

                $file_extension = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
                $new_filename = "avatar_" . $customer_id . "_" . time() . "." . $file_extension;
                $target_file = $target_dir . $new_filename;

                // Kiểm tra loại file
                $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
                if (in_array($file_extension, $allowed_types)) {
                    if (move_uploaded_file($_FILES['avatar']['tmp_name'], $target_file)) {
                        // Cập nhật đường dẫn ảnh trong database
                        update_avatar($customer_id, $target_file);
                        $success_message = 'Cập nhật thông tin và ảnh đại diện thành công!';
                    } else {
                        $error_message = 'Có lỗi xảy ra khi tải ảnh lên!';
                    }
                } else {
                    $error_message = 'Chỉ chấp nhận file ảnh có định dạng JPG, JPEG, PNG hoặc GIF!';
                }
            } else {
                $success_message = 'Cập nhật thông tin thành công!';
            }

            // Cập nhật thông tin trong session
            $_SESSION['customer_name'] = $hoten;

            // Lấy lại thông tin khách hàng sau khi cập nhật
            $customer = get_khachhang_by_id($customer_id);
        }
    }
}

// Thêm link đến file CSS profile
$custom_css = '<link rel="stylesheet" href="css/profile.css">';
include 'view/header.php';
?>

<div class="profile-container">
    <div class="profile-header">
        <h1>Thông tin cá nhân</h1>
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>
    </div>

    <div class="profile-content">
        <div class="profile-sidebar">
            <div class="avatar-container">
                <?php if (!empty($customer['avatar'])): ?>
                    <img src="<?php echo htmlspecialchars($customer['avatar']); ?>" alt="Ảnh đại diện"
                        class="profile-avatar">
                <?php else: ?>
                    <div class="default-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                <?php endif; ?>
                <h3><?php echo htmlspecialchars($customer['hoten']); ?></h3>
            </div>

            <!-- Thống kê đơn hàng -->
            <div class="user-stats">
                <h4>Thống kê đơn hàng</h4>
                <div class="stat-item">
                    <div class="stat-icon">
                        <i class="fas fa-shopping-bag"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-value"><?php echo number_format($order_stats['total_orders']); ?></div>
                        <div class="stat-label">Tổng đơn hàng</div>
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-value"><?php echo number_format($order_stats['total_spent']); ?> VNĐ</div>
                        <div class="stat-label">Tổng chi tiêu</div>
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-value">
                            <?php echo date('d/m/Y', strtotime($order_stats['last_order_date'] ?? 'now')); ?></div>
                        <div class="stat-label">Đơn hàng gần nhất</div>
                    </div>
                </div>
            </div>

            <!-- Sản phẩm yêu thích -->
            <?php if (!empty($favorite_products)): ?>
                <div class="favorite-products">
                    <h4>Sản phẩm yêu thích</h4>
                    <?php foreach ($favorite_products as $product): ?>
                        <div class="product-item">
                            <div class="product-image">
                                <img src="<?php echo htmlspecialchars($product['img']); ?>"
                                    alt="<?php echo htmlspecialchars($product['tensp']); ?>">
                            </div>
                            <div class="product-info">
                                <div class="product-name"><?php echo htmlspecialchars($product['tensp']); ?></div>
                                <div class="product-price"><?php echo number_format($product['gia']); ?> VNĐ</div>
                                <div class="product-qty">Đã mua <?php echo $product['quantity']; ?> lần</div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="profile-form">
            <form action="" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="hoten">Họ và tên:</label>
                    <input type="text" id="hoten" name="hoten"
                        value="<?php echo htmlspecialchars($customer['hoten']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="sdt">Số điện thoại:</label>
                    <input type="tel" id="sdt" name="sdt" value="<?php echo htmlspecialchars($customer['sdt']); ?>"
                        required>
                </div>
                <div class="form-group">
                    <label for="diachi">Địa chỉ:</label>
                    <textarea id="diachi" name="diachi" rows="3"
                        required><?php echo htmlspecialchars($customer['diachi']); ?></textarea>
                </div>
                <div class="form-group">
                    <label for="username">Tên đăng nhập:</label>
                    <input type="text" id="username" value="<?php echo htmlspecialchars($customer['username']); ?>"
                        readonly>
                    <small>Tên đăng nhập không thể thay đổi</small>
                </div>
                <div class="form-group">
                    <label for="avatar">Ảnh đại diện:</label>
                    <div class="file-upload">
                        <label for="avatar" class="file-upload-label">
                            <i class="fas fa-cloud-upload-alt"></i> Chọn ảnh đại diện
                        </label>
                        <input type="file" id="avatar" name="avatar" accept="image/*">
                        <div class="file-name" id="file-name">Chưa có file nào được chọn</div>
                    </div>
                    <small>Chọn ảnh có định dạng JPG, JPEG, PNG hoặc GIF</small>
                </div>
                <div class="form-actions">
                    <button type="submit" name="update_profile" class="btn-update">Cập nhật thông tin</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Hiển thị tên file khi chọn ảnh
    document.getElementById('avatar').addEventListener('change', function() {
        const fileName = this.files[0] ? this.files[0].name : 'Chưa có file nào được chọn';
        document.getElementById('file-name').textContent = fileName;
    });
</script>

<?php include 'view/footer.php'; ?>