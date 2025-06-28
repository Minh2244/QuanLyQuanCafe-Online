<?php
session_start();
include "../../model/connectdb.php";
include "../../model/danhmuc.php";
include "../../model/sanpham.php";
include "../../model/connectdb_thongke.php";

// Tạo bảng hóa đơn nếu chưa tồn tại
$conn = connectdb();
$sql = "CREATE TABLE IF NOT EXISTS tbl_hoadon (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    ngay_tao DATETIME DEFAULT CURRENT_TIMESTAMP,
    tong_tien DECIMAL(10,2) NOT NULL,
    trang_thai VARCHAR(50) DEFAULT 'Đã thanh toán'
)";
$conn->exec($sql);

$sql = "CREATE TABLE IF NOT EXISTS tbl_hoadon_chitiet (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    hoadon_id INT(11) NOT NULL,
    sanpham_id INT(11) NOT NULL,
    ten_sp VARCHAR(255) NOT NULL,
    gia DECIMAL(10,2) NOT NULL,
    so_luong INT(11) NOT NULL
)";
$conn->exec($sql);

$sql = "CREATE TABLE IF NOT EXISTS tbl_working_time (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    username VARCHAR(255) NOT NULL,
    time_login DATETIME DEFAULT CURRENT_TIMESTAMP,
    time_logout DATETIME NULL
)";
$conn->exec($sql);

// Ghi lại thời gian đăng nhập
if (isset($_SESSION['role']) && isset($_SESSION['iduser']) && !isset($_SESSION['logged_working_time'])) {
    $user_id = $_SESSION['iduser'];
    $username = $_SESSION['username'];

    // Kiểm tra xem đã có bản ghi working time nào chưa kết thúc không
    $sql = "SELECT id FROM tbl_working_time WHERE user_id = :user_id AND time_logout IS NULL";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $existing_session = $stmt->fetch();

    if (!$existing_session) {
        // Chỉ tạo bản ghi mới nếu không có session đang hoạt động
        $sql = "INSERT INTO tbl_working_time (user_id, username) VALUES (:user_id, :username)";
        try {
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':username', $username);
            $stmt->execute();
            $_SESSION['working_time_id'] = $conn->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error creating working time record: " . $e->getMessage());
        }
    } else {
        // Nếu có session đang hoạt động, sử dụng session đó
        $_SESSION['working_time_id'] = $existing_session['id'];
    }
    $_SESSION['logged_working_time'] = true;

    // Xóa thông báo giỏ hàng nếu nhân viên đăng nhập
    if ($_SESSION['role'] == 0 && isset($_SESSION['cart_message'])) {
        unset($_SESSION['cart_message']);
    }
}

// Xử lý ghi thời gian đăng xuất
if (isset($_GET['act']) && $_GET['act'] == 'logout') {
    if (isset($_SESSION['working_time_id'])) {
        $id = $_SESSION['working_time_id'];
        $sql = "UPDATE tbl_working_time SET time_logout = NOW() WHERE id = '$id'";
        $conn->exec($sql);
    }
    unset($_SESSION['role']);
    unset($_SESSION['iduser']);
    unset($_SESSION['username']);
    unset($_SESSION['logged_working_time']);
    unset($_SESSION['working_time_id']);
    header('location:../../admin/login.php');
    exit();
}

// Lấy dữ liệu danh mục
$dsdm = getall_dm();

// Lấy dữ liệu sản phẩm theo danh mục
$iddm = isset($_GET['iddm']) ? $_GET['iddm'] : 0;
$dssp = [];

if ($iddm > 0) {
    $conn = connectdb();
    $stmt = $conn->prepare("SELECT * FROM tbl_sanpham WHERE iddm = '$iddm'");
    $stmt->execute();
    $dssp = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $dssp = getall_sanpham();
}

// Xử lý thanh toán
if (isset($_POST['thanhtoan']) && isset($_SESSION['cart']) && count($_SESSION['cart']) > 0) {
    if (!isset($_SESSION['iduser'])) {
        // Thêm thông báo cần đăng nhập
        $_SESSION['error_message'] = "Vui lòng đăng nhập để thanh toán";
        // Redirect đến trang đăng nhập nếu chưa đăng nhập
        header('location: ../../admin/login.php');
        exit();
    }

    $user_id = $_SESSION['iduser'];
    $tong_tien = 0;

    foreach ($_SESSION['cart'] as $item) {
        if (is_array($item) && isset($item['gia']) && isset($item['soluong'])) {
            $tong_tien += $item['gia'] * $item['soluong'];
        }
    }

    try {
        // Bắt đầu transaction
        $conn->beginTransaction();

        // Tạo hóa đơn
        $sql = "INSERT INTO tbl_hoadon (user_id, tong_tien) VALUES (:user_id, :tong_tien)";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':tong_tien', $tong_tien);
        $stmt->execute();

        // Lấy ID hóa đơn vừa tạo
        $hoadon_id = $conn->lastInsertId();

        // Lưu chi tiết hóa đơn
        foreach ($_SESSION['cart'] as $item) {
            if (is_array($item) && isset($item['id']) && isset($item['tensp']) && isset($item['gia']) && isset($item['soluong'])) {
                $sanpham_id = $item['id'];
                $ten_sp = $item['tensp'];
                $gia = $item['gia'];
                $so_luong = $item['soluong'];

                $sql = "INSERT INTO tbl_hoadon_chitiet (hoadon_id, sanpham_id, ten_sp, gia, so_luong) 
                        VALUES (:hoadon_id, :sanpham_id, :ten_sp, :gia, :so_luong)";
                $stmt = $conn->prepare($sql);
                $stmt->bindParam(':hoadon_id', $hoadon_id);
                $stmt->bindParam(':sanpham_id', $sanpham_id);
                $stmt->bindParam(':ten_sp', $ten_sp);
                $stmt->bindParam(':gia', $gia);
                $stmt->bindParam(':so_luong', $so_luong);
                $stmt->execute();
            }
        }

        // Commit transaction
        $conn->commit();

        // Lưu hóa đơn ID vào session và xóa giỏ hàng
        $_SESSION['hoadon_id'] = $hoadon_id;
        $_SESSION['thanh_toan_ok'] = true;
        unset($_SESSION['cart']);

        // Chuyển hướng đến trang hóa đơn
        header('location: index.php?act=hoadon&id=' . $hoadon_id);
        exit();
    } catch (PDOException $e) {
        // Rollback transaction nếu có lỗi
        $conn->rollback();
        $_SESSION['error_message'] = "Lỗi thanh toán: " . $e->getMessage();
        header('location: index.php?act=cart');
        exit();
    }
}

// Xử lý thêm sản phẩm vào giỏ hàng
if (isset($_GET['act']) && $_GET['act'] == 'addtocart' && isset($_GET['id'])) {
    $id = $_GET['id'];
    $sp = getonesp($id);

    if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

    // Điều chỉnh đường dẫn ảnh để lưu vào giỏ hàng
    $img_path = $sp[0]['img'];
    // Chúng ta sẽ lưu đường dẫn gốc vào session để đơn giản hóa việc xử lý

    $item = [
        'id' => $sp[0]['id'],
        'tensp' => $sp[0]['tensp'],
        'gia' => $sp[0]['gia'],
        'img' => $img_path,
        'soluong' => 1
    ];

    // Kiểm tra sản phẩm đã có trong giỏ hàng chưa
    $found = false;
    foreach ($_SESSION['cart'] as $key => $cartItem) {
        if ($cartItem['id'] == $id) {
            $_SESSION['cart'][$key]['soluong'] += 1;
            $found = true;
            break;
        }
    }

    if (!$found) {
        $_SESSION['cart'][] = $item;
    }

    // Thêm thông báo sản phẩm đã được thêm vào giỏ hàng 
    // Chỉ hiển thị thông báo trên trang giỏ hàng của người dùng thông thường (không phải nhân viên)
    if (!isset($_SESSION['role']) || $_SESSION['role'] != 0) {
        $_SESSION['cart_message'] = "Đã thêm sản phẩm vào giỏ hàng!";

        // Đặt session id để xác định phiên làm việc hiện tại
        $_SESSION['current_session_id'] = session_id();
    } else {
        // Đảm bảo không có thông báo giỏ hàng nếu là nhân viên
        if (isset($_SESSION['cart_message'])) {
            unset($_SESSION['cart_message']);
        }
    }

    // Quay lại trang trước đó, không chuyển hướng đến cart
    header('location: ' . $_SERVER['HTTP_REFERER']);
    exit();
}

// Xử lý xóa sản phẩm khỏi giỏ hàng
if (isset($_GET['act']) && $_GET['act'] == 'removefromcart' && isset($_GET['id'])) {
    $id = $_GET['id'];

    foreach ($_SESSION['cart'] as $key => $item) {
        if ($item['id'] == $id) {
            unset($_SESSION['cart'][$key]);
            break;
        }
    }

    header('location: index.php?act=cart');
    exit();
}

// Thêm liên kết đến trang ý đơn hàng
if (isset($_GET['act']) && $_GET['act'] == 'orders') {
    header('location: staff_orders.php');
    exit();
}

// Lấy thông tin lịch sử hóa đơn
$bills = [];
if (isset($_SESSION['iduser'])) {
    $user_id = $_SESSION['iduser'];
    $conn = connectdb();

    // Nếu là admin, lấy tất cả hóa đơn
    if ($_SESSION['role'] == 1) {
        $stmt = $conn->prepare("SELECT h.*, u.user as username 
                FROM tbl_hoadon AS h 
                JOIN tbl_user AS u ON h.user_id = u.id 
                ORDER BY h.ngay_tao DESC");
        $stmt->execute();
    } else {
        // Nếu là nhân viên, chỉ lấy hóa đơn của mình
        $stmt = $conn->prepare("SELECT h.*, u.user as username 
                FROM tbl_hoadon AS h 
                JOIN tbl_user AS u ON h.user_id = u.id 
                WHERE h.user_id = :user_id 
                ORDER BY h.ngay_tao DESC");
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
    }

    $bills = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Lấy thông tin thời gian làm việc
$working_times = [];
if (isset($_SESSION['iduser'])) {
    $user_id = $_SESSION['iduser'];
    $conn = connectdb();

    // Nếu là admin, lấy tất cả thời gian làm việc
    if ($_SESSION['role'] == 1) {
        $stmt = $conn->prepare("SELECT wt.*, u.user as username 
                                FROM tbl_working_time AS wt 
                                JOIN tbl_user AS u ON wt.user_id = u.id 
                                ORDER BY wt.time_login DESC");
        $stmt->execute();
    } else {
        // Nếu là nhân viên, chỉ lấy thời gian làm việc của mình
        $stmt = $conn->prepare("SELECT wt.*, u.user as username 
                                FROM tbl_working_time AS wt 
                                JOIN tbl_user AS u ON wt.user_id = u.id 
                                WHERE wt.user_id = :user_id 
                                ORDER BY wt.time_login DESC");
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
    }

    $working_times = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Xử lý cập nhật trạng thái hóa đơn
if (isset($_POST['update_bill']) && ($_SESSION['role'] == 0 || $_SESSION['role'] == 1)) {
    $bill_id = $_POST['bill_id'];
    $trang_thai = $_POST['trang_thai'];

    $conn = connectdb();
    $sql = "UPDATE tbl_hoadon SET trang_thai = :trang_thai WHERE id = :id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':trang_thai', $trang_thai);
    $stmt->bindParam(':id', $bill_id);
    $stmt->execute();

    $_SESSION['success_message'] = "Đã cập nhật trạng thái hóa đơn thành công!";
    header('location: index.php?act=bills');
    exit();
}

// Xử lý xóa hóa đơn (cả admin và nhân viên)
if (isset($_GET['act']) && $_GET['act'] == 'deletebill' && isset($_GET['id'])) {
    // Kiểm tra quyền admin hoặc nhân viên
    if (!isset($_SESSION['role']) || ($_SESSION['role'] != 0 && $_SESSION['role'] != 1)) {
        $_SESSION['error_message'] = "Bạn không có quyền xóa hóa đơn!";
        header('location: index.php?act=bills');
        exit();
    }

    $bill_id = $_GET['id'];
    $conn = connectdb();

    try {
        $conn->beginTransaction();

        // Xóa chi tiết hóa đơn
        $sql = "DELETE FROM tbl_hoadon_chitiet WHERE hoadon_id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':id', $bill_id);
        $stmt->execute();

        // Xóa hóa đơn
        $sql = "DELETE FROM tbl_hoadon WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':id', $bill_id);
        $stmt->execute();

        $conn->commit();

        $_SESSION['success_message'] = "Đã xóa hóa đơn thành công!";
    } catch (PDOException $e) {
        $conn->rollback();
        $_SESSION['error_message'] = "Lỗi khi xóa hóa đơn: " . $e->getMessage();
    }

    header('location: index.php?act=bills');
    exit();
}

// Xử lý xóa thời gian làm việc (chỉ admin)
if (isset($_GET['act']) && $_GET['act'] == 'delete_worktime' && isset($_GET['id'])) {
    // Kiểm tra quyền admin
    if (!isset($_SESSION['role']) || $_SESSION['role'] != 1) {
        $_SESSION['error_message'] = "Bạn không có quyền xóa thời gian làm việc!";
        header('location: index.php?act=working_time');
        exit();
    }

    $worktime_id = $_GET['id'];
    $conn = connectdb();

    try {
        // Xóa thời gian làm việc
        $sql = "DELETE FROM tbl_working_time WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':id', $worktime_id);

        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Đã xóa thời gian làm việc thành công!";
        } else {
            $_SESSION['error_message'] = "Lỗi khi xóa thời gian làm việc!";
        }
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Lỗi database: " . $e->getMessage();
    }

    header('location: index.php?act=working_time');
    exit();
}

$page_title = "Best Coffee";
$act = isset($_GET['act']) ? $_GET['act'] : 'home';

if (isset($_GET['act']) && $_GET['act'] == 'ajax_menu') {
    $iddm = isset($_GET['iddm']) ? intval($_GET['iddm']) : 0;
    // Lấy sản phẩm theo $iddm
    // $dssp = ... (code lấy sản phẩm theo danh mục)
    include 'views/ajax_product_list.php';
    exit;
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo $page_title; ?></title>
    <!--link font awesome for icons-->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css " />
    <link rel="stylesheet" href="../form/style.css" />
    <link rel="stylesheet" href="../form/css/main.css" />
    <!-- Thêm jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
</head>

<body>
    <!-- Header / navbar -->
    <header>
        <nav class="navbar section-content">
            <a href="index.php" class="nav-logo">
                <h2 class="logo-text">Best Coffee ☕</h2>
            </a>
            <ul class="nav-menu">
                <button id="menu-close-button" class="fas fa-times"></button>

                <li class="nav-item">
                    <a href="index.php" class="nav-link">Trang Chủ</a>
                </li>
                <li class="nav-item">
                    <a href="index.php?act=menu" class="nav-link">Menu</a>
                </li>
                <li class="nav-item">
                    <a href="index.php?act=cart" class="nav-link">
                        Giỏ Hàng
                    </a>
                </li>
                <li class="nav-item">
                    <a href="index.php?act=bills" class="nav-link">Lịch Sử</a>
                </li>
                <li class="nav-item">
                    <a href="index.php?act=staff_orders" class="nav-link">Quản Lý</a>
                </li>
                <li class="nav-item">
                    <a href="index.php?act=working_time" class="nav-link">Thời Gian</a>
                </li>
                <li class="nav-item">
                    <a href="index.php?act=thongke_ngay" class="nav-link">Tổng Kết</a>
                </li>
                <li class="nav-item">
                    <?php
                    if (isset($_SESSION['role']) && ($_SESSION['role'] == 0 || $_SESSION['role'] == 1)) {
                        echo '<a href="#" class="nav-link">' . $_SESSION['username'] . '</a>';
                        echo '</li><li class="nav-item"><a href="index.php?act=logout" class="nav-link">Đăng Xuất</a>';
                    } else {
                        echo '<a href="../../admin/login.php" class="nav-link">Đăng Nhập</a>';
                    }
                    ?>
                </li>
            </ul>

            <button id="menu-open-button" class="fas fa-bars"></button>
        </nav>
        <!-- Thêm phần hiển thị thời gian -->
        <div class="current-time-container">
            <div id="current-time" class="current-time"></div>
        </div>
    </header>

    <main>
        <?php
        // Hiển thị nội dung dựa trên hành động
        switch ($act) {
            case 'menu':
                include "views/menu.php";
                break;

            case 'cart':
                include "views/cart.php";
                break;

            case 'hoadon':
                include "views/hoadon.php";
                break;

            case 'bills':
                include "views/bills.php";
                break;

            case 'working_time':
                include "views/working_time.php";
                break;

            case 'thongke_ngay':
                include "views/thongke_ngay.php";
                break;

            case 'thongke_sanpham':
                include "views/thongke_sanpham.php";
                break;

            case 'staff_orders':
                include "views/staff_orders_view.php";
                break;

            case 'get_bill_details':
                if (isset($_GET['id'])) {
                    $bill_id = $_GET['id'];
                    // Debug info
                    error_log("Processing get_bill_details for bill ID: " . $bill_id);
                    include "views/get_bill_details.php";
                } else {
                    // Debug info
                    error_log("get_bill_details was called without an ID");
                    echo "Không có mã hóa đơn được cung cấp";
                }
                break;

            case 'edit_bill_details':
                if (isset($_GET['id'])) {
                    $bill_id = $_GET['id'];
                    // Debug info
                    error_log("Processing edit_bill_details for bill ID: " . $bill_id);
                    include "views/edit_bill_details.php";
                } else {
                    // Debug info
                    error_log("edit_bill_details was called without an ID");
                    echo "Không có mã hóa đơn được cung cấp";
                }
                break;

            default:
                // Trang chủ
                include "views/home.php";
                break;
        }
        ?>
    </main>

    <footer class="footer">
        <div class="section-content">
            <div class="footer-content">
                <p>&copy; 2025 Best Coffee. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="../form/script.js"></script>
    <!-- Thêm JavaScript để hiển thị thời gian -->
    <script>
    $(document).ready(function() {
        let serverTimeOffset = 0;

        // Hàm lấy thời gian server
        function getServerTime() {
            $.ajax({
                url: 'get_server_time.php',
                method: 'GET',
                success: function(response) {
                    const serverTime = new Date(response.server_time).getTime();
                    const clientTime = Date.now();
                    serverTimeOffset = serverTime - clientTime;
                }
            });
        }

        // Lấy thời gian server khi trang được tải
        getServerTime();

        // Hàm cập nhật thời gian hiện tại
        function updateCurrentTime() {
            const now = new Date(Date.now() + serverTimeOffset);

            // Lấy giờ, phút, giây
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');

            // Lấy ngày, tháng, năm
            const day = String(now.getDate()).padStart(2, '0');
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const year = now.getFullYear();

            // Tạo chuỗi thời gian đầy đủ
            const timeString = `${hours}:${minutes}:${seconds} - ${day}/${month}/${year}`;

            // Hiển thị thời gian
            $('#current-time').html(timeString);

            // Kiểm tra thời gian để tự động đăng xuất
            if (hours === '00' && minutes === '00') {
                // Gửi request đăng xuất
                window.location.href = 'index.php?act=logout';
            }
        }

        // Cập nhật thời gian ngay lập tức
        updateCurrentTime();

        // Cập nhật thời gian mỗi giây
        setInterval(updateCurrentTime, 1000);

        // Đồng bộ lại thời gian server mỗi 5 phút
        setInterval(getServerTime, 300000);
    });
    </script>
    <script>
    // Debug script for viewBillDetail function
    window.viewBillDetailDebug = function(billId) {
        console.log('Calling viewBillDetail with ID:', billId);
        var ajaxUrl = 'index.php?act=get_bill_details&id=' + billId;
        console.log('Ajax URL:', ajaxUrl);

        $.ajax({
            url: ajaxUrl,
            type: 'GET',
            beforeSend: function() {
                console.log('AJAX request starting...');
            },
            success: function(response) {
                console.log('AJAX success, response length:', response.length);
                console.log('Response preview:', response.substring(0, 100) + '...');
                $('#bill-detail-content').html(response);
                $('#bill-detail-modal').show();
            },
            error: function(xhr, status, error) {
                console.error('AJAX error - Status:', status);
                console.error('AJAX error - Error:', error);
                console.error('AJAX error - Response:', xhr.responseText);
                alert('Có lỗi xảy ra khi lấy chi tiết hóa đơn: ' + error);
                $('#bill-detail-content').html("Lỗi khi tải thông tin: " + error);
                $('#bill-detail-modal').show();
            }
        });
    }
    </script>
    <script>
    // Nếu có đoạn mã thêm nút "X", hãy xóa nó
    const closeButton = document.querySelector('.cart-alert .close-alert');
    if (closeButton) {
        closeButton.remove();
    }
    </script>
    <script>
    // Tự động ẩn thông báo sau 5 giây
    setTimeout(() => {
        const alert = document.querySelector('.alert');
        if (alert) {
            alert.classList.add('hide'); // Thêm class "hide" để kích hoạt hiệu ứng mờ dần
        }
    }, 5000); // 5000ms = 5 giây
    </script>
    <script>
    // Đảm bảo menu danh mục hoạt động đúng khi trang được tải
    document.addEventListener('DOMContentLoaded', function() {
        const categoryMenu = document.getElementById('category-menu');
        if (categoryMenu) {
            // Đặt lại position để đảm bảo CSS sticky hoạt động
            setTimeout(function() {
                // Đảm bảo menu đã được tạo và sẵn sàng
                const stickySupport = CSS.supports('position', 'sticky') ||
                    CSS.supports('position', '-webkit-sticky');

                if (stickySupport) {
                    // Đảm bảo thanh danh mục không bị trôi và luôn hiển thị trong viewport
                    const header = document.querySelector('.header');
                    const headerHeight = header ? header.offsetHeight : 0;

                    categoryMenu.style.top = (headerHeight + 20) + 'px';
                    categoryMenu.style.maxHeight = 'calc(100vh - ' + (headerHeight + 40) + 'px)';

                    // Đặt lại vị trí để kích hoạt sticky
                    categoryMenu.style.position = 'relative';
                    setTimeout(() => {
                        categoryMenu.style.position = 'sticky';
                    }, 10);
                }
            }, 100);
        }
    });
    </script>
    <style>
    .alert.hide {
        opacity: 0;
        transform: translateX(100%);
    }

    .footer-content {
        text-align: center;
        padding: 20px 0;
        background-color: #f8f9fa;
        width: 100%;
    }

    .footer-content p {
        margin: 0;
        color: #666;
        font-size: 14px;
    }
    </style>
</body>

</html>