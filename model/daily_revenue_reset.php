<?php
require_once 'connectdb.php';
// Lấy tổng doanh thu, tổng đơn, tổng trạng thái trong ngày
function get_daily_revenue()
{
    $conn = connectdb();
    $today = date('Y-m-d');
    $stmt = $conn->prepare("
        SELECT 
            COUNT(*) as total_orders,
            IFNULL(SUM(total_amount), 0) as total_revenue,
            COUNT(CASE WHEN status = 'received' THEN 1 ELSE NULL END) as completed_orders,
            SUM(CASE WHEN status = 'received' THEN total_amount ELSE 0 END) as completed_revenue,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
            SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed_count,
            SUM(CASE WHEN status = 'shipping' THEN 1 ELSE 0 END) as shipping_count,
            SUM(CASE WHEN status = 'received' THEN 1 ELSE 0 END) as received_count,
            SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_count
        FROM tbl_customer_orders
        WHERE order_type = 'online' AND DATE(order_date) = :today
    ");
    $stmt->bindParam(':today', $today);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Lấy sản phẩm bán trong ngày
function get_daily_products()
{
    $conn = connectdb();
    $today = date('Y-m-d');
    $stmt = $conn->prepare("
        SELECT 
            p.tensp AS product_name,
            SUM(od.quantity) AS total_quantity,
            SUM(od.price * od.quantity) AS total_revenue
        FROM tbl_customer_order_details od
        JOIN tbl_sanpham p ON od.product_id = p.id
        JOIN tbl_customer_orders o ON od.order_id = o.id
        WHERE DATE(o.order_date) = :today AND o.order_type = 'online'
        GROUP BY od.product_id
    ");
    $stmt->bindParam(':today', $today);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Lấy hiệu suất nhân viên trong ngày
function get_daily_staff_performance()
{
    $conn = connectdb();
    $today = date('Y-m-d');
    $stmt = $conn->prepare("
        SELECT 
            u.user as staff_name,
            COUNT(o.id) as total_orders,
            SUM(o.total_amount) as total_revenue
        FROM tbl_customer_orders o
        JOIN tbl_user u ON o.staff_id = u.id
        WHERE DATE(o.order_date) = :today AND o.order_type = 'online'
        GROUP BY o.staff_id, u.user
    ");
    $stmt->bindParam(':today', $today);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Lấy chi tiết sản phẩm bán trong ngày (nếu cần)
function get_daily_product_details()
{
    // Có thể giống get_daily_products hoặc chi tiết hơn
    return get_daily_products();
}

// Lấy lịch sử đơn hàng trong ngày
function get_daily_order_history()
{
    $conn = connectdb();
    $today = date('Y-m-d');
    $stmt = $conn->prepare("
        SELECT 
            o.*, 
            k.hoten as customer_name, 
            u.user as staff_name
        FROM tbl_customer_orders o
        LEFT JOIN tbl_khachhang k ON o.user_id = k.id
        LEFT JOIN tbl_user u ON o.staff_id = u.id
        WHERE DATE(o.order_date) = :today AND o.order_type = 'online'
        ORDER BY o.order_date DESC
    ");
    $stmt->bindParam(':today', $today);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
