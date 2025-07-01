<?php
if (!function_exists('connectdb')) {
    function connectdb()
    {
        $servername = "localhost";
        $username = "root";
        $password = "";

        try {
            $conn = new PDO("mysql:host=$servername;dbname=product", $username, $password);
            // set the PDO error mode to exception
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            // echo "Connected successfully";
        } catch (PDOException $e) {
            // echo "Connection failed: " . $e->getMessage();
        }
        return $conn;
    }
}
