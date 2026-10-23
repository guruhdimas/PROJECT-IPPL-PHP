<?php
require __DIR__ . '/includes/auth.php';
$user = require_role('pembimbing');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard Pembimbing</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="dash-page">
    <header class="dash-head">
        <div>
            <p>Selamat Datang Kembali 👋</p>
            <h1><?= e($user['nama']) ?></h1>
            
            
        </div>
        <a class="btn-out" href="logout.php">Keluar</a>
    </header>
    <main class="dash-main">
        <p>Dashboard pembimbing. Lanjutkan dengan halaman Kelola Anggota, Jadwal, Absensi, Pengumuman, Penilaian, dan Tugas.</p>
    </main>
</body>
</html>
