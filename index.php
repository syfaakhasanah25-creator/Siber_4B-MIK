<?php require_once 'auth.php'; ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIBER - Login</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body class="login-page">
    <div class="login-wrapper">
        <div class="login-card">
            <div class="login-header">
                <div class="login-logo">
                    <img src="assets/logo.png" alt="SIBER" class="login-logo-img">
                    <span class="logo-text">SIBER</span>
                </div>
                <h1>Sistem Informasi Bed Management</h1>
                <p>Masuk untuk melanjutkan</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="auth.php" class="login-form">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" placeholder="Masukkan username"
                           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required autocomplete="username">
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Masukkan password" required autocomplete="current-password">
                </div>
                <button type="submit" class="btn btn-primary btn-full">Masuk</button>
            </form>

            <div class="login-footer">
                <!-- <small>Demo: admin / petugas_ranap / petugas_bangsal &mdash; password: <strong>password</strong></small> -->
            </div>
        </div>
    </div>
</body>
</html>
