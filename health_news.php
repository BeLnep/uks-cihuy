<?php
require_once 'header.php';

// Handle news deletion
if (isset($_POST['delete_news']) && $_SESSION['role'] == 'admin') {
    $news_id = $_POST['news_id'];
    $stmt = $pdo->prepare("DELETE FROM health_news WHERE id = ?");
    if ($stmt->execute([$news_id])) {
        $success = "Berita berhasil dihapus";
    } else {
        $error = "Gagal menghapus berita";
    }
}

// Get health news
$stmt = $pdo->query("
    SELECT hn.*, u.username as author_name 
    FROM health_news hn 
    JOIN users u ON hn.created_by = u.id 
    ORDER BY hn.created_at DESC
");
$news = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Berita Kesehatan</h2>
    <?php if ($_SESSION['role'] == 'admin'): ?>
    <a href="add_news.php" class="btn btn-primary">Tambah Berita</a>
    <?php endif; ?>
</div>

<?php if (isset($success)): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>

<?php if (isset($error)): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<div class="row">
    <?php foreach ($news as $item): ?>
    <div class="col-md-4 mb-4">
        <div class="card h-100">
            <?php if ($item['image_url']): ?>
            <img src="<?php echo htmlspecialchars($item['image_url']); ?>" class="card-img-top" alt="News Image">
            <?php endif; ?>
            <div class="card-body">
                <h5 class="card-title"><?php echo htmlspecialchars($item['title']); ?></h5>
                <p class="card-text"><?php echo substr(htmlspecialchars($item['content']), 0, 150) . '...'; ?></p>
                <div class="d-flex justify-content-between align-items-center">
                    <small class="text-muted">Oleh: <?php echo htmlspecialchars($item['author_name']); ?></small>
                    <small class="text-muted"><?php echo date('d/m/Y', strtotime($item['created_at'])); ?></small>
                </div>
                <?php if ($_SESSION['role'] == 'admin'): ?>
                <div class="mt-3">
                    <a href="edit_news.php?id=<?php echo $item['id']; ?>" class="btn btn-sm btn-warning">Edit</a>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="news_id" value="<?php echo $item['id']; ?>">
                        <button type="submit" name="delete_news" class="btn btn-sm btn-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus berita ini?')">Hapus</button>
                    </form>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php require_once 'footer.php'; ?> 