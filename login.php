<?php
session_start();
require_once 'config/database.php';

// Create necessary tables first
// Check if remember_tokens table exists, if not create it
$check_table = $conn->query("SHOW TABLES LIKE 'remember_tokens'");
if ($check_table->num_rows == 0) {
    $conn->query("CREATE TABLE remember_tokens (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        token VARCHAR(64) NOT NULL,
        expiry DATETIME NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY unique_token (token),
        INDEX (expiry)
    )");
}

// Check if user_logs table exists, if not create it
$check_table = $conn->query("SHOW TABLES LIKE 'user_logs'");
if ($check_table->num_rows == 0) {
    $conn->query("CREATE TABLE user_logs (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT,
        activity VARCHAR(255) NOT NULL,
        ip_address VARCHAR(45),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
    )");
}

// Check for remember me token
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_token'])) {
    $token = $_COOKIE['remember_token'];
    
    // Verify token from database with simplified query
    $stmt = $conn->prepare("SELECT u.* FROM users u 
                           JOIN remember_tokens rt ON u.id = rt.user_id 
                           WHERE rt.token = ? AND rt.expiry > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        
        // Delete old token
        $stmt = $conn->prepare("DELETE FROM remember_tokens WHERE token = ?");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        
        // Generate new token for continued remember me
        $new_token = bin2hex(random_bytes(32));
        $expiry = date('Y-m-d H:i:s', strtotime('+30 days'));
        
        // Store new token
        $stmt = $conn->prepare("INSERT INTO remember_tokens (user_id, token, expiry) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $user['id'], $new_token, $expiry);
        $stmt->execute();
        
        // Set new cookie
        setcookie('remember_token', $new_token, time() + (86400 * 30), '/');
        
        // Log the auto-login
        log_user_activity($conn, $user['id'], "User auto-logged in via remember me", $_SERVER['REMOTE_ADDR']);
        
        header("Location: dashboard.php");
        exit();
    } else {
        // Invalid or expired token, clear the cookie
        setcookie('remember_token', '', time() - 3600, '/');
    }
}

// Initialize variables
$login_error = '';
$register_error = '';
$register_success = '';
$reset_error = '';
$reset_success = '';

// Function to sanitize input
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Function to log user activity
function log_user_activity($conn, $user_id, $activity, $ip_address) {
    $stmt = $conn->prepare("INSERT INTO user_logs (user_id, activity, ip_address) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $user_id, $activity, $ip_address);
    $stmt->execute();
}

// Handle password reset request
if (isset($_POST['reset_request'])) {
    $email = sanitize_input($_POST['email']);
    
    // Check if email exists in users table
    $stmt = $conn->prepare("SELECT id, username FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if ($user) {
        // Generate reset token
        $token = bin2hex(random_bytes(32));
        $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // Store reset token in database
        $stmt = $conn->prepare("INSERT INTO password_resets (user_id, token, expiry) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $user['id'], $token, $expiry);
        $stmt->execute();
        
        // In a real application, send email with reset link
        // For demo purposes, we'll just show the token
        $reset_success = "Password reset link has been sent to your email. For demo: Token: " . $token;
    } else {
        $reset_error = "Email not found in our records";
    }
}

// Handle password reset
if (isset($_POST['reset_password'])) {
    $token = sanitize_input($_POST['token']);
    $new_password = sanitize_input($_POST['new_password']);
    $confirm_password = sanitize_input($_POST['confirm_password']);
    
    if ($new_password !== $confirm_password) {
        $reset_error = "Passwords do not match";
    } else {
        // Check if token is valid and not expired
        $stmt = $conn->prepare("SELECT user_id FROM password_resets WHERE token = ? AND expiry > NOW() AND used = 0");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();
        $reset = $result->fetch_assoc();
        
        if ($reset) {
            // Update password
            $hashed_password = md5($new_password);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $hashed_password, $reset['user_id']);
            $stmt->execute();
            
            // Mark token as used
            $stmt = $conn->prepare("UPDATE password_resets SET used = 1 WHERE token = ?");
            $stmt->bind_param("s", $token);
            $stmt->execute();
            
            $reset_success = "Password has been reset successfully. You can now login with your new password.";
        } else {
            $reset_error = "Invalid or expired reset token";
        }
    }
}

// Handle registration
if (isset($_POST['register'])) {
    $username = sanitize_input($_POST['username']);
    $password = sanitize_input($_POST['password']);
    $confirm_password = sanitize_input($_POST['confirm_password']);
    $email = sanitize_input($_POST['email']);

    // Validate input
    if (strlen($username) < 3) {
        $register_error = "Username must be at least 3 characters long";
    } else if (strlen($password) < 6) {
        $register_error = "Password must be at least 6 characters long";
    } else if ($password !== $confirm_password) {
        $register_error = "Password and confirm password do not match";
    } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $register_error = "Invalid email format";
    } else {
        // Check if username or email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $register_error = "Username or email already exists";
        } else {
            // Hash password with MD5 (Note: In a production environment, use a stronger hashing algorithm)
            $hashed_password = md5($password);
            
            // Insert new user as student
            $stmt = $conn->prepare("INSERT INTO users (username, password, email, role) VALUES (?, ?, ?, 'siswa')");
            $stmt->bind_param("sss", $username, $hashed_password, $email);
            
            if ($stmt->execute()) {
                $register_success = "Registration successful! Please login.";
                
                // Log the registration
                $user_id = $conn->insert_id;
                log_user_activity($conn, $user_id, "User registered", $_SERVER['REMOTE_ADDR']);
            } else {
                $register_error = "Registration failed";
            }
        }
    }
}

// Handle login
if (isset($_POST['login'])) {
    $username = sanitize_input($_POST['username']);
    $password = sanitize_input($_POST['password']);
    $remember_me = isset($_POST['remember_me']) ? true : false;

    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        if (md5($password) === $user['password']) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            
            // Record login information
            $ip_address = $_SERVER['REMOTE_ADDR'];
            $insert_session = "INSERT INTO user_sessions (user_id, username, role, ip_address) 
                              VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($insert_session);
            $stmt->bind_param("isss", $user['id'], $user['username'], $user['role'], $ip_address);
            $stmt->execute();
            
            // Set remember me cookie if requested
            if ($remember_me) {
                // Delete any existing tokens for this user
                $stmt = $conn->prepare("DELETE FROM remember_tokens WHERE user_id = ?");
                $stmt->bind_param("i", $user['id']);
                $stmt->execute();
                
                $token = bin2hex(random_bytes(32));
                $expiry = date('Y-m-d H:i:s', strtotime('+30 days'));
                
                // Store remember me token in database
                $stmt = $conn->prepare("INSERT INTO remember_tokens (user_id, token, expiry) VALUES (?, ?, ?)");
                $stmt->bind_param("iss", $user['id'], $token, $expiry);
                $stmt->execute();
                
                // Set cookie
                setcookie('remember_token', $token, time() + (86400 * 30), '/'); // 30 days
            }
            
            // Log the login
            log_user_activity($conn, $user['id'], "User logged in", $_SERVER['REMOTE_ADDR']);
            
            header("Location: dashboard.php");
            exit();
        } else {
            $login_error = "Invalid username or password";
        }
    } else {
        $login_error = "Invalid username or password";
    }
}

// Check if password_resets table exists, if not create it
$check_table = $conn->query("SHOW TABLES LIKE 'password_resets'");
if ($check_table->num_rows == 0) {
    $conn->query("CREATE TABLE password_resets (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT,
        token VARCHAR(64) NOT NULL,
        expiry DATETIME NOT NULL,
        used TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
    )");
}

// Check if users table has email column, if not add it
$result = $conn->query("SHOW COLUMNS FROM users LIKE 'email'");
if ($result->num_rows == 0) {
    $conn->query("ALTER TABLE users ADD COLUMN email VARCHAR(100) UNIQUE AFTER password");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - UKS System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #34495e;
            --accent-color: #3498db;
            --light-bg: #ffffff;
            --dark-text: #2c3e50;
            --card-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            --hover-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: var(--light-bg);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            position: relative;
            overflow: hidden;
        }

        /* Floating background elements */
        body::before,
        body::after {
            content: '';
            position: absolute;
            width: 300px;
            height: 300px;
            border-radius: 50%;
            background: linear-gradient(45deg, var(--accent-color), var(--primary-color));
            opacity: 0.1;
            animation: float 15s infinite ease-in-out;
            z-index: -1;
        }

        body::before {
            top: -150px;
            left: -150px;
            animation-delay: -5s;
        }

        body::after {
            bottom: -150px;
            right: -150px;
            animation-delay: -10s;
        }

        @keyframes float {
            0%, 100% {
                transform: translate(0, 0) rotate(0deg);
            }
            25% {
                transform: translate(50px, 50px) rotate(90deg);
            }
            50% {
                transform: translate(0, 100px) rotate(180deg);
            }
            75% {
                transform: translate(-50px, 50px) rotate(270deg);
            }
        }

        .login-container {
            width: 100%;
            max-width: 420px;
            padding: 1rem;
            animation: containerFloat 6s infinite ease-in-out;
        }

        @keyframes containerFloat {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-10px);
            }
        }

        .card {
            border: none;
            border-radius: 20px;
            box-shadow: var(--card-shadow);
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            animation: cardAppear 1s ease-out;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .card::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(
                45deg,
                transparent,
                rgba(255, 255, 255, 0.1),
                rgba(255, 255, 255, 0.2),
                rgba(255, 255, 255, 0.1),
                transparent
            );
            transform: rotate(45deg);
            animation: cardShine 6s infinite linear;
        }

        @keyframes cardShine {
            0% {
                transform: translateX(-100%) rotate(45deg);
            }
            100% {
                transform: translateX(100%) rotate(45deg);
            }
        }

        .card-header {
            background: transparent;
            border-bottom: none;
            text-align: center;
            padding: 2.5rem 2rem 1rem;
            position: relative;
            overflow: hidden;
        }

        .card-header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 50px;
            height: 3px;
            background: linear-gradient(90deg, var(--accent-color), var(--primary-color));
            border-radius: 3px;
            animation: headerLine 2s infinite ease-in-out;
        }

        @keyframes headerLine {
            0%, 100% {
                width: 50px;
            }
            50% {
                width: 100px;
            }
        }

        .logo {
            width: 100px;
            height: 100px;
            margin-bottom: 1.5rem;
            filter: drop-shadow(0 4px 6px rgba(0, 0, 0, 0.1));
            animation: logoFloat 4s infinite ease-in-out;
            position: relative;
        }

        .logo::after {
            content: '';
            position: absolute;
            top: -10px;
            left: -10px;
            right: -10px;
            bottom: -10px;
            background: radial-gradient(circle, rgba(52, 152, 219, 0.1) 0%, transparent 70%);
            border-radius: 50%;
            animation: logoGlow 3s infinite ease-in-out;
        }

        @keyframes logoGlow {
            0%, 100% {
                transform: scale(1);
                opacity: 0.5;
            }
            50% {
                transform: scale(1.2);
                opacity: 0.8;
            }
        }

        .form-control {
            border-radius: 12px;
            padding: 0.85rem 1.5rem;
            border: 2px solid rgba(238, 242, 247, 0.8);
            font-size: 0.95rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(5px);
            animation: inputAppear 0.5s ease-out forwards;
            opacity: 0;
            transform: translateY(20px);
        }

        .form-control:focus {
            border-color: var(--accent-color);
            box-shadow: 0 0 0 4px rgba(52, 152, 219, 0.15);
            transform: translateY(-2px);
            background: rgba(255, 255, 255, 1);
        }

        .input-group-text {
            border-radius: 12px 0 0 12px;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(5px);
            border: 2px solid rgba(238, 242, 247, 0.8);
            border-right: none;
            padding: 0.85rem 1.5rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .input-group:focus-within .input-group-text {
            border-color: var(--accent-color);
            background: rgba(255, 255, 255, 1);
        }

        .btn-login {
            border-radius: 12px;
            padding: 0.85rem 1.5rem;
            font-weight: 600;
            background: linear-gradient(45deg, var(--primary-color), var(--accent-color));
            border: none;
            width: 100%;
            margin-top: 1.5rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            animation: buttonAppear 0.5s ease-out 0.4s forwards;
            opacity: 0;
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.2);
        }

        .btn-login::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(
                45deg,
                transparent,
                rgba(255, 255, 255, 0.2),
                transparent
            );
            transform: translateX(-100%);
            transition: transform 0.6s;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(52, 152, 219, 0.3);
            background: linear-gradient(45deg, var(--accent-color), var(--primary-color));
        }

        .btn-login:hover::before {
            transform: translateX(100%);
        }

        .nav-tabs {
            border: none;
            margin-bottom: 2rem;
            justify-content: center;
            position: relative;
        }

        .nav-tabs::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 0;
            height: 2px;
            background: linear-gradient(90deg, var(--accent-color), var(--primary-color));
            transition: width 0.3s ease;
        }

        .nav-tabs:hover::after {
            width: 100px;
        }

        .nav-tabs .nav-link {
            border: none;
            color: #95a5a6;
            font-weight: 500;
            padding: 0.75rem 1.5rem;
            margin: 0 0.5rem;
            border-radius: 12px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .nav-tabs .nav-link::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, transparent, rgba(52, 152, 219, 0.1), transparent);
            transform: translateX(-100%);
            transition: transform 0.3s ease;
        }

        .nav-tabs .nav-link:hover::before {
            transform: translateX(100%);
        }

        .nav-tabs .nav-link.active {
            color: var(--accent-color);
            background: rgba(52, 152, 219, 0.1);
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.1);
        }

        .alert {
            border-radius: 12px;
            border: none;
            padding: 1rem 1.5rem;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(5px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            animation: alertSlide 0.5s ease-out;
        }

        @keyframes inputAppear {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .form-control:nth-child(1) { animation-delay: 0.1s; }
        .form-control:nth-child(2) { animation-delay: 0.2s; }
        .form-control:nth-child(3) { animation-delay: 0.3s; }

        .form-control:focus {
            border-color: var(--accent-color);
            box-shadow: 0 0 0 0.25rem rgba(52, 152, 219, 0.15);
            transform: translateY(-2px);
        }

        .btn-login {
            border-radius: 12px;
            padding: 0.85rem 1.5rem;
            font-weight: 600;
            background: var(--primary-color);
            border: none;
            width: 100%;
            margin-top: 1.5rem;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            animation: buttonAppear 0.5s ease-out 0.4s forwards;
            opacity: 0;
        }

        @keyframes buttonAppear {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .btn-login::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transform: translateX(-100%);
            transition: transform 0.6s;
        }

        .btn-login:hover::before {
            transform: translateX(100%);
        }

        .btn-login:hover {
            background: var(--secondary-color);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(80, 63, 44, 0.2);
        }

        .nav-tabs .nav-link {
            animation: tabAppear 0.5s ease-out forwards;
            opacity: 0;
            transform: translateY(20px);
        }

        @keyframes tabAppear {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .nav-tabs .nav-link:nth-child(1) { animation-delay: 0.2s; }
        .nav-tabs .nav-link:nth-child(2) { animation-delay: 0.3s; }

        .alert {
            animation: alertSlide 0.5s ease-out;
        }

        @keyframes alertSlide {
            from {
                transform: translateX(-100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        .card-header {
            background: transparent;
            border-bottom: none;
            text-align: center;
            padding: 2.5rem 2rem 1rem;
        }

        .card-body {
            padding: 2rem;
        }

        .card-header h4 {
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .card-header p {
            color: #7f8c8d;
            font-size: 0.95rem;
        }

        .input-group-text {
            border-radius: 12px 0 0 12px;
            background: transparent;
            border: 2px solid #eef2f7;
            border-right: none;
            padding: 0.85rem 1.5rem;
        }

        .input-group .form-control {
            border-radius: 0 12px 12px 0;
            border-left: none;
        }

        .input-group:focus-within .input-group-text,
        .input-group:focus-within .form-control {
            border-color: var(--accent-color);
        }

        .form-check-input:checked {
            background-color: var(--accent-color);
            border-color: var(--accent-color);
        }

        .forgot-password {
            text-align: right;
            margin: -0.5rem 0 1rem 0;
        }

        .forgot-password a {
            color: var(--accent-color);
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .forgot-password a:hover {
            color: var(--primary-color);
        }

        .form-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .form-footer {
            text-align: center;
            margin-top: 1.5rem;
            color: #7f8c8d;
            font-size: 0.9rem;
        }

        .form-footer a {
            color: var(--accent-color);
            text-decoration: none;
            font-weight: 500;
        }

        .form-footer a:hover {
            color: var(--primary-color);
        }

        /* Modal Styles */
        .modal-content {
            border: none;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
        }

        .modal-header {
            padding: 1.5rem;
            border-bottom: none;
        }

        .modal-title {
            color: var(--primary-color);
            font-weight: 600;
        }

        .modal-body {
            padding: 1.5rem;
        }

        .modal .input-group {
            margin-bottom: 1.5rem;
        }

        .modal .input-group-text {
            background: rgba(255, 255, 255, 0.9);
            border: 2px solid rgba(238, 242, 247, 0.8);
            border-right: none;
        }

        .modal .form-control {
            border: 2px solid rgba(238, 242, 247, 0.8);
            border-left: none;
            background: rgba(255, 255, 255, 0.9);
        }

        .modal .input-group:focus-within .input-group-text,
        .modal .input-group:focus-within .form-control {
            border-color: var(--accent-color);
            background: rgba(255, 255, 255, 1);
        }

        .modal .btn-login {
            background: #2c3e50;
            border: none;
            padding: 0.85rem 1.5rem;
            font-weight: 600;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .modal .btn-login::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transform: translateX(-100%);
            transition: transform 0.6s;
        }

        .modal .btn-login:hover::before {
            transform: translateX(100%);
        }

        .modal .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(44, 62, 80, 0.3);
            background: #2c3e50;
        }

        .modal .alert {
            border-radius: 12px;
            border: none;
            padding: 1rem 1.5rem;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(5px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            animation: alertSlide 0.5s ease-out;
        }
    </style>
</head>
<body class="login-page">
    <div class="login-container">
        <div class="card fade-in">
            <div class="card-header">
                <img src="logo.png" alt="UKS Logo" class="logo">
                <h4 class="mb-0">UKS System</h4>
                <p class="text-muted">Selamat datang di sistem UKS SMKN 5 Surakarta</p>
            </div>
            <div class="card-body">
                <ul class="nav nav-tabs" id="loginTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="login-tab" data-bs-toggle="tab" data-bs-target="#login" type="button" role="tab">
                            <i class="fas fa-sign-in-alt me-2"></i>Login
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="register-tab" data-bs-toggle="tab" data-bs-target="#register" type="button" role="tab">
                            <i class="fas fa-user-plus me-2"></i>Register
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="loginTabsContent">
                    <!-- Login Tab -->
                    <div class="tab-pane fade show active" id="login" role="tabpanel">
                        <h5 class="form-title">Login ke Akun Anda</h5>
                        <?php if (isset($login_error) && !empty($login_error)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-circle me-2"></i> <?php echo $login_error; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        <form method="POST" action="">
                            <div class="input-group mb-3">
                                <span class="input-group-text">
                                    <i class="fas fa-user"></i>
                                </span>
                                <input type="text" class="form-control" name="username" placeholder="Username" required>
                            </div>
                            <div class="input-group mb-3">
                                <span class="input-group-text">
                                    <i class="fas fa-lock"></i>
                                </span>
                                <input type="password" class="form-control" name="password" placeholder="Password" required>
                            </div>
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="remember_me" name="remember_me">
                                <label class="form-check-label" for="remember_me">
                                    Ingat saya
                                </label>
                            </div>
                            <div class="forgot-password">
                                <a href="#" data-bs-toggle="modal" data-bs-target="#forgotPasswordModal">
                                    <i class="fas fa-key me-1"></i>Lupa Password?
                                </a>
                            </div>
                            <button type="submit" name="login" class="btn btn-primary btn-login">
                                <i class="fas fa-sign-in-alt me-2"></i> Login
                            </button>
                        </form>
                    </div>

                    <!-- Register Tab -->
                    <div class="tab-pane fade" id="register" role="tabpanel">
                        <h5 class="form-title">Buat Akun Baru</h5>
                        <?php if (isset($register_error) && !empty($register_error)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-circle me-2"></i> <?php echo $register_error; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        <?php if (isset($register_success) && !empty($register_success)): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="fas fa-check-circle me-2"></i> <?php echo $register_success; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        <form method="POST" action="">
                            <div class="input-group mb-3">
                                <span class="input-group-text">
                                    <i class="fas fa-user"></i>
                                </span>
                                <input type="text" class="form-control" name="username" placeholder="Username" required>
                            </div>
                            <div class="input-group mb-3">
                                <span class="input-group-text">
                                    <i class="fas fa-envelope"></i>
                                </span>
                                <input type="email" class="form-control" name="email" placeholder="Email" required>
                            </div>
                            <div class="input-group mb-3">
                                <span class="input-group-text">
                                    <i class="fas fa-lock"></i>
                                </span>
                                <input type="password" class="form-control" name="password" placeholder="Password" required>
                            </div>
                            <div class="input-group mb-3">
                                <span class="input-group-text">
                                    <i class="fas fa-lock"></i>
                                </span>
                                <input type="password" class="form-control" name="confirm_password" placeholder="Konfirmasi Password" required>
                            </div>
                            <button type="submit" name="register" class="btn btn-primary btn-login">
                                <i class="fas fa-user-plus me-2"></i> Register
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Forgot Password Modal -->
    <div class="modal fade" id="forgotPasswordModal" tabindex="-1" aria-labelledby="forgotPasswordModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title" id="forgotPasswordModalLabel">
                        <i class="fas fa-key me-2"></i>Reset Password
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php if (isset($reset_error) && !empty($reset_error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i> <?php echo $reset_error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    <?php if (isset($reset_success) && !empty($reset_success)): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i> <?php echo $reset_success; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    <form method="POST" action="" id="resetPasswordForm">
                        <div class="input-group mb-3">
                            <span class="input-group-text">
                                <i class="fas fa-envelope"></i>
                            </span>
                            <input type="email" class="form-control" name="email" placeholder="Masukkan email Anda" required>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" name="reset_request" class="btn btn-primary btn-login">
                                <i class="fas fa-paper-plane me-2"></i> Kirim Link Reset
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto close alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    const closeButton = alert.querySelector('.btn-close');
                    if (closeButton) {
                        closeButton.click();
                    }
                }, 5000);
            });
        });
    </script>
</body>
</html> 