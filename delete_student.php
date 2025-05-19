<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    $_SESSION['error'] = "Anda tidak memiliki akses untuk menghapus data siswa";
    header("Location: students.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['student_id'])) {
    $student_id = mysqli_real_escape_string($conn, $_POST['student_id']);
    
    // Get student name for logging
    $get_name = "SELECT name FROM students WHERE id = '$student_id'";
    $result = mysqli_query($conn, $get_name);
    $student = mysqli_fetch_assoc($result);
    
    if (!$student) {
        $_SESSION['error'] = "Data siswa tidak ditemukan";
        header("Location: students.php");
        exit();
    }
    
    // Delete the student
    $delete_query = "DELETE FROM students WHERE id = '$student_id'";
    
    if (mysqli_query($conn, $delete_query)) {
        $_SESSION['success'] = "Data siswa " . htmlspecialchars($student['name']) . " berhasil dihapus";
    } else {
        $_SESSION['error'] = "Gagal menghapus data siswa: " . mysqli_error($conn);
    }
} else {
    $_SESSION['error'] = "Invalid request";
    }
    
header("Location: students.php");
exit();
?> 