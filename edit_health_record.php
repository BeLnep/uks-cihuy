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
    if (empty($_POST['id']) || empty($_POST['student_id']) || empty($_POST['check_date'])) {
        $_SESSION['error'] = "Data tidak lengkap!";
        header("Location: health_records.php");
        exit();
    }

    // Sanitize input
    $id = mysqli_real_escape_string($conn, $_POST['id']);
    $student_id = mysqli_real_escape_string($conn, $_POST['student_id']);
    $check_date = mysqli_real_escape_string($conn, $_POST['check_date']);
    $height = !empty($_POST['height']) ? mysqli_real_escape_string($conn, $_POST['height']) : null;
    $weight = !empty($_POST['weight']) ? mysqli_real_escape_string($conn, $_POST['weight']) : null;
    $blood_pressure = !empty($_POST['blood_pressure']) ? mysqli_real_escape_string($conn, $_POST['blood_pressure']) : null;
    $complaints = !empty($_POST['complaints']) ? mysqli_real_escape_string($conn, $_POST['complaints']) : null;
    $notes = !empty($_POST['notes']) ? mysqli_real_escape_string($conn, $_POST['notes']) : null;
    $medicine_id = !empty($_POST['medicine_id']) ? mysqli_real_escape_string($conn, $_POST['medicine_id']) : null;
    $medicine_qty = !empty($_POST['medicine_qty']) ? (int)$_POST['medicine_qty'] : null;

    // Check if health record exists
    $check_record = mysqli_query($conn, "SELECT medicine_id, medicine_qty FROM health_records WHERE id = '$id'");
    if (mysqli_num_rows($check_record) == 0) {
        $_SESSION['error'] = "Data pemeriksaan tidak ditemukan!";
        header("Location: health_records.php");
        exit();
    }
    $old_record = mysqli_fetch_assoc($check_record);
    $old_medicine_id = $old_record['medicine_id'];
    $old_medicine_qty = $old_record['medicine_qty'];

    // Jika memilih obat, cek stok cukup (hanya jika berubah)
    if ($medicine_id && $medicine_qty) {
        $check_medicine = mysqli_query($conn, "SELECT stock FROM medicines WHERE id = '$medicine_id'");
        if (mysqli_num_rows($check_medicine) == 0) {
            $_SESSION['error'] = "Obat tidak ditemukan!";
            header("Location: health_records.php");
            exit();
        }
        $medicine = mysqli_fetch_assoc($check_medicine);
        $stok_tersedia = $medicine['stock'];
        // Jika ganti obat atau jumlah, cek stok cukup
        if ($medicine_id != $old_medicine_id || $medicine_qty != $old_medicine_qty) {
            $stok_tersedia += ($medicine_id == $old_medicine_id) ? (int)$old_medicine_qty : 0;
            if ($stok_tersedia < $medicine_qty) {
                $_SESSION['error'] = "Stok obat tidak cukup!";
                header("Location: health_records.php");
                exit();
            }
        }
    }

    // Check if student exists
    $check_student = mysqli_query($conn, "SELECT id FROM students WHERE id = '$student_id'");
    if (mysqli_num_rows($check_student) == 0) {
        $_SESSION['error'] = "Siswa tidak ditemukan!";
        header("Location: health_records.php");
        exit();
    }

    // Update health record
    $query = "UPDATE health_records SET 
              student_id = '$student_id',
              check_date = '$check_date',
              height = " . ($height ? "'$height'" : "NULL") . ",
              weight = " . ($weight ? "'$weight'" : "NULL") . ",
              blood_pressure = " . ($blood_pressure ? "'$blood_pressure'" : "NULL") . ",
              complaints = " . ($complaints ? "'$complaints'" : "NULL") . ",
              notes = " . ($notes ? "'$notes'" : "NULL") . ",
              medicine_id = " . ($medicine_id ? "'$medicine_id'" : "NULL") . ",
              medicine_qty = " . ($medicine_qty ? "'$medicine_qty'" : "NULL") . "
              WHERE id = '$id'";

    if (mysqli_query($conn, $query)) {
        // Jika memilih obat, update stok (hanya jika berubah)
        if ($medicine_id && $medicine_qty) {
            // Kembalikan stok lama jika ganti obat/jumlah
            if ($old_medicine_id && $old_medicine_qty) {
                mysqli_query($conn, "UPDATE medicines SET stock = stock + $old_medicine_qty WHERE id = '$old_medicine_id'");
            }
            // Kurangi stok baru
            mysqli_query($conn, "UPDATE medicines SET stock = stock - $medicine_qty WHERE id = '$medicine_id'");
        } else if ($old_medicine_id && $old_medicine_qty) {
            // Jika hapus obat, kembalikan stok lama
            mysqli_query($conn, "UPDATE medicines SET stock = stock + $old_medicine_qty WHERE id = '$old_medicine_id'");
        }
        $_SESSION['success'] = "Data pemeriksaan kesehatan berhasil diperbarui";
    } else {
        $_SESSION['error'] = "Error: " . mysqli_error($conn);
    }
}

header("Location: health_records.php");
exit();
?> 