<?php
declare(strict_types=1);

require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/siswa_layout.php';
require __DIR__ . '/includes/ekskul.php';

$session = require_role('siswa');
$pdo     = db();
$user    = load_user($pdo, (int) $session['id']);

$id     = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$ekskul = find_ekskul($pdo, $id);
if (!$ekskul) { header('Location: daftar_ekskul.php'); exit; }

// Sudah pernah daftar -> ke halaman detail
if (find_pendaftaran($pdo, (int) $user['id'], $id)) { header('Location: ekskul_detail.php?id=' . $id); exit; }

$penuh  = $ekskul['terisi'] >= $ekskul['kuota'];
$errors = [];
$old    = ['nis' => $user['nis'], 'hp_siswa' => '', 'nama_ortu' => '', 'hp_ortu' => '', 'alasan' => '', 'pengalaman' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($old as $k => $_) { $old[$k] = trim($_POST[$k] ?? ''); }

    if (!csrf_valid($_POST['csrf'] ?? null)) $errors[] = 'Sesi tidak valid, muat ulang halaman.';
    if ($penuh)                              $errors[] = 'Kuota ekskul ini sudah penuh.';
    if ($old['nis'] === '')                  $errors['nis'] = 'NIS wajib diisi.';
    if (!preg_match('/^08\d{8,12}$/', $old['hp_siswa'])) $errors['hp_siswa'] = 'Nomor HP tidak valid (contoh: 08123456789).';
    if ($old['nama_ortu'] === '')            $errors['nama_ortu'] = 'Nama orang tua/wali wajib diisi.';
    if (!preg_match('/^08\d{8,12}$/', $old['hp_ortu']))  $errors['hp_ortu'] = 'Nomor HP tidak valid.';
    if ($old['alasan'] === '')               $errors['alasan'] = 'Alasan bergabung wajib diisi.';
    if (empty($_POST['setuju']))             $errors['setuju'] = 'Anda harus menyetujui pernyataan ini.';

    if (!$errors) {
        try {
            $ins = $pdo->prepare('INSERT INTO pendaftaran (siswa_id, ekskul_id, nis, hp_siswa, nama_ortu, hp_ortu, alasan, pengalaman)
                                  VALUES (?,?,?,?,?,?,?,?)');
            $ins->execute([$user['id'], $id, $old['nis'], $old['hp_siswa'], $old['nama_ortu'], $old['hp_ortu'], $old['alasan'], $old['pengalaman'] ?: null]);
            header('Location: daftar_ekskul_sukses.php?id=' . $id);
            exit;
        } catch (PDOException $ex) {
            $errors[] = 'Pendaftaran gagal disimpan. Coba lagi.';
        }
    }
}

function err(array $errors, string $key): string
{
    return isset($errors[$key]) ? '<small class="err">' . e($errors[$key]) . '</small>' : '';
}

siswa_header('Daftar Ekskul', 'ekskul', $user);
?>
<a href="daftar_ekskul.php" class="back">‹ Kembali</a>

<div class="form-layout">
    <form method="post" class="panel form-card" novalidate>
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="id" value="<?= $id ?>">

        <header class="form-head">
            <h3>Formulir Pendaftaran — <?= e($ekskul['nama']) ?></h3>
            <p>Lengkapi data berikut. Kolom bertanda * wajib diisi.</p>
        </header>

        <div class="form-body">
            <?php foreach ($errors as $k => $msg): if (is_int($k)): ?>
                <div class="alert-box"><?= e($msg) ?></div>
            <?php endif; endforeach; ?>

            <h4 class="sec"><i>1</i> Data Siswa</h4>
            <div class="grid-2">
                <div class="fld"><label>Nama Lengkap</label><input type="text" value="<?= e($user['nama']) ?>" readonly></div>
                <div class="fld"><label>Kelas</label><input type="text" value="<?= e(trim(str_replace('Kelas ', '', (string) $user['kelas']))) ?>" readonly></div>
                <div class="fld"><label for="nis">NIS *</label><input id="nis" name="nis" type="text" placeholder="Contoh: 2023104" value="<?= e($old['nis']) ?>"><?= err($errors, 'nis') ?></div>
                <div class="fld"><label>Tanggal Daftar</label><input type="text" value="<?= e(tanggal_id(date('Y-m-d'))) ?>" readonly></div>
            </div>

            <h4 class="sec"><i>2</i> Kontak</h4>
            <div class="grid-2">
                <div class="fld"><label for="hp_siswa">No. HP Siswa *</label><input id="hp_siswa" name="hp_siswa" type="tel" placeholder="08xxxxxxxxxx" value="<?= e($old['hp_siswa']) ?>"><?= err($errors, 'hp_siswa') ?></div>
                <span></span>
                <div class="fld"><label for="nama_ortu">Nama Orang Tua/Wali *</label><input id="nama_ortu" name="nama_ortu" type="text" placeholder="Nama lengkap" value="<?= e($old['nama_ortu']) ?>"><?= err($errors, 'nama_ortu') ?></div>
                <div class="fld"><label for="hp_ortu">No. HP Orang Tua/Wali *</label><input id="hp_ortu" name="hp_ortu" type="tel" placeholder="08xxxxxxxxxx" value="<?= e($old['hp_ortu']) ?>"><?= err($errors, 'hp_ortu') ?></div>
            </div>

            <h4 class="sec"><i>3</i> Motivasi</h4>
            <div class="fld"><label for="alasan">Alasan bergabung *</label>
                <textarea id="alasan" name="alasan" rows="3" placeholder="Mengapa Anda ingin bergabung dengan <?= e(explode(' ', $ekskul['nama'])[0]) ?>?"><?= e($old['alasan']) ?></textarea><?= err($errors, 'alasan') ?></div>
            <div class="fld"><label for="pengalaman">Pengalaman terkait (opsional)</label>
                <textarea id="pengalaman" name="pengalaman" rows="2" placeholder="Prestasi atau pengalaman sebelumnya"><?= e($old['pengalaman']) ?></textarea></div>

            <h4 class="sec"><i>4</i> Konfirmasi</h4>
            <label class="agree">
                <input type="checkbox" name="setuju" value="1" <?= !empty($_POST['setuju']) ? 'checked' : '' ?>>
                <span>Saya bersedia mengikuti jadwal kegiatan, menaati tata tertib ekstrakurikuler, dan telah mendapat izin orang tua/wali.</span>
            </label>
            <?= err($errors, 'setuju') ?>
        </div>

        <footer class="form-foot">
            <a href="daftar_ekskul.php" class="btn-light">Batal</a>
            <button type="submit" class="btn-primary" <?= $penuh ? 'disabled' : '' ?>>✓ Kirim Pendaftaran</button>
        </footer>
    </form>

    <aside class="side">
        <div class="side-hero">
            <small>EKSKUL DIPILIH</small>
            <h3><?= e($ekskul['nama']) ?></h3>
            <p><?= e($ekskul['deskripsi']) ?></p>
            <ul>
                <li><?= icon('absensi') ?> <?= e($ekskul['pembimbing']) ?></li>
                <li><?= icon('jadwal') ?> <?= e(jadwal_label($ekskul)) ?></li>
                <li><?= icon('ekskul') ?> Sisa kuota: <?= max(0, (int) $ekskul['kuota'] - (int) $ekskul['terisi']) ?> dari <?= (int) $ekskul['kuota'] ?></li>
            </ul>
        </div>
        <div class="panel side-card">
            <h4>Alur pendaftaran</h4>
            <ol class="steps">
                <li class="on"><i>1</i> Isi &amp; kirim formulir</li>
                <li><i>2</i> Pembimbing meninjau</li>
                <li><i>3</i> Notifikasi hasil</li>
                <li><i>4</i> Mulai kegiatan</li>
            </ol>
        </div>
    </aside>
</div>
<?php siswa_footer();
