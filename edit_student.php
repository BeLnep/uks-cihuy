<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    $_SESSION['error'] = "Anda tidak memiliki akses untuk halaman ini";
    header("Location: students.php");
    exit();
}

// Verify students table exists and has correct structure
$table_check = mysqli_query($conn, "SHOW TABLES LIKE 'students'");
if (mysqli_num_rows($table_check) == 0) {
    // Create students table if it doesn't exist
    $create_table = "CREATE TABLE students (
        id INT PRIMARY KEY AUTO_INCREMENT,
        nis VARCHAR(20) UNIQUE NOT NULL,
        name VARCHAR(100) NOT NULL,
        class VARCHAR(20) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB";
    
    if (!mysqli_query($conn, $create_table)) {
        $_SESSION['error'] = "Error creating students table: " . mysqli_error($conn);
        header("Location: students.php");
        exit();
    }
}

// Verify table structure
$columns_check = mysqli_query($conn, "SHOW COLUMNS FROM students");
$required_columns = ['id', 'nis', 'name', 'class', 'gender', 'created_at'];
$existing_columns = [];

while ($column = mysqli_fetch_assoc($columns_check)) {
    $existing_columns[] = $column['Field'];
}

$missing_columns = array_diff($required_columns, $existing_columns);

if (!empty($missing_columns)) {
    // Disable foreign key checks
    mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 0");
    
    // Drop and recreate table if structure is incorrect
    mysqli_query($conn, "DROP TABLE IF EXISTS students");
    
    $create_table = "CREATE TABLE students (
        id INT PRIMARY KEY AUTO_INCREMENT,
        nis VARCHAR(20) UNIQUE NOT NULL,
        name VARCHAR(100) NOT NULL,
        class VARCHAR(20) NOT NULL,
        gender ENUM('Laki-laki', 'Perempuan') NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB";
    
    if (!mysqli_query($conn, $create_table)) {
        $_SESSION['error'] = "Error recreating students table: " . mysqli_error($conn);
        // Re-enable foreign key checks
        mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 1");
        header("Location: students.php");
        exit();
    }
    
    // Re-enable foreign key checks
    mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 1");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate input
    if (empty($_POST['student_id']) || empty($_POST['nis']) || empty($_POST['name']) || empty($_POST['class']) || empty($_POST['gender'])) {
        $_SESSION['error'] = "Semua field harus diisi";
        header("Location: students.php");
        exit();
    }

    // Sanitize input
    $student_id = mysqli_real_escape_string($conn, $_POST['student_id']);
    $nis = mysqli_real_escape_string($conn, $_POST['nis']);
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $class = mysqli_real_escape_string($conn, $_POST['class']);
    $gender = mysqli_real_escape_string($conn, $_POST['gender']);

    // Debug log
    error_log("Updating student ID: " . $student_id . " with gender: " . $gender);

    // Validate gender value
    if (!in_array($gender, ['Laki-laki', 'Perempuan'])) {
        $_SESSION['error'] = "Jenis kelamin tidak valid";
        header("Location: students.php");
        exit();
    }

    // Check if student exists
    $check_query = "SELECT id FROM students WHERE id = '$student_id'";
    $check_result = mysqli_query($conn, $check_query);
    
    if (!$check_result || mysqli_num_rows($check_result) == 0) {
        $_SESSION['error'] = "Data siswa tidak ditemukan";
        header("Location: students.php");
        exit();
    }

    // Check if NIS already exists for other students
    $check_nis = "SELECT id FROM students WHERE nis = '$nis' AND id != '$student_id'";
    $nis_result = mysqli_query($conn, $check_nis);
    
    if (!$nis_result) {
        $_SESSION['error'] = "Error checking NIS: " . mysqli_error($conn);
        header("Location: students.php");
        exit();
    }

    if (mysqli_num_rows($nis_result) > 0) {
        $_SESSION['error'] = "NIS sudah terdaftar untuk siswa lain";
        header("Location: students.php");
        exit();
    }

    // Update student data with explicit gender value
    $update_query = "UPDATE students SET 
                    nis = '$nis',
                    name = '$name',
                    class = '$class', 
                    gender = '$gender' 
                    WHERE id = '$student_id'";
    
    // Debug log
    error_log("Update query: " . $update_query);

    if (mysqli_query($conn, $update_query)) {
        $_SESSION['success'] = "Data siswa berhasil diperbarui";
        // Debug log
        error_log("Update successful");
    } else {
        $_SESSION['error'] = "Error updating student: " . mysqli_error($conn);
        // Debug log
        error_log("Update failed: " . mysqli_error($conn));
    }
}

header("Location: students.php");
exit();
?> 