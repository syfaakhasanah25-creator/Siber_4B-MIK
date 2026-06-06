<?php
require_once 'functions.php';
requireLogin();

$search  = trim($_GET['search'] ?? '');
$filter  = $_GET['status'] ?? '';
$allowed = ['', 'active', 'completed', 'cancelled'];
if (!in_array($filter, $allowed)) $filter = '';

$bookings = getAllBookings($pdo, $search, $filter);

$counts = ['active'=>0,'completed'=>0,'cancelled'=>0];
foreach ($bookings as $b) {
    if (isset($counts[$b['status']])) $counts[$b['status']]++;
}

$statusLabel = [
    'active'    => 'Aktif',
    'completed' => 'Selesai',
    'cancelled' => 'Dibatalkan',
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Pasien - SIBER</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <?php include 'partials/navbar.php'; ?>

    <div class="page-wrapper">
        <div class="page-header">
            <div>
                <h2>Data Pasien</h2>
                <p>Riwayat seluruh booking tempat tidur</p>
            </div>
        </div>

        <!-- Summary pills -->
        <div class="pasien-summary">
            <a href="pasien.php<?= $search ? '?search='.urlencode($search) : '' ?>" class="summary-pill <?= $filter==='' ? 'active' : '' ?>">
                Semua <span><?= count($bookings) ?></span>
            </a>
            <a href="pasien.php?status=active<?= $search ? '&search='.urlencode($search) : '' ?>" class="summary-pill pill-active <?= $filter==='active' ? 'active' : '' ?>">
                Aktif <span><?= $counts['active'] ?></span>
            </a>
            <a href="pasien.php?status=completed<?= $search ? '&search='.urlencode($search) : '' ?>" class="summary-pill pill-completed <?= $filter==='completed' ? 'active' : '' ?>">
                Selesai <span><?= $counts['completed'] ?></span>
            </a>
            <a href="pasien.php?status=cancelled<?= $search ? '&search='.urlencode($search) : '' ?>" class="summary-pill pill-cancelled <?= $filter==='cancelled' ? 'active' : '' ?>">
                Dibatalkan <span><?= $counts['cancelled'] ?></span>
            </a>
        </div>

        <div class="card">
            <div class="card-header">
                <h3>
                    <?= $filter ? $statusLabel[$filter] : 'Semua Pasien' ?>
                    <span class="text-muted" style="font-weight:400;font-size:0.8rem">(<?= count($bookings) ?> data)</span>
                </h3>
                <form method="GET" class="search-form">
                    <?php if ($filter): ?>
                    <input type="hidden" name="status" value="<?= htmlspecialchars($filter) ?>">
                    <?php endif; ?>
                    <div class="search-wrap">
                        <input type="text" name="search" placeholder="Cari No. RM, nama, bed, ruangan..." value="<?= htmlspecialchars($search) ?>">
                        <button type="submit" class="btn btn-primary btn-sm">Cari</button>
                        <?php if ($search): ?>
                        <a href="pasien.php<?= $filter ? '?status='.$filter : '' ?>" class="btn btn-outline btn-sm">Reset</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>No. RM</th>
                            <th>Nama Pasien</th>
                            <th>Bed</th>
                            <th>Ruangan</th>
                            <th>Kelas</th>
                            <th>Check-in</th>
                            <th>Check-out</th>
                            <th>Lama Rawat</th>
                            <th>Petugas</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($bookings)): ?>
                        <tr><td colspan="12" class="text-center text-muted" style="padding:2rem">Tidak ada data ditemukan</td></tr>
                        <?php endif; ?>
                        <?php foreach ($bookings as $i => $bk):
                            $checkin  = new DateTime($bk['check_in_date']);
                            $checkout = $bk['check_out_date'] ? new DateTime($bk['check_out_date']) : new DateTime();
                            $diff     = $checkin->diff($checkout);
                            $lama     = $diff->days > 0 ? $diff->days . ' hari' : 'Hari ini';
                        ?>
                        <tr>
                            <td class="text-muted"><?= $i + 1 ?></td>
                            <td><span class="rm-badge"><?= htmlspecialchars($bk['no_rm'] ?? '-') ?></span></td>
                            <td><strong><?= htmlspecialchars($bk['patient_name']) ?></strong></td>
                            <td><?= htmlspecialchars($bk['bed_code']) ?></td>
                            <td><?= htmlspecialchars($bk['room_name']) ?></td>
                            <td><span class="room-type-badge type-<?= strtolower(str_replace(' ','',$bk['room_type'])) ?>"><?= $bk['room_type'] ?></span></td>
                            <td><?= $bk['check_in_date'] ?></td>
                            <td><?= $bk['check_out_date'] ?? '<span class="text-muted">-</span>' ?></td>
                            <td><?= $bk['status'] === 'active' ? '<span class="text-muted">'.$lama.'*</span>' : $lama ?></td>
                            <td class="text-muted"><?= htmlspecialchars($bk['petugas']) ?></td>
                            <td><span class="badge badge-<?= $bk['status'] ?>"><?= $statusLabel[$bk['status']] ?? $bk['status'] ?></span></td>
                            <td>
                                <button class="btn btn-sm btn-outline" onclick="openEditPasien(<?= $bk['id'] ?>, '<?= htmlspecialchars($bk['no_rm'] ?? '', ENT_QUOTES) ?>', '<?= htmlspecialchars($bk['patient_name'], ENT_QUOTES) ?>')">Edit</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <p class="text-muted" style="font-size:0.7rem;margin-top:0.5rem">* Lama rawat pasien aktif dihitung hingga hari ini</p>
    </div>

    <?php include 'partials/footer.php'; ?>

    <!-- Edit Pasien Modal -->
    <div class="modal-overlay" id="editPasienOverlay" onclick="closeEditPasien()"></div>
    <div class="modal" id="editPasienModal">
        <div class="modal-header">
            <h3>Edit Data Pasien</h3>
            <button class="modal-close" onclick="closeEditPasien()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="editPasienForm">
                <input type="hidden" id="editBookingId" name="booking_id">
                <input type="hidden" name="action" value="edit_pasien">
                <div class="form-group">
                    <label for="editNoRm">Nomor RM <span class="optional">(6 digit)</span></label>
                    <input type="text" id="editNoRm" name="no_rm" placeholder="Contoh: 100001" maxlength="6" pattern="[0-9]{6}">
                </div>
                <div class="form-group">
                    <label for="editPatientName">Nama Pasien <span class="required">*</span></label>
                    <input type="text" id="editPatientName" name="patient_name" placeholder="Nama lengkap pasien" required>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-outline" onclick="closeEditPasien()">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <script src="assets/app.js"></script>
    <script>
        function openEditPasien(id, noRm, name) {
            document.getElementById('editBookingId').value = id;
            document.getElementById('editNoRm').value = noRm;
            document.getElementById('editPatientName').value = name;
            document.getElementById('editPasienOverlay').classList.add('open');
            document.getElementById('editPasienModal').classList.add('open');
        }
        function closeEditPasien() {
            document.getElementById('editPasienOverlay').classList.remove('open');
            document.getElementById('editPasienModal').classList.remove('open');
        }
        document.getElementById('editPasienForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const noRm = document.getElementById('editNoRm').value;
            if (noRm && !/^\d{6}$/.test(noRm)) {
                showToast('Nomor RM harus 6 digit angka', 'error'); return;
            }
            fetch('api.php', { method: 'POST', body: new FormData(this) })
                .then(r => r.json())
                .then(data => {
                    if (data.error) { showToast(data.error, 'error'); return; }
                    closeEditPasien();
                    showToast('Data pasien diperbarui', 'success');
                    setTimeout(() => location.reload(), 800);
                });
        });
    </script>
</body>
</html>
