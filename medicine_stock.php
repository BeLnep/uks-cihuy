<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Anda harus login terlebih dahulu.";
    header("Location: login.php");
    exit();
}

// Check if user is admin or teacher
if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'teacher') {
    $_SESSION['error'] = "Anda tidak memiliki akses ke halaman ini.";
    header("Location: dashboard.php");
    exit();
}

// Check if medicines table exists, if not create it
$check_table = mysqli_query($conn, "SHOW TABLES LIKE 'medicines'");
if (mysqli_num_rows($check_table) == 0) {
    $create_table = "CREATE TABLE medicines (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        stock INT(11) NOT NULL DEFAULT 0,
        category VARCHAR(50) NOT NULL,
        expiry_date DATE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    if (!mysqli_query($conn, $create_table)) {
        $_SESSION['error'] = "Error creating medicines table: " . mysqli_error($conn);
    }
} else {
    // Check if category column exists
    $check_category = mysqli_query($conn, "SHOW COLUMNS FROM medicines LIKE 'category'");
    if (mysqli_num_rows($check_category) == 0) {
        // If category doesn't exist, add it
        $add_category = "ALTER TABLE medicines ADD COLUMN category VARCHAR(50) NOT NULL AFTER stock";
        if (!mysqli_query($conn, $add_category)) {
            $_SESSION['error'] = "Error adding category column: " . mysqli_error($conn);
        }
        
        // Check if unit column exists
        $check_unit = mysqli_query($conn, "SHOW COLUMNS FROM medicines LIKE 'unit'");
        if (mysqli_num_rows($check_unit) > 0) {
            // Copy data from unit to category
            $copy_data = "UPDATE medicines SET category = unit";
            if (!mysqli_query($conn, $copy_data)) {
                $_SESSION['error'] = "Error copying data: " . mysqli_error($conn);
            }
            
            // Drop the unit column
            $drop_unit = "ALTER TABLE medicines DROP COLUMN unit";
            if (!mysqli_query($conn, $drop_unit)) {
                $_SESSION['error'] = "Error dropping unit column: " . mysqli_error($conn);
            }
        }
    }
}

// Handle form submission for adding new medicine
if (isset($_POST['add_medicine'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $stock = (int)$_POST['stock'];
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $expiry_date = mysqli_real_escape_string($conn, $_POST['expiry_date']);
    
    // First check if the category column exists
    $check_category = mysqli_query($conn, "SHOW COLUMNS FROM medicines LIKE 'category'");
    if (mysqli_num_rows($check_category) == 0) {
        $_SESSION['error'] = "Error: Category column does not exist. Please contact administrator.";
    } else {
        $query = "INSERT INTO medicines (name, description, stock, category, expiry_date) VALUES ('$name', '$description', $stock, '$category', '$expiry_date')";
    
    if (mysqli_query($conn, $query)) {
        $_SESSION['success'] = "Obat berhasil ditambahkan.";
    } else {
        $_SESSION['error'] = "Gagal menambahkan obat: " . mysqli_error($conn);
        }
    }
    
    header("Location: medicine_stock.php");
    exit();
}

// Handle form submission for updating medicine
if (isset($_POST['update_medicine'])) {
    $id = (int)$_POST['medicine_id'];
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $stock = (int)$_POST['stock'];
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $expiry_date = mysqli_real_escape_string($conn, $_POST['expiry_date']);
    
    $query = "UPDATE medicines SET name='$name', description='$description', stock=$stock, category='$category', expiry_date='$expiry_date' WHERE id=$id";
    
    if (mysqli_query($conn, $query)) {
        $_SESSION['success'] = "Obat berhasil diperbarui.";
    } else {
        $_SESSION['error'] = "Gagal memperbarui obat: " . mysqli_error($conn);
    }
    
    header("Location: medicine_stock.php");
    exit();
}

// Handle form submission for deleting medicine
if (isset($_POST['delete_medicine'])) {
    $id = (int)$_POST['medicine_id'];
    
    $query = "DELETE FROM medicines WHERE id=$id";
    
    if (mysqli_query($conn, $query)) {
        $_SESSION['success'] = "Obat berhasil dihapus.";
    } else {
        $_SESSION['error'] = "Gagal menghapus obat: " . mysqli_error($conn);
    }
    
    header("Location: medicine_stock.php");
    exit();
}

// Get all medicines
$query = "SELECT * FROM medicines ORDER BY name ASC";
$result = mysqli_query($conn, $query);
$medicines = mysqli_fetch_all($result, MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stok Obat - UKS CIHUY</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/boxicons@2.0.7/css/boxicons.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
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

        .content-section {
            opacity: 0;
            animation: fadeIn 1s ease-in forwards;
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

        /* Staggered animation for cards */
        .card {
            opacity: 0;
            animation: fadeIn 0.5s ease-in forwards;
        }

        .card:nth-child(1) { animation-delay: 0.1s; }
        .card:nth-child(2) { animation-delay: 0.2s; }
        .card:nth-child(3) { animation-delay: 0.3s; }
        .card:nth-child(4) { animation-delay: 0.4s; }
        .card:nth-child(5) { animation-delay: 0.5s; }
        .card:nth-child(6) { animation-delay: 0.6s; }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--light-bg);
            color: var(--dark-text);
        }

        

        .page-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            box-shadow: var(--card-shadow);
            width: 100%;
            max-width: 100%;
        }

        .page-title {
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .page-subtitle {
            opacity: 0.9;
            font-weight: 400;
            font-size: 1.1rem;
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: var(--card-shadow);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            overflow: hidden;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: var(--hover-shadow);
        }

        .card-header {
            background: white;
            border-bottom: 1px solid rgba(0,0,0,0.1);
            padding: 1.5rem;
            color: var(--primary-color);
            font-weight: 600;
        }

        .table {
            margin-bottom: 0;
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

        .badge-low {
            background-color: var(--warning-color);
            color: white;
        }

        .badge-out {
            background-color: var(--primary-color);
            color: white;
        }

        .badge-ok {
            background-color: var(--success-color);
            color: white;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #6c757d;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            color: var(--primary-color);
            opacity: 0.5;
        }

        .empty-state h4 {
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .empty-state p {
            opacity: 0.7;
        }

        .btn {
            border-radius: 50px;
            padding: 0.5rem 1.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
            transform: translateY(-2px);
        }

        .btn-success {
            background-color: var(--success-color);
            border-color: var(--success-color);
        }

        .btn-success:hover {
            background-color: #218838;
            border-color: #1e7e34;
            transform: translateY(-2px);
        }

        .btn-warning {
            background-color: var(--warning-color);
            border-color: var(--warning-color);
            color: white;
        }

        .btn-warning:hover {
            background-color: #e0a800;
            border-color: #d39e00;
            color: white;
            transform: translateY(-2px);
        }

        .btn-danger {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-danger:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
            transform: translateY(-2px);
        }

        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: var(--card-shadow);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            margin-bottom: 1.5rem;
            border-left: 4px solid var(--primary-color);
        }

        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--hover-shadow);
        }

        .stats-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: var(--primary-color);
        }

        .stats-value {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: var(--primary-color);
        }

        .stats-label {
            font-size: 0.9rem;
            color: #6c757d;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .fade-in {
            animation: fadeIn 0.5s ease forwards;
        }

        .modal-content {
            border-radius: 15px;
            border: none;
            box-shadow: var(--card-shadow);
        }

        .modal-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: 15px 15px 0 0;
            border: none;
        }

        .modal-title {
            font-weight: 600;
        }

        .modal-footer {
            border-top: 1px solid rgba(0,0,0,0.1);
        }

        .form-control, .form-select {
            border-radius: 10px;
            padding: 0.75rem 1rem;
            border: 1px solid rgba(0,0,0,0.1);
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(220, 53, 69, 0.25);
        }

        .form-label {
            font-weight: 500;
            margin-bottom: 0.5rem;
        }
        
        .back-to-dashboard {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            z-index: 1000;
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="content">
        <div class="page-header">
            <div class="container">
                <div class="text-center">
                    <h1 class="page-title display-4 fw-bold">Stok Obat</h1>
                    <p class="page-subtitle lead">Kelola stok obat-obatan di UKS</p>
                </div>
            </div>
        </div>

        <div class="container">
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="row fade-in">
                <div class="col-md-4 mb-4">
                    <div class="card slide-up">
                        <div class="card-body">
                            <h5 class="card-title"><i class="fas fa-pills me-2"></i>Total Jenis Obat</h5>
                            <h2 class="mb-0"><?php echo count($medicines); ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card slide-up">
                        <div class="card-body">
                            <h5 class="card-title"><i class="fas fa-exclamation-triangle me-2"></i>Obat Hampir Habis</h5>
                            <h2 class="mb-0">
                                <?php 
                                $low_stock = 0;
                                foreach ($medicines as $medicine) {
                                    if ($medicine['stock'] <= 10) {
                                        $low_stock++;
                                    }
                                }
                                echo $low_stock;
                                ?>
                            </h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card slide-up">
                        <div class="card-body">
                            <h5 class="card-title"><i class="fas fa-calendar-times me-2"></i>Obat Kadaluarsa</h5>
                            <h2 class="mb-0">
                                <?php 
                                $expired = 0;
                                $today = date('Y-m-d');
                                foreach ($medicines as $medicine) {
                                    if (!empty($medicine['expiry_date']) && $medicine['expiry_date'] < $today) {
                                        $expired++;
                                    }
                                }
                                echo $expired;
                                ?>
                            </h2>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card scale-in fade-in">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Daftar Obat</h5>
                    <div>
                        <button class="btn btn-primary me-2 scale-in" data-bs-toggle="modal" data-bs-target="#addMedicineModal">
                            <i class="fas fa-plus me-1"></i> Tambah Obat
                        </button>
                        <button class="btn btn-primary scale-in" onclick="window.location.reload()">
                            <i class="fas fa-sync-alt me-1"></i> Refresh
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($medicines)): ?>
                        <div class="empty-state">
                            <i class='bx bx-capsule'></i>
                            <h4>Tidak Ada Data Obat</h4>
                            <p>Belum ada data obat yang ditambahkan. Silakan tambahkan obat baru.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Nama Obat</th>
                                        <th>Deskripsi</th>
                                        <th>Stok</th>
                                        <th>Kategori</th>
                                        <th>Kadaluarsa</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($medicines as $medicine): ?>
                                    <tr class="slide-up">
                                        <td><?php echo htmlspecialchars($medicine['name']); ?></td>
                                        <td><?php echo htmlspecialchars($medicine['description']); ?></td>
                                        <td>
                                            <?php 
                                            $stock_class = 'badge-ok';
                                            if ($medicine['stock'] <= 0) {
                                                $stock_class = 'badge-out';
                                                $stock_text = 'Habis';
                                            } elseif ($medicine['stock'] <= 10) {
                                                $stock_class = 'badge-low';
                                                $stock_text = 'Menipis';
                                            } else {
                                                $stock_text = 'Cukup';
                                            }
                                            ?>
                                            <span class="badge <?php echo $stock_class; ?>">
                                                <?php echo $medicine['stock']; ?> (<?php echo $stock_text; ?>)
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($medicine['category']); ?></td>
                                        <td>
                                            <?php 
                                            if (!empty($medicine['expiry_date'])) {
                                                $expiry_date = new DateTime($medicine['expiry_date']);
                                                $today = new DateTime();
                                                $interval = $today->diff($expiry_date);
                                                
                                                if ($expiry_date < $today) {
                                                    echo '<span class="badge badge-out">Kadaluarsa</span>';
                                                } else {
                                                    echo date('d M Y', strtotime($medicine['expiry_date']));
                                                    echo ' <small class="text-muted">(' . $interval->days . ' hari lagi)</small>';
                                                }
                                            } else {
                                                echo '<span class="text-muted">Tidak ada</span>';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-warning me-1" data-bs-toggle="modal" data-bs-target="#editMedicineModal<?php echo $medicine['id']; ?>">
                                                <i class='bx bx-edit'></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteMedicineModal<?php echo $medicine['id']; ?>">
                                                <i class='bx bx-trash'></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Medicine Modal -->
    <div class="modal fade" id="addMedicineModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content scale-in">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Obat</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nama Obat</label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Deskripsi</label>
                            <textarea class="form-control" name="description" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Stok</label>
                            <input type="number" class="form-control" name="stock" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Kategori Obat</label>
                            <select class="form-select" name="category" required>
                                <option value="">Pilih Kategori</option>
                                <option value="Antibiotik">Antibiotik</option>
                                <option value="Analgesik">Analgesik</option>
                                <option value="Antiseptik">Antiseptik</option>
                                <option value="Vitamin">Vitamin</option>
                                <option value="Obat Luar">Obat Luar</option>
                                <option value="Lainnya">Lainnya</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tanggal Kadaluarsa</label>
                            <input type="date" class="form-control" name="expiry_date" required>
                        </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="add_medicine" class="btn btn-primary">Simpan</button>
                </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Medicine Modals -->
    <?php foreach ($medicines as $medicine): ?>
    <div class="modal fade" id="editMedicineModal<?php echo $medicine['id']; ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Obat</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="medicine_id" value="<?php echo $medicine['id']; ?>">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="name<?php echo $medicine['id']; ?>" class="form-label">Nama Obat</label>
                            <input type="text" class="form-control" id="name<?php echo $medicine['id']; ?>" name="name" value="<?php echo htmlspecialchars($medicine['name']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="description<?php echo $medicine['id']; ?>" class="form-label">Deskripsi</label>
                            <textarea class="form-control" id="description<?php echo $medicine['id']; ?>" name="description" rows="3"><?php echo htmlspecialchars($medicine['description']); ?></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="stock<?php echo $medicine['id']; ?>" class="form-label">Stok</label>
                                <input type="number" class="form-control" id="stock<?php echo $medicine['id']; ?>" name="stock" min="0" value="<?php echo $medicine['stock']; ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="category<?php echo $medicine['id']; ?>" class="form-label">Kategori Obat</label>
                                <select class="form-select" id="category<?php echo $medicine['id']; ?>" name="category" required>
                                    <option value="Antibiotik" <?php echo $medicine['category'] == 'Antibiotik' ? 'selected' : ''; ?>>Antibiotik</option>
                                    <option value="Analgesik" <?php echo $medicine['category'] == 'Analgesik' ? 'selected' : ''; ?>>Analgesik</option>
                                    <option value="Antiseptik" <?php echo $medicine['category'] == 'Antiseptik' ? 'selected' : ''; ?>>Antiseptik</option>
                                    <option value="Vitamin" <?php echo $medicine['category'] == 'Vitamin' ? 'selected' : ''; ?>>Vitamin</option>
                                    <option value="Obat Luar" <?php echo $medicine['category'] == 'Obat Luar' ? 'selected' : ''; ?>>Obat Luar</option>
                                    <option value="Lainnya" <?php echo $medicine['category'] == 'Lainnya' ? 'selected' : ''; ?>>Lainnya</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="expiry_date<?php echo $medicine['id']; ?>" class="form-label">Tanggal Kadaluarsa</label>
                            <input type="date" class="form-control" id="expiry_date<?php echo $medicine['id']; ?>" name="expiry_date" value="<?php echo $medicine['expiry_date']; ?>">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="update_medicine" class="btn btn-primary">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Medicine Modal -->
    <div class="modal fade" id="deleteMedicineModal<?php echo $medicine['id']; ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Hapus Obat</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Apakah Anda yakin ingin menghapus obat <strong><?php echo htmlspecialchars($medicine['name']); ?></strong>?</p>
                    <p class="text-danger">Tindakan ini tidak dapat dibatalkan.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="medicine_id" value="<?php echo $medicine['id']; ?>">
                        <button type="submit" name="delete_medicine" class="btn btn-danger">Hapus</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <a href="dashboard.php" class="btn btn-primary back-to-dashboard">
        <i class='bx bx-arrow-back me-1'></i> Kembali ke Dashboard
    </a>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/script.js"></script>
</body>
</html> 