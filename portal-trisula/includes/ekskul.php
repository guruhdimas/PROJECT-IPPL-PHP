<?php
declare(strict_types=1);

function load_user(PDO $pdo, int $id): array
{
    $st = $pdo->prepare('SELECT id, nama, nis, angkatan, kelas FROM users WHERE id = ?');
    $st->execute([$id]);
    return $st->fetch() ?: ['id' => $id, 'nama' => '', 'nis' => '', 'angkatan' => '', 'kelas' => ''];
}

function jam_fmt(string $time): string
{
    return str_replace(':', '.', substr($time, 0, 5));
}

function jadwal_label(array $e): string
{
    return $e['hari'] . ', ' . jam_fmt($e['jam_mulai']) . ' - ' . jam_fmt($e['jam_selesai']);
}

function tanggal_id(string $date): string
{
    $bulan = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    $t = strtotime($date);
    return date('j', $t) . ' ' . $bulan[(int) date('n', $t)] . ' ' . date('Y', $t);
}

function bulan_pendek(string $date): string
{
    $bulan = ['', 'JAN', 'FEB', 'MAR', 'APR', 'MEI', 'JUN', 'JUL', 'AGU', 'SEP', 'OKT', 'NOV', 'DES'];
    return $bulan[(int) date('n', strtotime($date))];
}

/** Ambil ekskul + jumlah anggota yang disetujui. */
function find_ekskul(PDO $pdo, int $id): ?array
{
    $st = $pdo->prepare("SELECT e.*, (SELECT COUNT(*) FROM pendaftaran p WHERE p.ekskul_id = e.id AND p.status = 'disetujui') AS terisi
                         FROM ekskul e WHERE e.id = ?");
    $st->execute([$id]);
    $row = $st->fetch();
    return $row ?: null;
}

function find_pendaftaran(PDO $pdo, int $siswaId, int $ekskulId): ?array
{
    $st = $pdo->prepare('SELECT * FROM pendaftaran WHERE siswa_id = ? AND ekskul_id = ?');
    $st->execute([$siswaId, $ekskulId]);
    $row = $st->fetch();
    return $row ?: null;
}

function status_pill(string $status): string
{
    $map = ['menunggu' => ['Menunggu', 'pill-wait'], 'disetujui' => ['Disetujui', 'pill-ok'], 'ditolak' => ['Ditolak', 'pill-no']];
    [$label, $cls] = $map[$status] ?? [$status, 'pill-wait'];
    return '<span class="spill ' . $cls . '"><i></i>' . e($label) . '</span>';
}
