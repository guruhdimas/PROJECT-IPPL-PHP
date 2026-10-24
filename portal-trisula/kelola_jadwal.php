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

function jd_url(array $p = []): string
{
    $q = http_build_query(array_filter($p, static fn ($v) => $v !== '' && $v !== null && $v !== 0));
    return 'kelola_jadwal.php' . ($q !== '' ? '?' . $q : '');
}

// Ekskul yang diampu pembimbing ini
$st = $pdo->prepare('SELECT id, nama FROM ekskul WHERE pembimbing_id = ? ORDER BY nama');
$st->execute([$pid]);
$ekskulku = $st->fetchAll();
$idEkskul = array_map(static fn ($r) => (int) $r['id'], $ekskulku);
$namaEkskul = [];
foreach ($ekskulku as $r) { $namaEkskul[(int) $r['id']] = (string) $r['nama']; }

// ---------- Keadaan tampilan: filter, bulan, tanggal terpilih ----------
$f = (int) ($_GET['f'] ?? 0);
if (!in_array($f, $idEkskul, true)) $f = 0;

$bulan = (string) ($_GET['bulan'] ?? '');
if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $bulan)) $bulan = date('Y-m');

$tgl = (string) ($_GET['tgl'] ?? '');
$dt  = DateTime::createFromFormat('Y-m-d', $tgl);
if (!$dt || $dt->format('Y-m-d') !== $tgl || substr($tgl, 0, 7) !== $bulan) {
    $tgl = ($bulan === date('Y-m')) ? $today : $bulan . '-01';
}
$state = ['bulan' => $bulan, 'tgl' => $tgl, 'f' => $f];

// ===================== AKSI (POST) =====================
$errTambah  = [];
$bukaTambah = isset($_GET['tambah']);
$in = ['ekskul_id' => $f, 'tanggal' => $tgl, 'waktu' => '', 'lokasi' => '', 'keterangan' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = (string) ($_POST['aksi'] ?? '');

    if ($aksi === 'tambah') {
        $bukaTambah = true;
        $in = [
            'ekskul_id'  => (int) ($_POST['ekskul_id'] ?? 0),
            'tanggal'    => trim((string) ($_POST['tanggal'] ?? '')),
            'waktu'      => trim((string) ($_POST['waktu'] ?? '')),
            'lokasi'     => trim((string) ($_POST['lokasi'] ?? '')),
            'keterangan' => trim((string) ($_POST['keterangan'] ?? '')),
        ];
        if (!csrf_valid($_POST['csrf'] ?? null))          $errTambah[] = 'Sesi tidak valid, muat ulang halaman.';
        if (!in_array($in['ekskul_id'], $idEkskul, true)) $errTambah[] = 'Pilih ekstrakurikuler yang Anda ampu.';
        $d = DateTime::createFromFormat('Y-m-d', $in['tanggal']);
        if (!$d || $d->format('Y-m-d') !== $in['tanggal'])  $errTambah[] = 'Tanggal tidak valid.';
        if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $in['waktu'])) $errTambah[] = 'Waktu tidak valid.';
        if ($in['lokasi'] === '' || mb_strlen($in['lokasi']) > 100)   $errTambah[] = 'Lokasi wajib diisi (maks 100 karakter).';
        if (mb_strlen($in['keterangan']) > 150)                       $errTambah[] = 'Keterangan maksimal 150 karakter.';

        if (!$errTambah) {
            try {
                $st = $pdo->prepare('SELECT COUNT(*) FROM ekskul_pertemuan WHERE ekskul_id = ? AND tanggal = ? AND jam = ?');
                $st->execute([$in['ekskul_id'], $in['tanggal'], $in['waktu'] . ':00']);
                if ((int) $st->fetchColumn() > 0) {
                    $errTambah[] = 'Jadwal dengan ekskul, tanggal, dan waktu yang sama sudah ada.';
                } else {
                    $pdo->prepare('INSERT INTO ekskul_pertemuan (ekskul_id, tanggal, jam, kegiatan, lokasi) VALUES (?,?,?,?,?)')
                        ->execute([$in['ekskul_id'], $in['tanggal'], $in['waktu'] . ':00',
                                   $in['keterangan'] !== '' ? $in['keterangan'] : 'Latihan rutin', $in['lokasi']]);
                    $_SESSION['flash'] = ['ok', 'Jadwal berhasil ditambahkan.'];
                    header('Location: ' . jd_url(['bulan' => substr($in['tanggal'], 0, 7), 'tgl' => $in['tanggal'], 'f' => $f]));
                    exit;
                }
            } catch (PDOException $ex) {
                $errTambah[] = 'Gagal menyimpan jadwal. Pastikan database/absensi.sql sudah dijalankan.';
            }
        }
    } elseif ($aksi === 'hapus') {
        try {
            if (!csrf_valid($_POST['csrf'] ?? null)) throw new RuntimeException('Sesi tidak valid, muat ulang halaman.');
            $mid = (int) ($_POST['id'] ?? 0);
            $st = $pdo->prepare('SELECT m.id FROM ekskul_pertemuan m JOIN ekskul e ON e.id = m.ekskul_id WHERE m.id = ? AND e.pembimbing_id = ?');
            $st->execute([$mid, $pid]);
            if (!$st->fetch()) throw new RuntimeException('Jadwal tidak ditemukan.');

            $st = $pdo->prepare('SELECT COUNT(*) FROM ekskul_absensi WHERE pertemuan_id = ?');
            $st->execute([$mid]);
            if ((int) $st->fetchColumn() > 0) throw new RuntimeException('Jadwal tidak dapat dihapus karena sudah ada presensi siswa.');

            $pdo->prepare('DELETE FROM ekskul_pertemuan WHERE id = ?')->execute([$mid]);
            $_SESSION['flash'] = ['ok', 'Jadwal dihapus.'];
        } catch (RuntimeException $ex) {
            $_SESSION['flash'] = ['err', $ex->getMessage()];
        } catch (PDOException $ex) {
            $_SESSION['flash'] = ['err', 'Gagal menghapus jadwal, coba lagi.'];
        }
        header('Location: ' . jd_url($state));
        exit;
    }
}

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

// ===================== DATA KALENDER =====================
$first   = new DateTimeImmutable($bulan . '-01');
$jmlHari = (int) $first->format('t');
$mulaiN  = (int) $first->format('N'); // 1 = Senin
$prev    = $first->modify('-1 month')->format('Y-m');
$next    = $first->modify('+1 month')->format('Y-m');
$namaBln = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
$judulBulan = $namaBln[(int) $first->format('n')] . ' ' . $first->format('Y');

$sql = 'SELECT m.id, m.ekskul_id, m.tanggal, m.jam, m.kegiatan, m.lokasi, e.nama
        FROM ekskul_pertemuan m JOIN ekskul e ON e.id = m.ekskul_id
        WHERE e.pembimbing_id = ? AND m.tanggal BETWEEN ? AND ?';
$par = [$pid, $bulan . '-01', $first->format('Y-m-t')];
if ($f) { $sql .= ' AND m.ekskul_id = ?'; $par[] = $f; }
$st = $pdo->prepare($sql . ' ORDER BY m.tanggal, m.jam, m.id');
$st->execute($par);
$perTgl = [];
foreach ($st->fetchAll() as $r) { $perTgl[$r['tanggal']][] = $r; }
$hariIni = $perTgl[$tgl] ?? [];

// Mendatang (lintas bulan)
$sql = 'SELECT m.tanggal, m.jam, e.nama
        FROM ekskul_pertemuan m JOIN ekskul e ON e.id = m.ekskul_id
        WHERE e.pembimbing_id = ? AND m.tanggal >= ?';
$par = [$pid, $today];
if ($f) { $sql .= ' AND m.ekskul_id = ?'; $par[] = $f; }
$st = $pdo->prepare($sql . ' ORDER BY m.tanggal, m.jam LIMIT 5');
$st->execute($par);
$mendatang = $st->fetchAll();

pembimbing_header('Jadwal', 'jadwal', $me);

if ($flash): ?>
    <div class="pb-flash <?= $flash[0] === 'ok' ? 'ok' : 'err' ?>" role="status"><?= e($flash[1]) ?></div>
<?php endif; ?>

<div class="pb-jd">
    <section class="pb-jd-cal">
        <div class="pb-jd-head">
            <div><h2>Kalender Kegiatan</h2><p>Kelola jadwal seluruh ekskul</p></div>
            <div class="pb-jd-tools">
                <form method="get" action="kelola_jadwal.php">
                    <input type="hidden" name="bulan" value="<?= e($bulan) ?>">
                    <select name="f" onchange="this.form.submit()" aria-label="Filter ekskul">
                        <option value="0">Semua ekskul</option>
                        <?php foreach ($ekskulku as $ek): ?>
                            <option value="<?= (int) $ek['id'] ?>" <?= $f === (int) $ek['id'] ? 'selected' : '' ?>><?= e($ek['nama']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </form>
                <?php if ($ekskulku): ?><a class="pb-jd-add" href="<?= e(jd_url($state + ['tambah' => 1])) ?>">＋ Tambah Jadwal</a><?php endif; ?>
            </div>
        </div>

        <div class="pb-jd-body">
            <div class="pb-jd-nav">
                <h3><?= e($judulBulan) ?></h3>
                <div>
                    <a href="<?= e(jd_url(['bulan' => $prev, 'f' => $f])) ?>" aria-label="Bulan sebelumnya">‹</a>
                    <a href="<?= e(jd_url(['bulan' => date('Y-m'), 'f' => $f])) ?>">Hari ini</a>
                    <a href="<?= e(jd_url(['bulan' => $next, 'f' => $f])) ?>" aria-label="Bulan berikutnya">›</a>
                </div>
            </div>

            <div class="pb-jd-grid">
                <?php foreach (['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'] as $h): ?><div class="pb-jd-dow"><?= $h ?></div><?php endforeach; ?>

                <?php for ($i = 1; $i < $mulaiN; $i++): ?><div class="pb-jd-cell pb-jd-blank"></div><?php endfor; ?>

                <?php for ($d = 1; $d <= $jmlHari; $d++):
                    $iso  = sprintf('%s-%02d', $bulan, $d);
                    $list = $perTgl[$iso] ?? [];
                    $cls  = 'pb-jd-cell' . ($iso === $tgl ? ' sel' : '');
                ?>
                    <a class="<?= $cls ?>" href="<?= e(jd_url(['bulan' => $bulan, 'tgl' => $iso, 'f' => $f])) ?>">
                        <span class="pb-jd-num<?= $iso === $today ? ' today' : '' ?>"><?= $d ?></span>
                        <?php foreach (array_slice($list, 0, 2) as $s): ?>
                            <i class="pb-jd-chip"><?= e(substr((string) $s['jam'], 0, 5)) ?> <?= e(explode(' ', trim((string) $s['nama']))[0]) ?></i>
                        <?php endforeach; ?>
                        <?php if (count($list) > 2): ?><i class="pb-jd-more">+<?= count($list) - 2 ?> lagi</i><?php endif; ?>
                    </a>
                <?php endfor; ?>

                <?php $sisa = (7 - (($mulaiN - 1 + $jmlHari) % 7)) % 7;
                      for ($i = 0; $i < $sisa; $i++): ?><div class="pb-jd-cell pb-jd-blank"></div><?php endfor; ?>
            </div>
        </div>
    </section>

    <aside class="pb-jd-side">
        <div class="pb-jd-day">
            <h3><?= e(tanggal_id($tgl)) ?></h3>
            <small><?= count($hariIni) ?> kegiatan</small>
        </div>

        <?php if (!$hariIni): ?>
            <p class="pb-jd-none">Tidak ada kegiatan pada tanggal ini.</p>
        <?php endif; ?>
        <?php foreach ($hariIni as $s): ?>
            <div class="pb-jd-item">
                <span class="pb-jd-time"><?= e(substr((string) $s['jam'], 0, 5)) ?></span>
                <div>
                    <b><?= e($s['kegiatan']) ?></b>
                    <small><?= e($s['nama']) ?> · <?= e($s['lokasi']) ?></small>
                <div class="pb-jd-acts">
                        <a class="pb-jd-abs" href="<?= e('kelola_absensi.php?' . http_build_query(['tab' => 'input', 'e' => $s['ekskul_id'], 'p' => $s['id']])) ?>">Isi presensi</a>
                        <form method="post" action="<?= e(jd_url($state)) ?>" onsubmit="return confirm('Hapus jadwal ini?')">
                    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="aksi" value="hapus">
                    <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
                    <button type="submit" class="pb-jd-del" aria-label="Hapus jadwal">Hapus</button>
                </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <div class="pb-jd-next">
            <h4>MENDATANG</h4>
            <?php foreach ($mendatang as $m): ?>
                <div><span><?= e($m['nama']) ?></span><em><?= e(tanggal_id($m['tanggal'])) ?> · <?= e(substr((string) $m['jam'], 0, 5)) ?></em></div>
            <?php endforeach; ?>
            <?php if (!$mendatang): ?><p class="pb-jd-none">Belum ada jadwal mendatang.</p><?php endif; ?>
        </div>
    </aside>
</div>

<?php if ($bukaTambah && $ekskulku): $tutupUrl = e(jd_url($state)); ?>
    <div class="pb-modal-back">
        <div class="pb-modal pb-modal--form" role="dialog" aria-modal="true" aria-label="Tambah Jadwal">
            <form method="post" action="<?= e(jd_url($state)) ?>">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="aksi" value="tambah">
                <header><h3>Tambah Jadwal</h3><a href="<?= $tutupUrl ?>" aria-label="Tutup">✕</a></header>
                <div class="pb-modal-body">
                    <?php if ($errTambah): ?>
                        <div class="pb-flash err" role="alert"><?php foreach ($errTambah as $m): ?><div><?= e($m) ?></div><?php endforeach; ?></div>
                    <?php endif; ?>
                    <label class="pb-fl"><span>Ekstrakurikuler</span>
                        <select name="ekskul_id" required>
                            <?php foreach ($ekskulku as $ek): ?>
                                <option value="<?= (int) $ek['id'] ?>" <?= $in['ekskul_id'] === (int) $ek['id'] ? 'selected' : '' ?>><?= e($ek['nama']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <div class="pb-fl-row">
                        <label class="pb-fl"><span>Tanggal</span><input type="date" name="tanggal" value="<?= e($in['tanggal']) ?>" required></label>
                        <label class="pb-fl"><span>Waktu</span><input type="time" name="waktu" value="<?= e($in['waktu']) ?>" required></label>
                    </div>
                    <label class="pb-fl"><span>Lokasi</span><input type="text" name="lokasi" value="<?= e($in['lokasi']) ?>" maxlength="100" placeholder="cth. Lapangan Utama" required></label>
                    <label class="pb-fl"><span>Keterangan</span><input type="text" name="keterangan" value="<?= e($in['keterangan']) ?>" maxlength="150" placeholder="cth. Latihan rutin"></label>
                </div>
                <footer>
                    <a class="pb-close" href="<?= $tutupUrl ?>">Batal</a>
                    <button type="submit" class="pb-save">Simpan</button>
                </footer>
            </form>
        </div>
    </div>
<?php endif; ?>
<?php pembimbing_footer();
