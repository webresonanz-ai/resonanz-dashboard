-- ═══════════════════════════════════════════════════════════════
--  Resonanz Music Foundation — Database Schema
--  Engine : InnoDB | Charset : utf8mb4 / utf8mb4_unicode_ci
-- ═══════════════════════════════════════════════════════════════

CREATE DATABASE IF NOT EXISTS `resonanz_db`
    DEFAULT CHARACTER SET utf8mb4
    DEFAULT COLLATE utf8mb4_unicode_ci;

USE `resonanz_db`;

-- ───────────────────────────────────────────────────────────────
--  users
-- ───────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `users` (
    `id`         INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `name`       VARCHAR(100)    NOT NULL,
    `email`      VARCHAR(255)    NOT NULL,
    `password`   VARCHAR(255)    NOT NULL         COMMENT 'bcrypt hash',
    `role`       ENUM('user','admin') NOT NULL DEFAULT 'user',
    `created_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    UNIQUE  KEY `uq_users_email` (`email`),
    INDEX        `idx_users_role`  (`role`)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

-- ───────────────────────────────────────────────────────────────
--  Seed: default admin account
--  Password: password  (bcrypt cost 12, verified)
--  ⚠  Change this immediately in production.
-- ───────────────────────────────────────────────────────────────
INSERT IGNORE INTO `users` (`name`, `email`, `password`, `role`)
VALUES (
    'Resonanz Admin',
    'admin@resonanz.org',
    '$2y$12$zMIfqJJsddGPhzWPgwfsJOKrbq3CPcb21Mk9aeIx.oXbuPIHEU6My',
    'admin'
);
