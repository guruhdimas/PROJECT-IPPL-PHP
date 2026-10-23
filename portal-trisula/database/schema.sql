CREATE DATABASE IF NOT EXISTS portal_trisula CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE portal_trisula;

CREATE TABLE IF NOT EXISTS users (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username   VARCHAR(50)  NOT NULL,
    password   VARCHAR(255) NOT NULL,          -- hasil password_hash()
    nama       VARCHAR(100) NOT NULL,
    role       ENUM('siswa','pembimbing') NOT NULL DEFAULT 'siswa',
    nis        VARCHAR(20)  NULL,
    angkatan   YEAR         NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_username_role (username, role)
);
