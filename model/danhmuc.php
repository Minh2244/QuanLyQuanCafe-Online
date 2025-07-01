<?php

function  themdm($tendm) //them du lieu
{
    $conn = connectdb(); //lay du lieu database
    $sql = "INSERT INTO tbl_danhmuc (tendm) VALUES ('" . $tendm . "')"; //truyen du lieu
    // use exec() because no results are returned
    $conn->exec($sql);
}

function  deletedm($id) //xoa du lieu
{
    $conn = connectdb(); //lay du lieu database
    $sql = "DELETE FROM tbl_danhmuc WHERE id=" . $id; //delet 
    $conn->exec($sql);
}

function updatedm($id, $tendm) // thuc thi update
{
    $conn = connectdb(); //lay du lieu database
    $sql = "UPDATE tbl_danhmuc SET tendm='" . $tendm . "' WHERE id=" . $id;
    $stmt = $conn->prepare($sql);
    $stmt->execute();
}

function   getonedm($id) //cap nhat du lieu
{
    $conn = connectdb(); //lay du lieu database
    $stmt = $conn->prepare("SELECT * FROM tbl_danhmuc WHERE id=" . $id);
    $stmt->execute();
    $result = $stmt->setFetchMode(PDO::FETCH_ASSOC); // phiong thuc tra ve la dang mang
    $kq = $stmt->fetchAll();
    return $kq;
}

function getall_dm() // lien ket lay du lieu database
{
    $conn = connectdb();    //lay du lieu database
    $stmt = $conn->prepare("SELECT * FROM tbl_danhmuc");
    $stmt->execute();
    $result = $stmt->setFetchMode(PDO::FETCH_ASSOC); // phiong thuc tra ve la dang mang
    $kq = $stmt->fetchAll();
    return $kq;
}
