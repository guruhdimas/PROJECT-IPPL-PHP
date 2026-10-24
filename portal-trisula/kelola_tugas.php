<?php
declare(strict_types=1);

require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/ekskul.php';
require __DIR__ . '/includes/pembimbing_layout.php';

const TUGAS_NILAI = ['A+', 'A', 'A-', 'B+', 'B', 'B-', 'C+', 'C', 'D'];

$session = require_role('pembimbing');
$pdo     = db();
$me      = pembimbing_user($pdo, (int) $session['id']);
$pid     = (int) $me['id'];
$today   = date('Y-m-d');

function tg_url(array $p = []): string
{
    $q = http_build_query(array_filter($p, static fn ($v) => $v !== '' && $v !== null && $v !== 0));
    return 'kelola_tugas.php' . ($q !== '' ? '?' . $q : '');
}

// ===================== UNDUH FILE PENGUMPULAN =====================
if (isset($_GET['unduh'])) {
    $st = $pdo->prepare('SELECT g.nama_file, g.path_file
                         FROM pengumpulan g
                         JOIN ekskul_tugas t ON t.id = g.tugas_id
                         JOIN ekskul e ON e.id = t.ekskul_id
                         WHERE g.id = ? AND e.pembimbing_id = ?');
    $st->execute([(int) $_GET['unduh'], $pid]);
    $f    = $st->fetch();
    $base = realpath(__DIR__ . '/uploads/tugas');
    $full = $f ? realpath(__DIR__ . '/' . $f['path_file']) : false;
    if (!$f || $base === false || $full === false
        || !str_starts_with((string) $f['path_file'], 'uploads/tugas/')
        || !str_starts_with($full, $base . DIRECTORY_SEPARATOR) || !is_file($full)) {
        http_response_code(404);
        exit('File tidak ditemukan.');
    }
    $nama = (string) preg_replace('/[^\w.\- ()]/u', '_', (string) $f['nama_file']);
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="' . $nama . '"');
    header('X-Content-Type-Options: nosniff');
    header('Content-Length: ' . filesize($full));
    readfile($full);
    exit;
}

// Ekskul yang diampu pembimbing ini
$st = $pdo->prepare('SELECT id, nama FROM ekskul WHERE pembimbing_id = ? ORDER BY nama');
$st->execute([$pid]);
$ekskulku = $st->fetchAll();
$idEkskul = array_map(static fn ($r) => (int) $r['id'], $ekskulku);

// ===================== AKSI (POST) =====================
$errBuat = [];
$in      = ['ekskul_id' => 0, 'judul' => '', 'keterangan' => '', 'deadline' => ''];
$bukaBuat = isset($_GET['buat']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = (string) ($_POST['aksi'] ?? '');

    if ($aksi === 'buat') {
        $bukaBuat = true;
        $in = [
            'ekskul_id'  => (int) ($_POST['ekskul_id'] ?? 0),
            'judul'      => trim((string) ($_POST['judul'] ?? '')),
            'keterangan' => trim((string) ($_POST['keterangan'] ?? '')),
            'deadline'   => trim((string) ($_POST['deadline'] ?? '')),
        ];
        if (!csrf_valid($_POST['csrf'] ?? null))      $errBuat[] = 'Sesi tidak valid, muat ulang halaman.';
        if (!in_array($in['ekskul_id'], $idEkskul, true)) $errBuat[] = 'Pilih ekstrakurikuler yang Anda ampu.';
        if (mb_strlen($in['judul']) < 3 || mb_strlen($in['judul']) > 150) $errBuat[] = 'Judul tugas harus 3–150 karakter.';
        if (mb_strlen($in['keterangan']) > 255)       $errBuat[] = 'Deskripsi maksimal 255 karakter.';
        $d = DateTime::createFromFormat('Y-m-d', $in['deadline']);
        if (!$d || $d->format('Y-m-d') !== $in['deadline']) {
            $errBuat[] = 'Deadline tidak valid.';
        } elseif ($in['deadline'] < $today) {
            $errBuat[] = 'Deadline tidak boleh sebelum hari ini.';
        }

        if (!$errBuat) {
            try {
                $pdo->prepare('INSERT INTO ekskul_tugas (ekskul_id, judul, keterangan, deadline) VALUES (?,?,?,?)')
                    ->execute([$in['ekskul_id'], $in['judul'], $in['keterangan'], $in['deadline']]);
                $_SESSION['flash'] = ['ok', 'Tugas berhasil dibuat dan langsung tampil di halaman siswa.'];
                header('Location: ' . tg_url());
                exit;
            } catch (PDOException $ex) {
                $errBuat[] = 'Gagal menyimpan tugas, coba lagi.';
            }
        }
    } elseif ($aksi === 'nilai') {
        $gid = (int) ($_POST['pengumpulan_id'] ?? 0);
        $tid = 0;
        try {
            if (!csrf_valid($_POST['csrf'] ?? null)) throw new RuntimeException('Sesi tidak valid, muat ulang halaman.');
            $nilai = (string) ($_POST['nilai'] ?? '');
            $pesan = trim((string) ($_POST['pesan'] ?? ''));
            if (!in_array($nilai, TUGAS_NILAI, true)) throw new RuntimeException('Nilai tidak valid.');
            if (mb_strlen($pesan) > 255)              throw new RuntimeException('Pesan maksimal 255 karakter.');

            $st = $pdo->prepare('SELECT g.id, g.tugas_id
                                 FROM pengumpulan g
                                 JOIN ekskul_tugas t ON t.id = g.tugas_id
                                 JOIN ekskul e ON e.id = t.ekskul_id
                                 WHERE g.id = ? AND e.pembimbing_id = ?');
            $st->execute([$gid, $pid]);
            $g = $st->fetch();
            if (!$g) throw new RuntimeException('Pengumpulan tidak ditemukan.');
            $tid = (int) $g['tugas_id'];

            $pdo->prepare("UPDATE pengumpulan SET nilai = ?, pesan = ?, status = 'selesai', dinilai_at = NOW() WHERE id = ?")
                ->execute([$nilai, $pesan, $gid]);
            $_SESSION['flash'] = ['ok', 'Nilai tersimpan dan dikirim ke siswa.'];
        } catch (RuntimeException $ex) {
            $_SESSION['flash'] = ['err', $ex->getMessage()];
        } catch (PDOException $ex) {
            $_SESSION['flash'] = ['err', 'Gagal menyimpan nilai. Pastikan database/kelola_tugas.sql sudah dijalankan.'];
        }
        header('Location: ' . tg_url(['tugas' => $tid]));
        exit;
    }
}

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

// ===================== DATA TAMPILAN =====================
$st = $pdo->prepare("SELECT t.id, t.judul, t.keterangan, t.deadline, e.nama AS ekskul,
                            (SELECT COUNT(*) FROM pengumpulan g WHERE g.tugas_id = t.id) AS kumpul,
                            (SELECT COUNT(*) FROM pendaftaran p WHERE p.ekskul_id = t.ekskul_id AND p.status = 'disetujui') AS anggota
                     FROM ekskul_tugas t
                     JOIN ekskul e ON e.id = t.ekskul_id
                     WHERE e.pembimbing_id = ?
                     ORDER BY t.deadline, t.id");
$st->execute([$pid]);
$daftar = $st->fetchAll();

// Modal pengumpulan
$tid = (int) ($_GET['tugas'] ?? 0);
$modalTugas = null;
$rows = [];
foreach ($daftar as $t) {
    if ((int) $t['id'] === $tid) { $modalTugas = $t; break; }
}
if ($modalTugas) {
    $st = $pdo->prepare('SELECT g.id, g.nama_file, g.uploaded_at, g.nilai, g.pesan, u.nama
                         FROM pengumpulan g JOIN users u ON u.id = g.siswa_id
                         WHERE g.tugas_id = ? ORDER BY g.uploaded_at, g.id');
    $st->execute([$tid]);
    $rows = $st->fetchAll();
}

// Modal beri nilai
$beri = null;
$gidBeri = (int) ($_GET['beri'] ?? 0);
if ($modalTugas && $gidBeri) {
    foreach ($rows as $r) {
        if ((int) $r['id'] === $gidBeri) { $beri = $r; break; }
    }
}

pembimbing_header('Tugas', 'tugas', $me);

if ($flash): ?>
    <div class="pb-flash <?= $flash[0] === 'ok' ? 'ok' : 'err' ?>" role="status"><?= e($flash[1]) ?></div>
<?php endif; ?>

<section class="pb-panel pb-tg-panel">
    <div class="pb-tg-head">
        <div><h2>Daftar Tugas</h2><p>Buat tugas dan nilai pengumpulan</p></div>
        <?php if ($ekskulku): ?><a class="pb-tg-add" href="<?= e(tg_url(['buat' => 1])) ?>">＋ Buat Tugas</a><?php endif; ?>
    </div>

    <div class="pb-tg-grid">
        <?php foreach ($daftar as $t):
            $tutup = substr((string) $t['deadline'], 0, 10) < $today; ?>
            <article class="pb-tg-card">
                <div class="pb-tg-top">
                    <small><?= e($t['ekskul']) ?></small>
                    <i class="pb-tg-chip <?= $tutup ? 'off' : 'on' ?>">● <?= $tutup ? 'Ditutup' : 'Dibuka' ?></i>
                </div>
                <h3><?= e($t['judul']) ?></h3>
                <p><?= e($t['keterangan']) ?></p>
                <span class="pb-tg-dl">Deadline: <?= e(tanggal_id(substr((string) $t['deadline'], 0, 10))) ?></span>
                <a class="pb-tg-count" href="<?= e(tg_url(['tugas' => $t['id']])) ?>">Pengumpulan (<?= (int) $t['kumpul'] ?>/<?= (int) $t['anggota'] ?>)</a>
            </article>
        <?php endforeach; ?>
        <?php if (!$daftar): ?>
            <p class="pb-empty"><?= $ekskulku ? 'Belum ada tugas. Klik “Buat Tugas” untuk membuat yang pertama.' : 'Anda belum mengampu ekskul apa pun.' ?></p>
        <?php endif; ?>
    </div>
</section>

<?php /* ---------- Modal: pengumpulan ---------- */
if ($modalTugas && !$beri): $tutupUrl = e(tg_url()); ?>
    <div class="pb-modal-back">
        <div class="pb-modal pb-modal--wide" role="dialog" aria-modal="true" aria-label="Pengumpulan">
            <header><h3>Pengumpulan — <?= e($modalTugas['judul']) ?></h3><a href="<?= $tutupUrl ?>" aria-label="Tutup">✕</a></header>
            <div class="pb-modal-body">
                <div class="pb-tg-table">
                    <div class="pb-tg-tr pb-tg-th"><span>SISWA</span><span>FILE</span><span>TANGGAL</span><span>NILAI</span><span>DEADLINE</span><span></span></div>
                    <?php foreach ($rows as $r): ?>
                        <div class="pb-tg-tr">
                            <span class="pb-nm"><?= e($r['nama']) ?></span>
                            <span><a class="pb-tg-file" href="<?= e(tg_url(['unduh' => $r['id']])) ?>" target="_blank" rel="noopener">☑ <?= e($r['nama_file']) ?></a></span>
                            <span class="pb-mut"><?= e(tanggal_id(substr((string) $r['uploaded_at'], 0, 10))) ?></span>
                            <span><b class="pb-tg-nilai"><?= $r['nilai'] !== null && $r['nilai'] !== '' ? e($r['nilai']) : '&nbsp;' ?></b></span>
                            <span class="pb-red"><?= e(tanggal_id(substr((string) $modalTugas['deadline'], 0, 10))) ?></span>
                            <span><a class="pb-tg-btn" href="<?= e(tg_url(['tugas' => $modalTugas['id'], 'beri' => $r['id']])) ?>"><?= ($r['nilai'] !== null && $r['nilai'] !== '') ? 'Perbarui' : 'Simpan' ?></a></span>
                        </div>
                    <?php endforeach; ?>
                    <?php if (!$rows): ?><p class="pb-empty">Belum ada siswa yang mengumpulkan.</p><?php endif; ?>
                </div>
            </div>
            <footer><a class="pb-close" href="<?= $tutupUrl ?>">Tutup</a></footer>
        </div>
    </div>
<?php endif; ?>

<?php /* ---------- Modal: beri nilai ---------- */
if ($modalTugas && $beri): $balik = e(tg_url(['tugas' => $modalTugas['id']])); ?>
    <div class="pb-modal-back">
        <div class="pb-modal pb-modal--form" role="dialog" aria-modal="true" aria-label="Beri Nilai">
            <form method="post" action="<?= e(tg_url()) ?>">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="aksi" value="nilai">
                <input type="hidden" name="pengumpulan_id" value="<?= (int) $beri['id'] ?>">
                <header><h3>Beri Nilai <span class="pb-eknama">· <?= e($beri['nama']) ?></span></h3><a href="<?= $balik ?>" aria-label="Tutup">✕</a></header>
                <div class="pb-modal-body">
                    <label class="pb-fl"><span>Nilai</span>
                        <select name="nilai" required>
                            <?php foreach (TUGAS_NILAI as $n): ?>
                                <option value="<?= e($n) ?>" <?= (string) $beri['nilai'] === $n ? 'selected' : '' ?>><?= e($n) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="pb-fl"><span>Pesan (opsional)</span>
                        <textarea name="pesan" rows="4" maxlength="255"><?= e((string) $beri['pesan']) ?></textarea>
                    </label>
                </div>
                <footer>
                    <a class="pb-close" href="<?= $balik ?>">Batal</a>
                    <button type="submit" class="pb-save">Simpan &amp; Kirim</button>
                </footer>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php /* ---------- Modal: buat tugas ---------- */
if ($bukaBuat && $ekskulku): $tutupUrl = e(tg_url()); ?>
    <div class="pb-modal-back">
        <div class="pb-modal pb-modal--form" role="dialog" aria-modal="true" aria-label="Buat Tugas Baru">
            <form method="post" action="<?= e(tg_url()) ?>">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="aksi" value="buat">
                <header><h3>Buat Tugas Baru</h3><a href="<?= $tutupUrl ?>" aria-label="Tutup">✕</a></header>
                <div class="pb-modal-body">
                    <?php if ($errBuat): ?>
                        <div class="pb-flash err" role="alert"><?php foreach ($errBuat as $m): ?><div><?= e($m) ?></div><?php endforeach; ?></div>
                    <?php endif; ?>
                    <label class="pb-fl"><span>Ekstrakurikuler</span>
                        <select name="ekskul_id" required>
                            <?php foreach ($ekskulku as $ek): ?>
                                <option value="<?= (int) $ek['id'] ?>" <?= $in['ekskul_id'] === (int) $ek['id'] ? 'selected' : '' ?>><?= e($ek['nama']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="pb-fl"><span>Judul Tugas</span><input type="text" name="judul" value="<?= e($in['judul']) ?>" maxlength="150" required></label>
                    <label class="pb-fl"><span>Deskripsi Tugas</span><textarea name="keterangan" rows="3" maxlength="255"><?= e($in['keterangan']) ?></textarea></label>
                    <label class="pb-fl"><span>Deadline</span><input type="date" name="deadline" value="<?= e($in['deadline']) ?>" min="<?= e($today) ?>" required></label>
                </div>
                <footer>
                    <a class="pb-close" href="<?= $tutupUrl ?>">Batal</a>
                    <button type="submit" class="pb-save">Simpan &amp; Kirim</button>
                </footer>
            </form>
        </div>
    </div>
<?php endif; ?>
<?php pembimbing_footer();
