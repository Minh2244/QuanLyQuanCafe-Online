<?php
session_start();
// Xóa toàn bộ session liên quan đến khách hàng
session_unset();
session_destroy();
header("Location: index.php");
exit();