<?php
require_once 'functions.php';
requireLogin();

// ===== FILTER PERIODE =====
$preset    = $_GET['preset'] ?? '30';
$date_from = $_GET['date_from'] ?? '';
$date_to   = $_GET['date_to'] ?? '';

if ($preset === 'custom' && $date_from && $date_to) {
    $period_label = $date_from . ' s/d ' . $date_to;
    $period_type  = 'custom';
} elseif ($preset === 'all') {
    $period_label = 'Semua waktu';
    $period_type  = 'all';
    $date_from    = '';
    $date_to      = '';
} else {
    $days = in_array($preset, ['30','90']) ? (int)$preset : 30;
    $period_label = $days . ' hari terakhir';
    $period_type  = 'days';
    $date_from    = date('Y-m-d', strtotime("-{$days} days"));
    $date_to      = date('Y-m-d');
}

$stats    = getDashboardStats($pdo, $period_type, $date_from, $date_to);
$rooms    = getRoomStats($pdo);
$bookings = getRecentBookings($pdo, 50);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - SIBER</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <?php include 'partials/navbar.php'; ?>

    <div class="page-wrapper">
        <div class="page-header">
            <div>
                <h2>Dashboard</h2>
                <p>Ringkasan kondisi bed rumah sakit &mdash; periode <?= htmlspecialchars($period_label) ?></p>
            </div>
            <span class="badge-role"><?= strtoupper($_SESSION['role']) ?></span>
        </div>

        <!-- Filter Periode -->
        <form method="GET" class="period-filter-bar" id="periodForm">
            <div class="period-presets">
                <a href="?preset=30"  class="period-btn <?= $preset==='30'  ? 'active' : '' ?>">30 Hari</a>
                <span class="period-btn <?= $preset==='custom' ? 'active' : '' ?>" id="customToggle" style="cursor:pointer">Custom</span>
            </div>
            <div class="period-custom <?= $preset==='custom' ? 'open' : '' ?>" id="customRange">
                <input type="hidden" name="preset" value="custom">
                <label>Dari</label>
                <input type="date" name="date_from" value="<?= htmlspecialchars($date_from) ?>" required>
                <label>Sampai</label>
                <input type="date" name="date_to" value="<?= htmlspecialchars($date_to) ?>" required>
                <button type="submit" class="btn btn-primary btn-sm">Terapkan</button>
            </div>
        </form>

        <!-- KPI Cards -->
        <div class="kpi-grid">
            <div class="kpi-card">
                <div class="kpi-label">Total Bed</div>
                <div class="kpi-value"><?= $stats['total'] ?></div>
                <div class="kpi-sub">Seluruh ruangan</div>
            </div>
            <div class="kpi-card kpi-booked">
                <div class="kpi-label">Terisi</div>
                <div class="kpi-value"><?= $stats['booked'] ?></div>
                <div class="kpi-sub">Bed aktif digunakan</div>
            </div>
            <div class="kpi-card kpi-available">
                <div class="kpi-label">Tersedia</div>
                <div class="kpi-value"><?= $stats['available'] ?></div>
                <div class="kpi-sub">Siap digunakan</div>
            </div>
            <div class="kpi-card kpi-cleaning">
                <div class="kpi-label">Pembersihan</div>
                <div class="kpi-value"><?= $stats['cleaning'] ?></div>
                <div class="kpi-sub">Sedang dibersihkan</div>
            </div>
            <div class="kpi-card kpi-maintenance">
                <div class="kpi-label">Perbaikan</div>
                <div class="kpi-value"><?= $stats['maintenance'] ?></div>
                <div class="kpi-sub">Dalam perbaikan</div>
            </div>
        </div>

        <!-- Metrics BOR LOS TOI BTO -->
        <div class="metrics-grid">
            <div class="metric-card">
                <div class="metric-title">BOR</div>
                <div class="metric-subtitle">Bed Occupancy Rate</div>
                <div class="metric-value"><?= $stats['bor'] ?><span>%</span></div>
                <div class="metric-bar"><div class="metric-bar-fill" style="width:<?= min($stats['bor'],100) ?>%"></div></div>
                <div class="metric-desc">Tingkat pemakaian tempat tidur</div>
            </div>
            <div class="metric-card">
                <div class="metric-title">LOS</div>
                <div class="metric-subtitle">Length of Stay</div>
                <div class="metric-value"><?= $stats['los'] ?><span>hari</span></div>
                <div class="metric-bar"><div class="metric-bar-fill" style="width:<?= min($stats['los']*10,100) ?>%"></div></div>
                <div class="metric-desc">Rata-rata lama rawat pasien</div>
            </div>
            <div class="metric-card">
                <div class="metric-title">TOI</div>
                <div class="metric-subtitle">Turn Over Interval</div>
                <div class="metric-value"><?= $stats['toi'] ?><span>hari</span></div>
                <div class="metric-bar"><div class="metric-bar-fill" style="width:<?= min($stats['toi']*10,100) ?>%"></div></div>
                <div class="metric-desc">Rata-rata hari tempat tidur kosong</div>
            </div>
            <div class="metric-card">
                <div class="metric-title">BTO</div>
                <div class="metric-subtitle">Bed Turn Over</div>
                <div class="metric-value"><?= $stats['bto'] ?><span>x</span></div>
                <div class="metric-bar"><div class="metric-bar-fill" style="width:<?= min($stats['bto']*20,100) ?>%"></div></div>
                <div class="metric-desc">Frekuensi penggunaan tempat tidur</div>
            </div>
        </div>

        <div class="content-grid">
            <!-- Room Summary -->
            <div class="card">
                <div class="card-header">
                    <h3>Status per Ruangan</h3>
                    <a href="beds.php" class="btn btn-sm btn-outline">Lihat Semua</a>
                </div>
                <div class="room-summary-list">
                    <?php foreach ($rooms as $room): ?>
                    <div class="room-summary-item" onclick="window.location='beds.php?room=<?= $room['id'] ?>'">
                        <div class="room-summary-info">
                            <span class="room-name"><?= htmlspecialchars($room['room_name']) ?></span>
                            <span class="room-type-badge type-<?= strtolower(str_replace(' ','',$room['room_type'])) ?>"><?= $room['room_type'] ?></span>
                        </div>
                        <div class="room-summary-bars">
                            <span class="dot dot-available" title="Tersedia"><?= $room['available'] ?></span>
                            <span class="dot dot-booked" title="Terisi"><?= $room['booked'] ?></span>
                            <span class="dot dot-cleaning" title="Pembersihan"><?= $room['cleaning'] ?></span>
                            <span class="dot dot-maintenance" title="Perbaikan"><?= $room['maintenance'] ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Recent Bookings -->
            <div class="card">
                <div class="card-header">
                    <h3>Booking Terbaru</h3>
                    <a href="pasien.php" class="btn btn-sm btn-outline">Lihat Semua</a>
                </div>
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>No. RM</th>
                                <th>Pasien</th>
                                <th>Bed</th>
                                <th>Ruangan</th>
                                <th>Check-in</th>
                                <th>Check-out</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bookings as $bk): ?>
                            <tr>
                                <td class="text-muted"><?= htmlspecialchars($bk['no_rm'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($bk['patient_name']) ?></td>
                                <td><?= htmlspecialchars($bk['bed_code']) ?></td>
                                <td><?= htmlspecialchars($bk['room_name']) ?></td>
                                <td><?= $bk['check_in_date'] ?></td>
                                <td><?= $bk['check_out_date'] ?? '<span class="text-muted">-</span>' ?></td>
                                <td><span class="badge badge-<?= $bk['status'] ?>"><?= ucfirst($bk['status']) ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($bookings)): ?>
                            <tr><td colspan="6" class="text-center text-muted">Belum ada data booking</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <?php include 'partials/footer.php'; ?>

    <script>
        // Custom range toggle
        const customToggle = document.getElementById('customToggle');
        const customRange  = document.getElementById('customRange');
        if (customToggle) {
            customToggle.addEventListener('click', () => {
                customRange.classList.toggle('open');
            });
        }
    </script>
    <script src="assets/app.js"></script>
</body>
</html>
