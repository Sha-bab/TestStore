-- ============================================================
--  TEST STORE — Full Database Schema v1.0
--  Run in phpMyAdmin or MySQL CLI
-- ============================================================

CREATE DATABASE IF NOT EXISTS `test_store`
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `test_store`;

-- ── users ────────────────────────────────────────────────────
CREATE TABLE `users` (
  `id`          INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `username`    VARCHAR(100)     NOT NULL,
  `email`       VARCHAR(150)     NOT NULL,
  `password`    VARCHAR(255)     NOT NULL,
  `avatar`      VARCHAR(255)         NULL,
  `status`      ENUM('active','blocked') NOT NULL DEFAULT 'active',
  `created_at`  TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_email`    (`email`),
  UNIQUE KEY `uq_users_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── developers ───────────────────────────────────────────────
CREATE TABLE `developers` (
  `id`               INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `username`         VARCHAR(100)  NOT NULL,
  `email`            VARCHAR(150)  NOT NULL,
  `password`         VARCHAR(255)  NOT NULL,
  `mobile`           VARCHAR(20)       NULL,
  `profile_photo`    VARCHAR(255)      NULL,
  `country`          VARCHAR(50)       NULL,
  `developer_type`   ENUM('individual','company') NOT NULL DEFAULT 'individual',
  `bio`              TEXT              NULL,
  `role`             ENUM('developer','admin') NOT NULL DEFAULT 'developer',
  `status`           ENUM('active','blocked')  NOT NULL DEFAULT 'active',
  `created_at`       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_dev_email`    (`email`),
  UNIQUE KEY `uq_dev_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── apps ─────────────────────────────────────────────────────
CREATE TABLE `apps` (
  `id`                  INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `developer_id`        INT UNSIGNED    NOT NULL,
  `app_name`            VARCHAR(100)    NOT NULL,
  `package_name`        VARCHAR(150)    NOT NULL,
  `category`            VARCHAR(50)     NOT NULL,
  `app_type`            ENUM('free','paid') NOT NULL DEFAULT 'free',
  `version`             VARCHAR(20)     NOT NULL,
  `release_notes`       TEXT                NULL,
  `icon`                VARCHAR(255)    NOT NULL,
  `screenshots`         TEXT                NULL COMMENT 'JSON array',
  `promo_video_url`     VARCHAR(255)        NULL,
  `short_description`   VARCHAR(200)    NOT NULL,
  `full_description`    TEXT            NOT NULL,
  `keywords`            TEXT                NULL,
  `apk_file`            VARCHAR(255)    NOT NULL,
  `apk_size`            INT UNSIGNED    NOT NULL,
  `min_android_version` VARCHAR(10)     NOT NULL,
  `target_sdk`          INT             NOT NULL DEFAULT 33,
  `privacy_policy_url`  VARCHAR(255)        NULL,
  `terms_url`           VARCHAR(255)        NULL,
  `content_rating`      VARCHAR(20)         NULL DEFAULT 'Everyone',
  `target_audience`     VARCHAR(100)        NULL,
  `status`              ENUM('pending','approved','rejected','removed') NOT NULL DEFAULT 'pending',
  `rejection_reason`    TEXT                NULL,
  `total_downloads`     INT UNSIGNED    NOT NULL DEFAULT 0,
  `avg_rating`          DECIMAL(3,2)    NOT NULL DEFAULT 0.00,
  `total_reviews`       INT UNSIGNED    NOT NULL DEFAULT 0,
  `created_at`          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_apps_package` (`package_name`),
  KEY `fk_apps_developer` (`developer_id`),
  KEY `idx_apps_status`   (`status`),
  KEY `idx_apps_category` (`category`),
  CONSTRAINT `fk_apps_developer`
    FOREIGN KEY (`developer_id`) REFERENCES `developers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── app_versions ──────────────────────────────────────────────
CREATE TABLE `app_versions` (
  `id`            INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `app_id`        INT UNSIGNED  NOT NULL,
  `version`       VARCHAR(20)   NOT NULL,
  `apk_file`      VARCHAR(255)  NOT NULL,
  `apk_size`      INT UNSIGNED  NOT NULL,
  `release_notes` TEXT              NULL,
  `min_android`   VARCHAR(10)   NOT NULL,
  `released_at`   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_versions_app` (`app_id`),
  CONSTRAINT `fk_versions_app`
    FOREIGN KEY (`app_id`) REFERENCES `apps` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── downloads ────────────────────────────────────────────────
CREATE TABLE `downloads` (
  `id`            INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `app_id`        INT UNSIGNED  NOT NULL,
  `user_id`       INT UNSIGNED      NULL,
  `ip_address`    VARCHAR(45)   NOT NULL,
  `user_agent`    TEXT              NULL,
  `downloaded_at` TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_dl_app`  (`app_id`),
  KEY `fk_dl_user` (`user_id`),
  CONSTRAINT `fk_dl_app`  FOREIGN KEY (`app_id`)  REFERENCES `apps`  (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_dl_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── reviews ──────────────────────────────────────────────────
CREATE TABLE `reviews` (
  `id`         INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `app_id`     INT UNSIGNED  NOT NULL,
  `user_id`    INT UNSIGNED  NOT NULL,
  `rating`     TINYINT UNSIGNED NOT NULL,
  `title`      VARCHAR(100)      NULL,
  `body`       TEXT              NULL,
  `status`     ENUM('visible','hidden') NOT NULL DEFAULT 'visible',
  `created_at` TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_rev_app`  (`app_id`),
  KEY `fk_rev_user` (`user_id`),
  UNIQUE KEY `uq_one_review_per_user` (`app_id`, `user_id`),
  CONSTRAINT `fk_rev_app`  FOREIGN KEY (`app_id`)  REFERENCES `apps`  (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rev_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chk_rating` CHECK (`rating` BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── site_settings ────────────────────────────────────────────
CREATE TABLE `site_settings` (
  `id`            INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `setting_key`   VARCHAR(100)  NOT NULL,
  `setting_value` TEXT              NULL,
  `description`   VARCHAR(255)      NULL,
  `updated_at`    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Seed: site_settings ───────────────────────────────────────
INSERT INTO `site_settings` (`setting_key`, `setting_value`, `description`) VALUES
  ('site_name',          'TEST STORE',          'Website display name'),
  ('site_tagline',       'Download Free Apps',  'Subtitle shown in header'),
  ('contact_email',      'admin@teststore.com', 'Support email'),
  ('max_apk_size_mb',    '100',                 'Max APK upload size in MB'),
  ('allow_registration', '1',                   '1 = open, 0 = closed'),
  ('featured_category',  'Tools',               'Category shown on homepage');

-- ── Seed: default admin account ──────────────────────────────
-- Password: admin123  (CHANGE IMMEDIATELY after setup)
INSERT INTO `developers` (`username`, `email`, `password`, `role`, `status`, `developer_type`) VALUES
  ('admin', 'admin@teststore.com',
   '$2y$10$nt/s/oor5eOXjJgRdtnFmefiUIV4wJmbxrJqG3yfA6vKCdSpEg1Wq',
   'admin', 'active', 'company');
