<?php
require_once 'functions.php';
requireLogin();

if (!hasRole('admin')) {
    header('Location: dashboard.php');
    exit;
}

$users = $pdo->query("SELECT id, username, full_name, role, created_at FROM users ORDER BY role, full_name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - SIBER</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <?php include 'partials/navbar.php'; ?>

    <div class="page-wrapper">
        <div class="page-header">
            <div>
                <h2>User Management</h2>
                <p>Kelola akun pengguna sistem SIBER</p>
            </div>
            <button class="btn btn-primary" onclick="openUserModal()">+ Tambah User</button>
        </div>

        <div class="card">
            <div class="card-header">
                <h3>Daftar Pengguna</h3>
                <span class="text-muted" style="font-size:0.78rem"><?= count($users) ?> pengguna terdaftar</span>
            </div>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nama Lengkap</th>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Terdaftar</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $i => $u): ?>
                        <tr>
                            <td class="text-muted"><?= $i + 1 ?></td>
                            <td><strong><?= htmlspecialchars($u['full_name']) ?></strong></td>
                            <td><?= htmlspecialchars($u['username']) ?></td>
                            <td><span class="role-badge role-<?= $u['role'] ?>"><?= strtoupper($u['role']) ?></span></td>
                            <td class="text-muted"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                            <td>
                                <div class="action-btns">
                                    <button class="btn btn-sm btn-outline"
                                        onclick="openUserModal(<?= $u['id'] ?>, '<?= htmlspecialchars($u['full_name'], ENT_QUOTES) ?>', '<?= htmlspecialchars($u['username'], ENT_QUOTES) ?>', '<?= $u['role'] ?>')">
                                        Edit
                                    </button>
                                    <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                    <button class="btn btn-sm btn-danger"
                                        onclick="deleteUser(<?= $u['id'] ?>, '<?= htmlspecialchars($u['full_name'], ENT_QUOTES) ?>')">
                                        Hapus
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- User Modal -->
    <div class="modal-overlay" id="userOverlay" onclick="closeUserModal()"></div>
    <div class="modal" id="userModal">
        <div class="modal-header">
            <h3 id="userModalTitle">Tambah User</h3>
            <button class="modal-close" onclick="closeUserModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="userForm">
                <input type="hidden" id="userId" name="user_id">
                <input type="hidden" id="userAction" name="action" value="add_user">

                <div class="form-group">
                    <label for="userFullName">Nama Lengkap <span class="required">*</span></label>
                    <input type="text" id="userFullName" name="full_name" placeholder="Nama lengkap" required>
                </div>
                <div class="form-group">
                    <label for="userUsername">Username <span class="required">*</span></label>
                    <input type="text" id="userUsername" name="username" placeholder="Username untuk login" required>
                </div>
                <div class="form-group">
                    <label for="userRole">Role <span class="required">*</span></label>
                    <select id="userRole" name="role" required>
                        <option value="admin">ADMIN</option>
                        <option value="ranap">ranap</option>
                        <option value="bangsal">BANGSAL</option>
                    </select>
                </div>
                <div class="form-group" id="passwordGroup">
                    <label for="userPassword">Password <span class="required" id="passRequired">*</span></label>
                    <input type="password" id="userPassword" name="password" placeholder="Minimal 6 karakter">
                    <small id="passHint" class="text-muted" style="font-size:0.7rem;display:none">Kosongkan jika tidak ingin mengubah password</small>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn btn-outline" onclick="closeUserModal()">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <?php include 'partials/footer.php'; ?>

    <script>
        const USER_ROLE = '<?= $_SESSION['role'] ?>';

        function openUserModal(id, fullName, username, role) {
            const isEdit = !!id;
            document.getElementById('userModalTitle').textContent = isEdit ? 'Edit User' : 'Tambah User';
            document.getElementById('userAction').value = isEdit ? 'edit_user' : 'add_user';
            document.getElementById('userId').value = id || '';
            document.getElementById('userFullName').value = fullName || '';
            document.getElementById('userUsername').value = username || '';
            document.getElementById('userRole').value = role || 'ranap';
            document.getElementById('userPassword').value = '';
            document.getElementById('passRequired').style.display = isEdit ? 'none' : 'inline';
            document.getElementById('passHint').style.display = isEdit ? 'inline' : 'none';
            document.getElementById('userPassword').required = !isEdit;
            document.getElementById('userOverlay').classList.add('open');
            document.getElementById('userModal').classList.add('open');
        }

        function closeUserModal() {
            document.getElementById('userOverlay').classList.remove('open');
            document.getElementById('userModal').classList.remove('open');
        }

        document.getElementById('userForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const fd = new FormData(this);
            const pass = fd.get('password');
            const action = fd.get('action');

            if (action === 'add_user' && pass.length < 6) {
                showToast('Password minimal 6 karakter', 'error'); return;
            }
            if (action === 'edit_user' && pass && pass.length < 6) {
                showToast('Password minimal 6 karakter', 'error'); return;
            }

            fetch('api.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(data => {
                    if (data.error) { showToast(data.error, 'error'); return; }
                    showToast('User berhasil disimpan', 'success');
                    setTimeout(() => location.reload(), 800);
                });
        });

        function deleteUser(userId, fullName) {
            showConfirm('Hapus user "' + fullName + '"? Tindakan ini tidak bisa dibatalkan.', () => {
                const fd = new FormData();
                fd.append('action', 'delete_user');
                fd.append('user_id', userId);
                fetch('api.php', { method: 'POST', body: fd })
                    .then(r => r.json())
                    .then(data => {
                        if (data.error) { showToast(data.error, 'error'); return; }
                        showToast('User dihapus', 'success');
                        setTimeout(() => location.reload(), 800);
                    });
            });
        }
    </script>
    <script src="assets/app.js"></script>
</body>
</html>
