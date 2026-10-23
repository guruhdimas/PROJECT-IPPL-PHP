<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function e(?string $v): string
{
    return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_valid(?string $token): bool
{
    return is_string($token) && isset($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $token);
}

function is_logged_in(): bool
{
    return isset($_SESSION['user']);
}

function home_for_role(string $role): string
{
    return $role === 'pembimbing' ? 'dashboard_pembimbing.php' : 'dashboard_siswa.php';
}

/** Wajib login dan sesuai peran; kalau tidak, lempar ke login. */
function require_role(string $role): array
{
    if (!is_logged_in() || $_SESSION['user']['role'] !== $role) {
        header('Location: login.php');
        exit;
    }
    return $_SESSION['user'];
}
