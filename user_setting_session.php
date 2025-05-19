<?php
session_start();
require_once 'config/database.php';

// Handler hapus user harus di paling atas sebelum HTML
if (isset($_POST['delete_user'])) {
    $user_id = mysqli_real_escape_string($conn, $_POST['user_id']);
    $check_user = mysqli_query($conn, "SELECT role FROM users WHERE id = '$user_id'");
    if ($user = mysqli_fetch_assoc($check_user)) {
        if ($user['role'] == 'admin') {
            $admin_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE role = 'admin'"))['count'];
            if ($admin_count <= 1) {
                $_SESSION['error'] = "Tidak dapat menghapus admin terakhir.";
                header("Location: user_setting_session.php");
                exit();
            }
        }
        mysqli_begin_transaction($conn);
        try {
            $tables = ['user_logs', 'user_sessions', 'remember_tokens'];
            foreach ($tables as $table) {
                $query = "DELETE FROM $table WHERE user_id = ?";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "i", $user_id);
                mysqli_stmt_execute($stmt);
            }
            $query = "DELETE FROM users WHERE id = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "i", $user_id);
            mysqli_stmt_execute($stmt);
            mysqli_commit($conn);
            $_SESSION['success'] = "User berhasil dihapus";
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $_SESSION['error'] = "Gagal menghapus user: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "User tidak ditemukan";
    }
    header("Location: user_setting_session.php");
    exit();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if user_logs table exists and has required columns
$check_table = mysqli_query($conn, "SHOW TABLES LIKE 'user_logs'");
if (mysqli_num_rows($check_table) == 0) {
    // Create user_logs table if it doesn't exist
    $create_table = "CREATE TABLE user_logs (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        page_name VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    
    if (!mysqli_query($conn, $create_table)) {
        $_SESSION['error'] = "Error creating user_logs table: " . mysqli_error($conn);
    }
} else {
    // Check if page_name column exists
    $check_column = mysqli_query($conn, "SHOW COLUMNS FROM user_logs LIKE 'page_name'");
    if (mysqli_num_rows($check_column) == 0) {
        // Add page_name column if it doesn't exist
        $add_column = "ALTER TABLE user_logs ADD COLUMN page_name VARCHAR(255) AFTER user_id";
        if (!mysqli_query($conn, $add_column)) {
            $_SESSION['error'] = "Error adding page_name column: " . mysqli_error($conn);
        }
    }
}

// Get all users with their latest session and total sessions
$query = "SELECT u.*, 
          (SELECT login_time FROM user_sessions WHERE user_id = u.id ORDER BY login_time DESC LIMIT 1) as last_login,
          (SELECT COUNT(*) FROM user_sessions WHERE user_id = u.id) as total_sessions,
          (SELECT COUNT(*) FROM user_logs WHERE user_id = u.id) as total_logs
          FROM users u 
          ORDER BY u.created_at DESC";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Settings - UKS System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #dc3545;
            --secondary-color: #c82333;
            --accent-color: #e4606d;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --light-bg: #f8f9fa;
            --dark-text: #212529;
            --card-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --hover-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--light-bg);
            color: var(--dark-text);
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

        .container {
            animation: fadeIn 0.5s ease-in forwards;
        }

        .page-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 1.5rem 0;
            margin-bottom: 2rem;
            box-shadow: var(--card-shadow);
        }

        .page-title {
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .page-description {
            opacity: 0.8;
            font-weight: 400;
        }

        .user-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: var(--card-shadow);
            transition: all 0.3s ease;
            border-left: 4px solid var(--primary-color);
            position: relative;
            overflow: hidden;
        }

        .user-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--hover-shadow);
        }

        .user-info {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .user-details {
            flex-grow: 1;
        }

        .user-username {
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 0.25rem;
            font-size: 1.2rem;
        }

        .user-role {
            font-size: 0.9rem;
            color: #6c757d;
            margin-bottom: 0.5rem;
        }
        

        .user-stats {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }

        .stat-item {
            background: var(--light-bg);
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 0.9rem;
        }

        .stat-item i {
            color: var(--primary-color);
            margin-right: 0.5rem;
        }

        .back-to-dashboard {
            background-color: #dc3545;
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            z-index: 1000;
            transition: all 0.3s ease;
            border: none;
            padding: 12px 25px;
            border-radius: 50px;
            box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
        }

        .back-to-dashboard:hover {
            transform: translateY(-5px);
            background-color: #c82333;
            box-shadow: 0 8px 20px rgba(220, 53, 69, 0.4);
        }

        .back-to-dashboard:active {
            transform: translateY(0);
            box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
        }

        .back-to-dashboard i {
            transition: transform 0.3s ease;
        }

        .back-to-dashboard:hover i {
            transform: translateX(-5px);
        }

        .badge {
            padding: 0.35rem 0.65rem;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .badge-admin {
            background-color: var(--primary-color);
            color: white;
        }

        .badge-user {
            background-color: var(--warning-color);
            color: var(--dark-text);
        }

        .user-timeline {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid rgba(0,0,0,0.1);
        }

        .timeline-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }

        .timeline-item i {
            color: var(--primary-color);
            margin-right: 0.5rem;
            margin-top: 0.2rem;
        }

        .timeline-content {
            flex-grow: 1;
        }

        .timeline-date {
            color: #6c757d;
            font-size: 0.8rem;
        }

        .view-more-logs {
            font-size: 0.85rem;
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            transition: all 0.3s ease;
        }

        .view-more-logs:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(220, 53, 69, 0.2);
        }

        .timeline-container {
            max-height: 400px;
            overflow-y: auto;
            padding: 1rem;
        }

        .timeline-container::-webkit-scrollbar {
            width: 6px;
        }

        .timeline-container::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        .timeline-container::-webkit-scrollbar-thumb {
            background: var(--primary-color);
            border-radius: 10px;
        }

        .timeline-container::-webkit-scrollbar-thumb:hover {
            background: var(--secondary-color);
        }

        .timeline-title {
            color: var(--primary-color);
            font-weight: 600;
            font-size: 0.9rem;
            margin-top: 1rem;
        }

        /* Modal Styles */
        .modal-content {
            border: none;
            border-radius: 12px;
        }

        .modal-header {
            border-bottom: none;
            padding: 1.5rem 1.5rem 0.5rem;
        }

        .modal-header .modal-title {
            font-weight: 600;
            font-size: 1.25rem;
        }

        .modal-body {
            padding: 1.5rem;
        }

        .modal-footer .btn {
            min-width: 100px;
            border-radius: 6px;
            font-weight: 500;
        }

        .modal-dialog-centered {
            display: flex;
            align-items: center;
            min-height: calc(100% - 1rem);
        }

        /* Popup Animation */
        #deleteUserPopup {
            transition: background 0.2s;
        }
        #deleteUserPopupBox {
            opacity: 0;
            transform: translateY(-30px);
            transition: opacity 0.3s, transform 0.3s;
        }
        #deleteUserPopup.active {
            background: rgba(0,0,0,0.08);
        }
        #deleteUserPopup.active #deleteUserPopupBox {
            opacity: 1;
            transform: translateY(0);
        }
        #deleteUserPopup.fadeout #deleteUserPopupBox {
            opacity: 0;
            transform: translateY(-30px);
        }

        /* Popup Button Effects */
        #deleteUserPopup .btn {
            transition: background 0.18s, transform 0.12s;
        }
        #deleteUserPopup .btn:hover {
            filter: brightness(0.92);
            transform: translateY(-2px) scale(1.04);
        }
        #deleteUserPopup .btn:active {
            filter: brightness(0.85);
            transform: scale(0.97);
        }
    </style>
</head>
<body>
    <div class="page-header text-center w-100">
        <div class="container-a">
            <h1 class="page-title display-4 fw-bold">User Management</h1>
            <p class="page-description lead">Kelola data pengguna dan sesi login</p>
        </div>
    </div>

    <div class="container">
        <?php if (isset($success)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i> <?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-12">
                <?php while ($user = mysqli_fetch_assoc($result)): ?>
                    <div class="user-card">
                        <div class="user-info">
                            <div class="user-details">
                                <div class="user-username">
                                    <?php echo htmlspecialchars($user['username']); ?>
                                    <span class="badge <?php echo $user['role'] == 'admin' ? 'badge-admin' : 'badge-user'; ?>">
                                        <?php echo $user['role'] == 'admin' ? 'Admin' : 'Siswa'; ?>
                                    </span>
                                </div>
                                <div class="user-role">
                                    <i class="fas fa-calendar-alt me-1"></i> Registrasi: <?php echo date('d/m/Y H:i', strtotime($user['created_at'])); ?>
                                </div>
                                
                                <div class="user-stats">
                                    <div class="stat-item">
                                        <i class="fas fa-sign-in-alt"></i>
                                        Total Login: <?php echo $user['total_sessions']; ?>
                                    </div>
                                    <?php if ($user['last_login']): ?>
                                    <div class="stat-item">
                                        <i class="fas fa-clock"></i>
                                        Login Terakhir: <?php echo date('d/m/Y H:i', strtotime($user['last_login'])); ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <button type="button" class="btn btn-danger btn-delete-user" data-user-id="<?php echo $user['id']; ?>" data-username="<?php echo htmlspecialchars($user['username']); ?>">Hapus</button>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>

    <a href="user_sessions.php" class="btn btn-primary back-to-dashboard">
        <i class="fas fa-arrow-left me-2"></i> Sebelumnya
    </a>

    <!-- Custom Delete Confirmation Popup -->
    <div id="deleteUserPopup" style="display:none; position:fixed; z-index:2000; left:0; top:0; width:100vw; height:100vh;">
        <div id="deleteUserPopupBox" style="max-width:420px; margin:40px auto; background:#fff; border-radius:14px; box-shadow:0 4px 24px rgba(0,0,0,0.18); overflow:hidden; position:relative;">
            <div style="background:#dc3545; color:#fff; padding:18px 24px 14px 24px; display:flex; align-items:center; justify-content:space-between;">
                <span style="font-size:1.2rem; font-weight:600;">Hapus User</span>
                <button id="closeDeletePopup" style="background:none; border:none; color:#fff; font-size:1.3rem; font-weight:600; cursor:pointer;">&times;</button>
            </div>
            <div style="padding:24px 24px 0 24px; font-size:1.05rem; color:#222;">
                <span id="deleteUserPopupText"></span>
                <ul style="margin-top:16px; color:#c82333; font-size:0.98rem;">
                    <li>Semua data user, sesi login, dan log aktivitas akan dihapus.</li>
                    <li>User harus mendaftar ulang untuk mengakses sistem.</li>
                    <li>Tindakan ini <b>tidak dapat dibatalkan</b>.</li>
                </ul>
            </div>
            <div style="display:flex; justify-content:center; gap:16px; padding:24px 0 24px 0;">
                <button id="cancelDeleteUser" class="btn" style="background:#757d85; color:#fff; min-width:90px; font-weight:500;">Batal</button>
                <form id="deleteUserForm" method="POST" style="margin:0;">
                    <input type="hidden" name="user_id" id="deleteUserIdInput">
                    <button type="submit" name="delete_user" class="btn" style="background:#dc3545; color:#fff; min-width:90px; font-weight:500;">Hapus</button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Show popup when delete button clicked
    const popup = document.getElementById('deleteUserPopup');
    const popupBox = document.getElementById('deleteUserPopupBox');
    const popupText = document.getElementById('deleteUserPopupText');
    const deleteUserIdInput = document.getElementById('deleteUserIdInput');
    function showDeletePopup(userId, username) {
        popupText.innerHTML = `Apakah Anda yakin ingin menghapus user <b>${username}</b>?`;
        deleteUserIdInput.value = userId;
        popup.style.display = 'block';
        setTimeout(() => popup.classList.add('active'), 10);
        popup.classList.remove('fadeout');
    }
    function hideDeletePopup() {
        popup.classList.add('fadeout');
        popup.classList.remove('active');
        setTimeout(() => { popup.style.display = 'none'; }, 300);
    }
    document.querySelectorAll('.btn-delete-user').forEach(btn => {
        btn.addEventListener('click', function() {
            const userId = this.getAttribute('data-user-id');
            const username = this.getAttribute('data-username');
            showDeletePopup(userId, username);
        });
    });
    document.getElementById('closeDeletePopup').onclick = hideDeletePopup;
    document.getElementById('cancelDeleteUser').onclick = hideDeletePopup;
    popup.onclick = function(e) {
        if (e.target === popup) hideDeletePopup();
    };
    </script>
</body>
</html>