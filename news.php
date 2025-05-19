<?php
require_once 'config/config.php';
require_once 'includes/auth.php';

// Get all news
$query = "SELECT * FROM news ORDER BY created_at DESC";
$result = mysqli_query($conn, $query);
$news = mysqli_fetch_all($result, MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Berita - UKS CIHUY</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container mt-5">
        <h2 class="text-center mb-4">Berita Terbaru</h2>
        <div class="row">
        <?php foreach ($news as $news_item): ?>
            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title"><?= htmlspecialchars($news_item['title']); ?></h5>
                        <p class="card-text"><?= htmlspecialchars($news_item['content']); ?></p>
                        <p class="card-text"><small class="text-muted">Diposting pada: <?= date('d/m/Y H:i', strtotime($news_item['created_at'])); ?></small></p>
                        
                        <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'user'): ?>
                        <div class="d-flex justify-content-end mt-3">
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newsModal<?= $news_item['id']; ?>">
                                <i class="fas fa-book-reader me-2"></i>Baca Selengkapnya
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        </div>
    </div>

    <!-- News Detail Modals -->
    <?php foreach ($news as $news_item): ?>
    <div class="modal fade" id="newsModal<?= $news_item['id']; ?>" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?= htmlspecialchars($news_item['title']); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <?php if (!empty($news_item['image'])): ?>
                    <img src="uploads/news/<?= htmlspecialchars($news_item['image']); ?>" class="img-fluid mb-3" alt="<?= htmlspecialchars($news_item['title']); ?>">
                    <?php endif; ?>
                    <div class="news-content">
                        <?= nl2br(htmlspecialchars($news_item['content'])); ?>
                    </div>
                    <div class="mt-3">
                        <small class="text-muted">Diposting pada: <?= date('d/m/Y H:i', strtotime($news_item['created_at'])); ?></small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <style>
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            padding: 0.5rem 1.5rem;
            border-radius: 50px;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
            transform: translateY(-2px);
        }
        
        .news-content {
            line-height: 1.8;
            font-size: 1.1rem;
        }
    </style>
</body>
</html> 