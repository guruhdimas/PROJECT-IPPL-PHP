<?php
// Alat bantu sementara: cek foto Portal sudah ada di assets/img atau belum.
// Buka: http://localhost/portal-trisula/cek_gambar.php  (hapus file ini setelah selesai)
$dir = __DIR__ . '/assets/img/';
$perlu = [
    'logo.png', 'lambang.png', 'lambang_pudar.png', 'gedung.jpg', 'hero_pola.jpg',
    'pendiri1.jpg', 'pendiri2.jpg', 'pendiri3.jpg', 'pendiri4.jpg',
    'prestasi1.jpg', 'prestasi2.jpg', 'prestasi3.jpg',
    'robotik.jpg', 'cyber.jpg', 'debat.jpg', 'sains.jpg', 'seni.jpg', 'basket.jpg',
];
$ada = is_dir($dir) ? array_diff(scandir($dir), ['.', '..']) : [];
$hilang = 0;
?>
<!DOCTYPE html>
<html lang="id"><head><meta charset="UTF-8"><title>Cek gambar</title>
<style>
body{font:15px/1.5 system-ui,sans-serif;margin:32px;color:#111}
table{border-collapse:collapse}td,th{border:1px solid #ccc;padding:6px 12px;text-align:left}
.ok{color:#15803d;font-weight:600}.no{color:#b91c1c;font-weight:600}
code{background:#f1f5f9;padding:2px 6px;border-radius:4px}
</style></head><body>
<h2>Cek foto Portal</h2>
<p>Folder dicek: <code><?= htmlspecialchars($dir) ?></code> <?= is_dir($dir) ? '' : '<b class="no">(folder tidak ditemukan!)</b>' ?></p>
<table><tr><th>File yang dicari kode</th><th>Status</th><th>Ukuran</th></tr>
<?php foreach ($perlu as $f): $p = $dir . $f; $o = is_file($p); if (!$o) $hilang++; ?>
<tr><td><?= $f ?></td>
<td class="<?= $o ? 'ok' : 'no' ?>"><?= $o ? 'ADA' : 'TIDAK ADA' ?></td>
<td><?= $o ? round(filesize($p) / 1024) . ' KB' : '-' ?></td></tr>
<?php endforeach; ?>
</table>
<p><b><?= $hilang ?></b> dari <?= count($perlu) ?> file belum ada.</p>
<?php if ($hilang): ?>
<p>Salin file dari <code>assets_img.zip</code> (folder <code>assets\img</code> di dalamnya) ke folder di atas.</p>
<?php endif; ?>
<h3>Semua file di folder (<?= count($ada) ?>)</h3>
<ul><?php foreach ($ada as $f): ?><li><?= htmlspecialchars($f) ?></li><?php endforeach; ?></ul>
</body></html>
