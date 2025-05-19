<?php
session_start();

// Check if user is logged in and is admin or teacher
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'teacher')) {
    $_SESSION['error'] = "Anda tidak memiliki akses untuk halaman ini";
    header("Location: health_records.php");
    exit();
}

// Include database configuration
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate input
    if (empty($_POST['student_id']) || empty($_POST['check_date'])) {
        $_SESSION['error'] = "Siswa dan tanggal pemeriksaan harus diisi!";
        header("Location: health_records.php");
        exit();
    }

    // Sanitize input
    $student_id = mysqli_real_escape_string($conn, $_POST['student_id']);
    $check_date = mysqli_real_escape_string($conn, $_POST['check_date']);
    $height = !empty($_POST['height']) ? mysqli_real_escape_string($conn, $_POST['height']) : null;
    $weight = !empty($_POST['weight']) ? mysqli_real_escape_string($conn, $_POST['weight']) : null;
    $blood_pressure = !empty($_POST['blood_pressure']) ? mysqli_real_escape_string($conn, $_POST['blood_pressure']) : null;
    $complaints = !empty($_POST['complaints']) ? mysqli_real_escape_string($conn, $_POST['complaints']) : null;
    $notes = !empty($_POST['notes']) ? mysqli_real_escape_string($conn, $_POST['notes']) : null;
    $medicine_id = !empty($_POST['medicine_id']) ? mysqli_real_escape_string($conn, $_POST['medicine_id']) : null;
    $medicine_qty = !empty($_POST['medicine_qty']) ? (int)$_POST['medicine_qty'] : null;

    // Check if student exists
    $check_student = mysqli_query($conn, "SELECT id FROM students WHERE id = '$student_id'");
    if (mysqli_num_rows($check_student) == 0) {
        $_SESSION['error'] = "Siswa tidak ditemukan!";
        header("Location: health_records.php");
        exit();
    }

    // Jika memilih obat, cek stok cukup
    if ($medicine_id && $medicine_qty) {
        $check_medicine = mysqli_query($conn, "SELECT stock FROM medicines WHERE id = '$medicine_id'");
        if (mysqli_num_rows($check_medicine) == 0) {
            $_SESSION['error'] = "Obat tidak ditemukan!";
            header("Location: health_records.php");
            exit();
        }
        $medicine = mysqli_fetch_assoc($check_medicine);
        if ($medicine['stock'] < $medicine_qty) {
            $_SESSION['error'] = "Stok obat tidak cukup!";
            header("Location: health_records.php");
            exit();
        }
    }

    // Insert health record
    $query = "INSERT INTO health_records (student_id, check_date, height, weight, blood_pressure, complaints, notes, medicine_id, medicine_qty) 
              VALUES ('$student_id', '$check_date', " . 
              ($height ? "'$height'" : "NULL") . ", " . 
              ($weight ? "'$weight'" : "NULL") . ", " . 
              ($blood_pressure ? "'$blood_pressure'" : "NULL") . ", " . 
              ($complaints ? "'$complaints'" : "NULL") . ", " . 
              ($notes ? "'$notes'" : "NULL") . ", " . 
              ($medicine_id ? "'$medicine_id'" : "NULL") . ", " . 
              ($medicine_qty ? "'$medicine_qty'" : "NULL") . ")";

    if (mysqli_query($conn, $query)) {
        // Jika memilih obat, kurangi stok
        if ($medicine_id && $medicine_qty) {
            mysqli_query($conn, "UPDATE medicines SET stock = stock - $medicine_qty WHERE id = '$medicine_id'");
        }
        $_SESSION['success'] = "Data pemeriksaan kesehatan berhasil ditambahkan";
    } else {
        $_SESSION['error'] = "Error: " . mysqli_error($conn);
    }
}

header("Location: health_records.php");
exit();
?> 