<?php
declare(strict_types=1);

require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/siswa_layout.php';
require __DIR__ . '/includes/ekskul.php';

$session = require_role('siswa');
$pdo     = db();
$user    = load_user($pdo, (int) $session['id']);

$id     = (int) ($_GET['id'] ?? 0);
$ekskul = find_ekskul($pdo, $id);
$daftar = $ekskul ? find_pendaftaran($pdo, (int) $user['id'], $id) : null;
if (!$ekskul || !$daftar) { header('Location: daftar_ekskul.php'); exit; }

siswa_header('Daftar Ekskul', 'ekskul', $user);
?>
<section class="panel done-card">
    <div class="done-ico">✓</div>
    <h2>Pendaftaran Terkirim</h2>
    <p>Pendaftaran Anda ke <b><?= e($ekskul['nama']) ?></b> sedang menunggu persetujuan pembimbing <b><?= e($ekskul['pembimbing']) ?></b>. Anda akan menerima notifikasi setelah diproses.</p>
    <div class="done-info">
        <div><small>Ekskul</small><b><?= e($ekskul['nama']) ?></b></div>
        <div><small>Tanggal</small><b><?= e(tanggal_id(substr($daftar['created_at'], 0, 10))) ?></b></div>
        <div><small>Status</small><b class="txt-wait"><?= e(ucfirst($daftar['status'])) ?></b></div>
    </div>
    <a class="btn-primary" href="daftar_ekskul.php">Kembali ke Daftar Ekskul</a>
</section>
<?php siswa_footer();
