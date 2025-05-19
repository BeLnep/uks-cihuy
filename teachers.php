<?php
require_once 'header.php';

// Check if user is admin
if ($_SESSION['role'] != 'admin') {
    header("Location: dashboard.php");
    exit();
}

// Handle teacher deletion
if (isset($_POST['delete_teacher'])) {
    $teacher_id = $_POST['teacher_id'];
    $stmt = $pdo->prepare("DELETE FROM teachers WHERE id = ?");
    if ($stmt->execute([$teacher_id])) {
        $success = "Guru berhasil dihapus";
    } else {
        $error = "Gagal menghapus guru";
    }
}

// Get all teachers
$stmt = $pdo->query("SELECT * FROM teachers ORDER BY name");
$teachers = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Guru Piket UKS</h2>
    <a href="add_teacher.php" class="btn btn-primary">Tambah Guru</a>
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
                        <th>NIP</th>
                        <th>Nama</th>
                        <th>No. Telepon</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($teachers as $teacher): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($teacher['nip'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($teacher['name'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($teacher['phone'] ?? ''); ?></td>
                        <td>
                            <a href="edit_teacher.php?id=<?php echo $teacher['id']; ?>" class="btn btn-sm btn-warning">Edit</a>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="teacher_id" value="<?php echo $teacher['id']; ?>">
                                <button type="submit" name="delete_teacher" class="btn btn-sm btn-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus guru ini?')">Hapus</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?> 