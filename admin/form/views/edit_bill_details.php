<?php
session_start();
if (!isset($_SESSION['role']) || ($_SESSION['role'] != 0 && $_SESSION['role'] != 1)) {
    echo "Bạn không có quyền truy cập!";
    exit();
}

include "../../../model/connectdb.php";

// Xử lý cập nhật hóa đơn
if (isset($_POST['update_bill'])) {
    $hoadon_id = $_POST['hoadon_id'];
    $trang_thai = $_POST['trang_thai'];

    try {
        $conn = connectdb();
        $sql = "UPDATE tbl_hoadon SET trang_thai = :trang_thai WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':trang_thai', $trang_thai);
        $stmt->bindParam(':id', $hoadon_id);
        $stmt->execute();

        echo json_encode(["success" => true, "message" => "Cập nhật hóa đơn thành công"]);
    } catch (PDOException $e) {
        echo json_encode(["success" => false, "message" => "Lỗi: " . $e->getMessage()]);
    }
    exit();
}

// Xử lý cập nhật chi tiết hóa đơn
if (isset($_POST['update_detail'])) {
    $detail_id = $_POST['detail_id'];
    $so_luong = $_POST['so_luong'];

    try {
        $conn = connectdb();

        // Lấy thông tin chi tiết hiện tại
        $stmt = $conn->prepare("SELECT * FROM tbl_hoadon_chitiet WHERE id = :id");
        $stmt->bindParam(':id', $detail_id);
        $stmt->execute();
        $detail = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($detail) {
            // Cập nhật tổng tiền hóa đơn
            $hoadon_id = $detail['hoadon_id'];

            // Tính lại tổng tiền
            $stmt = $conn->prepare("SELECT SUM(gia * so_luong) as tong_tien FROM tbl_hoadon_chitiet WHERE hoadon_id = :hoadon_id");
            $stmt->bindParam(':hoadon_id', $hoadon_id);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $tong_tien = $result['tong_tien'];

            // Cập nhật tổng tiền hóa đơn
            $sql = "UPDATE tbl_hoadon SET tong_tien = :tong_tien WHERE id = :id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':tong_tien', $tong_tien);
            $stmt->bindParam(':id', $hoadon_id);
            $stmt->execute();

            echo json_encode(["success" => true, "message" => "Cập nhật chi tiết hóa đơn thành công", "total" => $tong_tien]);
        } else {
            echo json_encode(["success" => false, "message" => "Không tìm thấy chi tiết hóa đơn"]);
        }
    } catch (PDOException $e) {
        echo json_encode(["success" => false, "message" => "Lỗi: " . $e->getMessage()]);
    }
    exit();
}

// Xử lý xóa chi tiết hóa đơn
if (isset($_POST['delete_detail'])) {
    $detail_id = $_POST['detail_id'];

    try {
        $conn = connectdb();

        // Lấy thông tin chi tiết hiện tại
        $stmt = $conn->prepare("SELECT * FROM tbl_hoadon_chitiet WHERE id = :id");
        $stmt->bindParam(':id', $detail_id);
        $stmt->execute();
        $detail = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($detail) {
            $hoadon_id = $detail['hoadon_id'];

            // Xóa chi tiết
            $sql = "DELETE FROM tbl_hoadon_chitiet WHERE id = :id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':id', $detail_id);
            $stmt->execute();

            // Tính lại tổng tiền
            $stmt = $conn->prepare("SELECT SUM(gia * so_luong) as tong_tien FROM tbl_hoadon_chitiet WHERE hoadon_id = :hoadon_id");
            $stmt->bindParam(':hoadon_id', $hoadon_id);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $tong_tien = $result['tong_tien'] ?: 0;

            // Cập nhật tổng tiền hóa đơn
            $sql = "UPDATE tbl_hoadon SET tong_tien = :tong_tien WHERE id = :id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':tong_tien', $tong_tien);
            $stmt->bindParam(':id', $hoadon_id);
            $stmt->execute();

            echo json_encode(["success" => true, "message" => "Xóa chi tiết hóa đơn thành công", "total" => $tong_tien]);
        } else {
            echo json_encode(["success" => false, "message" => "Không tìm thấy chi tiết hóa đơn"]);
        }
    } catch (PDOException $e) {
        echo json_encode(["success" => false, "message" => "Lỗi: " . $e->getMessage()]);
    }
    exit();
}

if (isset($_GET['id'])) {
    $bill_id = $_GET['id'];
    $conn = connectdb();

    // Lấy thông tin hóa đơn
    $stmt = $conn->prepare("SELECT h.*, u.user as username FROM tbl_hoadon AS h
                     JOIN tbl_user AS u ON h.user_id = u.id
                     WHERE h.id = :id");
    $stmt->bindParam(':id', $bill_id);
    $stmt->execute();
    $bill = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($bill) {
        // Lấy chi tiết hóa đơn
        $stmt = $conn->prepare("SELECT * FROM tbl_hoadon_chitiet WHERE hoadon_id = :id");
        $stmt->bindParam(':id', $bill_id);
        $stmt->execute();
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
        <div class="bill-edit-form">
            <h3>Chỉnh sửa hóa đơn #<?php echo $bill['id']; ?></h3>

            <div class="bill-info">
                <p><strong>Ngày tạo:</strong> <?php echo date('d/m/Y H:i:s', strtotime($bill['ngay_tao'])); ?></p>
                <p><strong>Nhân viên:</strong> <?php echo $bill['username']; ?></p>

                <div class="form-group">
                    <label for="trang_thai"><strong>Trạng thái:</strong></label>
                    <select id="trang_thai" class="status-select" data-id="<?php echo $bill['id']; ?>">
                        <option value="Đã thanh toán" <?php echo ($bill['trang_thai'] == 'Đã thanh toán') ? 'selected' : ''; ?>>
                            Đã thanh toán</option>
                        <option value="Đang xử lý" <?php echo ($bill['trang_thai'] == 'Đang xử lý') ? 'selected' : ''; ?>>Đang
                            xử lý</option>
                        <option value="Đã hủy" <?php echo ($bill['trang_thai'] == 'Đã hủy') ? 'selected' : ''; ?>>Đã hủy
                        </option>
                    </select>
                </div>
            </div>

            <h4>Chi tiết hóa đơn</h4>

            <table class="cart-table">
                <thead>
                    <tr>
                        <th>STT</th>
                        <th>Sản phẩm</th>
                        <th>Đơn giá</th>
                        <th>Số lượng</th>
                        <th>Thành tiền</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $i = 1;
                    foreach ($items as $item) {
                        $thanh_tien = $item['gia'] * $item['so_luong'];
                    ?>
                        <tr data-id="<?php echo $item['id']; ?>">
                            <td><?php echo $i++; ?></td>
                            <td><?php echo $item['ten_sp']; ?></td>
                            <td><?php echo number_format($item['gia'], 0, ',', '.'); ?> VNĐ</td>
                            <td>
                                <input type="number" class="qty-input" min="1" value="<?php echo $item['so_luong']; ?>"
                                    data-id="<?php echo $item['id']; ?>" data-price="<?php echo $item['gia']; ?>">
                            </td>
                            <td class="item-total"><?php echo number_format($thanh_tien, 0, ',', '.'); ?> VNĐ</td>
                            <td>
                                <button class="delete-detail-btn" data-id="<?php echo $item['id']; ?>">
                                    <i class="fas fa-trash-alt"></i> Xóa
                                </button>
                            </td>
                        </tr>
                    <?php
                    }
                    ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="4" style="text-align: right;"><strong>Tổng tiền:</strong></td>
                        <td colspan="2"><strong id="bill-total"><?php echo number_format($bill['tong_tien'], 0, ',', '.'); ?>
                                VNĐ</strong></td>
                    </tr>
                </tfoot>
            </table>

            <div class="action-buttons">
                <button id="save-changes-btn" class="btn-primary" data-id="<?php echo $bill['id']; ?>">Lưu thay đổi</button>
                <button id="cancel-edit-btn" class="btn-secondary">Hủy</button>
            </div>
        </div>

        <script>
            $(document).ready(function() {
                // Cập nhật trạng thái hóa đơn
                $('#trang_thai').change(function() {
                    var billId = $(this).data('id');
                    var status = $(this).val();

                    $.ajax({
                        url: 'views/edit_bill_details.php',
                        type: 'POST',
                        data: {
                            update_bill: 1,
                            hoadon_id: billId,
                            trang_thai: status
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                alert(response.message);
                            } else {
                                alert(response.message);
                            }
                        }
                    });
                });

                //Cập nhật số lượng sản phẩm
                $('.qty-input').change(function() {
                    var detailId = $(this).data('id');
                    var quantity = $(this).val();
                    var price = $(this).data('price');

                    if (quantity < 1) {
                        $(this).val(1);
                        quantity = 1;
                    }

                    // Cập nhật thành tiền của sản phẩm
                    var total = price * quantity;
                    $(this).closest('tr').find('.item-total').text(formatCurrency(total) + ' VNĐ');

                    $.ajax({
                        url: 'views/edit_bill_details.php',
                        type: 'POST',
                        data: {
                            update_detail: 1,
                            detail_id: detailId,
                            so_luong: quantity
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                $('#bill-total').text(formatCurrency(response.total) + ' VNĐ');
                            } else {
                                alert(response.message);
                            }
                        }
                    });
                });

                // Xóa chi tiết hóa đơn
                $('.delete-detail-btn').click(function() {
                    if (!confirm('Bạn có chắc muốn xóa sản phẩm này khỏi hóa đơn?')) {
                        return;
                    }

                    var detailId = $(this).data('id');
                    var row = $(this).closest('tr');

                    $.ajax({
                        url: 'views/edit_bill_details.php',
                        type: 'POST',
                        data: {
                            delete_detail: 1,
                            detail_id: detailId
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                row.remove();
                                $('#bill-total').text(formatCurrency(response.total) + ' VNĐ');
                                alert(response.message);
                            } else {
                                alert(response.message);
                            }
                        }
                    });
                });

                // Lưu tất cả thay đổi
                $('#save-changes-btn').click(function() {
                    alert('Đã lưu tất cả thay đổi!');
                    $('#edit-bill-modal').hide();
                });

                // Hủy chỉnh sửa
                $('#cancel-edit-btn').click(function() {
                    $('#edit-bill-modal').hide();
                });

                // Hàm định dạng tiền tệ
                function formatCurrency(number) {
                    return new Intl.NumberFormat('vi-VN').format(number);
                }
            });
        </script>

        <style>
            .bill-edit-form {
                padding: 20px;
            }

            .bill-info {
                background-color: #f8f9fa;
                padding: 15px;
                border-radius: 5px;
                margin-bottom: 20px;
            }

            .form-group {
                margin-top: 10px;
            }

            .form-group label {
                display: inline-block;
                margin-right: 10px;
            }

            .qty-input {
                width: 60px;
                padding: 5px;
                text-align: center;
                border: 1px solid #ddd;
                border-radius: 4px;
            }

            .action-buttons {
                margin-top: 20px;
                text-align: right;
            }

            .btn-primary,
            .btn-secondary {
                padding: 8px 16px;
                border-radius: 4px;
                font-size: 14px;
                cursor: pointer;
                border: none;
                margin-left: 10px;
            }

            .btn-primary {
                background-color: #3498db;
                color: white;
            }

            .btn-secondary {
                background-color: #95a5a6;
                color: white;
            }

            .delete-detail-btn {
                background-color: #e74c3c;
                color: white;
                border: none;
                padding: 5px 10px;
                border-radius: 4px;
                cursor: pointer;
                font-size: 12px;
            }
        </style>
<?php
    } else {
        echo "<p>Không tìm thấy thông tin hóa đơn.</p>";
    }
} else {
    echo "<p>Không có mã hóa đơn được cung cấp.</p>";
}
?>