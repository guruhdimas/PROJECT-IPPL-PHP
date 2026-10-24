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
$mine    = pb_my_ekskul($pdo, $pid);
$myIds   = array_map('intval', array_column($mine, 'id'));
$today   = date('Y-m-d');
const STATUS_ABS = ['hadir' => 'Hadir', 'izin' => 'Izin', 'sakit' => 'Sakit', 'alpa' => 'Alpa'];

// ===== Simpan presensi =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $eid = (int) ($_POST['ekskul'] ?? 0);
    $mid = (int) ($_POST['pertemuan'] ?? 0);
    try {
        if (!csrf_valid($_POST['csrf'] ?? null)) throw new RuntimeException('Sesi tidak valid, muat ulang halaman.');

        // Pertemuan harus milik ekskul yang diampu dan sudah berlangsung
        $st = $pdo->prepare('SELECT m.id, m.tanggal FROM pertemuan m JOIN ekskul e ON e.id = m.ekskul_id WHERE m.id = ? AND e.id = ? AND e.pembimbing_id = ?');
        $st->execute([$mid, $eid, $pid]);
        $m = $st->fetch();
        if (!$m) throw new RuntimeException('Pertemuan tidak ditemukan.');
        if ($m['tanggal'] > $today) throw new RuntimeException('Presensi baru bisa diisi pada hari pertemuan.');

        // Hanya siswa yang benar-benar anggota aktif
        $st = $pdo->prepare("SELECT siswa_id FROM pendaftaran WHERE ekskul_id = ? AND status = 'disetujui'");
        $st->execute([$eid]);
        $anggota = array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN));

        $ins = $pdo->prepare('INSERT INTO presensi (pertemuan_id, siswa_id, status) VALUES (?,?,?) ON DUPLICATE KEY UPDATE status = VALUES(status)');
        $n = 0;
        $pdo->beginTransaction();
        foreach ((array) ($_POST['status'] ?? []) as $sid => $stt) {
            if (in_array((int) $sid, $anggota, true) && isset(STATUS_ABS[$stt])) { $ins->execute([$mid, (int) $sid, $stt]); $n++; }
        }
        $pdo->commit();
        if (!$n) throw new RuntimeException('Pilih status kehadiran minimal untuk satu siswa.');
        $_SESSION['flash'] = ['ok', 'Presensi tersimpan untuk ' . $n . ' siswa.'];
    } catch (RuntimeException $ex) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $_SESSION['flash'] = ['err', $ex->getMessage()];
    } catch (PDOException $ex) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $_SESSION['flash'] = ['err', 'Gagal menyimpan presensi, coba lagi.'];
    }
    header('Location: ' . pb_url('absensi_pembimbing.php', ['ekskul' => $eid, 'pertemuan' => $mid]));
    exit;
}

$tab = ($_GET['tab'] ?? '') === 'rekap' ? 'rekap' : 'input';

$eid = (int) ($_GET['ekskul'] ?? 0);
if (!in_array($eid, $myIds, true)) $eid = $myIds[0] ?? 0;

// Pertemuan yang sudah berlangsung (terbaru dulu)
$pertemuan = [];
if ($eid) {
    $st = $pdo->prepare('SELECT id, judul, tanggal, jam FROM pertemuan WHERE ekskul_id = ? AND tanggal <= ? ORDER BY tanggal DESC, jam DESC, id DESC');
    $st->execute([$eid, $today]);
    $pertemuan = $st->fetchAll();
}
$mid = (int) ($_GET['pertemuan'] ?? 0);
$cur = null;
foreach ($pertemuan as $p) { if ((int) $p['id'] === $mid) { $cur = $p; break; } }
if (!$cur && $pertemuan) $cur = $pertemuan[0];

$anggota = []; $status = [];
if ($cur) {
    $st = $pdo->prepare("SELECT u.id, u.nama, u.kelas FROM pendaftaran p JOIN users u ON u.id = p.siswa_id
                         WHERE p.ekskul_id = ? AND p.status = 'disetujui' ORDER BY u.nama");
    $st->execute([$eid]);
    $anggota = $st->fetchAll();
    $st = $pdo->prepare('SELECT siswa_id, status FROM presensi WHERE pertemuan_id = ?');
    $st->execute([$cur['id']]);
    foreach ($st->fetchAll() as $r) { $status[(int) $r['siswa_id']] = $r['status']; }
}
function kelas_lbl(?string $k): string { return $k ? (string) preg_replace('/^Kelas\s+/i', '', $k) : '-'; }

pembimbing_header('Absensi', 'absensi', $me);
pb_flash();
?>
<div class="pb-tabs">
    <a href="<?= e(pb_url('absensi_pembimbing.php', ['ekskul' => $eid, 'pertemuan' => $cur['id'] ?? 0])) ?>" class="<?= $tab === 'input' ? 'on' : '' ?>">Input Presensi</a>
    <a href="<?= e(pb_url('absensi_pembimbing.php', ['ekskul' => $eid, 'pertemuan' => $cur['id'] ?? 0, 'tab' => 'rekap'])) ?>" class="<?= $tab === 'rekap' ? 'on' : '' ?>">Rekap Anggota</a>
</div>

<section class="pb-panel">
    <div class="pb-panel-head pb-split">
        <div>
            <h2><?= $tab === 'input' ? 'Input Presensi' : 'Rekap Presensi' ?></h2>
            <p><?= $cur ? e($cur['judul'] . ' · ' . pb_tgl($cur['tanggal'])) : 'Belum ada pertemuan' ?></p>
        </div>
        <form method="get" class="pba-pick">
            <input type="hidden" name="tab" value="<?= e($tab) ?>">
            <select name="ekskul" onchange="this.form.pertemuan.value=0;this.form.submit()" aria-label="Pilih ekskul">
                <?php foreach ($mine as $e): ?><option value="<?= (int) $e['id'] ?>" <?= $eid === (int) $e['id'] ? 'selected' : '' ?>><?= e($e['nama']) ?></option><?php endforeach; ?>
            </select>
            <select name="pertemuan" onchange="this.form.submit()" aria-label="Pilih pertemuan">
                <?php foreach ($pertemuan as $p): ?>
                    <option value="<?= (int) $p['id'] ?>" <?= $cur && (int) $cur['id'] === (int) $p['id'] ? 'selected' : '' ?>><?= e(pb_tgl($p['tanggal']) . ' — ' . $p['judul']) ?></option>
                <?php endforeach; ?>
                <?php if (!$pertemuan): ?><option value="0">Belum ada pertemuan</option><?php endif; ?>
            </select>
        </form>
    </div>

    <div class="pb-panel-body">
    <?php if (!$mine): ?>
        <p class="pb-empty">Anda belum mengampu ekskul.</p>
    <?php elseif (!$cur): ?>
        <p class="pb-empty">Belum ada pertemuan yang sudah berlangsung. Tambahkan jadwal lewat menu <a href="jadwal_pembimbing.php" style="color:#0f5c3a;font-weight:700">Jadwal</a>.</p>
    <?php elseif (!$anggota): ?>
        <p class="pb-empty">Belum ada anggota aktif di ekskul ini. Setujui pendaftar lewat <a href="kelola_anggota.php?ekskul=<?= $eid ?>" style="color:#0f5c3a;font-weight:700">Kelola Anggota</a>.</p>
    <?php elseif ($tab === 'input'): ?>
        <form method="post">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="ekskul" value="<?= $eid ?>">
            <input type="hidden" name="pertemuan" value="<?= (int) $cur['id'] ?>">
            <div class="pb-table pba-table">
                <div class="pb-tr pb-th"><span>NAMA SISWA</span><span>KELAS</span><span>STATUS</span></div>
                <?php foreach ($anggota as $a): $now = $status[(int) $a['id']] ?? ''; ?>
                    <div class="pb-tr">
                        <span class="pb-nm"><?= e($a['nama']) ?></span>
                        <span class="pb-mut"><?= e(kelas_lbl($a['kelas'])) ?></span>
                        <span class="pba-opts" role="radiogroup" aria-label="Status <?= e($a['nama']) ?>">
                            <?php foreach (STATUS_ABS as $val => $lbl): ?>
                                <label class="pba-opt"><input type="radio" name="status[<?= (int) $a['id'] ?>]" value="<?= $val ?>" <?= $now === $val ? 'checked' : '' ?>><span><?= $lbl ?></span></label>
                            <?php endforeach; ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="pba-save"><button type="submit" class="pb-btn">✓ Simpan Presensi</button></div>
        </form>
    <?php else: ?>
        <div class="pb-table pba-table pba-rekap">
            <div class="pb-tr pb-th"><span>NAMA SISWA</span><span>KELAS</span><span>STATUS</span></div>
            <?php foreach ($anggota as $a): $now = $status[(int) $a['id']] ?? ''; ?>
                <div class="pb-tr">
                    <span class="pb-nm"><?= e($a['nama']) ?></span>
                    <span class="pb-mut"><?= e(kelas_lbl($a['kelas'])) ?></span>
                    <span class="pba-s pba-<?= e($now ?: 'none') ?>"><?= $now ? e(STATUS_ABS[$now]) : 'Belum diisi' ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    </div>
</section>
<?php pembimbing_footer();
