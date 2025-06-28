<?php
// Kiểm tra đơn hàng mới (sử dụng hàm từ file count_new_orders.php)
$new_orders_count = count_unassigned_orders();
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Best Coffee - Nhân viên</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" href="css/footer.css">
    <link rel="stylesheet" href="style.css">
    <?php if (isset($_GET['act']) && $_GET['act'] == 'menu'): ?>
        <link rel="stylesheet" href="css/menu.css">
    <?php else: ?>
        <link rel="stylesheet" href="css/layout.css">
    <?php endif; ?>
    <style>
        /* Đảm bảo nội dung chính có khoảng cách với header */
        .content {
            padding-top: 20px;
        }

        /* Reset một số thuộc tính CSS mặc định có thể gây xung đột */
        * {
            box-sizing: border-box;
        }

        /* Đảm bảo header có z-index cao để không bị che khuất */
        .header {
            z-index: 100;
            position: relative;
        }

        /* Đảm bảo body và html có chiều cao đủ */
        html,
        body {
            height: 100%;
            margin: 0;
            padding: 0;
        }
    </style>
    <script src="jquery-3.7.1.min.js"></script>
</head>

<body>
    <div class="header">
        <div class="logo">
            <h1>Best Coffee ☕</h1>
        </div>
        <div class="nav">
            <ul>
                <li><a href="index.php">Trang chủ</a></li>
                <li><a href="index.php?act=menu">Menu</a></li>
                <li><a href="index.php?act=cart">Giỏ hàng</a></li>
                <li><a href="staff_orders.php" class="highlight-menu">
                        <i class="fas fa-clipboard-list"></i> Quản lý đơn hàng
                        <?php if ($new_orders_count > 0): ?>
                            <span class="badge order-count"><?php echo $new_orders_count; ?></span>
                        <?php endif; ?>
                    </a></li>
                <li><a href="index.php?act=logout">Đăng xuất</a></li>
            </ul>
        </div>
        <div class="user-info">
            <span>Xin chào, <?php echo $_SESSION['username']; ?></span>
        </div>
    </div>

    <div class="content">
        <?php if ($new_orders_count > 0): ?>
            <div class="notification-banner">
                <div class="notification-content">
                    <i class="fas fa-bell"></i>
                    <span>Có <span class="new-orders-count"><?php echo $new_orders_count; ?></span> đơn hàng mới cần xử
                        lý!</span>
                </div>
                <a href="staff_orders.php" class="view-orders-btn">Xem đơn hàng</a>
                <button class="notification-close">&times;</button>
            </div>
        <?php endif; ?>