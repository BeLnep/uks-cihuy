<?php
require_once 'header.php';

// Check if user is teacher
if ($_SESSION['role'] != 'guru') {
    header("Location: dashboard.php");
    exit();
}

// Get current date's health records
$today = date('Y-m-d');
$stmt = $pdo->prepare("
    SELECT hr.*, s.name as student_name, s.class, s.student_id as student_number
    FROM health_records hr 
    JOIN students s ON hr.student_id = s.id 
    WHERE DATE(hr.created_at) = ?
    ORDER BY hr.created_at DESC
");
$stmt->execute([$today]);
$today_records = $stmt->fetchAll();

// Get recent health records (last 7 days)
$stmt = $pdo->prepare("
    SELECT hr.*, s.name as student_name, s.class, s.student_id as student_number
    FROM health_records hr 
    JOIN students s ON hr.student_id = s.id 
    WHERE DATE(hr.created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    ORDER BY hr.created_at DESC
");
$stmt->execute();
$recent_records = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Guru Piket UKS</h2>
    <a href="add_record.php" class="btn btn-primary">Tambah Data Perawatan</a>
</div>

<!-- Today's Records -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="card-title mb-0">Data Perawatan Hari Ini</h5>
    </div>
    <div class="card-body">
        <?php if (empty($today_records)): ?>
            <p class="text-muted">Belum ada data perawatan hari ini.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Waktu</th>
                            <th>NIS</th>
                            <th>Nama Siswa</th>
                            <th>Kelas</th>
                            <th>Keluhan</th>
                            <th>Diagnosa</th>
                            <th>Penanganan</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($today_records as $record): ?>
                        <tr>
                            <td><?php echo date('H:i', strtotime($record['created_at'])); ?></td>
                            <td><?php echo htmlspecialchars($record['student_number']); ?></td>
                            <td><?php echo htmlspecialchars($record['student_name']); ?></td>
                            <td><?php echo htmlspecialchars($record['class']); ?></td>
                            <td><?php echo htmlspecialchars($record['complaint']); ?></td>
                            <td><?php echo htmlspecialchars($record['diagnosis']); ?></td>
                            <td><?php echo htmlspecialchars($record['treatment']); ?></td>
                            <td>
                                <span class="badge bg-<?php 
                                    echo $record['status'] == 'pending' ? 'warning' : 
                                        ($record['status'] == 'treated' ? 'success' : 'danger'); 
                                ?>">
                                    <?php echo ucfirst($record['status']); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Recent Records -->
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">Riwayat Perawatan (7 Hari Terakhir)</h5>
    </div>
    <div class="card-body">
        <?php if (empty($recent_records)): ?>
            <p class="text-muted">Belum ada data perawatan dalam 7 hari terakhir.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>NIS</th>
                            <th>Nama Siswa</th>
                            <th>Kelas</th>
                            <th>Keluhan</th>
                            <th>Diagnosa</th>
                            <th>Penanganan</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_records as $record): ?>
                        <tr>
                            <td><?php echo date('d/m/Y H:i', strtotime($record['created_at'])); ?></td>
                            <td><?php echo htmlspecialchars($record['student_number']); ?></td>
                            <td><?php echo htmlspecialchars($record['student_name']); ?></td>
                            <td><?php echo htmlspecialchars($record['class']); ?></td>
                            <td><?php echo htmlspecialchars($record['complaint']); ?></td>
                            <td><?php echo htmlspecialchars($record['diagnosis']); ?></td>
                            <td><?php echo htmlspecialchars($record['treatment']); ?></td>
                            <td>
                                <span class="badge bg-<?php 
                                    echo $record['status'] == 'pending' ? 'warning' : 
                                        ($record['status'] == 'treated' ? 'success' : 'danger'); 
                                ?>">
                                    <?php echo ucfirst($record['status']); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'footer.php'; ?> 