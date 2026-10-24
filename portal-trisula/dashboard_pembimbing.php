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
function bln_kecil_pb(string $date): string
{
    static $b = [1 => 'jan', 'feb', 'mar', 'apr', 'mei', 'jun', 'jul', 'agu', 'sep', 'okt', 'nov', 'des'];
    return $b[(int) date('n', strtotime($date))];
}

$ekskulAktif  = q_one($pdo, 'SELECT COUNT(*) FROM ekskul WHERE pembimbing_id = ?', [$pid]);
$anggota      = q_one($pdo, "SELECT COUNT(*) FROM pendaftaran p JOIN ekskul e ON e.id = p.ekskul_id WHERE e.pembimbing_id = ? AND p.status = 'disetujui'", [$pid]);
$menunggu     = q_one($pdo, "SELECT COUNT(*) FROM pendaftaran p JOIN ekskul e ON e.id = p.ekskul_id WHERE e.pembimbing_id = ? AND p.status = 'menunggu'", [$pid]);
$belumDinilai = q_one($pdo, "SELECT COUNT(*) FROM pengumpulan g JOIN ekskul_tugas t ON t.id = g.tugas_id JOIN ekskul e ON e.id = t.ekskul_id WHERE e.pembimbing_id = ? AND g.status = 'dikirim'", [$pid]);

// Kehadiran: dari presensi yang tercatat di halaman Absensi (tabel ekskul_absensi)
$abs = q_all($pdo, "SELECT COUNT(*) AS total, COALESCE(SUM(a.status = 'hadir'), 0) AS hadir
                    FROM ekskul_absensi a JOIN ekskul_pertemuan m ON m.id = a.pertemuan_id JOIN ekskul e ON e.id = m.ekskul_id
                    WHERE e.pembimbing_id = ?", [$pid]);
$kehadiran = ($abs && (int) $abs[0]['total'] > 0) ? (int) round((int) $abs[0]['hadir'] / (int) $abs[0]['total'] * 100) : 0;

// Jadwal & pengumuman dari tabel yang sama dengan halaman Jadwal dan Pengumuman
$jadwal = q_all($pdo, "SELECT m.kegiatan AS judul, m.tanggal, m.jam, m.lokasi, m.ekskul_id
                       FROM ekskul_pertemuan m JOIN ekskul e ON e.id = m.ekskul_id
                       WHERE e.pembimbing_id = ? AND m.tanggal >= ? ORDER BY m.tanggal, m.jam LIMIT 3", [$pid, $today]);
$pengumuman = q_all($pdo, "SELECT a.id, a.judul, a.isi FROM info_pengumuman a JOIN ekskul e ON e.id = a.ekskul_id
                           WHERE e.pembimbing_id = ? ORDER BY a.created_at DESC, a.id DESC LIMIT 3", [$pid]);
$pendaftar  = q_all($pdo, "SELECT u.nama, e.nama AS ekskul, e.id AS eid FROM pendaftaran p
                           JOIN users u ON u.id = p.siswa_id JOIN ekskul e ON e.id = p.ekskul_id
                           WHERE e.pembimbing_id = ? AND p.status = 'menunggu' ORDER BY p.created_at DESC, p.id DESC LIMIT 3", [$pid]);

// Tautan
$eVerif = q_all($pdo, "SELECT e.id FROM ekskul e JOIN pendaftaran p ON p.ekskul_id = e.id AND p.status = 'menunggu'
                       WHERE e.pembimbing_id = ? GROUP BY e.id ORDER BY MIN(p.created_at) LIMIT 1", [$pid]);
$urlVerif = $eVerif ? 'kelola_anggota.php?' . http_build_query(['ekskul' => $eVerif[0]['id'], 'tab' => 'verifikasi']) : 'kelola_anggota.php';

$eAktif = q_all($pdo, "SELECT e.id FROM ekskul e LEFT JOIN pendaftaran p ON p.ekskul_id = e.id AND p.status = 'disetujui'
                       WHERE e.pembimbing_id = ? GROUP BY e.id ORDER BY COUNT(p.id) DESC, e.nama LIMIT 1", [$pid]);
$urlAnggota = $eAktif ? 'kelola_anggota.php?' . http_build_query(['ekskul' => $eAktif[0]['id'], 'tab' => 'aktif']) : 'kelola_anggota.php';

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
    <a class="pb-gold" href="<?= e($urlVerif) ?>">Verifikasi Sekarang</a>
</section>

<section class="pb-stats">
    <article class="pb-stat"><small>Ekskul Aktif</small><strong><?= $ekskulAktif ?></strong><a href="kelola_anggota.php">Lihat Semua</a></article>
    <article class="pb-stat"><small>Total Anggota</small><strong><?= $anggota ?></strong><a href="<?= e($urlAnggota) ?>">Anggota Aktif</a></article>
    <article class="pb-stat"><small>Daftar Menunggu</small><strong><?= $menunggu ?></strong><a class="red" href="<?= e($urlVerif) ?>">Perlu Verifikasi</a></article>
    <article class="pb-stat"><small>Rata Rata Kehadiran</small><strong><?= $kehadiran ?>%</strong><a href="kelola_absensi.php?tab=rekap" class="grn">Lihat Rekap</a></article>
</section>

<section class="pb-two">
    <article class="pb-card">
        <header><h3>Jadwal Mendatang</h3><a class="pb-outline" href="kelola_jadwal.php">Lihat Semua</a></header>
        <div class="pb-list">
            <?php foreach ($jadwal as $j): ?>
                <a class="pb-item" href="<?= e('kelola_jadwal.php?' . http_build_query(['bulan' => substr($j['tanggal'], 0, 7), 'tgl' => $j['tanggal']])) ?>">
                    <span class="pb-date"><b><?= (int) date('j', strtotime($j['tanggal'])) ?></b><small><?= e(bln_kecil_pb($j['tanggal'])) ?></small></span>
                    <div><b><?= e($j['judul']) ?></b><small><?= e(substr((string) $j['jam'], 0, 5)) ?> <?= e($j['lokasi']) ?></small></div>
                </a>
            <?php endforeach; ?>
            <?php if (!$jadwal): ?><p class="pb-empty">Belum ada jadwal mendatang.</p><?php endif; ?>
        </div>
    </article>

    <article class="pb-card">
        <header><h3>Pengumuman Terbaru</h3><a class="pb-outline" href="kelola_pengumuman.php">Lihat Semua</a></header>
        <div class="pb-list">
            <?php foreach ($pengumuman as $a): ?>
                <a class="pb-ann" href="<?= e('kelola_pengumuman.php?' . http_build_query(['lihat' => $a['id']])) ?>"><b><?= e($a['judul']) ?></b><small><?= e(mb_strimwidth((string) $a['isi'], 0, 90, '…')) ?></small></a>
            <?php endforeach; ?>
            <?php if (!$pengumuman): ?><p class="pb-empty">Belum ada pengumuman.</p><?php endif; ?>
        </div>
    </article>
</section>

<section class="pb-card">
    <header><h3>Pendaftar Terbaru</h3><a class="pb-outline" href="<?= e($urlVerif) ?>">Kelola</a></header>
    <div class="pb-chips">
        <?php foreach ($pendaftar as $d): ?>
            <a class="pb-chip" href="<?= e('kelola_anggota.php?' . http_build_query(['ekskul' => $d['eid'], 'tab' => 'verifikasi'])) ?>"><b><?= e($d['nama']) ?></b> <em><?= e(explode(' ', (string) $d['ekskul'])[0]) ?></em> <i>● Menunggu</i></a>
        <?php endforeach; ?>
        <?php if (!$pendaftar): ?><p class="pb-empty">Tidak ada pendaftar yang menunggu.</p><?php endif; ?>
    </div>
</section>
<?php pembimbing_footer();
