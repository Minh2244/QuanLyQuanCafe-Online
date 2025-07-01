<?php
if (count($dssp) > 0) {
    foreach ($dssp as $sp) {
        $img_path = $sp['img'];
        if (strpos($img_path, "../uploaded/") === 0) {
            $img_path = "../../uploaded/" . basename($img_path);
        }
        $ten_sp = $sp['tensp'];
        if (strlen($ten_sp) > 25) {
            $ten_sp = substr($ten_sp, 0, 22) . '...';
        }
        echo '<div class="product-card">';
        echo '<img src="' . $img_path . '" alt="' . $sp['tensp'] . '">';
        echo '<h3 title="' . $sp['tensp'] . '">' . $ten_sp . '</h3>';
        echo '<p class="price">' . number_format($sp['gia'], 0, ',', '.') . ' VNĐ</p>';
        echo '<button class="add-to-cart" data-id="' . $sp['id'] . '" data-name="' . $sp['tensp'] . '">Thêm vào giỏ</button>';
        echo '</div>';
    }
} else {
    echo '<p>Không có sản phẩm nào trong danh mục này.</p>';
}
