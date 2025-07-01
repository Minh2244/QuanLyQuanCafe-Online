<?php
session_start();
require_once '../model/connectdb.php';
require_once '../model/khachhang.php';

$error = '';
$success = '';
$show_reset_form = false;
$user_data = null;
$username = ''; // Lưu giữ username
$phone = ''; // Lưu giữ số điện thoại

// Xử lý Ajax cho xác minh danh tính
if (isset($_POST['ajax_verify']) && $_POST['ajax_verify'] == 1) {
    $response = ['success' => false, 'message' => '', 'show_reset_form' => false, 'fullname' => ''];

    $username = $_POST['username'] ?? '';
    $phone = $_POST['phone'] ?? '';

    if (empty($username) || empty($phone)) {
        $response['message'] = 'Vui lòng nhập đầy đủ thông tin';
    } else {
        $conn = connectdb();
        $stmt = $conn->prepare("SELECT * FROM tbl_khachhang WHERE username = ? AND sdt = ?");
        $stmt->execute([$username, $phone]);
        $customer = $stmt->fetch();

        if ($customer) {
            // Xác minh thành công
            $_SESSION['reset_customer_id'] = $customer['id'];
            $response['success'] = true;
            $response['message'] = 'Xác minh danh tính thành công';
            $response['show_reset_form'] = true;
            $response['fullname'] = $customer['hoten'];
        } else {
            $response['message'] = 'Thông tin xác minh không chính xác';
        }
    }

    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Xử lý Ajax cho đổi mật khẩu
if (isset($_POST['ajax_reset']) && $_POST['ajax_reset'] == 1) {
    $response = ['success' => false, 'message' => '', 'redirect' => ''];

    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Kiểm tra customer_id trong session
    if (!isset($_SESSION['reset_customer_id'])) {
        $response['message'] = 'Phiên làm việc đã hết hạn. Vui lòng thử lại.';
    }
    // Kiểm tra mật khẩu trống
    elseif (empty($new_password) || empty($confirm_password)) {
        $response['message'] = 'Vui lòng nhập đầy đủ thông tin mật khẩu';
    }
    // Kiểm tra mật khẩu khớp nhau
    elseif ($new_password !== $confirm_password) {
        $response['message'] = 'Mật khẩu xác nhận không khớp';
    } else {
        // Cập nhật mật khẩu
        $customer_id = $_SESSION['reset_customer_id'];

        // Sử dụng hàm update_password từ model khachhang.php
        $result = update_password($customer_id, $new_password);

        if ($result) {
            // Xóa session đã sử dụng
            unset($_SESSION['reset_customer_id']);
            // Xóa số lần đăng nhập sai
            unset($_SESSION['login_attempts']);

            $response['success'] = true;
            $response['message'] = 'Đổi mật khẩu thành công!';
            $response['redirect'] = 'login.php?password_reset=success';
        } else {
            $response['message'] = 'Có lỗi xảy ra. Vui lòng thử lại sau.';
        }
    }

    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Xử lý yêu cầu POST truyền thống (không ajax)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Kiểm tra xem đang gửi form xác minh hay form đặt lại mật khẩu
    if (isset($_POST['verify_identity'])) {
        $username = $_POST['username'] ?? '';
        $phone = $_POST['phone'] ?? '';

        if (empty($username) || empty($phone)) {
            $error = 'Vui lòng nhập đầy đủ thông tin';
        } else {
            $conn = connectdb();
            $stmt = $conn->prepare("SELECT * FROM tbl_khachhang WHERE username = ? AND sdt = ?");
            $stmt->execute([$username, $phone]);
            $customer = $stmt->fetch();

            if ($customer) {
                // Xác minh thành công, hiển thị form đổi mật khẩu
                $show_reset_form = true;
                $user_data = $customer;
                $_SESSION['reset_customer_id'] = $customer['id'];
            } else {
                $error = 'Thông tin xác minh không chính xác';
            }
        }
    } elseif (isset($_POST['reset_password'])) {
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        // Kiểm tra customer_id trong session
        if (!isset($_SESSION['reset_customer_id'])) {
            $error = 'Phiên làm việc đã hết hạn. Vui lòng thử lại.';
        }
        // Kiểm tra mật khẩu trống
        elseif (empty($new_password) || empty($confirm_password)) {
            $error = 'Vui lòng nhập đầy đủ thông tin mật khẩu';
            $show_reset_form = true;
        }
        // Kiểm tra mật khẩu khớp nhau
        elseif ($new_password !== $confirm_password) {
            $error = 'Mật khẩu xác nhận không khớp';
            $show_reset_form = true;
        } else {
            // Cập nhật mật khẩu
            $customer_id = $_SESSION['reset_customer_id'];

            // Sử dụng hàm update_password từ model khachhang.php
            $result = update_password($customer_id, $new_password);

            if ($result) {
                // Xóa session đã sử dụng
                unset($_SESSION['reset_customer_id']);
                // Xóa số lần đăng nhập sai
                unset($_SESSION['login_attempts']);

                // Chuyển hướng về trang đăng nhập với thông báo thành công
                header('Location: login.php?password_reset=success');
                exit;
            } else {
                $error = 'Có lỗi xảy ra. Vui lòng thử lại sau.';
                $show_reset_form = true;
            }
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
                    <h2 class="card-title text-center mb-4">Quên mật khẩu</h2>

                    <div id="message-container">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>

                        <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>
                    </div>

                    <div id="verify-form-container" <?php echo $show_reset_form ? 'style="display:none;"' : ''; ?>>
                        <!-- Form xác minh danh tính -->
                        <form id="verify-form" method="POST" class="needs-validation" novalidate>
                            <div class="mb-3">
                                <label for="username" class="form-label">Tên đăng nhập</label>
                                <input type="text" class="form-control" id="username" name="username"
                                    value="<?php echo htmlspecialchars($username); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="phone" class="form-label">Số điện thoại</label>
                                <input type="tel" class="form-control" id="phone" name="phone"
                                    value="<?php echo htmlspecialchars($phone); ?>"
                                    placeholder="Nhập số điện thoại đã đăng ký" required>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" name="verify_identity" class="btn btn-primary">Xác minh</button>
                                <a href="login.php" class="btn btn-outline-secondary">Quay lại đăng nhập</a>
                            </div>
                        </form>
                    </div>

                    <div id="reset-form-container" <?php echo $show_reset_form ? '' : 'style="display:none;"'; ?>>
                        <!-- Form đặt lại mật khẩu -->
                        <p id="user-greeting" class="alert alert-info">
                            <?php if ($show_reset_form): ?>
                                Xin chào <strong><?php echo htmlspecialchars($user_data['hoten'] ?? ''); ?></strong>!
                                Vui lòng nhập mật khẩu mới cho tài khoản của bạn.
                            <?php endif; ?>
                        </p>

                        <form id="reset-form" method="POST" class="needs-validation" novalidate>
                            <div class="mb-3">
                                <label for="new_password" class="form-label">Mật khẩu mới</label>
                                <input type="password" class="form-control" id="new_password" name="new_password"
                                    required>
                            </div>

                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Xác nhận mật khẩu</label>
                                <input type="password" class="form-control" id="confirm_password"
                                    name="confirm_password" required>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" name="reset_password" class="btn btn-primary">Đổi mật
                                    khẩu</button>
                                <button type="button" id="cancel-reset" class="btn btn-outline-secondary">Hủy</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const verifyForm = document.getElementById('verify-form');
        const resetForm = document.getElementById('reset-form');
        const messageContainer = document.getElementById('message-container');
        const verifyFormContainer = document.getElementById('verify-form-container');
        const resetFormContainer = document.getElementById('reset-form-container');
        const userGreeting = document.getElementById('user-greeting');
        const cancelReset = document.getElementById('cancel-reset');

        // Xử lý form xác minh danh tính
        if (verifyForm) {
            verifyForm.addEventListener('submit', function(e) {
                e.preventDefault();

                const formData = new FormData(verifyForm);
                formData.append('ajax_verify', '1');

                fetch('forgot_password.php', {
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

                        // Hiển thị form đặt lại mật khẩu nếu xác minh thành công
                        if (data.success && data.show_reset_form) {
                            verifyFormContainer.style.display = 'none';
                            resetFormContainer.style.display = 'block';

                            // Cập nhật thông báo chào mừng
                            userGreeting.innerHTML =
                                `Xin chào <strong>${data.fullname}</strong>! Vui lòng nhập mật khẩu mới cho tài khoản của bạn.`;
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        messageContainer.innerHTML =
                            '<div class="alert alert-danger">Có lỗi xảy ra khi xử lý yêu cầu</div>';
                    });
            });
        }

        // Xử lý form đặt lại mật khẩu
        if (resetForm) {
            resetForm.addEventListener('submit', function(e) {
                e.preventDefault();

                const formData = new FormData(resetForm);
                formData.append('ajax_reset', '1');

                fetch('forgot_password.php', {
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

                        // Chuyển hướng nếu thành công
                        if (data.success && data.redirect) {
                            setTimeout(() => {
                                window.location.href = data.redirect;
                            }, 1500);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        messageContainer.innerHTML =
                            '<div class="alert alert-danger">Có lỗi xảy ra khi xử lý yêu cầu</div>';
                    });
            });
        }

        // Xử lý nút hủy
        if (cancelReset) {
            cancelReset.addEventListener('click', function() {
                verifyFormContainer.style.display = 'block';
                resetFormContainer.style.display = 'none';
                messageContainer.innerHTML = '';
            });
        }
    });
</script>

<?php include 'view/footer.php'; ?>