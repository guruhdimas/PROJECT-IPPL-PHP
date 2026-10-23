<?php
declare(strict_types=1);

/** Ikon sidebar (SVG sederhana, stroke mengikuti warna teks) */
function icon(string $name): string
{
    $p = [
        'dashboard'  => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>',
        'ekskul'     => '<path d="M8 6h13M8 12h13M8 18h13"/><circle cx="4" cy="6" r="1"/><circle cx="4" cy="12" r="1"/><circle cx="4" cy="18" r="1"/>',
        'jadwal'     => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/>',
        'tugas'      => '<rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 4h6v3H9zM9 12h6M9 16h6"/>',
        'absensi'    => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
        'pengumuman' => '<rect x="3" y="7" width="18" height="12" rx="2"/><path d="M9 7V5h6v2"/>',
        'penilaian'  => '<path d="M6 20V10M12 20V4M18 20v-7"/>',
        'keluar'     => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/>',
    ];
    return '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ($p[$name] ?? '') . '</svg>';
}

/** "Abdul Husein, S.T" -> "AH" */
function initials(string $nama): string
{
    $nama  = trim(explode(',', $nama)[0]);
    $parts = preg_split('/\s+/', $nama) ?: [];
    $out   = '';
    foreach (array_slice($parts, 0, 2) as $w) {
        $out .= mb_strtoupper(mb_substr($w, 0, 1));
    }
    return $out;
}

function avatar_color(string $nama): string
{
    $palette = ['#f97316', '#06b6d4', '#8b5cf6', '#ef4444', '#16a34a'];
    return $palette[crc32($nama) % count($palette)];
}

function time_ago(string $datetime): string
{
    $diff = max(0, time() - strtotime($datetime));
    if ($diff < 3600)  return max(1, intdiv($diff, 60)) . ' menit yang lalu';
    if ($diff < 86400) return intdiv($diff, 3600) . ' jam yang lalu';
    return intdiv($diff, 86400) . ' hari yang lalu';
}

function siswa_header(string $title, string $active, array $user): void
{
    // Halaman yang belum dibuat memakai '#'. Ganti dengan nama file-nya saat halamannya jadi.
    $menu = [
        'dashboard'  => ['Dashboard',    'dashboard_siswa.php'],
        'ekskul'     => ['Daftar Ekskul', 'daftar_ekskul.php'],
        'jadwal'     => ['Jadwal',       'jadwal.php'],
        'tugas'      => ['Tugas',        'tugas.php'],
        'absensi'    => ['Absensi',      'absensi.php'],
        'pengumuman' => ['Pengumuman',   'pengumuman.php'],
        'penilaian'  => ['Penilaian',    'penilaian.php'],
    ];
    ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?> - Portal Ekstrakurikuler</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/dashboard.css">
</head>
<body class="app">
<aside class="sidebar">
    <div class="brand">
        <img src="assets/img/logo.png" alt="" onerror="this.style.visibility='hidden'">
        <div><b>SMA TRISULA PRAJA</b><span>EKSTRAKURIKULER</span></div>
    </div>
    <p class="menu-label">MENU UTAMA</p>
    <nav>
        <?php foreach ($menu as $key => [$label, $href]): ?>
            <a href="<?= e($href) ?>" class="<?= $key === $active ? 'active' : '' ?>">
                <?= icon($key) ?> <span><?= e($label) ?></span>
            </a>
        <?php endforeach; ?>
    </nav>
    <a href="logout.php" class="logout"><?= icon('keluar') ?> <span>Keluar</span></a>
</aside>

<div class="content">
    <header class="top">
        <h1><?= e($title) ?></h1>
        <a href="profil.php" class="who" style="text-decoration:none;color:inherit">
            <span class="avatar" style="background:#004029"><?= e(initials($user['nama'])) ?></span>
            <div><b><?= e($user['nama']) ?></b><small><?= e($user['kelas'] ?? '') ?></small></div>
        </a>
    </header>
    <main class="page">
<?php
}

function siswa_footer(): void
{
    ?>
    </main>
    <footer class="foot">Portal Ekstrakurikuler © <?= date('Y') ?> · SMA Trisula Praja</footer>
</div>
</body>
</html>
<?php
}
