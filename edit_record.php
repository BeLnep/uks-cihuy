<?php
// Start session and include database connection
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if user is admin
if ($_SESSION['role'] != 'admin') {
    header("Location: health_records.php");
    exit();
}

// Get record data
if (!isset($_GET['id'])) {
    header("Location: health_records.php");
    exit();
}

$record_id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM health_records WHERE id = ?");
$stmt->execute([$record_id]);
$record = $stmt->fetch();

if (!$record) {
    header("Location: health_records.php");
    exit();
}

// Get students list
$stmt = $pdo->query("SELECT * FROM students ORDER BY name");
$students = $stmt->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $student_id = $_POST['student_id'];
    $complaint = $_POST['complaint'];
    $diagnosis = $_POST['diagnosis'];
    $treatment = $_POST['treatment'];
    $status = $_POST['status'];

    $stmt = $pdo->prepare("
        UPDATE health_records 
        SET student_id = ?, complaint = ?, diagnosis = ?, treatment = ?, status = ?
        WHERE id = ?
    ");
    
    if ($stmt->execute([$student_id, $complaint, $diagnosis, $treatment, $status, $record_id])) {
        header("Location: health_records.php");
        exit();
    } else {
        $error = "Gagal mengupdate data";
    }
}

// Include header after all redirects
require_once 'header.php';
?>

<h2>Edit Data Perawatan</h2>

<?php if (isset($error)): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<div class="card mt-4">
    <div class="card-body">
        <form method="POST">
            <div class="mb-3">
                <label for="student_id" class="form-label">Siswa</label>
                <select class="form-select" id="student_id" name="student_id" required>
                    <option value="">Pilih Siswa</option>
                    <?php foreach ($students as $student): ?>
                    <option value="<?php echo $student['id']; ?>" <?php echo $student['id'] == $record['student_id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($student['name'] . ' - ' . $student['class'] . ' (' . $student['student_id'] . ')'); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="complaint" class="form-label">Keluhan</label>
                <textarea class="form-control" id="complaint" name="complaint" rows="3" required><?php echo htmlspecialchars($record['complaint']); ?></textarea>
            </div>
            <div class="mb-3">
                <label for="diagnosis" class="form-label">Diagnosa</label>
                <textarea class="form-control" id="diagnosis" name="diagnosis" rows="3"><?php echo htmlspecialchars($record['diagnosis']); ?></textarea>
            </div>
            <div class="mb-3">
                <label for="treatment" class="form-label">Penanganan</label>
                <textarea class="form-control" id="treatment" name="treatment" rows="3"><?php echo htmlspecialchars($record['treatment']); ?></textarea>
            </div>
            <div class="mb-3">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status" required>
                    <option value="pending" <?php echo $record['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="treated" <?php echo $record['status'] == 'treated' ? 'selected' : ''; ?>>Ditangani</option>
                    <option value="referred" <?php echo $record['status'] == 'referred' ? 'selected' : ''; ?>>Dirujuk</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Update</button>
            <a href="health_records.php" class="btn btn-secondary">Kembali</a>
        </form>
    </div>
</div>

<?php require_once 'footer.php'; ?>