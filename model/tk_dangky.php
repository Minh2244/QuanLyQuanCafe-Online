<?php
include "connectdb.php";

function insert_taikhoan($user, $pass, $role = 0)
{
    $conn = connectdb();
    $sql = "INSERT INTO tbl_user (user, pass, role) VALUES ('$user', '$pass', '$role')";
    $conn->exec($sql);
}

function getall_taikhoan()
{
    $conn = connectdb();
    $stmt = $conn->prepare("SELECT * FROM tbl_user");
    $stmt->execute();
    $result = $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $kq = $stmt->fetchAll();
    return $kq;
}

function delete_taikhoan($id)
{
    try {
        $conn = connectdb();
        $conn->beginTransaction();

        // 1. Xóa dữ liệu từ bảng tbl_working_time
        $stmt = $conn->prepare("DELETE FROM tbl_working_time WHERE user_id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        // 2. Xóa dữ liệu từ bảng tbl_thongke_sanpham liên quan đến thống kê của user
        $stmt = $conn->prepare("
            DELETE ts FROM tbl_thongke_sanpham ts
            INNER JOIN tbl_thongke_ngay tn ON ts.thongke_id = tn.id
            WHERE tn.user_id = :id
        ");
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        // 3. Xóa dữ liệu từ bảng tbl_thongke_ngay
        $stmt = $conn->prepare("DELETE FROM tbl_thongke_ngay WHERE user_id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        // 4. Xóa dữ liệu từ bảng tbl_hoadon_chitiet liên quan đến hóa đơn của user
        $stmt = $conn->prepare("
            DELETE hc FROM tbl_hoadon_chitiet hc
            INNER JOIN tbl_hoadon h ON hc.hoadon_id = h.id
            WHERE h.user_id = :id
        ");
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        // 5. Xóa dữ liệu từ bảng tbl_hoadon
        $stmt = $conn->prepare("DELETE FROM tbl_hoadon WHERE user_id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        // 6. Cuối cùng xóa tài khoản người dùng
        $stmt = $conn->prepare("DELETE FROM tbl_user WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        $conn->commit();
        return true;
    } catch (PDOException $e) {
        if ($conn) {
            $conn->rollBack();
        }
        // Ghi log lỗi nếu cần
        error_log("Lỗi xóa tài khoản: " . $e->getMessage());
        return false;
    }
}

function getone_taikhoan($id)
{
    $conn = connectdb();
    $stmt = $conn->prepare("SELECT * FROM tbl_user WHERE id=" . $id);
    $stmt->execute();
    $result = $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $kq = $stmt->fetchAll();
    return $kq;
}

function update_taikhoan($id, $user, $pass, $role)
{
    $conn = connectdb();
    $sql = "UPDATE tbl_user SET user='$user', pass='$pass', role='$role' WHERE id=$id";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
}

function check_user_exist($user)
{
    $conn = connectdb();
    $stmt = $conn->prepare("SELECT * FROM tbl_user WHERE user='$user'");
    $stmt->execute();
    $result = $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $kq = $stmt->fetchAll();
    return count($kq) > 0;
}
