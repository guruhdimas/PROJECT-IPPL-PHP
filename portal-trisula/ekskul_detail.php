<?php
declare(strict_types=1);

require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/siswa_layout.php';
require __DIR__ . '/includes/ekskul.php';

$session = require_role('siswa');
$pdo     = db();
$user    = load_user($pdo, (int) $session['id']);

$id     = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$ekskul = find_ekskul($pdo, $id);
$daftar = $ekskul ? find_pendaftaran($pdo, (int) $user['id'], $id) : null;

// Detail hanya untuk ekskul yang sedang menunggu / sudah disetujui
if (!$ekskul || !$daftar || $daftar['status'] === 'ditolak') { header('Location: daftar_ekskul.php'); exit; }

// Batalkan pendaftaran (hanya saat masih menunggu)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'batal' && csrf_valid($_POST['csrf'] ?? null)) {
    $pdo->prepare("DELETE FROM pendaftaran WHERE siswa_id = ? AND ekskul_id = ? AND status = 'menunggu'")
        ->execute([$user['id'], $id]);
    header('Location: daftar_ekskul.php');
    exit;
}

$today = date('Y-m-d');

$st = $pdo->prepare('SELECT * FROM pertemuan WHERE ekskul_id = ? ORDER BY tanggal');
$st->execute([$id]);
$pertemuan = $st->fetchAll();
$mendatang = array_values(array_filter($pertemuan, fn ($p) => $p['tanggal'] >= $today));

$st = $pdo->prepare('SELECT * FROM ekskul_tugas WHERE ekskul_id = ? ORDER BY deadline DESC');
$st->execute([$id]);
$tugas      = $st->fetchAll();
$tugasAktif = count(array_filter($tugas, fn ($t) => $t['deadline'] >= $today));

$st = $pdo->prepare("SELECT u.nama, u.kelas FROM pendaftaran p JOIN users u ON u.id = p.siswa_id
                     WHERE p.ekskul_id = ? AND p.status = 'disetujui' ORDER BY p.id LIMIT 12");
$st->execute([$id]);
$anggota = $st->fetchAll();

$st = $pdo->prepare('SELECT * FROM ekskul_pengumuman WHERE ekskul_id = ? ORDER BY tanggal DESC LIMIT 5');
$st->execute([$id]);
$pengumuman = $st->fetchAll();

siswa_header('Daftar Ekskul', 'ekskul', $user);
?>
<a href="daftar_ekskul.php" class="back">‹ Kembali</a>

<section class="hero-e">
    <div class="hero-main">
        <span class="hero-letter"><?= e(mb_strtoupper(mb_substr($ekskul['nama'], 0, 1))) ?></span>
        <div>
            <h2><?= e($ekskul['nama']) ?> <span class="badge"><i></i>Dibuka</span></h2>
            <p><?= e($ekskul['deskripsi']) ?></p>
            <div class="hero-meta">
                <span><?= icon('absensi') ?> <?= e($ekskul['pembimbing']) ?></span>
                <span><?= icon('jadwal') ?> <?= e($ekskul['hari'] . ', ' . jam_fmt($ekskul['jam_mulai'])) ?></span>
                <span><?= icon('ekskul') ?> <?= (int) $ekskul['terisi'] ?> anggota</span>
            </div>
        </div>
    </div>
    <div class="hero-status"><?= status_pill($daftar['status']) ?></div>
    <?php if ($daftar['status'] === 'menunggu'): ?>
        <form method="post" class="hero-cancel" onsubmit="return confirm('Batalkan pendaftaran ke <?= e($ekskul['nama']) ?>?')">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="id" value="<?= $id ?>">
            <input type="hidden" name="aksi" value="batal">
            <button type="submit" class="btn-white">Batalkan Pendaftaran</button>
        </form>
    <?php endif; ?>
</section>

<div class="detail-grid">
    <div class="col">
        <div class="mini-stats">
            <article class="stat"><small>JADWAL MENDATANG</small><strong style="color:#101828"><?= count($mendatang) ?></strong>
                <small><?= $mendatang ? 'Berikutnya ' . e(tanggal_id($mendatang[0]['tanggal'])) : 'Belum ada jadwal' ?></small></article>
            <article class="stat"><small>TUGAS AKTIF</small><strong style="color:#101828"><?= $tugasAktif ?></strong>
                <small><?= count($tugas) ?> total tugas</small></article>
        </div>

        <article class="panel">
            <header class="panel-head"><h3><?= icon('jadwal') ?> Jadwal Kegiatan</h3><a href="#">Lihat kalender</a></header>
            <?php foreach ($pertemuan as $p): $lewat = $p['tanggal'] < $today; ?>
                <div class="meet <?= $lewat ? 'past' : '' ?>">
                    <div class="datebox"><b><?= e(date('j', strtotime($p['tanggal']))) ?></b><small><?= e(bulan_pendek($p['tanggal'])) ?></small></div>
                    <div><h4><?= e($p['judul']) ?></h4><small>◷ <?= e(jam_fmt($p['jam'])) ?> &nbsp; ⌖ <?= e($p['lokasi']) ?></small></div>
                    <span class="tag <?= $lewat ? 'tag-done' : 'tag-next' ?>"><i></i><?= $lewat ? 'Selesai' : 'Mendatang' ?></span>
                </div>
            <?php endforeach; ?>
            <?php if (!$pertemuan): ?><p class="empty">Belum ada jadwal kegiatan.</p><?php endif; ?>
        </article>

        <article class="panel">
            <header class="panel-head"><h3><?= icon('tugas') ?> Tugas</h3></header>
            <div class="task-list">
                <?php foreach ($tugas as $t): $lewat = $t['deadline'] < $today; ?>
                    <div class="task">
                        <div><h4><?= e($t['judul']) ?></h4><p><?= e($t['keterangan']) ?></p></div>
                        <small class="<?= $lewat ? '' : 'txt-wait' ?>">Deadline <?= e(tanggal_id($t['deadline'])) ?></small>
                    </div>
                <?php endforeach; ?>
                <?php if (!$tugas): ?><p class="empty">Belum ada tugas.</p><?php endif; ?>
            </div>
        </article>
    </div>

    <div class="col">
        <article class="panel">
            <header class="panel-head"><h3><?= icon('absensi') ?> Anggota (<?= count($anggota) ?>)</h3></header>
            <div class="members">
                <?php foreach ($anggota as $a): ?>
                    <div class="member">
                        <span class="avatar sm" style="background:#e8f3ee;color:#004029"><?= e(initials($a['nama'])) ?></span>
                        <div><b><?= e($a['nama']) ?></b><small><?= e((string) $a['kelas']) ?></small></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </article>

        <article class="panel">
            <header class="panel-head"><h3><?= icon('pengumuman') ?> Pengumuman</h3></header>
            <div class="ann-list">
                <?php foreach ($pengumuman as $a): ?>
                    <div class="ann"><h4><?= e($a['judul']) ?></h4><p><?= e($a['isi']) ?></p><small><?= e(tanggal_id($a['tanggal'])) ?></small></div>
                <?php endforeach; ?>
                <?php if (!$pengumuman): ?><p class="empty">Belum ada pengumuman.</p><?php endif; ?>
            </div>
        </article>
    </div>
</div>
<?php siswa_footer();
