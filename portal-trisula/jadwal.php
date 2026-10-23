<?php
declare(strict_types=1);

require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/siswa_layout.php';
require __DIR__ . '/includes/ekskul.php';

$session = require_role('siswa');
$pdo     = db();
$user    = load_user($pdo, (int) $session['id']);

function tgl_panjang(string $date): string
{
    static $b = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $t = strtotime($date);
    return date('j', $t) . ' ' . $b[(int) date('n', $t)] . ' ' . date('Y', $t);
}

function jadwal_url(array $p): string
{
    return 'jadwal.php' . ($p ? '?' . http_build_query($p) : '');
}

$namaBulan = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
$hariNo    = ['Senin' => 1, 'Selasa' => 2, 'Rabu' => 3, 'Kamis' => 4, 'Jumat' => 5, 'Sabtu' => 6, 'Minggu' => 7];
$today     = date('Y-m-d');

// ===== Ekskul yang diikuti (disetujui) =====
$st = $pdo->prepare("SELECT e.id, e.nama, e.hari, e.jam_mulai, e.jam_selesai
                     FROM pendaftaran p JOIN ekskul e ON e.id = p.ekskul_id
                     WHERE p.siswa_id = ? AND p.status = 'disetujui' ORDER BY e.nama");
$st->execute([$user['id']]);
$saya = $st->fetchAll();

$filterId = (int) ($_GET['ekskul'] ?? 0);
if (!in_array($filterId, array_column($saya, 'id'), true)) { $filterId = 0; }
$aktif = $filterId ? array_values(array_filter($saya, fn ($e) => (int) $e['id'] === $filterId)) : $saya;
$ids   = array_map('intval', array_column($aktif, 'id'));

// ===== Bulan & tanggal terpilih =====
$bulan = preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $_GET['bulan'] ?? '') ? $_GET['bulan'] : date('Y-m');
$first = new DateTimeImmutable($bulan . '-01');
$jmlHari = (int) $first->format('t');
$offset  = (int) $first->format('N') - 1;          // Senin = 0
$prevBulan = $first->modify('-1 month')->format('Y-m');
$nextBulan = $first->modify('+1 month')->format('Y-m');

$sel = $_GET['tanggal'] ?? '';
$selOk = preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $sel, $m) && checkdate((int) $m[2], (int) $m[3], (int) $m[1]) && substr($sel, 0, 7) === $bulan;
if (!$selOk) { $sel = (date('Y-m') === $bulan) ? $today : $bulan . '-01'; }

// ===== Susun kegiatan per tanggal =====
$events = [];   // 'Y-m-d' => [ [jam, judul, ket] ]
foreach ($aktif as $e) {
    for ($d = 1; $d <= $jmlHari; $d++) {
        $tgl = sprintf('%s-%02d', $bulan, $d);
        if ((int) date('N', strtotime($tgl)) === $hariNo[$e['hari']]) {
            $events[$tgl][] = ['jam' => jam_fmt($e['jam_mulai']), 'judul' => $e['nama'],
                               'ket' => jam_fmt($e['jam_mulai']) . ' – ' . jam_fmt($e['jam_selesai'])];
        }
    }
}

$tugasBulan = [];
$tugasNext  = [];
if ($ids) {
    $in = implode(',', array_fill(0, count($ids), '?'));
    $st = $pdo->prepare("SELECT t.judul, t.deadline, e.nama FROM ekskul_tugas t JOIN ekskul e ON e.id = t.ekskul_id
                         WHERE t.ekskul_id IN ($in) AND t.deadline BETWEEN ? AND ?");
    $st->execute([...$ids, $bulan . '-01', $bulan . '-' . $jmlHari]);
    foreach ($st->fetchAll() as $t) {
        $events[$t['deadline']][] = ['jam' => '21:59', 'judul' => 'Tugas: ' . $t['judul'], 'ket' => 'Batas pengumpulan 21.59 · ' . $t['nama']];
    }
    $st = $pdo->prepare("SELECT t.judul, t.deadline FROM ekskul_tugas t WHERE t.ekskul_id IN ($in) AND t.deadline >= ? ORDER BY t.deadline LIMIT 5");
    $st->execute([...$ids, $today]);
    foreach ($st->fetchAll() as $t) { $tugasNext[] = ['tgl' => $t['deadline'], 'label' => 'Tugas: ' . $t['judul']]; }
}
foreach ($events as &$list) { usort($list, fn ($a, $b) => strcmp($a['jam'], $b['jam'])); }
unset($list);

// ===== Mendatang: sesi berikutnya tiap ekskul + tugas =====
$mendatang = $tugasNext;
foreach ($aktif as $e) {
    for ($i = 0; $i < 7; $i++) {
        $tgl = date('Y-m-d', strtotime("$today +$i day"));
        if ((int) date('N', strtotime($tgl)) === $hariNo[$e['hari']] && $tgl >= $today) {
            $mendatang[] = ['tgl' => $tgl, 'label' => $e['nama']];
            break;
        }
    }
}
usort($mendatang, fn ($a, $b) => strcmp($a['tgl'], $b['tgl']));
$mendatang = array_slice($mendatang, 0, 6);

$kegiatanHariIni = $events[$sel] ?? [];
$qs = $filterId ? ['ekskul' => $filterId] : [];

siswa_header('Jadwal', 'jadwal', $user);
?>
<div class="jadwal-layout">
    <section class="panel">
        <header class="cal-top">
            <div><h3>Kalender Kegiatan</h3><p>Jadwal ekskul yang Anda ikuti</p></div>
            <form method="get">
                <input type="hidden" name="bulan" value="<?= e($bulan) ?>">
                <select name="ekskul" onchange="this.form.submit()" aria-label="Filter ekskul">
                    <option value="0">Semua ekskul</option>
                    <?php foreach ($saya as $e): ?>
                        <option value="<?= (int) $e['id'] ?>" <?= $filterId === (int) $e['id'] ? 'selected' : '' ?>><?= e($e['nama']) ?></option>
                    <?php endforeach; ?>
                </select>
            </form>
        </header>

        <div class="cal-wrap">
            <div class="cal-bar">
                <h3><?= e($namaBulan[(int) $first->format('n')] . ' ' . $first->format('Y')) ?></h3>
                <div class="cal-nav">
                    <a class="nav-btn" href="<?= e(jadwal_url($qs + ['bulan' => $prevBulan])) ?>" aria-label="Bulan sebelumnya">‹</a>
                    <a class="nav-btn nav-today" href="<?= e(jadwal_url($qs)) ?>">Hari ini</a>
                    <a class="nav-btn" href="<?= e(jadwal_url($qs + ['bulan' => $nextBulan])) ?>" aria-label="Bulan berikutnya">›</a>
                </div>
            </div>

            <div class="cal">
                <?php foreach (['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'] as $h): ?><div class="dow"><?= $h ?></div><?php endforeach; ?>
                <?php
                $total = (int) (ceil(($offset + $jmlHari) / 7) * 7);
                for ($i = 0; $i < $total; $i++):
                    $day = $i - $offset + 1;
                    if ($day < 1 || $day > $jmlHari): ?>
                        <div class="cell cell--off"></div>
                    <?php continue; endif;
                    $tgl = sprintf('%s-%02d', $bulan, $day);
                    $cls = 'cell' . ($tgl === $today ? ' cell--today' : '') . ($tgl === $sel ? ' cell--sel' : '');
                ?>
                    <a class="<?= $cls ?>" href="<?= e(jadwal_url($qs + ['bulan' => $bulan, 'tanggal' => $tgl])) ?>">
                        <b><?= $day ?></b>
                        <?php foreach (array_slice($events[$tgl] ?? [], 0, 2) as $ev): ?>
                            <span class="chip" title="<?= e($ev['judul']) ?>"><?= e($ev['jam'] . ' ' . $ev['judul']) ?></span>
                        <?php endforeach; ?>
                        <?php if (count($events[$tgl] ?? []) > 2): ?><small>+<?= count($events[$tgl]) - 2 ?> lagi</small><?php endif; ?>
                    </a>
                <?php endfor; ?>
            </div>
        </div>
    </section>

    <aside class="panel day-panel">
        <header class="day-head"><h3><?= e(tgl_panjang($sel)) ?></h3><p><?= count($kegiatanHariIni) ?> kegiatan</p></header>
        <div class="day-body">
            <?php if (!$kegiatanHariIni): ?>
                <p class="day-empty">Tidak ada kegiatan pada tanggal ini.</p>
            <?php else: foreach ($kegiatanHariIni as $ev): ?>
                <div class="day-item"><b><?= e($ev['judul']) ?></b><small><?= e($ev['ket']) ?></small></div>
            <?php endforeach; endif; ?>
        </div>
        <div class="soon">
            <h4>MENDATANG</h4>
            <?php foreach ($mendatang as $m): ?>
                <div class="soon-item"><span><?= e($m['label']) ?></span><small><?= e(tgl_panjang($m['tgl'])) ?></small></div>
            <?php endforeach; ?>
            <?php if (!$mendatang): ?>
                <p class="day-empty" style="padding:8px 0">Belum ada ekskul yang disetujui. <a href="daftar_ekskul.php" style="color:var(--green);font-weight:600">Daftar ekskul</a></p>
            <?php endif; ?>
        </div>
    </aside>
</div>
<?php siswa_footer();
