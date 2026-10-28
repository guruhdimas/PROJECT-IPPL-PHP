<?php
declare(strict_types=1);

/**
 * Ubah password milik sendiri (siswa & pembimbing).
 * Alamat: http://localhost/portal-trisula/ubah_password.php
 */
require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/password.php';
require __DIR__ . '/includes/pembimbing_layout.php';   // sudah memuat siswa_layout.php

if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

$session = $_SESSION['user'];
$role    = ($session['role'] ?? '') === 'pembimbing' ? 'pembimbing' : 'siswa';
$uid     = (int) $session['id'];
$pdo     = db();
$kembali = $role === 'pembimbing' ? 'profil_pembimbing.php' : 'profil.php';

// Data untuk header (nama di pojok kanan atas)
if ($role === 'pembimbing') {
    $me = pembimbing_user($pdo, $uid);
} else {
    $st = $pdo->prepare('SELECT * FROM users WHERE id = ?');
    $st->execute([$uid]);
    $row = $st->fetch(PDO::FETCH_ASSOC) ?: [];
    $me  = ['nama' => $row['nama'] ?? $session['nama'], 'kelas' => $row['kelas'] ?? ''];
}

$errors = [];
$sukses = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid($_POST['csrf'] ?? null)) {
        $errors[] = 'Sesi tidak valid, muat ulang halaman.';
    } else {
        $errors = ubah_password_sendiri(
            $pdo,
            $uid,
            (string) ($_POST['lama'] ?? ''),
            (string) ($_POST['baru'] ?? ''),
            (string) ($_POST['ulang'] ?? '')
        );
        if (!$errors) {
            $sukses = 'Password berhasil diubah.';
        }
    }
}

if ($role === 'pembimbing') {
    pembimbing_header('Ubah Password', 'profil', $me);
} else {
    siswa_header('Ubah Password', 'profil', $me);
}
?>
<style>
.gp-card{max-width:480px;margin:0 auto;padding:28px;background:#fff;border:1px solid #e2e8f0;border-radius:12px;font-family:inherit}
.gp-card h2{margin:0 0 4px;font-size:20px;line-height:28px;font-weight:700;color:#0f172a}
.gp-card > p{margin:0 0 20px;color:#64748b;font-size:14px;line-height:20px}
.gp-card label{display:block;margin:14px 0 6px;font-size:13px;line-height:18px;font-weight:600;color:#334155}
.gp-card input{width:100%;box-sizing:border-box;padding:10px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-family:inherit;font-size:14px;line-height:20px;background:#f8fafc}
.gp-card input:focus{outline:2px solid #bfe0d0;border-color:#1a6b45;background:#fff}
.gp-hint{margin:6px 0 0;color:#64748b;font-size:12px;line-height:16px}
.gp-act{display:flex;align-items:center;gap:12px;margin-top:20px}
.gp-btn{padding:11px 24px;border:0;border-radius:8px;background:#023724;color:#fff;cursor:pointer;font-family:inherit;font-size:14px;line-height:20px;font-weight:700}
.gp-btn:hover{background:#0a6244}
.gp-back{color:#475569;font-size:14px;font-weight:600;text-decoration:none}
.gp-back:hover{text-decoration:underline}
.gp-ok{margin-bottom:12px;padding:12px 14px;border-radius:8px;background:#eaf6ef;border:1px solid #bfe0d0;color:#14532d;font-size:14px;line-height:20px;font-weight:600}
.gp-err{margin-bottom:12px;padding:12px 14px;border-radius:8px;background:#fef2f2;border:1px solid #fecaca;color:#991b1b;font-size:14px;line-height:20px;font-weight:600}
</style>

<section class="gp-card">
    <h2>Ubah Password</h2>
    <p>Masukkan password lama, lalu pilih password baru untuk akun Anda.</p>

    <?php if ($sukses): ?><div class="gp-ok"><?= e($sukses) ?></div><?php endif; ?>
    <?php foreach ($errors as $m): ?><div class="gp-err"><?= e($m) ?></div><?php endforeach; ?>

    <form method="post" autocomplete="off" novalidate>
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">

        <label for="lama">Password lama</label>
        <input id="lama" name="lama" type="password" autocomplete="current-password" required>

        <label for="baru">Password baru</label>
        <input id="baru" name="baru" type="password" autocomplete="new-password" minlength="<?= PASSWORD_MIN ?>" required>
        <p class="gp-hint">Minimal <?= PASSWORD_MIN ?> karakter dan berbeda dari password lama.</p>

        <label for="ulang">Ulangi password baru</label>
        <input id="ulang" name="ulang" type="password" autocomplete="new-password" minlength="<?= PASSWORD_MIN ?>" required>

        <div class="gp-act">
            <button type="submit" class="gp-btn">Simpan Password</button>
            <a class="gp-back" href="<?= e($kembali) ?>">Kembali ke profil</a>
        </div>
    </form>
</section>
<?php
if ($role === 'pembimbing') {
    pembimbing_footer();
} else {
    siswa_footer();
}
