<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    $_SESSION['error'] = "Anda tidak memiliki akses untuk halaman ini";
    header("Location: health_records.php");
    exit();
}

// Include database configuration
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    $id = mysqli_real_escape_string($conn, $_POST['id']);

    // Check if health record exists
    $check_record = mysqli_query($conn, "SELECT id FROM health_records WHERE id = '$id'");
    if (mysqli_num_rows($check_record) == 0) {
        $_SESSION['error'] = "Data pemeriksaan tidak ditemukan!";
        header("Location: health_records.php");
        exit();
    }

    // Delete health record
    $query = "DELETE FROM health_records WHERE id = '$id'";
    if (mysqli_query($conn, $query)) {
        $_SESSION['success'] = "Data pemeriksaan kesehatan berhasil dihapus";
    } else {
        $_SESSION['error'] = "Error: " . mysqli_error($conn);
    }
} else {
    $_SESSION['error'] = "Data tidak valid!";
}

header("Location: health_records.php");
exit();
?> 