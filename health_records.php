<?php
require_once 'header.php';

// Handle record deletion
if (isset($_POST['delete_record']) && $_SESSION['role'] == 'admin') {
    $record_id = $_POST['record_id'];
    $stmt = $pdo->prepare("DELETE FROM health_records WHERE id = ?");
    if ($stmt->execute([$record_id])) {
        $success = "Data berhasil dihapus";
    } else {
        $error = "Gagal menghapus data";
    }
}

// Get all health records
$stmt = $pdo->query("
    SELECT hr.*, s.name as student_name, s.class, s.student_id as student_number
    FROM health_records hr 
    JOIN students s ON hr.student_id = s.id 
    ORDER BY hr.created_at DESC
");
$health_records = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Perawatan</h2>
    <?php if ($_SESSION['role'] == 'admin'): ?>
    <a href="add_record.php" class="btn btn-primary">Tambah Data Perawatan</a>
    <?php endif; ?>
</div>

<?php if (isset($success)): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>

<?php if (isset($error)): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
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
                        <?php if ($_SESSION['role'] == 'admin'): ?>
                        <th>Aksi</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($health_records as $record): ?>
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
                        <?php if ($_SESSION['role'] == 'admin'): ?>
                        <td>
                            <a href="edit_record.php?id=<?php echo $record['id']; ?>" class="btn btn-sm btn-warning">Edit</a>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="record_id" value="<?php echo $record['id']; ?>">
                                <button type="submit" name="delete_record" class="btn btn-sm btn-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus data ini?')">Hapus</button>
                            </form>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?> 