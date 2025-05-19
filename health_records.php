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

// Create health_records table if it doesn't exist
$create_table = "CREATE TABLE IF NOT EXISTS health_records (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    check_date DATE NOT NULL,
    height FLOAT,
    weight FLOAT,
    blood_pressure VARCHAR(20),
    complaints TEXT,
    notes TEXT,
    medicine_id INT NULL,
    medicine_qty INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB";

if (!mysqli_query($conn, $create_table)) {
    $_SESSION['error'] = "Error creating health_records table: " . mysqli_error($conn);
    header("Location: index.php");
    exit();
}

// Add check_date column if it doesn't exist
$check_column = mysqli_query($conn, "SHOW COLUMNS FROM health_records LIKE 'check_date'");
if (mysqli_num_rows($check_column) == 0) {
    // First add the column as nullable
    $add_column = "ALTER TABLE health_records ADD COLUMN check_date DATE NULL AFTER student_id";
    if (!mysqli_query($conn, $add_column)) {
        $_SESSION['error'] = "Error adding check_date column: " . mysqli_error($conn);
        header("Location: index.php");
        exit();
    }
    
    // Update any NULL values to current date
    $update_dates = "UPDATE health_records SET check_date = CURRENT_DATE WHERE check_date IS NULL";
    if (!mysqli_query($conn, $update_dates)) {
        $_SESSION['error'] = "Error updating check_date values: " . mysqli_error($conn);
        header("Location: index.php");
        exit();
    }
    
    // Now modify the column to be NOT NULL
    $modify_column = "ALTER TABLE health_records MODIFY COLUMN check_date DATE NOT NULL";
    if (!mysqli_query($conn, $modify_column)) {
        $_SESSION['error'] = "Error modifying check_date column: " . mysqli_error($conn);
        header("Location: index.php");
        exit();
    }
}

// Add height column if it doesn't exist
$check_column = mysqli_query($conn, "SHOW COLUMNS FROM health_records LIKE 'height'");
if (mysqli_num_rows($check_column) == 0) {
    $add_column = "ALTER TABLE health_records ADD COLUMN height FLOAT AFTER check_date";
    if (!mysqli_query($conn, $add_column)) {
        $_SESSION['error'] = "Error adding height column: " . mysqli_error($conn);
        header("Location: index.php");
        exit();
    }
}

// Add weight column if it doesn't exist
$check_column = mysqli_query($conn, "SHOW COLUMNS FROM health_records LIKE 'weight'");
if (mysqli_num_rows($check_column) == 0) {
    $add_column = "ALTER TABLE health_records ADD COLUMN weight FLOAT AFTER height";
    if (!mysqli_query($conn, $add_column)) {
        $_SESSION['error'] = "Error adding weight column: " . mysqli_error($conn);
        header("Location: index.php");
        exit();
    }
}

// Add blood_pressure column if it doesn't exist
$check_column = mysqli_query($conn, "SHOW COLUMNS FROM health_records LIKE 'blood_pressure'");
if (mysqli_num_rows($check_column) == 0) {
    $add_column = "ALTER TABLE health_records ADD COLUMN blood_pressure VARCHAR(20) AFTER weight";
    if (!mysqli_query($conn, $add_column)) {
        $_SESSION['error'] = "Error adding blood_pressure column: " . mysqli_error($conn);
        header("Location: index.php");
        exit();
    }
}

// Add complaints column if it doesn't exist
$check_column = mysqli_query($conn, "SHOW COLUMNS FROM health_records LIKE 'complaints'");
if (mysqli_num_rows($check_column) == 0) {
    $add_column = "ALTER TABLE health_records ADD COLUMN complaints TEXT AFTER blood_pressure";
    if (!mysqli_query($conn, $add_column)) {
        $_SESSION['error'] = "Error adding complaints column: " . mysqli_error($conn);
        header("Location: index.php");
        exit();
    }
}

// Add notes column if it doesn't exist
$check_column = mysqli_query($conn, "SHOW COLUMNS FROM health_records LIKE 'notes'");
if (mysqli_num_rows($check_column) == 0) {
    $add_column = "ALTER TABLE health_records ADD COLUMN notes TEXT AFTER complaints";
    if (!mysqli_query($conn, $add_column)) {
        $_SESSION['error'] = "Error adding notes column: " . mysqli_error($conn);
        header("Location: index.php");
        exit();
    }
}

// Add medicine_id column if it doesn't exist
$check_column = mysqli_query($conn, "SHOW COLUMNS FROM health_records LIKE 'medicine_id'");
if (mysqli_num_rows($check_column) == 0) {
    $add_column = "ALTER TABLE health_records ADD COLUMN medicine_id INT NULL AFTER notes";
    if (!mysqli_query($conn, $add_column)) {
        $_SESSION['error'] = "Error adding medicine_id column: " . mysqli_error($conn);
        header("Location: index.php");
        exit();
    }
}

// Add medicine_qty column if it doesn't exist
$check_column = mysqli_query($conn, "SHOW COLUMNS FROM health_records LIKE 'medicine_qty'");
if (mysqli_num_rows($check_column) == 0) {
    $add_column = "ALTER TABLE health_records ADD COLUMN medicine_qty INT DEFAULT 1 AFTER medicine_id";
    if (!mysqli_query($conn, $add_column)) {
        $_SESSION['error'] = "Error adding medicine_qty column: " . mysqli_error($conn);
        header("Location: index.php");
        exit();
    }
}

// Get all health records with student names
$query = "SELECT hr.*, s.name as student_name, s.class 
    FROM health_records hr 
          INNER JOIN students s ON hr.student_id = s.id 
          ORDER BY hr.check_date DESC";
$result = mysqli_query($conn, $query);

if (!$result) {
    $_SESSION['error'] = "Error fetching health records: " . mysqli_error($conn);
} else {
    $health_records = mysqli_fetch_all($result, MYSQLI_ASSOC);
}

// Include header
require_once 'header.php';
?>

<div class="container mt-4 page-transition">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-notes-medical me-2"></i>Data Pemeriksaan Kesehatan</h5>
                    <?php if (isset($_SESSION['role']) && ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'teacher')): ?>
                    <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#addHealthRecordModal">
                        <i class="fas fa-plus me-2"></i>Tambah Data
                    </button>
                    <?php endif; ?>
                </div>
                <div class="card-body">
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

                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Tanggal</th>
                                    <th>Nama Siswa</th>
                                    <th>Kelas</th>
                                    <th>Tekanan Darah</th>
                                    <th>Keluhan</th>
                                    <th>Catatan</th>
                                    <?php if (isset($_SESSION['role']) && ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'teacher')): ?>
                                    <th>Aksi</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $no = 1;
                                if (!empty($health_records)):
                                foreach ($health_records as $record): 
                                ?>
                                <tr>
                                    <td><?= $no++; ?></td>
                                    <td><?= date('d/m/Y', strtotime($record['check_date'])); ?></td>
                                    <td><?= htmlspecialchars($record['student_name']); ?></td>
                                    <td><?= htmlspecialchars($record['class']); ?></td>
                                    <td><?= $record['blood_pressure'] ?: '-'; ?></td>
                                    <td><?= htmlspecialchars($record['complaints'] ?: '-'); ?></td>
                                    <td><?= htmlspecialchars($record['notes'] ?: '-'); ?></td>
                                    <?php if (isset($_SESSION['role']) && ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'teacher')): ?>
                                    <td>
                                        <button class="btn btn-sm btn-warning edit-btn" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#editHealthRecordModal"
                                                    data-id="<?= $record['id']; ?>"
                                                    data-student-id="<?= $record['student_id']; ?>"
                                                    data-check-date="<?= $record['check_date']; ?>"
                                                data-blood-pressure="<?= htmlspecialchars($record['blood_pressure'] ?: ''); ?>"
                                                data-complaints="<?= htmlspecialchars($record['complaints'] ?: ''); ?>"
                                                data-notes="<?= htmlspecialchars($record['notes'] ?: ''); ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        <button class="btn btn-sm btn-danger delete-btn"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#deleteHealthRecordModal"
                                                    data-id="<?= $record['id']; ?>"
                                                data-name="<?= htmlspecialchars($record['student_name']); ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                    </td>
                                    <?php endif; ?>
                                </tr>
                                <?php 
                                endforeach;
                                else:
                                ?>
                                <tr>
                                    <td colspan="<?= isset($_SESSION['role']) && ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'teacher') ? '8' : '7' ?>" class="text-center">
                                        <div class="empty-state">
                                            <i class="fas fa-clipboard-list"></i>
                                            <p>Tidak ada data pemeriksaan kesehatan</p>
                                        </div>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Daftar Obat Table -->
        <div class="row mt-4 medicine-table">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Daftar Obat</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Nama Obat</th>
                                        <th>Deskripsi</th>
                                        <th>Stok</th>
                                        <th>Kategori</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $query = "SELECT * FROM medicines ORDER BY name ASC";
                                    $result = mysqli_query($conn, $query);
                                    
                                    if(mysqli_num_rows($result) > 0) {
                                        while($row = mysqli_fetch_assoc($result)) {
                                            $stock_class = '';
                                            if($row['stock'] == 0) {
                                                $stock_class = 'badge-out';
                                                $stock_text = 'Habis';
                                            } elseif($row['stock'] <= 10) {
                                                $stock_class = 'badge-low';
                                                $stock_text = 'Menipis';
                                            } else {
                                                $stock_class = 'badge-ok';
                                                $stock_text = 'Cukup';
                                            }
                                            echo "<tr>";
                                            echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                                            echo "<td>" . htmlspecialchars($row['description']) . "</td>";
                                            echo "<td><span class='badge " . $stock_class . "'>" . $stock_text . " (" . $row['stock'] . ")</span></td>";
                                            echo "<td>" . htmlspecialchars($row['category']) . "</td>";
                                            echo "</tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='4' class='text-center'>Tidak ada data obat</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (isset($_SESSION['role']) && ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'teacher')): ?>
<!-- Add Health Record Modal -->
<div class="modal fade" id="addHealthRecordModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Data Pemeriksaan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="add_health_record.php" method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="student_id" class="form-label">Siswa</label>
                        <select class="form-select" id="student_id" name="student_id" required>
                            <option value="">Pilih Siswa</option>
                            <?php
                            $students = mysqli_query($conn, "SELECT id, nis, name, class FROM students ORDER BY name");
                            while ($student = mysqli_fetch_assoc($students)):
                            ?>
                            <option value="<?= $student['id']; ?>">
                                <?= htmlspecialchars($student['nis'] . ' - ' . $student['name'] . ' (' . $student['class'] . ')'); ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="check_date" class="form-label">Tanggal Pemeriksaan</label>
                        <input type="date" class="form-control" id="check_date" name="check_date" required>
                    </div>
                    <div class="mb-3">
                        <label for="blood_pressure" class="form-label">Tekanan Darah</label>
                        <input type="text" class="form-control" id="blood_pressure" name="blood_pressure" placeholder="Contoh: 120/80">
                    </div>
                    <div class="mb-3">
                        <label for="complaints" class="form-label">Keluhan</label>
                        <textarea class="form-control" id="complaints" name="complaints" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="notes" class="form-label">Catatan</label>
                        <textarea class="form-control" id="notes" name="notes" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="medicine_id" class="form-label">Obat</label>
                        <select class="form-select" id="medicine_id" name="medicine_id">
                            <option value="">Pilih Obat (opsional)</option>
                            <?php
                            $medicines = mysqli_query($conn, "SELECT id, name, stock FROM medicines ORDER BY name");
                            while ($medicine = mysqli_fetch_assoc($medicines)):
                            ?>
                            <option value="<?= $medicine['id']; ?>">
                                <?= htmlspecialchars($medicine['name']) . ' (Stok: ' . $medicine['stock'] . ')'; ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="medicine_qty" class="form-label">Jumlah Obat</label>
                        <input type="number" class="form-control" id="medicine_qty" name="medicine_qty" min="1" value="1">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Health Record Modal -->
<div class="modal fade" id="editHealthRecordModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Data Pemeriksaan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="edit_health_record.php" method="POST">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_student_id" class="form-label">Siswa</label>
                        <select class="form-select" id="edit_student_id" name="student_id" required>
                            <option value="">Pilih Siswa</option>
                            <?php
                            mysqli_data_seek($students, 0);
                            while ($student = mysqli_fetch_assoc($students)):
                            ?>
                            <option value="<?= $student['id']; ?>">
                                <?= htmlspecialchars($student['nis'] . ' - ' . $student['name'] . ' (' . $student['class'] . ')'); ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit_check_date" class="form-label">Tanggal Pemeriksaan</label>
                        <input type="date" class="form-control" id="edit_check_date" name="check_date" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_blood_pressure" class="form-label">Tekanan Darah</label>
                        <input type="text" class="form-control" id="edit_blood_pressure" name="blood_pressure" placeholder="Contoh: 120/80">
                    </div>
                    <div class="mb-3">
                        <label for="edit_complaints" class="form-label">Keluhan</label>
                        <textarea class="form-control" id="edit_complaints" name="complaints" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="edit_notes" class="form-label">Catatan</label>
                        <textarea class="form-control" id="edit_notes" name="notes" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="edit_medicine_id" class="form-label">Obat</label>
                        <select class="form-select" id="edit_medicine_id" name="medicine_id">
                            <option value="">Pilih Obat (opsional)</option>
                            <?php
                            $medicines = mysqli_query($conn, "SELECT id, name, stock FROM medicines ORDER BY name");
                            while ($medicine = mysqli_fetch_assoc($medicines)):
                            ?>
                            <option value="<?= $medicine['id']; ?>">
                                <?= htmlspecialchars($medicine['name']) . ' (Stok: ' . $medicine['stock'] . ')'; ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit_medicine_qty" class="form-label">Jumlah Obat</label>
                        <input type="number" class="form-control" id="edit_medicine_qty" name="medicine_qty" min="1" value="1">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Health Record Modal -->
<div class="modal fade" id="deleteHealthRecordModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Hapus Data Pemeriksaan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Apakah Anda yakin ingin menghapus data pemeriksaan untuk siswa <span id="delete_student_name"></span>?</p>
            </div>
            <div class="modal-footer">
                <form action="delete_health_record.php" method="POST">
                    <input type="hidden" name="id" id="delete_id">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">Hapus</button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
    :root {
        --primary-color: #dc3545;
        --secondary-color: #c82333;
        --accent-color: #e4606d;
        --success-color: #28a745;
        --warning-color: #ffc107;
        --danger-color: #e74c3c;
        --light-bg: #f8f9fa;
        --dark-text: #212529;
        --card-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        --hover-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
        --gradient-primary: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        --gradient-accent: linear-gradient(135deg, var(--accent-color), var(--secondary-color));
    }

    body {
        background-color: #f0f2f5;
        color: var(--dark-text);
        font-family: 'Poppins', sans-serif;
        min-height: 100vh;
    }

    .content-section {
        opacity: 0;
        animation: fadeIn 1s ease-in forwards;
        padding: 20px;
    }

    .container {
        padding: 20px;
    }

    /* Card styling with floating effect */
    .card {
        background: rgba(255, 255, 255, 0.95);
        border: none;
        border-radius: 20px;
        box-shadow: var(--card-shadow);
        margin-bottom: 1.5rem;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        backdrop-filter: blur(10px);
        transform: translateY(0);
        position: relative;
        overflow: hidden;
    }

    .card:hover {
        transform: translateY(-10px);
        box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
    }

    .card::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        transition: 0.5s;
    }

    .card:hover::before {
        left: 100%;
    }

    /* Card content styling */
    .card-body {
        padding: 1.5rem;
        position: relative;
        z-index: 1;
    }

    .card-title {
        color: var(--primary-color);
        font-weight: 600;
        margin-bottom: 1rem;
        position: relative;
    }

    .card-title::after {
        content: '';
        position: absolute;
        bottom: -5px;
        left: 0;
        width: 50px;
        height: 2px;
        background: var(--gradient-primary);
        border-radius: 2px;
    }

    /* Table styling with hover effects */
    .table-responsive {
        border-radius: 15px;
        overflow: hidden;
        background: white;
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.06);
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
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        background-color: rgba(220, 53, 69, 0.02);
    }

    /* Button styling */
    .btn {
        border-radius: 50px;
        padding: 0.75rem 1.5rem;
        font-weight: 500;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
    }

    .btn::before {
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

    .btn:hover::before {
        transform: translateX(100%);
    }

    .btn-primary {
        background: var(--gradient-primary);
        border: none;
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(220, 53, 69, 0.2);
    }

    /* Alert styling with animation */
    .alert {
        border-radius: 15px;
        border: none;
        padding: 1rem 1.5rem;
        box-shadow: var(--card-shadow);
        animation: alertSlide 0.5s ease-out;
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.95), rgba(255, 255, 255, 0.9));
        backdrop-filter: blur(10px);
    }

    @keyframes alertSlide {
        from {
            transform: translateY(-100%);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }

    /* Modal styling */
    .modal-content {
        border: none;
        border-radius: 20px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
    }

    .modal-header {
        background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        color: white !important;
        border-radius: 20px 20px 0 0 !important;
        border-bottom: none !important;
        font-weight: 600;
        font-size: 1.2rem;
        box-shadow: none !important;
        padding: 1.5rem 1.5rem 1rem 1.5rem;
    }

    .modal-title {
        color: white !important;
        font-weight: 700;
        letter-spacing: 0.5px;
        text-shadow: 0 1px 4px rgba(0,0,0,0.12);
        background: none !important;
    }

    .modal-body {
        padding: 1.5rem;
    }

    .modal-footer {
        border-top: none;
        padding: 1.5rem;
    }

    /* Form controls */
    .form-control, .form-select {
        border-radius: 12px;
        padding: 0.85rem 1.5rem;
        border: 2px solid rgba(238, 242, 247, 0.8);
        font-size: 0.95rem;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        background: rgba(255, 255, 255, 0.9);
    }

    .form-control:focus, .form-select:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 4px rgba(220, 53, 69, 0.15);
        transform: translateY(-2px);
        background: rgba(255, 255, 255, 1);
    }

    /* Action buttons */
    .action-buttons {
        display: flex;
        gap: 0.5rem;
    }

    .action-buttons .btn {
        padding: 0.5rem;
        width: 35px;
        height: 35px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
    }

    .action-buttons .btn i {
        font-size: 0.9rem;
    }

    /* Empty state */
    .empty-state {
        text-align: center;
        padding: 3rem 1rem;
        color: #6c757d;
    }

    .empty-state i {
        font-size: 3rem;
        margin-bottom: 1rem;
        color: var(--primary-color);
        opacity: 0.5;
    }

    /* Animations */
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

    .fade-in {
        animation: fadeIn 0.5s ease-out forwards;
    }

    /* Page transition */
    .page-transition {
        position: relative;
    }

    .page-transition::before {
        content: '';
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        backdrop-filter: blur(10px);
        z-index: -1;
    }

    .table-notification.error i {
        color: #dc3545;
    }

    /* Medicine Table Styles */
    .medicine-table {
        margin-top: 2rem;
    }

    .medicine-table .card {
        border: none;
        border-radius: 15px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        animation: fadeIn 0.5s ease-out;
    }

    .medicine-table .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
    }

    .medicine-table .card-header {
        color: var(--primary-color);
        border-radius: 15px 15px 0 0 !important;
        padding: 1rem 1.5rem;
    }

    .medicine-table .table {
        margin-bottom: 0;
    }

    .medicine-table .table th {
        border-top: none;
        background-color: #f8f9fa;
        color: #495057;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.85rem;
        padding: 1rem;
    }

    .medicine-table .table td {
        padding: 1rem;
        vertical-align: middle;
        color: #495057;
        border-color: #e9ecef;
    }

    .medicine-table .table tbody tr {
        transition: background-color 0.3s ease;
    }

    .medicine-table .table tbody tr:hover {
        background-color: #f8f9fa;
    }

    .badge {
        padding: 0.5rem 1rem;
        border-radius: 50px;
        font-weight: 500;
        font-size: 0.85rem;
    }

    .badge-ok {
        background-color: #28a745;
        color: white;
    }

    .badge-low {
        background-color: #ffc107;
        color: #000;
    }

    .badge-out {
        background-color: #dc3545;
        color: white;
    }

    /* Animation for table rows */
    .medicine-table tbody tr {
        opacity: 0;
        animation: slideIn 0.5s ease forwards;
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

    .medicine-table tbody tr:nth-child(1) { animation-delay: 0.1s; }
    .medicine-table tbody tr:nth-child(2) { animation-delay: 0.2s; }
    .medicine-table tbody tr:nth-child(3) { animation-delay: 0.3s; }
    .medicine-table tbody tr:nth-child(4) { animation-delay: 0.4s; }
    .medicine-table tbody tr:nth-child(5) { animation-delay: 0.5s; }
</style>

<script>
    // Edit button click handler with animation
    document.querySelectorAll('.edit-btn').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.dataset.id;
            const studentId = this.dataset.studentId;
            const checkDate = this.dataset.checkDate;
            const bloodPressure = this.dataset.bloodPressure;
            const complaints = this.dataset.complaints;
            const notes = this.dataset.notes;

            // Add animation to modal
            const modal = document.getElementById('editHealthRecordModal');
            modal.classList.add('fade-in');

            document.getElementById('edit_id').value = id;
            document.getElementById('edit_student_id').value = studentId;
            document.getElementById('edit_check_date').value = checkDate;
            document.getElementById('edit_blood_pressure').value = bloodPressure;
            document.getElementById('edit_complaints').value = complaints;
            document.getElementById('edit_notes').value = notes;
        });
    });

    // Delete button click handler with animation
    document.querySelectorAll('.delete-btn').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.dataset.id;
            const studentName = this.dataset.name;

            // Add animation to modal
            const modal = document.getElementById('deleteHealthRecordModal');
            modal.classList.add('fade-in');

            document.getElementById('delete_id').value = id;
            document.getElementById('delete_student_name').textContent = studentName;
        });
    });

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
<?php endif; ?>

<?php require_once 'footer.php'; ?> 