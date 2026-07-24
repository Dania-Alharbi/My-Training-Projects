<?php
require_once 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $conn->real_escape_string($_POST['name']);
    $age = intval($_POST['age']);
    $status = isset($_POST['status']) ? 1 : 0;

    if (!empty($name) && $age > 0) {
        $sql = "INSERT INTO users (name, age, status) VALUES ('$name', $age, $status)";
        $conn->query($sql);
    }
}

header("Location: index.php");
exit();
?>