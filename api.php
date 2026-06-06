<?php
require_once 'functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Unauthorized']); exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {

    case 'bed_detail':
        $bed_id = (int)($_GET['bed_id'] ?? 0);
        $bed    = getBedDetail($pdo, $bed_id);
        if (!$bed) { echo json_encode(['error' => 'Bed tidak ditemukan']); exit; }
        $stmt = $pdo->prepare("SELECT id, patient_name, no_rm, check_in_date, check_out_date FROM bookings WHERE bed_id = ? AND status = 'active' LIMIT 1");
        $stmt->execute([$bed_id]);
        $booking = $stmt->fetch();
        echo json_encode(['bed' => $bed, 'booking' => $booking]);
        break;

    case 'update_status':
        if (!hasRole('bangsal','admin')) {
            echo json_encode(['error' => 'Akses ditolak']); exit;
        }
        $bed_id    = (int)($_POST['bed_id'] ?? 0);
        $new_status = $_POST['status'] ?? '';
        $allowed   = ['available','booked','cleaning','maintenance'];

        if (!$bed_id || !in_array($new_status, $allowed)) {
            echo json_encode(['error' => 'Parameter tidak valid']); exit;
        }

        if ($new_status === 'available') {
            $pdo->prepare("UPDATE bookings SET status='completed', check_out_date=CURDATE() WHERE bed_id=? AND status='active'")->execute([$bed_id]);
        }

        $pdo->prepare("UPDATE beds SET status=? WHERE id=?")->execute([$new_status, $bed_id]);
        echo json_encode(['success' => true, 'status' => $new_status, 'label' => statusLabel($new_status)]);
        break;

    case 'complete_booking':
        if (!hasRole('ranap','bangsal','admin')) {
            echo json_encode(['error' => 'Akses ditolak']); exit;
        }
        $booking_id = (int)($_POST['booking_id'] ?? 0);
        $bed_id     = (int)($_POST['bed_id'] ?? 0);
        if (!$booking_id || !$bed_id) { echo json_encode(['error' => 'Parameter tidak valid']); exit; }

        $pdo->prepare("UPDATE bookings SET status='completed', check_out_date=CURDATE() WHERE id=? AND status='active'")->execute([$booking_id]);
        $pdo->prepare("UPDATE beds SET status='cleaning' WHERE id=?")->execute([$bed_id]);
        echo json_encode(['success' => true]);
        break;

    case 'cancel_booking':
        if (!hasRole('ranap','bangsal','admin')) {
            echo json_encode(['error' => 'Akses ditolak']); exit;
        }
        $booking_id = (int)($_POST['booking_id'] ?? 0);
        if (!$booking_id) { echo json_encode(['error' => 'ID booking tidak valid']); exit; }

        $stmt = $pdo->prepare("SELECT bed_id FROM bookings WHERE id=? AND status='active'");
        $stmt->execute([$booking_id]);
        $bk = $stmt->fetch();

        if (!$bk) { echo json_encode(['error' => 'Booking tidak ditemukan']); exit; }

        $pdo->prepare("UPDATE bookings SET status='cancelled' WHERE id=?")->execute([$booking_id]);
        $pdo->prepare("UPDATE beds SET status='available' WHERE id=?")->execute([$bk['bed_id']]);
        echo json_encode(['success' => true]);
        break;

    case 'room_beds':
        $room_id = (int)($_GET['room_id'] ?? 0);
        $beds    = getBedsByRoom($pdo, $room_id);
        echo json_encode(['beds' => $beds]);
        break;

    case 'add_user':
        if (!hasRole('admin')) { echo json_encode(['error'=>'Akses ditolak']); exit; }
        $full_name = trim($_POST['full_name'] ?? '');
        $username  = trim($_POST['username'] ?? '');
        $password  = $_POST['password'] ?? '';
        $role      = $_POST['role'] ?? 'ranap';
        if (!$full_name || !$username || !$password) { echo json_encode(['error'=>'Data tidak lengkap']); exit; }
        if (!in_array($role, ['admin','ranap','bangsal'])) { echo json_encode(['error'=>'Role tidak valid']); exit; }
        if (strlen($password) < 6) { echo json_encode(['error'=>'Password minimal 6 karakter']); exit; }
        $chk = $pdo->prepare("SELECT id FROM users WHERE username=?");
        $chk->execute([$username]);
        if ($chk->fetch()) { echo json_encode(['error'=>'Username sudah digunakan']); exit; }
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $pdo->prepare("INSERT INTO users (username, password, full_name, role) VALUES (?,?,?,?)")->execute([$username,$hash,$full_name,$role]);
        echo json_encode(['success'=>true]);
        break;

    case 'edit_user':
        if (!hasRole('admin')) { echo json_encode(['error'=>'Akses ditolak']); exit; }
        $user_id   = (int)($_POST['user_id'] ?? 0);
        $full_name = trim($_POST['full_name'] ?? '');
        $username  = trim($_POST['username'] ?? '');
        $password  = $_POST['password'] ?? '';
        $role      = $_POST['role'] ?? 'ranap';
        if (!$user_id || !$full_name || !$username) { echo json_encode(['error'=>'Data tidak lengkap']); exit; }
        if (!in_array($role, ['admin','ranap','bangsal'])) { echo json_encode(['error'=>'Role tidak valid']); exit; }
        // Cek duplikat username (selain diri sendiri)
        $chk = $pdo->prepare("SELECT id FROM users WHERE username=? AND id!=?");
        $chk->execute([$username, $user_id]);
        if ($chk->fetch()) { echo json_encode(['error'=>'Username sudah digunakan']); exit; }
        if ($password) {
            if (strlen($password) < 6) { echo json_encode(['error'=>'Password minimal 6 karakter']); exit; }
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $pdo->prepare("UPDATE users SET full_name=?, username=?, role=?, password=? WHERE id=?")->execute([$full_name,$username,$role,$hash,$user_id]);
        } else {
            $pdo->prepare("UPDATE users SET full_name=?, username=?, role=? WHERE id=?")->execute([$full_name,$username,$role,$user_id]);
        }
        echo json_encode(['success'=>true]);
        break;

    case 'delete_user':
        if (!hasRole('admin')) { echo json_encode(['error'=>'Akses ditolak']); exit; }
        $user_id = (int)($_POST['user_id'] ?? 0);
        if (!$user_id) { echo json_encode(['error'=>'ID tidak valid']); exit; }
        if ($user_id == ($_SESSION['user_id'] ?? 0)) { echo json_encode(['error'=>'Tidak bisa menghapus akun sendiri']); exit; }
        $pdo->prepare("DELETE FROM users WHERE id=?")->execute([$user_id]);
        echo json_encode(['success'=>true]);
        break;
        echo json_encode(getDashboardStats($pdo));
        break;

    case 'edit_pasien':
        $booking_id   = (int)($_POST['booking_id'] ?? 0);
        $patient_name = trim($_POST['patient_name'] ?? '');
        $no_rm        = trim($_POST['no_rm'] ?? '');
        if (!$booking_id || !$patient_name) { echo json_encode(['error'=>'Data tidak lengkap']); exit; }
        if ($no_rm && !preg_match('/^\d{6}$/', $no_rm)) { echo json_encode(['error'=>'Nomor RM harus 6 digit angka']); exit; }
        $pdo->prepare("UPDATE bookings SET patient_name=?, no_rm=? WHERE id=?")->execute([$patient_name, $no_rm ?: null, $booking_id]);
        echo json_encode(['success'=>true]);
        break;

    case 'add_room':
        if (!hasRole('admin','bangsal')) { echo json_encode(['error'=>'Akses ditolak']); exit; }
        $room_name = trim($_POST['room_name'] ?? '');
        $room_type = $_POST['room_type'] ?? '';
        $valid_types = ['VVIP','VIP','Kelas 1','Kelas 2','Kelas 3'];
        if (!$room_name || !in_array($room_type, $valid_types)) { echo json_encode(['error'=>'Data tidak lengkap']); exit; }
        $chk = $pdo->prepare("SELECT id FROM rooms WHERE room_name=?");
        $chk->execute([$room_name]);
        if ($chk->fetch()) { echo json_encode(['error'=>'Nama ruangan sudah ada']); exit; }
        $pdo->prepare("INSERT INTO rooms (room_name, room_type, total_beds) VALUES (?,?,0)")->execute([$room_name, $room_type]);
        echo json_encode(['success'=>true, 'id'=>$pdo->lastInsertId()]);
        break;

    case 'edit_room':
        if (!hasRole('admin','bangsal')) { echo json_encode(['error'=>'Akses ditolak']); exit; }
        $room_id   = (int)($_POST['room_id'] ?? 0);
        $room_name = trim($_POST['room_name'] ?? '');
        $room_type = $_POST['room_type'] ?? '';
        $valid_types = ['VVIP','VIP','Kelas 1','Kelas 2','Kelas 3'];
        if (!$room_id || !$room_name || !in_array($room_type, $valid_types)) { echo json_encode(['error'=>'Data tidak lengkap']); exit; }
        $chk = $pdo->prepare("SELECT id FROM rooms WHERE room_name=? AND id!=?");
        $chk->execute([$room_name, $room_id]);
        if ($chk->fetch()) { echo json_encode(['error'=>'Nama ruangan sudah digunakan']); exit; }
        $pdo->prepare("UPDATE rooms SET room_name=?, room_type=? WHERE id=?")->execute([$room_name, $room_type, $room_id]);
        echo json_encode(['success'=>true]);
        break;

    case 'delete_room':
        if (!hasRole('admin','bangsal')) { echo json_encode(['error'=>'Akses ditolak']); exit; }
        $room_id = (int)($_POST['room_id'] ?? 0);
        if (!$room_id) { echo json_encode(['error'=>'ID tidak valid']); exit; }
        $chk = $pdo->prepare("SELECT COUNT(*) FROM beds b JOIN bookings bk ON bk.bed_id=b.id WHERE b.room_id=? AND bk.status='active'");
        $chk->execute([$room_id]);
        if ($chk->fetchColumn() > 0) { echo json_encode(['error'=>'Ruangan masih memiliki booking aktif']); exit; }
        $pdo->prepare("DELETE FROM rooms WHERE id=?")->execute([$room_id]);
        echo json_encode(['success'=>true]);
        break;

    case 'add_bed':
        if (!hasRole('admin','bangsal')) { echo json_encode(['error'=>'Akses ditolak']); exit; }
        $room_id    = (int)($_POST['room_id'] ?? 0);
        $bed_code   = trim($_POST['bed_code'] ?? '');
        $bed_number = (int)($_POST['bed_number'] ?? 0);
        $status     = $_POST['status'] ?? 'available';
        if (!$room_id || !$bed_code || !$bed_number) { echo json_encode(['error'=>'Data tidak lengkap']); exit; }
        $allowed = ['available','cleaning','maintenance'];
        if (!in_array($status, $allowed)) $status = 'available';
        // Cek duplikat kode
        $chk = $pdo->prepare("SELECT id FROM beds WHERE bed_code=? AND room_id=?");
        $chk->execute([$bed_code, $room_id]);
        if ($chk->fetch()) { echo json_encode(['error'=>'Kode bed sudah ada di ruangan ini']); exit; }
        $pdo->prepare("INSERT INTO beds (room_id, bed_number, bed_code, status) VALUES (?,?,?,?)")->execute([$room_id,$bed_number,$bed_code,$status]);
        $new_id = $pdo->lastInsertId();
        // Update total_beds
        $pdo->prepare("UPDATE rooms SET total_beds=(SELECT COUNT(*) FROM beds WHERE room_id=?) WHERE id=?")->execute([$room_id,$room_id]);
        echo json_encode(['success'=>true, 'id'=>$new_id, 'bed_code'=>$bed_code, 'bed_number'=>$bed_number, 'status'=>$status, 'label'=>statusLabel($status)]);
        break;

    case 'edit_bed':
        if (!hasRole('admin','bangsal')) { echo json_encode(['error'=>'Akses ditolak']); exit; }
        $bed_id     = (int)($_POST['bed_id'] ?? 0);
        $bed_code   = trim($_POST['bed_code'] ?? '');
        $bed_number = (int)($_POST['bed_number'] ?? 0);
        $status     = $_POST['status'] ?? 'available';
        if (!$bed_id || !$bed_code || !$bed_number) { echo json_encode(['error'=>'Data tidak lengkap']); exit; }
        $allowed = ['available','cleaning','maintenance','booked'];
        if (!in_array($status, $allowed)) $status = 'available';
        // Cek duplikat kode 
        $chk = $pdo->prepare("SELECT id FROM beds WHERE bed_code=? AND id!=?");
        $chk->execute([$bed_code, $bed_id]);
        if ($chk->fetch()) { echo json_encode(['error'=>'Kode bed sudah digunakan']); exit; }
        $pdo->prepare("UPDATE beds SET bed_code=?, bed_number=?, status=? WHERE id=?")->execute([$bed_code,$bed_number,$status,$bed_id]);
        echo json_encode(['success'=>true, 'bed_code'=>$bed_code, 'bed_number'=>$bed_number, 'status'=>$status, 'label'=>statusLabel($status)]);
        break;

    case 'delete_bed':
        if (!hasRole('admin','bangsal')) { echo json_encode(['error'=>'Akses ditolak']); exit; }
        $bed_id = (int)($_POST['bed_id'] ?? 0);
        if (!$bed_id) { echo json_encode(['error'=>'ID tidak valid']); exit; }
        // Cek ada booking aktif
        $chk = $pdo->prepare("SELECT id FROM bookings WHERE bed_id=? AND status='active'");
        $chk->execute([$bed_id]);
        if ($chk->fetch()) { echo json_encode(['error'=>'Bed masih memiliki booking aktif, tidak bisa dihapus']); exit; }
        $stmt = $pdo->prepare("SELECT room_id FROM beds WHERE id=?");
        $stmt->execute([$bed_id]);
        $row = $stmt->fetch();
        $pdo->prepare("DELETE FROM beds WHERE id=?")->execute([$bed_id]);
        if ($row) $pdo->prepare("UPDATE rooms SET total_beds=(SELECT COUNT(*) FROM beds WHERE room_id=?) WHERE id=?")->execute([$row['room_id'],$row['room_id']]);
        echo json_encode(['success'=>true]);
        break;

    default:
        echo json_encode(['error' => 'Action tidak dikenal']);
}
