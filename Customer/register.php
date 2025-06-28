<?php
session_start();
require_once '../model/connectdb.php';
require_once '../model/khachhang.php';
$error = '';
$success = '';

// Lưu lại trang chuyển hướng nếu có
$redirect = isset($_SESSION['redirect_after_login']) ? $_SESSION['redirect_after_login'] : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    if ($fullname && $phone && $address && $username && $password) {
        if (get_khachhang_by_username($username)) {
            $error = 'Tên đăng nhập đã tồn tại!';
        } else {
            insert_khachhang($fullname, $phone, $address, $username, $password);
            $success = 'Đăng ký thành công! Vui lòng đăng nhập.';
            // Chuyển hướng đến trang đăng nhập với thông báo đăng ký thành công
            header('Location: login.php?registered=1');
            exit();
        }
    } else {
        $error = 'Vui lòng nhập đầy đủ thông tin bắt buộc!';
    }
}
include 'view/header.php';
?>
<link rel="stylesheet" href="css/register.css">

<div class="register-form">
    <h2>Đăng ký tài khoản</h2>

    <?php if ($error): ?>
        <div class="error-message"><?php echo $error; ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="success-message"><?php echo $success; ?></div>
    <?php endif; ?>

    <form action="" method="POST">
        <div class="form-group">
            <label for="fullname">Họ tên <span class="required">*</span></label>
            <input type="text" id="fullname" name="fullname" required>
        </div>
        <div class="form-group">
            <label for="phone">Số điện thoại <span class="required">*</span></label>
            <input type="tel" id="phone" name="phone" required>
        </div>
        <div class="form-group">
            <label for="address">Địa chỉ <span class="required">*</span></label>
            <input type="text" id="address" name="address" required>
        </div>
        <div class="form-group">
            <label for="username">Tên đăng nhập <span class="required">*</span></label>
            <input type="text" id="username" name="username" required>
        </div>
        <div class="form-group">
            <label for="password">Mật khẩu <span class="required">*</span></label>
            <input type="password" id="password" name="password" required>
        </div>
        <button type="submit" name="register" class="btn-register">Đăng ký</button>
        <div class="login-link">
            Đã có tài khoản? <a href="login.php">Đăng nhập</a>
        </div>
    </form>
</div>

<?php
include 'view/footer.php';
?>