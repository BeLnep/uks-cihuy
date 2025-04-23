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

// Get news data
if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$news_id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM health_news WHERE id = ?");
$stmt->execute([$news_id]);
$news = $stmt->fetch();

if (!$news) {
    header("Location: index.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'];
    $content = $_POST['content'];
    $image_url = $_POST['image_url'];

    $stmt = $pdo->prepare("
        UPDATE health_news 
        SET title = ?, content = ?, image_url = ?
        WHERE id = ?
    ");
    
    if ($stmt->execute([$title, $content, $image_url, $news_id])) {
        header("Location: dashboard.php");
        exit();
    } else {
        $error = "Gagal mengupdate berita";
    }
}

// Include header after all potential redirects
require_once 'header.php';
?>

<h2>Edit Berita Kesehatan</h2>

<?php if (isset($error)): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<div class="card mt-4">
    <div class="card-body">
        <form method="POST">
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
            <button type="submit" class="btn btn-primary">Update</button>
            <a href="index.php" class="btn btn-secondary">Kembali</a>
        </form>
    </div>
</div>

<?php require_once 'footer.php'; ?> 