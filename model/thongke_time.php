<?php
require_once 'connectdb_thongke.php';

function getDateRange($time_range)
{
    $end_date = date('Y-m-d');

    switch ($time_range) {
        case '7days':
            $start_date = date('Y-m-d', strtotime('-6 days')); // 7 ngày tính cả ngày hiện tại
            break;
        case '1month':
            $start_date = date('Y-m-d', strtotime('-1 month +1 day'));
            break;
        case '1year':
            $start_date = date('Y-m-d', strtotime('-1 year +1 day'));
            break;
        case 'all':
            $start_date = '2025-01-01'; // Bắt đầu từ 1/1/2025
            break;
        default:
            $start_date = $end_date; // Mặc định là ngày hiện tại
    }

    return array($start_date, $end_date);
}

function getThongKeTheoThoiGian($time_range, $user_id = null)
{
    $conn = connectdb_thongke();
    list($start_date, $end_date) = getDateRange($time_range);

    // Query cơ bản
    $base_query = "
        SELECT 
            COUNT(DISTINCT h.id) as tong_don_hang,
            COALESCE(SUM(hc.so_luong), 0) as tong_san_pham,
            COALESCE(SUM(hc.gia * hc.so_luong), 0) as tong_doanh_thu
        FROM tbl_hoadon AS h
        LEFT JOIN tbl_hoadon_chitiet AS hc ON h.id = hc.hoadon_id
        WHERE DATE(h.ngay_tao) BETWEEN :start_date AND :end_date
        AND h.trang_thai = 'Đã thanh toán'
    ";

    // Thêm điều kiện user_id nếu không phải admin
    if ($user_id !== null) {
        $base_query .= " AND h.user_id = :user_id";
    }

    $stmt = $conn->prepare($base_query);
    $stmt->bindParam(':start_date', $start_date);
    $stmt->bindParam(':end_date', $end_date);
    if ($user_id !== null) {
        $stmt->bindParam(':user_id', $user_id);
    }
    $stmt->execute();

    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getChiTietSanPhamTheoThoiGian($time_range, $user_id = null)
{
    $conn = connectdb_thongke();
    list($start_date, $end_date) = getDateRange($time_range);

    // Query cơ bản
    $base_query = "
        SELECT 
            hc.sanpham_id,
            hc.ten_sp,
            SUM(hc.so_luong) as so_luong,
            SUM(hc.gia * hc.so_luong) as doanh_thu
        FROM tbl_hoadon AS h
        JOIN tbl_hoadon_chitiet AS hc ON h.id = hc.hoadon_id
        WHERE DATE(h.ngay_tao) BETWEEN :start_date AND :end_date
        AND h.trang_thai = 'Đã thanh toán'
    ";

    // Thêm điều kiện user_id nếu không phải admin
    if ($user_id !== null) {
        $base_query .= " AND h.user_id = :user_id";
    }

    $base_query .= " GROUP BY hc.sanpham_id, hc.ten_sp ORDER BY doanh_thu DESC";

    try {
        $stmt = $conn->prepare($base_query);
        $stmt->bindParam(':start_date', $start_date);
        $stmt->bindParam(':end_date', $end_date);
        if ($user_id !== null) {
            $stmt->bindParam(':user_id', $user_id);
        }
        $stmt->execute();

        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Tính tổng số lượng và doanh thu
        $total_quantity = 0;
        $total_revenue = 0;
        foreach ($result as $item) {
            $total_quantity += $item['so_luong'];
            $total_revenue += $item['doanh_thu'];
        }

        return array(
            'items' => $result,
            'total_quantity' => $total_quantity,
            'total_revenue' => $total_revenue
        );
    } catch (PDOException $e) {
        error_log("Lỗi truy vấn chi tiết sản phẩm: " . $e->getMessage());
        return array(
            'items' => array(),
            'total_quantity' => 0,
            'total_revenue' => 0
        );
    }
}

function getThongKeNhanVienTheoThoiGian($time_range)
{
    $conn = connectdb_thongke();
    list($start_date, $end_date) = getDateRange($time_range);

    $query = "
        SELECT 
            h.user_id,
            u.user as username,
            COUNT(DISTINCT h.id) as tong_don_hang,
            COALESCE(SUM(hc.so_luong), 0) as tong_san_pham,
            COALESCE(SUM(hc.gia * hc.so_luong), 0) as tong_doanh_thu
        FROM tbl_hoadon AS h
        LEFT JOIN tbl_hoadon_chitiet AS hc ON h.id = hc.hoadon_id
        JOIN tbl_user AS u ON h.user_id = u.id
        WHERE DATE(h.ngay_tao) BETWEEN :start_date AND :end_date
        AND h.trang_thai = 'Đã thanh toán'
        GROUP BY h.user_id, u.user
        ORDER BY tong_doanh_thu DESC
    ";

    $stmt = $conn->prepare($query);
    $stmt->bindParam(':start_date', $start_date);
    $stmt->bindParam(':end_date', $end_date);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
