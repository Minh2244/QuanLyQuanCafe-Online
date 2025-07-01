<?php
function   getonesp($id) //cap nhat du lieu
{
    $conn = connectdb(); //lay du lieu database
    $stmt = $conn->prepare("SELECT * FROM tbl_sanpham WHERE id=" . $id);
    $stmt->execute();
    $result = $stmt->setFetchMode(PDO::FETCH_ASSOC); // phiong thuc tra ve la dang mang
    $kq = $stmt->fetchAll();
    return $kq;
}

function deletesp($id)
{
    $conn = connectdb();    //lay du lieu database
    $sql = "DELETE FROM tbl_sanpham WHERE id=$id";
    // use exec() because no results are returned
    $conn->exec($sql);
}

function insert_sanpham($iddm, $tensp, $gia, $img)
{
    $conn = connectdb();    //lay du lieu database
    $sql = "INSERT INTO tbl_sanpham (iddm, tensp, gia, img)
    VALUES ('$iddm', '$tensp', '$gia', '$img')";
    // use exec() because no results are returned
    $conn->exec($sql);
}

function getall_sanpham() // lien ket lay du lieu database
{
    $conn = connectdb();    //lay du lieu database
    $stmt = $conn->prepare("SELECT * FROM tbl_sanpham");
    $stmt->execute();
    $result = $stmt->setFetchMode(PDO::FETCH_ASSOC); // phiong thuc tra ve la dang mang
    $kq = $stmt->fetchAll();
    return $kq;
}

function updatesp($id, $tensp, $img, $gia, $iddm) // thuc thi update
{
    $conn = connectdb(); //lay du lieu database
    if ($img == "") {
        $sql = "UPDATE tbl_sanpham SET tensp='" . $tensp . "', gia='" . $gia . "', iddm='" . $iddm . "' WHERE id=" . $id;
    } else {
        $sql = "UPDATE tbl_sanpham SET tensp='" . $tensp . "', gia='" . $gia . "', iddm='" . $iddm . "', img='" . $img . "' WHERE id=" . $id;
    }
    $stmt = $conn->prepare($sql);
    $stmt->execute();
}
