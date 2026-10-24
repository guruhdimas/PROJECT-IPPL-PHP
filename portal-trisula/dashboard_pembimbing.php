<?php
declare(strict_types=1);

require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/ekskul.php';
require __DIR__ . '/includes/pembimbing_layout.php';

$session = require_role('pembimbing');
$pdo     = db();
$me      = pembimbing_user($pdo, (int) $session['id']);
$pid     = (int) $me['id'];
$today   = date('Y-m-d');

/** Query aman: kalau tabel belum ada (SQL belum dijalankan) hasilnya kosong, bukan error. */
function q_all(PDO $pdo, string $sql, array $p = []): array
{
    try { $st = $pdo->prepare($sql); $st->execute($p); return $st->fetchAll(); }
    catch (PDOException $e) { return []; }
}
function q_one(PDO $pdo, string $sql, array $p = []): int
{
    $r = q_all($pdo, $sql, $p);
    return $r ? (int) array_values($r[0])[0] : 0;
}
function tgl_panjang_pb(string $date): string
{
    static $b = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $t = strtotime($date);
    return date('j', $t) . ' ' . $b[(int) date('n', $t)] . ' ' . date('Y', $t);
}

$ekskulAktif = q_one($pdo, 'SELECT COUNT(*) FROM ekskul WHERE pembimbing_id = ?', [$pid]);
$anggota     = q_one($pdo, "SELECT COUNT(*) FROM pendaftaran p JOIN ekskul e ON e.id = p.ekskul_id WHERE e.pembimbing_id = ? AND p.status = 'disetujui'", [$pid]);
$menunggu    = q_one($pdo, "SELECT COUNT(*) FROM pendaftaran p JOIN ekskul e ON e.id = p.ekskul_id WHERE e.pembimbing_id = ? AND p.status = 'menunggu'", [$pid]);
$belumDinilai = q_one($pdo, "SELECT COUNT(*) FROM pengumpulan g JOIN ekskul_tugas t ON t.id = g.tugas_id JOIN ekskul e ON e.id = t.ekskul_id WHERE e.pembimbing_id = ? AND g.status = 'dikirim'", [$pid]);

$abs = q_all($pdo, "SELECT COUNT(*) AS total, COALESCE(SUM(a.status = 'hadir'), 0) AS hadir
                    FROM ekskul_absensi a JOIN pertemuan m ON m.id = a.pertemuan_id JOIN ekskul e ON e.id = m.ekskul_id
                    WHERE e.pembimbing_id = ?", [$pid]);
$kehadiran = ($abs && (int) $abs[0]['total'] > 0) ? (int) round((int) $abs[0]['hadir'] / (int) $abs[0]['total'] * 100) : 0;

$jadwal = q_all($pdo, "SELECT m.judul, m.tanggal, m.jam, m.lokasi FROM pertemuan m JOIN ekskul e ON e.id = m.ekskul_id
                       WHERE e.pembimbing_id = ? AND m.tanggal >= ? ORDER BY m.tanggal, m.jam LIMIT 3", [$pid, $today]);
$pengumuman = q_all($pdo, 'SELECT judul, isi FROM pengumuman ORDER BY created_at DESC, id DESC LIMIT 3');
$pendaftar  = q_all($pdo, "SELECT u.nama, e.nama AS ekskul FROM pendaftaran p
                           JOIN users u ON u.id = p.siswa_id JOIN ekskul e ON e.id = p.ekskul_id
                           WHERE e.pembimbing_id = ? AND p.status = 'menunggu' ORDER BY p.created_at DESC, p.id DESC LIMIT 3", [$pid]);

$ringkas = [];
if ($menunggu)     $ringkas[] = $menunggu . ' pendaftaran menunggu';
if ($belumDinilai) $ringkas[] = $belumDinilai . ' tugas belum dinilai';

pembimbing_header('Dashboard', 'dashboard', $me);
?>
<section class="pb-banner">
    <div>
        <small><?= e(tgl_panjang_pb($today)) ?></small>
        <h2>SELAMAT DATANG, <?= e($me['nama']) ?></h2>
        <p><?= $ringkas ? e(implode(' dan ', $ringkas)) : 'Tidak ada yang perlu diproses saat ini' ?></p>
    </div>
    <a class="pb-gold" href="kelola_anggota.php">Verifikasi Sekarang</a>
</section>

<section class="pb-stats">
    <article class="pb-stat"><small>Ekskul Aktif</small><strong><?= $ekskulAktif ?></strong><a href="kelola_anggota.php">Lihat Semua</a></article>
    <article class="pb-stat"><small>Total Anggota</small><strong><?= $anggota ?></strong><span>Anggota Aktif</span></article>
    <article class="pb-stat"><small>Daftar Menunggu</small><strong><?= $menunggu ?></strong><span class="red">Perlu Verifikasi</span></article>
    <article class="pb-stat"><small>Rata Rata Kehadiran</small><strong><?= $kehadiran ?>%</strong><a href="kelola_absensi.php" class="grn">Lihat Rekap</a></article>
</section>

<section class="pb-two">
    <article class="pb-card">
        <header><h3>Jadwal Mendatang</h3><a class="pb-outline" href="kelola_jadwal.php">Lihat Semua</a></header>
        <div class="pb-list">
            <?php foreach ($jadwal as $j): ?>
                <div class="pb-item">
                    <span class="pb-date"><b><?= (int) date('j', strtotime($j['tanggal'])) ?></b><small><?= e(strtolower(bulan_pendek($j['tanggal']))) ?></small></span>
                    <div><b><?= e($j['judul']) ?></b><small><?= e(jam_fmt($j['jam'])) ?> <?= e($j['lokasi']) ?></small></div>
                </div>
            <?php endforeach; ?>
            <?php if (!$jadwal): ?><p class="pb-empty">Belum ada jadwal mendatang.</p><?php endif; ?>
        </div>
    </article>

    <article class="pb-card">
        <header><h3>Pengumuman Terbaru</h3><a class="pb-outline" href="kelola_pengumuman.php">Lihat Semua</a></header>
        <div class="pb-list">
            <?php foreach ($pengumuman as $a): ?>
                <div class="pb-ann"><b><?= e($a['judul']) ?></b><small><?= e($a['isi']) ?></small></div>
            <?php endforeach; ?>
            <?php if (!$pengumuman): ?><p class="pb-empty">Belum ada pengumuman.</p><?php endif; ?>
        </div>
    </article>
</section>

<section class="pb-card">
    <header><h3>Pendaftar Terbaru</h3><a class="pb-outline" href="kelola_anggota.php">Kelola</a></header>
    <div class="pb-chips">
        <?php foreach ($pendaftar as $d): ?>
            <span class="pb-chip"><b><?= e($d['nama']) ?></b> <em><?= e(explode(' ', $d['ekskul'])[0]) ?></em> <i>● Menunggu</i></span>
        <?php endforeach; ?>
        <?php if (!$pendaftar): ?><p class="pb-empty">Tidak ada pendaftar yang menunggu.</p><?php endif; ?>
    </div>
</section>
<?php pembimbing_footer();
