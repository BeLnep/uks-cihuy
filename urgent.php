<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Create emergency_cases table if not exists
$create_table = "CREATE TABLE IF NOT EXISTS emergency_cases (
    id INT PRIMARY KEY AUTO_INCREMENT,
    reporter_id INT,
    victim_name VARCHAR(100),
    victim_class VARCHAR(20),
    location VARCHAR(255),
    description TEXT,
    emergency_type VARCHAR(50),
    status ENUM('pending', 'in_progress', 'resolved') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (reporter_id) REFERENCES users(id)
)";

if (!mysqli_query($conn, $create_table)) {
    $_SESSION['error'] = "Error creating emergency_cases table: " . mysqli_error($conn);
    header("Location: urgent.php");
    exit();
}

// Handle emergency submission
if (isset($_POST['submit_emergency'])) {
    $reporter_id = $_SESSION['user_id'];
    $location = mysqli_real_escape_string($conn, $_POST['location']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $victim_name = mysqli_real_escape_string($conn, $_POST['victim_name']);
    $victim_class = mysqli_real_escape_string($conn, $_POST['victim_class']);
    $emergency_type = mysqli_real_escape_string($conn, $_POST['emergency_type']);
    
    // Insert emergency case
    $query = "INSERT INTO emergency_cases (reporter_id, victim_name, victim_class, location, description, emergency_type) 
              VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "isssss", $reporter_id, $victim_name, $victim_class, $location, $description, $emergency_type);
    
    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['success'] = "Laporan darurat berhasil dikirim! Petugas PMR akan segera menuju lokasi.";
    } else {
        $_SESSION['error'] = "Gagal mengirim laporan: " . mysqli_error($conn);
    }
    
    header("Location: urgent.php");
    exit();
}

// Handle status update
if (isset($_POST['update_status']) && $_SESSION['role'] == 'admin') {
    $case_id = mysqli_real_escape_string($conn, $_POST['case_id']);
    $new_status = mysqli_real_escape_string($conn, $_POST['new_status']);
    
    $update_query = "UPDATE emergency_cases SET status = ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $update_query);
    mysqli_stmt_bind_param($stmt, "si", $new_status, $case_id);
    
    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['success'] = "Status berhasil diperbarui!";
    } else {
        $_SESSION['error'] = "Gagal memperbarui status: " . mysqli_error($conn);
    }
    
    header("Location: urgent.php");
    exit();
}

// Check if table exists before querying
$check_table = mysqli_query($conn, "SHOW TABLES LIKE 'emergency_cases'");
if (mysqli_num_rows($check_table) > 0) {
    // Get active emergency cases
    $query = "SELECT ec.*, u.username as reporter_name 
              FROM emergency_cases ec 
              JOIN users u ON ec.reporter_id = u.id 
              WHERE ec.status != 'resolved' 
              ORDER BY ec.created_at DESC";
    $result = mysqli_query($conn, $query);
} else {
    $result = false;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Urgent - PMR Emergency Response</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --emergency-red: #dc3545;
            --warning-yellow: #ffc107;
            --success-green: #28a745;
        }
        
        .urgent-container {
            background: transparent;
            border-radius: 0;
            box-shadow: none;
            padding: 2.5rem 0 2rem 0;
            margin-bottom: 2.5rem;
            animation: fadeIn 0.7s cubic-bezier(0.4,0,0.2,1);
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .emergency-card {
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 1rem;
            border: none;
            transition: transform 0.2s;
        }
        
        .emergency-card:hover {
            transform: translateY(-3px);
        }
        
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-weight: 500;
            font-size: 0.9rem;
        }
        
        .status-pending {
            background-color: var(--emergency-red);
            color: white;
        }
        
        .status-in_progress {
            background-color: var(--warning-yellow);
            color: black;
        }
        
        .status-resolved {
            background-color: var(--success-green);
            color: white;
        }
        
        .emergency-form {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .emergency-title {
            color: var(--emergency-red);
            font-weight: 600;
            margin-bottom: 1.5rem;
        }
        
        .submit-emergency {
            background: var(--emergency-red);
            border: none;
            padding: 0.8rem 2rem;
            border-radius: 50px;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .submit-emergency:hover {
            background: #c82333;
            transform: translateY(-2px);
        }
    
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        .emergency-alert {
            animation: pulse 2s infinite;
        }
        
        /* Tambahkan style untuk form update status */
        .form-select-sm {
            border-radius: 6px;
            border: 1px solid #dee2e6;
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
            transition: all 0.2s;
        }
        
        .form-select-sm:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
        }
        
        .btn-sm {
            padding: 0.25rem 0.8rem;
            font-size: 0.875rem;
            border-radius: 6px;
            transition: all 0.2s;
        }
        
        .btn-sm:hover {
            transform: translateY(-1px);
        }
        
        .btn-primary {
            background: var(--emergency-red);
            border: none;
        }
        
        .btn-primary:hover {
            background: var(--emergency-red);
        }
        
        .gap-2 {
            gap: 0.5rem;
        }

        .back-to-dashboard {
            background-color: var(--emergency-red);
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            z-index: 1000;
            transition: all 0.3s ease;
            border: none;
            padding: 12px 25px;
            border-radius: 50px;
            box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
            font-weight: 600;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
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
    </style>
</head>
<body class="bg-light">
    <!-- <?php require_once 'header.php'; ?> -->
    
    <!-- HEADER SAMA SEPERTI LOBBY -->
    <div class="page-header text-center w-100" style="background: linear-gradient(135deg, #dc3545, #c82333); color: white; padding: 2.5rem 0 1.5rem 0; margin-bottom: 2rem; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
        <div class="container-a">
            <h1 class="display-4 fw-bold" style="font-weight:700;">Urgent PMR</h1>
            <p class="lead" style="opacity:0.95; font-weight:400;">Halaman gerak cepat PMR untuk penanganan keadaan darurat oleh para murid</p>
        </div>
    </div>
    
    <div class="urgent-container mx-auto" style="max-width:1200px;">
        <div class="row">
            <!-- Emergency Form -->
            <div class="col-md-6 mb-4">
                <div class="emergency-form">
                    <h3 class="emergency-title">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Laporkan Keadaan Darurat
                    </h3>
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label class="form-label">Nama Korban</label>
                            <input type="text" name="victim_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Kelas Korban</label>
                            <input type="text" name="victim_class" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Lokasi Kejadian</label>
                            <input type="text" name="location" class="form-control location-input" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Jenis Keadaan Darurat</label>
                            <select name="emergency_type" class="form-select" required>
                                <option value="">Pilih jenis keadaan darurat</option>
                                <option value="Cedera">Cedera</option>
                                <option value="Pingsan">Pingsan</option>
                                <option value="Sakit">Sakit</option>
                                <option value="Kecelakaan">Kecelakaan</option>
                                <option value="Lainnya">Lainnya</option>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="form-label">Deskripsi Keadaan</label>
                            <textarea name="description" class="form-control" rows="4" required></textarea>
                        </div>
                        <button type="submit" name="submit_emergency" class="btn btn-danger submit-emergency">
                            <i class="fas fa-paper-plane me-2"></i>
                            Kirim Laporan Darurat
                        </button>
                    </form>
                </div>
            </div>

            <!-- Active Emergency Cases -->
            <div class="col-md-6">
                <h4 class="mb-4">Keadaan Darurat Aktif</h4>
                <?php if (mysqli_num_rows($result) > 0): ?>
                    <?php while ($case = mysqli_fetch_assoc($result)): ?>
                        <div class="card emergency-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <h5 class="card-title mb-0"><?php echo htmlspecialchars($case['emergency_type']); ?></h5>
                                    <span class="status-badge status-<?php echo $case['status']; ?>">
                                        <?php 
                                        $status_text = [
                                            'pending' => 'Menunggu Respon',
                                            'in_progress' => 'Sedang Ditangani',
                                            'resolved' => 'Selesai'
                                        ];
                                        echo $status_text[$case['status']];
                                        ?>
                                    </span>
                                </div>
                                <p class="card-text">
                                    <strong>Korban:</strong> <?php echo htmlspecialchars($case['victim_name']); ?> (<?php echo htmlspecialchars($case['victim_class']); ?>)<br>
                                    <strong>Lokasi:</strong> <?php echo htmlspecialchars($case['location']); ?><br>
                                    <strong>Deskripsi:</strong> <?php echo htmlspecialchars($case['description']); ?>
                                </p>
                                <div class="text-muted small">
                                    <i class="fas fa-user me-1"></i> Dilaporkan oleh: <?php echo htmlspecialchars($case['reporter_name']); ?><br>
                                    <i class="fas fa-clock me-1"></i> <?php echo date('d/m/Y H:i', strtotime($case['created_at'])); ?>
                                </div>
                                
                                <?php if ($_SESSION['role'] == 'admin' && $case['status'] != 'resolved'): ?>
                                    <div class="mt-3 pt-3 border-top">
                                        <form method="POST" class="d-flex gap-2">
                                            <input type="hidden" name="case_id" value="<?php echo $case['id']; ?>">
                                            <select name="new_status" class="form-select form-select-sm" style="max-width: 200px;">
                                                <option value="in_progress" <?php echo $case['status'] == 'in_progress' ? 'selected' : ''; ?>>Menuju Lokasi</option>
                                                <option value="resolved" <?php echo $case['status'] == 'resolved' ? 'selected' : ''; ?>>Sudah Ditangani</option>
                                            </select>
                                            <button type="submit" name="update_status" class="btn btn-sm btn-primary">
                                                <i class="fas fa-check me-1"></i> Update
                                            </button>
                                        </form>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Tidak ada keadaan darurat aktif saat ini.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Tombol Kembali ke Dashboard -->
    <a href="dashboard.php" class="btn btn-danger back-to-dashboard">
        <i class="fas fa-arrow-left me-2"></i> Kembali
    </a>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 