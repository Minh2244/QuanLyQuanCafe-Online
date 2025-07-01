<?php
require_once 'connectdb.php';

// Lấy tất cả đơn hàng khách
function get_all_orders()
{
    $conn = connectdb();
    $stmt = $conn->prepare("SELECT * FROM tbl_customer_orders ORDER BY order_date DESC");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Cập nhật trạng thái đơn hàng
function update_order_status($order_id, $status, $staff_id, $notes = null)
{
    $conn = connectdb();
    $sql = "UPDATE tbl_customer_orders SET status = ?, staff_id = ?, notes = ?, updated_at = NOW() WHERE id = ?";
    $stmt = $conn->prepare($sql);
    return $stmt->execute([$status, $staff_id, $notes, $order_id]);
}

// Hiển thị trạng thái đơn hàng
function get_order_status_text($status)
{
    $arr = [
        'pending' => 'Chờ xác nhận',
        'confirmed' => 'Đã xác nhận',
        'shipping' => 'Đang giao',
        'received' => 'Đã nhận hàng',
        'cancelled' => 'Đã hủy'
    ];
    return $arr[$status] ?? $status;
}

// Lấy danh sách đơn hàng online theo ngày
function get_online_orders_by_date($date)
{
    $conn = connectdb();
    $stmt = $conn->prepare("
        SELECT 
            COUNT(CASE WHEN status = 'received' THEN 1 ELSE NULL END) as completed_orders,
            SUM(CASE WHEN status = 'received' THEN total_amount ELSE 0 END) as completed_revenue,
            COUNT(*) as total_orders,
            SUM(total_amount) as total_revenue,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
            SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed_count,
            SUM(CASE WHEN status = 'shipping' THEN 1 ELSE 0 END) as shipping_count,
            SUM(CASE WHEN status = 'received' THEN 1 ELSE 0 END) as received_count,
            SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_count
        FROM tbl_customer_orders
        WHERE order_type = 'online' AND DATE(order_date) = :date
    ");
    $stmt->bindParam(':date', $date);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Lấy danh sách sản phẩm trong các đơn hàng online theo ngày
function get_online_products_by_date($date)
{
    $conn = connectdb();
    $stmt = $conn->prepare("
        SELECT 
            p.tensp AS product_name,
            SUM(od.quantity) AS total_quantity,
            SUM(od.price * od.quantity) AS total_revenue
        FROM tbl_customer_order_details od
        JOIN tbl_sanpham p ON od.product_id = p.id
        JOIN tbl_customer_orders o ON od.order_id = o.id
        WHERE DATE(o.order_date) = ? AND o.status = 'received'
        GROUP BY od.product_id
    ");
    $stmt->execute([$date]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Lấy danh sách đơn hàng online theo nhân viên xử lý trong ngày
function get_online_orders_by_staff($date, $staff_id)
{
    $conn = connectdb();
    $stmt = $conn->prepare("
        SELECT *
        FROM tbl_customer_orders
        WHERE DATE(order_date) = :date AND staff_id = :staff_id
    ");
    $stmt->bindParam(':date', $date);
    $stmt->bindParam(':staff_id', $staff_id);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ALTER TABLE tbl_tbl_customer_orders ADD COLUMN staff_id INT DEFAULT NULL;

// $orders = get_online_orders_by_staff($date, $_SESSION['user_id']);

// Lấy thống kê đơn hàng theo khoảng ngày
function get_online_orders_by_date_range($start_date, $end_date)
{
    $conn = connectdb();
    $stmt = $conn->prepare("
        SELECT 
            COUNT(CASE WHEN status = 'received' THEN 1 ELSE NULL END) as completed_orders,
            SUM(CASE WHEN status = 'received' THEN total_amount ELSE 0 END) as completed_revenue,
            COUNT(*) as total_orders,
            SUM(total_amount) as total_revenue,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
            SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed_count,
            SUM(CASE WHEN status = 'shipping' THEN 1 ELSE 0 END) as shipping_count,
            SUM(CASE WHEN status = 'received' THEN 1 ELSE 0 END) as received_count,
            SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_count
        FROM tbl_customer_orders
        WHERE order_type = 'online' AND DATE(order_date) BETWEEN :start_date AND :end_date
    ");
    $stmt->bindParam(':start_date', $start_date);
    $stmt->bindParam(':end_date', $end_date);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Lấy thống kê sản phẩm bán theo khoảng ngày
function get_online_products_by_date_range($start_date, $end_date)
{
    $conn = connectdb();
    $stmt = $conn->prepare("
        SELECT 
            p.id as product_id,
            p.tensp AS product_name,
            SUM(od.quantity) as total_quantity,
            SUM(od.price * od.quantity) as total_revenue
        FROM tbl_customer_orders o
        JOIN tbl_customer_order_details od ON o.id = od.order_id
        JOIN tbl_sanpham p ON od.product_id = p.id
        WHERE DATE(o.order_date) BETWEEN :start_date AND :end_date
        AND o.status = 'received'
        GROUP BY p.id, p.tensp
    ");
    $stmt->bindParam(':start_date', $start_date);
    $stmt->bindParam(':end_date', $end_date);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Lấy thống kê đơn hàng theo nhân viên trong khoảng ngày
function get_online_orders_by_staff_range($start_date, $end_date)
{
    $conn = connectdb();
    $stmt = $conn->prepare("
        SELECT 
            u.id as staff_id,
            u.user as staff_name,
            COUNT(o.id) as total_orders,
            SUM(o.total_amount) as total_revenue
        FROM tbl_customer_orders o
        JOIN tbl_user u ON o.staff_id = u.id
        WHERE DATE(o.order_date) BETWEEN :start_date AND :end_date
        AND o.status = 'received'
        GROUP BY u.id, u.user
    ");
    $stmt->bindParam(':start_date', $start_date);
    $stmt->bindParam(':end_date', $end_date);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Lấy chi tiết sản phẩm bán theo khoảng ngày (có khách hàng và nhân viên)
function get_online_product_details_by_date_range($start_date, $end_date)
{
    $conn = connectdb();
    $stmt = $conn->prepare("
        SELECT 
            p.tensp as product_name,
            od.quantity,
            (od.price * od.quantity) as subtotal,
            c.hoten as customer_name,
            u.user as staff_name,
            o.status
        FROM tbl_customer_orders o
        JOIN tbl_customer_order_details od ON o.id = od.order_id
        JOIN tbl_sanpham p ON od.product_id = p.id
        LEFT JOIN tbl_user u ON o.staff_id = u.id
        LEFT JOIN tbl_khachhang c ON o.user_id = c.id
        WHERE DATE(o.order_date) BETWEEN :start_date AND :end_date
    ");
    $stmt->bindParam(':start_date', $start_date);
    $stmt->bindParam(':end_date', $end_date);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Lấy chi tiết sản phẩm bán theo ngày (có khách hàng và nhân viên)
function get_online_product_details_by_date($date)
{
    $conn = connectdb();
    $stmt = $conn->prepare("
        SELECT 
            sp.tensp as product_name,
            od.quantity,
            (od.price * od.quantity) as subtotal,
            c.hoten as customer_name,
            u.user as staff_name,
            o.status
        FROM tbl_customer_orders o
        JOIN tbl_customer_order_details od ON o.id = od.order_id
        JOIN tbl_sanpham sp ON od.product_id = sp.id
        LEFT JOIN tbl_user u ON o.staff_id = u.id
        LEFT JOIN tbl_khachhang c ON o.user_id = c.id
        WHERE DATE(o.order_date) = :date
    ");
    $stmt->bindParam(':date', $date);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function get_order($order_id)
{
    $conn = connectdb();
    $stmt = $conn->prepare("SELECT * FROM tbl_customer_orders WHERE id = :order_id");
    $stmt->bindParam(':order_id', $order_id, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function get_order_details($order_id)
{
    $conn = connectdb();
    $stmt = $conn->prepare("SELECT od.*, sp.tensp as product_name FROM tbl_customer_order_details od
        JOIN tbl_sanpham sp ON od.product_id = sp.id
        WHERE od.order_id = :order_id");
    $stmt->bindParam(':order_id', $order_id, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function get_new_orders()
{
    $conn = connectdb();
    $stmt = $conn->prepare("SELECT * FROM tbl_customer_orders WHERE status = 'pending' ORDER BY order_date DESC");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function get_new_orders_for_staff()
{
    $conn = connectdb();
    $stmt = $conn->prepare("SELECT * FROM tbl_customer_orders WHERE status = 'pending' ORDER BY order_date DESC");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function get_staff_processing_orders($staff_id)
{
    $conn = connectdb();
    $stmt = $conn->prepare("SELECT * FROM tbl_customer_orders WHERE staff_id = :staff_id AND status IN ('confirmed', 'shipping') ORDER BY order_date DESC");
    $stmt->bindParam(':staff_id', $staff_id, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function get_staff_completed_orders($staff_id)
{
    $conn = connectdb();
    $stmt = $conn->prepare("SELECT * FROM tbl_customer_orders WHERE staff_id = :staff_id AND status = 'received' ORDER BY order_date DESC");
    $stmt->bindParam(':staff_id', $staff_id, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function count_unassigned_orders()
{
    $conn = connectdb();
    $stmt = $conn->prepare("SELECT COUNT(*) FROM tbl_customer_orders WHERE status = 'pending' AND (staff_id IS NULL OR staff_id = 0)");
    $stmt->execute();
    return $stmt->fetchColumn();
}

function cancel_order($order_id)
{
    $conn = connectdb();
    $stmt = $conn->prepare("UPDATE tbl_customer_orders SET status = 'cancelled' WHERE id = ?");
    return $stmt->execute([$order_id]);
}

function get_online_order_status_stats($start_date, $end_date)
{
    $conn = connectdb();
    $stmt = $conn->prepare("
        SELECT 
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
            SUM(CASE WHEN status = 'shipping' THEN 1 ELSE 0 END) as shipping,
            SUM(CASE WHEN status = 'received' THEN 1 ELSE 0 END) as received,
            SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
        FROM tbl_customer_orders
        WHERE order_type = 'online' AND DATE(order_date) BETWEEN :start_date AND :end_date
    ");
    $stmt->bindParam(':start_date', $start_date);
    $stmt->bindParam(':end_date', $end_date);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Lấy tất cả đơn hàng của tất cả nhân viên trong một ngày
function get_all_staff_orders_by_date($date)
{
    $conn = connectdb();
    $stmt = $conn->prepare("
        SELECT 
            u.id as staff_id,
            u.user as staff_name,
            COUNT(o.id) as total_orders,
            SUM(o.total_amount) as total_revenue
        FROM tbl_customer_orders o
        JOIN tbl_user u ON o.staff_id = u.id
        WHERE DATE(o.order_date) = :date
        AND o.status = 'received'
        GROUP BY u.id, u.user
    ");
    $stmt->bindParam(':date', $date);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Lấy thống kê tổng hợp đơn hàng của khách hàng
 * Bao gồm tổng số đơn, tổng chi tiêu, ngày đặt đơn gần nhất
 */
function get_customer_order_stats($customer_id)
{
    $conn = connectdb();
    $stmt = $conn->prepare("
        SELECT 
            COUNT(id) as total_orders,
            IFNULL(SUM(total_amount), 0) as total_spent,
            MAX(order_date) as last_order_date
        FROM tbl_customer_orders
        WHERE user_id = :customer_id
    ");
    $stmt->bindParam(':customer_id', $customer_id);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    return $result ?: [
        'total_orders' => 0,
        'total_spent' => 0,
        'last_order_date' => null
    ];
}

/**
 * Lấy danh sách sản phẩm yêu thích của khách hàng
 * Sản phẩm yêu thích là sản phẩm được mua nhiều nhất
 */
function get_customer_favorite_products($customer_id)
{
    $conn = connectdb();

    // Lấy ra sản phẩm được mua nhiều nhất
    $stmt = $conn->prepare("
        SELECT 
            p.id,
            p.tensp,
            p.img,
            p.gia,
            SUM(od.quantity) as quantity
        FROM tbl_customer_orders o
        JOIN tbl_customer_order_details od ON o.id = od.order_id
        JOIN tbl_sanpham p ON od.product_id = p.id
        WHERE o.user_id = :customer_id
        GROUP BY p.id
        ORDER BY quantity DESC
        LIMIT 5
    ");
    $stmt->bindParam(':customer_id', $customer_id);
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Nếu có sản phẩm, lấy ra số lượng mua cao nhất
    if (!empty($products)) {
        $max_quantity = $products[0]['quantity'];

        // Lọc ra những sản phẩm có số lượng mua bằng với số lượng cao nhất
        $favorite_products = array_filter($products, function ($product) use ($max_quantity) {
            return $product['quantity'] == $max_quantity;
        });

        return array_values($favorite_products); // Đảm bảo index là tuần tự
    }

    return [];
}

/**
 * Lấy thông tin thanh toán chuyển khoản từ bảng payment_orders
 */
function get_payment_orders($start_date = null, $end_date = null)
{
    $conn = connectdb();

    $sql = "
        SELECT p.*, c.hoten as customer_name, o.id as order_id, o.status as order_status, u.user as staff_name
        FROM payment_orders p
        LEFT JOIN tbl_khachhang c ON p.customer_id = c.id
        LEFT JOIN tbl_customer_orders o ON p.order_code LIKE CONCAT('DH', o.id, '%')
        LEFT JOIN tbl_user u ON o.staff_id = u.id
        WHERE 1=1
    ";

    $params = [];

    if ($start_date && $end_date) {
        $sql .= " AND DATE(p.created_at) BETWEEN ? AND ?";
        $params[] = $start_date;
        $params[] = $end_date;
    }

    $sql .= " GROUP BY p.id ORDER BY p.created_at DESC";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Cập nhật trạng thái thanh toán
 */
function update_payment_status($payment_id, $status)
{
    $conn = connectdb();
    $stmt = $conn->prepare("UPDATE payment_orders SET status = ? WHERE id = ?");
    return $stmt->execute([$status, $payment_id]);
}

/**
 * Lấy thông tin thanh toán theo order_code
 */
function get_payment_by_order_code($order_code)
{
    $conn = connectdb();
    $stmt = $conn->prepare("SELECT * FROM payment_orders WHERE order_code = ?");
    $stmt->execute([$order_code]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Lấy thông tin thanh toán chuyển khoản từ bảng payment_orders theo staff_id
 * @param int $staff_id ID của nhân viên (nếu là null thì lấy tất cả)
 * @param string $start_date Ngày bắt đầu (định dạng Y-m-d)
 * @param string $end_date Ngày kết thúc (định dạng Y-m-d)
 * @return array Danh sách các thanh toán
 */
function get_payment_orders_for_staff($staff_id = null, $start_date = null, $end_date = null)
{
    $conn = connectdb();

    $sql = "
        SELECT p.*, c.hoten as customer_name, o.id as order_id, o.status as order_status, u.user as staff_name
        FROM payment_orders p
        LEFT JOIN tbl_khachhang c ON p.customer_id = c.id
        LEFT JOIN tbl_customer_orders o ON p.order_code LIKE CONCAT('DH', o.id, '%')
        LEFT JOIN tbl_user u ON o.staff_id = u.id
        WHERE 1=1
    ";

    $params = [];

    // Lọc theo nhân viên nếu có staff_id
    if ($staff_id) {
        $sql .= " AND o.staff_id = ?";
        $params[] = $staff_id;
    }

    // Lọc theo ngày nếu có
    if ($start_date && $end_date) {
        $sql .= " AND DATE(p.created_at) BETWEEN ? AND ?";
        $params[] = $start_date;
        $params[] = $end_date;
    } elseif ($start_date) {
        $sql .= " AND DATE(p.created_at) = ?";
        $params[] = $start_date;
    }

    $sql .= " GROUP BY p.id ORDER BY p.created_at DESC";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Lấy tổng hợp thống kê thanh toán (đã thanh toán, chờ thanh toán) theo nhân viên
 * @param int $staff_id ID của nhân viên (nếu là null thì lấy tất cả)
 * @param string $date Ngày thống kê (định dạng Y-m-d)
 * @return array Thống kê thanh toán
 */
function get_payment_summary_for_staff($staff_id = null, $date = null)
{
    $conn = connectdb();

    $sql = "
        SELECT 
            SUM(CASE WHEN p.status = 'paid' THEN p.amount ELSE 0 END) as total_paid,
            SUM(CASE WHEN p.status = 'pending' THEN p.amount ELSE 0 END) as total_pending,
            SUM(p.amount) as total_amount,
            COUNT(CASE WHEN p.status = 'paid' THEN 1 END) as paid_count,
            COUNT(CASE WHEN p.status = 'pending' THEN 1 END) as pending_count,
            COUNT(p.id) as total_count
        FROM payment_orders p
        LEFT JOIN tbl_customer_orders o ON p.order_code LIKE CONCAT('DH', o.id, '%')
        WHERE 1=1
    ";

    $params = [];

    // Lọc theo nhân viên nếu có staff_id
    if ($staff_id) {
        $sql .= " AND o.staff_id = ?";
        $params[] = $staff_id;
    }

    // Lọc theo ngày nếu có
    if ($date) {
        $sql .= " AND DATE(p.created_at) = ?";
        $params[] = $date;
    }

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    // Đảm bảo trả về dữ liệu không null
    return [
        'total_paid' => $result['total_paid'] ?? 0,
        'total_pending' => $result['total_pending'] ?? 0,
        'total_amount' => $result['total_amount'] ?? 0,
        'paid_count' => $result['paid_count'] ?? 0,
        'pending_count' => $result['pending_count'] ?? 0,
        'total_count' => $result['total_count'] ?? 0
    ];
}

// Lấy thống kê thanh toán theo phương thức cho nhân viên
function get_payment_method_stats_for_staff($staff_id, $date = null)
{
    $conn = connectdb();

    $sql = "SELECT 
        payment_method,
        COUNT(*) as order_count,
        SUM(total_amount) as total_amount
    FROM tbl_customer_orders 
    WHERE staff_id = :staff_id 
        AND status = 'received'";

    if ($date) {
        $sql .= " AND DATE(updated_at) = :date";
    }

    $sql .= " GROUP BY payment_method";

    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':staff_id', $staff_id);

    if ($date) {
        $stmt->bindParam(':date', $date);
    }

    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Lấy thống kê theo phương thức thanh toán (tiền mặt, chuyển khoản) cho toàn hệ thống
 * @param string $start_date Ngày bắt đầu (định dạng Y-m-d)
 * @param string $end_date Ngày kết thúc (định dạng Y-m-d)
 * @return array Thống kê thanh toán
 */
function get_payment_method_stats($start_date = null, $end_date = null)
{
    $conn = connectdb();

    $sql = "SELECT 
        payment_method,
        COUNT(*) as order_count,
        SUM(total_amount) as total_amount
    FROM tbl_customer_orders 
    WHERE status = 'received'";

    $params = [];

    // Lọc theo ngày nếu có
    if ($start_date && $end_date) {
        $sql .= " AND DATE(updated_at) BETWEEN ? AND ?";
        $params[] = $start_date;
        $params[] = $end_date;
    } elseif ($start_date) {
        $sql .= " AND DATE(updated_at) = ?";
        $params[] = $start_date;
    }

    $sql .= " GROUP BY payment_method";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);

    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Chuẩn hóa kết quả thành mảng với các key cố định
    $stats = [
        'cod' => ['order_count' => 0, 'total_amount' => 0],
        'bank_transfer' => ['order_count' => 0, 'total_amount' => 0],
        'total' => ['order_count' => 0, 'total_amount' => 0]
    ];

    foreach ($results as $row) {
        $method = $row['payment_method'];

        // Đảm bảo phương thức thanh toán được nhận diện đúng
        if ($method == 'bank' || $method == 'banking' || $method == 'transfer') {
            $method = 'bank_transfer';
        } elseif ($method == 'cash' || $method == 'money') {
            $method = 'cod';
        }

        if (!isset($stats[$method])) {
            $stats[$method] = ['order_count' => 0, 'total_amount' => 0];
        }

        $stats[$method] = [
            'order_count' => (int)$row['order_count'],
            'total_amount' => (float)$row['total_amount']
        ];

        $stats['total']['order_count'] += (int)$row['order_count'];
        $stats['total']['total_amount'] += (float)$row['total_amount'];
    }

    return $stats;
}

/**
 * Lấy chi tiết thanh toán theo phương thức thanh toán với thông tin nhân viên xử lý
 * @param string $start_date Ngày bắt đầu (định dạng Y-m-d)
 * @param string $end_date Ngày kết thúc (định dạng Y-m-d)
 * @return array Chi tiết thanh toán theo phương thức
 */
function get_payment_method_details($start_date = null, $end_date = null)
{
    $conn = connectdb();

    $sql = "SELECT 
        o.id as order_id,
        o.payment_method,
        o.total_amount,
        o.status,
        o.updated_at,
        k.hoten as customer_name,
        u.user as staff_name,
        u.id as staff_id
    FROM tbl_customer_orders o
    LEFT JOIN tbl_khachhang k ON o.user_id = k.id
    LEFT JOIN tbl_user u ON o.staff_id = u.id
    WHERE o.status = 'received'";

    $params = [];

    // Lọc theo ngày nếu có
    if ($start_date && $end_date) {
        $sql .= " AND DATE(o.updated_at) BETWEEN ? AND ?";
        $params[] = $start_date;
        $params[] = $end_date;
    } elseif ($start_date) {
        $sql .= " AND DATE(o.updated_at) = ?";
        $params[] = $start_date;
    }

    $sql .= " ORDER BY o.updated_at DESC";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Chuẩn hóa phương thức thanh toán trong kết quả
    foreach ($results as &$row) {
        $method = $row['payment_method'];

        // Chuẩn hóa phương thức thanh toán
        if ($method == 'bank' || $method == 'banking' || $method == 'transfer') {
            $row['payment_method'] = 'bank_transfer';
        } elseif ($method == 'cash' || $method == 'money') {
            $row['payment_method'] = 'cod';
        }
    }

    return $results;
}

/**
 * Lấy thống kê theo nhân viên và phương thức thanh toán
 * @param string $start_date Ngày bắt đầu (định dạng Y-m-d)
 * @param string $end_date Ngày kết thúc (định dạng Y-m-d)
 * @return array Thống kê theo nhân viên và phương thức
 */
function get_staff_payment_method_stats($start_date = null, $end_date = null)
{
    $conn = connectdb();

    $sql = "SELECT 
        u.id as staff_id,
        u.user as staff_name,
        o.payment_method,
        COUNT(*) as order_count,
        SUM(o.total_amount) as total_amount
    FROM tbl_customer_orders o
    LEFT JOIN tbl_user u ON o.staff_id = u.id
    WHERE o.status = 'received' AND o.staff_id IS NOT NULL";

    $params = [];

    // Lọc theo ngày nếu có
    if ($start_date && $end_date) {
        $sql .= " AND DATE(o.updated_at) BETWEEN ? AND ?";
        $params[] = $start_date;
        $params[] = $end_date;
    } elseif ($start_date) {
        $sql .= " AND DATE(o.updated_at) = ?";
        $params[] = $start_date;
    }

    $sql .= " GROUP BY u.id, u.user, o.payment_method
              ORDER BY u.user, o.payment_method";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);

    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stats = [];

    // Tổ chức lại dữ liệu theo nhân viên
    foreach ($results as $row) {
        $staff_id = $row['staff_id'];
        $method = $row['payment_method'];

        // Chuẩn hóa phương thức thanh toán
        if ($method == 'bank' || $method == 'banking' || $method == 'transfer') {
            $method = 'bank_transfer';
        } elseif ($method == 'cash' || $method == 'money') {
            $method = 'cod';
        }

        if (!isset($stats[$staff_id])) {
            $stats[$staff_id] = [
                'staff_name' => $row['staff_name'],
                'cod' => ['order_count' => 0, 'total_amount' => 0],
                'bank_transfer' => ['order_count' => 0, 'total_amount' => 0],
                'total' => ['order_count' => 0, 'total_amount' => 0]
            ];
        }

        $stats[$staff_id][$method] = [
            'order_count' => (int)$row['order_count'],
            'total_amount' => (float)$row['total_amount']
        ];

        $stats[$staff_id]['total']['order_count'] += (int)$row['order_count'];
        $stats[$staff_id]['total']['total_amount'] += (float)$row['total_amount'];
    }

    return $stats;
}