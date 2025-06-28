<?php
header('Content-Type: application/json');

// Trả về thời gian server dưới dạng JSON
echo json_encode([
    'server_time' => date('c') // ISO 8601 format
]);
