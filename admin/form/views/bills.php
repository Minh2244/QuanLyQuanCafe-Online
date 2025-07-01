<section class="bills-section">
    <div class="section-content">
        <h2 class="section-header">Lịch sử hóa đơn</h2>

        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success">
                <span><?php echo $_SESSION['success_message']; ?></span>
                <button class="close-alert">&times;</button>
                <?php unset($_SESSION['success_message']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger">
                <span><?php echo $_SESSION['error_message']; ?></span>
                <button class="close-alert">&times;</button>
                <?php unset($_SESSION['error_message']); ?>
            </div>
        <?php endif; ?>

        <?php
        if (isset($_SESSION['role']) && ($_SESSION['role'] == 0 || $_SESSION['role'] == 1)) {
            if (count($bills) > 0) {
        ?>
                <table class="bills-table">
                    <thead>
                        <tr>
                            <th>Mã hóa đơn</th>
                            <th>Nhân viên</th>
                            <th>Ngày tạo</th>
                            <th>Tổng tiền</th>
                            <th>Trạng thái</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        foreach ($bills as $bill) {
                        ?>
                            <tr>
                                <td>#<?php echo $bill['id']; ?></td>
                                <td><?php echo isset($bill['username']) ? $bill['username'] : 'N/A'; ?></td>
                                <td><?php echo date('d/m/Y H:i:s', strtotime($bill['ngay_tao'])); ?></td>
                                <td><?php echo number_format($bill['tong_tien'], 0, ',', '.'); ?> VNĐ</td>
                                <td>
                                    <?php if (isset($_SESSION['role']) && ($_SESSION['role'] == 0 || $_SESSION['role'] == 1)): ?>
                                        <form method="post" action="index.php?act=bills" style="display: inline;">
                                            <input type="hidden" name="bill_id" value="<?php echo $bill['id']; ?>">
                                            <select name="trang_thai" onchange="this.form.submit()" class="status-select">
                                                <option value="Đã thanh toán"
                                                    <?php echo ($bill['trang_thai'] == 'Đã thanh toán') ? 'selected' : ''; ?>>Đã thanh
                                                    toán</option>
                                                <option value="Đang xử lý"
                                                    <?php echo ($bill['trang_thai'] == 'Đang xử lý') ? 'selected' : ''; ?>>Đang xử lý
                                                </option>
                                                <option value="Đã hủy"
                                                    <?php echo ($bill['trang_thai'] == 'Đã hủy') ? 'selected' : ''; ?>>Đã hủy</option>
                                            </select>
                                            <input type="hidden" name="update_bill" value="1">
                                        </form>
                                    <?php else: ?>
                                        <?php echo $bill['trang_thai']; ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="#" class="detail-btn view-bill-details" data-id="<?php echo $bill['id']; ?>">Xem chi
                                        tiết</a>

                                    <?php if (isset($_SESSION['role']) && ($_SESSION['role'] == 0 || $_SESSION['role'] == 1)): ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php
                        }
                        ?>
                    </tbody>
                </table>

                <!-- Modal hiển thị chi tiết hóa đơn -->
                <div id="bill-detail-modal" class="modal">
                    <div class="modal-content">
                        <span class="modal-close">&times;</span>
                        <div id="bill-detail-content"></div>
                    </div>
                </div>

                <!-- Modal chỉnh sửa hóa đơn -->
                <div id="edit-bill-modal" class="modal">
                    <div class="modal-content">
                        <span class="modal-close edit-close">&times;</span>
                        <div id="edit-bill-content"></div>
                    </div>
                </div>

                <script>
                    $(document).ready(function() {
                        // Xử lý hiển thị chi tiết hóa đơn
                        $('.view-bill-details').click(function(e) {
                            e.preventDefault();
                            var billId = $(this).data('id');

                            // Kiểm tra nếu billId hợp lệ
                            if (!billId) {
                                alert('Không tìm thấy mã hóa đơn.');
                                return;
                            }

                            // Lấy chi tiết hóa đơn bằng AJAX
                            $.ajax({
                                url: 'index.php?act=get_bill_details',
                                type: 'GET',
                                data: {
                                    id: billId
                                },
                                success: function(response) {
                                    // Kiểm tra nếu response trả về rỗng
                                    if (!response.trim()) {
                                        alert('Không tìm thấy thông tin hóa đơn.');
                                        return;
                                    }

                                    $('#bill-detail-content').html(response);
                                    $('#bill-detail-modal').show();
                                },
                                error: function(xhr, status, error) {
                                    console.error("Lỗi AJAX: " + error);
                                    alert('Có lỗi xảy ra khi lấy chi tiết hóa đơn: ' + error);
                                }
                            });
                        });

                        // // Xử lý chỉnh sửa hóa đơn
                        // $('.edit-bill-details').click(function(e) {
                        //     e.preventDefault();
                        //     var billId = $(this).data('id');

                        //     // Lấy form chỉnh sửa hóa đơn bằng AJAX
                        //     $.ajax({
                        //         url: 'index.php?act=edit_bill_details',
                        //         type: 'GET',
                        //         data: {
                        //             id: billId
                        //         },
                        //         success: function(response) {
                        //             $('#edit-bill-content').html(response);
                        //             $('#edit-bill-modal').show();
                        //         },
                        //         error: function(xhr, status, error) {
                        //             console.error("Lỗi AJAX: " + error);
                        //             alert('Có lỗi xảy ra khi lấy thông tin hóa đơn: ' +
                        //                 error);
                        //         }
                        //     });
                        // });

                        // Thêm class modal-open khi mở modal
                        $('.view-bill-details').click(function() {
                            $('body').addClass('modal-open');
                        });

                        // Xóa class modal-open khi đóng modal
                        $('.modal-close, .close-btn').click(function() {
                            $('body').removeClass('modal-open');
                            $(this).closest('.modal').hide();
                        });

                        // Đóng modal khi click ra ngoài
                        $(window).click(function(e) {
                            if ($(e.target).hasClass('modal')) {
                                $('.modal').hide();
                                $('body').removeClass('modal-open');
                            }
                        });
                    });
                </script>
        <?php
            } else {
                echo '<p>Bạn chưa có hóa đơn nào. <a href="index.php?act=menu">Bắt đầu mua hàng</a></p>';
            }
        } else {
            // Kiểm tra nếu người dùng chưa đăng nhập
            if (!isset($_SESSION['role'])) {
                echo '<p>Vui lòng <a href="../../admin/login.php">đăng nhập</a> để xem lịch sử hóa đơn.</p>';
            } else {
                echo '<p>Không có quyền truy cập vào trang này.</p>';
            }
        }
        ?>
    </div>
</section>

<style>
    .modal {
        display: none;
        position: fixed;
        z-index: 9999;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgba(0, 0, 0, 0.5);
    }

    .modal-content {
        background-color: #fefefe;
        margin: 5% auto;
        padding: 15px;
        border-radius: 8px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        max-width: 500px;
        width: 90%;
        position: relative;
    }

    /* Đảm bảo modal không hiển thị header và navbar */
    body.modal-open header,
    body.modal-open nav,
    body.modal-open .navbar {
        display: none !important;
    }

    .modal-close {
        position: absolute;
        top: 5px;
        right: 15px;
        font-size: 24px;
        font-weight: bold;
        color: #999;
        cursor: pointer;
        z-index: 10000;
    }

    .modal-close:hover {
        color: #333;
    }

    .status-select {
        padding: 4px 8px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 13px;
    }
</style>