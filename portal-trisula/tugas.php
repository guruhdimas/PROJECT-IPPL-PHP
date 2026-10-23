<?php
declare(strict_types=1);

require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/siswa_layout.php';
require __DIR__ . '/includes/ekskul.php';

const MAKS_UPLOAD = 5 * 1024 * 1024; // 5 MB

if (!function_exists('tgl_panjang')) {
    function tgl_panjang(string $date): string
    {
        static $b = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        $t = strtotime($date);
        return date('j', $t) . ' ' . $b[(int) date('n', $t)] . ' ' . date('Y', $t);
    }
}

$session = require_role('siswa');
$pdo     = db();
$user    = load_user($pdo, (int) $session['id']);
$uid     = (int) $user['id'];

// ===== Proses upload =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!csrf_valid($_POST['csrf'] ?? null)) {
            throw new RuntimeException('Sesi tidak valid, muat ulang halaman.');
        }
        $tid = (int) ($_POST['tugas_id'] ?? 0);

        // Tugas harus milik ekskul yang sudah disetujui untuk siswa ini
        $st = $pdo->prepare("SELECT t.id, t.deadline, g.id AS gid, g.status AS gstatus, g.path_file
                             FROM ekskul_tugas t
                             JOIN pendaftaran p ON p.ekskul_id = t.ekskul_id AND p.siswa_id = ? AND p.status = 'disetujui'
                             LEFT JOIN pengumpulan g ON g.tugas_id = t.id AND g.siswa_id = ?
                             WHERE t.id = ?");
        $st->execute([$uid, $uid, $tid]);
        $t = $st->fetch();

        if (!$t)                              throw new RuntimeException('Tugas tidak ditemukan.');
        if ($t['gstatus'] === 'selesai')      throw new RuntimeException('Tugas sudah diserahkan dan tidak dapat diubah.');
        if ($t['deadline'] < date('Y-m-d'))   throw new RuntimeException('Batas waktu pengumpulan sudah lewat.');

        $f = $_FILES['berkas'] ?? null;
        if (!$f || $f['error'] !== UPLOAD_ERR_OK) {
            $besar = $f && in_array($f['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true);
            throw new RuntimeException($besar ? 'Ukuran file melebihi batas (maks 5 MB).' : 'File gagal diunggah, coba lagi.');
        }
        if ($f['size'] > MAKS_UPLOAD)         throw new RuntimeException('Ukuran file melebihi batas (maks 5 MB).');
        if (strtolower(pathinfo($f['name'], PATHINFO_EXTENSION)) !== 'pdf') throw new RuntimeException('Hanya file PDF yang diperbolehkan.');
        if (file_get_contents($f['tmp_name'], false, null, 0, 5) !== '%PDF-')  throw new RuntimeException('File bukan PDF yang valid.');

        $dir = __DIR__ . '/uploads/tugas/' . $uid;
        if (!is_dir($dir) && !mkdir($dir, 0755, true)) throw new RuntimeException('Folder upload tidak dapat dibuat.');

        $simpan = bin2hex(random_bytes(12)) . '.pdf';
        if (!move_uploaded_file($f['tmp_name'], $dir . '/' . $simpan)) throw new RuntimeException('File gagal disimpan.');

        $asli = mb_substr((string) preg_replace('/[^\w.\- ()]/u', '_', basename($f['name'])), 0, 150);
        $rel  = 'uploads/tugas/' . $uid . '/' . $simpan;

        if ($t['gid']) {
            $pdo->prepare("UPDATE pengumpulan SET nama_file = ?, path_file = ?, status = 'dikirim', uploaded_at = NOW() WHERE id = ?")
                ->execute([$asli, $rel, $t['gid']]);
            // Hapus file lama (hanya yang di dalam folder uploads/tugas)
            if (str_starts_with((string) $t['path_file'], 'uploads/tugas/') && is_file(__DIR__ . '/' . $t['path_file'])) {
                @unlink(__DIR__ . '/' . $t['path_file']);
            }
        } else {
            $pdo->prepare('INSERT INTO pengumpulan (tugas_id, siswa_id, nama_file, path_file) VALUES (?,?,?,?)')
                ->execute([$tid, $uid, $asli, $rel]);
        }
        $_SESSION['flash'] = ['ok', 'File "' . $asli . '" berhasil diunggah.'];
    } catch (RuntimeException $ex) {
        $_SESSION['flash'] = ['err', $ex->getMessage()];
    } catch (PDOException $ex) {
        $_SESSION['flash'] = ['err', 'Gagal menyimpan data, coba lagi.'];
    }
    header('Location: tugas.php');
    exit;
}

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

// ===== Daftar tugas =====
$st = $pdo->prepare("SELECT t.id, t.judul, t.keterangan, t.deadline, e.nama AS ekskul,
                            g.status AS gstatus, g.nama_file, g.uploaded_at
                     FROM ekskul_tugas t
                     JOIN ekskul e ON e.id = t.ekskul_id
                     JOIN pendaftaran p ON p.ekskul_id = t.ekskul_id AND p.siswa_id = ? AND p.status = 'disetujui'
                     LEFT JOIN pengumpulan g ON g.tugas_id = t.id AND g.siswa_id = ?
                     ORDER BY t.deadline, t.id");
$st->execute([$uid, $uid]);
$tugas = $st->fetchAll();

$today  = date('Y-m-d');
$hitung = ['selesai' => 0, 'proses' => 0, 'belum' => 0];
foreach ($tugas as &$t) {
    $t['state'] = $t['gstatus'] === 'selesai' ? 'selesai' : ($t['gstatus'] === 'dikirim' ? 'proses' : 'belum');
    $hitung[$t['state']]++;
}
unset($t);

$label = ['selesai' => ['Selesai', 'st-ok'], 'proses' => ['Dalam Proses', 'st-proc'], 'belum' => ['Belum Selesai', 'st-no']];

siswa_header('Tugas', 'tugas', $user);
?>
<div class="page-title"><h2>Tugas</h2><p>Kelola semua tugas anda!</p></div>

<?php if ($flash): ?>
    <div class="flash <?= $flash[0] === 'ok' ? 'flash-ok' : 'flash-err' ?>" role="status"><?= e($flash[1]) ?></div>
<?php endif; ?>

<section class="stats">
    <article class="stat"><strong style="color:#004029"><?= count($tugas) ?></strong><small>Total Item</small></article>
    <article class="stat"><strong style="color:#00a63e"><?= $hitung['selesai'] ?></strong><small>Selesai</small></article>
    <article class="stat"><strong style="color:#e17100"><?= $hitung['proses'] ?></strong><small>Dalam Proses</small></article>
    <article class="stat"><strong style="color:#e7000b"><?= $hitung['belum'] ?></strong><small>Belum Selesai</small></article>
</section>

<section class="panel ttable">
    <div class="trow thead"><span>NAMA TUGAS</span><span>STATUS</span><span>DETAIL / DEADLINE</span><span>UPLOAD FILE</span></div>

    <?php foreach ($tugas as $t):
        $lewat = $t['deadline'] < $today;
        [$txt, $cls] = $label[$t['state']];
    ?>
        <div class="trow">
            <span class="tname"><b><?= e($t['judul']) ?></b><small><?= e($t['ekskul']) ?></small></span>
            <span><i class="stpill <?= $cls ?>"><?= e($txt) ?></i></span>
            <span class="tdetail">
                <?= e($t['keterangan']) ?>
                <b class="dl">Deadline: <?= e(tgl_panjang($t['deadline'])) ?></b>
                <?php if ($t['nama_file']): ?><small>📄 <?= e($t['nama_file']) ?> · dikirim <?= e(tgl_panjang(substr($t['uploaded_at'], 0, 10))) ?></small><?php endif; ?>
            </span>
            <span>
                <?php if ($t['state'] === 'selesai'): ?>
                    <span class="btn-up btn-up--off">Diserahkan</span>
                <?php elseif ($lewat): ?>
                    <span class="btn-up btn-up--off">Ditutup</span>
                <?php else: ?>
                    <form method="post" enctype="multipart/form-data">
                        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="tugas_id" value="<?= (int) $t['id'] ?>">
                        <label class="btn-up">
                            <input type="file" name="berkas" accept="application/pdf,.pdf" hidden onchange="this.form.submit()">
                            ⬆ <?= $t['state'] === 'proses' ? 'Ganti File' : 'Upload File' ?>
                        </label>
                    </form>
                <?php endif; ?>
            </span>
        </div>
    <?php endforeach; ?>

    <?php if (!$tugas): ?>
        <p class="empty">Belum ada tugas. Tugas muncul setelah pendaftaran ekskul Anda disetujui pembimbing.</p>
    <?php endif; ?>
</section>
<?php siswa_footer();
