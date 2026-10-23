<?php
declare(strict_types=1);

require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/siswa_layout.php';
require __DIR__ . '/includes/ekskul.php';

const JENDELA_HARI = 7; // presensi bisa diisi sejak hari pertemuan sampai 7 hari sesudahnya

if (!function_exists('tgl_panjang')) {
    function tgl_panjang(string $date): string
    {
        static $b = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        $t = strtotime($date);
        return date('j', $t) . ' ' . $b[(int) date('n', $t)] . ' ' . date('Y', $t);
    }
}

function abs_bulan(string $date): string
{
    static $b = [1 => 'JAN', 'FEB', 'MAR', 'APR', 'MEI', 'JUN', 'JUL', 'AGU', 'SEP', 'OKT', 'NOV', 'DES'];
    return $b[(int) date('n', strtotime($date))];
}

$session = require_role('siswa');
$pdo     = db();
$user    = load_user($pdo, (int) $session['id']);
$uid     = (int) $user['id'];

$today = date('Y-m-d');
$batas = date('Y-m-d', strtotime('-' . JENDELA_HARI . ' days'));

// ===== Proses isi presensi =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!csrf_valid($_POST['csrf'] ?? null)) {
            throw new RuntimeException('Sesi tidak valid, muat ulang halaman.');
        }
        $pid    = (int) ($_POST['pertemuan_id'] ?? 0);
        $status = (string) ($_POST['status'] ?? '');
        if (!in_array($status, ['hadir', 'izin', 'sakit'], true)) {
            throw new RuntimeException('Status kehadiran tidak valid.');
        }

        // Pertemuan harus milik ekskul yang sudah disetujui untuk siswa ini
        $st = $pdo->prepare("SELECT m.id, m.tanggal, a.id AS aid
                             FROM ekskul_pertemuan m
                             JOIN pendaftaran p ON p.ekskul_id = m.ekskul_id AND p.siswa_id = ? AND p.status = 'disetujui'
                             LEFT JOIN ekskul_absensi a ON a.pertemuan_id = m.id AND a.siswa_id = ?
                             WHERE m.id = ?");
        $st->execute([$uid, $uid, $pid]);
        $m = $st->fetch();

        if (!$m)                      throw new RuntimeException('Pertemuan tidak ditemukan.');
        if ($m['aid'])                throw new RuntimeException('Presensi sudah diisi dan tidak dapat diubah.');
        if ($m['tanggal'] > $today)   throw new RuntimeException('Presensi baru dibuka pada hari pertemuan.');
        if ($m['tanggal'] < $batas)   throw new RuntimeException('Batas waktu pengisian presensi sudah lewat.');

        $pdo->prepare('INSERT INTO ekskul_absensi (pertemuan_id, siswa_id, status) VALUES (?,?,?)')
            ->execute([$pid, $uid, $status]);

        $_SESSION['flash'] = ['ok', 'Presensi berhasil disimpan sebagai ' . ucfirst($status) . '.'];
    } catch (RuntimeException $ex) {
        $_SESSION['flash'] = ['err', $ex->getMessage()];
    } catch (PDOException $ex) {
        $_SESSION['flash'] = ['err', 'Gagal menyimpan presensi, coba lagi.'];
    }
    header('Location: absensi.php');
    exit;
}

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

// ===== Data pertemuan (hanya yang sudah/sedang berlangsung) =====
$st = $pdo->prepare("SELECT m.id, m.tanggal, m.jam, m.kegiatan, m.lokasi, e.nama AS ekskul, a.status
                     FROM ekskul_pertemuan m
                     JOIN ekskul e ON e.id = m.ekskul_id
                     JOIN pendaftaran p ON p.ekskul_id = m.ekskul_id AND p.siswa_id = ? AND p.status = 'disetujui'
                     LEFT JOIN ekskul_absensi a ON a.pertemuan_id = m.id AND a.siswa_id = ?
                     WHERE m.tanggal <= ?
                     ORDER BY m.tanggal DESC, m.jam DESC, m.id DESC");
$st->execute([$uid, $uid, $today]);
$semua = $st->fetchAll();

$hitung  = ['hadir' => 0, 'izin' => 0, 'sakit' => 0, 'alpa' => 0];
$pending = [];
$riwayat = [];
foreach ($semua as $r) {
    if ($r['status']) {
        $r['state'] = $r['status'];
    } elseif ($r['tanggal'] >= $batas) {
        $pending[] = $r;
        continue;
    } else {
        $r['state'] = 'alpa'; // lewat batas dan tidak diisi
    }
    $hitung[$r['state']]++;
    $riwayat[] = $r;
}
$pending = array_reverse($pending); // yang paling lama dulu

$total  = array_sum($hitung);
$persen = $total > 0 ? (int) round($hitung['hadir'] / $total * 100) : 0;

$label = ['hadir' => 'Hadir', 'izin' => 'Izin', 'sakit' => 'Sakit', 'alpa' => 'Alpa'];

siswa_header('Absensi', 'absensi', $user);
?>
<div class="page-title"><h2>Absensi</h2><p>Lihat status kehadiran Anda!</p></div>

<?php if ($flash): ?>
    <div class="flash <?= $flash[0] === 'ok' ? 'flash-ok' : 'flash-err' ?>" role="status"><?= e($flash[1]) ?></div>
<?php endif; ?>

<section class="abs-stats">
    <article class="abs-stat">
        <small>Persentase Kehadiran</small>
        <strong><?= $persen ?>%</strong>
        <span class="ok"><?= $total ?> pertemuan</span>
    </article>
    <article class="abs-stat"><small>Hadir</small><strong><?= $hitung['hadir'] ?></strong><span class="ok">pertemuan</span></article>
    <article class="abs-stat"><small>Izin</small><strong><?= $hitung['izin'] ?></strong><span class="warn">pertemuan</span></article>
    <article class="abs-stat"><small>Sakit</small><strong><?= $hitung['sakit'] ?></strong><span class="warn">pertemuan</span></article>
    <article class="abs-stat"><small>Alpa</small><strong><?= $hitung['alpa'] ?></strong><span class="warn">pertemuan</span></article>
</section>

<section class="panel abs-panel">
    <header class="abs-head">
        <h3>Isi Presensi</h3>
        <p>Pilih status kehadiran Anda untuk setiap pertemuan</p>
    </header>

    <?php foreach ($pending as $r): ?>
        <div class="abs-fill">
            <div class="abs-date"><b><?= (int) date('j', strtotime($r['tanggal'])) ?></b><span><?= e(abs_bulan($r['tanggal'])) ?></span></div>
            <div class="abs-info">
                <b><?= e($r['ekskul']) ?> · <?= e($r['kegiatan']) ?></b>
                <small><?= e(substr((string) $r['jam'], 0, 5)) ?> · <?= e($r['lokasi']) ?></small>
            </div>
            <form method="post" class="abs-actions">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="pertemuan_id" value="<?= (int) $r['id'] ?>">
                <button type="submit" name="status" value="hadir" class="abs-btn abs-btn--hadir"
                        onclick="return confirm('Isi presensi sebagai Hadir? Pilihan tidak dapat diubah.')">✓ Hadir</button>
                <button type="submit" name="status" value="izin" class="abs-btn"
                        onclick="return confirm('Isi presensi sebagai Izin? Pilihan tidak dapat diubah.')">✎ Izin</button>
                <button type="submit" name="status" value="sakit" class="abs-btn"
                        onclick="return confirm('Isi presensi sebagai Sakit? Pilihan tidak dapat diubah.')">◷ Sakit</button>
            </form>
        </div>
    <?php endforeach; ?>

    <?php if (!$pending): ?>
        <p class="empty">Semua pertemuan sudah diisi presensinya.</p>
    <?php endif; ?>
</section>

<section class="panel abs-panel">
    <header class="abs-head">
        <h3>Riwayat Kehadiran</h3>
        <p>Kehadiran pribadi Anda</p>
    </header>

    <div class="abs-table">
        <div class="abs-tr abs-th"><span>TANGGAL</span><span>EKSKUL</span><span>KEGIATAN</span><span>LOKASI</span><span>STATUS</span></div>
        <?php foreach ($riwayat as $r): ?>
            <div class="abs-tr">
                <span><?= e(tgl_panjang($r['tanggal'])) ?></span>
                <span class="abs-ek"><?= e($r['ekskul']) ?></span>
                <span><?= e($r['kegiatan']) ?></span>
                <span class="muted"><?= e($r['lokasi']) ?></span>
                <span><i class="abs-pill abs-<?= e($r['state']) ?>"><?= e($label[$r['state']]) ?></i></span>
            </div>
        <?php endforeach; ?>
        <?php if (!$riwayat): ?>
            <p class="empty">Belum ada riwayat kehadiran.</p>
        <?php endif; ?>
    </div>
</section>
<?php siswa_footer();
