<?php
/**
 * profil.php - Halaman Profil Siswa (Figma: UI PROFIL & UI PROFIL EDIT)
 *
 * PENTING: 4 baris "BOOTSTRAP" di bawah harus disamakan dengan halaman siswa
 * yang sudah jalan (mis. tugas.php): cara memanggil koneksi DB, cek login,
 * dan $user. Asumsi saya: $pdo (PDO), $user (array users yang login).
 */

// ---- BOOTSTRAP (samakan dengan tugas.php) ----
require_once __DIR__ . '/config/database.php';       // fungsi db()
require_once __DIR__ . '/includes/auth.php';         // fungsi login + e()
require_once __DIR__ . '/includes/siswa_layout.php'; // siswa_header(), siswa_footer(), initials()
$pdo  = db();
$user = require_role('siswa');
// Ambil data lengkap dari tabel users (nis, kelas, dst.), digabung dengan data sesi
$st = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$st->execute([$user['id']]);
$user = array_merge($user, $st->fetch(PDO::FETCH_ASSOC) ?: []);
// ----------------------------------------------


$edit   = isset($_GET['edit']) && $_GET['edit'] === '1';
$errors = [];
$notice = $_SESSION['pf_notice'] ?? '';
unset($_SESSION['pf_notice']);

// Data tambahan (tabel siswa_profil)
$st = $pdo->prepare('SELECT email, telepon, alamat, bio FROM siswa_profil WHERE user_id = ?');
$st->execute([$user['id']]);
$extra = $st->fetch(PDO::FETCH_ASSOC) ?: ['email' => '', 'telepon' => '', 'alamat' => '', 'bio' => ''];

// Ekskul = pendaftaran yang disetujui (sesuaikan nama tabel/kolom jika berbeda)
$st = $pdo->prepare("SELECT e.nama FROM pendaftaran p JOIN ekskul e ON e.id = p.ekskul_id
                     WHERE p.siswa_id = ? AND p.status = 'disetujui' ORDER BY e.nama");
$st->execute([$user['id']]);
$ekskulList = $st->fetchAll(PDO::FETCH_COLUMN);
$ekskul = $ekskulList ? implode(' & ', $ekskulList) : '-';

$form = [
    'nama'    => $user['nama'],
    'email'   => $extra['email'],
    'telepon' => $extra['telepon'],
    'alamat'  => $extra['alamat'],
    'bio'     => $extra['bio'] ?? '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $edit = true;
    if (!csrf_valid($_POST['csrf'] ?? null)) {
        $errors[] = 'Sesi formulir tidak valid. Muat ulang halaman lalu coba lagi.';
    }
    foreach (['nama', 'email', 'telepon', 'alamat', 'bio'] as $k) {
        $form[$k] = trim((string)($_POST[$k] ?? ''));
    }
    if ($form['nama'] === '' || mb_strlen($form['nama']) > 100) $errors[] = 'Nama lengkap wajib diisi (maksimal 100 karakter).';
    if ($form['email'] !== '' && (!filter_var($form['email'], FILTER_VALIDATE_EMAIL) || mb_strlen($form['email']) > 120))
        $errors[] = 'Format email tidak valid.';
    if ($form['telepon'] !== '' && !preg_match('/^\+?[0-9][0-9\-\s]{7,18}$/', $form['telepon']))
        $errors[] = 'Nomor telepon harus 8-19 karakter berisi angka, spasi, atau tanda hubung.';
    if (mb_strlen($form['alamat']) > 255) $errors[] = 'Alamat maksimal 255 karakter.';
    if (mb_strlen($form['bio']) > 500)    $errors[] = 'Bio maksimal 500 karakter.';

    if (!$errors) {
        $pdo->beginTransaction();
        try {
            $pdo->prepare('UPDATE users SET nama = ? WHERE id = ?')->execute([$form['nama'], $user['id']]);
            $pdo->prepare('INSERT INTO siswa_profil (user_id, email, telepon, alamat, bio) VALUES (?,?,?,?,?)
                           ON DUPLICATE KEY UPDATE email=VALUES(email), telepon=VALUES(telepon), alamat=VALUES(alamat), bio=VALUES(bio)')
                ->execute([$user['id'], $form['email'], $form['telepon'], $form['alamat'], $form['bio']]);
            $pdo->commit();
            $_SESSION['user']['nama'] = $form['nama'];   // nama di pojok kanan atas ikut berubah
            $_SESSION['pf_notice'] = 'Profil berhasil disimpan.';
            header('Location: profil.php');
            exit;
        } catch (Throwable $ex) {
            $pdo->rollBack();
            $errors[] = 'Perubahan gagal disimpan. Coba lagi.';
        }
    }
}

siswa_header('Profil', 'profil', $user);   // 'profil' tidak ada di menu, jadi tidak ada item sidebar yang aktif
?>
<div class="pf-head">
    <h1>Profil Saya</h1>
    <p>Informasi akun dan data diri</p>
</div>

<div class="pf-grid">
    <aside class="pf-card pf-side">
        <div class="pf-avatar"><?= e(initials($user['nama'])) ?></div>
        <h2><?= e($user['nama']) ?></h2>
        <p class="kelas"><?= e($user['kelas'] ?? '') ?></p>
        <span class="pf-badge"><?= e($user['jabatan'] ?? 'Anggota Aktif') ?></span>
        <div class="pf-meta"><small>NIS</small><b class="nis"><?= e($user['nis'] ?? '') ?></b></div>
        <div class="pf-meta"><small>Ekstrakurikuler</small><b><?= e($ekskul) ?></b></div>
    </aside>

    <section class="pf-card pf-main">
        <?php if ($notice): ?><div class="pf-ok"><?= e($notice) ?></div><?php endif; ?>
        <?php if ($errors): ?>
            <div class="pf-err"><ul><?php foreach ($errors as $m): ?><li><?= e($m) ?></li><?php endforeach; ?></ul></div>
        <?php endif; ?>

        <?php if (!$edit): ?>
            <div class="pf-actions"><a class="pf-btn ghost" href="ubah_password.php">Ubah Password</a><a class="pf-btn" href="profil.php?edit=1">✎ Edit Profil</a></div>
            <div class="pf-fields">
                <div class="pf-field"><span class="lbl">Nama Lengkap</span><div class="val"><?= e($user['nama']) ?></div></div>
                <div class="pf-field"><span class="lbl">NIS</span><div class="val"><?= e($user['nis'] ?? '') ?></div></div>
                <div class="pf-field"><span class="lbl">Kelas</span><div class="val"><?= e($user['kelas'] ?? '') ?></div></div>
                <div class="pf-field"><span class="lbl">Angkatan</span><div class="val"><?= e($user['angkatan'] ?? '-') ?></div></div>
                <div class="pf-field"><span class="lbl">Email</span><div class="val"><?= e($extra['email'] ?: '-') ?></div></div>
                <div class="pf-field"><span class="lbl">Telepon</span><div class="val"><?= e($extra['telepon'] ?: '-') ?></div></div>
                <div class="pf-field"><span class="lbl">Jabatan</span><div class="val"><?= e($user['jabatan'] ?? 'Anggota Aktif') ?></div></div>
                <div class="pf-field"><span class="lbl">Ekstrakurikuler</span><div class="val"><?= e($ekskul) ?></div></div>
                <div class="pf-field full"><span class="lbl">Alamat</span><div class="val"><?= e($extra['alamat'] ?: '-') ?></div></div>
                <div class="pf-field full"><span class="lbl">Bio</span><div class="val bio"><?= e($extra['bio'] ?: '-') ?></div></div>
            </div>
        <?php else: ?>
            <form method="post" action="profil.php?edit=1" novalidate>
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                <div class="pf-actions">
                    <a class="pf-btn ghost" href="profil.php">Batal</a>
                    <button class="pf-btn primary" type="submit">Simpan Perubahan</button>
                </div>
                <div class="pf-fields edit">
                    <div class="pf-field"><label for="nama">Nama Lengkap</label><input id="nama" name="nama" maxlength="100" value="<?= e($form['nama']) ?>" required></div>
                    <div class="pf-field"><label>NIS</label><input value="<?= e($user['nis'] ?? '') ?>" readonly></div>
                    <div class="pf-field"><label>Kelas</label><input value="<?= e($user['kelas'] ?? '') ?>" readonly></div>
                    <div class="pf-field"><label>Angkatan</label><input value="<?= e($user['angkatan'] ?? '-') ?>" readonly></div>
                    <div class="pf-field"><label for="email">Email</label><input id="email" name="email" type="email" maxlength="120" value="<?= e($form['email']) ?>"></div>
                    <div class="pf-field"><label for="telepon">Telepon</label><input id="telepon" name="telepon" maxlength="20" value="<?= e($form['telepon']) ?>"></div>
                    <div class="pf-field"><label>Jabatan</label><input value="<?= e($user['jabatan'] ?? 'Anggota Aktif') ?>" readonly></div>
                    <div class="pf-field"><label>Ekstrakurikuler</label><input value="<?= e($ekskul) ?>" readonly></div>
                    <div class="pf-field full"><label for="alamat">Alamat</label><input id="alamat" name="alamat" maxlength="255" value="<?= e($form['alamat']) ?>"></div>
                    <div class="pf-field full"><label for="bio">Bio</label><textarea id="bio" name="bio" maxlength="500"><?= e($form['bio']) ?></textarea></div>
                </div>
            </form>
        <?php endif; ?>
    </section>
</div>
<script>
// Cegah klik ganda saat menyimpan, dan beri tanda bahwa tombol sedang bekerja
document.querySelectorAll('form').forEach(function (f) {
    f.addEventListener('submit', function () {
        var b = f.querySelector('button[type=submit]');
        if (b) { b.classList.add('loading'); b.textContent = 'Menyimpan...'; }
    });
});
</script>
<?php siswa_footer(); ?>
