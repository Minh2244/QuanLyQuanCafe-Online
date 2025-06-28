<?php
require_once 'connectdb.php';

function insert_khachhang($hoten, $sdt, $diachi, $username, $password)
{
    $conn = connectdb();
    $sql = "INSERT INTO tbl_khachhang (hoten, sdt, diachi, username, password) VALUES (:hoten, :sdt, :diachi, :username, :password)";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':hoten', $hoten);
    $stmt->bindParam(':sdt', $sdt);
    $stmt->bindParam(':diachi', $diachi);
    $stmt->bindParam(':username', $username);
    $stmt->bindParam(':password', $password);
    $stmt->execute();
    return $conn->lastInsertId();
}

function check_khachhang($username, $password)
{
    $conn = connectdb();
    $sql = "SELECT * FROM tbl_khachhang WHERE username = :username AND password = :password";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':username', $username);
    $stmt->bindParam(':password', $password);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function get_khachhang_by_username($username)
{
    $conn = connectdb();
    $sql = "SELECT * FROM tbl_khachhang WHERE username = :username";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':username', $username);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function get_khachhang_by_id($id)
{
    $conn = connectdb();
    $sql = "SELECT * FROM tbl_khachhang WHERE id = :id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function update_password($id, $new_password)
{
    $conn = connectdb();
    $sql = "UPDATE tbl_khachhang SET password = :password WHERE id = :id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':password', $new_password);
    $stmt->bindParam(':id', $id);
    return $stmt->execute();
}

function update_khachhang($id, $hoten, $sdt, $diachi)
{
    $conn = connectdb();
    $sql = "UPDATE tbl_khachhang SET hoten = :hoten, sdt = :sdt, diachi = :diachi WHERE id = :id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':hoten', $hoten);
    $stmt->bindParam(':sdt', $sdt);
    $stmt->bindParam(':diachi', $diachi);
    $stmt->bindParam(':id', $id);
    return $stmt->execute();
}

function update_avatar($id, $avatar_path)
{
    $conn = connectdb();
    $sql = "UPDATE tbl_khachhang SET avatar = :avatar WHERE id = :id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':avatar', $avatar_path);
    $stmt->bindParam(':id', $id);
    return $stmt->execute();
}