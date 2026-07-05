TEST STORE
APK Store Platform — Product Requirements Document
Full-Stack PHP + MySQL | XAMPP → cPanel Deployable

Project Name	TEST STORE — APK Store Platform
Version	1.0.0
Stack	PHP (Backend) + HTML/CSS/JS (Frontend) + MySQL (DB)
Local Server	XAMPP (Apache + MySQL)
Production	cPanel Shared Hosting (zero-code-change deploy)
UI Library	MDBootstrap + Poppins Font
Storage Path	/storage/ (APK files, icons, screenshots)
User Roles	Normal User · Developer · Admin



1. Product Overview
TEST STORE is a full-stack APK distribution platform built with PHP and MySQL on XAMPP for local development, deployable directly to cPanel hosting without any code changes. Each page is a single self-contained .php file that handles both frontend rendering and backend logic. A single central configuration file (config.php) manages all database credentials, website settings, and global constants. Reusable UI components (header, footer, navigation, card templates) are extracted into shared include files and imported wherever needed, eliminating duplication across the codebase.

2. Architecture & Core Design Principles
2.1 Single-File Page Architecture
Every page of the website is a single .php file that merges frontend HTML/CSS/JS with PHP backend logic. There are no separate API endpoints, no MVC framework, and no build tools required. This keeps the codebase flat, portable, and easy to understand.
•	Each page file: handles its own form submissions, DB queries, session checks, and HTML output
•	PHP logic block at top → HTML output below (clear separation within one file)
•	No routing framework — Apache URL mapping handles navigation

2.2 Config-Driven Deployment
One file (config.php) controls the entire environment. Uploading the project to cPanel and updating config.php is all that is needed to go live.
// config.php — central configuration
define('DB_HOST',    'localhost');
define('DB_NAME',    'test_store');
define('DB_USER',    'root');
define('DB_PASS',    '');
define('SITE_NAME',  'TEST STORE');
define('SITE_URL',   'http://localhost/test-store');
define('STORAGE',    __DIR__ . '/storage/');
define('UPLOAD_URL', SITE_URL . '/storage/');

2.3 Reusable Component System
Shared UI pieces are stored in /includes/ and loaded with PHP require_once. Any change to a component propagates site-wide instantly.
•	includes/header.php — HTML <head>, meta tags, MDBootstrap CSS, Poppins font link
•	includes/navbar.php — top navigation bar (role-aware: user / developer / admin)
•	includes/footer.php — site footer, scripts, JS includes
•	includes/db.php — PDO connection singleton using config.php constants
•	includes/auth.php — session helpers (isLoggedIn, requireRole, getCurrentUser)
•	includes/card.php — reusable app card template for grid listings
•	includes/alerts.php — flash message display (success / error / info)

3. File & Directory Structure
test-store/
├── config.php               ← Central config (DB + settings)
├── index.php                ← Home page
├── search.php               ← Search results
├── category.php             ← Category filter listing
├── app.php                  ← APK detail page
├── download.php             ← Secure APK download handler
├── review.php               ← Ratings & reviews page
│
├── auth/
│   ├── login.php            ← User + Developer login
│   ├── register.php         ← New user registration
│   └── logout.php           ← Session destroy
│
├── developer/
│   ├── dashboard.php        ← Developer home + stats
│   ├── publish.php          ← Upload new APK
│   ├── edit-app.php         ← Edit existing APK
│   ├── my-apps.php          ← List of developer's apps
│   ├── analytics.php        ← Downloads / ratings charts
│   └── profile.php          ← Edit developer profile
│
├── admin/
│   ├── login.php            ← Admin login
│   ├── dashboard.php        ← Platform overview
│   ├── developers.php       ← List + search developers
│   ├── developer-view.php   ← Full developer profile
│   ├── apps.php             ← All apps list + filter
│   ├── app-approval.php     ← Approve / reject queue
│   └── settings.php        ← Website settings editor
│
├── includes/
│   ├── header.php
│   ├── navbar.php
│   ├── footer.php
│   ├── db.php
│   ├── auth.php
│   ├── card.php
│   └── alerts.php
│
├── storage/
│   ├── apk/                 ← APK binaries
│   ├── icons/               ← App icons
│   └── screenshots/         ← App screenshots
│
└── assets/
    ├── css/custom.css
    ├── js/main.js
    └── images/

4. Database Schema Reference
Seven tables cover all platform functionality. All tables use InnoDB with utf8mb4 encoding. Foreign keys enforce referential integrity — deleting a developer cascades to their apps, downloads, and reviews.

4.1 developers
Stores developer/user account information.

Column	Type	Constraints	Description
id	INT	PK, AUTO_INCREMENT	Unique developer ID
username	VARCHAR(100)	UNIQUE, NOT NULL	Login username / brand name
email	VARCHAR(150)	UNIQUE, NOT NULL	Contact email address
password	VARCHAR(255)	NOT NULL	bcrypt hashed password
mobile	VARCHAR(20)	NULL	Contact phone number
profile_photo	VARCHAR(255)	NULL	Path to profile image
country	VARCHAR(50)	NULL	Developer country
developer_type	ENUM('individual','company')	DEFAULT 'individual'	Account type
bio	TEXT	NULL	Short biography
role	ENUM('developer','admin')	DEFAULT 'developer'	Platform role
status	ENUM('active','blocked')	DEFAULT 'active'	Account status
created_at	TIMESTAMP	DEFAULT NOW()	Registration timestamp

4.2 apps
Core app metadata submitted by developers.

Column	Type	Constraints	Description
id	INT	PK, AUTO_INCREMENT	Unique app ID
developer_id	INT	FK → developers.id	Owning developer
app_name	VARCHAR(100)	NOT NULL	Display app name
package_name	VARCHAR(150)	UNIQUE, NOT NULL	Android package name
category	VARCHAR(50)	NOT NULL	App category
app_type	ENUM('free','paid')	NOT NULL	Pricing model
version	VARCHAR(20)	NOT NULL	Current version string
release_notes	TEXT	NULL	Changelog for this version
icon	VARCHAR(255)	NOT NULL	Path to app icon
screenshots	TEXT	NULL, JSON	JSON array of screenshot paths
promo_video_url	VARCHAR(255)	NULL	Optional promo video URL
short_description	VARCHAR(200)	NOT NULL	One-line summary
full_description	TEXT	NOT NULL	Full markdown description
keywords	TEXT	NULL	Comma-separated search keywords
apk_file	VARCHAR(255)	NOT NULL	Path to stored APK file
apk_size	INT	NOT NULL	File size in bytes
min_android_version	VARCHAR(10)	NOT NULL	Minimum Android version
target_sdk	INT	NOT NULL	Target Android SDK level
privacy_policy_url	VARCHAR(255)	NULL	Link to privacy policy
terms_url	VARCHAR(255)	NULL	Link to terms of service
content_rating	VARCHAR(20)	NULL	Age rating (e.g. Everyone)
target_audience	VARCHAR(100)	NULL	Target demographic
status	ENUM('pending','approved','rejected','removed')	DEFAULT 'pending'	Moderation status
rejection_reason	TEXT	NULL	Admin rejection note
total_downloads	INT	DEFAULT 0	Cumulative download count
avg_rating	DECIMAL(3,2)	DEFAULT 0.00	Cached average rating
total_reviews	INT	DEFAULT 0	Cached review count
created_at	TIMESTAMP	DEFAULT NOW()	Submission timestamp
updated_at	TIMESTAMP	ON UPDATE NOW()	Last update timestamp

4.3 downloads
Tracks every APK download event for analytics.

Column	Type	Constraints	Description
id	INT	PK, AUTO_INCREMENT	Download record ID
app_id	INT	FK → apps.id	Downloaded app
user_id	INT	FK → users.id, NULL	Logged-in user (NULL = guest)
ip_address	VARCHAR(45)	NOT NULL	Downloader IP (IPv6-safe)
user_agent	TEXT	NULL	Browser / OS info
downloaded_at	TIMESTAMP	DEFAULT NOW()	Download timestamp

4.4 reviews
User ratings and text reviews for apps.

Column	Type	Constraints	Description
id	INT	PK, AUTO_INCREMENT	Review ID
app_id	INT	FK → apps.id	Reviewed app
user_id	INT	FK → users.id	Reviewer account
rating	TINYINT	NOT NULL, CHECK 1–5	Star rating (1 to 5)
title	VARCHAR(100)	NULL	Short review headline
body	TEXT	NULL	Full review text
status	ENUM('visible','hidden')	DEFAULT 'visible'	Moderation state
created_at	TIMESTAMP	DEFAULT NOW()	Submission time

4.5 users
Public-facing user accounts (browse, download, review).

Column	Type	Constraints	Description
id	INT	PK, AUTO_INCREMENT	User ID
username	VARCHAR(100)	UNIQUE, NOT NULL	Display name
email	VARCHAR(150)	UNIQUE, NOT NULL	Login email
password	VARCHAR(255)	NOT NULL	bcrypt hash
avatar	VARCHAR(255)	NULL	Profile picture path
status	ENUM('active','blocked')	DEFAULT 'active'	Account status
created_at	TIMESTAMP	DEFAULT NOW()	Registration date

4.6 app_versions
Version history for each published app.

Column	Type	Constraints	Description
id	INT	PK, AUTO_INCREMENT	Version record ID
app_id	INT	FK → apps.id	Parent app
version	VARCHAR(20)	NOT NULL	Version string (e.g. 2.1.0)
apk_file	VARCHAR(255)	NOT NULL	Path to this version's APK
apk_size	INT	NOT NULL	File size in bytes
release_notes	TEXT	NULL	What changed in this version
min_android	VARCHAR(10)	NOT NULL	Min Android requirement
released_at	TIMESTAMP	DEFAULT NOW()	Release timestamp

4.7 site_settings
Key-value store for admin-controlled site configuration.

Column	Type	Constraints	Description
id	INT	PK, AUTO_INCREMENT	Setting ID
setting_key	VARCHAR(100)	UNIQUE, NOT NULL	Config key (snake_case)
setting_value	TEXT	NULL	Config value
description	VARCHAR(255)	NULL	Human-readable note
updated_at	TIMESTAMP	ON UPDATE NOW()	Last changed

5. Database Creation SQL
Run this script in phpMyAdmin or the MySQL CLI after updating the database name in config.php. All tables use InnoDB with UTF-8 (utf8mb4) collation.

-- ============================================================
--  TEST STORE — Full Database Schema
--  Run once after creating the database in phpMyAdmin / MySQL CLI
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── apps ─────────────────────────────────────────────────────
CREATE TABLE `apps` (
  `id`                  INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `developer_id`        INT UNSIGNED    NOT NULL,
  `app_name`            VARCHAR(100)    NOT NULL,
  `package_name`        VARCHAR(150)    NOT NULL,
  `category`            VARCHAR(50)     NOT NULL,
  `app_type`            ENUM('free','paid') NOT NULL,
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
  `target_sdk`          INT             NOT NULL,
  `privacy_policy_url`  VARCHAR(255)        NULL,
  `terms_url`           VARCHAR(255)        NULL,
  `content_rating`      VARCHAR(20)         NULL,
  `target_audience`     VARCHAR(100)        NULL,
  `status`              ENUM('pending','approved','rejected','removed')
                                        NOT NULL DEFAULT 'pending',
  `rejection_reason`    TEXT                NULL,
  `total_downloads`     INT UNSIGNED    NOT NULL DEFAULT 0,
  `avg_rating`          DECIMAL(3,2)    NOT NULL DEFAULT 0.00,
  `total_reviews`       INT UNSIGNED    NOT NULL DEFAULT 0,
  `created_at`          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP
                                        ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_apps_package` (`package_name`),
  KEY `fk_apps_developer` (`developer_id`),
  KEY `idx_apps_status`   (`status`),
  KEY `idx_apps_category` (`category`),
  CONSTRAINT `fk_apps_developer`
    FOREIGN KEY (`developer_id`) REFERENCES `developers` (`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── site_settings ────────────────────────────────────────────
CREATE TABLE `site_settings` (
  `id`            INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `setting_key`   VARCHAR(100)  NOT NULL,
  `setting_value` TEXT              NULL,
  `description`   VARCHAR(255)      NULL,
  `updated_at`    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP
                               ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Seed default site settings ───────────────────────────────
INSERT INTO `site_settings` (`setting_key`, `setting_value`, `description`) VALUES
  ('site_name',          'TEST STORE',          'Website display name'),
  ('site_tagline',       'Download Free Apps',  'Subtitle shown in header'),
  ('contact_email',      'admin@teststore.com', 'Support email'),
  ('max_apk_size_mb',    '100',                 'Max APK upload size in MB'),
  ('allow_registration', '1',                   '1 = open, 0 = closed'),
  ('featured_category',  'Tools',               'Category shown on homepage');

6. Page-by-Page Workflow
6.1 Public User Pages
index.php — Home Page
•	Fetches featured apps (approved, sorted by downloads)
•	Renders category strip with icons
•	Renders trending / newest / top-rated app grids using includes/card.php
•	Search bar POSTs to search.php

category.php — Category Filter
•	Reads ?cat=Games (or similar) from GET params
•	Paginates approved apps filtered by category
•	Sort controls: Newest / Most Downloaded / Top Rated

app.php — APK Detail Page
•	Reads ?id=X, fetches full app record + developer info
•	Screenshot carousel, description tabs, version history table
•	Download button triggers download.php?id=X (logs download, streams APK)
•	Reviews section loads reviews, shows submit form for logged-in users
•	Similar apps widget: same category, approved, excluding current app

6.2 Authentication Pages
auth/login.php
•	Single form handles both users and developers (checks both tables by email)
•	Sets $_SESSION['user_id'] + $_SESSION['role'] on success
•	Admin login is a separate form in admin/login.php

auth/register.php
•	Toggle between User registration and Developer registration
•	Developer form collects: username, email, password, developer_type, country
•	Passwords hashed with password_hash(PASSWORD_BCRYPT)

6.3 Developer Pages
developer/dashboard.php
•	Stats bar: total apps, total downloads (SUM), avg rating
•	Recent activity list (latest downloads / reviews)
•	Quick links to Publish, My Apps, Analytics

developer/publish.php
•	Multi-part form: app info → media upload → technical details
•	APK stored to storage/apk/{package_name}_{version}.apk
•	Icon stored to storage/icons/{package_name}.png
•	Screenshots stored to storage/screenshots/{package_name}_{n}.jpg
•	app inserted with status = 'pending', awaits admin approval

developer/analytics.php
•	Daily downloads chart (last 30 days): SELECT DATE(downloaded_at), COUNT(*)
•	Rating distribution bar chart from reviews table
•	Per-app download table with sparklines via Chart.js

6.4 Admin Pages
admin/app-approval.php
•	Lists apps WHERE status = 'pending', ordered by created_at ASC
•	Admin can preview icon, screenshots, read description
•	Approve button: UPDATE apps SET status = 'approved'
•	Reject button: shows textarea for rejection_reason, sets status = 'rejected'

admin/developers.php
•	Searchable / filterable table of all developers
•	Filters: status (active / blocked), developer_type, country
•	Block / Unblock toggle: UPDATE developers SET status = ...
•	Delete developer: cascades to all their apps + associated downloads / reviews

admin/settings.php
•	Form reads all rows from site_settings table
•	On submit: UPDATE site_settings SET setting_value = ? WHERE setting_key = ?
•	Changes take effect immediately site-wide (config loaded on each request)

7. XAMPP → cPanel Deployment Guide
Local Development Setup (XAMPP)
•	Place project folder inside C:/xampp/htdocs/test-store/
•	Start Apache + MySQL from XAMPP Control Panel
•	Open phpMyAdmin, create database test_store, import schema SQL from §5
•	Set config.php: DB_HOST=localhost, DB_USER=root, DB_PASS=(empty)
•	Visit http://localhost/test-store — site is live

cPanel Production Deployment
•	Upload entire project folder to public_html/test-store/ via File Manager or FTP
•	In cPanel → MySQL Databases: create DB, create user, assign ALL PRIVILEGES
•	Import the §5 SQL script via phpMyAdmin
•	Update config.php only — change DB_NAME, DB_USER, DB_PASS, SITE_URL
•	Verify storage/ directory permissions are 755 (writable by PHP)
•	Visit https://yourdomain.com/test-store — zero other changes needed

Setting	XAMPP (Local)	cPanel (Production)
DB_HOST	localhost	localhost
DB_USER	root	cpanel_db_user
DB_PASS	(empty)	your_strong_password
SITE_URL	http://localhost/test-store	https://yourdomain.com

8. Future Features Roadmap
•	AI-based App Recommendation — collaborative filtering on downloads + category
•	Dark Mode — CSS variable swap via JS, preference stored in localStorage
•	Push Notifications — web push API via service worker
•	App Version Control — multiple APKs per app, version-specific download links
•	Developer Verification Badge — admin-issued, stored in developers.verified column
•	Trending Apps Section — downloads in last 7 days weighted algorithm
•	REST API Integration — JSON endpoints for future mobile companion app
•	Two-Factor Authentication — TOTP for admin and developer accounts



TEST STORE PRD v1.0 — Full-Stack PHP + MySQL APK Platform
