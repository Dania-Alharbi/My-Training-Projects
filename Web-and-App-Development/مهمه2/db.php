<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$host = "sql202.byetcluster.com";
$user = "if0_42486675";
$pass = "CMSGckTZDmqy";
$db   = "if0_42486675_db";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("فشل الاتصال بقاعدة البيانات: " . $conn->connect_error);
}
?>