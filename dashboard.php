<?php
require_once 'header.php';
require_once 'config/database.php';

// Handle news deletion
if (isset($_POST['delete_news']) && ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'teacher')) {
    $news_id = mysqli_real_escape_string($conn, $_POST['news_id']);
    $query = "DELETE FROM health_news WHERE id = '$news_id'";
    if (mysqli_query($conn, $query)) {
        $success = "Berita berhasil dihapus";
    } else {
        $error = "Gagal menghapus berita";
    }
}

// Get total students
$query = "SELECT COUNT(*) as total FROM students";
$result = mysqli_query($conn, $query);
$total_students = mysqli_fetch_assoc($result)['total'];

// Get total health records
$query = "SELECT COUNT(*) as total FROM health_records";
$result = mysqli_query($conn, $query);
$total_records = mysqli_fetch_assoc($result)['total'];

// Get recent health records
$query = "SELECT hr.*, s.name as student_name, s.class 
          FROM health_records hr 
          JOIN students s ON hr.student_id = s.id 
          ORDER BY hr.created_at DESC 
          LIMIT 5";
$recent_records = mysqli_query($conn, $query);

// Get recent health news
$query = "SELECT hn.*, u.username as author_name 
    FROM health_news hn 
    JOIN users u ON hn.created_by = u.id 
    ORDER BY hn.created_at DESC 
          LIMIT 6";
$news_result = mysqli_query($conn, $query);
$news = [];
while ($row = mysqli_fetch_assoc($news_result)) {
    $news[] = $row;
}

// Get daily health records for the current week
$query = "SELECT 
    DATE(created_at) as date,
    COUNT(*) as count
FROM health_records
WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
GROUP BY DATE(created_at)
ORDER BY date ASC";
$daily_result = mysqli_query($conn, $query);
$daily_records = [];
while ($row = mysqli_fetch_assoc($daily_result)) {
    $daily_records[] = $row;
}

// Prepare data for the chart
$dates = [];
$counts = [];
foreach ($daily_records as $record) {
    $dates[] = date('d/m', strtotime($record['date']));
    $counts[] = $record['count'];
}
?>

<style>
    :root {
        --primary-color: #dc3545;
        --secondary-color: #c82333;
        --accent-color: #e4606d;
        --success-color: #28a745;
        --warning-color: #ffc107;
        --danger-color: #e74c3c;
        --light-bg: #f8f9fa;
        --dark-text: #212529;
        --card-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        --hover-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
        --gradient-primary: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        --gradient-accent: linear-gradient(135deg, var(--accent-color), var(--secondary-color));
    }

    body {
        background-color: #f0f2f5;
        color: var(--dark-text);
        font-family: 'Poppins', sans-serif;
        min-height: 100vh;
    }

    .content-section {
        opacity: 0;
        animation: fadeIn 1s ease-in forwards;
        padding: 20px;
    }

    .container {
        padding: 20px;
    }

    /* Efek mengambang untuk semua card */
    .card, .stat-card {
        border: none;
        border-radius: 20px;
        background: white;
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.08);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        transform: translateY(0);
        margin-bottom: 1.5rem;
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.18);
    }

    .card:hover, .stat-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 15px 30px rgba(0, 0, 0, 0.12);
    }

    /* Efek mengambang untuk stat cards */
    .stat-card {
        padding: 2rem;
        position: relative;
        overflow: hidden;
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.9), rgba(255, 255, 255, 0.7));
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.18);
    }

    .stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: var(--gradient-primary);
        opacity: 0.03;
        transition: opacity 0.3s ease;
    }

    .stat-card:hover::before {
        opacity: 0.05;
    }

    /* Efek mengambang untuk news cards */
    .news-card {
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.9), rgba(255, 255, 255, 0.8));
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.18);
    }

    .news-card:hover {
        transform: translateY(-10px) scale(1.02);
    }

    /* Efek untuk table */
    .table-responsive {
        background: white;
        border-radius: 15px;
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.06);
    }

    .table thead th {
        background: rgba(220, 53, 69, 0.05);
        border: none;
    }

    .table tbody tr {
        transition: all 0.3s ease;
    }

    .table tbody tr:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        background-color: rgba(220, 53, 69, 0.02);
    }

    /* Efek untuk chart */
    .chart-container {
        background: white;
        border-radius: 20px;
        padding: 20px;
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.08);
        transition: all 0.3s ease;
    }

    .chart-container:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 30px rgba(0, 0, 0, 0.12);
    }

    /* Animasi fade in untuk konten */
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

    /* Efek blur untuk background */
    .page-transition {
        position: relative;
    }

    .page-transition::before {
        content: '';
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        backdrop-filter: blur(10px);
        z-index: -1;
    }

    /* Animasi untuk alert */
    .alert {
        animation: slideIn 0.5s ease-out;
        border: none;
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.95), rgba(255, 255, 255, 0.9));
        backdrop-filter: blur(10px);
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
    }

    @keyframes slideIn {
        from {
            transform: translateY(-100%);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }

    .stat-icon {
        font-size: 3rem;
        margin-bottom: 1.5rem;
        color: var(--primary-color);
        transition: all 0.5s ease;
        position: relative;
        z-index: 1;
    }

    .stat-card:hover .stat-icon {
        transform: scale(1.1);
        color: var(--secondary-color);
    }

    .stat-value {
        font-size: 2.5rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
        color: var(--primary-color);
        position: relative;
        z-index: 1;
    }

    .stat-label {
        font-size: 1.1rem;
        color: #6c757d;
        position: relative;
        z-index: 1;
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

    .table {
        margin-bottom: 0;
    }

    .table th {
        border-top: none;
        font-weight: 600;
        color: var(--primary-color);
        padding: 1rem;
        background-color: rgba(220, 53, 69, 0.05);
    }

    .table td {
        padding: 1rem;
        vertical-align: middle;
    }

    .badge {
        padding: 0.5rem 1rem;
        border-radius: 50px;
        font-weight: 500;
        transition: all 0.3s ease;
    }

    .news-img {
        height: 200px;
        object-fit: cover;
        border-radius: 20px 20px 0 0;
        transition: all 0.5s ease;
    }

    .news-card:hover .news-img {
        transform: scale(1.05);
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
        background: var(--gradient-primary);
        border: none;
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(220, 53, 69, 0.2);
    }

    .btn-warning {
        background: var(--warning-color);
        border: none;
        color: white;
    }

    .btn-danger {
        background: var(--danger-color);
        border: none;
    }

    .avatar-circle {
        width: 40px;
        height: 40px;
        background: var(--gradient-primary);
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        transition: all 0.3s ease;
    }

    .avatar-circle:hover {
        transform: scale(1.1);
    }

    /* Chart Styling */
    .chart-container {
        position: relative;
        margin: auto;
        height: 300px;
        width: 100%;
    }

    canvas {
        border-radius: 15px;
    }

    /* Tambahan CSS untuk modal delete */
    .modal-content {
        border: none;
        box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
    }

    .modal-header.bg-danger {
        background-color: #dc3545 !important;
    }

    .modal-header .btn-close-white {
        filter: brightness(0) invert(1);
    }

    .modal .btn {
        font-weight: 500;
        min-width: 100px;
    }

    .modal .btn-secondary {
        background-color: #6c757d;
        border: none;
    }

    .modal .btn-danger {
        background-color: #dc3545;
        border: none;
    }

    .modal-dialog-top {
        margin-top: 20px !important;
    }

    @media (min-width: 576px) {
        .modal-dialog-top {
            margin-top: 30px !important;
        }
    }

    #healthTipsBanner .carousel-item {
      opacity: 0;
      transition: opacity 0.7s cubic-bezier(0.4,0,0.2,1);
      position: absolute;
      width: 100%;
      left: 0;
      top: 0;
      z-index: 1;
      display: block;
    }
    #healthTipsBanner .carousel-item.active {
      opacity: 1;
      position: relative;
      z-index: 2;
    }
    #healthTipsBanner .carousel-inner {
      position: relative;
      min-height: 120px;
    }
</style>

<div class="container mt-4 page-transition">
<?php if (isset($success)): ?>
        <div class="alert alert-success alert-dismissible fade show fade-in" role="alert">
            <i class="fas fa-check-circle me-2"></i> <?php echo $success; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
<?php endif; ?>

<?php if (isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show fade-in" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
<?php endif; ?>

    <div id="healthTipsBanner" class="carousel slide carousel-fade mb-4" data-bs-ride="carousel" style="max-width: 100%; border-radius: 20px; overflow: hidden; box-shadow: 0 4px 16px rgba(0,0,0,0.08);">
      <div class="carousel-inner">
        <div class="carousel-item active">
          <div class="d-flex flex-column justify-content-center" style="background: linear-gradient(90deg, #e3ffe6 60%, #fff 100%); min-height: 120px; padding: 32px 40px 28px 40px; border-radius: 20px;">
            <h5 class="mb-2" style="color: #28a745; font-weight: 700;">Rutin Berolahraga</h5>
            <p class="mb-0" style="font-size: 1.1rem; color: #333;">Luangkan waktu minimal 30 menit setiap hari untuk berolahraga agar tubuh tetap bugar dan sehat.</p>
          </div>
        </div>
        <div class="carousel-item">
          <div class="d-flex flex-column justify-content-center" style="background: linear-gradient(90deg, #fffbe6 60%, #fff 100%); min-height: 120px; padding: 32px 40px 28px 40px; border-radius: 20px;">
            <h5 class="mb-2" style="color: #ffc107; font-weight: 700;">Konsumsi Makanan Sehat</h5>
            <p class="mb-0" style="font-size: 1.1rem; color: #333;">Perbanyak makan buah, sayur, dan makanan bergizi seimbang untuk menjaga daya tahan tubuh.</p>
          </div>
        </div>
        <div class="carousel-item">
          <div class="d-flex flex-column justify-content-center" style="background: linear-gradient(90deg, #e6f0ff 60%, #fff 100%); min-height: 120px; padding: 32px 40px 28px 40px; border-radius: 20px;">
            <h5 class="mb-2" style="color: #007bff; font-weight: 700;">Lengkapi Vaksinasi</h5>
            <p class="mb-0" style="font-size: 1.1rem; color: #333;">Pastikan vaksinasi Anda dan keluarga lengkap untuk perlindungan optimal dari penyakit menular.</p>
          </div>
        </div>
      </div>
      <button class="carousel-control-prev" type="button" data-bs-target="#healthTipsBanner" data-bs-slide="prev">
        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Previous</span>
      </button>
      <button class="carousel-control-next" type="button" data-bs-target="#healthTipsBanner" data-bs-slide="next">
        <span class="carousel-control-next-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Next</span>
      </button>
    </div>

    <div class="row mb-4">
        <div class="col-md-4 mb-4">
            <div class="stat-card">
                <div class="d-flex align-items-center">
                    <div class="stat-icon me-3">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <div>
                        <div class="stat-value"><?php echo $total_students; ?></div>
                        <div class="stat-label">Total Siswa</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="stat-card">
                <div class="d-flex align-items-center">
                    <div class="stat-icon me-3">
                        <i class="fas fa-notes-medical"></i>
                    </div>
                    <div>
                        <div class="stat-value"><?php echo $total_records; ?></div>
                        <div class="stat-label">Total Pemeriksaan</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="stat-card">
                <div class="d-flex align-items-center">
                    <div class="stat-icon me-3">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div>
                        <div class="stat-value"><?php echo count($daily_records); ?></div>
                        <div class="stat-label">Pemeriksaan Minggu Ini</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i> Grafik Pemeriksaan</h5>
                </div>
                <div class="card-body">
                    <canvas id="dailyChart" height="300"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-list-alt me-2"></i> Pemeriksaan Terbaru</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Nama</th>
                                    <th>Kelas</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($record = mysqli_fetch_assoc($recent_records)): ?>
                                <tr>
                                    <td><?= date('d/m/Y', strtotime($record['created_at'])) ?></td>
                                    <td><?= htmlspecialchars($record['student_name']) ?></td>
                                    <td><?= htmlspecialchars($record['class']) ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-newspaper me-2"></i> Tips dan Berita Kesehatan</h5>
            <?php if ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'teacher'): ?>
            <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#addNewsModal">
                <i class="fas fa-plus me-1"></i> Tambah Berita
            </button>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <?php if (empty($news)): ?>
            <div class="text-center py-5">
                <i class="fas fa-newspaper fa-3x text-muted mb-3"></i>
                <p class="text-muted">Belum ada berita kesehatan.</p>
            </div>
            <?php else: ?>
            <div class="row">
                <?php foreach ($news as $item): ?>
                <div class="col-md-4 mb-4">
                    <div class="card news-card h-100">
                        <?php if ($item['image_url']): ?>
                        <img src="<?php echo htmlspecialchars($item['image_url']); ?>" class="card-img-top news-img" alt="News Image">
                        <?php else: ?>
                        <div class="card-img-top news-img d-flex align-items-center justify-content-center bg-light">
                            <i class="fas fa-newspaper fa-3x text-muted"></i>
                        </div>
                        <?php endif; ?>
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($item['title']); ?></h5>
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted">
                                    <i class="fas fa-user me-1"></i> <?php echo htmlspecialchars($item['author_name']); ?>
                                </small>
                                <small class="text-muted">
                                    <i class="fas fa-calendar me-1"></i> <?php echo date('d/m/Y', strtotime($item['created_at'])); ?>
                                </small>
                            </div>
                            <?php if ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'teacher'): ?>
                            <div class="mt-3">
                                <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editNewsModal<?php echo $item['id']; ?>">
                                    <i class="fas fa-edit me-1"></i> Edit
                                </button>
                                <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteNewsModal<?php echo $item['id']; ?>">
                                    <i class="fas fa-trash me-1"></i> Hapus
                                </button>
                            </div>
                            <?php else: ?>
                            <div class="mt-3">
                                <a href="article_detail.php?id=<?php echo $item['id']; ?>" class="btn btn-danger btn-sm">
                                    <i class="fas fa-book-reader me-1"></i> Baca Selengkapnya
                                </a>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Delete News Modals -->
<?php foreach ($news as $item): ?>
<div class="modal fade" id="deleteNewsModal<?php echo $item['id']; ?>" tabindex="-1">
    <div class="modal-dialog modal-dialog-top" style="margin-top: 20px;">
        <div class="modal-content" style="border-radius: 15px;">
            <div class="modal-header bg-danger text-white border-0">
                <h5 class="modal-title">Hapus Berita</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-4">
                <p class="mb-0">Apakah Anda yakin ingin menghapus berita berjudul <?php echo htmlspecialchars($item['title']); ?>?</p>
            </div>
            <div class="modal-footer border-0 justify-content-center gap-2">
                <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal" style="border-radius: 8px; padding: 8px 20px;">Batal</button>
                <form method="POST" action="dashboard.php" class="m-0">
                    <input type="hidden" name="news_id" value="<?php echo $item['id']; ?>">
                    <button type="submit" name="delete_news" class="btn btn-danger px-4" style="border-radius: 8px; padding: 8px 20px;">Hapus</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>

<!-- Add News Modal -->
<div class="modal fade" id="addNewsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Berita Kesehatan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="add_news.php" method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="title" class="form-label">Judul</label>
                        <input type="text" class="form-control" id="title" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label for="content" class="form-label">Konten</label>
                        <textarea class="form-control" id="content" name="content" rows="5" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="image_url" class="form-label">URL Gambar (opsional)</label>
                        <input type="url" class="form-control" id="image_url" name="image_url">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit News Modals -->
<?php foreach ($news as $item): ?>
<div class="modal fade" id="editNewsModal<?php echo $item['id']; ?>" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Berita Kesehatan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="edit_news.php">
                <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_title<?php echo $item['id']; ?>" class="form-label">Judul</label>
                        <input type="text" class="form-control" id="edit_title<?php echo $item['id']; ?>" name="title" value="<?php echo htmlspecialchars($item['title']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_content<?php echo $item['id']; ?>" class="form-label">Konten</label>
                        <textarea class="form-control" id="edit_content<?php echo $item['id']; ?>" name="content" rows="5" required><?php echo htmlspecialchars($item['content']); ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="edit_image_url<?php echo $item['id']; ?>" class="form-label">URL Gambar (opsional)</label>
                        <input type="url" class="form-control" id="edit_image_url<?php echo $item['id']; ?>" name="image_url" value="<?php echo htmlspecialchars($item['image_url']); ?>">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endforeach; ?>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Daily Chart
const dailyCtx = document.getElementById('dailyChart').getContext('2d');
const dailyChart = new Chart(dailyCtx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($dates); ?>,
        datasets: [{
            label: 'Jumlah Pemeriksaan',
            data: <?php echo json_encode($counts); ?>,
            backgroundColor: 'rgba(220, 53, 69, 0.2)',
            borderColor: 'rgba(220, 53, 69, 1)',
            borderWidth: 2,
            borderRadius: 10,
            barThickness: 40,
            hoverBackgroundColor: 'rgba(220, 53, 69, 0.4)'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1,
                    font: {
                        family: 'Poppins',
                        size: 12
                    }
                },
                grid: {
                    display: true,
                    color: 'rgba(0, 0, 0, 0.05)',
                    drawBorder: false
                }
            },
            x: {
                grid: {
                    display: false
                },
                ticks: {
                    font: {
                        family: 'Poppins',
                        size: 12
                    }
                }
            }
        },
        plugins: {
            legend: {
                display: false
            },
            title: {
                display: true,
                text: 'Jumlah Pemeriksaan per Hari (7 Hari Terakhir)',
                font: {
                    family: 'Poppins',
                    size: 16,
                    weight: 'bold'
                },
                color: '#2c3e50',
                padding: {
                    top: 10,
                    bottom: 20
                }
            }
        },
        animation: {
            duration: 2000,
            easing: 'easeInOutQuart'
        }
    }
});
</script>

<?php require_once 'footer.php'; ?> 