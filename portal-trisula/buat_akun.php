<?php
declare(strict_types=1);

/**
 * Tambah akun SISWA atau PEMBIMBING (hanya bisa dibuka oleh pembimbing yang sedang login).
 * Alamat: http://localhost/portal-trisula/buat_akun.php
 */
require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/pembimbing_layout.php';

$session = require_role('pembimbing');
$pdo     = db();
$me      = pembimbing_user($pdo, (int) $session['id']);

$errors = [];
$sukses = '';
$v = ['username' => '', 'nama' => '', 'nis' => '', 'kelas' => '', 'angkatan' => date('Y'), 'role' => 'siswa'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($v as $k => $_) { $v[$k] = trim((string) ($_POST[$k] ?? '')); }
    $password = (string) ($_POST['password'] ?? '');

    if (!in_array($v['role'], ['siswa', 'pembimbing'], true))      $v['role'] = 'siswa';
    $siswa = $v['role'] === 'siswa';

    if (!csrf_valid($_POST['csrf'] ?? null))                       $errors[] = 'Sesi tidak valid, muat ulang halaman.';
    if (!preg_match('/^[A-Za-z0-9._-]{3,50}$/', $v['username']))   $errors[] = 'Username 3-50 karakter: huruf, angka, titik, garis bawah, atau strip.';
    if (strlen($password) < 6)                                     $errors[] = 'Password minimal 6 karakter.';
    if ($v['nama'] === '' || mb_strlen($v['nama']) > 100)          $errors[] = 'Nama lengkap wajib diisi (maksimal 100 karakter).';
    if ($siswa) {
        if (!preg_match('/^\d{4,20}$/', $v['nis']))                $errors[] = 'NIS harus berupa angka (4-20 digit).';
        if ($v['kelas'] === '' || mb_strlen($v['kelas']) > 30)     $errors[] = 'Kelas wajib diisi, contoh: XI IPA 2.';
        if (!preg_match('/^\d{4}$/', $v['angkatan']))              $errors[] = 'Tahun angkatan harus 4 digit, contoh: 2025.';
    }

    if (!$errors) {
        if ($siswa) {
            $st = $pdo->prepare('SELECT username, nis FROM users WHERE username = ? OR nis = ? LIMIT 1');
            $st->execute([$v['username'], $v['nis']]);
        } else {
            $st = $pdo->prepare('SELECT username, nis FROM users WHERE username = ? LIMIT 1');
            $st->execute([$v['username']]);
        }
        if ($dup = $st->fetch()) {
            $errors[] = $dup['username'] === $v['username'] ? 'Username sudah dipakai.' : 'NIS sudah terdaftar.';
        }
    }

    if (!$errors) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        try {
            if ($siswa) {
                $kelas = str_starts_with($v['kelas'], 'Kelas ') ? $v['kelas'] : 'Kelas ' . $v['kelas'];
                $pdo->prepare("INSERT INTO users (username, password, nama, role, nis, angkatan, kelas) VALUES (?,?,?,'siswa',?,?,?)")
                    ->execute([$v['username'], $hash, $v['nama'], $v['nis'], $v['angkatan'], $kelas]);
            } else {
                try {
                    $pdo->prepare("INSERT INTO users (username, password, nama, role) VALUES (?,?,?,'pembimbing')")
                        ->execute([$v['username'], $hash, $v['nama']]);
                } catch (PDOException $ex) {   // kolom lain wajib diisi -> isi NULL
                    $pdo->prepare("INSERT INTO users (username, password, nama, role, nis, angkatan, kelas) VALUES (?,?,?,'pembimbing',NULL,NULL,NULL)")
                        ->execute([$v['username'], $hash, $v['nama']]);
                }
            }
            $sukses = 'Akun ' . $v['role'] . ' "' . $v['username'] . '" berhasil dibuat. Sampaikan username dan password-nya, dan pilih peran ' . ucfirst($v['role']) . ' saat login.';
            $v = ['username' => '', 'nama' => '', 'nis' => '', 'kelas' => '', 'angkatan' => date('Y'), 'role' => $v['role']];
        } catch (PDOException $ex) {
            $errors[] = 'Gagal menyimpan akun. Periksa struktur tabel users (tab Structure di phpMyAdmin).';
        }
    }
}

pembimbing_header('Tambah Akun', 'anggota', $me);
?>
<style>
.ba-card{max-width:640px;margin:0 auto;padding:28px;background:#fff;border:1px solid #e2e8f0;border-radius:12px}
.ba-card h2{margin:0 0 4px;font:700 20px/28px 'Nunito Sans',sans-serif;color:#0f172a}
.ba-card > p{margin:0 0 20px;color:#64748b;font:400 14px/20px 'Nunito Sans',sans-serif}
.ba-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.ba-grid .full{grid-column:1 / -1}
.ba-card label{display:block;margin-bottom:6px;font:600 13px/18px 'Nunito Sans',sans-serif;color:#334155}
.ba-card input{width:100%;box-sizing:border-box;padding:10px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font:400 14px/20px 'Nunito Sans',sans-serif}
.ba-card input:focus{outline:2px solid #bfe0d0;border-color:#1a6b45}
.ba-role{display:flex;gap:20px;margin-bottom:16px}
.ba-role label{display:flex;align-items:center;gap:8px;margin:0;cursor:pointer}
.ba-role input{width:auto}
.ba-btn{margin-top:20px;padding:11px 24px;border:0;border-radius:8px;background:#023724;color:#fff;cursor:pointer;font:700 14px/20px 'Nunito Sans',sans-serif}
.ba-btn:hover{background:#0a6244}
.ba-ok{margin-bottom:16px;padding:12px 14px;border-radius:8px;background:#eaf6ef;border:1px solid #bfe0d0;color:#14532d;font:600 14px/20px 'Nunito Sans',sans-serif}
.ba-err{margin-bottom:16px;padding:12px 14px;border-radius:8px;background:#fef2f2;border:1px solid #fecaca;color:#991b1b;font:600 14px/20px 'Nunito Sans',sans-serif}
@media (max-width:640px){.ba-grid{grid-template-columns:1fr}}
</style>

<section class="ba-card">
    <h2>Tambah Akun</h2>
    <p>Buat akun siswa atau pembimbing. Akun baru langsung bisa dipakai login.</p>

    <?php if ($sukses): ?><div class="ba-ok"><?= e($sukses) ?></div><?php endif; ?>
    <?php foreach ($errors as $m): ?><div class="ba-err"><?= e($m) ?></div><?php endforeach; ?>

    <form method="post" autocomplete="off" novalidate>
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <div class="ba-role">
            <label><input type="radio" name="role" value="siswa" <?= $v['role'] === 'siswa' ? 'checked' : '' ?>> Siswa</label>
            <label><input type="radio" name="role" value="pembimbing" <?= $v['role'] === 'pembimbing' ? 'checked' : '' ?>> Pembimbing</label>
        </div>
        <div class="ba-grid">
            <div class="full"><label for="nama">Nama lengkap</label><input id="nama" name="nama" maxlength="100" value="<?= e($v['nama']) ?>" required></div>
            <div class="js-siswa"><label for="nis">NIS</label><input id="nis" name="nis" inputmode="numeric" maxlength="20" value="<?= e($v['nis']) ?>" required></div>
            <div class="js-siswa"><label for="kelas">Kelas</label><input id="kelas" name="kelas" placeholder="XI IPA 2" maxlength="30" value="<?= e($v['kelas']) ?>" required></div>
            <div class="js-siswa"><label for="angkatan">Tahun angkatan</label><input id="angkatan" name="angkatan" inputmode="numeric" maxlength="4" value="<?= e($v['angkatan']) ?>" required></div>
            <div class="js-siswa"></div>
            <div><label for="username">Username</label><input id="username" name="username" maxlength="50" value="<?= e($v['username']) ?>" required></div>
            <div><label for="password">Password</label><input id="password" name="password" type="password" minlength="6" required></div>
        </div>
        <button type="submit" class="ba-btn">Buat Akun</button>
    </form>
</section>
<script>
function ubahPeran() {
    var siswa = document.querySelector('input[name=role]:checked').value === 'siswa';
    document.querySelectorAll('.js-siswa').forEach(function (el) { el.style.display = siswa ? '' : 'none'; });
}
document.querySelectorAll('input[name=role]').forEach(function (r) { r.addEventListener('change', ubahPeran); });
ubahPeran();
</script>
<?php pembimbing_footer();
