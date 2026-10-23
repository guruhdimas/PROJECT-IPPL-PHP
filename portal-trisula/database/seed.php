<?php
// Jalankan sekali dari terminal:  php database/seed.php
require __DIR__ . '/../config/database.php';

$rows = [
    ['budi',     'siswa123',      'Budi Setiawan',      'siswa',      '202210045', 2025],
    ['pak.andi', 'pembimbing123', 'Andi Wijaya, S.Pd.', 'pembimbing', null,        null],
];

$stmt = db()->prepare(
    'INSERT INTO users (username, password, nama, role, nis, angkatan)
     VALUES (?,?,?,?,?,?)
     ON DUPLICATE KEY UPDATE password = VALUES(password)'
);
foreach ($rows as [$u, $p, $n, $r, $nis, $ang]) {
    $stmt->execute([$u, password_hash($p, PASSWORD_DEFAULT), $n, $r, $nis, $ang]);
    echo "OK: $u ($r)\n";
}
