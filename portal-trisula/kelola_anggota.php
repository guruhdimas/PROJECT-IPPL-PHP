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

function kelas_label(?string $k): string
{
    return $k ? (string) preg_replace('/^Kelas\s+/i', '', $k) : '-';
}

function kurl(array $p): string
{
    return 'kelola_anggota.php?' . http_build_query(array_filter($p, fn ($v) => $v !== '' && $v !== null && $v !== 0));
}

// ===================== AKSI (POST) =====================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $eid  = (int) ($_POST['ekskul_id'] ?? 0);
    $aksi = (string) ($_POST['aksi'] ?? '');
    $tab  = ($_POST['tab'] ?? '') === 'aktif' ? 'aktif' : 'verifikasi';

    try {
        if (!csrf_valid($_POST['csrf'] ?? null)) throw new RuntimeException('Sesi tidak valid, muat ulang halaman.');

        $pdo->beginTransaction();
        // Kunci baris ekskul (milik pembimbing ini) supaya hitungan kuota tidak balapan
        $st = $pdo->prepare('SELECT id, kuota FROM ekskul WHERE id = ? AND pembimbing_id = ? FOR UPDATE');
        $st->execute([$eid, $pid]);
        $ek = $st->fetch();
        if (!$ek) throw new RuntimeException('Ekskul tidak ditemukan.');

        $st = $pdo->prepare("SELECT COUNT(*) FROM pendaftaran WHERE ekskul_id = ? AND status = 'disetujui'");
        $st->execute([$eid]);
        $sisa = max(0, (int) $ek['kuota'] - (int) $st->fetchColumn());
        $pdid = (int) ($_POST['pendaftaran_id'] ?? 0);

        switch ($aksi) {
            case 'terima':
                if ($sisa <= 0) throw new RuntimeException('Kuota ekskul sudah penuh.');
                $u = $pdo->prepare("UPDATE pendaftaran SET status = 'disetujui', diproses_at = NOW() WHERE id = ? AND ekskul_id = ? AND status = 'menunggu'");
                $u->execute([$pdid, $eid]);
                if (!$u->rowCount()) throw new RuntimeException('Pendaftaran tidak ditemukan atau sudah diproses.');
                $pesan = 'Pendaftaran diterima.';
                break;

            case 'tolak':
                $u = $pdo->prepare("UPDATE pendaftaran SET status = 'ditolak', diproses_at = NOW() WHERE id = ? AND ekskul_id = ? AND status = 'menunggu'");
                $u->execute([$pdid, $eid]);
                if (!$u->rowCount()) throw new RuntimeException('Pendaftaran tidak ditemukan atau sudah diproses.');
                $pesan = 'Pendaftaran ditolak.';
                break;

            case 'terima_semua':
                if ($sisa <= 0) throw new RuntimeException('Kuota ekskul sudah penuh.');
                $st = $pdo->prepare("SELECT id FROM pendaftaran WHERE ekskul_id = ? AND status = 'menunggu' ORDER BY created_at, id");
                $st->execute([$eid]);
                $semua = $st->fetchAll(PDO::FETCH_COLUMN);
                $terima = array_slice($semua, 0, $sisa);
                $u = $pdo->prepare("UPDATE pendaftaran SET status = 'disetujui', diproses_at = NOW() WHERE id = ?");
                foreach ($terima as $id) { $u->execute([$id]); }
                $pesan = count($terima) . ' pendaftaran diterima.';
                if (count($semua) > count($terima)) $pesan .= ' Sisanya belum bisa diterima karena kuota penuh.';
                break;

            case 'keluarkan':
                $d = $pdo->prepare("DELETE FROM pendaftaran WHERE id = ? AND ekskul_id = ? AND status = 'disetujui'");
                $d->execute([$pdid, $eid]);
                if (!$d->rowCount()) throw new RuntimeException('Anggota tidak ditemukan.');
                $pesan = 'Anggota dikeluarkan dari ekskul.';
                break;

            default:
                throw new RuntimeException('Aksi tidak dikenal.');
        }
        $pdo->commit();
        $_SESSION['flash'] = ['ok', $pesan];
    } catch (RuntimeException $ex) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $_SESSION['flash'] = ['err', $ex->getMessage()];
    } catch (PDOException $ex) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $_SESSION['flash'] = ['err', 'Gagal memproses, coba lagi.'];
    }
    header('Location: ' . kurl(['ekskul' => $eid, 'tab' => $tab]));
    exit;
}

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

// ===================== TAMPILAN =====================
$eid = (int) ($_GET['ekskul'] ?? 0);
$ekskul = null;
if ($eid) {
    $st = $pdo->prepare("SELECT e.*, (SELECT COUNT(*) FROM pendaftaran p WHERE p.ekskul_id = e.id AND p.status = 'disetujui') AS terisi
                         FROM ekskul e WHERE e.id = ? AND e.pembimbing_id = ?");
    $st->execute([$eid, $pid]);
    $ekskul = $st->fetch() ?: null;
}

pembimbing_header('Kelola Anggota', 'anggota', $me);

if ($flash): ?>
    <div class="pb-flash <?= $flash[0] === 'ok' ? 'ok' : 'err' ?>" role="status"><?= e($flash[1]) ?></div>
<?php endif;

// ---------- Langkah 1: pilih ekskul ----------
if (!$ekskul):
    $q = trim($_GET['q'] ?? '');
    $f = (int) ($_GET['f'] ?? 0);
    $semua = $pdo->prepare('SELECT id, nama, deskripsi FROM ekskul WHERE pembimbing_id = ? ORDER BY nama');
    $semua->execute([$pid]);
    $semua = $semua->fetchAll();
    $daftar = array_values(array_filter($semua, fn ($e) =>
        ($q === '' || stripos($e['nama'], $q) !== false) && (!$f || (int) $e['id'] === $f)));
?>
<section class="pb-panel">
    <div class="pb-panel-head"><h2>Kelola Ekstrakurikuler</h2><p>Pilih Ekstrakurikuler Yang Ingin Di ampu</p></div>
    <div class="pb-panel-body">
        <form method="get" class="pb-filter">
            <input type="search" name="q" value="<?= e($q) ?>" placeholder="Cari Ekstrakurikuler ...">
            <select name="f" onchange="this.form.submit()" aria-label="Filter ekskul">
                <option value="0">Semua Ekskul</option>
                <?php foreach ($semua as $e): ?><option value="<?= (int) $e['id'] ?>" <?= $f === (int) $e['id'] ? 'selected' : '' ?>><?= e($e['nama']) ?></option><?php endforeach; ?>
            </select>
        </form>

        <div class="pb-ekskul-grid">
            <?php foreach ($daftar as $e): ?>
                <article class="pb-ek">
                    <h3><?= e($e['nama']) ?></h3>
                    <p><?= e($e['deskripsi']) ?></p>
                    <a class="pb-pill" href="<?= e(kurl(['ekskul' => $e['id']])) ?>">Ampu Ekskul</a>
                </article>
            <?php endforeach; ?>
            <?php if (!$daftar): ?><p class="pb-empty">Tidak ada ekskul yang diampu.</p><?php endif; ?>
        </div>
    </div>
</section>
<?php
// ---------- Langkah 2: kelola satu ekskul ----------
else:
    $tab    = ($_GET['tab'] ?? '') === 'aktif' ? 'aktif' : 'verifikasi';
    $q      = trim($_GET['q'] ?? '');
    $kelas  = trim($_GET['kelas'] ?? '');
    $status = ($_GET['status'] ?? '') === 'ditolak' ? 'ditolak' : 'menunggu';
    $kuota  = (int) $ekskul['kuota'];
    $terisi = (int) $ekskul['terisi'];
    $persen = $kuota > 0 ? min(100, (int) round($terisi / $kuota * 100)) : 0;

    $st = $pdo->prepare("SELECT COUNT(*) FROM pendaftaran WHERE ekskul_id = ? AND status = 'menunggu'");
    $st->execute([$eid]);
    $jmlMenunggu = (int) $st->fetchColumn();

    $stBaris = $tab === 'aktif' ? 'disetujui' : $status;
    $sql    = 'SELECT p.id, p.created_at, p.status, u.nama, u.kelas FROM pendaftaran p JOIN users u ON u.id = p.siswa_id WHERE p.ekskul_id = ? AND p.status = ?';
    $par    = [$eid, $stBaris];
    if ($q !== '')     { $sql .= ' AND u.nama LIKE ?'; $par[] = '%' . addcslashes($q, '%_') . '%'; }
    if ($kelas !== '') { $sql .= ' AND u.kelas = ?';   $par[] = $kelas; }
    $st = $pdo->prepare($sql . ' ORDER BY p.created_at, p.id');
    $st->execute($par);
    $baris = $st->fetchAll();

    $st = $pdo->prepare('SELECT DISTINCT u.kelas FROM pendaftaran p JOIN users u ON u.id = p.siswa_id WHERE p.ekskul_id = ? AND p.status = ? AND u.kelas IS NOT NULL ORDER BY u.kelas');
    $st->execute([$eid, $stBaris]);
    $opsiKelas = $st->fetchAll(PDO::FETCH_COLUMN);

    // Modal profil anggota
    $profil = null;
    $profilId = (int) ($_GET['profil'] ?? 0);
    if ($tab === 'aktif' && $profilId) {
        $st = $pdo->prepare("SELECT p.id, p.siswa_id, p.created_at, p.diproses_at, u.nama, u.kelas
                             FROM pendaftaran p JOIN users u ON u.id = p.siswa_id
                             WHERE p.id = ? AND p.ekskul_id = ? AND p.status = 'disetujui'");
        $st->execute([$profilId, $eid]);
        $profil = $st->fetch() ?: null;
        if ($profil) {
            $hadir = 0; $total = 0; $tugas = 0;
            try {
                $s2 = $pdo->prepare("SELECT COUNT(*) AS total, COALESCE(SUM(a.status = 'hadir'), 0) AS hadir
                                     FROM pertemuan m LEFT JOIN presensi a ON a.pertemuan_id = m.id AND a.siswa_id = ?
                                     WHERE m.ekskul_id = ? AND m.tanggal <= ?");
                $s2->execute([$profil['siswa_id'], $eid, date('Y-m-d')]);
                $r = $s2->fetch();
                $total = (int) $r['total']; $hadir = (int) $r['hadir'];
                $s3 = $pdo->prepare('SELECT COUNT(*) FROM pengumpulan g JOIN ekskul_tugas t ON t.id = g.tugas_id WHERE t.ekskul_id = ? AND g.siswa_id = ?');
                $s3->execute([$eid, $profil['siswa_id']]);
                $tugas = (int) $s3->fetchColumn();
            } catch (PDOException $ex) { /* tabel absensi/tugas belum ada: tampilkan 0 */ }
            $profil['persen'] = $total ? (int) round($hadir / $total * 100) : 0;
            $profil['tugas']  = $tugas;
        }
    }
    $base = ['ekskul' => $eid, 'tab' => $tab, 'q' => $q, 'kelas' => $kelas, 'status' => $tab === 'verifikasi' && $status !== 'menunggu' ? $status : ''];

    // Detail isi formulir pendaftaran (modal)
    $detail = null;
    $detailId = (int) ($_GET['detail'] ?? 0);
    if ($detailId) {
        $st = $pdo->prepare('SELECT p.id, p.status, p.nis, p.hp_siswa, p.nama_ortu, p.hp_ortu, p.alasan, p.pengalaman, p.created_at, u.nama, u.kelas
                             FROM pendaftaran p JOIN users u ON u.id = p.siswa_id WHERE p.id = ? AND p.ekskul_id = ?');
        $st->execute([$detailId, $eid]);
        $detail = $st->fetch() ?: null;
    }
?>
<a href="kelola_anggota.php" class="pb-back">‹ Pilih ekskul lain</a>

<div class="pb-tabs">
    <a href="<?= e(kurl(['ekskul' => $eid, 'tab' => 'verifikasi'])) ?>" class="<?= $tab === 'verifikasi' ? 'on' : '' ?>">Verifikasi<?= $jmlMenunggu ? ' (' . $jmlMenunggu . ')' : '' ?></a>
    <a href="<?= e(kurl(['ekskul' => $eid, 'tab' => 'aktif'])) ?>" class="<?= $tab === 'aktif' ? 'on' : '' ?>">Anggota Aktif</a>
</div>

<section class="pb-panel">
    <div class="pb-panel-head pb-split">
        <div>
            <h2><?= $tab === 'verifikasi' ? 'Verifikasi Pendaftaran' : 'Anggota Aktif Ekstrakurikuler' ?> <span class="pb-eknama">· <?= e($ekskul['nama']) ?></span></h2>
            <p><?= $tab === 'verifikasi' ? 'Terima Atau Tolak Pendaftar' : 'Kelola Anggota Ekskul' ?></p>
        </div>
        <div class="pb-quota"><span>Kuota</span><div class="pb-bar"><i style="width:<?= $persen ?>%"></i></div><b><?= $terisi ?>/<?= $kuota ?></b></div>
        <?php if ($tab === 'verifikasi'): ?>
            <form method="post">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="ekskul_id" value="<?= $eid ?>">
                <input type="hidden" name="tab" value="verifikasi">
                <input type="hidden" name="aksi" value="terima_semua">
                <button type="submit" class="pb-all" <?= ($jmlMenunggu && $terisi < $kuota) ? '' : 'disabled' ?>
                        onclick="return confirm('Terima semua pendaftar yang menunggu (sesuai sisa kuota)?')">✓ Terima Semua (<?= $jmlMenunggu ?>)</button>
            </form>
        <?php endif; ?>
    </div>

    <div class="pb-panel-body">
        <form method="get" class="pb-filter">
            <input type="hidden" name="ekskul" value="<?= $eid ?>">
            <input type="hidden" name="tab" value="<?= e($tab) ?>">
            <input type="search" name="q" value="<?= e($q) ?>" placeholder="Cari Nama Siswa ...">
            <select name="kelas" onchange="this.form.submit()" aria-label="Filter kelas">
                <option value="">Kelas</option>
                <?php foreach ($opsiKelas as $k): ?><option value="<?= e($k) ?>" <?= $kelas === $k ? 'selected' : '' ?>><?= e(kelas_label($k)) ?></option><?php endforeach; ?>
            </select>
            <?php if ($tab === 'verifikasi'): ?>
                <select name="status" onchange="this.form.submit()" aria-label="Filter status">
                    <option value="menunggu" <?= $status === 'menunggu' ? 'selected' : '' ?>>Menunggu</option>
                    <option value="ditolak"  <?= $status === 'ditolak' ? 'selected' : '' ?>>Ditolak</option>
                </select>
            <?php endif; ?>
            <a href="<?= e(kurl(['ekskul' => $eid, 'tab' => $tab])) ?>" class="pb-reset">Reset</a>
        </form>

        <div class="pb-table <?= $tab === 'aktif' ? 'pb-table--aktif' : '' ?>">
            <div class="pb-tr pb-th">
                <span>NAMA SISWA</span><span>KELAS</span><span>EKSTRAKURIKULER</span>
                <?php if ($tab === 'verifikasi'): ?><span>TANGGAL DAFTAR</span><?php endif; ?>
                <span>STATUS</span><span><?= $tab === 'verifikasi' ? 'AKSI' : '' ?></span>
            </div>
            <?php foreach ($baris as $r): ?>
                <div class="pb-tr">
                    <span class="pb-nm"><a class="pb-namelink" href="<?= e(kurl($base + ['detail' => $r['id']])) ?>"><?= e($r['nama']) ?></a></span>
                    <span class="pb-mut"><?= e(kelas_label($r['kelas'])) ?></span>
                    <span class="pb-mut"><?= e($ekskul['nama']) ?></span>
                    <?php if ($tab === 'verifikasi'): ?><span class="pb-red"><?= e(tanggal_id(substr($r['created_at'], 0, 10))) ?></span><?php endif; ?>
                    <span>
                        <?php if ($r['status'] === 'menunggu'): ?><i class="pb-st pb-st-wait">● Menunggu</i>
                        <?php elseif ($r['status'] === 'ditolak'): ?><i class="pb-st pb-st-no">● Ditolak</i>
                        <?php else: ?><i class="pb-st pb-st-on">● Aktif</i><?php endif; ?>
                    </span>
                    <span class="pb-act">
                        <?php if ($tab === 'aktif'): ?>
                            <a class="pb-mini" href="<?= e(kurl($base + ['profil' => $r['id']])) ?>">Kelola</a>
                        <?php else: ?>
                            <a class="pb-mini" href="<?= e(kurl($base + ['detail' => $r['id']])) ?>">Detail</a>
                        <?php endif; ?>
                        <?php if ($tab === 'verifikasi' && $r['status'] === 'menunggu'): ?>
                            <?php foreach ([['terima', 'Terima', 'pb-mini pb-mini--on', ''], ['tolak', 'Tolak', 'pb-mini', "Tolak pendaftaran {$r['nama']}?"]] as [$a, $lbl, $cls, $cf]): ?>
                                <form method="post">
                                    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="ekskul_id" value="<?= $eid ?>">
                                    <input type="hidden" name="tab" value="verifikasi">
                                    <input type="hidden" name="pendaftaran_id" value="<?= (int) $r['id'] ?>">
                                    <input type="hidden" name="aksi" value="<?= $a ?>">
                                    <button type="submit" class="<?= $cls ?>" <?= $cf ? 'onclick="return confirm(' . e(json_encode($cf)) . ')"' : '' ?>><?= $lbl ?></button>
                                </form>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </span>
                </div>
            <?php endforeach; ?>
            <?php if (!$baris): ?>
                <p class="pb-empty"><?= $tab === 'verifikasi' ? 'Tidak ada pendaftaran yang perlu diverifikasi.' : 'Belum ada anggota aktif.' ?></p>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php if ($detail):
    $tutupD = e(kurl(['ekskul' => $eid, 'tab' => $tab, 'q' => $q, 'kelas' => $kelas, 'status' => $base['status']]));
    $bisaTerima = $terisi < $kuota;
?>
    <div class="pb-modal-back">
        <div class="pb-modal pb-modal-wide" role="dialog" aria-modal="true" aria-label="Detail Pendaftaran">
            <header><h3>Detail Pendaftaran</h3><a href="<?= $tutupD ?>" aria-label="Tutup">✕</a></header>
            <div class="pb-modal-body">
                <div class="pb-mhead">
                    <span class="pb-mav"><?= e(initials($detail['nama'])) ?></span>
                    <div><b><?= e($detail['nama']) ?></b><small><?= e(kelas_label($detail['kelas'])) ?> · <?= e($ekskul['nama']) ?></small></div>
                </div>
                <div class="pb-dgrid">
                    <div><small>Tanggal Daftar</small><b><?= e(tanggal_id(substr($detail['created_at'], 0, 10))) ?></b></div>
                    <div><small>NIS</small><b><?= e($detail['nis']) ?></b></div>
                    <div><small>No. HP Siswa</small><b><?= e($detail['hp_siswa']) ?></b></div>
                    <div><small>Orang Tua/Wali</small><b><?= e($detail['nama_ortu']) ?></b></div>
                    <div><small>No. HP Orang Tua/Wali</small><b><?= e($detail['hp_ortu']) ?></b></div>
                    <div><small>Status</small><b><?= e(ucfirst($detail['status'])) ?></b></div>
                </div>
                <div class="pb-dtext"><small>Alasan bergabung</small><p><?= nl2br(e($detail['alasan'])) ?></p></div>
                <div class="pb-dtext"><small>Pengalaman terkait</small><p><?= $detail['pengalaman'] ? nl2br(e($detail['pengalaman'])) : '–' ?></p></div>
            </div>
            <footer>
                <?php if ($detail['status'] === 'menunggu'): ?>
                    <?php foreach ([['tolak', 'Tolak', 'pb-danger'], ['terima', 'Terima', 'pb-save']] as [$a, $lbl, $cls]): ?>
                        <form method="post" onsubmit="return confirm('<?= $lbl ?> pendaftaran <?= e($detail['nama']) ?>?')">
                            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="ekskul_id" value="<?= $eid ?>">
                            <input type="hidden" name="tab" value="verifikasi">
                            <input type="hidden" name="pendaftaran_id" value="<?= (int) $detail['id'] ?>">
                            <input type="hidden" name="aksi" value="<?= $a ?>">
                            <button type="submit" class="<?= $cls ?>" <?= ($a === 'terima' && !$bisaTerima) ? 'disabled title="Kuota penuh"' : '' ?>><?= $lbl ?></button>
                        </form>
                    <?php endforeach; ?>
                <?php endif; ?>
                <a class="pb-close" href="<?= $tutupD ?>">Tutup</a>
            </footer>
        </div>
    </div>
<?php endif; ?>

<?php if ($profil): $tutup = e(kurl(['ekskul' => $eid, 'tab' => 'aktif', 'q' => $q, 'kelas' => $kelas])); ?>
    <div class="pb-modal-back">
        <div class="pb-modal" role="dialog" aria-modal="true" aria-label="Profil Anggota">
            <header><h3>Profil Anggota</h3><a href="<?= $tutup ?>" aria-label="Tutup">✕</a></header>
            <div class="pb-modal-body">
                <div class="pb-mhead">
                    <span class="pb-mav"><?= e(initials($profil['nama'])) ?></span>
                    <div><b><?= e($profil['nama']) ?></b><small><?= e(kelas_label($profil['kelas'])) ?> · <?= e($ekskul['nama']) ?></small></div>
                </div>
                <div class="pb-mstats">
                    <div><small>Bergabung</small><b><?= e(tanggal_id(substr((string) ($profil['diproses_at'] ?: $profil['created_at']), 0, 10))) ?></b></div>
                    <div><small>Kehadiran</small><b><?= (int) $profil['persen'] ?>%</b></div>
                    <div><small>Tugas Dikumpul</small><b><?= (int) $profil['tugas'] ?></b></div>
                </div>
            </div>
            <footer>
                <form method="post" onsubmit="return confirm('Keluarkan <?= e($profil['nama']) ?> dari ekskul ini?')">
                    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="ekskul_id" value="<?= $eid ?>">
                    <input type="hidden" name="tab" value="aktif">
                    <input type="hidden" name="pendaftaran_id" value="<?= (int) $profil['id'] ?>">
                    <input type="hidden" name="aksi" value="keluarkan">
                    <button type="submit" class="pb-danger">Keluarkan</button>
                </form>
                <a class="pb-close" href="<?= $tutup ?>">Tutup</a>
            </footer>
        </div>
    </div>
<?php endif; ?>
<?php endif;
pembimbing_footer();
