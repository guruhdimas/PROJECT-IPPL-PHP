<?php
declare(strict_types=1);

/**
 * Ganti password akun mana pun (hanya bisa dibuka oleh pembimbing yang sedang login).
 * Alamat: http://localhost/portal-trisula/ganti_password.php
 */
require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/pembimbing_layout.php';

$session = require_role('pembimbing');
$pdo     = db();
$me      = pembimbing_user($pdo, (int) $session['id']);

$errors = [];
$sukses = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string) ($_POST['username'] ?? ''));
    $baru     = (string) ($_POST['password'] ?? '');
    $ulang    = (string) ($_POST['ulang'] ?? '');

    if (!csrf_valid($_POST['csrf'] ?? null)) $errors[] = 'Sesi tidak valid, muat ulang halaman.';
    if ($username === '')                    $errors[] = 'Username wajib diisi.';
    if (strlen($baru) < 6)                   $errors[] = 'Password baru minimal 6 karakter.';
    if ($baru !== $ulang)                    $errors[] = 'Konfirmasi password tidak sama.';

    if (!$errors) {
        $st = $pdo->prepare('SELECT id, nama, role FROM users WHERE username = ? LIMIT 1');
        $st->execute([$username]);
        $u = $st->fetch();
        if (!$u) {
            $errors[] = 'Username "' . $username . '" tidak ditemukan.';
        } else {
            $pdo->prepare('UPDATE users SET password = ? WHERE id = ?')
                ->execute([password_hash($baru, PASSWORD_DEFAULT), $u['id']]);
            $sukses = 'Password untuk ' . $u['nama'] . ' (' . $u['role'] . ', username ' . $username . ') berhasil diganti.';
            $username = '';
        }
    }
}

pembimbing_header('Ganti Password', 'anggota', $me);
?>
<style>
.gp-card{max-width:480px;margin:0 auto;padding:28px;background:#fff;border:1px solid #e2e8f0;border-radius:12px}
.gp-card h2{margin:0 0 4px;font:700 20px/28px 'Nunito Sans',sans-serif;color:#0f172a}
.gp-card > p{margin:0 0 20px;color:#64748b;font:400 14px/20px 'Nunito Sans',sans-serif}
.gp-card label{display:block;margin:14px 0 6px;font:600 13px/18px 'Nunito Sans',sans-serif;color:#334155}
.gp-card input{width:100%;box-sizing:border-box;padding:10px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font:400 14px/20px 'Nunito Sans',sans-serif}
.gp-card input:focus{outline:2px solid #bfe0d0;border-color:#1a6b45}
.gp-btn{margin-top:20px;padding:11px 24px;border:0;border-radius:8px;background:#023724;color:#fff;cursor:pointer;font:700 14px/20px 'Nunito Sans',sans-serif}
.gp-btn:hover{background:#0a6244}
.gp-ok{margin-bottom:12px;padding:12px 14px;border-radius:8px;background:#eaf6ef;border:1px solid #bfe0d0;color:#14532d;font:600 14px/20px 'Nunito Sans',sans-serif}
.gp-err{margin-bottom:12px;padding:12px 14px;border-radius:8px;background:#fef2f2;border:1px solid #fecaca;color:#991b1b;font:600 14px/20px 'Nunito Sans',sans-serif}
</style>

<section class="gp-card">
    <h2>Ganti Password</h2>
    <p>Atur password baru untuk akun siswa atau pembimbing.</p>

    <?php if ($sukses): ?><div class="gp-ok"><?= e($sukses) ?></div><?php endif; ?>
    <?php foreach ($errors as $m): ?><div class="gp-err"><?= e($m) ?></div><?php endforeach; ?>

    <form method="post" autocomplete="off" novalidate>
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <label for="username">Username akun</label>
        <input id="username" name="username" maxlength="50" value="<?= e($username) ?>" required>
        <label for="password">Password baru</label>
        <input id="password" name="password" type="password" minlength="6" required>
        <label for="ulang">Ulangi password baru</label>
        <input id="ulang" name="ulang" type="password" minlength="6" required>
        <button type="submit" class="gp-btn">Simpan Password</button>
    </form>
</section>
<?php pembimbing_footer();
