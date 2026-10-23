<?php
declare(strict_types=1);

require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/siswa_layout.php';
require __DIR__ . '/includes/ekskul.php';

$session = require_role('siswa');
$pdo     = db();
$user    = load_user($pdo, (int) $session['id']);

$q      = trim($_GET['q'] ?? '');
$filter = in_array($_GET['status'] ?? '', ['dibuka', 'menunggu', 'disetujui'], true) ? $_GET['status'] : '';

$st = $pdo->prepare("SELECT e.*,
        (SELECT COUNT(*) FROM pendaftaran p WHERE p.ekskul_id = e.id AND p.status = 'disetujui') AS terisi,
        (SELECT p2.status FROM pendaftaran p2 WHERE p2.ekskul_id = e.id AND p2.siswa_id = :sid) AS status_saya
    FROM ekskul e WHERE e.nama LIKE :q ORDER BY e.id");
$st->execute([':sid' => $user['id'], ':q' => '%' . addcslashes($q, '%_') . '%']);
$daftar = array_values(array_filter($st->fetchAll(), function (array $e) use ($filter): bool {
    return match ($filter) {
        'dibuka'    => $e['status_saya'] === null && $e['terisi'] < $e['kuota'],
        'menunggu'  => $e['status_saya'] === 'menunggu',
        'disetujui' => $e['status_saya'] === 'disetujui',
        default     => true,
    };
}));

siswa_header('Daftar Ekskul', 'ekskul', $user);
?>
<div class="page-title">
    <h2>Daftar Ekstrakurikuler</h2>
    <p>Lihat pembimbing, kuota, dan status pendaftaran</p>
</div>

<section class="panel panel-pad">
    <form method="get" class="toolbar">
        <div class="search">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="#99a1af" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
            <input type="search" name="q" value="<?= e($q) ?>" placeholder="Cari Ekstrakurikuler...">
        </div>
        <select name="status" onchange="this.form.submit()" aria-label="Filter status">
            <option value="">Semua status</option>
            <option value="dibuka"    <?= $filter === 'dibuka' ? 'selected' : '' ?>>Dibuka</option>
            <option value="menunggu"  <?= $filter === 'menunggu' ? 'selected' : '' ?>>Menunggu</option>
            <option value="disetujui" <?= $filter === 'disetujui' ? 'selected' : '' ?>>Disetujui</option>
        </select>
    </form>

    <div class="eks-grid">
        <?php foreach ($daftar as $e):
            $penuh = $e['terisi'] >= $e['kuota'];
            $persen = $e['kuota'] > 0 ? min(100, (int) round($e['terisi'] / $e['kuota'] * 100)) : 0;
            $terdaftar = in_array($e['status_saya'], ['menunggu', 'disetujui'], true);
        ?>
            <article class="eks-card">
                <div class="eks-top">
                    <h3><?php if ($terdaftar): ?><a href="ekskul_detail.php?id=<?= (int) $e['id'] ?>"><?= e($e['nama']) ?></a><?php else: ?><?= e($e['nama']) ?><?php endif; ?></h3>
                    <span class="badge <?= $penuh ? 'badge--full' : '' ?>"><i></i><?= $penuh ? 'Penuh' : 'Dibuka' ?></span>
                </div>
                <p class="meta"><span>Pembimbing:</span> <?= e($e['pembimbing']) ?><br><span>Jadwal:</span> <?= e(jadwal_label($e)) ?></p>
                <div class="kuota"><span>Kuota</span><b><?= (int) $e['terisi'] ?>/<?= (int) $e['kuota'] ?></b></div>
                <div class="track"><i style="width:<?= $persen ?>%"></i></div>
                <div class="eks-action">
                    <?php if ($e['status_saya']): ?>
                        <?= status_pill($e['status_saya']) ?>
                    <?php elseif ($penuh): ?>
                        <span class="btn-primary btn-disabled">Kuota Penuh</span>
                    <?php else: ?>
                        <a class="btn-primary" href="daftar_ekskul_form.php?id=<?= (int) $e['id'] ?>">Daftar Sekarang</a>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
        <?php if (!$daftar): ?><p class="empty">Tidak ada ekstrakurikuler yang cocok.</p><?php endif; ?>
    </div>
</section>
<?php siswa_footer();
