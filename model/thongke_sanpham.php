<?php
require_once 'connectdb.php';

/**
 * Lấy chi tiết sản phẩm đã bán tại quầy
 */
function get_counter_sales_details($date = null, $staff_id = null)
{
    if (!$date) {
        $date = date('Y-m-d');
    }

    $conn = connectdb();

    $sql = "
        SELECT 
            sp.name as ten_sp,
            sp.img as hinh_anh,
            cthd.soluong as so_luong,
            cthd.dongia as don_gia,
            (cthd.soluong * cthd.dongia) as thanh_tien,
            hd.payment_method as hinh_thuc_thanh_toan,
            hd.trang_thai as trang_thai,
            u.user as nhan_vien,
            hd.ngay_tao as ngay_ban
        FROM tbl_chitiethoadon cthd
        JOIN tbl_hoadon hd ON cthd.id_hoadon = hd.id
        JOIN tbl_sanpham sp ON cthd.id_sanpham = sp.id
        LEFT JOIN tbl_user u ON hd.user_id = u.id
        WHERE DATE(hd.ngay_tao) = :date
        AND hd.trang_thai = 'Đã thanh toán'
    ";

    if ($staff_id) {
        $sql .= " AND hd.user_id = :staff_id";
    }

    $sql .= " ORDER BY hd.ngay_tao DESC";

    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':date', $date);

    if ($staff_id) {
        $stmt->bindParam(':staff_id', $staff_id);
    }

    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Lấy chi tiết sản phẩm đã bán online
 */
function get_online_sales_details($date = null, $staff_id = null)
{
    if (!$date) {
        $date = date('Y-m-d');
    }

    $conn = connectdb();

    $sql = "
        SELECT 
            sp.name as ten_sp,
            sp.img as hinh_anh,
            cod.quantity as so_luong,
            cod.price as don_gia,
            (cod.quantity * cod.price) as thanh_tien,
            co.payment_method as hinh_thuc_thanh_toan,
            co.status as trang_thai,
            u.user as nhan_vien,
            co.order_date as ngay_dat,
            co.updated_at as ngay_cap_nhat
        FROM tbl_customer_order_details cod
        JOIN tbl_customer_orders co ON cod.order_id = co.id
        JOIN tbl_sanpham sp ON cod.product_id = sp.id
        LEFT JOIN tbl_user u ON co.staff_id = u.id
        WHERE DATE(co.updated_at) = :date
        AND co.status = 'received'
    ";

    if ($staff_id) {
        $sql .= " AND co.staff_id = :staff_id";
    }

    $sql .= " ORDER BY co.updated_at DESC";

    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':date', $date);

    if ($staff_id) {
        $stmt->bindParam(':staff_id', $staff_id);
    }

    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Lấy thống kê theo trạng thái đơn hàng tại quầy
 */
function get_counter_status_stats($date = null, $staff_id = null)
{
    if (!$date) {
        $date = date('Y-m-d');
    }

    $conn = connectdb();

    $sql = "
        SELECT 
            trang_thai,
            COUNT(*) as so_luong,
            SUM(tong_tien) as tong_tien
        FROM tbl_hoadon
        WHERE DATE(ngay_tao) = :date
    ";

    if ($staff_id) {
        $sql .= " AND user_id = :staff_id";
    }

    $sql .= " GROUP BY trang_thai";

    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':date', $date);

    if ($staff_id) {
        $stmt->bindParam(':staff_id', $staff_id);
    }

    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Lấy thống kê theo trạng thái đơn hàng online
 */
function get_online_status_stats($date = null, $staff_id = null)
{
    if (!$date) {
        $date = date('Y-m-d');
    }

    $conn = connectdb();

    $sql = "
        SELECT 
            status as trang_thai,
            COUNT(*) as so_luong,
            SUM(total_amount) as tong_tien
        FROM tbl_customer_orders
        WHERE DATE(updated_at) = :date
    ";

    if ($staff_id) {
        $sql .= " AND staff_id = :staff_id";
    }

    $sql .= " GROUP BY status";

    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':date', $date);

    if ($staff_id) {
        $stmt->bindParam(':staff_id', $staff_id);
    }

    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
