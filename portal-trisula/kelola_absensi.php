<?php
declare(strict_types=1);

require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/ekskul.php';
require __DIR__ . '/includes/pembimbing_layout.php';

const ABSENSI_STATUS = ['hadir', 'izin', 'sakit', 'alpa'];
const ABSENSI_JENDELA_HARI = 7; // sama dengan halaman Absensi siswa

$session = require_role('pembimbing');
$pdo     = db();
$me      = pembimbing_user($pdo, (int) $session['id']);
$pid     = (int) $me['id'];
$today   = date('Y-m-d');
$batas   = date('Y-m-d', strtotime('-' . ABSENSI_JENDELA_HARI . ' days'));

function ab_url(array $p = []): string
{
    $q = http_build_query(array_filter($p, static fn ($v) => $v !== '' && $v !== null && $v !== 0));
    return 'kelola_absensi.php' . ($q !== '' ? '?' . $q : '');
}

function ab_kelas(?string $k): string
{
    return $k ? (string) preg_replace('/^Kelas\s+/i', '', $k) : '-';
}

// Anggota aktif sebuah ekskul
function ab_anggota(PDO $pdo, int $eid): array
{
    $st = $pdo->prepare("SELECT p.siswa_id, u.nama, u.kelas
                         FROM pendaftaran p JOIN users u ON u.id = p.siswa_id
                         WHERE p.ekskul_id = ? AND p.status = 'disetujui' ORDER BY u.nama");
    $st->execute([$eid]);
    return $st->fetchAll();
}

// ===================== SIMPAN PRESENSI (POST) =====================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mid = (int) ($_POST['pertemuan_id'] ?? 0);
    $eidBalik = 0;
    try {
        if (!csrf_valid($_POST['csrf'] ?? null)) throw new RuntimeException('Sesi tidak valid, muat ulang halaman.');

        $st = $pdo->prepare('SELECT m.id, m.ekskul_id, m.tanggal
                             FROM ekskul_pertemuan m JOIN ekskul e ON e.id = m.ekskul_id
                             WHERE m.id = ? AND e.pembimbing_id = ?');
        $st->execute([$mid, $pid]);
        $m = $st->fetch();
        if (!$m) throw new RuntimeException('Pertemuan tidak ditemukan.');
        $eidBalik = (int) $m['ekskul_id'];
        if ($m['tanggal'] > $today) throw new RuntimeException('Presensi baru dapat diisi pada hari pertemuan.');

        $anggota = [];
        foreach (ab_anggota($pdo, $eidBalik) as $a) { $anggota[(int) $a['siswa_id']] = true; }

        $pilih = $_POST['status'] ?? [];
        if (!is_array($pilih)) $pilih = [];

        $pdo->beginTransaction();
        $ins = $pdo->prepare('INSERT INTO ekskul_absensi (pertemuan_id, siswa_id, status) VALUES (?,?,?)
                              ON DUPLICATE KEY UPDATE status = ?');
        $n = 0;
        foreach ($pilih as $sid => $val) {
            $sid = (int) $sid;
            if (!isset($anggota[$sid]) || !in_array($val, ABSENSI_STATUS, true)) continue;
            $ins->execute([$mid, $sid, $val, $val]);
            $n++;
        }
        if ($n === 0) throw new RuntimeException('Pilih status kehadiran untuk minimal satu siswa.');
        $pdo->commit();
        $_SESSION['flash'] = ['ok', "Presensi $n siswa berhasil disimpan."];
    } catch (RuntimeException $ex) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $_SESSION['flash'] = ['err', $ex->getMessage()];
    } catch (PDOException $ex) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $_SESSION['flash'] = ['err', 'Gagal menyimpan presensi. Pastikan database/absensi.sql sudah dijalankan.'];
    }
    header('Location: ' . ab_url(['tab' => 'input', 'e' => $eidBalik, 'p' => $mid]));
    exit;
}

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

// ===================== TAMPILAN =====================
$st = $pdo->prepare('SELECT id, nama FROM ekskul WHERE pembimbing_id = ? ORDER BY nama');
$st->execute([$pid]);
$ekskulku = $st->fetchAll();
$idEkskul = array_map(static fn ($r) => (int) $r['id'], $ekskulku);

$tab = ($_GET['tab'] ?? '') === 'rekap' ? 'rekap' : 'input';
$e   = (int) ($_GET['e'] ?? 0);
if (!in_array($e, $idEkskul, true)) {
    // Tanpa pilihan: buka ekskul yang punya pertemuan terbaru (sampai hari ini), bukan sekadar yang pertama di daftar
    $e = $idEkskul[0] ?? 0;
    if ($idEkskul) {
        $ph = implode(',', array_fill(0, count($idEkskul), '?'));
        $st = $pdo->prepare("SELECT ekskul_id FROM ekskul_pertemuan WHERE ekskul_id IN ($ph)
                             ORDER BY (tanggal <= ?) DESC, tanggal DESC, id DESC LIMIT 1");
        $st->execute(array_merge($idEkskul, [$today]));
        $e = (int) ($st->fetchColumn() ?: $e);
    }
}

$pertemuan = [];
$cur = null;
$anggota = [];
$status  = [];
if ($e) {
    $st = $pdo->prepare('SELECT id, tanggal, jam, kegiatan, lokasi FROM ekskul_pertemuan
                         WHERE ekskul_id = ? ORDER BY tanggal DESC, jam DESC, id DESC LIMIT 60');
    $st->execute([$e]);
    $pertemuan = $st->fetchAll();

    $p = (int) ($_GET['p'] ?? 0);
    foreach ($pertemuan as $r) { if ((int) $r['id'] === $p) { $cur = $r; break; } }
    if (!$cur && $pertemuan) {
        foreach ($pertemuan as $r) { if ($r['tanggal'] <= $today) { $cur = $r; break; } }   // yang terbaru sampai hari ini
        if (!$cur) $cur = end($pertemuan);                                                  // belum ada: yang paling dekat
    }
    if ($cur) {
        $anggota = ab_anggota($pdo, $e);
        $st = $pdo->prepare('SELECT siswa_id, status FROM ekskul_absensi WHERE pertemuan_id = ?');
        $st->execute([$cur['id']]);
        foreach ($st->fetchAll() as $r) { $status[(int) $r['siswa_id']] = (string) $r['status']; }
    }
}
$namaEkskul = '';
foreach ($ekskulku as $r) { if ((int) $r['id'] === $e) $namaEkskul = (string) $r['nama']; }
$belumBuka = $cur && $cur['tanggal'] > $today;
$label = ['hadir' => 'Hadir', 'izin' => 'Izin', 'sakit' => 'Sakit', 'alpa' => 'Alpa'];

pembimbing_header('Absensi', 'absensi', $me);

if ($flash): ?>
    <div class="pb-flash <?= $flash[0] === 'ok' ? 'ok' : 'err' ?>" role="status"><?= e($flash[1]) ?></div>
<?php endif; ?>

<?php if (!$ekskulku): ?>
    <section class="pb-panel"><p class="pb-empty">Anda belum mengampu ekskul apa pun.</p></section>
<?php else: ?>

<div class="pb-tabs">
    <a href="<?= e(ab_url(['tab' => 'input', 'e' => $e, 'p' => $cur['id'] ?? 0])) ?>" class="<?= $tab === 'input' ? 'on' : '' ?>">Input Presensi</a>
    <a href="<?= e(ab_url(['tab' => 'rekap', 'e' => $e, 'p' => $cur['id'] ?? 0])) ?>" class="<?= $tab === 'rekap' ? 'on' : '' ?>">Rekap Anggota</a>
</div>

<form method="get" action="kelola_absensi.php" class="pb-ab-pick">
    <input type="hidden" name="tab" value="<?= e($tab) ?>">
    <label><span>Ekstrakurikuler</span>
        <select name="e" onchange="this.form.submit()">
            <?php foreach ($ekskulku as $ek): ?>
                <option value="<?= (int) $ek['id'] ?>" <?= $e === (int) $ek['id'] ? 'selected' : '' ?>><?= e($ek['nama']) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label><span>Pertemuan</span>
        <select name="p" onchange="this.form.submit()" <?= $pertemuan ? '' : 'disabled' ?>>
            <?php foreach ($pertemuan as $r): ?>
                <option value="<?= (int) $r['id'] ?>" <?= $cur && (int) $cur['id'] === (int) $r['id'] ? 'selected' : '' ?>>
                    <?= e(tanggal_id($r['tanggal'])) ?> · <?= e(substr((string) $r['jam'], 0, 5)) ?> · <?= e($r['kegiatan']) ?>
                </option>
            <?php endforeach; ?>
            <?php if (!$pertemuan): ?><option>Belum ada pertemuan</option><?php endif; ?>
        </select>
    </label>
</form>

<?php if (!$cur): ?>
    <section class="pb-panel"><p class="pb-empty">Ekskul ini belum punya pertemuan. Tambahkan lewat menu <a href="kelola_jadwal.php"><b>Jadwal</b></a>.</p></section>
<?php else: ?>

<section class="pb-panel">
    <div class="pb-panel-head">
        <h2><?= $tab === 'input' ? 'Input Presensi' : 'Rekap Anggota' ?></h2>
        <p><?= e($cur['kegiatan']) ?> · <?= e(tanggal_id($cur['tanggal'])) ?> · <?= e($namaEkskul) ?></p>
    </div>

    <div class="pb-panel-body">
    <?php if ($tab === 'input'): ?>
        <?php if ($belumBuka): ?><div class="pb-flash err" style="margin-bottom:14px">Pertemuan ini belum berlangsung. Presensi dapat diisi mulai <?= e(tanggal_id($cur['tanggal'])) ?>.</div><?php endif; ?>

        <form method="post" action="<?= e(ab_url(['tab' => 'input', 'e' => $e, 'p' => $cur['id']])) ?>">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="pertemuan_id" value="<?= (int) $cur['id'] ?>">

            <div class="pb-ab-table">
                <div class="pb-ab-tr pb-ab-th"><span>NAMA SISWA</span><span>KELAS</span><span>STATUS</span></div>
                <?php foreach ($anggota as $a): $sid = (int) $a['siswa_id']; $now = $status[$sid] ?? ''; ?>
                    <div class="pb-ab-tr">
                        <span class="pb-nm"><?= e($a['nama']) ?></span>
                        <span class="pb-mut"><?= e(ab_kelas($a['kelas'])) ?></span>
                        <span class="pb-ab-opts">
                            <?php foreach (ABSENSI_STATUS as $s): ?>
                                <label class="pb-ab-opt">
                                    <input type="radio" name="status[<?= $sid ?>]" value="<?= $s ?>" <?= $now === $s ? 'checked' : '' ?> <?= $belumBuka ? 'disabled' : '' ?>>
                                    <span><?= $label[$s] ?></span>
                                </label>
                            <?php endforeach; ?>
                        </span>
                    </div>
                <?php endforeach; ?>
                <?php if (!$anggota): ?><p class="pb-empty">Belum ada anggota aktif di ekskul ini.</p><?php endif; ?>
            </div>

            <?php if ($anggota): ?>
                <div class="pb-ab-foot">
                    <button type="submit" class="pb-save" <?= $belumBuka ? 'disabled' : '' ?>>✓ Simpan Presensi</button>
                </div>
            <?php endif; ?>
        </form>

    <?php else: ?>
        <div class="pb-ab-table">
            <div class="pb-ab-tr pb-ab-th"><span>NAMA SISWA</span><span>KELAS</span><span>STATUS</span></div>
            <?php foreach ($anggota as $a):
                $sid = (int) $a['siswa_id'];
                $now = $status[$sid] ?? '';
                if ($now === '') { $now = ($cur['tanggal'] < $batas) ? 'alpa' : ($belumBuka ? 'belum' : 'kosong'); }
                $teks = $label[$now] ?? ($now === 'belum' ? '-' : 'Belum diisi');
            ?>
                <div class="pb-ab-tr">
                    <span class="pb-nm"><?= e($a['nama']) ?></span>
                    <span class="pb-mut"><?= e(ab_kelas($a['kelas'])) ?></span>
                    <span><i class="pb-ab-st pb-ab-st-<?= e($now) ?>"><?= e($teks) ?></i></span>
                </div>
            <?php endforeach; ?>
            <?php if (!$anggota): ?><p class="pb-empty">Belum ada anggota aktif di ekskul ini.</p><?php endif; ?>
        </div>
    <?php endif; ?>
    </div>
</section>

<?php endif; ?>
<?php endif; ?>
<?php pembimbing_footer();
