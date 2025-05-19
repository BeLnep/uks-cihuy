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
require_once 'config/database.php'; // Pastikan path ini benar

// Get user information
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
// Simpan role ke session jika belum ada atau untuk update
$_SESSION['role'] = $user['role']; 

// Handle new discussion creation
if (isset($_POST['create_discussion'])) {
    $title = $_POST['title'];
    $content = $_POST['content'];
    $category = $_POST['category'];
    
    $stmt = $conn->prepare("INSERT INTO lobby_discussions (user_id, title, content, category) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $user_id, $title, $content, $category);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Diskusi berhasil dibuat!";
    } else {
        $_SESSION['error'] = "Gagal membuat diskusi.";
    }
    
    header("Location: lobby.php");
    exit();
}

// Handle discussion deletion (Hanya Admin)
if (isset($_POST['delete_discussion']) && isset($_SESSION['role']) && $_SESSION['role'] == 'admin') {
    $discussion_id = isset($_POST['discussion_id']) ? (int)$_POST['discussion_id'] : 0;
    
    if ($discussion_id > 0) {
        // Start transaction
        mysqli_begin_transaction($conn);
        
        try {
            // Delete comments first
            $stmt = $conn->prepare("DELETE FROM lobby_comments WHERE discussion_id = ?");
            $stmt->bind_param("i", $discussion_id);
            $stmt->execute();
            
            // Then delete the discussion
            $stmt = $conn->prepare("DELETE FROM lobby_discussions WHERE id = ?");
            $stmt->bind_param("i", $discussion_id);
            $stmt->execute();
            
            // If all operations successful, commit the transaction
            mysqli_commit($conn);
            $_SESSION['success'] = "Diskusi berhasil dihapus";
        } catch (Exception $e) {
            // If any operation fails, rollback the transaction
            mysqli_rollback($conn);
            $_SESSION['error'] = "Gagal menghapus diskusi: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "ID diskusi tidak valid";
    }
    
    header("Location: lobby.php");
    exit();
}

// Get recent discussions (Misal, 10 terbaru)
$query = "SELECT ld.*, u.username, u.role 
          FROM lobby_discussions ld 
          JOIN users u ON ld.user_id = u.id 
          ORDER BY ld.created_at DESC 
          LIMIT 10"; // Ambil lebih banyak diskusi jika perlu
$discussions_result = mysqli_query($conn, $query);

// Get active users (dalam 10 menit terakhir)
// Pastikan ada tabel user_sessions dengan kolom user_id dan login_time
$active_users_query = "SELECT DISTINCT u.id, u.username 
                       FROM users u 
                       JOIN user_sessions us ON u.id = us.user_id 
                       WHERE us.login_time >= DATE_SUB(NOW(), INTERVAL 10 MINUTE)
                       ORDER BY us.login_time DESC";
$active_users_result = mysqli_query($conn, $active_users_query);
$active_users = [];
if ($active_users_result) {
    while($row = mysqli_fetch_assoc($active_users_result)) {
        $active_users[] = $row;
    }
}
$active_users_count = count($active_users);


// Get current page for active menu (jika diperlukan)
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lobby - UKS System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
        }

        .page-header {
            background: linear-gradient(135deg, #0d6efd, #0dcaf0);
            color: white;
            padding: 3rem 0;
            margin-bottom: 2rem;
            text-align: center;
        }

        .discussion-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
            transition: transform 0.3s ease-in-out, box-shadow 0.3s ease-in-out;
            overflow: hidden;
        }

        .discussion-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.2);
        }

        .discussion-header {
            padding: 1.5rem;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
        }

        .discussion-body {
            padding: 1.5rem;
        }

        .discussion-footer {
            padding: 1rem 1.5rem;
            background-color: rgba(0, 0, 0, 0.02);
            border-top: 1px solid rgba(0, 0, 0, 0.1);
        }

        .comment-section {
            margin-top: 1rem;
        }

        .comment-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
            transition: transform 0.3s ease;
        }

        .comment-card:hover {
            transform: translateX(5px);
        }

        .btn {
            border-radius: 50px;
            padding: 0.5rem 1.5rem;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: linear-gradient(135deg, #dc3545, #c82333);
            border: none;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #dc3545, #c82333);
            transform: translateY(-2px);
        }

        .form-control {
            border-radius: 10px;
            border: 1px solid rgba(0, 0, 0, 0.1);
            padding: 0.75rem 1rem;
        }

        .form-control:focus {
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
            border-color: #0d6efd;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 1rem;
        }

        .timestamp {
            color: #6c757d;
            font-size: 0.875rem;
        }

        .modal-content {
            border-radius: 15px;
            border: none;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .modal-header {
            background: linear-gradient(135deg, #0d6efd, #0dcaf0);
            color: white;
            border-radius: 15px 15px 0 0;
        }

        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: #6c757d;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        :root {
            --primary-color: #dc3545;
            --secondary-color: #c82333;
            --accent-color: #e4606d;
            --success-color: #198754; /* Updated success color */
            --warning-color: #ffc107;
            --info-color: #0dcaf0;    /* Added info color */
            --light-bg: #f8f9fa;
            --dark-text: #212529;
            --card-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --hover-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
        }

        .page-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 2rem 0; /* Increased padding */
            margin-bottom: 2.5rem; /* Increased margin */
            box-shadow: var(--card-shadow);
            width: 100%;
            max-width: 100%;
        }

        .page-title {
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .page-description {
            opacity: 0.9; /* Slightly less opaque */
            font-weight: 400;
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: var(--card-shadow);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            margin-bottom: 1.5rem;
            background-color: #fff; /* Ensure card background is white */
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: var(--hover-shadow);
        }

        .card-header {
            background: white;
            border-bottom: 1px solid rgba(0,0,0,0.1);
            border-radius: 15px 15px 0 0 !important;
            padding: 1.25rem 1.5rem; /* Adjusted padding */
            color: var(--primary-color);
            font-weight: 600;
            display: flex;
            align-items: center;
        }
        .card-header i {
            margin-right: 0.75rem; /* Space after icon */
        }

        .card-body {
            padding: 1.5rem;
        }

        .btn {
            border-radius: 50px;
            padding: 0.5rem 1.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
            border: none; /* Remove default border */
        }

        .btn-danger {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
        }
        .btn-danger:hover {
             background-color: var(--secondary-color);
             border-color: var(--secondary-color);
             transform: translateY(-2px);
             box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        .btn-secondary {
             background-color: #6c757d;
             border-color: #6c757d;
             color: white;
        }
         .btn-secondary:hover {
             background-color: #5a6268;
             border-color: #545b62;
             transform: translateY(-2px);
             box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }


        .lobby-item {
            display: flex;
            align-items: flex-start; /* Align items to the top */
            padding: 1.25rem; /* Increased padding */
            border-radius: 10px;
            background-color: white;
            margin-bottom: 1rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.07); /* Softer shadow */
            transition: all 0.3s ease;
            border: 1px solid #eee; /* Subtle border */
        }

        .lobby-item:hover {
            transform: translateY(-3px) scale(1.01); /* Slight scale effect */
            box-shadow: var(--hover-shadow);
            border-color: var(--accent-color);
        }

        .lobby-icon {
            width: 45px; /* Slightly smaller */
            height: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: var(--light-bg);
            border-radius: 50%;
            margin-right: 1rem;
            color: var(--primary-color);
            font-size: 1.4rem; /* Adjusted size */
            flex-shrink: 0; /* Prevent icon from shrinking */
        }

        .lobby-content {
            flex: 1;
            min-width: 0; /* Prevent content from overflowing */
        }

        .lobby-title {
            font-weight: 600;
            margin-bottom: 0.25rem;
            color: var(--dark-text);
            word-break: break-word; /* Prevent long titles from breaking layout */
        }
        .lobby-title a {
            text-decoration: none;
            color: inherit;
        }
         .lobby-title a:hover {
            color: var(--primary-color);
        }


        .lobby-description {
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 0.5rem; /* Add space before meta */
             white-space: pre-wrap; /* Preserve line breaks in content */
             word-break: break-word; /* Break long words */
        }

        .lobby-meta {
            font-size: 0.8rem;
            color: #888;
        }

        .lobby-action {
            margin-left: 1rem;
            display: flex;
            align-items: center; /* Align buttons vertically */
             flex-shrink: 0; /* Prevent action area from shrinking */
        }
        .lobby-action .btn {
            padding: 0.3rem 1rem; /* Smaller padding for action buttons */
            font-size: 0.85rem; /* Smaller font size */
        }
         .lobby-action .btn-danger i {
            font-size: 0.9em; /* Adjust icon size if needed */
        }


        .back-to-dashboard {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            z-index: 1000;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 50px;
            font-weight: 500;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
        }

        .back-to-dashboard:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(220, 53, 69, 0.4);
            background: linear-gradient(135deg, var(--secondary-color), var(--primary-color));
            color: white;
        }

        /* Fade-in animation for content */
        .content-section {
            opacity: 0;
            animation: fadeIn 0.8s 0.2s ease-out forwards; /* Added delay */
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

        /* Staggered animation for cards */
        .row > .col-md-8 .card,
        .row > .col-md-4 .card {
            opacity: 0;
            animation: fadeIn 0.5s ease-in forwards;
        }
        /* Apply delays based on column and card order */
        .row > .col-md-8 .card { animation-delay: 0.4s; }
        .row > .col-md-4 .card:nth-child(1) { animation-delay: 0.5s; }
        .row > .col-md-4 .card:nth-child(2) { animation-delay: 0.6s; }


        .modal-content {
            border-radius: 15px;
            border: none;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2); /* Stronger shadow for modal */
        }
        
        .modal-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: 15px 15px 0 0;
            border-bottom: none; /* Remove border */
             padding: 1rem 1.5rem; /* Adjust padding */
        }
        .modal-header .btn-close {
            filter: invert(1) grayscale(100%) brightness(200%); /* Make close button white */
        }
        
        .modal-body {
            padding: 2rem;
        }
         .modal-body .alert {
             border-radius: 8px;
         }
        
        .modal-footer {
            background-color: var(--light-bg); /* Light background for footer */
            border-top: 1px solid #dee2e6;
             border-radius: 0 0 15px 15px;
             padding: 1rem 1.5rem; /* Adjust padding */
        }

        .form-control, .form-select {
            border-radius: 10px;
            padding: 0.75rem 1rem;
            border: 1px solid #ced4da; /* Standard border */
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--accent-color); /* Focus color */
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
        }
        
        .btn-create {
            background: var(--primary-color);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 50px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-create:hover {
            background: var(--secondary-color);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
        }

         /* Fix modal backdrop issue if necessary - This might hide it completely */
        /* .modal-backdrop.show {
             opacity: 0.5 !important; 
             background-color: rgba(0, 0, 0, 0.5) !important; 
         } */

        /* Shake animation for validation */
         @keyframes shake {
             0%, 100% { transform: translateX(0); }
             25%, 75% { transform: translateX(-6px); } /* Increased shake */
             50% { transform: translateX(6px); }
         }

         .shake {
             animation: shake 0.4s ease-in-out;
         }

         #agreementError {
             transition: all 0.3s ease;
         }

        /* Styling for active users list */
        .active-user-item {
            transition: background-color 0.2s ease;
            padding: 0.5rem 0.75rem;
            border-radius: 8px;
            margin-bottom: 0.5rem; /* Add space between users */
        }
        .active-user-item:hover {
            background-color: #f1f1f1; /* Subtle hover effect */
        }
        .active-user-avatar {
            width: 40px;
            height: 40px;
            background-color: var(--accent-color); /* Use accent color */
            color: white;
            font-size: 1.1rem; /* Adjust icon size */
        }
         .active-user-item small {
            font-size: 0.8em;
            color: var(--success-color); /* Green for online */
            font-weight: 500;
        }

    </style>
</head>
<body>
    <div class="page-header text-center w-100">
        <div class="container">
            <h1 class="page-title display-4 fw-bold">Lobby UKS</h1>
            <p class="page-description lead">Tempat berkumpul, berdiskusi, dan berbagi informasi seputar UKS.</p>
        </div>
    </div>

    <div class="container content-section">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i> <?php echo htmlspecialchars($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i> <?php echo htmlspecialchars($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <div class="row">
            <div class="col-lg-8 mb-4 mb-lg-0">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-comments"></i> Diskusi Terbaru
                    </div>
                    <div class="card-body">
                        <?php if (mysqli_num_rows($discussions_result) > 0): ?>
                            <?php while ($discussion = mysqli_fetch_assoc($discussions_result)): ?>
                                <div class="lobby-item">
                                    <div class="lobby-icon">
                                        <?php
                                        // Display icon based on category
                                        switch ($discussion['category']) {
                                            case 'health_tips': echo '<i class="fas fa-heartbeat"></i>'; break; // Changed icon
                                            case 'schedule': echo '<i class="fas fa-calendar-alt"></i>'; break; // Changed icon
                                            case 'qa': echo '<i class="fas fa-question-circle"></i>'; break;
                                            default: echo '<i class="fas fa-comment-dots"></i>'; break; // Default icon
                                        }
                                        ?>
                                    </div>
                                    <div class="lobby-content">
                                        <h5 class="lobby-title"><a href="discussion.php?id=<?php echo $discussion['id']; ?>"><?php echo htmlspecialchars($discussion['title']); ?></a></h5>
                                        <p class="lobby-description"><?php echo nl2br(htmlspecialchars(substr($discussion['content'], 0, 150))); ?><?php echo strlen($discussion['content']) > 150 ? '...' : ''; ?></p> <small class="lobby-meta text-muted">
                                            <i class="fas fa-user me-1"></i> <?php echo htmlspecialchars($discussion['username']); ?> • 
                                            <i class="fas fa-clock ms-2 me-1"></i> <?php echo date('d M Y, H:i', strtotime($discussion['created_at'])); ?> •
                                            <span class="badge bg-secondary ms-2"><?php 
                                                switch ($discussion['category']) {
                                                    case 'health_tips': echo 'Tips Kesehatan'; break;
                                                    case 'schedule': echo 'Jadwal'; break;
                                                    case 'qa': echo 'Tanya Jawab'; break;
                                                    default: echo htmlspecialchars(ucfirst($discussion['category'])); break;
                                                }
                                            ?></span>
                                        </small>
                                    </div>
                                    <div class="lobby-action">
                                        <a href="discussion.php?id=<?php echo $discussion['id']; ?>" class="btn btn-primary btn-sm">Lihat</a>
                                        <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
                                            <button type="button" class="btn btn-danger btn-sm ms-2 delete-discussion-btn"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#deleteConfirmModal"
                                                    data-discussion-id="<?php echo $discussion['id']; ?>"
                                                    data-discussion-title="<?php echo htmlspecialchars($discussion['title']); ?>">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p class="text-center text-muted">Belum ada diskusi. Mulai diskusi baru!</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-info-circle"></i> Informasi Lobby
                    </div>
                    <div class="card-body">
                        <p>Selamat datang di Lobby UKS! Gunakan ruang ini untuk:</p>
                        <ul class="list-unstyled">
                            <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i>Berdiskusi seputar kesehatan.</li>
                            <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i>Mendapatkan informasi terbaru.</li>
                            <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i>Bertanya kepada petugas UKS.</li>
                            <li><i class="fas fa-check-circle text-success me-2"></i>Berbagi pengalaman Anda.</li>
                        </ul>
                        <hr>
                        <button class="btn btn-create w-100 mt-2" data-bs-toggle="modal" data-bs-target="#createDiscussionModal">
                            <i class="fas fa-plus me-2"></i> Buat Diskusi Baru
                        </button>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-users"></i> Pengguna Aktif (<?php echo $active_users_count; ?>)
                    </div>
                    <div class="card-body">
                        <?php if ($active_users_count > 0): ?>
                            <?php foreach ($active_users as $active_user): ?>
                                <div class="d-flex align-items-center mb-3 active-user-item">
                                    <div class="rounded-circle active-user-avatar d-flex align-items-center justify-content-center">
                                        <i class="fas fa-user"></i>
                                    </div>
                                    <div class="ms-3">
                                        <h6 class="mb-0 fw-bold"><?php echo htmlspecialchars($active_user['username']); ?></h6>
                                        <small class="text-success">Online</small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-muted text-center mb-0 fst-italic">Tidak ada pengguna yang aktif saat ini.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="createDiscussionModal" tabindex="-1" aria-labelledby="createDiscussionModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg"> <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createDiscussionModalLabel">Buat Diskusi Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning mb-4" role="alert"> <h6 class="alert-heading fw-bold"><i class="fas fa-exclamation-circle me-2"></i>Peraturan Diskusi</h6>
                        <p class="mb-0 small">Mohon patuhi peraturan berikut saat membuat atau berpartisipasi dalam diskusi:</p>
                        <ul class="mb-0 mt-2 small">
                            <li>Gunakan bahasa Indonesia yang baik, sopan, dan santun.</li>
                            <li>Dilarang menggunakan kata-kata kasar, SARA, atau pornografi.</li>
                            <li>Jaga etika berkomunikasi dan hargai pendapat pengguna lain.</li>
                            <li>Fokus pada topik yang relevan dengan kesehatan dan UKS.</li>
                            <li>Pelanggaran dapat mengakibatkan penghapusan diskusi/komentar atau sanksi lainnya.</li>
                        </ul>
                    </div>
                    <form method="POST" action="lobby.php" id="createDiscussionForm">
                        <div class="mb-3">
                            <label for="title" class="form-label fw-bold">Judul Diskusi <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="title" name="title" required placeholder="Contoh: Cara mengatasi sakit kepala ringan">
                        </div>
                        <div class="mb-3">
                            <label for="category" class="form-label fw-bold">Kategori <span class="text-danger">*</span></label>
                            <select class="form-select" id="category" name="category" required>
                                <option value="" disabled selected>-- Pilih Kategori --</option>
                                <option value="health_tips">Tips Kesehatan</option>
                                <option value="schedule">Jadwal UKS</option>
                                <option value="qa">Tanya Jawab</option>
                                <option value="other">Lainnya</option> </select>
                        </div>
                        <div class="mb-3">
                            <label for="content" class="form-label fw-bold">Isi Diskusi <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="content" name="content" rows="5" required placeholder="Tuliskan isi diskusi Anda di sini..."></textarea>
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" value="" id="agree_terms" required>
                            <label class="form-check-label small" for="agree_terms">
                                Saya telah membaca dan setuju untuk mematuhi <strong class="text-danger">peraturan diskusi</strong> di atas.
                            </label>
                            <div class="invalid-feedback">Anda harus menyetujui peraturan diskusi.</div>
                        </div>
                        <div id="createFormError" class="alert alert-danger mt-3" style="display: none;"></div>
                    </form>
                </div>
                 <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="create_discussion" form="createDiscussionForm" class="btn btn-create">
                       <i class="fas fa-paper-plane me-2"></i> Buat Diskusi
                    </button>
                </div>
            </div>
        </div>
    </div>

    <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
    <div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteConfirmModalLabel"><i class="fas fa-exclamation-triangle text-warning me-2"></i> Konfirmasi Hapus</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Apakah Anda yakin ingin menghapus diskusi berjudul:</p>
                    <p><strong id="discussionTitleToDelete" class="text-danger"></strong>?</p>
                    <p class="text-danger fw-bold mt-3"><i class="fas fa-exclamation-circle me-1"></i> Tindakan ini bersifat permanen dan tidak dapat dibatalkan.</p>
                    <ul class="text-danger small">
                        <li>Semua komentar terkait diskusi ini juga akan dihapus.</li>
                        <li>Data yang dihapus tidak dapat dipulihkan kembali.</li>
                    </ul>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <form method="POST" action="lobby.php" id="deleteDiscussionForm">
                        <input type="hidden" name="discussion_id" id="discussionIdToDelete" value="">
                        <input type="hidden" name="delete_discussion" value="1">
                        <button type="submit" class="btn btn-danger" id="confirmDeleteBtn">Ya, Hapus Diskusi</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>


    <?php if (!isset($_SESSION['lobby_agreed'])): ?>
    <div class="modal fade" id="lobbyAccessModal" tabindex="-1" aria-labelledby="lobbyAccessModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="lobbyAccessModalLabel"><i class="fas fa-shield-alt me-2"></i> Selamat Datang di Lobby UKS</h5>
                    </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <h6 class="alert-heading fw-bold"><i class="fas fa-info-circle me-2"></i>Penting Dibaca!</h6>
                        <p>Sebelum Anda mulai berinteraksi di Lobby UKS, mohon perhatikan dan setujui beberapa aturan dasar berikut untuk menjaga kenyamanan bersama:</p>
                        <ul>
                            <li>Gunakan bahasa Indonesia yang sopan, santun, dan mudah dimengerti.</li>
                            <li>Hindari penggunaan kata-kata kasar, tidak pantas, SARA, atau pornografi.</li>
                            <li>Jaga etika dalam berkomunikasi, hargai pendapat pengguna lain.</li>
                            <li>Fokus pada topik yang relevan dengan kesehatan, UKS, atau kegiatan sekolah terkait.</li>
                            <li>Dilarang menyebarkan informasi palsu (hoax) atau spam.</li>
                             <li>Hormati privasi pengguna lain.</li>
                        </ul>
                        <p class="mb-0">Dengan melanjutkan, Anda setuju untuk mematuhi aturan ini.</p>
                    </div>
                    <div class="form-check mt-3">
                        <input class="form-check-input" type="checkbox" id="agree_lobby_terms">
                        <label class="form-check-label fw-bold" for="agree_lobby_terms">
                            Saya telah membaca dan setuju untuk mematuhi aturan Lobby UKS.
                        </label>
                    </div>
                    <div id="agreementError" class="alert alert-danger mt-3 py-2" style="display: none;">
                        <i class="fas fa-exclamation-circle me-2"></i> Anda harus menyetujui aturan untuk melanjutkan.
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="dashboard.php" class="btn btn-secondary"><i class="fas fa-times me-2"></i>Keluar Lobby</a>
                    <button type="button" class="btn btn-primary" id="confirmLobbyAccess"><i class="fas fa-check me-2"></i>Setuju dan Lanjutkan</button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <a href="dashboard.php" class="btn btn-primary back-to-dashboard" title="Kembali ke Dashboard">
        <i class="fas fa-arrow-left me-2"></i> Kembali
    </a>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Show lobby access modal only if it exists and user hasn't agreed yet
            const lobbyAccessModalElement = document.getElementById('lobbyAccessModal');
            <?php if (!isset($_SESSION['lobby_agreed'])): ?>
            if (lobbyAccessModalElement) {
                const lobbyAccessModal = new bootstrap.Modal(lobbyAccessModalElement, {
                    backdrop: 'static',
                    keyboard: false
                });
                lobbyAccessModal.show();
            }
            <?php endif; ?>

            // Handle lobby access confirmation
            const confirmButton = document.getElementById('confirmLobbyAccess');
            if (confirmButton) {
                confirmButton.addEventListener('click', function() {
                    const agreeCheckbox = document.getElementById('agree_lobby_terms');
                    const errorDiv = document.getElementById('agreementError');
                    const modalElement = document.getElementById('lobbyAccessModal'); // Get modal element again
                    
                    if (agreeCheckbox.checked) {
                        // Option 1: Hide modal using Bootstrap JS instance
                        const modalInstance = bootstrap.Modal.getInstance(modalElement);
                        if (modalInstance) {
                             modalInstance.hide();
                        }
                       
                        // Option 2: Send signal to server to remember agreement (AJAX recommended)
                        // For simplicity here, we'll just hide. You'd need AJAX to set the session.
                        // Example: fetch('set_lobby_agreed.php'); 
                         
                        <?php $_SESSION['lobby_agreed'] = true; // Set session directly (works on next page load) ?>

                        errorDiv.style.display = 'none';

                    } else {
                        errorDiv.style.display = 'block';
                        errorDiv.classList.add('shake'); // Add shake to error message
                         agreeCheckbox.classList.add('shake'); // Shake checkbox too
                        setTimeout(() => {
                            errorDiv.classList.remove('shake');
                             agreeCheckbox.classList.remove('shake');
                        }, 500);
                    }
                });
            }

            // Handle discussion creation form validation
            const createForm = document.getElementById('createDiscussionForm');
            if(createForm) {
                 const submitCreateButton = document.querySelector('button[form="createDiscussionForm"]'); // Get the submit button linked to the form
                 submitCreateButton.addEventListener('click', function(e) { // Listen on the button click instead of form submit directly
                     const agreeCheckbox = document.getElementById('agree_terms');
                     const errorDiv = document.getElementById('createFormError'); // Assuming you have an error div
                     
                     // Reset previous validation state
                     agreeCheckbox.classList.remove('is-invalid');
                     agreeCheckbox.closest('.form-check').querySelector('.invalid-feedback').style.display = 'none';
                     if(errorDiv) errorDiv.style.display = 'none';

                     // Perform basic check (Bootstrap 5 validation handles others if form tag includes novalidate)
                     if (!agreeCheckbox.checked) {
                         e.preventDefault(); // Prevent form submission
                         agreeCheckbox.classList.add('is-invalid'); // Add Bootstrap invalid class
                         agreeCheckbox.closest('.form-check').querySelector('.invalid-feedback').style.display = 'block'; // Show feedback
                         agreeCheckbox.closest('.form-check').classList.add('shake');
                         setTimeout(() => {
                            agreeCheckbox.closest('.form-check').classList.remove('shake');
                         }, 500);
                         // Optionally show a general error message
                         // if(errorDiv) {
                         //    errorDiv.textContent = 'Anda harus menyetujui peraturan diskusi.';
                         //    errorDiv.style.display = 'block';
                         // }
                     } else {
                         // If validation passes, allow the form to submit (or handle via AJAX)
                         // If you let the form submit normally, ensure the button type="submit" and name="create_discussion" are correct
                     }
                 });
            }


            // Handle delete discussion form submission
            const deleteForm = document.getElementById('deleteDiscussionForm');
            if (deleteForm) {
                deleteForm.addEventListener('submit', function(e) {
                    // Disable the submit button to prevent double submission
                    const submitBtn = document.getElementById('confirmDeleteBtn');
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Menghapus...';
                    
                    // Let the form submit normally
                    return true;
                });
            }

            // Handle setting data for the delete confirmation modal
            const deleteConfirmModalElement = document.getElementById('deleteConfirmModal');
            if (deleteConfirmModalElement) {
                deleteConfirmModalElement.addEventListener('show.bs.modal', function (event) {
                    const button = event.relatedTarget;
                    const discussionId = button.getAttribute('data-discussion-id');
                    const discussionTitle = button.getAttribute('data-discussion-title');
                    
                    const modalTitleSpan = deleteConfirmModalElement.querySelector('#discussionTitleToDelete');
                    const modalInputId = deleteConfirmModalElement.querySelector('#discussionIdToDelete');
                    
                    if (modalTitleSpan) modalTitleSpan.textContent = discussionTitle;
                    if (modalInputId) modalInputId.value = discussionId;
                });

                // Reset form when modal is hidden
                deleteConfirmModalElement.addEventListener('hidden.bs.modal', function () {
                    const submitBtn = document.getElementById('confirmDeleteBtn');
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = 'Ya, Hapus Diskusi';
                    }
                });
            }

            // Optional: Auto-dismiss alerts after a few seconds
            const autoDismissAlerts = document.querySelectorAll('.alert-success, .alert-danger');
             if (autoDismissAlerts.length > 0) {
                setTimeout(() => {
                    autoDismissAlerts.forEach(alert => {
                        const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
                        if (bsAlert) {
                            bsAlert.close();
                        }
                    });
                }, 5000); // Dismiss after 5 seconds
            }

        });
    </script>
</body>
</html>