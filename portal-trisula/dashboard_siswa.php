<?php
declare(strict_types=1);

require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/siswa_layout.php';

$session = require_role('siswa');
$pdo     = db();

// Data terbaru siswa (termasuk kelas)
$st = $pdo->prepare('SELECT nama, nis, angkatan, kelas FROM users WHERE id = ?');
$st->execute([$session['id']]);
$user = $st->fetch() ?: $session;

// Ringkasan
$st = $pdo->prepare("SELECT COUNT(*) FROM info_pengumuman a
                     JOIN pendaftaran p ON p.ekskul_id = a.ekskul_id AND p.siswa_id = ? AND p.status = 'disetujui'
                     WHERE a.created_at >= NOW() - INTERVAL 2 DAY");
$st->execute([$session['id']]);
$jmlPengumuman = (int) $st->fetchColumn();

$st = $pdo->prepare("SELECT COUNT(*) FROM tugas WHERE siswa_id = ? AND status = 'aktif'");
$st->execute([$session['id']]);
$jmlTugas = (int) $st->fetchColumn();

$jmlAgenda = (int) $pdo->query('SELECT COUNT(*) FROM jadwal')->fetchColumn();

$st = $pdo->prepare('SELECT aspek, nilai FROM ekskul_nilai
                     WHERE siswa_id = ? AND semester = (SELECT semester FROM ekskul_nilai WHERE siswa_id = ? ORDER BY id DESC LIMIT 1)
                     ORDER BY urutan, id');
$st->execute([$session['id'], $session['id']]);
$capaian = $st->fetchAll();
$rata    = $capaian ? (int) round(array_sum(array_column($capaian, 'nilai')) / count($capaian)) : 0;

$st = $pdo->prepare("SELECT COALESCE(NULLIF(a.penulis, ''), e.pembimbing, 'Pembimbing') AS penulis, a.judul, a.isi, a.created_at
                     FROM info_pengumuman a
                     JOIN ekskul e ON e.id = a.ekskul_id
                     JOIN pendaftaran p ON p.ekskul_id = a.ekskul_id AND p.siswa_id = ? AND p.status = 'disetujui'
                     ORDER BY a.created_at DESC, a.id DESC LIMIT 3");
$st->execute([$session['id']]);
$pengumuman = $st->fetchAll();

$kartu = [
    ['angka' => $jmlPengumuman, 'judul' => 'Pengumuman',       'ket' => 'Baru hari ini',      'warna' => '#8b5cf6'],
    ['angka' => $jmlTugas,      'judul' => 'Tugas Aktif',      'ket' => 'Harus diselesaikan', 'warna' => '#e7000b'],
    ['angka' => $jmlAgenda,     'judul' => 'Agenda Minggu Ini','ket' => 'Terjadwal',          'warna' => '#004029'],
    ['angka' => $rata . '%',    'judul' => 'Capaian Rata-rata','ket' => 'Semester Ganjil',    'warna' => '#00a63e'],
];

siswa_header('Dashboard Utama', 'dashboard', $user);
?>
<section class="banner">
    <div>
        <p>Selamat Datang Kembali 👋</p>
        <h2><?= e($user['nama']) ?></h2>
        <span class="pill">NIS <?= e($user['nis']) ?></span>
        <span class="pill">Tahun Angkatan <?= e((string) $user['angkatan']) ?></span>
    </div>
    <svg class="banner-ico" viewBox="0 0 100 100" aria-hidden="true"><circle cx="38" cy="30" r="17"/><circle cx="62" cy="30" r="17"/><path d="M5 92c0-24 14-38 33-38 5 0 9 1 12 3 3-2 7-3 12-3 19 0 33 14 33 38z"/></svg>
</section>

<section class="stats">
    <?php foreach ($kartu as $k): ?>
        <article class="stat">
            <strong style="color:<?= e($k['warna']) ?>"><?= e((string) $k['angka']) ?></strong>
            <b><?= e($k['judul']) ?></b>
            <small><?= e($k['ket']) ?></small>
        </article>
    <?php endforeach; ?>
</section>

<section class="grid2">
    <article class="panel">
        <header class="panel-head">
            <h3><i class="dot" style="background:#004029"></i> Pengumuman Terbaru</h3>
            <a href="pengumuman.php">Lihat semua →</a>
        </header>
        <?php foreach ($pengumuman as $p): ?>
            <div class="news">
                <span class="avatar" style="background:<?= e(avatar_color($p['penulis'])) ?>"><?= e(initials($p['penulis'])) ?></span>
                <div>
                    <small><?= e($p['penulis']) ?></small>
                    <h4><?= e($p['judul']) ?></h4>
                    <p><?= e($p['isi']) ?></p>
                    <small class="muted">Dibuat <?= e(time_ago($p['created_at'])) ?></small>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if (!$pengumuman): ?><p class="empty">Belum ada pengumuman.</p><?php endif; ?>
    </article>

    <article class="panel panel--short">
        <header class="panel-head">
            <h3><i class="dot" style="background:#00a63e"></i> Capaian Semester</h3>
            <a href="penilaian.php">Detail →</a>
        </header>
        <div class="bars">
            <?php foreach ($capaian as $c): ?>
                <div class="bar">
                    <div><span><?= e($c['aspek']) ?></span><b><?= (int) $c['nilai'] ?>%</b></div>
                    <div class="track"><i style="width:<?= (int) $c['nilai'] ?>%"></i></div>
                </div>
            <?php endforeach; ?>
            <?php if (!$capaian): ?><p class="empty">Belum ada penilaian.</p><?php endif; ?>
        </div>
    </article>
</section>
<?php siswa_footer();
