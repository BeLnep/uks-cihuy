<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

// Get current page number
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 20;
$offset = ($page - 1) * $records_per_page;

// Get total number of records
$total_records_query = "SELECT COUNT(*) as total FROM user_sessions";
$total_records_result = mysqli_query($conn, $total_records_query);
$total_records = mysqli_fetch_assoc($total_records_result)['total'];
$total_pages = ceil($total_records / $records_per_page);

// Get sessions with pagination
$query = "SELECT us.*, u.username, u.role 
          FROM user_sessions us 
          JOIN users u ON us.user_id = u.id 
          ORDER BY us.login_time DESC 
          LIMIT ? OFFSET ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "ii", $records_per_page, $offset);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Get all user registrations
$query_reg = "SELECT * FROM users ORDER BY created_at DESC";
$result_reg = mysqli_query($conn, $query_reg);

// Get daily user activity for the chart
$query_activity = "SELECT 
    DATE(login_time) as date,
    COUNT(*) as count
FROM user_sessions
WHERE login_time >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
GROUP BY DATE(login_time)
ORDER BY date ASC";
$activity_result = mysqli_query($conn, $query_activity);
$activity_data = [];
while ($row = mysqli_fetch_assoc($activity_result)) {
    $activity_data[] = $row;
}

// Prepare data for the chart
$dates = [];
$counts = [];
foreach ($activity_data as $data) {
    $dates[] = date('d/m', strtotime($data['date']));
    $counts[] = (int)$data['count'];
}

// If no data, add today's date with 0 count
if (empty($dates)) {
    $dates[] = date('d/m');
    $counts[] = 0;
}

// Get statistics
$total_users = mysqli_num_rows($result_reg);
$active_users = mysqli_num_rows(mysqli_query($conn, "SELECT DISTINCT user_id FROM user_sessions WHERE login_time >= DATE_SUB(NOW(), INTERVAL 30 MINUTE)"));
$total_sessions_query = "SELECT COUNT(*) as total FROM user_sessions";
$total_sessions_result = mysqli_query($conn, $total_sessions_query);
$total_sessions = mysqli_fetch_assoc($total_sessions_result)['total'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Sessions - UKS System</title>
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

        .container{
            animation: fadeIn 0.5s ease-in forwards;
        }

        /* Staggered animation for cards */
        .card {
            opacity: 0;
            animation: fadeIn 0.5s ease-in forwards;
        }

        .page-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 1.5rem 0;
            margin-bottom: 2rem;
            box-shadow: var(--card-shadow);
            width: 100%;
            max-width: 100%;
        }

        .page-title {
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .page-description {
            opacity: 0.8;
            font-weight: 400;
        }

        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: var(--card-shadow);
            transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            height: 100%;
            position: relative;
            overflow: hidden;
            border: none;
            border-left: 4px solid var(--primary-color);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            opacity: 0;
            transition: opacity 0.5s ease;
            z-index: 0;
        }

        .stat-card:hover::before {
            opacity: 0.05;
        }

        .stat-card:hover {
            transform: translateY(-10px);
            box-shadow: var(--hover-shadow);
        }

        .stat-icon {
            font-size: 3rem;
            margin-bottom: 1.5rem;
            color: var(--primary-color);
            transition: all 0.5s ease;
            position: relative;
            z-index: 1;
        }

        .stat-card:hover .stat-icon {
            transform: scale(1.1);
            color: var(--secondary-color);
        }

        .stat-value {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: var(--primary-color);
            position: relative;
            z-index: 1;
        }

        .stat-label {
            color: #6c757d;
            font-size: 1rem;
            position: relative;
            z-index: 1;
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: var(--card-shadow);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            margin-bottom: 1.5rem;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: var(--hover-shadow);
        }

        .card-header {
            background: white;
            border-bottom: 1px solid rgba(0,0,0,0.1);
            border-radius: 15px 15px 0 0 !important;
            padding: 1.5rem;
            color: var(--primary-color);
            font-weight: 600;
        }

        .card-body {
            padding: 1.5rem;
        }

        .table {
            margin-bottom: 0;
        }

        .table th {
            border-top: none;
            font-weight: 600;
            color: var(--primary-color);
            padding: 1rem;
            background-color: rgba(220, 53, 69, 0.05);
        }

        .table td {
            padding: 1rem;
            vertical-align: middle;
        }

        .table tbody tr {
            transition: all 0.3s ease;
        }

        .table tbody tr:hover {
            background-color: rgba(220, 53, 69, 0.05);
            transform: scale(1.01);
        }

        .badge {
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-weight: 500;
        }

        .badge-online {
            background-color: #28a745;
            color: white;
            animation: pulse 2s infinite;
        }

        .badge-offline {
            background-color: #ffc107;
            color: #212529;
        }

        .badge-admin {
            background-color: var(--primary-color);
            color: white;
        }

        .badge-user {
            background-color: var(--warning-color);
            color: white;
        }

        .badge-success {
            background-color: #28a745;
            color: #fff;
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

        .btn-danger {
            transition: all 0.3s ease;
        }

        .btn-danger:hover {
            transform: scale(1.1);
            box-shadow: 0 4px 8px rgba(220, 53, 69, 0.3);
        }

        .btn-danger i {
            transition: transform 0.3s ease;
        }

        .btn-danger:hover i {
            transform: rotate(90deg);
        }
        
        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(40, 167, 69, 0.4);
            }
            70% {
                box-shadow: 0 0 0 10px rgba(40, 167, 69, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(40, 167, 69, 0);
            }
        }

        .pagination .page-link {
            color: #dc3545;
        }

        .pagination .page-link:hover {
            color: #dc3545;
            background-color: rgba(220, 53, 69, 0.1);
        }

        .pagination .page-item.active .page-link {
            background-color: #dc3545;
            border-color: #dc3545;
            color: white;
        }

        .fade-in {
            animation: fadeIn 0.5s ease-in;
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
    </style>
</head>
<body>
    <div class="page-header text-center w-100">
        <div class="containerf">
            <h1 class="page-title display-4 fw-bold">User Activity</h1>
            <p class="page-description lead">Monitoring user login dan aktifitas registrasi</p>
        </div>
    </div>

    <div class="container">
        <?php if (isset(
            $_SESSION['error']) && $_SESSION['error']): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i> <?= $_SESSION['error']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        <div class="row mb-4">
            <div class="col-md-4 mb-4">
                <div class="stat-card">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon me-3">
                            <i class="fas fa-users"></i>
                        </div>
                        <div>
                            <div class="stat-value"><?= $total_users ?></div>
                            <div class="stat-label">Total User</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="stat-card">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon me-3">
                            <i class="fas fa-user-check"></i>
                        </div>
                        <div>
                            <div class="stat-value"><?= $active_users ?></div>
                            <div class="stat-label">User Aktif</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="stat-card">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon me-3">
                            <i class="fas fa-sign-in-alt"></i>
                        </div>
                        <div>
                            <div class="stat-value"><?= $total_sessions ?></div>
                            <div class="stat-label">Total Login</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>User Activity</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="activityChart" height="300"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-user-plus me-2"></i>Registrasi Terbaru</h5>
                        <a href="user_setting_session.php" class="btn btn-sm btn-danger">
                            <i class="fas fa-cog"></i>
                        </a>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Username</th>
                                        <th>Role</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    mysqli_data_seek($result_reg, 0);
                                    $count = 0;
                                    while ($count < 5 && ($row = mysqli_fetch_assoc($result_reg))): 
                                        $count++;
                                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['username']) ?></td>
                                        <td>
                                            <span class="badge <?php
                                                if ($row['role'] == 'admin') echo 'badge-admin';
                                                else if ($row['role'] == 'teacher') echo 'badge-user';
                                                else echo 'badge-success';
                                            ?>">
                                                <?= $row['role'] == 'admin' ? 'Admin' : ($row['role'] == 'teacher' ? 'Teacher' : 'User') ?>
                                            </span>
                                        </td>
                                        <td><?= date('d/m/Y', strtotime($row['created_at'])) ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-sign-in-alt me-2"></i>Login User</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Username</th>
                                        <th>Role</th>
                                        <th>Waktu Login</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                    <tr class="fade-in">
                                        <td><?= htmlspecialchars($row['username']) ?></td>
                                        <td>
                                            <span class="badge <?php
                                                if ($row['role'] == 'admin') echo 'badge-admin';
                                                else if ($row['role'] == 'teacher') echo 'badge-user';
                                                else echo 'badge-success';
                                            ?>">
                                                <?= $row['role'] == 'admin' ? 'Admin' : ($row['role'] == 'teacher' ? 'Teacher' : 'User') ?>
                                            </span>
                                        </td>
                                        <td><?= date('d/m/Y H:i:s', strtotime($row['login_time'])) ?></td>
                                        <td>
                                            <?php
                                            $login_time = strtotime($row['login_time']);
                                            $current_time = time();
                                            $time_diff = ($current_time - $login_time) / 60; // difference in minutes
                                            $is_online = $time_diff <= 15;
                                            ?>
                                            <span class="badge <?= $is_online ? 'badge-online' : 'badge-offline' ?>">
                                                <?= $is_online ? 'Online' : 'Offline' ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                        <div class="d-flex justify-content-between align-items-center mt-4">
                            <div class="text-muted">
                                Menampilkan <?php echo $offset + 1; ?> - <?php echo min($offset + $records_per_page, $total_records); ?> dari <?php echo $total_records; ?> data
                            </div>
                            <nav aria-label="Page navigation">
                                <ul class="pagination mb-0">
                                    <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page - 1; ?>" aria-label="Previous">
                                            <span aria-hidden="true">&laquo;</span>
                                        </a>
                                    </li>
                                    <?php endif; ?>

                                    <?php
                                    $start_page = max(1, $page - 2);
                                    $end_page = min($total_pages, $page + 2);

                                    if ($start_page > 1) {
                                        echo '<li class="page-item"><a class="page-link" href="?page=1">1</a></li>';
                                        if ($start_page > 2) {
                                            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                        }
                                    }

                                    for ($i = $start_page; $i <= $end_page; $i++) {
                                        echo '<li class="page-item ' . ($i == $page ? 'active' : '') . '">';
                                        echo '<a class="page-link" href="?page=' . $i . '">' . $i . '</a>';
                                        echo '</li>';
                                    }

                                    if ($end_page < $total_pages) {
                                        if ($end_page < $total_pages - 1) {
                                            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                        }
                                        echo '<li class="page-item"><a class="page-link" href="?page=' . $total_pages . '">' . $total_pages . '</a></li>';
                                    }
                                    ?>

                                    <?php if ($page < $total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page + 1; ?>" aria-label="Next">
                                            <span aria-hidden="true">&raquo;</span>
                                        </a>
                                    </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <a href="dashboard.php" class="btn btn-primary back-to-dashboard">
        <i class="fas fa-arrow-left me-2"></i> Kembali ke Dashboard
    </a>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    // Activity Chart
    const activityCtx = document.getElementById('activityChart').getContext('2d');
    const activityChart = new Chart(activityCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($dates); ?>,
            datasets: [{
                label: 'User Logins',
                data: <?php echo json_encode($counts); ?>,
                backgroundColor: 'rgba(220, 53, 69, 0.2)',
                borderColor: 'rgba(220, 53, 69, 1)',
                borderWidth: 2,
                tension: 0.4,
                fill: true,
                pointBackgroundColor: 'rgba(220, 53, 69, 1)',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1,
                        font: {
                            family: 'Poppins',
                            size: 12
                        }
                    },
                    grid: {
                        display: true,
                        color: 'rgba(0, 0, 0, 0.05)',
                        drawBorder: false
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        font: {
                            family: 'Poppins',
                            size: 12
                        }
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                },
                title: {
                    display: true,
                    text: 'User Login Activity (7 Hari)',
                    font: {
                        family: 'Poppins',
                        size: 16,
                        weight: 'bold'
                    },
                    color: '#2c3e50',
                    padding: {
                        top: 10,
                        bottom: 20
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    titleFont: {
                        family: 'Poppins',
                        size: 14,
                        weight: 'bold'
                    },
                    bodyFont: {
                        family: 'Poppins',
                        size: 13
                    },
                    padding: 12,
                    cornerRadius: 8,
                    displayColors: false
                }
            },
            animation: {
                duration: 2000,
                easing: 'easeInOutQuart'
            },
            interaction: {
                intersect: false,
                mode: 'index'
            }
        }
    });
    </script>
</body>
</html> 