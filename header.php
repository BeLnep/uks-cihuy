<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get current page
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UKS System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #dc3545;
            --secondary-color: #2c3e50;
            --accent-color: #e74c3c;
            --text-color: #2c3e50;
            --light-bg: #f8f9fa;
            --card-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --hover-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
        }

        body {
            background-color: var(--light-bg);
            color: var(--text-color);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* General Transitions */
        * {
            transition: all 0.3s ease-in-out;
        }

        /* Card Animations */
        .card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: none;
            border-radius: 10px;
            box-shadow: var(--card-shadow);
            background: white;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: var(--hover-shadow);
        }

        /* Button Animations */
        .btn {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            border-radius: 8px;
            font-weight: 500;
            padding: 8px 20px;
        }
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        .btn-primary:hover {
            background-color: var(--accent-color);
            border-color: var(--accent-color);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.2);
        }
        .btn-secondary {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }
        .btn-secondary:hover {
            background-color: #34495e;
            border-color: #34495e;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(44, 62, 80, 0.2);
        }

        /* Table Animations */
        .table {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: var(--card-shadow);
        }
        .table thead th {
            background-color: var(--secondary-color);
            color: white;
            font-weight: 500;
            border: none;
        }
        .table tbody tr {
            transition: background-color 0.3s ease;
        }
        .table tbody tr:hover {
            background-color: rgba(44, 62, 80, 0.05);
        }

        /* Form Control Animations */
        .form-control, .form-select {
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
            border-radius: 8px;
            border: 1px solid #dee2e6;
            padding: 10px 15px;
        }
        .form-control:focus, .form-select:focus {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(44, 62, 80, 0.1);
            border-color: var(--primary-color);
        }

        /* Alert Animations */
        .alert {
            animation: slideIn 0.5s ease;
            border-radius: 10px;
            border: none;
            box-shadow: var(--card-shadow);
        }
        @keyframes slideIn {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        /* Badge Animations */
        .badge {
            transition: transform 0.2s ease;
            padding: 6px 12px;
            border-radius: 6px;
            font-weight: 500;
        }
        .badge:hover {
            transform: scale(1.1);
        }

        /* Navigation Animations */
        .navbar {
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color)) !important;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 15px 0;
        }
        .nav-link {
            position: relative;
            font-weight: 500;
            color: white !important;
            padding: 8px 16px;
            margin: 0 5px;
            border-radius: 6px;
        }
        .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }
        .nav-link::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            bottom: 0;
            left: 0;
            background-color: #fff;
            transition: width 0.3s ease;
        }
        .nav-link:hover::after {
            width: 100%;
        }
        .nav-link.active {
            background-color: rgba(255, 255, 255, 0.2);
            color: white !important;
            font-weight: 600;
        }

        /* Page Content Animation */
        .container {
            animation: fadeIn 0.5s ease;
            padding: 20px;
        }
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Card Header Animation */
        .card-header {
            transition: background-color 0.3s ease;
            background-color: white;
            border-bottom: 1px solid rgba(0,0,0,0.1);
            padding: 15px 20px;
            font-weight: 600;
        }
        .card:hover .card-header {
            background-color: rgba(44, 62, 80, 0.02);
        }

        /* Image Animations */
        img {
            transition: transform 0.3s ease;
            border-radius: 10px;
        }
        img:hover {
            transform: scale(1.05);
        }

        /* Custom Header Style */
        .navbar-brand {
            color: white !important;
            font-weight: 700;
            font-size: 1.5rem;
            letter-spacing: 0.5px;
        }

        /* Section Headers */
        h2 {
            color: var(--secondary-color);
            font-weight: 600;
            margin-bottom: 1.5rem;
            position: relative;
            padding-bottom: 10px;
        }
        h2::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 50px;
            height: 3px;
            background-color: var(--primary-color);
        }

        /* Card Body */
        .card-body {
            padding: 20px;
        }

        /* Table Responsive */
        .table-responsive {
            border-radius: 10px;
            overflow: hidden;
        }

        /* Form Label */
        .form-label {
            font-weight: 500;
            color: var(--secondary-color);
            margin-bottom: 8px;
        }

        /* Form Group */
        .form-group {
            margin-bottom: 20px;
        }

        /* Form Text */
        .form-text {
            color: #6c757d;
            font-size: 0.875rem;
            margin-top: 5px;
        }

        /* Form Check */
        .form-check {
            margin-bottom: 10px;
        }

        /* Form Check Input */
        .form-check-input {
            margin-right: 10px;
        }

        /* Form Check Label */
        .form-check-label {
            font-weight: 500;
            color: var(--secondary-color);
        }

        /* Form Select */
        .form-select {
            background-color: white;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 10px 15px;
            font-weight: 500;
            color: var(--secondary-color);
        }

        /* Form Select Focus */
        .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(220, 53, 69, 0.25);
        }

        /* Form Select Option */
        .form-select option {
            font-weight: 500;
            color: var(--secondary-color);
        }

        /* Form Select Option Hover */
        .form-select option:hover {
            background-color: var(--light-bg);
        }

        /* Form Select Option Selected */
        .form-select option:selected {
            background-color: var(--primary-color);
            color: white;
        }

        /* Form Select Option Disabled */
        .form-select option:disabled {
            color: #6c757d;
        }

        /* Form Select Option Group */
        .form-select optgroup {
            font-weight: 600;
            color: var(--secondary-color);
        }

        /* Form Select Option Group Option */
        .form-select optgroup option {
            font-weight: 500;
            color: var(--secondary-color);
        }

        /* Form Select Option Group Option Hover */
        .form-select optgroup option:hover {
            background-color: var(--light-bg);
        }

        /* Form Select Option Group Option Selected */
        .form-select optgroup option:selected {
            background-color: var(--primary-color);
            color: white;
        }

        /* Form Select Option Group Option Disabled */
        .form-select optgroup option:disabled {
            color: #6c757d;
        }

        /* Form Select Option Group Option Group */
        .form-select optgroup optgroup {
            font-weight: 600;
            color: var(--secondary-color);
        }

        /* Form Select Option Group Option Group Option */
        .form-select optgroup optgroup option {
            font-weight: 500;
            color: var(--secondary-color);
        }

        /* Form Select Option Group Option Group Option Hover */
        .form-select optgroup optgroup option:hover {
            background-color: var(--light-bg);
        }

        /* Form Select Option Group Option Group Option Selected */
        .form-select optgroup optgroup option:selected {
            background-color: var(--primary-color);
            color: white;
        }

        /* Form Select Option Group Option Group Option Disabled */
        .form-select optgroup optgroup option:disabled {
            color: #6c757d;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">UKS System</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                            <i class="fas fa-home me-1"></i> Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page == 'health_records.php' ? 'active' : ''; ?>" href="health_records.php">
                            <i class="fas fa-notes-medical me-1"></i> Data Perawatan
                        </a>
                    </li>
                    <?php if ($_SESSION['role'] == 'admin'): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page == 'students.php' ? 'active' : ''; ?>" href="students.php">
                            <i class="fas fa-user-graduate me-1"></i> Data Siswa
                        </a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">
                            <i class="fas fa-sign-out-alt me-1"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <div class="container mt-4"> 