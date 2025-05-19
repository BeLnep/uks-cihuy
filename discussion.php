<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Include database connection
require_once 'config/database.php';

// Get discussion ID
$discussion_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get discussion details
$stmt = $conn->prepare("SELECT ld.*, u.username, u.role 
                       FROM lobby_discussions ld 
                       JOIN users u ON ld.user_id = u.id 
                       WHERE ld.id = ?");
$stmt->bind_param("i", $discussion_id);
$stmt->execute();
$result = $stmt->get_result();
$discussion = $result->fetch_assoc();

if (!$discussion) {
    header("Location: lobby.php");
    exit();
}

// Handle new comment
if (isset($_POST['add_comment'])) {
    $content = $_POST['content'];
    $user_id = $_SESSION['user_id'];
    
    $stmt = $conn->prepare("INSERT INTO lobby_comments (discussion_id, user_id, content) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $discussion_id, $user_id, $content);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Komentar berhasil ditambahkan!";
    } else {
        $_SESSION['error'] = "Gagal menambahkan komentar.";
    }
    
    header("Location: discussion.php?id=" . $discussion_id);
    exit();
}

// Get comments
$stmt = $conn->prepare("SELECT lc.*, u.username, u.role 
                       FROM lobby_comments lc 
                       JOIN users u ON lc.user_id = u.id 
                       WHERE lc.discussion_id = ? 
                       ORDER BY lc.created_at ASC");
$stmt->bind_param("i", $discussion_id);
$stmt->execute();
$comments = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($discussion['title']); ?> - UKS System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #dc3545;
            --secondary-color: #c82333;
            --accent-color: #e4606d;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --light-bg: #f8f9fa;
            --dark-text: #212529;
            --card-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --hover-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--light-bg);
            color: var(--dark-text);
            min-height: 100vh;
        }

        .page-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 1.5rem 0;
            margin-bottom: 2rem;
            box-shadow: var(--card-shadow);
            animation: fadeInDown 0.5s ease-out;
        }

        .card {
            border: none;
            border-radius: 20px;
            background: white;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.08);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            transform: translateY(0);
            margin-bottom: 1.5rem;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.18);
            animation: fadeInUp 0.5s ease-out;
        }

        .card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.12);
        }

        .card-header {
            background: white;
            border-bottom: 1px solid rgba(0,0,0,0.1);
            border-radius: 20px 20px 0 0 !important;
            padding: 1.5rem;
            color: var(--primary-color);
            position: relative;
            overflow: hidden;
        }

        .card-header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background: var(--gradient-primary);
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 0.5s ease;
        }

        .card:hover .card-header::after {
            transform: scaleX(1);
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .comment {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            transform: translateY(0);
            animation: fadeInUp 0.5s ease-out;
            animation-fill-mode: both;
        }

        .comment:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
        }

        .comment-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }

        .comment-author {
            font-weight: 600;
            color: var(--primary-color);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .comment-date {
            color: #6c757d;
            font-size: 0.9rem;
        }

        .comment-content {
            color: var(--dark-text);
            line-height: 1.6;
        }

        .btn {
            border-radius: 50px;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transform: translateX(-100%);
            transition: transform 0.6s;
        }

        .btn:hover::before {
            transform: translateX(100%);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(220, 53, 69, 0.2);
        }

        .back-to-lobby {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            z-index: 1000;
            padding: 1rem 2rem;
            transform: translateY(0);
            transition: all 0.3s ease;
        }

        .back-to-lobby:hover {
            transform: translateY(-5px);
        }

        .alert {
            border-radius: 15px;
            border: none;
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .form-control, .form-select {
            border-radius: 12px;
            padding: 0.85rem 1.5rem;
            border: 2px solid rgba(238, 242, 247, 0.8);
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(220, 53, 69, 0.15);
            transform: translateY(-2px);
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


        /* Add staggered animation for comments */
        .comment:nth-child(1) { animation-delay: 0.1s; }
        .comment:nth-child(2) { animation-delay: 0.2s; }
        .comment:nth-child(3) { animation-delay: 0.3s; }
        .comment:nth-child(4) { animation-delay: 0.4s; }
        .comment:nth-child(5) { animation-delay: 0.5s; }
    </style>
</head>
<body>
    <div class="page-header text-center">
        <div class="container">
            <h1 class="display-4 fw-bold"><?php echo htmlspecialchars($discussion['title']); ?></h1>
            <p class="lead">
                Dibuat oleh <?php echo htmlspecialchars($discussion['username']); ?> • 
                <?php echo date('d/m/Y H:i', strtotime($discussion['created_at'])); ?>
            </p>
        </div>
    </div>

    <div class="container">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i> <?php echo $_SESSION['success']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i> <?php echo $_SESSION['error']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body">
                        <div class="discussion-content">
                            <?php echo nl2br(htmlspecialchars($discussion['content'])); ?>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-comments me-2"></i>Komentar</h5>
                    </div>
                    <div class="card-body">
                        <?php while ($comment = mysqli_fetch_assoc($comments)): ?>
                            <div class="comment">
                                <div class="comment-header">
                                    <div class="comment-author">
                                        <?php echo htmlspecialchars($comment['username']); ?>
                                        <?php if ($comment['role'] == 'admin'): ?>
                                            <span class="badge bg-danger">Admin</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="comment-date">
                                        <?php echo date('d/m/Y H:i', strtotime($comment['created_at'])); ?>
                                    </div>
                                </div>
                                <div class="comment-content">
                                    <?php echo nl2br(htmlspecialchars($comment['content'])); ?>
                                </div>
                            </div>
                        <?php endwhile; ?>

                        <form method="POST" action="" class="mt-4">
                            <div class="alert alert-info mb-4">
                                <h6 class="alert-heading"><i class="fas fa-info-circle me-2"></i>Penting!</h6>
                                <p class="mb-0">Sebelum menambahkan komentar, harap perhatikan hal berikut:</p>
                                <ul class="mb-0 mt-2">
                                    <li>Gunakan bahasa yang sopan dan santun</li>
                                    <li>Hindari kata-kata kasar atau tidak pantas</li>
                                    <li>Jaga etika dalam berkomunikasi</li>
                                    <li>Fokus pada topik diskusi</li>
                                </ul>
                            </div>
                            <div class="mb-3">
                                <label for="content" class="form-label">Tambah Komentar</label>
                                <textarea class="form-control" id="content" name="content" rows="3" required></textarea>
                            </div>
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="agree_comment_terms" required>
                                <label class="form-check-label" for="agree_comment_terms">
                                    Saya setuju untuk menggunakan bahasa yang sopan dan santun dalam komentar
                                </label>
                            </div>
                            <button type="submit" name="add_comment" class="btn btn-primary">
                                <i class="fas fa-paper-plane me-2"></i>Kirim Komentar
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Informasi Diskusi</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Kategori:</strong> 
                            <?php
                            switch ($discussion['category']) {
                                case 'health_tips':
                                    echo 'Tips Kesehatan';
                                    break;
                                case 'schedule':
                                    echo 'Jadwal';
                                    break;
                                case 'qa':
                                    echo 'Tanya Jawab';
                                    break;
                            }
                            ?>
                        </p>
                        <p><strong>Dibuat:</strong> <?php echo date('d/m/Y H:i', strtotime($discussion['created_at'])); ?></p>
                        <p><strong>Terakhir diperbarui:</strong> <?php echo date('d/m/Y H:i', strtotime($discussion['updated_at'])); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <a href="lobby.php" class="btn btn-primary back-to-lobby">
        <i class="fas fa-arrow-left me-2"></i> Kembali ke Lobby
    </a>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Handle comment form submission
        document.querySelector('form[method="POST"]').addEventListener('submit', function(e) {
            const agreeCheckbox = document.getElementById('agree_comment_terms');
            if (!agreeCheckbox.checked) {
                e.preventDefault();
                alert('Anda harus menyetujui untuk menggunakan bahasa yang sopan dan santun');
            }
        });
    </script>
</body>
</html> 