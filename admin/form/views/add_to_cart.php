<?php
session_start();
require_once "../../../model/connectdb.php";
require_once "../../../model/sanpham.php";

if (isset($_POST['product_id'])) {
    $id = $_POST['product_id'];

    // Lấy thông tin sản phẩm
    $conn = connectdb();
    $stmt = $conn->prepare("SELECT * FROM tbl_sanpham WHERE id = :id");
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    $sp = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($sp) {
        // Kiểm tra xem giỏ hàng đã tồn tại chưa
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = array();
        }

        // Kiểm tra xem sản phẩm đã có trong giỏ hàng chưa
        $index = -1;
        foreach ($_SESSION['cart'] as $key => $item) {
            if ($item['id'] == $id) {
                $index = $key;
                break;
            }
        }

        // Nếu sản phẩm đã có trong giỏ hàng, tăng số lượng lên 1
        if ($index != -1) {
            $_SESSION['cart'][$index]['soluong'] += 1;
        }
        // Nếu sản phẩm chưa có trong giỏ hàng, thêm vào giỏ hàng
        else {
            $cart_item = array(
                'id' => $sp['id'],
                'tensp' => $sp['tensp'],
                'img' => $sp['img'],
                'gia' => $sp['gia'],
                'soluong' => 1
            );

            $_SESSION['cart'][] = $cart_item;
        }

        // Trả về kết quả JSON
        echo json_encode(array(
            'success' => true,
            'message' => $sp['tensp'] . ' đã được thêm vào giỏ hàng'
        ));
    } else {
        echo json_encode(array(
            'success' => false,
            'message' => 'Không tìm thấy sản phẩm'
        ));
    }
} else {
    echo json_encode(array(
        'success' => false,
        'message' => 'Không có ID sản phẩm'
    ));
}
