<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: dashboard.php");
    exit();
}

// Get and validate input
$article_id = mysqli_real_escape_string($conn, $_POST['article_id']);
$comment = mysqli_real_escape_string($conn, $_POST['comment']);
$user_id = $_SESSION['user_id'];

// Validate comment is not empty
if (empty($comment)) {
    $_SESSION['error'] = "Komentar tidak boleh kosong.";
    header("Location: article_detail.php?id=" . $article_id);
    exit();
}

// Insert comment into database
$query = "INSERT INTO comments (article_id, user_id, content, created_at) 
          VALUES ('$article_id', '$user_id', '$comment', NOW())";

if (mysqli_query($conn, $query)) {
    $_SESSION['success'] = "Komentar berhasil ditambahkan.";
} else {
    $_SESSION['error'] = "Gagal menambahkan komentar. Silakan coba lagi.";
}

// Redirect back to article
header("Location: article_detail.php?id=" . $article_id);
exit(); 