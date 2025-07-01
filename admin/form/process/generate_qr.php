<?php
function generateVietQR($amount, $description)
{
    // Thông tin tài khoản ngân hàng
    $bankId = "970436"; // Mã ngân hàng Vietcombank theo NAPAS
    $accountNo = "1030549759"; // Số tài khoản
    $accountName = "MAI NHUT MINH"; // Tên chủ tài khoản

    // Tạo chuỗi dữ liệu cho mã QR theo chuẩn EMV của VietQR
    $qrContent = sprintf(
        "https://img.vietqr.io/image/%s-%s-%s-%s-%s-compact2.png?accountName=%s&addInfo=%s&amount=%s",
        $bankId,
        $accountNo,
        urlencode($accountName),
        $amount,
        "vietqr", // Sử dụng template compact2 của VietQR
        urlencode($accountName),
        urlencode($description),
        $amount
    );

    return [
        'success' => true,
        'qr_url' => $qrContent,
        'bank_info' => [
            'bank_name' => 'Vietcombank',
            'account_no' => $accountNo,
            'account_name' => $accountName,
            'branch' => 'PGD TAN HIEP',
            'amount' => $amount,
            'description' => $description
        ]
    ];
}

// Nhận dữ liệu từ request
$amount = isset($_POST['amount']) ? intval($_POST['amount']) : 0;
$description = isset($_POST['description']) ? $_POST['description'] : '';

if ($amount > 0 && !empty($description)) {
    $result = generateVietQR($amount, $description);
    header('Content-Type: application/json');
    echo json_encode($result);
} else {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Thiếu thông tin thanh toán hoặc số tiền không hợp lệ'
    ]);
}