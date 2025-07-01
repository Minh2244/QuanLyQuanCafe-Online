<?php
function checkuser($user, $pass) // lien ket lay du lieu database
{
    $conn = connectdb();    //lay du lieu database
    $stmt = $conn->prepare("SELECT * FROM tbl_user WHERE user='" . $user . "' AND pass='" . $pass . "'"); //truy van du lieu
    $stmt->execute();
    $result = $stmt->setFetchMode(PDO::FETCH_ASSOC); // phiong thuc tra ve la dang mang
    $kq = $stmt->fetchAll();
    if (count($kq) > 0) return $kq[0]['role'];
    else return 0;
}
function getuserinfo($user, $pass) // lien ket lay du lieu database
{
    $conn = connectdb();    //lay du lieu database
    $stmt = $conn->prepare("SELECT * FROM tbl_user WHERE user='" . $user . "' AND pass='" . $pass . "'"); //truy van du lieu
    $stmt->execute();
    $result = $stmt->setFetchMode(PDO::FETCH_ASSOC); // phiong thuc tra ve la dang mang
    $kq = $stmt->fetchAll();
    return $kq;
}