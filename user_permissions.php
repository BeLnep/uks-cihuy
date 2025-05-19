<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Anda harus login terlebih dahulu.";
    header("Location: login.php");
    exit();
}

// Check if user is admin
if ($_SESSION['role'] !== 'admin') {
    $_SESSION['error'] = "Anda tidak memiliki akses ke halaman ini.";
    header("Location: dashboard.php");
    exit();
}

// Handle form submission for updating user permissions
if (isset($_POST['update_permissions'])) {
    $user_id = (int)$_POST['user_id'];
    $role = mysqli_real_escape_string($conn, $_POST['role']);
    
    $query = "UPDATE users SET role = '$role' WHERE id = $user_id";
    
    if (mysqli_query($conn, $query)) {
        $_SESSION['success'] = "Hak akses pengguna berhasil diperbarui.";
    } else {
        $_SESSION['error'] = "Gagal memperbarui hak akses pengguna: " . mysqli_error($conn);
    }
    
    header("Location: user_permissions.php");
    exit();
}

// Get all users with their roles
$query = "SELECT id, username, role, created_at FROM users ORDER BY username ASC";
$result = mysqli_query($conn, $query);
$users = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Get user statistics
$total_users = count($users);
$admin_count = 0;
$teacher_count = 0;
$student_count = 0;

foreach ($users as $user) {
    if ($user['role'] === 'admin') {
        $admin_count++;
    } elseif ($user['role'] === 'teacher') {
        $teacher_count++;
    } elseif ($user['role'] === 'student') {
        $student_count++;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Permissions - UKS CIHUY</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link href="assets/css/style.css" rel="stylesheet">
    <link href="assets/css/transitions.css" rel="stylesheet">
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
            background-color: var(--light-bg);
            color: var(--dark-text);
            font-family: 'Poppins', sans-serif;
        }

        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: var(--card-shadow);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            height: 100%;
            border-left: 4px solid var(--primary-color);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--hover-shadow);
        }

        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: var(--primary-color);
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: var(--primary-color);
        }

        .stat-label {
            font-size: 0.9rem;
            color: #6c757d;
        }

        .permission-card {
            background: white;
            border-radius: 15px;
            box-shadow: var(--card-shadow);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            overflow: hidden;
        }

        .permission-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--hover-shadow);
        }

        .permission-card .card-header {
            background: white;
            border-bottom: 1px solid rgba(0,0,0,0.1);
            padding: 1.5rem;
            color: var(--primary-color);
            font-weight: 600;
        }

        .table th {
            border-top: none;
            font-weight: 600;
            color: var(--primary-color);
            background-color: rgba(220, 53, 69, 0.05);
            padding: 1rem;
        }

        .table td {
            padding: 1rem;
            vertical-align: middle;
        }

        .table tbody tr {
            transition: all 0.3s ease;
        }

        .table tbody tr:hover {
            background-color: rgba(220, 53, 69, 0.03);
        }

        .badge {
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-weight: 500;
        }

        .badge-admin {
            background-color: var(--warning-color);
            color: white;
        }

        .badge-teacher {
            background-color: var(--success-color);
            color: white;
        }

        .badge-student {
            background-color: var(--accent-color);
            color: white;
        }

        .btn-edit {
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 50px;
            padding: 0.5rem 1.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-edit:hover {
            background-color: var(--secondary-color);
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">User Permissions</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button type="button" class="btn btn-primary" onclick="window.location.reload()">
                            <i class='bx bx-refresh'></i> Refresh
                        </button>
                    </div>
                </div>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php 
                        echo $_SESSION['success'];
                        unset($_SESSION['success']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php 
                        echo $_SESSION['error'];
                        unset($_SESSION['error']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="d-flex align-items-center">
                                <div class="stat-icon me-3">
                                    <i class='bx bx-user'></i>
                                </div>
                                <div>
                                    <div class="stat-value"><?php echo $total_users; ?></div>
                                    <div class="stat-label">Total Users</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="d-flex align-items-center">
                                <div class="stat-icon me-3">
                                    <i class='bx bx-user-check'></i>
                                </div>
                                <div>
                                    <div class="stat-value"><?php echo $admin_count; ?></div>
                                    <div class="stat-label">Admin Users</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="d-flex align-items-center">
                                <div class="stat-icon me-3">
                                    <i class='bx bx-user-pin'></i>
                                </div>
                                <div>
                                    <div class="stat-value"><?php echo $teacher_count; ?></div>
                                    <div class="stat-label">Teacher Users</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="d-flex align-items-center">
                                <div class="stat-icon me-3">
                                    <i class='bx bx-user-voice'></i>
                                </div>
                                <div>
                                    <div class="stat-value"><?php echo $student_count; ?></div>
                                    <div class="stat-label">Student Users</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Users Table -->
                <div class="permission-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">User Permissions</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Username</th>
                                        <th>Role</th>
                                        <th>Created At</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $user['role'] === 'admin' ? 'danger' : 
                                                        ($user['role'] === 'teacher' ? 'warning' : 'info'); 
                                                ?>">
                                                    <?php echo ucfirst($user['role']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('d/m/Y H:i:s', strtotime($user['created_at'])); ?></td>
                                            <td>
                                                <button type="button" class="btn btn-edit" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $user['id']; ?>">
                                                    <i class='bx bx-edit-alt'></i> Edit
                                                </button>
                                            </td>
                                        </tr>

                                        <!-- Edit Modal -->
                                        <div class="modal fade" id="editModal<?php echo $user['id']; ?>" tabindex="-1" aria-labelledby="editModalLabel<?php echo $user['id']; ?>" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="editModalLabel<?php echo $user['id']; ?>">Edit User Permissions</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <form action="user_permissions.php" method="post">
                                                        <div class="modal-body">
                                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                            <div class="mb-3">
                                                                <label for="username<?php echo $user['id']; ?>" class="form-label">Username</label>
                                                                <input type="text" class="form-control" id="username<?php echo $user['id']; ?>" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label for="role<?php echo $user['id']; ?>" class="form-label">Role</label>
                                                                <select class="form-select" id="role<?php echo $user['id']; ?>" name="role" required>
                                                                    <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                                                    <option value="teacher" <?php echo $user['role'] === 'teacher' ? 'selected' : ''; ?>>Teacher</option>
                                                                    <option value="student" <?php echo $user['role'] === 'student' ? 'selected' : ''; ?>>Student</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" name="update_permissions" class="btn btn-primary">Save Changes</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <?php require_once 'includes/footer.php'; ?>
</body>
</html> 