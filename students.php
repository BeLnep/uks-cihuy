<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    $_SESSION['error'] = "Silakan login terlebih dahulu";
    header("Location: login.php");
    exit();
}

// Include database configuration
require_once 'config/database.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Fix database structure
$fix_table = "ALTER TABLE students MODIFY COLUMN gender ENUM('Laki-laki', 'Perempuan') NOT NULL";
if (!mysqli_query($conn, $fix_table)) {
    error_log("Error fixing table structure: " . mysqli_error($conn));
}

// Pagination settings
$records_per_page = 30;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $records_per_page;

// Get statistics
$total_students = 0;
$male_students = 0;
$female_students = 0;

// Modify the query to include search functionality
$search_condition = "";
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = mysqli_real_escape_string($conn, $_GET['search']);
    $search_condition = " WHERE (name LIKE '%$search%' OR nis LIKE '%$search%')";
}

// Get total counts first
$count_query = "SELECT 
    COUNT(*) as total_count,
    SUM(CASE WHEN gender = 'Laki-laki' THEN 1 ELSE 0 END) as male_count,
    SUM(CASE WHEN gender = 'Perempuan' THEN 1 ELSE 0 END) as female_count
FROM students" . $search_condition;

$count_result = mysqli_query($conn, $count_query);

if (!$count_result) {
    error_log("Error in count query: " . mysqli_error($conn));
    $_SESSION['error'] = "Error fetching student counts";
} else {
    $counts = mysqli_fetch_assoc($count_result);
    $total_students = (int)$counts['total_count'];
    $male_students = (int)$counts['male_count'];
    $female_students = (int)$counts['female_count'];
    $total_pages = ceil($total_students / $records_per_page);
    
    // Adjust page number if it exceeds total pages
    if ($page > $total_pages && $total_pages > 0) {
        header("Location: ?page=" . $total_pages . (isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : ''));
        exit();
    }
}

// Get paginated students data
$query = "SELECT * FROM students" . $search_condition . " ORDER BY name ASC LIMIT ?, ?";
$stmt = mysqli_prepare($conn, $query);

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "ii", $offset, $records_per_page);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (!$result) {
        error_log("Error fetching students: " . mysqli_error($conn));
        $_SESSION['error'] = "Error fetching students";
    } else {
        $students = mysqli_fetch_all($result, MYSQLI_ASSOC);
    }
    mysqli_stmt_close($stmt);
} else {
    error_log("Error preparing statement: " . mysqli_error($conn));
    $_SESSION['error'] = "Error preparing database query";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Siswa - UKS CIHUY</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
        }

        body {
            background-color: var(--light-bg);
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
        }

        .page-header {
            background: var(--primary-color);
            padding:2rem 0;
            margin-bottom: 2rem;
            text-align: center;
            color: white;
        }

        .page-header h1 {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .page-header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: var(--card-shadow);
            transition: all 0.3s ease;
            margin-bottom: 1rem;
            animation: slideIn 0.5s ease-out forwards;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
        }

        .stat-icon {
            background: var(--primary-color);
            color: white;
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: var(--dark-text);
            margin-bottom: 0.25rem;
        }

        .stat-label {
            color: #6c757d;
            font-size: 0.9rem;
        }

        .content-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            animation: fadeIn 0.8s ease-out forwards;
            opacity: 0;
            animation-delay: 0.3s;
        }

        .content-title {
            color: var(--primary-color);
            font-size: 1.25rem;
            font-weight: 600;
            margin: 0;
        }

        .btn-danger {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            padding: 0.5rem 1rem;
            opacity: 0;
            animation: fadeIn 0.8s ease-out forwards;
            animation-delay: 0.5s;
            position: relative;
            overflow: hidden;
        }

        .btn-danger::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
        }

        .btn-danger:hover::before {
            width: 200%;
            height: 200%;
        }

        .content-header .btn-danger {
            transform: scale(1);
            transition: transform 0.3s ease;
        }

        .content-header .btn-danger:hover {
            transform: scale(1.05);
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: var(--card-shadow);
            overflow: hidden;
            opacity: 0;
            animation: slideInFromBottom 0.8s ease-out forwards;
            animation-delay: 0.6s;
        }

        .table {
            margin-bottom: 0;
        }

        .table th {
            background-color: rgba(220, 53, 69, 0.05);
            color: var(--primary-color);
            font-weight: 600;
            border-top: none;
            padding: 1rem;
        }

        .table td {
            vertical-align: middle;
            padding: 1rem;
        }

        .table tr {
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
        }

        .table tr:hover {
            background-color: rgba(220, 53, 69, 0.05);
            transform: scale(1.01);
            box-shadow: 0 2px 8px rgba(220, 53, 69, 0.1);
            z-index: 1;
        }

        .table tr.selected {
            background-color: rgba(220, 53, 69, 0.08);
            transform: scale(1.01);
            box-shadow: 0 2px 8px rgba(220, 53, 69, 0.1);
            z-index: 1;
        }

        .table td {
            vertical-align: middle;
            padding: 1rem;
            transition: all 0.3s ease;
        }

        .table tr:hover td {
            color: var(--primary-color);
        }

        .table tr.selected td {
            color: var(--primary-color);
        }

        .action-buttons .btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
            margin: 0 0.25rem;
        }

        .back-to-dashboard {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            padding: 0.75rem 1.5rem;
            border-radius: 50px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            z-index: 1000;
            transition: all 0.3s ease;
            background-color: #dc3545;
            border-color: #c82333;
        }

        .back-to-dashboard:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            background-color: #dc3545;
            border-color: #c82333;
        }

        .back-to-dashboard i {
            transition: transform 0.3s ease;
        }

        .back-to-dashboard:hover i {
            transform: translateX(-3px);
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        @keyframes slideInFromBottom {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .badge {
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-weight: 500;
            font-size: 0.875rem;
            display: inline-block;
            min-width: 80px;
            text-align: center;
        }

        .badge.bg-primary {
            background-color: #007bff !important;
            color: white;
        }

        .badge.bg-danger {
            background-color: #dc3545 !important;
            color: white;
        }

        .table td {
            vertical-align: middle;
            padding: 1rem;
        }

        .table td .badge {
            margin: 0;
            white-space: nowrap;
        }

        .modal-content {
            border-radius: 15px;
            border: none;
        }

        .modal-header {
            background-color: var(--primary-color);
            color: white;
            border-radius: 15px 15px 0 0;
            padding: 1.5rem;
        }

        .modal-header .btn-close {
            color: white;
            opacity: 1;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
        }

        .empty-state i {
            font-size: 4rem;
            color: var(--primary-color);
            opacity: 0.5;
            margin-bottom: 1rem;
        }

        .empty-state p {
            color: #6c757d;
            font-size: 1.1rem;
            margin: 0;
        }

        .search-form {
            min-width: 300px;
        }
        
        .search-form .input-group {
            border-radius: 50px;
            overflow: hidden;
        }
        
        .search-form .form-control {
            border-top-left-radius: 50px;
            border-bottom-left-radius: 50px;
            border-right: none;
            padding-left: 20px;
        }
        
        .search-form .btn {
            border-top-right-radius: 50px;
            border-bottom-right-radius: 50px;
            border-left: none;
            padding-right: 20px;
        }
        
        .search-form .form-control:focus {
            box-shadow: none;
            border-color: #ced4da;
        }
        
        .search-form .btn:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }

        /* Pagination styles */
        .pagination .page-link {
            color: #dc3545;
        }

        .pagination .page-link:hover {
            color: white;
            background-color: #dc3545;
            border-color: #dc3545;
        }

        .pagination .page-item.active .page-link {
            background-color: #dc3545;
            border-color: #dc3545;
            color: white;
        }

        .pagination .page-item.disabled .page-link {
            color: #6c757d;
            border-color: #dee2e6;
        }
    </style>
</head>
<body>

<div class="page-header">
    <h1>Data Siswa</h1>
    <p>Kelola data siswa di UKS</p>
</div>

<div class="container">
    <div class="row">
        <div class="col-md-4">
            <div class="stat-card">
                <div class="d-flex align-items-center">
                    <div class="stat-icon me-3">
                        <i class="fas fa-users"></i>
                    </div>
                    <div>
                        <div class="stat-value"><?php echo $total_students; ?></div>
                        <div class="stat-label">Total Siswa</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <div class="d-flex align-items-center">
                    <div class="stat-icon me-3">
                        <i class="fas fa-male"></i>
                    </div>
                    <div>
                        <div class="stat-value"><?php echo $male_students; ?></div>
                        <div class="stat-label">Siswa Laki-laki</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <div class="d-flex align-items-center">
                    <div class="stat-icon me-3">
                        <i class="fas fa-female"></i>
                    </div>
                    <div>
                        <div class="stat-value"><?php echo $female_students; ?></div>
                        <div class="stat-label">Siswa Perempuan</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="content-header">
        <h5 class="content-title">Daftar Siswa</h5>
        <div class="d-flex align-items-center">
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
            <button type="button" class="btn btn-danger me-2" data-bs-toggle="modal" data-bs-target="#addStudentModal">
                <i class="fas fa-plus me-2"></i>Tambah Siswa
            </button>
            <?php endif; ?>
            <form action="" method="GET" class="search-form">
                <div class="input-group">
                    <input type="text" class="form-control" name="search" placeholder="Cari nama/NIS..." value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
                    <button class="btn btn-danger" type="submit">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i><?= $_SESSION['success']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i><?= $_SESSION['error']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>NIS</th>
                            <th>Nama Siswa</th>
                            <th>Kelas</th>
                            <th>Jenis Kelamin</th>
                            <th>Tanggal Daftar</th>
                            <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
                            <th>Aksi</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if (!empty($students)):
                            $no = ($page - 1) * $records_per_page + 1;
                            foreach ($students as $student): 
                        ?>
                        <tr>
                            <td><?= $no++; ?></td>
                            <td><?= htmlspecialchars($student['nis']); ?></td>
                            <td><?= htmlspecialchars($student['name']); ?></td>
                            <td><?= htmlspecialchars($student['class']); ?></td>
                            <td>
                                <?php 
                                    $gender = htmlspecialchars($student['gender']);
                                    if ($gender === 'Laki-laki') {
                                        echo '<span class="badge bg-primary">' . $gender . '</span>';
                                    } elseif ($gender === 'Perempuan') {
                                        echo '<span class="badge bg-danger">' . $gender . '</span>';
                                    } else {
                                        echo '<span class="badge bg-secondary">Tidak Diketahui</span>';
                                    }
                                ?>
                            </td>
                            <td><?= date('d/m/Y', strtotime($student['created_at'])); ?></td>
                            <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
                            <td>
                                <div class="action-buttons">
                                    <button type="button" class="btn btn-warning edit-btn" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editStudentModal"
                                            data-id="<?= $student['id']; ?>"
                                            data-nis="<?= htmlspecialchars($student['nis']); ?>"
                                            data-name="<?= htmlspecialchars($student['name']); ?>"
                                            data-class="<?= htmlspecialchars($student['class']); ?>"
                                            data-gender="<?= htmlspecialchars($student['gender']); ?>"
                                            onclick="setEditFormData(this)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button" class="btn btn-danger delete-btn"
                                            data-bs-toggle="modal"
                                            data-bs-target="#deleteStudentModal"
                                            data-id="<?= $student['id']; ?>"
                                            data-name="<?= htmlspecialchars($student['name']); ?>">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <?php 
                            endforeach;
                        else:
                        ?>
                        <tr>
                            <td colspan="<?= isset($_SESSION['role']) && $_SESSION['role'] == 'admin' ? '7' : '6' ?>" class="text-center">
                                <div class="empty-state">
                                    <i class="fas fa-user-graduate"></i>
                                    <p>Tidak ada data siswa</p>
                                </div>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                <?php if (isset($total_pages) && $total_pages > 1): ?>
                <nav aria-label="Page navigation" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo ($page - 1) . (isset($_GET['search']) ? '&search=' . htmlspecialchars($_GET['search']) : ''); ?>" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);
                        
                        if ($start_page > 1) {
                            echo '<li class="page-item"><a class="page-link" href="?page=1' . (isset($_GET['search']) ? '&search=' . htmlspecialchars($_GET['search']) : '') . '">1</a></li>';
                            if ($start_page > 2) {
                                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                            }
                        }
                        
                        for ($i = $start_page; $i <= $end_page; $i++) {
                            echo '<li class="page-item ' . ($page == $i ? 'active' : '') . '">';
                            echo '<a class="page-link" href="?page=' . $i . (isset($_GET['search']) ? '&search=' . htmlspecialchars($_GET['search']) : '') . '">' . $i . '</a>';
                            echo '</li>';
                        }
                        
                        if ($end_page < $total_pages) {
                            if ($end_page < $total_pages - 1) {
                                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                            }
                            echo '<li class="page-item"><a class="page-link" href="?page=' . $total_pages . (isset($_GET['search']) ? '&search=' . htmlspecialchars($_GET['search']) : '') . '">' . $total_pages . '</a></li>';
                        }
                        ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo ($page + 1) . (isset($_GET['search']) ? '&search=' . htmlspecialchars($_GET['search']) : ''); ?>" aria-label="Next">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Back to Dashboard Button -->
<a href="dashboard.php" class="btn btn-primary back-to-dashboard">
        <i class="fas fa-arrow-left me-2"></i> Kembali ke Dashboard
    </a>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
<!-- Add Student Modal -->
<div class="modal fade" id="addStudentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Siswa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="add_student.php">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">NIS</label>
                        <input type="text" class="form-control" name="nis" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nama Siswa</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kelas</label>
                        <select class="form-select" name="class" required>
                            <option value="">Pilih Kelas</option>
                            <!-- DPIB -->
                            <option value="X DPIB A">X DPIB A</option>
                            <option value="X DPIB B">X DPIB B</option>
                            <option value="XI DPIB A">XI DPIB A</option>
                            <option value="XI DPIB B">XI DPIB B</option>
                            <option value="XII DPIB A">XII DPIB A</option>
                            <option value="XII DPIB B">XII DPIB B</option>
                            
                            <!-- TKP -->
                            <option value="X TKP">X TKP</option>
                            <option value="XI TKP">XI TKP</option>
                            <option value="XII TKP">XII TKP</option>
                            
                            <!-- TM -->
                            <option value="X TM A">X TM A</option>
                            <option value="X TM B">X TM B</option>
                            <option value="X TM C">X TM C</option>
                            <option value="X TM D">X TM D</option>
                            <option value="X TM E">X TM E</option>
                            <option value="XI TM A">XI TM A</option>
                            <option value="XI TM B">XI TM B</option>
                            <option value="XI TM C">XI TM C</option>
                            <option value="XI TM D">XI TM D</option>
                            <option value="XI TM E">XI TM E</option>
                            <option value="XII TM A">XII TM A</option>
                            <option value="XII TM B">XII TM B</option>
                            <option value="XII TM C">XII TM C</option>
                            <option value="XII TM D">XII TM D</option>
                            <option value="XII TM E">XII TM E</option>
                            
                            <!-- TKRO -->
                            <option value="X TKRO A">X TKRO A</option>
                            <option value="X TKRO B">X TKRO B</option>
                            <option value="XI TKRO A">XI TKRO A</option>
                            <option value="XI TKRO B">XI TKRO B</option>
                            <option value="XII TKRO A">XII TKRO A</option>
                            <option value="XII TKRO B">XII TKRO B</option>
                            
                            <!-- TBSM -->
                            <option value="X TBSM A">X TBSM A</option>
                            <option value="X TBSM B">X TBSM B</option>
                            <option value="XI TBSM A">XI TBSM A</option>
                            <option value="XI TBSM B">XI TBSM B</option>
                            <option value="XII TBSM A">XII TBSM A</option>
                            <option value="XII TBSM B">XII TBSM B</option>
                            
                            <!-- TEI -->
                            <option value="X TEI A">X TEI A</option>
                            <option value="X TEI B">X TEI B</option>
                            <option value="X TEI C">X TEI C</option>
                            <option value="XI TEI A">XI TEI A</option>
                            <option value="XI TEI B">XI TEI B</option>
                            <option value="XI TEI C">XI TEI C</option>
                            <option value="XII TEI A">XII TEI A</option>
                            <option value="XII TEI B">XII TEI B</option>
                            <option value="XII TEI C">XII TEI C</option>
                            
                            <!-- TK -->
                            <option value="X TK A">X TK A</option>
                            <option value="X TK B">X TK B</option>
                            <option value="XI TK A">XI TK A</option>
                            <option value="XI TK B">XI TK B</option>
                            <option value="XII TK A">XII TK A</option>
                            <option value="XII TK B">XII TK B</option>
                            
                            <!-- TOI -->
                            <option value="X TOI A">X TOI A</option>
                            <option value="X TOI B">X TOI B</option>
                            <option value="X TOI C">X TOI C</option>
                            <option value="XI TOI A">XI TOI A</option>
                            <option value="XI TOI B">XI TOI B</option>
                            <option value="XI TOI C">XI TOI C</option>
                            <option value="XII TOI A">XII TOI A</option>
                            <option value="XII TOI B">XII TOI B</option>
                            <option value="XII TOI C">XII TOI C</option>
                            
                            <!-- PPLG -->
                            <option value="X PPLG A">X PPLG A</option>
                            <option value="X PPLG B">X PPLG B</option>
                            <option value="X PPLG C">X PPLG C</option>
                            <option value="XI PPLG A">XI PPLG A</option>
                            <option value="XI PPLG B">XI PPLG B</option>
                            <option value="XI PPLG C">XI PPLG C</option>
                            <option value="XII PPLG A">XII PPLG A</option>
                            <option value="XII PPLG B">XII PPLG B</option>
                            <option value="XII PPLG C">XII PPLG C</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Jenis Kelamin</label>
                        <select class="form-select" name="gender" required>
                            <option value="">Pilih Jenis Kelamin</option>
                            <option value="Laki-laki">Laki-laki</option>
                            <option value="Perempuan">Perempuan</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Student Modal -->
<div class="modal fade" id="editStudentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Siswa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="edit_student.php">
                <input type="hidden" name="student_id" id="edit_student_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">NIS</label>
                        <input type="text" class="form-control" name="nis" id="edit_nis" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nama Siswa</label>
                        <input type="text" class="form-control" name="name" id="edit_name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kelas</label>
                        <select class="form-select" name="class" id="edit_class" required>
                            <option value="">Pilih Kelas</option>
                            <!-- DPIB -->
                            <option value="X DPIB A">X DPIB A</option>
                            <option value="X DPIB B">X DPIB B</option>
                            <option value="XI DPIB A">XI DPIB A</option>
                            <option value="XI DPIB B">XI DPIB B</option>
                            <option value="XII DPIB A">XII DPIB A</option>
                            <option value="XII DPIB B">XII DPIB B</option>
                            
                            <!-- TKP -->
                            <option value="X TKP">X TKP</option>
                            <option value="XI TKP">XI TKP</option>
                            <option value="XII TKP">XII TKP</option>
                            
                            <!-- TM -->
                            <option value="X TM A">X TM A</option>
                            <option value="X TM B">X TM B</option>
                            <option value="X TM C">X TM C</option>
                            <option value="X TM D">X TM D</option>
                            <option value="X TM E">X TM E</option>
                            <option value="XI TM A">XI TM A</option>
                            <option value="XI TM B">XI TM B</option>
                            <option value="XI TM C">XI TM C</option>
                            <option value="XI TM D">XI TM D</option>
                            <option value="XI TM E">XI TM E</option>
                            <option value="XII TM A">XII TM A</option>
                            <option value="XII TM B">XII TM B</option>
                            <option value="XII TM C">XII TM C</option>
                            <option value="XII TM D">XII TM D</option>
                            <option value="XII TM E">XII TM E</option>
                            
                            <!-- TKRO -->
                            <option value="X TKRO A">X TKRO A</option>
                            <option value="X TKRO B">X TKRO B</option>
                            <option value="XI TKRO A">XI TKRO A</option>
                            <option value="XI TKRO B">XI TKRO B</option>
                            <option value="XII TKRO A">XII TKRO A</option>
                            <option value="XII TKRO B">XII TKRO B</option>
                            
                            <!-- TBSM -->
                            <option value="X TBSM A">X TBSM A</option>
                            <option value="X TBSM B">X TBSM B</option>
                            <option value="XI TBSM A">XI TBSM A</option>
                            <option value="XI TBSM B">XI TBSM B</option>
                            <option value="XII TBSM A">XII TBSM A</option>
                            <option value="XII TBSM B">XII TBSM B</option>
                            
                            <!-- TEI -->
                            <option value="X TEI A">X TEI A</option>
                            <option value="X TEI B">X TEI B</option>
                            <option value="X TEI C">X TEI C</option>
                            <option value="XI TEI A">XI TEI A</option>
                            <option value="XI TEI B">XI TEI B</option>
                            <option value="XI TEI C">XI TEI C</option>
                            <option value="XII TEI A">XII TEI A</option>
                            <option value="XII TEI B">XII TEI B</option>
                            <option value="XII TEI C">XII TEI C</option>
                            
                            <!-- TK -->
                            <option value="X TK A">X TK A</option>
                            <option value="X TK B">X TK B</option>
                            <option value="XI TK A">XI TK A</option>
                            <option value="XI TK B">XI TK B</option>
                            <option value="XII TK A">XII TK A</option>
                            <option value="XII TK B">XII TK B</option>
                            
                            <!-- TOI -->
                            <option value="X TOI A">X TOI A</option>
                            <option value="X TOI B">X TOI B</option>
                            <option value="X TOI C">X TOI C</option>
                            <option value="XI TOI A">XI TOI A</option>
                            <option value="XI TOI B">XI TOI B</option>
                            <option value="XI TOI C">XI TOI C</option>
                            <option value="XII TOI A">XII TOI A</option>
                            <option value="XII TOI B">XII TOI B</option>
                            <option value="XII TOI C">XII TOI C</option>
                            
                            <!-- PPLG -->
                            <option value="X PPLG A">X PPLG A</option>
                            <option value="X PPLG B">X PPLG B</option>
                            <option value="X PPLG C">X PPLG C</option>
                            <option value="XI PPLG A">XI PPLG A</option>
                            <option value="XI PPLG B">XI PPLG B</option>
                            <option value="XI PPLG C">XI PPLG C</option>
                            <option value="XII PPLG A">XII PPLG A</option>
                            <option value="XII PPLG B">XII PPLG B</option>
                            <option value="XII PPLG C">XII PPLG C</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Jenis Kelamin</label>
                        <select class="form-select" name="gender" id="edit_gender" required>
                            <option value="">Pilih Jenis Kelamin</option>
                            <option value="Laki-laki">Laki-laki</option>
                            <option value="Perempuan">Perempuan</option>
                        </select>
                    </div>
                </div>  
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Student Modal -->
<div class="modal fade" id="deleteStudentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Hapus Siswa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Apakah Anda yakin ingin menghapus siswa <strong id="delete_student_name"></strong>?</p>
                <p class="text-danger">Tindakan ini tidak dapat dibatalkan.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <form method="POST" action="delete_student.php" style="display: inline;">
                    <input type="hidden" name="student_id" id="delete_student_id">
                    <button type="submit" class="btn btn-danger">Hapus</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

</body>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function setEditFormData(button) {
    const id = button.getAttribute('data-id');
    const nis = button.getAttribute('data-nis');
    const name = button.getAttribute('data-name');
    const class_ = button.getAttribute('data-class');
    const gender = button.getAttribute('data-gender');

    document.getElementById('edit_student_id').value = id;
    document.getElementById('edit_nis').value = nis;
    document.getElementById('edit_name').value = name;
    document.getElementById('edit_class').value = class_;
    document.getElementById('edit_gender').value = gender;

    console.log('Setting gender to:', gender); // Debug log
}

document.addEventListener('DOMContentLoaded', function() {
    // Add click handler for table rows
    const tableRows = document.querySelectorAll('table tbody tr');
    tableRows.forEach(row => {
        row.addEventListener('click', function() {
            tableRows.forEach(r => r.classList.remove('selected'));
            this.classList.add('selected');
        });
    });

    // Add form submission handler for edit form
    const editForm = document.querySelector('#editStudentModal form');
    if (editForm) {
        editForm.addEventListener('submit', function(e) {
            const genderSelect = document.getElementById('edit_gender');
            if (!genderSelect.value) {
                e.preventDefault();
                alert('Silakan pilih jenis kelamin');
                return false;
            }
        });
    }

    // Delete button click handler
    document.querySelectorAll('.delete-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            e.stopPropagation();
            const id = this.dataset.id;
            const name = this.dataset.name;
            
            document.getElementById('delete_student_id').value = id;
            document.getElementById('delete_student_name').textContent = name;
        });
    });
});
</script>
</html>
