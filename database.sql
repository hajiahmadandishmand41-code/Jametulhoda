-- ============================================================
-- مدرسه علمیه جامعه‌الهدی — Database Schema (نسخه امن ۲.۳)
-- نسخه: 2.3 | بدون DROP TABLE — داده‌های موجود حفظ می‌شوند
-- ============================================================
-- ⚠️ این فایل از CREATE TABLE IF NOT EXISTS استفاده می‌کند.
--    جداول موجود یا داده‌های قبلی حذف نمی‌شوند.
--    برای نصب اولیه و یا اضافه کردن جداول جدید ایمن است.
-- ============================================================
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET time_zone = '+04:30';

-- ─── users ────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `users` (
  `id`         INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `username`   VARCHAR(80)      NOT NULL,
  `email`      VARCHAR(180)     NOT NULL DEFAULT '',
  `password`   VARCHAR(255)     NOT NULL,
  `full_name`  VARCHAR(120)     NOT NULL DEFAULT '',
  `role`       ENUM('superadmin','admin','editor') NOT NULL DEFAULT 'admin',
  `is_active`  TINYINT(1)       NOT NULL DEFAULT 1,
  `last_login` DATETIME             NULL,
  `created_at` DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── categories ───────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `categories` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`        VARCHAR(120) NOT NULL,
  `slug`        VARCHAR(160) NOT NULL,
  `description` TEXT             NULL,
  `post_type`   VARCHAR(30)  NOT NULL DEFAULT 'all',
  `sort_order`  INT          NOT NULL DEFAULT 0,
  `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── posts ────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `posts` (
  `id`              INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `title`           VARCHAR(400)     NOT NULL,
  `slug`            VARCHAR(450)     NOT NULL,
  `summary`         TEXT                 NULL,
  `content`         LONGTEXT             NULL,
  `featured_image`  VARCHAR(350)         NULL,
  `featured_video`  VARCHAR(500)         NULL,
  `post_type`       ENUM('news','article','announcement','speech','program','religious') NOT NULL DEFAULT 'news',
  `page_section`    VARCHAR(300)     NOT NULL DEFAULT 'home,news',
  `category_id`     INT UNSIGNED         NULL,
  `author_id`       INT UNSIGNED         NULL,
  `status`          ENUM('published','draft') NOT NULL DEFAULT 'draft',
  `is_featured`     TINYINT(1)       NOT NULL DEFAULT 0,
  `views`           INT UNSIGNED     NOT NULL DEFAULT 0,
  `published_at`    DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at`      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_slug` (`slug`(191)),
  KEY `idx_status_type` (`status`, `post_type`),
  KEY `idx_category`    (`category_id`),
  KEY `idx_published`   (`published_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── post_images ──────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `post_images` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `post_id`    INT UNSIGNED NOT NULL,
  `image_path` VARCHAR(350) NOT NULL,
  `alt_text`   VARCHAR(200)     NULL,
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_post` (`post_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── lessons ──────────────────────────────────────────────
-- ستون‌های summary و page_section اضافه شده (نسخه 2.3)
CREATE TABLE IF NOT EXISTS `lessons` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title`          VARCHAR(400) NOT NULL,
  `slug`           VARCHAR(450) NOT NULL,
  `subject`        VARCHAR(150)     NULL,
  `teacher`        VARCHAR(150)     NULL,
  `content`        LONGTEXT         NULL,
  `summary`        TEXT             NULL,
  `audio_file`     VARCHAR(350)     NULL,
  `featured_image` VARCHAR(350)     NULL,
  `level`          ENUM('beginner','intermediate','advanced') NOT NULL DEFAULT 'beginner',
  `status`         ENUM('published','draft') NOT NULL DEFAULT 'draft',
  `page_section`   VARCHAR(255) NOT NULL DEFAULT 'home',
  `sort_order`     INT          NOT NULL DEFAULT 0,
  `views`          INT UNSIGNED NOT NULL DEFAULT 0,
  `created_by`     INT UNSIGNED     NULL,
  `created_at`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_slug` (`slug`(191)),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── contact_messages ─────────────────────────────────────
-- از is_read استفاده می‌کند (سازگار با پنل مدیریت)
CREATE TABLE IF NOT EXISTS `contact_messages` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`       VARCHAR(200) NOT NULL,
  `email`      VARCHAR(200)     NULL,
  `phone`      VARCHAR(50)      NULL,
  `subject`    VARCHAR(300)     NULL,
  `message`    TEXT         NOT NULL,
  `ip_address` VARCHAR(45)      NULL,
  `is_read`    TINYINT(1)   NOT NULL DEFAULT 0,
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_is_read` (`is_read`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── post_likes ───────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `post_likes` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `post_id`    INT UNSIGNED NOT NULL,
  `ip_hash`    VARCHAR(64)  NOT NULL,
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_like` (`post_id`, `ip_hash`),
  KEY `idx_post` (`post_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── media_files ──────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `media_files` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ref_type`   VARCHAR(30)  NOT NULL DEFAULT 'post',
  `ref_id`     INT UNSIGNED NOT NULL,
  `kind`       ENUM('image','video','audio','document') NOT NULL DEFAULT 'image',
  `file_path`  VARCHAR(500) NOT NULL,
  `title`      VARCHAR(300)     NULL,
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ref`  (`ref_type`, `ref_id`),
  KEY `idx_kind` (`kind`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── settings ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `settings` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `key`        VARCHAR(100) NOT NULL,
  `value`      TEXT             NULL,
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_key` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Default admin user (فقط اگر وجود نداشت) ─────────────
-- رمز عبور: Admin@1234  (bcrypt hash)
INSERT IGNORE INTO `users`
  (`username`, `email`, `password`, `full_name`, `role`, `is_active`)
VALUES
  ('admin', 'admin@jamiatalhoda.af',
   '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
   'مدیر سیستم', 'superadmin', 1);

-- ─── Default settings (فقط اگر وجود نداشت) ──────────────
INSERT IGNORE INTO `settings` (`key`, `value`) VALUES
('site_name',        'مدرسه علمیه جامعه‌الهدی'),
('site_slogan',      'علم، معرفت و تهذیب در پرتو قرآن و عترت'),
('site_email',       'hajiahmads299@gmail.com'),
('address',          'کابل، افغانستان'),
('phone',            '0798228441'),
('email',            'hajiahmads299@gmail.com'),
('social_telegram',  ''),
('social_youtube',   ''),
('social_instagram', '');

-- ─── Default categories (فقط اگر وجود نداشت) ────────────
INSERT IGNORE INTO `categories` (`name`, `slug`, `description`, `post_type`, `sort_order`) VALUES
('فقه و اصول',         'fiqh-osul',         'مباحث فقه و اصول فقه',              'all',      1),
('تفسیر و قرآن',       'tafsir-quran',       'تفسیر آیات قرآن کریم',              'all',      2),
('فلسفه و کلام',       'falsafe-kalam',      'فلسفه اسلامی و علم کلام',           'all',      3),
('اخلاق و معرفت',      'akhlaq-marefat',     'اخلاق اسلامی و معرفت دینی',         'all',      4),
('اخبار مدرسه',        'akhbar-madrasa',     'اخبار و رویدادهای مدرسه',           'news',     5),
('مقالات علمی',        'maqalat-elmi',       'مقالات علمی و پژوهشی',              'article',  6),
('برنامه‌های تابستانه','baraname-tabestane', 'دوره‌های تابستانه',                 'program',  7),
('فعالیت‌های مذهبی',   'faaliyet-mazhabie',  'مراسم، محافل و فعالیت‌های مذهبی',  'religious',8);

-- ─── Migration ایمن: اضافه کردن ستون‌های جدید به جداول قدیمی ──────────────
-- اضافه کردن featured_video به posts اگر وجود ندارد
SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'posts' AND COLUMN_NAME = 'featured_video');
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE posts ADD COLUMN `featured_video` VARCHAR(500) DEFAULT NULL AFTER `featured_image`',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- اضافه کردن summary به lessons اگر وجود ندارد
SET @col_exists2 = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'lessons' AND COLUMN_NAME = 'summary');
SET @sql2 = IF(@col_exists2 = 0,
    'ALTER TABLE lessons ADD COLUMN `summary` TEXT DEFAULT NULL AFTER `content`',
    'SELECT 1');
PREPARE stmt2 FROM @sql2;
EXECUTE stmt2;
DEALLOCATE PREPARE stmt2;

-- اضافه کردن page_section به lessons اگر وجود ندارد
SET @col_exists3 = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'lessons' AND COLUMN_NAME = 'page_section');
SET @sql3 = IF(@col_exists3 = 0,
    "ALTER TABLE lessons ADD COLUMN `page_section` VARCHAR(255) NOT NULL DEFAULT 'home' AFTER `status`",
    'SELECT 1');
PREPARE stmt3 FROM @sql3;
EXECUTE stmt3;
DEALLOCATE PREPARE stmt3;

-- اضافه کردن ip_address به contact_messages اگر وجود ندارد
SET @col_exists4 = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'contact_messages' AND COLUMN_NAME = 'ip_address');
SET @sql4 = IF(@col_exists4 = 0,
    'ALTER TABLE contact_messages ADD COLUMN `ip_address` VARCHAR(45) DEFAULT NULL AFTER `message`',
    'SELECT 1');
PREPARE stmt4 FROM @sql4;
EXECUTE stmt4;
DEALLOCATE PREPARE stmt4;

-- اضافه کردن is_read به contact_messages اگر وجود ندارد
SET @col_exists5 = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'contact_messages' AND COLUMN_NAME = 'is_read');
SET @sql5 = IF(@col_exists5 = 0,
    'ALTER TABLE contact_messages ADD COLUMN `is_read` TINYINT(1) NOT NULL DEFAULT 0 AFTER `ip_address`',
    'SELECT 1');
PREPARE stmt5 FROM @sql5;
EXECUTE stmt5;
DEALLOCATE PREPARE stmt5;

SET FOREIGN_KEY_CHECKS = 1;
-- ============================================================
-- End of SQL v2.3 — Safe (no DROP TABLE)
-- ============================================================

-- ─── books ────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `books` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title`       VARCHAR(400) NOT NULL,
  `description` TEXT             NULL,
  `cover_image` VARCHAR(350)     NULL,
  `pdf_file`    VARCHAR(350)     NULL,
  `word_file`   VARCHAR(350)     NULL,
  `downloads`   INT UNSIGNED NOT NULL DEFAULT 0,
  `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
