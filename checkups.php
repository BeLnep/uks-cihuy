<?php
require_once 'includes/header.php';
?>

<div class="card fade-in">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Data Pemeriksaan</h5>
        <button class="btn btn-primary scale-in" data-bs-toggle="modal" data-bs-target="#addCheckupModal">
            <i class="fas fa-plus me-1"></i> Tambah Pemeriksaan
        </button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>NIS</th>
                        <th>Nama Siswa</th>
                        <th>Keluhan</th>
                        <th>Diagnosis</th>
                        <th>Tindakan</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($checkups as $checkup): ?>
                    <tr class="slide-up">
                        <td><?php echo date('d/m/Y', strtotime($checkup['checkup_date'])); ?></td>
                        <td><?php echo $checkup['nis']; ?></td>
                        <td><?php echo $checkup['student_name']; ?></td>
                        <td><?php echo $checkup['complaint']; ?></td>
                        <td><?php echo $checkup['diagnosis']; ?></td>
                        <td><?php echo $checkup['action']; ?></td>
                        <td>
                            <span class="badge bg-<?php echo $checkup['status'] == 'Selesai' ? 'success' : 'warning'; ?>">
                                <?php echo $checkup['status']; ?>
                            </span>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-info scale-in" onclick="viewCheckup(<?php echo $checkup['id']; ?>)">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-warning scale-in" onclick="editCheckup(<?php echo $checkup['id']; ?>)">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-danger scale-in" onclick="deleteCheckup(<?php echo $checkup['id']; ?>)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Checkup Modal -->
<div class="modal fade" id="addCheckupModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content scale-in">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Pemeriksaan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addCheckupForm">
                    <div class="mb-3">
                        <label class="form-label">Tanggal</label>
                        <input type="date" class="form-control" name="checkup_date" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Siswa</label>
                        <select class="form-control" name="student_id" required>
                            <option value="">Pilih Siswa</option>
                            <?php foreach ($students as $student): ?>
                            <option value="<?php echo $student['id']; ?>">
                                <?php echo $student['nis'] . ' - ' . $student['name']; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Keluhan</label>
                        <textarea class="form-control" name="complaint" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Diagnosis</label>
                        <textarea class="form-control" name="diagnosis" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tindakan</label>
                        <textarea class="form-control" name="action" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select class="form-control" name="status" required>
                            <option value="Proses">Proses</option>
                            <option value="Selesai">Selesai</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" onclick="saveCheckup()">Simpan</button>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 