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
    header("Location: index.php");
    exit();
}

// Get news ID from either GET or POST
$news_id = isset($_POST['id']) ? $_POST['id'] : (isset($_GET['id']) ? $_GET['id'] : null);

if (!$news_id) {
    $_SESSION['error'] = "ID berita tidak valid";
    header("Location: dashboard.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'];
    $content = $_POST['content'];
    $image_url = $_POST['image_url'];

    $stmt = $conn->prepare("
        UPDATE health_news 
        SET title = ?, content = ?, image_url = ?
        WHERE id = ?
    ");
    
    $stmt->bind_param("sssi", $title, $content, $image_url, $news_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Berita berhasil diperbarui!";
        header("Location: dashboard.php");
        exit();
    } else {
        $error = "Gagal mengupdate berita";
    }
}

// Get news data
$stmt = $conn->prepare("SELECT * FROM health_news WHERE id = ?");
$stmt->bind_param("i", $news_id);
$stmt->execute();
$result = $stmt->get_result();
$news = $result->fetch_assoc();

if (!$news) {
    $_SESSION['error'] = "Berita tidak ditemukan";
    header("Location: dashboard.php");
    exit();
}

// Include header after all potential redirects
require_once 'header.php';
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-edit me-2"></i>Edit Berita Kesehatan</h5>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <input type="hidden" name="id" value="<?php echo $news_id; ?>">
                        <div class="mb-3">
                            <label for="title" class="form-label">Judul</label>
                            <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($news['title']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="content" class="form-label">Konten</label>
                            <textarea class="form-control" id="content" name="content" rows="5" required><?php echo htmlspecialchars($news['content']); ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="image_url" class="form-label">URL Gambar (opsional)</label>
                            <input type="url" class="form-control" id="image_url" name="image_url" value="<?php echo htmlspecialchars($news['image_url']); ?>">
                        </div>
                        <div class="d-flex justify-content-end gap-2">
                            <a href="dashboard.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Batal
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?> 