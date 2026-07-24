<?php
require_once 'db.php';

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $sql = "UPDATE users SET status = NOT status WHERE id = $id";
    $conn->query($sql);
}

header("Location: index.php");
exit();
?>