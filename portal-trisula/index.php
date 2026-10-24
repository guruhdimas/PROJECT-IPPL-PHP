<?php
declare(strict_types=1);
require __DIR__ . '/includes/auth.php';

$loginUrl = is_logged_in() ? home_for_role($_SESSION['user']['role']) : 'login.php';

$pendiri = [
    ['Dr. H. Arif Pratama, M.Pd.',  'Pendiri utama dan penggagas sekolah',   'Berperan merumuskan visi pendidikan, standar akademik, dan sistem pembinaan karakter siswa.', 'pendiri1.jpg'],
    ['Dra. Siti Rahmawati, M.Pd.',  'Pendiri bidang kurikulum',              'Bertanggung jawab dalam konsep pengembangan kurikulum, metode pembelajaran, dan peningkatan kompetensi guru.', 'pendiri2.jpg'],
    ['Ir. Bima Saputra, M.Kom.',    'Pendiri bidang teknologi dan inovasi',  'Menggagas pemanfaatan teknologi informasi, laboratorium komputer, dan program inovasi digital bagi siswa.', 'pendiri3.jpg'],
    ['Dr. Maya Kusumawardani, M.M.','Pendiri bidang pengembangan siswa',     'Mengembangkan program kepemimpinan, kegiatan sosial, ekstrakurikuler, dan kemitraan masyarakat.', 'pendiri4.jpg'],
];
$misi = [
    'Menyelenggarakan pendidikan berkualitas dengan pembelajaran yang kreatif dan inovatif.',
    'Membentuk siswa yang disiplin, jujur, bertanggung jawab, dan memiliki jiwa kepemimpinan.',
    'Mengembangkan kemampuan teknologi, bahasa asing, sains, seni, dan olahraga.',
    'Mendorong siswa untuk aktif mengikuti kompetisi akademik maupun nonakademik.',
    'Membangun kerja sama pendidikan dengan sekolah dan lembaga internasional.',
    'Menanamkan kepedulian terhadap lingkungan dan masyarakat.',
];
$prestasi = [
    ['Juara 1 Kompetisi Robotika Pelajar Nasional', '2025', 'Tim robotika berhasil meraih juara melalui inovasi robot pemilah sampah otomatis berbasis sensor.', 'prestasi1.jpg'],
    ['Medali Emas Olimpiade Sains Pelajar',         '2025', 'Siswa memperoleh medali dalam kompetisi sains bidang informatika dan matematika.', 'prestasi2.jpg'],
    ['Juara Turnamen Basket Antarpelajar',          '2024', 'Tim basket meraih gelar juara tingkat provinsi melalui pembinaan olahraga yang terarah.', 'prestasi3.jpg'],
];
$stats = [['Siswa Baru 2025/2026', '320 Siswa'], ['Siswa Aktif', '1.248 Siswa'], ['Siswa International', '24 Siswa']];
$ekskul = [
    ['Robotics and IoT Club',            'Merakit robot, mempelajari sensor, mikrokontroler, Internet of Things, dan mengikuti kompetisi teknologi.', 'robotik.jpg'],
    ['Cyber Security and Programming',   'Belajar pemrograman, jaringan komputer, keamanan digital, pengembangan website, dan kompetisi Capture the Flag secara legal.', 'cyber.jpg'],
    ['English Debate and Public Speaking','Melatih cara berkomunikasi, argumentasi, presentasi, dan kemampuan berbahasa Inggris.', 'debat.jpg'],
    ['Science Research Club',            'Mengembangkan penelitian, eksperimen, karya ilmiah, dan inovasi untuk kompetisi sains.', 'sains.jpg'],
    ['Seni dan Budaya Nusantara',        'Mengembangkan bakat tari tradisional, musik, teater, dan pelestarian budaya Indonesia.', 'seni.jpg'],
    ['Basket dan Futsal Club',           'Membangun kebugaran, kerja sama tim, kedisiplinan, dan kemampuan berkompetisi.', 'basket.jpg'],
];
$peran = [
    'Meningkatkan kualitas pendidikan: membekali siswa dengan ilmu pengetahuan dan keterampilan yang relevan.',
    'Membangun karakter: membentuk pribadi yang jujur, disiplin, mandiri, dan bertanggung jawab.',
    'Mengembangkan teknologi: memperkenalkan pemrograman, jaringan komputer, dan inovasi digital.',
    'Mendorong prestasi: menyediakan ruang bagi siswa untuk mengembangkan bakat akademik dan nonakademik.',
    'Memperluas wawasan global: menyiapkan siswa untuk berinteraksi dalam lingkungan multikultural.',
    'Meningkatkan kepedulian sosial: mendorong kegiatan pengabdian masyarakat dan pelestarian lingkungan.',
];
$layanan = ['Penerimaan siswa baru', 'Akademik', 'Kesiswaan', 'Bimbingan konseling', 'Layanan orang tua', 'Kerja sama internasional', 'Layanan alumni'];
$keterangan = [
    'Informasi pendaftaran, seleksi, dan pengumuman', 'Kalender pendidikan, jadwal, dan informasi pembelajaran',
    'Organisasi siswa, ekstrakurikuler, dan prestasi', 'Pendampingan akademik dan pengembangan diri',
    'Informasi perkembangan belajar siswa', 'Program pertukaran pelajar dan kegiatan lintas budaya',
    'Pendataan alumni dan informasi studi lanjutan',
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Portal Ekstrakurikuler SMA Trisula Praja</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;800&family=Condiment&family=Concert+One&family=Baloo+Bhaina+2&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css?v=<?= (int) @filemtime(__DIR__ . '/assets/css/style.css') ?>">
</head>
<body>

<header class="topbar">
    <a href="#portal" class="logo-badge" aria-label="SMA Trisula Praja">
        <img src="assets/img/logo.png" alt="Logo SMA Trisula Praja" onerror="this.style.display='none'">
    </a>
    <nav class="nav">
        <a href="#portal">Portal</a>
        <a href="#sejarah">Sejarah Trisula</a>
        <a href="#prestasi">Prestasi</a>
        <a href="#ekskul">Rekomendasi Ekskul</a>
        <a href="<?= e($loginUrl) ?>">Pendaftaran</a>
        <a href="#layanan">Layanan</a>
    </nav>
</header>

<section class="hero" id="portal">
    <p class="welcome">SELAMAT DATANG<br>di</p>
    <h1>
        <span class="t-script">PORTAL</span>
        <span class="t-script t-script--2">EKSTRAKURIKULER</span>
        <span class="t-title">TRISULA PRAJA</span>
    </h1>
    <div class="hero-actions">
        <a class="btn btn--gold" href="<?= e($loginUrl) ?>">Masuk</a>
        <a class="btn btn--ghost" href="#sejarah">Pelajari Selengkapnya &gt;</a>
    </div>
</section>
<div class="yellow-line"></div>
<div class="campus" role="img" aria-label="Gedung SMA Trisula Praja"></div>

<section class="block" id="sejarah">
    <h2>Sejarah Singkat</h2>
    <p>SMA Trisula Praja merupakan konsep sekolah menengah atas yang dirancang sebagai sekolah modern, unggul dalam akademik, teknologi, seni, olahraga, serta pembentukan karakter. Sekolah ini mengusung semangat untuk mencetak generasi muda yang disiplin, kreatif, berintegritas, dan siap bersaing di tingkat nasional maupun internasional.</p>
    <p>Catatan: seluruh sejarah, nama pendiri, statistik, prestasi, alamat, dan informasi kontak di bawah ini merupakan data rekaan untuk keperluan desain profil sekolah, website, atau proyek. Data tersebut bukan klaim tentang sekolah nyata.</p>
    <p>SMA Trisula Praja didirikan pada 15 Juli 2008 oleh sekelompok tokoh pendidikan dan pemerhati generasi muda yang memiliki visi membangun lembaga pendidikan berkualitas dengan keseimbangan antara ilmu pengetahuan, karakter, dan keterampilan.</p>
    <p>Pada awal berdirinya, sekolah ini hanya memiliki 6 ruang kelas, 12 tenaga pendidik, dan 96 siswa angkatan pertama. Berkat dukungan para pendiri, guru, orang tua, dan masyarakat, SMA Trisula Praja berkembang menjadi sekolah yang mengutamakan pembelajaran berbasis teknologi, kegiatan penelitian, kewirausahaan, serta pengembangan bakat siswa.</p>
    <p>Memasuki tahun 2018, sekolah mulai memperluas fasilitas laboratorium komputer, perpustakaan digital, lapangan olahraga, dan pusat kegiatan ekstrakurikuler. Pada 2022, sekolah memperkenalkan program Global Student Exchange sebagai konsep kerja sama pendidikan internasional.</p>
    <p>Kini, dalam profil konseptualnya, SMA Trisula Praja dikenal sebagai sekolah berwawasan global yang memadukan prestasi akademik, kreativitas, kepemimpinan, dan kepedulian sosial.</p>
</section>

<section class="block">
    <h2>PARA PENDIRI SEKOLAH</h2>
    <div class="founders">
        <?php foreach ($pendiri as [$nama, $jabatan, $peranPendiri, $foto]): ?>
            <article class="founder">
                <div class="photo" style="background-image:url('assets/img/<?= e($foto) ?>')" role="img" aria-label="Foto <?= e($nama) ?>"></div>
                <div><h3><?= e($nama) ?></h3><b><small><?= e($jabatan) ?></small></b><small><?= e($peranPendiri) ?></small></div>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<div class="divider"></div>

<section class="block vm">
    <h2>VISI DAN MISI SEKOLAH</h2>
    <h3>VISI</h3>
    <p class="center" style="text-align:center">Menjadi sekolah menengah atas unggulan yang menghasilkan generasi berkarakter, berprestasi, inovatif, dan mampu bersaing di tingkat internasional.</p>
    <h3>MISI</h3>
    <ol><?php foreach ($misi as $m): ?><li><?= e($m) ?></li><?php endforeach; ?></ol>
</section>

<div class="divider"></div>

<section class="block" id="prestasi">
    <h2>PRESTASI UNGGULAN SEKOLAH</h2>
    <div class="awards">
        <?php foreach ($prestasi as [$judul, $tahun, $desk, $foto]): ?>
            <article class="award">
                <div class="thumb" style="background-image:url('assets/img/<?= e($foto) ?>')" role="img" aria-label="<?= e($judul) ?>"></div>
                <div><b><?= e($judul) ?></b><?= e($tahun) ?><br><?= e($desk) ?></div>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<section class="home-stats">
    <?php foreach ($stats as [$label, $nilai]): ?>
        <div><span><?= e($label) ?></span><strong><?= e($nilai) ?></strong></div>
    <?php endforeach; ?>
</section>

<section class="block ekskul" id="ekskul">
    <h2>Rekomendasi Ekskul Terbaik</h2>
    <div class="cards">
        <?php foreach ($ekskul as [$judul, $desk, $gambar]): ?>
            <article class="card">
                <div class="pic" style="background-image:url('assets/img/<?= e($gambar) ?>')" role="img" aria-label="<?= e($judul) ?>"></div>
                <h3><?= e($judul) ?></h3>
                <p><?= e($desk) ?></p>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<section class="block peran">
    <h2>PERAN SMA TRISULA PRAJA</h2>
    <p style="text-align:left">SMA Trisula Praja memiliki peran penting dalam konsep pengembangan pendidikan, yaitu:</p>
    <ul><?php foreach ($peran as $p): ?><li><?= e($p) ?></li><?php endforeach; ?></ul>
    <p class="center" style="text-align:center"><a class="btn btn--gold" href="<?= e($loginUrl) ?>">Masuk</a></p>
</section>

<div class="footer-logo">
    <img src="assets/img/logo.png" alt="" style="margin:0 auto 8px;width:220px" onerror="this.style.display='none'">
    SMA TRISULA PRAJA
    <em>“Cerdas dalam Ilmu, Tangguh dalam Karakter, Unggul dalam Prestasi.”</em>
</div>

<footer id="layanan">
    <div>
        <p>Jl. Trisula Praja No. 15, Kecamatan Marpoyan Damai, Kota Pekanbaru, Riau, Indonesia.<br><small>Alamat simulasi untuk proyek profil sekolah.</small></p>
        <p style="margin-top:8px">0761-000-000<br><small>Nomor contoh, bukan nomor aktif terverifikasi.</small></p>
        <p style="margin-top:8px">info@trisulapraja.example<br><small>Alamat email contoh untuk rancangan website.</small></p>
    </div>
    <div>
        <h4>Layanan Dan Informasi</h4>
        <ul><?php foreach ($layanan as $l): ?><li><?= e($l) ?></li><?php endforeach; ?></ul>
    </div>
    <div>
        <h4>Keterangan</h4>
        <ul><?php foreach ($keterangan as $k): ?><li>- <?= e($k) ?></li><?php endforeach; ?></ul>
    </div>
</footer>

</body>
</html>
