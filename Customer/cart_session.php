<?php
session_start();
require_once __DIR__ . '/../model/connectdb.php';

// Khởi tạo giỏ hàng nếu chưa có
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = array();
}

// Hàm thêm sản phẩm vào giỏ hàng
function addToCart($product_id, $product_info)
{
    if (!isset($_SESSION['cart'][$product_id])) {
        $_SESSION['cart'][$product_id] = array(
            'quantity' => 1
        );
    } else {
        $_SESSION['cart'][$product_id]['quantity']++;
    }
}

// Hàm cập nhật số lượng sản phẩm
function updateCartQuantity($product_id, $quantity)
{
    if (isset($_SESSION['cart'][$product_id])) {
        if ($quantity > 0) {
            $_SESSION['cart'][$product_id]['quantity'] = $quantity;
        } else {
            removeFromCart($product_id);
        }
        return true;
    }
    return false;
}

// Hàm xóa sản phẩm khỏi giỏ hàng
function removeFromCart($product_id)
{
    if (isset($_SESSION['cart'][$product_id])) {
        unset($_SESSION['cart'][$product_id]);
        return true;
    }
    return false;
}

// Hàm lấy số lượng của một sản phẩm trong giỏ hàng
function getCartItemQuantity($product_id)
{
    if (isset($_SESSION['cart'][$product_id]['quantity'])) {
        return $_SESSION['cart'][$product_id]['quantity'];
    }
    return 0;
}

// Hàm lấy tổng số lượng sản phẩm trong giỏ hàng
function getCartItemCount()
{
    $count = 0;
    if (isset($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $item) {
            if (isset($item['quantity'])) {
                $count += $item['quantity'];
            }
        }
    }
    return $count;
}

// Hàm kiểm tra sản phẩm có trong giỏ hàng không
function isInCart($product_id)
{
    return isset($_SESSION['cart'][$product_id]);
}

// Hàm lấy tổng tiền giỏ hàng
function getCartTotal()
{
    $total = 0;
    if (isset($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $item) {
            if (isset($item['price']) && isset($item['quantity'])) {
                $total += $item['price'] * $item['quantity'];
            }
        }
    }
    return $total;
}

// Hàm xóa toàn bộ giỏ hàng
function clearCart()
{
    $_SESSION['cart'] = array();
}

// Thêm sản phẩm vào giỏ hàng
if (isset($_POST['action']) && $_POST['action'] === 'add') {
    $product_id = intval($_POST['product_id']);
    $quantity = intval($_POST['quantity'] ?? 1);
    // Kiểm tra sản phẩm đã có trong giỏ chưa
    if (isset($_SESSION['cart'][$product_id])) {
        $_SESSION['cart'][$product_id] += $quantity;
    } else {
        $_SESSION['cart'][$product_id] = $quantity;
    }
    echo json_encode(['success' => true, 'cart_count' => array_sum($_SESSION['cart'])]);
    exit;
}

// Xóa sản phẩm khỏi giỏ hàng
if (isset($_POST['action']) && $_POST['action'] === 'remove') {
    $product_id = intval($_POST['product_id']);
    unset($_SESSION['cart'][$product_id]);
    echo json_encode(['success' => true, 'cart_count' => array_sum($_SESSION['cart'])]);
    exit;
}

// Cập nhật số lượng sản phẩm
if (isset($_POST['action']) && $_POST['action'] === 'update') {
    $product_id = intval($_POST['product_id']);
    $quantity = intval($_POST['quantity']);
    if ($quantity > 0) {
        $_SESSION['cart'][$product_id] = $quantity;
    } else {
        unset($_SESSION['cart'][$product_id]);
    }
    echo json_encode(['success' => true, 'cart_count' => array_sum($_SESSION['cart'])]);
    exit;
}

// Lấy giỏ hàng hiện tại
if (isset($_GET['action']) && $_GET['action'] === 'get') {
    $cart = $_SESSION['cart'];
    echo json_encode(['cart' => $cart, 'cart_count' => array_sum($cart)]);
    exit;
}
