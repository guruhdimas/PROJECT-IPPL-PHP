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
const ASPEK = ['Kemandirian', 'Kerjasama', 'Kedisiplinan', 'Kreativitas', 'Kepemimpinan', 'Kehadiran'];

function kelas_lbl2(?string $k): string { return $k ? (string) preg_replace('/^Kelas\s+/i', '', $k) : '-'; }

/** Anggota aktif satu ekskul milik pembimbing (null kalau bukan miliknya). */
function anggota_ekskul(PDO $pdo, int $pid, int $eid, int $siswaId = 0): ?array
{
    $sql = "SELECT u.id, u.nama, u.kelas, e.nama AS ekskul
            FROM pendaftaran p JOIN users u ON u.id = p.siswa_id JOIN ekskul e ON e.id = p.ekskul_id
            WHERE e.id = ? AND e.pembimbing_id = ? AND p.status = 'disetujui'" . ($siswaId ? ' AND u.id = ?' : '') . ' ORDER BY u.nama';
    $st = $pdo->prepare($sql);
    $st->execute($siswaId ? [$eid, $pid, $siswaId] : [$eid, $pid]);
    $rows = $st->fetchAll();
    return $siswaId ? ($rows[0] ?? null) : $rows;
}

// ===== Simpan nilai aspek =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $eid = (int) ($_POST['ekskul'] ?? 0);
    $sid = (int) ($_POST['siswa'] ?? 0);
    try {
        if (!csrf_valid($_POST['csrf'] ?? null)) throw new RuntimeException('Sesi tidak valid, muat ulang halaman.');
        if (!anggota_ekskul($pdo, $pid, $eid, $sid)) throw new RuntimeException('Siswa tidak ditemukan di ekskul Anda.');

        $cari = $pdo->prepare('SELECT id FROM penilaian WHERE siswa_id = ? AND aspek = ?');
        $upd  = $pdo->prepare('UPDATE penilaian SET nilai = ?, predikat = ? WHERE id = ?');
        $ins  = $pdo->prepare('INSERT INTO penilaian (siswa_id, aspek, nilai, predikat) VALUES (?,?,?,?)');
        $n = 0;
        foreach (ASPEK as $a) {
            $raw = trim((string) ($_POST['nilai'][$a] ?? ''));
            if ($raw === '') continue;
            if (!ctype_digit($raw) || (int) $raw > 100) throw new RuntimeException('Nilai ' . $a . ' harus angka 0–100.');
            $v = (int) $raw;
            $p = $v >= 90 ? 'A' : ($v >= 80 ? 'B' : ($v >= 70 ? 'C' : 'D'));
            $cari->execute([$sid, $a]);
            $id = $cari->fetchColumn();
            if ($id) $upd->execute([$v, $p, $id]); else $ins->execute([$sid, $a, $v, $p]);
            $n++;
        }
        if (!$n) throw new RuntimeException('Isi minimal satu nilai aspek.');
        $_SESSION['flash'] = ['ok', 'Nilai aspek tersimpan (' . $n . ' aspek).'];
    } catch (RuntimeException $ex) {
        $_SESSION['flash'] = ['err', $ex->getMessage()];
    } catch (PDOException $ex) {
        $_SESSION['flash'] = ['err', 'Gagal menyimpan nilai, coba lagi.'];
    }
    header('Location: ' . pb_url('penilaian_pembimbing.php', ['ekskul' => $eid, 'siswa' => $sid]));
    exit;
}

$eid = (int) ($_GET['ekskul'] ?? 0);
$sid = (int) ($_GET['siswa'] ?? 0);
$ek  = null;
foreach ($mine as $e) { if ((int) $e['id'] === $eid) $ek = $e; }

pembimbing_header('Indeks Penilaian', 'penilaian', $me);
pb_flash();

// ---------- Level 1: pilih ekskul ----------
if (!$ek): ?>
<section class="pb-panel">
    <div class="pb-head2"><div><h2>Indeks Penilaian</h2><p>Rekapan Penilaian Pencapaian Siswa</p></div></div>
    <div class="pb-panel-body">
        <div class="pbn-grid">
            <?php foreach ($mine as $e): $persen = $e['kuota'] > 0 ? min(100, (int) round($e['terisi'] / $e['kuota'] * 100)) : 0; ?>
                <article class="pbn-card">
                    <h3><?= e($e['nama']) ?></h3>
                    <p><?= e($e['deskripsi']) ?></p>
                    <small class="pb-mut">Pembimbing: <b style="color:#111"><?= e($e['pembimbing']) ?></b></small>
                    <div class="pbn-q"><span>Jumlah Siswa</span><b><?= (int) $e['terisi'] ?>/<?= (int) $e['kuota'] ?></b></div>
                    <div class="pbn-bar"><i style="width:<?= $persen ?>%"></i></div>
                    <a class="pb-pill" href="<?= e(pb_url('penilaian_pembimbing.php', ['ekskul' => $e['id']])) ?>">Lihat</a>
                </article>
            <?php endforeach; ?>
            <?php if (!$mine): ?><p class="pb-empty">Anda belum mengampu ekskul.</p><?php endif; ?>
        </div>
    </div>
</section>

<?php
// ---------- Level 2: daftar siswa ----------
elseif (!$sid):
    $siswa = anggota_ekskul($pdo, $pid, $eid);
?>
<a href="penilaian_pembimbing.php" class="pb-back">‹ Pilih ekskul lain</a>
<section class="pb-panel">
    <div class="pb-head2"><div><h2>Indeks Penilaian</h2><p>Rekapan Penilaian Pencapaian Siswa</p></div></div>
    <div class="pb-panel-body">
        <div class="pbn-sub"><b><?= e($ek['nama']) ?></b><small class="pb-mut">Pembimbing: <b style="color:#111"><?= e($ek['pembimbing']) ?></b></small></div>
        <div class="pb-table pbn-table">
            <div class="pb-tr pb-th"><span>NAMA SISWA</span><span>KELAS</span><span></span></div>
            <?php foreach ($siswa as $s): ?>
                <div class="pb-tr">
                    <span class="pb-nm"><?= e($s['nama']) ?></span>
                    <span class="pb-mut"><?= e(kelas_lbl2($s['kelas'])) ?></span>
                    <span><a class="pbn-raport" href="<?= e(pb_url('penilaian_pembimbing.php', ['ekskul' => $eid, 'siswa' => $s['id']])) ?>">Lihat Raport</a></span>
                </div>
            <?php endforeach; ?>
            <?php if (!$siswa): ?><p class="pb-empty">Belum ada anggota aktif di ekskul ini.</p><?php endif; ?>
        </div>
    </div>
</section>

<?php
// ---------- Level 3: raport satu siswa ----------
else:
    $s = anggota_ekskul($pdo, $pid, $eid, $sid);
    if (!$s) { echo '<p class="pb-empty">Siswa tidak ditemukan.</p>'; pembimbing_footer(); exit; }

    // Kehadiran dari absensi
    $total = 0; $hadir = 0;
    try {
        $q = $pdo->prepare("SELECT COUNT(*) AS total, COALESCE(SUM(a.status = 'hadir'), 0) AS hadir
                            FROM pertemuan m LEFT JOIN presensi a ON a.pertemuan_id = m.id AND a.siswa_id = ?
                            WHERE m.ekskul_id = ? AND m.tanggal <= ?");
        $q->execute([$sid, $eid, date('Y-m-d')]);
        $r = $q->fetch(); $total = (int) $r['total']; $hadir = (int) $r['hadir'];
    } catch (PDOException $ex) { /* tabel absensi belum ada */ }
    $kehadiran = $total ? (int) round($hadir / $total * 100) : 0;

    $q = $pdo->prepare('SELECT aspek, nilai FROM penilaian WHERE siswa_id = ? ORDER BY id');
    $q->execute([$sid]);
    $aspek = [];
    foreach ($q->fetchAll() as $r) { $aspek[$r['aspek']] = (int) $r['nilai']; }

    $detail = [];
    try {
        $q = $pdo->prepare('SELECT t.judul, g.nilai FROM ekskul_tugas t JOIN pengumpulan g ON g.tugas_id = t.id AND g.siswa_id = ?
                            WHERE t.ekskul_id = ? AND g.nilai IS NOT NULL ORDER BY t.deadline');
        $q->execute([$sid, $eid]);
        $detail = $q->fetchAll();
    } catch (PDOException $ex) { /* kolom nilai belum ada */ }
    $rata = $detail ? nilai_ke_huruf(array_sum(array_map(fn ($d) => NILAI_HURUF[$d['nilai']] ?? 0, $detail)) / count($detail)) : '–';
?>
<a href="<?= e(pb_url('penilaian_pembimbing.php', ['ekskul' => $eid])) ?>" class="pb-back">‹ Kembali ke daftar siswa</a>
<section class="pb-panel">
    <div class="pb-head2"><div><h2><?= e($s['nama']) ?></h2><p><?= e(kelas_lbl2($s['kelas'])) ?> · <?= e($s['ekskul']) ?></p></div></div>
    <div class="pb-panel-body pbn-raportwrap">
        <div class="pbn-left">
            <div class="pbn-two">
                <div class="pbn-box"><small>Rata-Rata Nilai</small><strong><?= e($rata) ?></strong></div>
                <div class="pbn-box"><small>Kehadiran</small><strong><?= $kehadiran ?>%</strong></div>
            </div>
            <div class="pbn-box pbn-bars">
                <div class="pbn-b"><div><span>Kehadiran</span><b><?= $kehadiran ?>%</b></div><div class="pbn-bar"><i style="width:<?= $kehadiran ?>%"></i></div></div>
                <?php foreach ($aspek as $nama => $v): if ($nama === 'Kehadiran') continue; ?>
                    <div class="pbn-b"><div><span><?= e($nama) ?></span><b><?= $v ?>%</b></div><div class="pbn-bar"><i style="width:<?= min(100, $v) ?>%"></i></div></div>
                <?php endforeach; ?>
                <?php if (!$aspek): ?><p class="pb-hint">Nilai aspek belum diisi. Isi lewat form di bawah.</p><?php endif; ?>
            </div>
        </div>
        <div class="pbn-box pbn-detail">
            <b>Detail penilaian</b>
            <?php foreach ($detail as $d): ?><div><span><?= e($d['judul']) ?></span><b><?= e($d['nilai']) ?></b></div><?php endforeach; ?>
            <?php if (!$detail): ?><p class="pb-hint">Belum ada tugas yang dinilai.</p><?php endif; ?>
        </div>
    </div>

    <form method="post" class="pb-panel-body pbn-form pb-form">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="ekskul" value="<?= $eid ?>">
        <input type="hidden" name="siswa" value="<?= $sid ?>">
        <h3>Input Nilai Aspek <small>(0–100, kosongkan jika tidak diubah)</small></h3>
        <div class="pbn-inputs">
            <?php foreach (ASPEK as $a): ?>
                <label><?= e($a) ?><input type="number" name="nilai[<?= e($a) ?>]" min="0" max="100" value="<?= isset($aspek[$a]) ? (int) $aspek[$a] : '' ?>"></label>
            <?php endforeach; ?>
        </div>
        <button type="submit" class="pb-btn">✓ Simpan Nilai</button>
    </form>
</section>
<?php endif;
pembimbing_footer();
