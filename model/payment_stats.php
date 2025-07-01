<?php

/**
 * Lấy thống kê doanh thu theo phương thức thanh toán tại quầy
 * @param string $date Ngày thống kê (định dạng Y-m-d)
 * @param int $staff_id ID của nhân viên (null nếu là admin)
 * @return array Thống kê doanh thu theo phương thức thanh toán
 */
function get_counter_payment_stats($date = null, $staff_id = null)
{
    if (!$date) {
        $date = date('Y-m-d');
    }

    $conn = connectdb();

    // Lấy tổng doanh thu tiền mặt (cash hoặc cod) từ tbl_hoadon (đơn hàng tại quầy)
    $sql = "
        SELECT COALESCE(SUM(tong_tien), 0) as tong_tien
        FROM tbl_hoadon
        WHERE DATE(ngay_tao) = :date
        AND trang_thai = 'Đã thanh toán'
        AND (payment_method = 'cash' OR payment_method = 'cod')
    ";

    // Nếu có ID nhân viên, thêm điều kiện lọc
    if ($staff_id) {
        $sql .= " AND user_id = :staff_id";
    }

    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':date', $date);

    if ($staff_id) {
        $stmt->bindParam(':staff_id', $staff_id);
    }

    $stmt->execute();
    $cash_result = $stmt->fetch(PDO::FETCH_ASSOC);
    $cash_amount = $cash_result['tong_tien'] ?? 0;

    // Lấy tổng doanh thu chuyển khoản (bank hoặc transfer) từ tbl_hoadon (đơn hàng tại quầy)
    $sql = "
        SELECT COALESCE(SUM(tong_tien), 0) as tong_tien
        FROM tbl_hoadon
        WHERE DATE(ngay_tao) = :date
        AND trang_thai = 'Đã thanh toán'
        AND (payment_method = 'bank' OR payment_method = 'transfer')
    ";

    // Nếu có ID nhân viên, thêm điều kiện lọc
    if ($staff_id) {
        $sql .= " AND user_id = :staff_id";
    }

    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':date', $date);

    if ($staff_id) {
        $stmt->bindParam(':staff_id', $staff_id);
    }

    $stmt->execute();
    $bank_result = $stmt->fetch(PDO::FETCH_ASSOC);
    $bank_amount = $bank_result['tong_tien'] ?? 0;

    // Tổng doanh thu tại quầy
    $total_amount = $cash_amount + $bank_amount;

    return [
        'cash' => $cash_amount,
        'bank' => $bank_amount,
        'total' => $total_amount,
        'date' => $date
    ];
}

/**
 * Lấy thống kê doanh thu theo phương thức thanh toán online
 * @param string $date Ngày thống kê (định dạng Y-m-d)
 * @param int $staff_id ID của nhân viên (null nếu là admin)
 * @return array Thống kê doanh thu theo phương thức thanh toán
 */
function get_online_payment_stats($date = null, $staff_id = null)
{
    if (!$date) {
        $date = date('Y-m-d');
    }

    $conn = connectdb();

    // Lấy tổng doanh thu tiền mặt (cod) từ tbl_customer_orders (đơn hàng online)
    $sql = "
        SELECT COALESCE(SUM(total_amount), 0) as tong_tien
        FROM tbl_customer_orders
        WHERE DATE(updated_at) = :date
        AND status = 'received'
        AND payment_method = 'cod'
    ";

    // Nếu có ID nhân viên, thêm điều kiện lọc
    if ($staff_id) {
        $sql .= " AND staff_id = :staff_id";
    }

    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':date', $date);

    if ($staff_id) {
        $stmt->bindParam(':staff_id', $staff_id);
    }

    $stmt->execute();
    $cash_result = $stmt->fetch(PDO::FETCH_ASSOC);
    $cash_amount = $cash_result['tong_tien'] ?? 0;

    // Lấy tổng doanh thu chuyển khoản (banking hoặc bank) từ tbl_customer_orders (đơn hàng online)
    $sql = "
        SELECT COALESCE(SUM(total_amount), 0) as tong_tien
        FROM tbl_customer_orders
        WHERE DATE(updated_at) = :date
        AND status = 'received'
        AND (payment_method = 'banking' OR payment_method = 'bank')
    ";

    // Nếu có ID nhân viên, thêm điều kiện lọc
    if ($staff_id) {
        $sql .= " AND staff_id = :staff_id";
    }

    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':date', $date);

    if ($staff_id) {
        $stmt->bindParam(':staff_id', $staff_id);
    }

    $stmt->execute();
    $bank_result = $stmt->fetch(PDO::FETCH_ASSOC);
    $bank_amount = $bank_result['tong_tien'] ?? 0;

    // Tổng doanh thu online
    $total_amount = $cash_amount + $bank_amount;

    return [
        'cash' => $cash_amount,
        'bank' => $bank_amount,
        'total' => $total_amount,
        'date' => $date
    ];
}

/**
 * Lấy thống kê doanh thu tổng hợp (cả tại quầy và online)
 * @param string $date Ngày thống kê (định dạng Y-m-d)
 * @param int $staff_id ID của nhân viên (null nếu là admin)
 * @return array Thống kê doanh thu theo phương thức thanh toán
 */
function get_total_payment_stats($date = null, $staff_id = null)
{
    if (!$date) {
        $date = date('Y-m-d');
    }

    // Lấy thống kê tại quầy
    $counter_stats = get_counter_payment_stats($date, $staff_id);

    // Lấy thống kê online
    $online_stats = get_online_payment_stats($date, $staff_id);

    // Tổng hợp
    return [
        'cash' => $counter_stats['cash'] + $online_stats['cash'],
        'bank' => $counter_stats['bank'] + $online_stats['bank'],
        'total' => $counter_stats['total'] + $online_stats['total'],
        'counter_cash' => $counter_stats['cash'],
        'counter_bank' => $counter_stats['bank'],
        'counter_total' => $counter_stats['total'],
        'online_cash' => $online_stats['cash'],
        'online_bank' => $online_stats['bank'],
        'online_total' => $online_stats['total'],
        'date' => $date
    ];
}

/**
 * Lấy thống kê doanh thu theo phương thức thanh toán tại quầy theo khoảng thời gian
 * @param string $start_date Ngày bắt đầu (định dạng Y-m-d)
 * @param string $end_date Ngày kết thúc (định dạng Y-m-d)
 * @param int $staff_id ID của nhân viên (null nếu là admin)
 * @return array Thống kê doanh thu theo phương thức thanh toán
 */
function get_counter_payment_stats_by_range($start_date, $end_date, $staff_id = null)
{
    $conn = connectdb();

    // Lấy tổng doanh thu tiền mặt (cash hoặc cod)
    $sql = "
        SELECT COALESCE(SUM(tong_tien), 0) as tong_tien
        FROM tbl_hoadon
        WHERE DATE(ngay_tao) BETWEEN :start_date AND :end_date
        AND trang_thai = 'Đã thanh toán'
        AND (payment_method = 'cash' OR payment_method = 'cod')
    ";

    // Nếu có ID nhân viên, thêm điều kiện lọc
    if ($staff_id) {
        $sql .= " AND user_id = :staff_id";
    }

    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':start_date', $start_date);
    $stmt->bindParam(':end_date', $end_date);

    if ($staff_id) {
        $stmt->bindParam(':staff_id', $staff_id);
    }

    $stmt->execute();
    $cash_result = $stmt->fetch(PDO::FETCH_ASSOC);
    $cash_amount = $cash_result['tong_tien'] ?? 0;

    // Lấy tổng doanh thu chuyển khoản (bank hoặc transfer)
    $sql = "
        SELECT COALESCE(SUM(tong_tien), 0) as tong_tien
        FROM tbl_hoadon
        WHERE DATE(ngay_tao) BETWEEN :start_date AND :end_date
        AND trang_thai = 'Đã thanh toán'
        AND (payment_method = 'bank' OR payment_method = 'transfer')
    ";

    // Nếu có ID nhân viên, thêm điều kiện lọc
    if ($staff_id) {
        $sql .= " AND user_id = :staff_id";
    }

    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':start_date', $start_date);
    $stmt->bindParam(':end_date', $end_date);

    if ($staff_id) {
        $stmt->bindParam(':staff_id', $staff_id);
    }

    $stmt->execute();
    $bank_result = $stmt->fetch(PDO::FETCH_ASSOC);
    $bank_amount = $bank_result['tong_tien'] ?? 0;

    // Tổng doanh thu
    $total_amount = $cash_amount + $bank_amount;

    return [
        'cash' => $cash_amount,
        'bank' => $bank_amount,
        'total' => $total_amount,
        'start_date' => $start_date,
        'end_date' => $end_date
    ];
}

/**
 * Lấy thống kê doanh thu theo phương thức thanh toán tại quầy theo nhân viên
 * @param int $staff_id ID của nhân viên
 * @param string $date Ngày thống kê (định dạng Y-m-d)
 * @return array Thống kê doanh thu theo phương thức thanh toán của nhân viên
 */
function get_counter_payment_stats_by_staff($staff_id, $date = null)
{
    if (!$date) {
        $date = date('Y-m-d');
    }

    $conn = connectdb();

    // Lấy tổng doanh thu tiền mặt của nhân viên (cash hoặc cod) từ tbl_hoadon (đơn hàng tại quầy)
    $stmt = $conn->prepare("
        SELECT COALESCE(SUM(tong_tien), 0) as tong_tien
        FROM tbl_hoadon
        WHERE DATE(ngay_tao) = :date
        AND trang_thai = 'Đã thanh toán'
        AND (payment_method = 'cash' OR payment_method = 'cod')
        AND user_id = :staff_id
    ");
    $stmt->bindParam(':date', $date);
    $stmt->bindParam(':staff_id', $staff_id);
    $stmt->execute();
    $cash_result = $stmt->fetch(PDO::FETCH_ASSOC);
    $cash_amount = $cash_result['tong_tien'] ?? 0;

    // Lấy tổng doanh thu chuyển khoản của nhân viên (bank hoặc transfer) từ tbl_hoadon (đơn hàng tại quầy)
    $stmt = $conn->prepare("
        SELECT COALESCE(SUM(tong_tien), 0) as tong_tien
        FROM tbl_hoadon
        WHERE DATE(ngay_tao) = :date
        AND trang_thai = 'Đã thanh toán'
        AND (payment_method = 'bank' OR payment_method = 'transfer')
        AND user_id = :staff_id
    ");
    $stmt->bindParam(':date', $date);
    $stmt->bindParam(':staff_id', $staff_id);
    $stmt->execute();
    $bank_result = $stmt->fetch(PDO::FETCH_ASSOC);
    $bank_amount = $bank_result['tong_tien'] ?? 0;

    // Lấy tổng doanh thu tiền mặt của nhân viên (cod) từ tbl_customer_orders (đơn hàng online)
    $stmt = $conn->prepare("
        SELECT COALESCE(SUM(total_amount), 0) as tong_tien
        FROM tbl_customer_orders
        WHERE DATE(updated_at) = :date
        AND status = 'received'
        AND payment_method = 'cod'
        AND staff_id = :staff_id
    ");
    $stmt->bindParam(':date', $date);
    $stmt->bindParam(':staff_id', $staff_id);
    $stmt->execute();
    $online_cash_result = $stmt->fetch(PDO::FETCH_ASSOC);
    $online_cash_amount = $online_cash_result['tong_tien'] ?? 0;

    // Lấy tổng doanh thu chuyển khoản của nhân viên (banking hoặc bank) từ tbl_customer_orders (đơn hàng online)
    $stmt = $conn->prepare("
        SELECT COALESCE(SUM(total_amount), 0) as tong_tien
        FROM tbl_customer_orders
        WHERE DATE(updated_at) = :date
        AND status = 'received'
        AND (payment_method = 'banking' OR payment_method = 'bank')
        AND staff_id = :staff_id
    ");
    $stmt->bindParam(':date', $date);
    $stmt->bindParam(':staff_id', $staff_id);
    $stmt->execute();
    $online_bank_result = $stmt->fetch(PDO::FETCH_ASSOC);
    $online_bank_amount = $online_bank_result['tong_tien'] ?? 0;

    // Tổng doanh thu của nhân viên
    $counter_total = $cash_amount + $bank_amount;
    $online_total = $online_cash_amount + $online_bank_amount;
    $total_amount = $counter_total + $online_total;

    // Lấy tên nhân viên
    $stmt = $conn->prepare("SELECT user FROM tbl_user WHERE id = :staff_id");
    $stmt->bindParam(':staff_id', $staff_id);
    $stmt->execute();
    $staff_result = $stmt->fetch(PDO::FETCH_ASSOC);
    $staff_name = $staff_result['user'] ?? 'Không xác định';

    return [
        'staff_id' => $staff_id,
        'staff_name' => $staff_name,
        'cash' => $cash_amount,
        'bank' => $bank_amount,
        'counter_total' => $counter_total,
        'online_cash' => $online_cash_amount,
        'online_bank' => $online_bank_amount,
        'online_total' => $online_total,
        'total_cash' => $cash_amount + $online_cash_amount,
        'total_bank' => $bank_amount + $online_bank_amount,
        'total' => $total_amount,
        'date' => $date
    ];
}
