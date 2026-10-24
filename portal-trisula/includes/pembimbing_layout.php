<?php
declare(strict_types=1);

require_once __DIR__ . '/siswa_layout.php';   // initials()

function pembimbing_user(PDO $pdo, int $id): array
{
    $st = $pdo->prepare('SELECT id, nama FROM users WHERE id = ?');
    $st->execute([$id]);
    $u = $st->fetch() ?: ['id' => $id, 'nama' => 'Pembimbing'];
    // Foto profil (tabel pembimbing_profil); kosong bila tabel belum ada
    try {
        $f = $pdo->prepare('SELECT foto FROM pembimbing_profil WHERE user_id = ?');
        $f->execute([$id]);
        $u['foto'] = (string) ($f->fetchColumn() ?: '');
    } catch (PDOException $e) {
        $u['foto'] = '';
    }
    return $u;
}

function pb_icon(string $name): string
{
    $p = [
        'home'      => '<path d="M3 11 12 3l9 8"/><path d="M5 10v10h14V10"/>',
        'users'     => '<circle cx="9" cy="8" r="3.5"/><path d="M2 20a7 7 0 0 1 14 0"/><circle cx="17" cy="9" r="2.5"/><path d="M18 14a5 5 0 0 1 4 5"/>',
        'calendar'  => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/>',
        'tasks'     => '<rect x="5" y="3" width="14" height="18" rx="2"/><path d="M9 8h6M9 12h6M9 16h4"/>',
        'check'     => '<rect x="5" y="3" width="14" height="18" rx="2"/><path d="m9 12 2 2 4-4"/>',
        'megaphone' => '<path d="M3 10v4h4l8 5V5L7 10z"/><path d="M18 9a4 4 0 0 1 0 6"/>',
        'chart'     => '<path d="M4 20V4M4 20h16"/><path d="m8 15 3-4 3 2 4-6"/>',
        'user'      => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
    ];
    return '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ($p[$name] ?? '') . '</svg>';
}

function pembimbing_header(string $title, string $active, array $user): void
{
    // '#' = halaman belum dibuat; ganti dengan nama file saat sudah jadi
    $menu = [
        'dashboard'  => ['Dashboard',        'dashboard_pembimbing.php', 'home'],
        'anggota'    => ['Kelola Anggota',   'kelola_anggota.php',       'users'],
        'jadwal'     => ['Jadwal',           'kelola_jadwal.php',        'calendar'],
        'tugas'      => ['Tugas',            'kelola_tugas.php',         'tasks'],
        'absensi'    => ['Absensi',          'kelola_absensi.php',       'check'],
        'pengumuman' => ['Pengumuman',       'kelola_pengumuman.php',    'megaphone'],
        'penilaian'  => ['Indeks Penilaian', 'kelola_penilaian.php',    'chart'],
    ];
    ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?> - Portal Pembimbing</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/pembimbing.css?v=<?= (int) @filemtime(__DIR__ . '/../assets/css/pembimbing.css') ?>">
</head>
<body class="pb">
<aside class="pb-side">
    <a href="dashboard_pembimbing.php" class="pb-logo">
        <img src="assets/img/logo.png" alt="" onerror="this.style.display='none'">
        <b>SMA TRISULA<br>PRAJA</b>
    </a>
    <nav>
        <?php foreach ($menu as $key => [$label, $href, $ico]): ?>
            <a href="<?= e($href) ?>" class="<?= $key === $active ? 'on' : '' ?>"><?= pb_icon($ico) ?> <span><?= e($label) ?></span></a>
        <?php endforeach; ?>
    </nav>
    <a href="logout.php" class="pb-logout">Logout</a>
</aside>

<div class="pb-main">
    <header class="pb-top">
        <a href="profil_pembimbing.php" class="pb-who" style="display:flex;align-items:center;gap:12px;color:inherit;text-decoration:none">
            <span class="pb-ava"><?php if (!empty($user['foto'])): ?><img src="<?= e($user['foto']) ?>" alt="" style="width:100%;height:100%;border-radius:50%;object-fit:cover;display:block"><?php else: ?><?= pb_icon('user') ?><?php endif; ?></span>
            <div><b><?= e($user['nama']) ?></b><em>Pembimbing</em></div>
        </a>
    </header>
    <main class="pb-page">
<?php
}

function pembimbing_footer(): void
{
    ?>
    </main>
    <footer class="pb-foot">Portal Ekstrakurikuler © <?= date('Y') ?> · SMA Trisula Praja</footer>
</div>
</body>
</html>
<?php
}
