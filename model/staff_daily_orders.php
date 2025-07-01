<?php
require_once '../model/staff_daily_orders.php';

function get_staff_daily_revenue($staff_id)
{
    $conn = connectdb();
    $today = date('Y-m-d');
    $stmt = $conn->prepare("SELECT COUNT(*) as total_orders, SUM(total_amount) as total_revenue FROM tbl_customer_orders WHERE staff_id = ? AND DATE(order_date) = ?");
    $stmt->execute([$staff_id, $today]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function get_staff_daily_products($staff_id)
{
    $conn = connectdb();
    $today = date('Y-m-d');
    $stmt = $conn->prepare("
        SELECT p.tensp as product_name, SUM(od.quantity) as total_quantity, SUM(od.price * od.quantity) as total_revenue
        FROM tbl_customer_orders o
        JOIN tbl_customer_order_details od ON o.id = od.order_id
        JOIN tbl_sanpham p ON od.product_id = p.id
        WHERE o.staff_id = ? AND DATE(o.order_date) = ?
        GROUP BY p.id, p.tensp
    ");
    $stmt->execute([$staff_id, $today]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function get_staff_daily_order_history($staff_id)
{
    $conn = connectdb();
    $today = date('Y-m-d');
    $stmt = $conn->prepare("
        SELECT o.*, k.hoten as customer_name
        FROM tbl_customer_orders o
        LEFT JOIN tbl_khachhang k ON o.user_id = k.id
        WHERE o.staff_id = ? AND DATE(o.order_date) = ?
        ORDER BY o.order_date DESC
    ");
    $stmt->execute([$staff_id, $today]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function get_pending_orders_for_staff()
{
    $conn = connectdb();
    $stmt = $conn->prepare("
        SELECT o.*, k.hoten as customer_name
        FROM tbl_customer_orders o
        LEFT JOIN tbl_khachhang k ON o.user_id = k.id
        WHERE o.status = 'pending' AND (o.staff_id IS NULL OR o.staff_id = 0)
        ORDER BY o.order_date ASC
    ");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
