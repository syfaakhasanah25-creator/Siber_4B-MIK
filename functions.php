<?php
require_once 'config.php';

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: index.php');
        exit;
    }
}

function hasRole(...$roles) {
    return in_array($_SESSION['role'] ?? '', $roles);
}

function getRooms($pdo) {
    $stmt = $pdo->query("SELECT * FROM rooms ORDER BY FIELD(room_type,'VVIP','VIP','Kelas 1','Kelas 2','Kelas 3'), room_name");
    return $stmt->fetchAll();
}

function getBedsByRoom($pdo, $room_id) {
    $stmt = $pdo->prepare("SELECT * FROM beds WHERE room_id = ? ORDER BY bed_number");
    $stmt->execute([$room_id]);
    return $stmt->fetchAll();
}

function getBedDetail($pdo, $bed_id) {
    $stmt = $pdo->prepare("
        SELECT b.*, r.room_name, r.room_type,
               bk.patient_name, bk.check_in_date, bk.check_out_date, bk.id as booking_id
        FROM beds b
        JOIN rooms r ON b.room_id = r.id
        LEFT JOIN bookings bk ON bk.bed_id = b.id AND bk.status = 'active'
        WHERE b.id = ?
    ");
    $stmt->execute([$bed_id]);
    return $stmt->fetch();
}

function getActiveBooking($pdo, $bed_id) {
    $stmt = $pdo->prepare("SELECT * FROM bookings WHERE bed_id = ? AND status = 'active' LIMIT 1");
    $stmt->execute([$bed_id]);
    return $stmt->fetch();
}

function getDashboardStats($pdo, $period_type = 'days', $date_from = '', $date_to = '') {
    $period = 30;

    // WHERE berdasarkan tipe periode
    if ($period_type === 'all') {
        $where_period  = "1=1";
        $where_out     = "1=1";
        $params_period = [];
        $params_out    = [];
        // Hitung periode aktual dari data
        $min = $pdo->query("SELECT MIN(check_in_date) FROM bookings WHERE status IN ('active','completed')")->fetchColumn();
        $period = $min ? max(1, (int)((time() - strtotime($min)) / 86400)) : 30;
    } elseif ($period_type === 'custom' && $date_from && $date_to) {
        $where_period  = "check_in_date BETWEEN ? AND ?";
        $where_out     = "check_out_date BETWEEN ? AND ?";
        $params_period = [$date_from, $date_to];
        $params_out    = [$date_from, $date_to];
        $period = max(1, (int)((strtotime($date_to) - strtotime($date_from)) / 86400) + 1);
    } else {
        $days = 30;
        if ($date_from) {
            $days = max(1, (int)((strtotime($date_to ?: 'today') - strtotime($date_from)) / 86400) + 1);
        }
        $period = $days;
        $where_period  = "check_in_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)";
        $where_out     = "check_out_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)";
        $params_period = [$period];
        $params_out    = [$period];
    }

    $total       = $pdo->query("SELECT COUNT(*) FROM beds")->fetchColumn();
    $booked      = $pdo->query("SELECT COUNT(*) FROM beds WHERE status = 'booked'")->fetchColumn();
    $available   = $pdo->query("SELECT COUNT(*) FROM beds WHERE status = 'available'")->fetchColumn();
    $cleaning    = $pdo->query("SELECT COUNT(*) FROM beds WHERE status = 'cleaning'")->fetchColumn();
    $maintenance = $pdo->query("SELECT COUNT(*) FROM beds WHERE status = 'maintenance'")->fetchColumn();

    // Hari perawatan
    $sql = "SELECT COALESCE(SUM(DATEDIFF(COALESCE(check_out_date, CURDATE()), check_in_date)), 0)
            FROM bookings WHERE status IN ('active','completed') AND $where_period";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params_period);
    $total_care_days = (int)$stmt->fetchColumn();

    // Pasien keluar
    $sql2 = "SELECT COUNT(*) FROM bookings WHERE status = 'completed' AND $where_out";
    $stmt2 = $pdo->prepare($sql2);
    $stmt2->execute($params_out);
    $patients_out = (int)$stmt2->fetchColumn();

    $bor = $total > 0 ? round(($total_care_days / ($total * $period)) * 100, 1) : 0;
    $los = $patients_out > 0 ? round($total_care_days / $patients_out, 1) : 0;
    $toi = $patients_out > 0 ? round((($total * $period) - $total_care_days) / $patients_out, 1) : 0;
    $bto = $total > 0 ? round($patients_out / $total, 2) : 0;

    return compact('total','booked','available','cleaning','maintenance','bor','los','toi','bto','period');
}

function getRoomStats($pdo) {
    $stmt = $pdo->query("
        SELECT r.id, r.room_name, r.room_type, r.total_beds,
               SUM(CASE WHEN b.status='available' THEN 1 ELSE 0 END) as available,
               SUM(CASE WHEN b.status='booked' THEN 1 ELSE 0 END) as booked,
               SUM(CASE WHEN b.status='cleaning' THEN 1 ELSE 0 END) as cleaning,
               SUM(CASE WHEN b.status='maintenance' THEN 1 ELSE 0 END) as maintenance
        FROM rooms r
        LEFT JOIN beds b ON b.room_id = r.id
        GROUP BY r.id
        ORDER BY FIELD(r.room_type,'VVIP','VIP','Kelas 1','Kelas 2','Kelas 3'), r.room_name
    ");
    return $stmt->fetchAll();
}

function getRecentBookings($pdo, $limit = 10) {
    $stmt = $pdo->prepare("
        SELECT bk.*, b.bed_code, r.room_name, u.full_name as petugas
        FROM bookings bk
        JOIN beds b ON bk.bed_id = b.id
        JOIN rooms r ON b.room_id = r.id
        JOIN users u ON bk.user_id = u.id
        ORDER BY FIELD(bk.status,'active','completed','cancelled'), bk.created_at DESC
        LIMIT ?
    ");
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}

function getAllBookings($pdo, $search = '', $status = '') {
    $where = [];
    $params = [];
    if ($search) {
        $where[] = "(bk.patient_name LIKE ? OR bk.no_rm LIKE ? OR b.bed_code LIKE ? OR r.room_name LIKE ?)";
        $s = "%$search%";
        $params = array_merge($params, [$s, $s, $s, $s]);
    }
    if ($status) {
        $where[] = "bk.status = ?";
        $params[] = $status;
    }
    $sql = "
        SELECT bk.*, b.bed_code, r.room_name, r.room_type, u.full_name as petugas
        FROM bookings bk
        JOIN beds b ON bk.bed_id = b.id
        JOIN rooms r ON b.room_id = r.id
        JOIN users u ON bk.user_id = u.id
    ";
    if ($where) $sql .= " WHERE " . implode(' AND ', $where);
    $sql .= " ORDER BY FIELD(bk.status,'active','completed','cancelled'), bk.created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function statusLabel($status) {
    $map = [
        'available'   => 'Tersedia',
        'booked'      => 'Terisi',
        'cleaning'    => 'Pembersihan',
        'maintenance' => 'Perbaikan',
    ];
    return $map[$status] ?? $status;
}

function statusClass($status) {
    $map = [
        'available'   => 'status-available',
        'booked'      => 'status-booked',
        'cleaning'    => 'status-cleaning',
        'maintenance' => 'status-maintenance',
    ];
    return $map[$status] ?? '';
}
