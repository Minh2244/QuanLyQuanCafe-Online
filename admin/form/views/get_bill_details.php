<?php
// Kiểm tra xem session đã được bắt đầu chưa
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['role']) || ($_SESSION['role'] != 0 && $_SESSION['role'] != 1)) {
    echo "Bạn không có quyền truy cập!";
    exit();
}

// Kiểm tra đường dẫn tệp include
$db_path = "../../../model/connectdb.php";
if (file_exists($db_path)) {
    include_once $db_path;
} else {
    $alt_path = "../../model/connectdb.php";
    if (file_exists($alt_path)) {
        include_once $alt_path;
    } else {
        echo "<p>Lỗi: Không thể kết nối đến cơ sở dữ liệu</p>";
        exit();
    }
}

if (!isset($_GET['id'])) {
    echo "Không tìm thấy mã hóa đơn.";
    exit;
}

$bill_id = $_GET['id'];
$conn = connectdb();

// Lấy thông tin hóa đơn
$stmt = $conn->prepare("SELECT h.*, u.user as username 
                       FROM tbl_hoadon h 
                       JOIN tbl_user u ON h.user_id = u.id 
                       WHERE h.id = :id");
$stmt->bindParam(':id', $bill_id);
$stmt->execute();
$bill = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$bill) {
    echo "Không tìm thấy thông tin hóa đơn.";
    exit;
}

// Lấy chi tiết hóa đơn
$stmt = $conn->prepare("SELECT * FROM tbl_hoadon_chitiet WHERE hoadon_id = :id");
$stmt->bindParam(':id', $bill_id);
$stmt->execute();
$bill_details = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="bill-details">
    <h3>Chi tiết hóa đơn #<?php echo $bill['id']; ?></h3>

    <div class="bill-info">
        <p><strong>Nhân viên:</strong> <?php echo $bill['username']; ?></p>
        <p><strong>Ngày tạo:</strong> <?php echo date('d/m/Y H:i:s', strtotime($bill['ngay_tao'])); ?></p>
        <p><strong>Trạng thái:</strong> <?php echo $bill['trang_thai']; ?></p>
    </div>

    <table class="bill-details-table">
        <thead>
            <tr>
                <th>Sản phẩm</th>
                <th>Số lượng</th>
                <th>Đơn giá</th>
                <th>Thành tiền</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($bill_details as $detail): ?>
                <tr>
                    <td><?php echo $detail['ten_sp']; ?></td>
                    <td><?php echo $detail['so_luong']; ?></td>
                    <td><?php echo number_format($detail['gia'], 0, ',', '.'); ?> VNĐ</td>
                    <td><?php echo number_format($detail['gia'] * $detail['so_luong'], 0, ',', '.'); ?> VNĐ</td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3"><strong>Tổng cộng:</strong></td>
                <td><strong><?php echo number_format($bill['tong_tien'], 0, ',', '.'); ?> VNĐ</strong></td>
            </tr>
        </tfoot>
    </table>
</div>

<style>
    .bill-details {
        padding: 20px;
    }

    .bill-info {
        margin-bottom: 20px;
    }

    .bill-details-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }

    .bill-details-table th,
    .bill-details-table td {
        padding: 10px;
        border: 1px solid #ddd;
        text-align: left;
    }

    .bill-details-table th {
        background-color: #f5f5f5;
    }

    .bill-details-table tfoot tr td {
        background-color: #f9f9f9;
    }
</style>