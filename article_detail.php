<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if article ID is provided
if (!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$article_id = mysqli_real_escape_string($conn, $_GET['id']);

// Get article details
$query = "SELECT hn.*, u.username as author_name 
          FROM health_news hn 
          JOIN users u ON hn.created_by = u.id 
          WHERE hn.id = '$article_id'";
$result = mysqli_query($conn, $query);
$article = mysqli_fetch_assoc($result);

if (!$article) {
    header("Location: dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($article['title']); ?> - UKS CIHUY</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #dc3545;
            --secondary-color: #c82333;
            --accent-color: #e4606d;
            --light-bg: #f8f9fa;
            --dark-text: #212529;
            --card-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --hover-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
        }

        body {
            background-color: var(--light-bg);
            font-family: 'Poppins', sans-serif;
            padding-top: 2rem;
            padding-bottom: 4rem;
        }

        .article-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 3rem 2rem;
            position: relative;
            overflow: hidden;
            border-radius: 20px 20px 0 0;
            text-align: center;
            margin: -2rem -2rem 2rem -2rem;
        }

        .article-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="40" fill="rgba(255,255,255,0.1)"/></svg>') repeat;
            opacity: 0.1;
        }

        .article-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
        }

        .article-meta {
            display: flex;
            gap: 2rem;
            color: rgba(255,255,255,0.9);
            justify-content: center;
            align-items: center;
        }

        .article-meta i {
            margin-right: 0.5rem;
        }

        .article-content {
            line-height: 1.8;
            font-size: 1.1rem;
            color: var(--dark-text);
            margin-bottom: 2rem;
            padding-bottom: 2rem;
            border-bottom: 1px solid rgba(0,0,0,0.1);
        }

        .article-image {
            width: 100%;
            height: 400px;
            object-fit: cover;
            border-radius: 15px;
            margin: 2rem 0;
            box-shadow: var(--card-shadow);
            transition: transform 0.3s ease;
        }

        .article-image:hover {
            transform: scale(1.02);
        }

        .article-card {
            background: white;
            border-radius: 20px;
            box-shadow: var(--card-shadow);
            padding: 2rem;
            position: relative;
            z-index: 1;
            animation: fadeInUp 0.6s ease-out;
        }

        .back-button {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
            background: var(--primary-color);
            border: none;
            padding: 1rem 2rem;
            border-radius: 50px;
            color: white;
            font-weight: 500;
            box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .back-button:hover {
            background: var(--secondary-color);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(220, 53, 69, 0.4);
            color: white;
        }

        .back-button i {
            transition: transform 0.3s ease;
        }

        .back-button:hover i {
            transform: translateX(-5px);
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .article-tags {
            display: flex;
            gap: 0.5rem;
            margin: 1.5rem 0;
            flex-wrap: wrap;
        }

        .article-tag {
            background: rgba(220, 53, 69, 0.1);
            color: var(--primary-color);
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .article-share {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid rgba(0,0,0,0.1);
        }

        .share-button {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .share-button:hover {
            transform: translateY(-3px);
            color: white;
        }

        .comments-section {
            margin-top: 2rem;
        }

        .comments-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: var(--dark-text);
        }

        .comment-form {
            margin-bottom: 2rem;
        }

        .comment-textarea {
            border: 1px solid rgba(0,0,0,0.1);
            border-radius: 15px;
            padding: 1rem;
            font-family: inherit;
            resize: vertical;
            min-height: 100px;
            transition: all 0.3s ease;
        }

        .comment-textarea:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
            outline: none;
        }

        .comment-submit {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 50px;
            font-weight: 500;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .comment-submit:hover {
            background: var(--secondary-color);
            transform: translateY(-2px);
        }

        .comment-list {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .comment-item {
            padding: 1.5rem;
            background: rgba(0,0,0,0.02);
            border-radius: 15px;
            transition: all 0.3s ease;
        }

        .comment-item:hover {
            background: rgba(0,0,0,0.03);
            transform: translateX(5px);
        }

        .comment-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .comment-author {
            font-weight: 600;
            color: var(--primary-color);
        }

        .comment-date {
            font-size: 0.9rem;
            color: #6c757d;
        }

        .comment-content {
            color: var(--dark-text);
            line-height: 1.6;
        }

        .no-comments {
            text-align: center;
            color: #6c757d;
            font-style: italic;
            padding: 2rem;
            background: rgba(0,0,0,0.02);
            border-radius: 15px;
        }

    </style>
</head>
<body>
    <div class="container">
        <div class="article-card">
            <div class="article-header">
                <div class="text-center">
                    <h1 class="article-title"><?= htmlspecialchars($article['title']); ?></h1>
                    <div class="article-meta">
                        <span><i class="fas fa-user"></i> <?= htmlspecialchars($article['author_name']); ?></span>
                        <span><i class="fas fa-calendar"></i> <?= date('d/m/Y H:i', strtotime($article['created_at'])); ?></span>
                    </div>
                </div>
            </div>

            <?php if ($article['image_url']): ?>
            <img src="<?= htmlspecialchars($article['image_url']); ?>" class="article-image" alt="<?= htmlspecialchars($article['title']); ?>">
            <?php endif; ?>
            
            <div class="article-tags">
                <span class="article-tag">Kesehatan</span>
                <span class="article-tag">UKS</span>
                <span class="article-tag">Informasi</span>
            </div>
            
            <div class="article-content">
                <?= nl2br(htmlspecialchars($article['content'])); ?>
            </div>

            <div class="comments-section">
                <h3 class="comments-title">Komentar</h3>
                
                <!-- Comment Form -->
                <form class="comment-form" method="POST" action="add_comment.php">
                    <input type="hidden" name="article_id" value="<?= $article_id ?>">
                    <div class="mb-3">
                        <textarea class="form-control comment-textarea" name="comment" rows="4" placeholder="Tulis komentar Anda di sini..." required></textarea>
                    </div>
                    <div class="text-end">
                        <button type="submit" class="comment-submit">
                            <i class="fas fa-paper-plane me-2"></i>Kirim Komentar
                        </button>
                    </div>
                </form>

                <!-- Comments List -->
                <div class="comment-list">
                    <?php
                    // Get comments for this article
                    $comments_query = "SELECT c.*, u.username 
                                     FROM comments c 
                                     JOIN users u ON c.user_id = u.id 
                                     WHERE c.article_id = '$article_id' 
                                     ORDER BY c.created_at DESC";
                    $comments_result = mysqli_query($conn, $comments_query);
                    
                    if (mysqli_num_rows($comments_result) > 0):
                        while ($comment = mysqli_fetch_assoc($comments_result)):
                    ?>
                        <div class="comment-item">
                            <div class="comment-header">
                                <span class="comment-author">
                                    <i class="fas fa-user-circle me-2"></i>
                                    <?= htmlspecialchars($comment['username']); ?>
                                </span>
                                <span class="comment-date">
                                    <i class="fas fa-clock me-1"></i>
                                    <?= date('d/m/Y H:i', strtotime($comment['created_at'])); ?>
                                </span>
                            </div>
                            <div class="comment-content">
                                <?= nl2br(htmlspecialchars($comment['content'])); ?>
                            </div>
                        </div>
                    <?php 
                        endwhile;
                    else:
                    ?>
                        <div class="no-comments">
                            <i class="fas fa-comments me-2"></i>
                            Belum ada komentar. Jadilah yang pertama berkomentar!
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <a href="dashboard.php" class="back-button">
        <i class="fas fa-arrow-left me-2"></i> Kembali ke Dashboard
    </a>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 