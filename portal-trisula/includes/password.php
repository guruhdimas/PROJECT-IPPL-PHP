<?php
declare(strict_types=1);

/**
 * Logika ganti password milik sendiri (dipakai halaman siswa & pembimbing).
 * Mengembalikan daftar pesan error; array kosong berarti berhasil.
 */
const PASSWORD_MIN = 8;   // ubah di sini kalau mau aturan lain
const PASSWORD_MAX = 72;  // batas aman bcrypt (PASSWORD_DEFAULT)

function ubah_password_sendiri(PDO $pdo, int $userId, string $lama, string $baru, string $ulang): array
{
    $errors = [];

    if ($lama === '')                         $errors[] = 'Password lama wajib diisi.';
    if (strlen($baru) < PASSWORD_MIN)         $errors[] = 'Password baru minimal ' . PASSWORD_MIN . ' karakter.';
    if (strlen($baru) > PASSWORD_MAX)         $errors[] = 'Password baru maksimal ' . PASSWORD_MAX . ' karakter.';
    if ($baru !== $ulang)                     $errors[] = 'Konfirmasi password baru tidak sama.';
    if ($lama !== '' && $baru === $lama)      $errors[] = 'Password baru harus berbeda dari password lama.';
    if ($errors) {
        return $errors;
    }

    $st = $pdo->prepare('SELECT password FROM users WHERE id = ? LIMIT 1');
    $st->execute([$userId]);
    $hash = $st->fetchColumn();

    if (!is_string($hash) || !password_verify($lama, $hash)) {
        return ['Password lama salah.'];
    }

    $pdo->prepare('UPDATE users SET password = ? WHERE id = ?')
        ->execute([password_hash($baru, PASSWORD_DEFAULT), $userId]);

    session_regenerate_id(true); // amankan sesi setelah kredensial berubah
    return [];
}
