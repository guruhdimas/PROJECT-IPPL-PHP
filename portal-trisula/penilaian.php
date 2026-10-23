<?php
declare(strict_types=1);

require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/siswa_layout.php';
require __DIR__ . '/includes/ekskul.php';

$session = require_role('siswa');
$pdo     = db();
$user    = load_user($pdo, (int) $session['id']);
$uid     = (int) $user['id'];

// Semester terbaru yang sudah dinilai
$st = $pdo->prepare('SELECT semester FROM ekskul_nilai WHERE siswa_id = ? ORDER BY id DESC LIMIT 1');
$st->execute([$uid]);
$sem = $st->fetchColumn();

$nilai = [];
if ($sem !== false) {
    $st = $pdo->prepare('SELECT aspek, nilai, catatan, semester
                         FROM ekskul_nilai
                         WHERE siswa_id = ? AND semester = ?
                         ORDER BY urutan, id');
    $st->execute([$uid, $sem]);
    $nilai = $st->fetchAll();
}

$jumlah = count($nilai);
$rata   = $jumlah ? (int) round(array_sum(array_column($nilai, 'nilai')) / $jumlah) : 0;
$baik   = count(array_filter($nilai, static fn($r) => (int) $r['nilai'] >= 80));
$kurang = $jumlah - $baik;

// A >= 90, B >= 80, C >= 70, D < 70
$grade = static function (int $n): array {
    if ($n >= 90) return ['A', 'pn-g-a'];
    if ($n >= 80) return ['B', 'pn-g-b'];
    if ($n >= 70) return ['C', 'pn-g-c'];
    return ['D', 'pn-g-d'];
};

siswa_header('Penilaian', 'penilaian', $user);
?>
<div class="page-title"><h2>Penilaian</h2><p>Pantau dan catat capaian kompetensi per semester</p></div>

<?php if (!$nilai): ?>
    <section class="panel"><p class="empty">Belum ada penilaian. Nilai akan muncul setelah pembimbing mengisi penilaian Anda.</p></section>
<?php else: ?>

<section class="pn-top">
    <article class="pn-hero">
        <strong><?= $rata ?></strong>
        <span>Rata-rata Nilai</span>
        <div class="pn-hero-bar"><i style="width:<?= $rata ?>%"></i></div>
    </article>
    <article class="pn-box">
        <strong class="ok"><?= $baik ?></strong>
        <span>Aspek Nilai Baik (≥80)</span>
        <small>dari <?= $jumlah ?> total aspek</small>
    </article>
    <article class="pn-box">
        <strong class="warn"><?= $kurang ?></strong>
        <span>Aspek Perlu Ditingkatkan</span>
        <small>nilai di bawah 80</small>
    </article>
</section>

<section class="pn-grid">
    <article class="panel pn-panel">
        <h3 class="pn-title">Grafik Capaian</h3>
        <div class="pn-bars">
            <?php foreach ($nilai as $r): $n = (int) $r['nilai']; ?>
                <div class="pn-bar">
                    <div class="pn-bar-top"><span><?= e($r['aspek']) ?></span><b><?= $n ?> <small>/ 100</small></b></div>
                    <div class="pn-track"><i style="width:<?= max(0, min(100, $n)) ?>%"></i></div>
                </div>
            <?php endforeach; ?>
        </div>
    </article>

    <article class="panel pn-panel pn-panel--list">
        <h3 class="pn-title">Detail Penilaian</h3>
        <?php foreach ($nilai as $r): [$huruf, $cls] = $grade((int) $r['nilai']); ?>
            <div class="pn-item">
                <div class="pn-item-head"><b><?= e($r['aspek']) ?></b><i class="pn-grade <?= $cls ?>"><?= $huruf ?></i></div>
                <?php if ($r['catatan'] !== ''): ?><p><?= e($r['catatan']) ?></p><?php endif; ?>
                <small><?= e($r['semester']) ?></small>
            </div>
        <?php endforeach; ?>
    </article>
</section>

<?php endif; ?>
<?php siswa_footer();
