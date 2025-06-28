<?php
if (!isset($_SESSION['thanh_toan_ok'])) {
    header('location: index.php?act=cart');
    exit();
}

// Lấy thông tin hóa đơn
$hoadon_id = isset($_GET['id']) ? $_GET['id'] : $_SESSION['hoadon_id'];
$conn = connectdb();

// Lấy thông tin hóa đơn
$stmt = $conn->prepare("SELECT h.*, u.user as username 
                         FROM tbl_hoadon AS h
                         JOIN tbl_user AS u ON h.user_id = u.id 
                         WHERE h.id = :id");
$stmt->bindParam(':id', $hoadon_id);
$stmt->execute();
$bill = $stmt->fetch(PDO::FETCH_ASSOC);

// Lấy chi tiết hóa đơn
$stmt = $conn->prepare("SELECT * FROM tbl_hoadon_chitiet WHERE hoadon_id = :id");
$stmt->bindParam(':id', $hoadon_id);
$stmt->execute();
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Xóa session sau khi lấy thông tin
unset($_SESSION['thanh_toan_ok']);
unset($_SESSION['hoadon_id']);

// Lấy danh sách hóa đơn
$stmt = $conn->prepare("SELECT h.*, u.user as username 
                       FROM tbl_hoadon AS h
                       JOIN tbl_user AS u ON h.user_id = u.id
                       ORDER BY h.ngay_tao DESC");
$stmt->execute();
$invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="invoice-overlay">
    <div class="invoice-container">
        <div class="invoice-header">
            <h2>HÓA ĐƠN</h2>
            <p>Best Coffee ☕</p>
            <p>Địa chỉ: số 4, Đông Mỹ, Thới Lai, Cần Thơ</p>
            <p>Điện thoại: 0869378427</p>
        </div>

        <div class="invoice-details">
            <p><strong>Mã hóa đơn:</strong> #<?php echo $bill['id']; ?></p>
            <p><strong>Ngày tạo:</strong> <?php echo date('d/m/Y H:i:s', strtotime($bill['ngay_tao'])); ?></p>
            <p><strong>Nhân viên:</strong> <?php echo $bill['username']; ?></p>
        </div>

        <table class="invoice-table">
            <thead>
                <tr>
                    <th>STT</th>
                    <th>Sản phẩm</th>
                    <th>Đơn giá</th>
                    <th>SL</th>
                    <th>Thành tiền</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $i = 1;
                foreach ($items as $item) {
                    $thanh_tien = $item['gia'] * $item['so_luong'];
                ?>
                    <tr>
                        <td><?php echo $i++; ?></td>
                        <td><?php echo $item['ten_sp']; ?></td>
                        <td><?php echo number_format($item['gia'], 0, ',', '.'); ?> VND</td>
                        <td><?php echo $item['so_luong']; ?></td>
                        <td><?php echo number_format($thanh_tien, 0, ',', '.'); ?> VND</td>
                    </tr>
                <?php
                }
                ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" class="text-right"><strong>Tổng tiền:</strong></td>
                    <td><strong><?php echo number_format($bill['tong_tien'], 0, ',', '.'); ?> VND</strong></td>
                </tr>
            </tfoot>
        </table>

        <div class="invoice-footer">
            <p class="thank-you">Cảm ơn quý khách đã sử dụng dịch vụ của chúng tôi!</p>
            <p class="time-generated">Thời gian hiện tại: <?php echo date('d/m/Y H:i:s'); ?></p>
        </div>

        <div class="invoice-actions">
            <button onclick="printInvoice()" class="btn btn-primary">In hóa đơn</button>
            <button onclick="continueShopping()" class="btn btn-success">Tiếp tục mua hàng</button>
        </div>

        <p class="copyright-text">&copy; 2025 Best Coffee. All rights reserved.</p>
    </div>
</div>

<style>
    html,
    body {
        margin: 0;
        padding: 0;
        height: 100%;
        background-color: #f8f9fa;
    }

    .invoice-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 999;
    }

    .invoice-container {
        background: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 0 20px rgba(0, 0, 0, 0.2);
        width: 100%;
        max-width: 400px;
        min-height: auto;
        margin: 20px auto;
        position: relative;
    }

    .invoice-header {
        text-align: center;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 1px solid #eee;
    }

    .invoice-header h2 {
        margin: 0 0 10px 0;
        color: #333;
        font-size: 20px;
    }

    .invoice-header p {
        margin: 3px 0;
        color: #666;
        font-size: 14px;
    }

    .invoice-details {
        margin: 15px 0;
        padding: 0 15px;
    }

    .invoice-details p {
        margin: 8px 0;
        font-size: 14px;
    }

    .invoice-table {
        width: 100%;
        border-collapse: collapse;
        margin: 15px 0;
        font-size: 14px;
    }

    .invoice-table th,
    .invoice-table td {
        padding: 8px;
        text-align: left;
        border-bottom: 1px solid #ddd;
    }

    .invoice-table th {
        background-color: #f8f9fa;
        font-weight: bold;
        color: #333;
    }

    .invoice-table tbody tr:hover {
        background-color: #f9f9f9;
    }

    .invoice-table tfoot tr td {
        border-top: 2px solid #ddd;
        font-weight: bold;
        background-color: #f8f9fa;
    }

    .text-right {
        text-align: right;
    }

    .invoice-footer {
        text-align: center;
        margin-top: 20px;
        padding-top: 15px;
        border-top: 1px solid #eee;
    }

    .thank-you {
        font-style: italic;
        margin-bottom: 8px;
        color: #666;
        font-size: 14px;
    }

    .time-generated {
        font-size: 12px;
        color: #888;
    }

    .invoice-actions {
        display: flex;
        justify-content: center;
        gap: 15px;
        margin-top: 30px;
    }

    .invoice-actions button {
        padding: 10px 25px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-weight: bold;
        transition: all 0.3s ease;
    }

    .btn-primary {
        background-color: #007bff;
        color: white;
    }

    .btn-primary:hover {
        background-color: #0056b3;
    }

    .btn-success {
        background-color: #28a745;
        color: white;
    }

    .btn-success:hover {
        background-color: #218838;
    }

    .copyright-text {
        text-align: center;
        margin-top: 10px;
        color: #666;
        font-size: 12px;
    }

    @media print {
        @page {
            size: 80mm auto;
            margin: 0;
        }

        html,
        body {
            width: 80mm;
            /* Đặt chiều rộng trang in */
            background: none;
            margin: 0;
            padding: 0;
        }

        .invoice-overlay {
            position: static;
            background: none;
            padding: 0;
            margin: 0;
            width: 100%;
        }

        .invoice-container {
            width: 100%;
            margin: 0;
            padding: 10px;
            box-shadow: none;
            border-radius: 0;
        }

        .invoice-header h2 {
            font-size: 16px;
            margin-bottom: 5px;
        }

        .invoice-header p {
            font-size: 12px;
            margin: 2px 0;
        }

        .invoice-details p {
            font-size: 12px;
            margin: 4px 0;
        }

        .invoice-table {
            font-size: 12px;
            margin: 8px 0;
        }

        .invoice-table th,
        .invoice-table td {
            padding: 5px;
        }

        .thank-you {
            font-size: 12px;
            margin: 5px 0;
        }

        .time-generated {
            font-size: 10px;
        }

        .copyright-text {
            font-size: 10px;
            margin-top: 5px;
            padding-top: 5px;
            border-top: 1px dashed #ddd;
        }

        .invoice-actions,
        nav,
        header,
        footer,
        .btn,
        button {
            display: none !important;
            /* Ẩn các nút và phần không cần thiết khi in */
        }
    }
</style>

<script>
    function printInvoice() {
        // Lưu lại title gốc
        var originalTitle = document.title;

        // Đặt title mới cho trang in
        document.title = "Hóa đơn #" + <?php echo $bill['id']; ?>;

        // In hóa đơn
        window.print();

        // Khôi phục title gốc
        document.title = originalTitle;
    }

    function continueShopping() {
        window.location.href = 'index.php?act=menu';
    }

    // Thêm sự kiện để đóng hóa đơn khi nhấn ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            continueShopping();
        }
    });
</script>