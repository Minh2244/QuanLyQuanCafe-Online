<?php
function connectdb_thongke()
{
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "product";

    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $conn;
    } catch (PDOException $e) {
        echo "Connection failed: " . $e->getMessage();
        return null;
    }
}

// Hàm lấy thống kê tổng hợp theo ngày
function getThongKeNgay($ngay, $user_id = null)
{
    $conn = connectdb_thongke();

    if ($user_id === null) {
        // Trường hợp admin - lấy tất cả
        $stmt = $conn->prepare("
            SELECT COUNT(DISTINCT h.id) as tong_don_hang,
                   SUM(hc.so_luong) as tong_san_pham,
                   SUM(hc.gia * hc.so_luong) as tong_doanh_thu
            FROM tbl_hoadon AS h
            JOIN tbl_hoadon_chitiet AS hc ON h.id = hc.hoadon_id
            WHERE DATE(h.ngay_tao) = :ngay
            AND h.trang_thai = 'Đã thanh toán'
        ");
        $stmt->bindParam(':ngay', $ngay);
    } else {
        // Trường hợp nhân viên - lấy theo user_id
        $stmt = $conn->prepare("
            SELECT COUNT(DISTINCT h.id) as tong_don_hang,
                   SUM(hc.so_luong) as tong_san_pham,
                   SUM(hc.gia * hc.so_luong) as tong_doanh_thu
            FROM tbl_hoadon AS h
            JOIN tbl_hoadon_chitiet AS hc ON h.id = hc.hoadon_id
            WHERE DATE(h.ngay_tao) = :ngay
            AND h.trang_thai = 'Đã thanh toán'
            AND h.user_id = :user_id
        ");
        $stmt->bindParam(':ngay', $ngay);
        $stmt->bindParam(':user_id', $user_id);
    }

    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Hàm lấy thống kê chi tiết sản phẩm
function getThongKeSanPham($ngay, $user_id = null)
{
    $conn = connectdb_thongke();

    if ($user_id === null) {
        // Trường hợp admin - lấy tất cả
        $stmt = $conn->prepare("
            SELECT hc.sanpham_id, hc.ten_sp,
                   SUM(hc.so_luong) as so_luong,
                   SUM(hc.gia * hc.so_luong) as doanh_thu
            FROM tbl_hoadon AS h
            JOIN tbl_hoadon_chitiet AS hc ON h.id = hc.hoadon_id
            WHERE DATE(h.ngay_tao) = :ngay
            AND h.trang_thai = 'Đã thanh toán'
            GROUP BY hc.sanpham_id, hc.ten_sp
            ORDER BY doanh_thu DESC
        ");
        $stmt->bindParam(':ngay', $ngay);
    } else {
        // Trường hợp nhân viên - lấy theo user_id
        $stmt = $conn->prepare("
            SELECT hc.sanpham_id, hc.ten_sp,
                   SUM(hc.so_luong) as so_luong,
                   SUM(hc.gia * hc.so_luong) as doanh_thu
            FROM tbl_hoadon AS h
            JOIN tbl_hoadon_chitiet AS hc ON h.id = hc.hoadon_id
            WHERE DATE(h.ngay_tao) = :ngay
            AND h.trang_thai = 'Đã thanh toán'
            AND h.user_id = :user_id
            GROUP BY hc.sanpham_id, hc.ten_sp
            ORDER BY doanh_thu DESC
        ");
        $stmt->bindParam(':ngay', $ngay);
        $stmt->bindParam(':user_id', $user_id);
    }

    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Hàm lấy thống kê theo nhân viên
function getThongKeNhanVien($ngay)
{
    $conn = connectdb_thongke();

    $stmt = $conn->prepare("
        SELECT h.user_id, u.user as username,
               COUNT(DISTINCT h.id) as tong_don_hang,
               SUM(hc.so_luong) as tong_san_pham,
               SUM(hc.gia * hc.so_luong) as tong_doanh_thu
        FROM tbl_hoadon AS h
        JOIN tbl_hoadon_chitiet AS hc ON h.id = hc.hoadon_id
        JOIN tbl_user AS u ON h.user_id = u.id
        WHERE DATE(h.ngay_tao) = :ngay
        AND h.trang_thai = 'Đã thanh toán'
        GROUP BY h.user_id, u.user
        ORDER BY tong_doanh_thu DESC
    ");
    $stmt->bindParam(':ngay', $ngay);

    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function thongke_ngay($id)
{
    $conn = connectdb();    //lay du lieu database
    $sql = "DELETE FROM tbl_thongke_ngay WHERE id=$id";
    // use exec() because no results are returned
    $conn->exec($sql);
}

function thongke_sanpham($id)
{
    $conn = connectdb();    //lay du lieu database
    $sql = "DELETE FROM tbl_thongke_sanpham WHERE id=$id";
    // use exec() because no results are returned
    $conn->exec($sql);
}
