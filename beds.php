<?php
require_once 'functions.php';
requireLogin();

$rooms       = getRooms($pdo);
$active_room = isset($_GET['room']) ? (int)$_GET['room'] : 0; // 0 = semua
$show_all    = ($active_room === 0);

if ($show_all) {
    // Ambil semua bed dari semua ruangan
    $beds = $pdo->query("SELECT b.*, r.room_name, r.room_type FROM beds b JOIN rooms r ON b.room_id = r.id ORDER BY r.id, b.bed_number")->fetchAll();
} else {
    $beds = getBedsByRoom($pdo, $active_room);
}

$current_room = null;
foreach ($rooms as $r) {
    if ($r['id'] == $active_room) { $current_room = $r; break; }
}

$can_crud = hasRole('admin');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bed Management - SIBER</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <?php include 'partials/navbar.php'; ?>

    <div class="page-wrapper">
        <div class="page-header">
            <div>
                <h2>Bed Management</h2>
                <p>Kelola dan pantau status tempat tidur per ruangan</p>
            </div>
            <div style="display:flex;gap:0.5rem">
                <?php if ($can_crud): ?>
                <button class="btn btn-outline" onclick="openRoomModal()">+ Tambah Ruangan</button>
                <?php endif; ?>
                <?php if ($can_crud && !$show_all): ?>
                <button class="btn btn-primary" onclick="openAddBedModal()">+ Tambah Bed</button>
                <?php endif; ?>
            </div>
        </div>

        <!-- Legend -->
        <div class="legend">
            <span class="legend-item"><span class="legend-dot dot-available"></span> Tersedia</span>
            <span class="legend-item"><span class="legend-dot dot-booked"></span> Terisi</span>
            <span class="legend-item"><span class="legend-dot dot-cleaning"></span> Pembersihan</span>
            <span class="legend-item"><span class="legend-dot dot-maintenance"></span> Perbaikan</span>
        </div>

        <div class="beds-layout">
            <!-- Sidebar -->
            <div class="room-sidebar">
                <!-- ALL -->
                <div class="sidebar-group">
                    <a href="beds.php" class="sidebar-room <?= $show_all ? 'active' : '' ?>">Semua Ruangan</a>
                </div>
                <?php
                $grouped   = [];
                foreach ($rooms as $r) $grouped[$r['room_type']][] = $r;
                $typeOrder = ['VVIP','VIP','Kelas 1','Kelas 2','Kelas 3'];
                foreach ($typeOrder as $type):
                    if (!isset($grouped[$type])) continue;
                ?>
                <div class="sidebar-group">
                    <div class="sidebar-group-label">
                        <?= $type ?>
                    </div>
                    <?php foreach ($grouped[$type] as $room): ?>
                    <div class="sidebar-room-wrap">
                        <a href="beds.php?room=<?= $room['id'] ?>"
                           class="sidebar-room <?= $room['id'] == $active_room ? 'active' : '' ?>">
                            <?= htmlspecialchars($room['room_name']) ?>
                        </a>
                        <?php if ($can_crud): ?>
                        <div class="sidebar-room-actions">
                            <button class="sidebar-action-btn" title="Edit" onclick="openRoomModal(<?= $room['id'] ?>, '<?= htmlspecialchars($room['room_name'], ENT_QUOTES) ?>', '<?= $room['room_type'] ?>')">&#9998;</button>
                            <button class="sidebar-action-btn danger" title="Hapus" onclick="deleteRoom(<?= $room['id'] ?>, '<?= htmlspecialchars($room['room_name'], ENT_QUOTES) ?>')">&#10005;</button>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Bed Grid -->
            <div class="bed-content">
                <?php
                $cnt = ['available'=>0,'booked'=>0,'cleaning'=>0,'maintenance'=>0];
                foreach ($beds as $b) $cnt[$b['status']]++;
                ?>
                <div class="bed-content-header">
                    <div>
                        <?php if ($show_all): ?>
                            <h3>Semua Ruangan</h3>
                            <span class="room-type-badge" style="background:#4682b4">Semua Kelas</span>
                        <?php elseif ($current_room): ?>
                            <h3><?= htmlspecialchars($current_room['room_name']) ?></h3>
                            <span class="room-type-badge type-<?= strtolower(str_replace(' ','',$current_room['room_type'])) ?>"><?= $current_room['room_type'] ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="bed-count-summary" id="bedSummary">
                        <span class="dot dot-available"><?= $cnt['available'] ?> Tersedia</span>
                        <span class="dot dot-booked"><?= $cnt['booked'] ?> Terisi</span>
                        <span class="dot dot-cleaning"><?= $cnt['cleaning'] ?> Pembersihan</span>
                        <span class="dot dot-maintenance"><?= $cnt['maintenance'] ?> Perbaikan</span>
                    </div>
                </div>

                <?php if ($show_all): ?>
                <!-- Tampil per grup ruangan -->
                <?php
                $byRoom = [];
                foreach ($beds as $b) $byRoom[$b['room_id']][] = $b;
                foreach ($rooms as $room):
                    if (!isset($byRoom[$room['id']])) continue;
                ?>
                <div class="room-group-section">
                    <div class="room-group-title">
                        <span><?= htmlspecialchars($room['room_name']) ?></span>
                        <span class="room-type-badge type-<?= strtolower(str_replace(' ','',$room['room_type'])) ?>"><?= $room['room_type'] ?></span>
                    </div>
                    <div class="bed-grid">
                        <?php foreach ($byRoom[$room['id']] as $bed): ?>
                        <div class="bed-card <?= $bed['status'] ?>" data-bed-id="<?= $bed['id'] ?>" data-status="<?= $bed['status'] ?>" onclick="openBedModal(<?= $bed['id'] ?>)">
                            <div class="bed-code"><?= htmlspecialchars($bed['bed_code']) ?></div>
                            <div class="bed-number">Bed <?= $bed['bed_number'] ?></div>
                            <div class="bed-status-label"><?= statusLabel($bed['status']) ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>

                <?php else: ?>
                <div class="bed-grid" id="bedGrid">
                    <?php foreach ($beds as $bed): ?>
                    <div class="bed-card <?= $bed['status'] ?>" data-bed-id="<?= $bed['id'] ?>" data-status="<?= $bed['status'] ?>" onclick="openBedModal(<?= $bed['id'] ?>)">
                        <div class="bed-code"><?= htmlspecialchars($bed['bed_code']) ?></div>
                        <div class="bed-number">Bed <?= $bed['bed_number'] ?></div>
                        <div class="bed-status-label"><?= statusLabel($bed['status']) ?></div>
                        <?php if ($can_crud): ?>
                        <div class="bed-crud-actions" onclick="event.stopPropagation()">
                            <button class="bed-edit-btn" onclick="openEditBedModal(<?= $bed['id'] ?>, '<?= htmlspecialchars($bed['bed_code']) ?>', '<?= $bed['status'] ?>')">Edit</button>
                            <button class="bed-del-btn" onclick="deleteBed(<?= $bed['id'] ?>, '<?= htmlspecialchars($bed['bed_code']) ?>')">Hapus</button>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Bed Detail Modal -->
    <div class="modal-overlay" id="modalOverlay" onclick="closeModal()"></div>
    <div class="modal" id="bedModal">
        <div class="modal-header">
            <h3 id="modalTitle">Detail Bed</h3>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <div class="modal-body" id="modalBody">
            <div class="loading">Memuat...</div>
        </div>
    </div>

    <!-- CRUD Modal -->
    <div class="modal-overlay" id="crudOverlay" onclick="closeCrudModal()"></div>
    <div class="modal" id="crudModal">
        <div class="modal-header">
            <h3 id="crudTitle">Tambah Bed</h3>
            <button class="modal-close" onclick="closeCrudModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="crudForm">
                <input type="hidden" id="crudBedId" name="bed_id">
                <input type="hidden" id="crudAction" name="action" value="add_bed">
                <input type="hidden" name="room_id" value="<?= $active_room ?>">
                <div class="form-group">
                    <label for="crudBedCode">Kode Bed <span class="required">*</span></label>
                    <input type="text" id="crudBedCode" name="bed_code" placeholder="Contoh: KRS-05" required>
                </div>
                <div class="form-group">
                    <label for="crudBedNumber">Nomor Bed <span class="required">*</span></label>
                    <input type="number" id="crudBedNumber" name="bed_number" min="1" placeholder="Contoh: 5" required>
                </div>
                <div class="form-group">
                    <label for="crudStatus">Status</label>
                    <select id="crudStatus" name="status">
                        <option value="available">Tersedia</option>
                        <option value="cleaning">Pembersihan</option>
                        <option value="maintenance">Perbaikan</option>
                    </select>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-outline" onclick="closeCrudModal()">Batal</button>
                    <button type="submit" class="btn btn-primary" id="crudSubmit">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <?php include 'partials/footer.php'; ?>

    <!-- Room CRUD Modal -->
    <div class="modal-overlay" id="roomOverlay" onclick="closeRoomModal()"></div>
    <div class="modal" id="roomModal">
        <div class="modal-header">
            <h3 id="roomModalTitle">Tambah Ruangan</h3>
            <button class="modal-close" onclick="closeRoomModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="roomForm">
                <input type="hidden" id="roomId" name="room_id">
                <input type="hidden" id="roomAction" name="action" value="add_room">
                <div class="form-group">
                    <label for="roomName">Nama Ruangan <span class="required">*</span></label>
                    <input type="text" id="roomName" name="room_name" placeholder="Contoh: Werkudara" required>
                </div>
                <div class="form-group">
                    <label for="roomType">Tipe Kelas <span class="required">*</span></label>
                    <select id="roomType" name="room_type" required>
                        <option value="VVIP">VVIP</option>
                        <option value="VIP">VIP</option>
                        <option value="Kelas 1">Kelas 1</option>
                        <option value="Kelas 2">Kelas 2</option>
                        <option value="Kelas 3">Kelas 3</option>
                    </select>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-outline" onclick="closeRoomModal()">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const USER_ROLE   = '<?= $_SESSION['role'] ?>';
        const CURRENT_ROOM = <?= $active_room ?>;
        const CAN_CRUD    = <?= $can_crud ? 'true' : 'false' ?>;
    </script>
    <script src="assets/app.js"></script>
    <script>
        // CRUD Room — inline agar tersedia saat onclick dipanggil
        function openRoomModal(id, name, type) {
            const isEdit = !!id;
            document.getElementById('roomModalTitle').textContent = isEdit ? 'Edit Ruangan' : 'Tambah Ruangan';
            document.getElementById('roomAction').value = isEdit ? 'edit_room' : 'add_room';
            document.getElementById('roomId').value = id || '';
            document.getElementById('roomName').value = name || '';
            document.getElementById('roomType').value = type || 'VVIP';
            document.getElementById('roomOverlay').classList.add('open');
            document.getElementById('roomModal').classList.add('open');
        }
        function closeRoomModal() {
            document.getElementById('roomOverlay').classList.remove('open');
            document.getElementById('roomModal').classList.remove('open');
        }
        document.getElementById('roomForm').addEventListener('submit', function(e) {
            e.preventDefault();
            fetch('api.php', { method: 'POST', body: new FormData(this) })
                .then(r => r.json())
                .then(data => {
                    if (data.error) { showToast(data.error, 'error'); return; }
                    closeRoomModal();
                    showToast('Ruangan berhasil disimpan', 'success');
                    setTimeout(() => location.reload(), 800);
                });
        });
        function deleteRoom(roomId, roomName) {
            showConfirm('Hapus ruangan "' + roomName + '"? Semua bed di ruangan ini juga akan dihapus.', () => {
                const fd = new FormData();
                fd.append('action', 'delete_room');
                fd.append('room_id', roomId);
                fetch('api.php', { method: 'POST', body: fd })
                    .then(r => r.json())
                    .then(data => {
                        if (data.error) { showToast(data.error, 'error'); return; }
                        showToast('Ruangan dihapus', 'success');
                        setTimeout(() => location.href = 'beds.php', 800);
                    });
            });
        }
    </script>
</body>
</html>
