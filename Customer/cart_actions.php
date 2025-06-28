<?php
session_start();
require_once '../model/connectdb.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';
$product_id = intval($_POST['product_id'] ?? 0);
$quantity = intval($_POST['quantity'] ?? 1);

$response = ['success' => false, 'message' => ''];

// Kiểm tra sản phẩm tồn tại
$conn = connectdb();
$stmt = $conn->prepare("SELECT * FROM tbl_sanpham WHERE id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    $response['message'] = 'Sản phẩm không tồn tại';
    echo json_encode($response);
    exit;
}

// Chỉ thao tác với session (không còn đăng nhập)
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

switch ($action) {
    case 'add':
        if (isset($_SESSION['cart'][$product_id])) {
            $_SESSION['cart'][$product_id] += $quantity;
        } else {
            $_SESSION['cart'][$product_id] = $quantity;
        }
        $response['success'] = true;
        $response['message'] = 'Đã thêm vào giỏ hàng';
        break;
    case 'update':
        if (isset($_SESSION['cart'][$product_id])) {
            $_SESSION['cart'][$product_id] = $quantity;
            $response['success'] = true;
            $response['message'] = 'Đã cập nhật giỏ hàng';
        }
        break;
    case 'remove':
        if (isset($_SESSION['cart'][$product_id])) {
            unset($_SESSION['cart'][$product_id]);
            $response['success'] = true;
            $response['message'] = 'Đã xóa sản phẩm';
        }
        break;
}

// Tính tổng số lượng sản phẩm trong giỏ hàng
$total_items = 0;
foreach ($_SESSION['cart'] as $qty) {
    $total_items += $qty;
}
$response['cart_count'] = $total_items;
echo json_encode($response);