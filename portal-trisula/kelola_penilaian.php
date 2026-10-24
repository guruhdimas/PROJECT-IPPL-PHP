<?php
declare(strict_types=1);

require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/ekskul.php';
require __DIR__ . '/includes/pembimbing_layout.php';

const PENILAIAN_ASPEK  = ['Kemandirian', 'Kerjasama', 'Kedisiplinan', 'Kreativitas', 'Kepemimpinan'];
const PENILAIAN_JENDELA_HARI = 7; // sama dengan halaman Absensi

$session = require_role('pembimbing');
$pdo     = db();
$me      = pembimbing_user($pdo, (int) $session['id']);
$pid     = (int) $me['id'];
$today   = date('Y-m-d');
$batas   = date('Y-m-d', strtotime('-' . PENILAIAN_JENDELA_HARI . ' days'));

function pn_url(array $p = []): string
{
    $q = http_build_query(array_filter($p, static fn ($v) => $v !== '' && $v !== null && $v !== 0));
    return 'kelola_penilaian.php' . ($q !== '' ? '?' . $q : '');
}

function pn_kelas(?string $k): string
{
    return $k ? (string) preg_replace('/^Kelas\s+/i', '', $k) : '-';
}

// Angka (0-100) -> huruf
function pn_huruf(float $n): string
{
    if ($n >= 97) return 'A+';
    if ($n >= 93) return 'A';
    if ($n >= 90) return 'A-';
    if ($n >= 87) return 'B+';
    if ($n >= 83) return 'B';
    if ($n >= 80) return 'B-';
    if ($n >= 77) return 'C+';
    if ($n >= 70) return 'C';
    return 'D';
}

// Huruf nilai tugas -> poin
function pn_poin(string $h): ?int
{
    return ['A+' => 98, 'A' => 95, 'A-' => 91, 'B+' => 88, 'B' => 85, 'B-' => 81, 'C+' => 78, 'C' => 73, 'D' => 60][$h] ?? null;
}

// Ekskul yang diampu + jumlah anggota aktif
$st = $pdo->prepare("SELECT e.id, e.nama, e.deskripsi, e.pembimbing, e.kuota,
                            (SELECT COUNT(*) FROM pendaftaran p WHERE p.ekskul_id = e.id AND p.status = 'disetujui') AS jml
                     FROM ekskul e WHERE e.pembimbing_id = ? ORDER BY e.nama");
$st->execute([$pid]);
$ekskulku = $st->fetchAll();

$e   = (int) ($_GET['e'] ?? 0);
$sid = (int) ($_GET['s'] ?? 0);
$cur = null;
foreach ($ekskulku as $r) { if ((int) $r['id'] === $e) { $cur = $r; break; } }

function pn_anggota(PDO $pdo, int $eid, int $sid): ?array
{
    $st = $pdo->prepare("SELECT u.id, u.nama, u.kelas FROM pendaftaran p JOIN users u ON u.id = p.siswa_id
                         WHERE p.ekskul_id = ? AND p.siswa_id = ? AND p.status = 'disetujui'");
    $st->execute([$eid, $sid]);
    return $st->fetch() ?: null;
}

// ===================== SIMPAN PENILAIAN (POST) =====================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $eid = (int) ($_POST['e'] ?? 0);
    $sidp = (int) ($_POST['s'] ?? 0);
    $balik = pn_url(['e' => $eid, 's' => $sidp]);
    try {
        if (!csrf_valid($_POST['csrf'] ?? null)) throw new RuntimeException('Sesi tidak valid, muat ulang halaman.');
        $punya = false;
        foreach ($ekskulku as $r) { if ((int) $r['id'] === $eid) $punya = true; }
        if (!$punya || !pn_anggota($pdo, $eid, $sidp)) throw new RuntimeException('Siswa tidak ditemukan di ekskul Anda.');

        $sem = trim((string) ($_POST['semester'] ?? ''));
        if ($sem === '' || mb_strlen($sem) > 30) throw new RuntimeException('Semester wajib diisi (maks 30 karakter), contoh: Ganjil 2026.');

        $baris = [];
        foreach (PENILAIAN_ASPEK as $i => $aspek) {
            $v = trim((string) ($_POST['nilai'][$i] ?? ''));
            if ($v === '' || !ctype_digit($v) || (int) $v > 100) throw new RuntimeException("Nilai $aspek harus berupa angka 0–100.");
            $cat = trim((string) ($_POST['catatan'][$i] ?? ''));
            if (mb_strlen($cat) > 255) throw new RuntimeException("Catatan $aspek maksimal 255 karakter.");
            $baris[] = [$aspek, (int) $v, $cat, $i + 1];
        }

        $pdo->beginTransaction();
        $pdo->prepare('DELETE FROM ekskul_nilai WHERE siswa_id = ? AND semester = ?')->execute([$sidp, $sem]);
        $ins = $pdo->prepare('INSERT INTO ekskul_nilai (siswa_id, aspek, nilai, catatan, semester, urutan) VALUES (?,?,?,?,?,?)');
        foreach ($baris as [$aspek, $v, $cat, $urut]) { $ins->execute([$sidp, $aspek, $v, $cat, $sem, $urut]); }
        $pdo->commit();
        $_SESSION['flash'] = ['ok', 'Penilaian tersimpan dan langsung tampil di halaman siswa.'];
    } catch (RuntimeException $ex) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $_SESSION['flash'] = ['err', $ex->getMessage()];
        $balik = pn_url(['e' => $eid, 's' => $sidp, 'ubah' => 1]);
    } catch (PDOException $ex) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $_SESSION['flash'] = ['err', 'Gagal menyimpan penilaian. Pastikan database/penilaian.sql sudah dijalankan.'];
        $balik = pn_url(['e' => $eid, 's' => $sidp, 'ubah' => 1]);
    }
    header('Location: ' . $balik);
    exit;
}

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

// ===================== DATA TAMPILAN =====================
$anggota = [];
$siswa   = null;
if ($cur) {
    $st = $pdo->prepare("SELECT u.id, u.nama, u.kelas FROM pendaftaran p JOIN users u ON u.id = p.siswa_id
                         WHERE p.ekskul_id = ? AND p.status = 'disetujui' ORDER BY u.nama");
    $st->execute([$cur['id']]);
    $anggota = $st->fetchAll();
    if ($sid) $siswa = pn_anggota($pdo, (int) $cur['id'], $sid);
}

// Raport
$hadirPct = null; $jmlPertemuan = 0; $aspekRows = []; $semester = ''; $tugasRows = []; $rata = null; $rataNilai = null;
if ($cur && $siswa) {
    // Kehadiran dari absensi
    $st = $pdo->prepare('SELECT m.tanggal, a.status FROM ekskul_pertemuan m
                         LEFT JOIN ekskul_absensi a ON a.pertemuan_id = m.id AND a.siswa_id = ?
                         WHERE m.ekskul_id = ? AND m.tanggal <= ?');
    $st->execute([$siswa['id'], $cur['id'], $today]);
    $hadir = 0;
    foreach ($st->fetchAll() as $r) {
        if ($r['status'] === null && $r['tanggal'] >= $batas) continue; // masih boleh diisi
        $jmlPertemuan++;
        if ($r['status'] === 'hadir') $hadir++;
    }
    $hadirPct = $jmlPertemuan > 0 ? (int) round($hadir / $jmlPertemuan * 100) : null;

    // Penilaian aspek (semester terbaru)
    $st = $pdo->prepare('SELECT semester FROM ekskul_nilai WHERE siswa_id = ? ORDER BY id DESC LIMIT 1');
    $st->execute([$siswa['id']]);
    $semester = (string) ($st->fetchColumn() ?: '');
    if ($semester !== '') {
        $st = $pdo->prepare('SELECT aspek, nilai, catatan FROM ekskul_nilai WHERE siswa_id = ? AND semester = ? ORDER BY urutan, id');
        $st->execute([$siswa['id'], $semester]);
        $aspekRows = $st->fetchAll();
    }

    // Nilai tugas
    $st = $pdo->prepare('SELECT t.judul, g.nilai FROM ekskul_tugas t
                         LEFT JOIN pengumpulan g ON g.tugas_id = t.id AND g.siswa_id = ?
                         WHERE t.ekskul_id = ? ORDER BY t.deadline, t.id');
    $st->execute([$siswa['id'], $cur['id']]);
    $tugasRows = $st->fetchAll();

    // Rata-rata: dari aspek bila ada; jika tidak, dari nilai tugas
    $angka = array_map(static fn ($r) => (int) $r['nilai'], $aspekRows);
    if (!$angka) {
        foreach ($tugasRows as $r) { $p = pn_poin((string) $r['nilai']); if ($p !== null) $angka[] = $p; }
    }
    if ($angka) { $rataNilai = array_sum($angka) / count($angka); $rata = pn_huruf($rataNilai); }
}
$ubah = $siswa && isset($_GET['ubah']);

pembimbing_header('Indeks Penilaian', 'penilaian', $me);

if ($flash): ?>
    <div class="pb-flash <?= $flash[0] === 'ok' ? 'ok' : 'err' ?>" role="status"><?= e($flash[1]) ?></div>
<?php endif; ?>

<?php if (!$cur): /* ================= 1. DAFTAR EKSKUL ================= */ ?>
<section class="pb-panel">
    <div class="pb-panel-head"><h2>Indeks Penilaian</h2><p>Rekapan Penilaian Pencapaian Siswa</p></div>
    <div class="pb-panel-body">
        <div class="pb-pn-grid">
            <?php foreach ($ekskulku as $r):
                $pct = (int) $r['kuota'] > 0 ? min(100, (int) round((int) $r['jml'] / (int) $r['kuota'] * 100)) : 0; ?>
                <article class="pb-pn-card">
                    <h3><?= e(explode(' ', trim((string) $r['nama']))[0]) ?></h3>
                    <p class="pb-pn-desc"><?= e((string) $r['deskripsi']) ?></p>
                    <div class="pb-pn-by"><span>Pembimbing:</span> <b><?= e($r['pembimbing'] !== null && $r['pembimbing'] !== '' ? $r['pembimbing'] : $me['nama']) ?></b></div>
                    <div class="pb-pn-quota"><span>Jumlah Siswa</span><b><?= (int) $r['jml'] ?>/<?= (int) $r['kuota'] ?></b></div>
                    <div class="pb-pn-bar"><i style="width:<?= $pct ?>%"></i></div>
                    <a class="pb-pn-pill" href="<?= e(pn_url(['e' => $r['id']])) ?>">Lihat</a>
                </article>
            <?php endforeach; ?>
            <?php if (!$ekskulku): ?><p class="pb-empty">Anda belum mengampu ekskul apa pun.</p><?php endif; ?>
        </div>
    </div>
</section>

<?php elseif (!$siswa): /* ================= 2. DAFTAR SISWA ================= */ ?>
<a class="pb-back" href="<?= e(pn_url()) ?>">← Kembali ke daftar ekskul</a>
<section class="pb-panel">
    <div class="pb-panel-head"><h2>Indeks Penilaian</h2><p>Rekapan Penilaian Pencapaian Siswa</p></div>
    <div class="pb-panel-body">
        <div class="pb-pn-sub"><b><?= e(explode(' ', trim((string) $cur['nama']))[0]) ?></b><span>Pembimbing: <b><?= e($cur['pembimbing'] !== null && $cur['pembimbing'] !== '' ? $cur['pembimbing'] : $me['nama']) ?></b></span></div>
        <div class="pb-pn-table">
            <div class="pb-pn-tr pb-pn-th"><span>NAMA SISWA</span><span>KELAS</span><span></span></div>
            <?php foreach ($anggota as $a): ?>
                <div class="pb-pn-tr">
                    <span class="pb-nm"><?= e($a['nama']) ?></span>
                    <span class="pb-mut"><?= e(pn_kelas($a['kelas'])) ?></span>
                    <span><a class="pb-pn-raport" href="<?= e(pn_url(['e' => $cur['id'], 's' => $a['id']])) ?>">Lihat Raport</a></span>
                </div>
            <?php endforeach; ?>
            <?php if (!$anggota): ?><p class="pb-empty">Belum ada anggota aktif di ekskul ini.</p><?php endif; ?>
        </div>
    </div>
</section>

<?php else: /* ================= 3. RAPORT SISWA ================= */ ?>
<a class="pb-back" href="<?= e(pn_url(['e' => $cur['id']])) ?>">← Kembali ke daftar siswa</a>
<section class="pb-panel">
    <div class="pb-pn-rhead">
        <div><h2><?= e($siswa['nama']) ?></h2><p><?= e(pn_kelas($siswa['kelas'])) ?> · <?= e(explode(' ', trim((string) $cur['nama']))[0]) ?></p></div>
        <a class="pb-pn-edit" href="<?= e(pn_url(['e' => $cur['id'], 's' => $siswa['id'], 'ubah' => 1])) ?>">✎ <?= $aspekRows ? 'Ubah Penilaian' : 'Isi Penilaian' ?></a>
    </div>
    <div class="pb-panel-body">
        <div class="pb-pn-raport-grid">
            <div class="pb-pn-left">
                <div class="pb-pn-stats">
                    <div class="pb-pn-stat"><small>Rata-Rata Nilai</small><strong><?= $rata ?? '-' ?></strong><?php if ($rataNilai !== null): ?><em><?= (int) round($rataNilai) ?></em><?php endif; ?></div>
                    <div class="pb-pn-stat"><small>Kehadiran</small><strong><?= $hadirPct !== null ? $hadirPct . '%' : '-' ?></strong><em><?= $jmlPertemuan ?> pertemuan</em></div>
                </div>

                <div class="pb-pn-bars">
                    <div class="pb-pn-b"><div><span>Kehadiran</span><b><?= $hadirPct !== null ? $hadirPct . '%' : '-' ?></b></div><i><u style="width:<?= (int) $hadirPct ?>%"></u></i></div>
                    <?php foreach ($aspekRows as $r): if (strcasecmp((string) $r['aspek'], 'Kehadiran') === 0) continue; ?>
                        <div class="pb-pn-b"><div><span><?= e($r['aspek']) ?></span><b><?= (int) $r['nilai'] ?>%</b></div><i><u style="width:<?= (int) $r['nilai'] ?>%"></u></i></div>
                    <?php endforeach; ?>
                    <?php if (!$aspekRows): ?><p class="pb-pn-none">Belum ada penilaian aspek. Klik “Isi Penilaian”.</p><?php endif; ?>
                    <?php if ($semester !== ''): ?><small class="pb-pn-sem"><?= e($semester) ?></small><?php endif; ?>
                </div>
            </div>

            <div class="pb-pn-detail">
                <h3>Detail penilaian</h3>
                <?php foreach ($tugasRows as $r): ?>
                    <div class="pb-pn-d"><span><?= e($r['judul']) ?></span><b><?= $r['nilai'] !== null && $r['nilai'] !== '' ? e($r['nilai']) : '-' ?></b></div>
                <?php endforeach; ?>
                <?php if (!$tugasRows): ?><p class="pb-pn-none">Belum ada tugas di ekskul ini.</p><?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php if ($ubah):
    $tutupUrl = e(pn_url(['e' => $cur['id'], 's' => $siswa['id']]));
    $isi = [];
    foreach ($aspekRows as $r) { $isi[mb_strtolower((string) $r['aspek'])] = $r; }
?>
    <div class="pb-modal-back">
        <div class="pb-modal pb-modal--detail pb-modal--nilai" role="dialog" aria-modal="true" aria-label="Penilaian">
            <form method="post" action="<?= e(pn_url()) ?>">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="e" value="<?= (int) $cur['id'] ?>">
                <input type="hidden" name="s" value="<?= (int) $siswa['id'] ?>">
                <header><h3>Penilaian — <?= e($siswa['nama']) ?></h3><a href="<?= $tutupUrl ?>" aria-label="Tutup">✕</a></header>
                <div class="pb-modal-body">
                    <label class="pb-fl"><span>Semester</span><input type="text" name="semester" value="<?= e($semester !== '' ? $semester : 'Ganjil ' . date('Y')) ?>" maxlength="30" required></label>
                    <div class="pb-pn-fgrid">
                        <?php foreach (PENILAIAN_ASPEK as $i => $aspek): $d = $isi[mb_strtolower($aspek)] ?? null; ?>
                            <label class="pb-fl"><span><?= e($aspek) ?> (0–100)</span>
                                <input type="number" name="nilai[<?= $i ?>]" min="0" max="100" step="1" value="<?= $d ? (int) $d['nilai'] : '' ?>" required>
                            </label>
                            <label class="pb-fl"><span>Catatan <?= e($aspek) ?></span>
                                <input type="text" name="catatan[<?= $i ?>]" maxlength="255" value="<?= e($d ? (string) $d['catatan'] : '') ?>">
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <p class="pb-pn-none">Menyimpan akan mengganti penilaian semester ini dan langsung tampil di halaman Penilaian siswa.</p>
                </div>
                <footer>
                    <a class="pb-close" href="<?= $tutupUrl ?>">Batal</a>
                    <button type="submit" class="pb-save">Simpan</button>
                </footer>
            </form>
        </div>
    </div>
<?php endif; ?>
<?php endif; ?>
<?php pembimbing_footer();
