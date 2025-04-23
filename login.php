<?php
session_start();
require_once 'config/database.php';

// Handle registration
if (isset($_POST['register'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Check if username already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        $register_error = "Username sudah digunakan";
    } else if ($password !== $confirm_password) {
        $register_error = "Password dan konfirmasi password tidak sesuai";
    } else {
        // Hash password with MD5
        $hashed_password = md5($password);
        
        // Insert new user as student
        $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'siswa')");
        if ($stmt->execute([$username, $hashed_password])) {
            $register_success = "Registrasi berhasil! Silakan login.";
        } else {
            $register_error = "Gagal melakukan registrasi";
        }
    }
}

// Handle login
if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && $user['password'] === md5($password)) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        header("Location: dashboard.php");
        exit();
    } else {
        $login_error = "Username atau password salah";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UKS Login & Register</title>
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
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            animation: fadeIn 1s ease;
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

        .auth-container {
            width: 100%;
            max-width: 400px;
            margin: 20px;
            animation: slideIn 0.8s ease;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-50px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: var(--card-shadow);
            overflow: hidden;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: var(--hover-shadow);
        }

        .card-header {
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            color: white;
            border: none;
            padding: 20px;
            text-align: center;
        }

        .card-body {
            padding: 30px;
        }

        .form-control {
            border-radius: 8px;
            padding: 12px;
            border: 1px solid #dee2e6;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.1);
            border-color: var(--primary-color);
            transform: translateY(-2px);
        }

        .btn {
            padding: 12px 25px;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .btn::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            transition: width 0.6s ease, height 0.6s ease;
        }

        .btn:hover::after {
            width: 300px;
            height: 300px;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            border: none;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.2);
        }

        .alert {
            border-radius: 8px;
            border: none;
            padding: 15px 20px;
            margin-bottom: 20px;
            animation: slideIn 0.5s ease;
        }

        @keyframes slideIn {
            from {
                transform: translateY(-10px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .logo-container {
            text-align: center;
            margin-bottom: 30px;
            animation: fadeIn 1s ease;
        }

        .logo {
            max-width: 250px;
            height: auto;
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0% {
                transform: translateY(0px);
            }
            50% {
                transform: translateY(-10px);
            }
            100% {
                transform: translateY(0px);
            }
        }

        .form-label {
            font-weight: 500;
            color: var(--text-color);
            margin-bottom: 8px;
            transition: all 0.3s ease;
        }

        .form-floating {
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }

        .form-floating > .form-control {
            padding: 1rem 0.75rem;
        }

        .form-floating > label {
            padding: 1rem 0.75rem;
        }

        .auth-tabs {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-bottom: 20px;
        }

        .auth-tab {
            background: none;
            border: none;
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .auth-tab::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            width: 0;
            height: 2px;
            background: white;
            transition: all 0.3s ease;
            transform: translateX(-50%);
        }

        .auth-tab:hover::after {
            width: 100%;
        }

        .auth-tab:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateY(-2px);
        }

        .auth-tab.active {
            background: rgba(255, 255, 255, 0.2);
            font-weight: 500;
        }

        .auth-tab.active::after {
            width: 100%;
        }

        .auth-form {
            transition: all 0.5s ease;
            opacity: 1;
            transform: translateX(0);
        }

        .auth-form.hidden {
            opacity: 0;
            transform: translateX(50px);
            display: none;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="logo-container">
            <img src="logo.png" alt="UKS CIHUY Logo" class="logo">
        </div>
        <div class="card">
            <div class="card-header">
                <div class="auth-tabs">
                    <button class="auth-tab active" onclick="showTab('login')">
                        <i class="fas fa-sign-in-alt me-2"></i>Login
                    </button>
                    <button class="auth-tab" onclick="showTab('register')">
                        <i class="fas fa-user-plus me-2"></i>Register
                    </button>
                </div>
            </div>
            <div class="card-body">
                <!-- Login Form -->
                <div id="login-form" class="auth-form">
                    <?php if (isset($login_error)): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle me-2"></i><?php echo $login_error; ?>
                        </div>
                    <?php endif; ?>
                    <?php if (isset($register_success)): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i><?php echo $register_success; ?>
                        </div>
                    <?php endif; ?>
                    <form method="POST">
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" id="username" name="username" placeholder="Username" required>
                            <label for="username">Username</label>
                        </div>
                        <div class="form-floating mb-4">
                            <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                            <label for="password">Password</label>
                        </div>
                        <button type="submit" name="login" class="btn btn-primary w-100">
                            <i class="fas fa-sign-in-alt me-2"></i>Login
                        </button>
                    </form>
                </div>

                <!-- Register Form -->
                <div id="register-form" class="auth-form hidden">
                    <?php if (isset($register_error)): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle me-2"></i><?php echo $register_error; ?>
                        </div>
                    <?php endif; ?>
                    <form method="POST">
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" id="reg_username" name="username" placeholder="Username" required>
                            <label for="reg_username">Username</label>
                        </div>
                        <div class="form-floating mb-3">
                            <input type="password" class="form-control" id="reg_password" name="password" placeholder="Password" required>
                            <label for="reg_password">Password</label>
                        </div>
                        <div class="form-floating mb-4">
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Konfirmasi Password" required>
                            <label for="confirm_password">Konfirmasi Password</label>
                        </div>
                        <button type="submit" name="register" class="btn btn-primary w-100">
                            <i class="fas fa-user-plus me-2"></i>Register
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showTab(tabName) {
            const loginForm = document.getElementById('login-form');
            const registerForm = document.getElementById('register-form');
            const tabs = document.querySelectorAll('.auth-tab');
            
            if (tabName === 'login') {
                registerForm.classList.add('hidden');
                setTimeout(() => {
                    loginForm.classList.remove('hidden');
                }, 300);
                tabs[0].classList.add('active');
                tabs[1].classList.remove('active');
            } else {
                loginForm.classList.add('hidden');
                setTimeout(() => {
                    registerForm.classList.remove('hidden');
                }, 300);
                tabs[0].classList.remove('active');
                tabs[1].classList.add('active');
            }
        }
    </script>
</body>
</html> 