<?php
declare(strict_types=1);

/**
 * Profil Pembimbing (Figma: UI PROFIL PEMBIMBING & UI PROFIL EDIT)
 * Tampilan biasa: profil_pembimbing.php   |   Mode edit: profil_pembimbing.php?edit=1
 */
require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/pembimbing_layout.php';

const INSTANSI    = 'SMA Trisula Praja';
const MAKS_FOTO   = 2 * 1024 * 1024;   // 2 MB

$session = require_role('pembimbing');
$pdo     = db();
$me      = pembimbing_user($pdo, (int) $session['id']);
$uid     = (int) $me['id'];

$edit   = ($_GET['edit'] ?? '') === '1';
$errors = [];
$flash  = $_SESSION['pbp_flash'] ?? null;
unset($_SESSION['pbp_flash']);

$nama = $me['nama'];

// ===== Proses form =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = (string) ($_POST['aksi'] ?? '');
    try {
        if (!csrf_valid($_POST['csrf'] ?? null)) {
            throw new RuntimeException('Sesi tidak valid, muat ulang halaman.');
        }

        if ($aksi === 'simpan') {
            $edit = true;
            $nama = trim((string) ($_POST['nama'] ?? ''));
            if ($nama === '' || mb_strlen($nama) > 100) {
                $errors[] = 'Nama lengkap wajib diisi (maksimal 100 karakter).';
            } else {
                $pdo->prepare('UPDATE users SET nama = ? WHERE id = ?')->execute([$nama, $uid]);
                $_SESSION['user']['nama'] = $nama;
                $_SESSION['pbp_flash'] = ['ok', 'Profil berhasil disimpan.'];
                header('Location: profil_pembimbing.php');
                exit;
            }
        } elseif ($aksi === 'foto') {
            $f = $_FILES['foto'] ?? null;
            if (!$f || $f['error'] !== UPLOAD_ERR_OK) {
                $besar = $f && in_array($f['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true);
                throw new RuntimeException($besar ? 'Ukuran foto melebihi batas (maks 2 MB).' : 'Foto gagal diunggah, coba lagi.');
            }
            if ($f['size'] > MAKS_FOTO) throw new RuntimeException('Ukuran foto melebihi batas (maks 2 MB).');

            $info = @getimagesize($f['tmp_name']);
            $ext  = $info ? ([IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'][$info[2]] ?? null) : null;
            if (!$ext) throw new RuntimeException('Format foto harus JPG, PNG, atau WEBP.');

            $dir = __DIR__ . '/uploads/profil';
            if (!is_dir($dir) && !mkdir($dir, 0755, true)) throw new RuntimeException('Folder upload tidak dapat dibuat.');

            $simpan = 'pb' . $uid . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
            if (!move_uploaded_file($f['tmp_name'], $dir . '/' . $simpan)) throw new RuntimeException('Foto gagal disimpan.');

            $lama = (string) ($me['foto'] ?? '');
            $pdo->prepare('INSERT INTO pembimbing_profil (user_id, foto) VALUES (?, ?)
                           ON DUPLICATE KEY UPDATE foto = VALUES(foto)')->execute([$uid, 'uploads/profil/' . $simpan]);
            if (str_starts_with($lama, 'uploads/profil/') && is_file(__DIR__ . '/' . $lama)) { @unlink(__DIR__ . '/' . $lama); }

            $_SESSION['pbp_flash'] = ['ok', 'Foto profil berhasil diperbarui.'];
            header('Location: profil_pembimbing.php');
            exit;
        }
    } catch (RuntimeException $ex) {
        $_SESSION['pbp_flash'] = ['err', $ex->getMessage()];
        header('Location: profil_pembimbing.php');
        exit;
    } catch (PDOException $ex) {
        $_SESSION['pbp_flash'] = ['err', 'Gagal menyimpan. Pastikan profil_pembimbing.sql sudah dijalankan.'];
        header('Location: profil_pembimbing.php');
        exit;
    }
}

// ===== Data binaan =====
$st = $pdo->prepare('SELECT nama FROM ekskul WHERE pembimbing_id = ? ORDER BY nama');
$st->execute([$uid]);
$namaEkskul = $st->fetchAll(PDO::FETCH_COLUMN);
$jmlKelas   = count($namaEkskul);

$pendek = array_map(static fn(string $n): string => explode(' ', trim($n))[0], $namaEkskul);
if (count($pendek) > 1) {
    $akhir  = array_pop($pendek);
    $binaan = implode(', ', $pendek) . ' dan ' . $akhir;
} else {
    $binaan = $pendek ? $pendek[0] : '-';
}

$st = $pdo->prepare("SELECT COUNT(*) FROM pendaftaran p JOIN ekskul e ON e.id = p.ekskul_id
                     WHERE e.pembimbing_id = ? AND p.status = 'disetujui'");
$st->execute([$uid]);
$totalSiswa = (int) $st->fetchColumn();

$foto = (string) ($me['foto'] ?? '');

pembimbing_header('Profil', 'profil', $me);
?>
<link rel="stylesheet" href="assets/css/pembimbing_profil.css?v=<?= (int) @filemtime(__DIR__ . '/assets/css/pembimbing_profil.css') ?>">
<section class="pbp-card">
    <header class="pbp-band">
        <div>
            <h2>Profil Pembimbing</h2>
            <p>Informasi akun dan data pembimbing</p>
        </div>
        <div class="pbp-band-act">
            <?php if ($edit): ?>
                <a class="pbp-cancel" href="profil_pembimbing.php">Batal</a>
                <button type="submit" form="pbp-form" class="pbp-btn">Simpan Perubahan</button>
            <?php else: ?>
                <a class="pbp-cancel" href="ubah_password.php">Ubah Password</a>
                <a class="pbp-btn" href="profil_pembimbing.php?edit=1">Edit Profil</a>
            <?php endif; ?>
        </div>
    </header>

    <div class="pbp-wrap">
        <?php if ($flash): ?>
            <div class="pbp-flash <?= $flash[0] === 'ok' ? 'pbp-ok' : 'pbp-err' ?>" role="status"><?= e($flash[1]) ?></div>
        <?php endif; ?>
        <?php if ($errors): ?>
            <div class="pbp-flash pbp-err" role="alert"><?php foreach ($errors as $m): ?><div><?= e($m) ?></div><?php endforeach; ?></div>
        <?php endif; ?>

        <div class="pbp-body">
            <aside class="pbp-side">
                <div class="pbp-ava">
                    <?php if ($foto !== ''): ?>
                        <img src="<?= e($foto) ?>" alt="Foto <?= e($me['nama']) ?>">
                    <?php else: ?>
                        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8.5" r="4.2"/><path d="M3.5 22c0-4.7 3.8-8 8.5-8s8.5 3.3 8.5 8z"/></svg>
                    <?php endif; ?>
                </div>
                <h3><?= e($me['nama']) ?></h3>
                <span class="pbp-pill">Pembimbing</span>

                <form method="post" action="profil_pembimbing.php" enctype="multipart/form-data">
                    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="aksi" value="foto">
                    <label class="pbp-ghost">
                        <input type="file" name="foto" accept="image/jpeg,image/png,image/webp" hidden onchange="this.form.submit()">
                        Ubah Foto
                    </label>
                </form>

                <dl class="pbp-stats">
                    <div><dt>Ekstrakurikuler Binaan</dt><dd><?= $jmlKelas ?> Kelas</dd></div>
                    <div><dt>Total Siswa</dt><dd><?= $totalSiswa ?> Siswa</dd></div>
                </dl>
            </aside>

            <section class="pbp-main">
                <div class="pbp-title">
                    <h3>Informasi Pribadi</h3>
                    <span class="pbp-status"><i></i>Akun Aktif</span>
                </div>
                <p class="pbp-sub">Data utama yang digunakan pada portal ekstrakurikuler.</p>

                <?php if ($edit): ?><form id="pbp-form" method="post" action="profil_pembimbing.php?edit=1" novalidate>
                    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="aksi" value="simpan"><?php endif; ?>

                <div class="pbp-fields">
                    <div class="pbp-field">
                        <label for="nama">Nama Lengkap</label>
                        <?php if ($edit): ?>
                            <input id="nama" name="nama" maxlength="100" value="<?= e($nama) ?>" class="pbp-in pbp-in--edit" required>
                        <?php else: ?>
                            <div class="pbp-in"><?= e($me['nama']) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="pbp-field"><label>Peran</label><div class="pbp-in">Pembimbing</div></div>
                    <div class="pbp-field"><label>Instansi</label><div class="pbp-in"><?= e(INSTANSI) ?></div></div>
                    <div class="pbp-field"><label>Ekstrakurikuler Binaan</label><div class="pbp-in"><?= e($binaan) ?></div></div>
                </div>

                <?php if ($edit): ?></form><?php endif; ?>

                <div class="pbp-info">
                    <span class="pbp-num"><?= $jmlKelas ?></span>
                    <div>
                        <b>Program Binaan Aktif</b>
                        <p>Mengelola kegiatan, kehadiran, tugas, dan penilaian siswa pada kelas <?= e($binaan) ?>.</p>
                    </div>
                </div>
            </section>
        </div>
    </div>
</section>
<?php pembimbing_footer();
