<?php
require_once 'functions.php';
requireLogin();

if (!hasRole('ranap','admin')) {
    header('Location: beds.php');
    exit;
}

$bed_id = isset($_GET['bed_id']) ? (int)$_GET['bed_id'] : 0;
if (!$bed_id) { header('Location: beds.php'); exit; }

$bed = getBedDetail($pdo, $bed_id);
if (!$bed || $bed['status'] !== 'available') {
    header('Location: beds.php');
    exit;
}

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patient_name   = trim($_POST['patient_name'] ?? '');
    $no_rm          = trim($_POST['no_rm'] ?? '');
    $check_in_date  = $_POST['check_in_date'] ?? '';
    $check_out_date = $_POST['check_out_date'] ?? '';
    $notes          = trim($_POST['notes'] ?? '');

    if (!$patient_name || !$check_in_date) {
        $error = 'Nama pasien dan tanggal check-in wajib diisi.';
    } elseif ($no_rm && !preg_match('/^\d{6}$/', $no_rm)) {
        $error = 'Nomor RM harus 6 digit angka.';
    } elseif ($check_out_date && $check_out_date < $check_in_date) {
        $error = 'Tanggal check-out tidak boleh sebelum check-in.';
    } else {
        try {
            $pdo->beginTransaction();

    
            $booking_role = in_array($_SESSION['role'], ['ranap','bangsal']) ? $_SESSION['role'] : 'ranap';

            $stmt = $pdo->prepare("
                INSERT INTO bookings (bed_id, user_id, user_role, no_rm, patient_name, booking_date, check_in_date, check_out_date, notes, status)
                VALUES (?, ?, ?, ?, ?, CURDATE(), ?, ?, ?, 'active')
            ");
            $stmt->execute([
                $bed_id,
                $_SESSION['user_id'],
                $booking_role,
                $no_rm ?: null,
                $patient_name,
                $check_in_date,
                $check_out_date ?: null,
                $notes ?: null
            ]);

            $pdo->prepare("UPDATE beds SET status='booked' WHERE id=?")->execute([$bed_id]);
            $pdo->commit();

            header('Location: beds.php?room=' . $bed['room_id'] . '&msg=booked');
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Terjadi kesalahan: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Bed - SIBER</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <?php include 'partials/navbar.php'; ?>

    <div class="page-wrapper">
        <div class="page-header">
            <div>
                <h2>Form Booking Bed</h2>
                <p>Isi data pasien untuk melakukan pemesanan tempat tidur</p>
            </div>
            <a href="beds.php?room=<?= $bed['room_id'] ?>" class="btn btn-outline">Kembali</a>
        </div>

        <div class="form-page-layout">
            <!-- Bed Info -->
            <div class="card bed-info-card">
                <div class="card-header"><h3>Informasi Bed</h3></div>
                <div class="bed-info-detail">
                    <div class="bed-preview available">
                        <div class="bed-code"><?= htmlspecialchars($bed['bed_code']) ?></div>
                        <div class="bed-number">Bed <?= $bed['bed_number'] ?></div>
                        <div class="bed-status-label">Tersedia</div>
                    </div>
                    <div class="bed-info-meta">
                        <div class="info-row"><span>Ruangan</span><strong><?= htmlspecialchars($bed['room_name']) ?></strong></div>
                        <div class="info-row"><span>Kelas</span><strong><?= htmlspecialchars($bed['room_type']) ?></strong></div>
                        <div class="info-row"><span>Kode Bed</span><strong><?= htmlspecialchars($bed['bed_code']) ?></strong></div>
                    </div>
                </div>
            </div>

            #<! Booking Form >#
            <div class="card">
                <div class="card-header"><h3>Data Pasien</h3></div>

                <?php if ($error): ?>
                    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="POST" class="booking-form">
                    <div class="form-group">
                        <label for="no_rm">Nomor RM <span class="optional">(6 digit)</span></label>
                        <input type="text" id="no_rm" name="no_rm"
                               value="<?= htmlspecialchars($_POST['no_rm'] ?? '') ?>"
                               placeholder="Contoh: 100001" maxlength="6" pattern="[0-9]{6}"
                               title="Nomor RM harus 6 digit angka" required>
                    </div>
                    <div class="form-group">
                        <label for="patient_name">Nama Pasien <span class="required">*</span></label>
                        <input type="text" id="patient_name" name="patient_name"
                               value="<?= htmlspecialchars($_POST['patient_name'] ?? '') ?>"
                               placeholder="Masukkan nama lengkap pasien" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="check_in_date">Tanggal Check-in <span class="required">*</span></label>
                            <input type="date" id="check_in_date" name="check_in_date"
                                   value="<?= $_POST['check_in_date'] ?? date('Y-m-d') ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="check_out_date">Tanggal Check-out <span class="optional">(opsional)</span></label>
                            <input type="date" id="check_out_date" name="check_out_date"
                                   value="<?= $_POST['check_out_date'] ?? '' ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="notes">Catatan</label>
                        <textarea id="notes" name="notes" rows="3" placeholder="Catatan tambahan (opsional)"><?= htmlspecialchars($_POST['notes'] ?? '') ?></textarea>
                    </div>
                    <div class="form-actions">
                        <a href="beds.php?room=<?= $bed['room_id'] ?>" class="btn btn-outline">Batal</a>
                        <button type="submit" class="btn btn-primary">Konfirmasi Booking</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script src="assets/app.js"></script>
</body>
</html>
