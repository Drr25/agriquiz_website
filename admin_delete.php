<?php
session_start();
require 'db_conn.php';

// Admin lang ang pwede
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Siguraduhing integer ang id
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id || $id <= 0) {
    header('Location: admin_student_list.php');
    exit;
}

// Prepared statement; students lang ang buburahin, hindi admin
$stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'user'");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->close();

header('Location: admin_student_list.php');
exit;