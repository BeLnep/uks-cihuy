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

// Get recent health news
$stmt = $pdo->query("
    SELECT hn.*, u.username as author_name 
    FROM health_news hn 
    JOIN users u ON hn.created_by = u.id 
    ORDER BY hn.created_at DESC 
    LIMIT 6
");
$news = $stmt->fetchAll();

// Get recent health records with student information
$stmt = $pdo->query("
    SELECT hr.*, s.name as student_name, s.class
    FROM health_records hr 
    JOIN students s ON hr.student_id = s.id 
    ORDER BY hr.created_at DESC 
    LIMIT 10
");
$health_records = $stmt->fetchAll();

// Get daily health records for the current week
$stmt = $pdo->query("
    SELECT 
        DAYNAME(created_at) as day,
        COUNT(*) as count
    FROM health_records
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY DAYNAME(created_at)
    ORDER BY FIELD(DAYNAME(created_at), 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday')
");
$daily_records = $stmt->fetchAll();
?>

<h2>Home</h2>

<?php if (isset($success)): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>

<?php if (isset($error)): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<!-- Health News Section -->
<div class="row mt-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="mb-0">Tips dan Berita Kesehatan</h4>
                <?php if ($_SESSION['role'] == 'admin'): ?>
                <a href="add_news.php" class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i> Tambah Berita
                </a>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if (empty($news)): ?>
                    <p class="text-muted">Belum ada berita kesehatan.</p>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($news as $item): ?>
                        <div class="col-md-4 mb-4">
                            <div class="card h-100">
                                <?php if ($item['image_url']): ?>
                                <img src="<?php echo htmlspecialchars($item['image_url']); ?>" class="card-img-top" alt="News Image" style="height: 200px; object-fit: cover;">
                                <?php endif; ?>
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($item['title']); ?></h5>
                                    <p class="card-text"><?php echo substr(htmlspecialchars($item['content']), 0, 100) . '...'; ?></p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">Oleh: <?php echo htmlspecialchars($item['author_name']); ?></small>
                                        <small class="text-muted"><?php echo date('d/m/Y', strtotime($item['created_at'])); ?></small>
                                    </div>
                                    <?php if ($_SESSION['role'] == 'admin'): ?>
                                    <div class="mt-3">
                                        <a href="edit_news.php?id=<?php echo $item['id']; ?>" class="btn btn-sm btn-warning">
                                            <i class="fas fa-edit me-1"></i> Edit
                                        </a>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="news_id" value="<?php echo $item['id']; ?>">
                                            <button type="submit" name="delete_news" class="btn btn-sm btn-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus berita ini?')">
                                                <i class="fas fa-trash me-1"></i> Hapus
                                            </button>
                                        </form>
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
</div>

<!-- Health Records Chart Section -->
<div class="row mt-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0">Grafik Jumlah Siswa Sakit</h4>
            </div>
            <div class="card-body">
                <canvas id="dailyChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Recent Health Records Section -->
<div class="row mt-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0">Riwayat Siswa Sakit Terbaru</h4>
            </div>
            <div class="card-body">
                <?php if (empty($health_records)): ?>
                    <p class="text-muted">Belum ada riwayat kesehatan.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Nama Siswa</th>
                                    <th>Kelas</th>
                                    <th>Keluhan</th>
                                    <th>Diagnosa</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($health_records as $record): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y H:i', strtotime($record['created_at'])); ?></td>
                                    <td><?php echo htmlspecialchars($record['student_name']); ?></td>
                                    <td><?php echo htmlspecialchars($record['class']); ?></td>
                                    <td><?php echo htmlspecialchars($record['complaint']); ?></td>
                                    <td><?php echo htmlspecialchars($record['diagnosis']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $record['status'] == 'pending' ? 'warning' : 
                                                ($record['status'] == 'treated' ? 'success' : 'danger'); 
                                        ?>">
                                            <?php echo ucfirst($record['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Daily Chart
const dailyCtx = document.getElementById('dailyChart').getContext('2d');
const dailyChart = new Chart(dailyCtx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode(array_column($daily_records, 'day')); ?>,
        datasets: [{
            label: 'Jumlah Siswa Sakit',
            data: <?php echo json_encode(array_column($daily_records, 'count')); ?>,
            backgroundColor: 'rgba(220, 53, 69, 0.2)',
            borderColor: 'rgba(220, 53, 69, 1)',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        },
        plugins: {
            title: {
                display: true,
                text: 'Jumlah Siswa Sakit per Hari (Senin-Jumat)'
            }
        }
    }
});
</script>

<?php require_once 'footer.php'; ?> 