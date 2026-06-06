<?php
$current = basename($_SERVER['PHP_SELF']);
?>
<nav class="navbar">
    <div class="navbar-brand">
        <img src="assets/logo.png" alt="SIBER" class="brand-logo-img">
        <div class="brand-text-wrap">
            <span class="brand-text">SIBER</span>
            <span class="brand-sub">Bed Management</span>
        </div>
    </div>
    <div class="navbar-menu">
        <a href="dashboard.php" class="nav-link <?= $current === 'dashboard.php' ? 'active' : '' ?>">Dashboard</a>
        <a href="beds.php" class="nav-link <?= $current === 'beds.php' ? 'active' : '' ?>">Bed Management</a>
        <a href="pasien.php" class="nav-link <?= $current === 'pasien.php' ? 'active' : '' ?>">Pasien</a>
        <?php if ($_SESSION['role'] === 'admin'): ?>
        <a href="users.php" class="nav-link <?= $current === 'users.php' ? 'active' : '' ?>">User Management</a>
        <?php endif; ?>
    </div>
    <div class="navbar-user">
        <span class="user-info">
            <span class="user-name"><?= htmlspecialchars($_SESSION['full_name']) ?></span>
            <span class="user-role"><?= strtoupper($_SESSION['role']) ?></span>
        </span>
        <a href="logout.php" class="btn btn-sm btn-outline-light">Keluar</a>
    </div>
    <button class="navbar-toggle" id="navToggle">
        <span></span><span></span><span></span>
    </button>
</nav>
<div class="navbar-mobile-menu" id="mobileMenu">
    <a href="dashboard.php" class="nav-link <?= $current === 'dashboard.php' ? 'active' : '' ?>">Dashboard</a>
    <a href="beds.php" class="nav-link <?= $current === 'beds.php' ? 'active' : '' ?>">Bed Management</a>
    <a href="pasien.php" class="nav-link <?= $current === 'pasien.php' ? 'active' : '' ?>">Pasien</a>
    <?php if ($_SESSION['role'] === 'admin'): ?>
    <a href="users.php" class="nav-link <?= $current === 'users.php' ? 'active' : '' ?>">User Management</a>
    <?php endif; ?>
    <a href="logout.php" class="nav-link">Keluar</a>
</div>