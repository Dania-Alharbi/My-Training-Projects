<?php
$host = "sql201.infinityfree.com";
$user = "if0_42622283";
$pass = "fFFDo14WRahIa";
$dbname = "if0_42622283_robot_db";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "فشل الاتصال: " . $conn->connect_error]));
}
?>