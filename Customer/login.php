<?php
session_start();
require_once '../model/connectdb.php';
require_once '../model/khachhang.php';

$error = '';
$info = '';
$show_forgot_password = false;
$username = '';

// Kiểm tra và khởi tạo biến đếm đăng nhập sai
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
}

// Xử lý đăng nhập qua AJAX
if (isset($_POST['ajax_login']) && $_POST['ajax_login'] == 1) {
    $response = ['success' => false, 'message' => '', 'show_forgot' => false, 'redirect' => ''];

    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $response['message'] = 'Vui lòng nhập đầy đủ thông tin';
    } else {
        $khachhang = check_khachhang($username, $password);
        if ($khachhang) {
            // Đăng nhập thành công
            $_SESSION['login_attempts'] = 0;
            $_SESSION['customer_id'] = $khachhang['id'];
            $_SESSION['customer_name'] = $khachhang['hoten'];
            // Kiểm tra redirect
            $redirect = $_SESSION['redirect_after_login'] ?? 'index.php';
            unset($_SESSION['redirect_after_login']);
            $response['success'] = true;
            $response['message'] = 'Đăng nhập thành công!';
            $response['redirect'] = $redirect;
        } else {
            // Đăng nhập thất bại
            $_SESSION['login_attempts']++;
            $response['message'] = 'Tên đăng nhập hoặc mật khẩu không đúng';
            // Hiển thị quên mật khẩu sau 3 lần đăng nhập sai
            if ($_SESSION['login_attempts'] >= 3) {
                $response['show_forgot'] = true;
            }
        }
    }
    // Trả về kết quả dưới dạng JSON
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Hiển thị thông báo nếu được chuyển hướng từ trang thanh toán
if (isset($_SESSION['redirect_after_login']) && $_SESSION['redirect_after_login'] === 'checkout.php') {
    $info = 'Vui lòng đăng nhập để tiếp tục thanh toán';
}

// Hiển thị thông báo khi đăng ký thành công
if (isset($_GET['registered']) && $_GET['registered'] == 1) {
    $info = 'Đăng ký tài khoản thành công! Vui lòng đăng nhập để tiếp tục.';
}

// Hiển thị thông báo đổi mật khẩu thành công
if (isset($_GET['password_reset']) && $_GET['password_reset'] == 'success') {
    $info = 'Đổi mật khẩu thành công! Vui lòng đăng nhập lại.';
    // Reset số lần đăng nhập sai
    $_SESSION['login_attempts'] = 0;
}

// Hiển thị quên mật khẩu nếu đã thất bại 3 lần trở lên
if (isset($_SESSION['login_attempts']) && $_SESSION['login_attempts'] >= 3) {
    $show_forgot_password = true;
}

// Xử lý đăng nhập truyền thống (không ajax)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['ajax_login'])) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    if (empty($username) || empty($password)) {
        $error = 'Vui lòng nhập đầy đủ thông tin';
    } else {
        $khachhang = check_khachhang($username, $password);
        if ($khachhang) {
            $_SESSION['login_attempts'] = 0;
            $_SESSION['customer_id'] = $khachhang['id'];
            $_SESSION['customer_name'] = $khachhang['hoten'];
            $redirect = $_SESSION['redirect_after_login'] ?? 'index.php';
            unset($_SESSION['redirect_after_login']);
            header('Location: ' . $redirect);
            exit();
        } else {
            $_SESSION['login_attempts']++;
            $error = 'Tên đăng nhập hoặc mật khẩu không đúng';
        }
    }
}

include 'view/header.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h2 class="card-title text-center mb-4">Đăng nhập</h2>

                    <div id="message-container">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>

                        <?php if ($info): ?>
                            <div class="alert alert-info"><?php echo $info; ?></div>
                        <?php endif; ?>
                    </div>

                    <form id="login-form" method="POST" class="needs-validation" novalidate>
                        <div class="mb-3">
                            <label for="username" class="form-label">Tên đăng nhập</label>
                            <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Mật khẩu</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Đăng nhập</button>
                            <div id="forgot-password-container" <?php echo $show_forgot_password ? '' : 'style="display:none;"'; ?>>
                                <a href="forgot_password.php" class="btn btn-outline-secondary forgot-password-btn">Quên mật khẩu?</a>
                            </div>
                            <a href="register.php" class="btn btn-outline-secondary">Đăng ký tài khoản mới</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const loginForm = document.getElementById('login-form');
        const messageContainer = document.getElementById('message-container');
        const forgotPasswordContainer = document.getElementById('forgot-password-container');

        loginForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(loginForm);
            formData.append('ajax_login', '1');

            fetch('login.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    // Xóa thông báo cũ
                    messageContainer.innerHTML = '';

                    // Hiển thị thông báo
                    if (data.message) {
                        const alertClass = data.success ? 'alert-success' : 'alert-danger';
                        const alertDiv = document.createElement('div');
                        alertDiv.className = `alert ${alertClass}`;
                        alertDiv.textContent = data.message;
                        messageContainer.appendChild(alertDiv);
                    }

                    // Hiển thị nút quên mật khẩu nếu cần
                    if (data.show_forgot) {
                        forgotPasswordContainer.style.display = 'block';
                    }

                    // Chuyển hướng nếu đăng nhập thành công
                    if (data.success && data.redirect) {
                        setTimeout(() => {
                            window.location.href = data.redirect;
                        }, 1000);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    messageContainer.innerHTML = '<div class="alert alert-danger">Có lỗi xảy ra khi xử lý yêu cầu</div>';
                });
        });
    });
</script>

<?php include 'view/footer.php'; ?>