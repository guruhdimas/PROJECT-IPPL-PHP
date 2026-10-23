<?php
declare(strict_types=1);

require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/auth.php';

// Sudah login -> langsung ke dashboard
if (is_logged_in()) {
    header('Location: ' . home_for_role($_SESSION['user']['role']));
    exit;
}

$error    = '';
$username = '';
$role     = 'siswa';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role     = in_array($_POST['role'] ?? '', ['siswa', 'pembimbing'], true) ? $_POST['role'] : 'siswa';

    if (!csrf_valid($_POST['csrf'] ?? null)) {
        $error = 'Sesi tidak valid, silakan muat ulang halaman.';
    } elseif ($username === '' || $password === '') {
        $error = 'Username dan password wajib diisi.';
    } else {
        try {
            $stmt = db()->prepare('SELECT * FROM users WHERE username = :u AND role = :r LIMIT 1');
            $stmt->execute([':u' => $username, ':r' => $role]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                session_regenerate_id(true);
                $_SESSION['user'] = [
                    'id'       => (int) $user['id'],
                    'username' => $user['username'],
                    'nama'     => $user['nama'],
                    'role'     => $user['role'],
                    'nis'      => $user['nis'],
                    'angkatan' => $user['angkatan'],
                ];
                unset($_SESSION['csrf']);
                header('Location: ' . home_for_role($user['role']));
                exit;
            }
            // Pesan sengaja dibuat umum agar tidak membocorkan mana yang salah
            $error = 'Username, password, atau peran tidak sesuai.';
        } catch (PDOException $ex) {
            $error = 'Tidak dapat terhubung ke database.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - Portal Ekstrakurikuler Trisula Praja</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;800&family=Condiment&family=Concert+One&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-page">

    <div class="topbar topbar--login">
        <a href="index.php" class="logo-badge" aria-label="Kembali ke portal">
            <img src="assets/img/logo.png" alt="Logo SMA Trisula Praja" onerror="this.style.display='none'">
        </a>
    </div>

    <main class="login-wrap">
        <section class="login-hero">
            <h1>
                <span class="t-script">PORTAL</span>
                <span class="t-script t-script--2">EKSTRAKURIKULER</span>
                <span class="t-title">TRISULA PRAJA</span>
            </h1>
        </section>

        <section class="login-side">
            <div class="login-card">
                <h2>Selamat Datang Kembali!</h2>
                <p class="sub">Masuk untuk melanjutkan ke dasbor Anda</p>

                <?php if ($error): ?>
                    <div class="alert" role="alert"><?= e($error) ?></div>
                <?php endif; ?>

                <form method="post" action="login.php" autocomplete="off" novalidate>
                    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">

                    <label for="username">Username</label>
                    <div class="field">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="#6a7282" stroke-width="2"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>
                        <input type="text" id="username" name="username" placeholder="Masukan Username"
                               value="<?= e($username) ?>" required>
                    </div>

                    <label for="password" class="mt">Password</label>
                    <div class="field">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="#6a7282" stroke-width="2"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>
                        <input type="password" id="password" name="password" placeholder="••••••••" required>
                    </div>

                    <p class="label mt">Silahkan Pilih Peran Anda!</p>
                    <div class="roles">
                        <label class="role">
                            <input type="radio" name="role" value="siswa" <?= $role === 'siswa' ? 'checked' : '' ?>>
                            <span class="role-box">
                                <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>
                                Siswa
                            </span>
                        </label>
                        <label class="role">
                            <input type="radio" name="role" value="pembimbing" <?= $role === 'pembimbing' ? 'checked' : '' ?>>
                            <span class="role-box">
                                <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 9l10-5 10 5-10 5z"/><path d="M6 11v5c3 2 9 2 12 0v-5"/></svg>
                                Pembimbing
                            </span>
                        </label>
                    </div>

                    <button type="submit" class="btn-login">Login</button>
                </form>

                <p class="help">Butuh Bantuan? <a href="https://wa.me/620000000000" target="_blank" rel="noopener"><b>klik disini</b></a></p>
            </div>

            <p class="legal">By signing in, you agree to our Terms of Service and Privacy Policy.<br>
                Your privacy and data security are our top priorities.</p>
        </section>
    </main>
</body>
</html>
