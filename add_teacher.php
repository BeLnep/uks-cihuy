<?php
require_once 'header.php';

// Check if user is admin
if ($_SESSION['role'] != 'admin') {
    header("Location: dashboard.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nip = $_POST['nip'];
    $name = $_POST['name'];
    $phone = $_POST['phone'];

    // Check if NIP already exists
    $stmt = $pdo->prepare("SELECT id FROM teachers WHERE nip = ?");
    $stmt->execute([$nip]);
    if ($stmt->fetch()) {
        $error = "NIP sudah terdaftar";
    } else {
        // Insert new teacher
        $stmt = $pdo->prepare("INSERT INTO teachers (nip, name, phone) VALUES (?, ?, ?)");
        if ($stmt->execute([$nip, $name, $phone])) {
            header("Location: teachers.php");
            exit();
        } else {
            $error = "Gagal menambahkan guru";
        }
    }
}
?>

<h2>Tambah Guru Piket UKS</h2>

<?php if (isset($error)): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<div class="card mt-4">
    <div class="card-body">
        <form method="POST">
            <div class="mb-3">
                <label for="nip" class="form-label">NIP</label>
                <input type="text" class="form-control" id="nip" name="nip" required>
            </div>
            <div class="mb-3">
                <label for="name" class="form-label">Nama Lengkap</label>
                <input type="text" class="form-control" id="name" name="name" required>
            </div>
            <div class="mb-3">
                <label for="phone" class="form-label">No. Telepon</label>
                <input type="tel" class="form-control" id="phone" name="phone" required>
            </div>
            <button type="submit" class="btn btn-primary">Simpan</button>
            <a href="teachers.php" class="btn btn-secondary">Kembali</a>
        </form>
    </div>
</div>

<?php require_once 'footer.php'; ?> 