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
        gender ENUM('Laki-laki', 'Perempuan') NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB";
    
    if (!mysqli_query($conn, $create_table)) {
        $_SESSION['error'] = "Error creating students table: " . mysqli_error($conn);
        header("Location: students.php");
        exit();
    }
} else {
    // Check if gender column exists
    $check_gender_column = mysqli_query($conn, "SHOW COLUMNS FROM students LIKE 'gender'");
    if (mysqli_num_rows($check_gender_column) == 0) {
        // Add gender column if it doesn't exist
        $add_gender_column = "ALTER TABLE students ADD COLUMN gender ENUM('Laki-laki', 'Perempuan') NOT NULL AFTER class";
        if (!mysqli_query($conn, $add_gender_column)) {
            $_SESSION['error'] = "Error adding gender column: " . mysqli_error($conn);
            header("Location: students.php");
            exit();
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate input
    if (empty($_POST['nis']) || empty($_POST['name']) || empty($_POST['class']) || empty($_POST['gender'])) {
        $_SESSION['error'] = "Semua field harus diisi";
        header("Location: students.php");
        exit();
    }

    // Sanitize input
    $nis = mysqli_real_escape_string($conn, $_POST['nis']);
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $class = mysqli_real_escape_string($conn, $_POST['class']);
    $gender = mysqli_real_escape_string($conn, $_POST['gender']);

    // Debug log
    error_log("Adding new student with gender: " . $gender);

    // Validate gender value
    if (!in_array($gender, ['Laki-laki', 'Perempuan'])) {
        $_SESSION['error'] = "Jenis kelamin tidak valid";
        header("Location: students.php");
        exit();
    }

    // Check if NIS already exists
    $check_nis = "SELECT id FROM students WHERE nis = '$nis'";
    $nis_result = mysqli_query($conn, $check_nis);

    if (!$nis_result) {
        $_SESSION['error'] = "Error checking NIS: " . mysqli_error($conn);
        header("Location: students.php");
        exit();
    }

    if (mysqli_num_rows($nis_result) > 0) {
        $_SESSION['error'] = "NIS sudah terdaftar";
        header("Location: students.php");
        exit();
    }

    // Insert new student with explicit gender value
    $insert_query = "INSERT INTO students (nis, name, class, gender) 
                    VALUES ('$nis', '$name', '$class', '$gender')";
    
    // Debug log
    error_log("Insert query: " . $insert_query);
    
    if (mysqli_query($conn, $insert_query)) {
        $_SESSION['success'] = "Data siswa berhasil ditambahkan";
        // Debug log
        error_log("Insert successful");
    } else {
        $_SESSION['error'] = "Error adding student: " . mysqli_error($conn);
        // Debug log
        error_log("Insert failed: " . mysqli_error($conn));
    }
}

header("Location: students.php");
exit();
?> 