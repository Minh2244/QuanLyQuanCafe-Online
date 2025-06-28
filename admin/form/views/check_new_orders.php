<?php
// Bao gồm các file cần thiết
include_once "../../../model/connectdb.php";
include_once "../../../model/tbl_customer_orders.php";

// Kiểm tra đơn hàng mới
$new_orders = get_new_orders();
$new_orders_count = count($new_orders);

// Trả về kết quả dạng JSON
header('Content-Type: application/json');
echo json_encode(['new_orders' => $new_orders_count]);
