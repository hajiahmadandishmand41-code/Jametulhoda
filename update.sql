-- ============================================================
-- update.sql — Migration اضافه کردن ستون featured_video
-- ایمن: فقط ALTER TABLE — بدون DROP یا حذف اطلاعات
-- ============================================================
-- اجرا کنید از طریق phpMyAdmin → SQL tab
-- اگر ستون قبلاً وجود دارد، این اسکریپت بدون خطا از آن رد می‌شود.
-- ============================================================

-- اضافه کردن featured_video به جدول posts
SET @col_exists_fv = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'posts'
      AND COLUMN_NAME  = 'featured_video'
);
SET @sql_fv = IF(
    @col_exists_fv = 0,
    'ALTER TABLE `posts` ADD COLUMN `featured_video` VARCHAR(500) DEFAULT NULL AFTER `featured_image`',
    'SELECT 1 /* featured_video already exists */'
);
PREPARE stmt_fv FROM @sql_fv;
EXECUTE stmt_fv;
DEALLOCATE PREPARE stmt_fv;

-- اضافه کردن summary به جدول lessons (اگر وجود نداشت)
SET @col_exists_ls = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'lessons'
      AND COLUMN_NAME  = 'summary'
);
SET @sql_ls = IF(
    @col_exists_ls = 0,
    'ALTER TABLE `lessons` ADD COLUMN `summary` TEXT DEFAULT NULL AFTER `content`',
    'SELECT 1 /* summary already exists */'
);
PREPARE stmt_ls FROM @sql_ls;
EXECUTE stmt_ls;
DEALLOCATE PREPARE stmt_ls;

-- اضافه کردن page_section به جدول lessons (اگر وجود نداشت)
SET @col_exists_ps = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'lessons'
      AND COLUMN_NAME  = 'page_section'
);
SET @sql_ps = IF(
    @col_exists_ps = 0,
    "ALTER TABLE `lessons` ADD COLUMN `page_section` VARCHAR(255) NOT NULL DEFAULT 'home' AFTER `status`",
    'SELECT 1 /* page_section already exists */'
);
PREPARE stmt_ps FROM @sql_ps;
EXECUTE stmt_ps;
DEALLOCATE PREPARE stmt_ps;

-- اضافه کردن ip_address به contact_messages (اگر وجود نداشت)
SET @col_exists_ip = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'contact_messages'
      AND COLUMN_NAME  = 'ip_address'
);
SET @sql_ip = IF(
    @col_exists_ip = 0,
    'ALTER TABLE `contact_messages` ADD COLUMN `ip_address` VARCHAR(45) DEFAULT NULL AFTER `message`',
    'SELECT 1 /* ip_address already exists */'
);
PREPARE stmt_ip FROM @sql_ip;
EXECUTE stmt_ip;
DEALLOCATE PREPARE stmt_ip;

-- اضافه کردن is_read به contact_messages (اگر وجود نداشت)
SET @col_exists_ir = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'contact_messages'
      AND COLUMN_NAME  = 'is_read'
);
SET @sql_ir = IF(
    @col_exists_ir = 0,
    'ALTER TABLE `contact_messages` ADD COLUMN `is_read` TINYINT(1) NOT NULL DEFAULT 0',
    'SELECT 1 /* is_read already exists */'
);
PREPARE stmt_ir FROM @sql_ir;
EXECUTE stmt_ir;
DEALLOCATE PREPARE stmt_ir;

-- ساخت جدول post_likes (اگر وجود نداشت)
CREATE TABLE IF NOT EXISTS `post_likes` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `post_id`    INT UNSIGNED NOT NULL,
  `ip_hash`    VARCHAR(64)  NOT NULL,
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_like` (`post_id`, `ip_hash`),
  KEY `idx_post` (`post_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ساخت جدول media_files (اگر وجود نداشت)
CREATE TABLE IF NOT EXISTS `media_files` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ref_type`   VARCHAR(30)  NOT NULL DEFAULT 'post',
  `ref_id`     INT UNSIGNED NOT NULL,
  `kind`       ENUM('image','video','audio','document') NOT NULL DEFAULT 'image',
  `file_path`  VARCHAR(500) NOT NULL,
  `title`      VARCHAR(300) DEFAULT NULL,
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ref`  (`ref_type`, `ref_id`),
  KEY `idx_kind` (`kind`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- پایان Migration — اطلاعات موجود دست‌نخورده است
-- ============================================================

-- v3.0: جدول کتاب‌ها
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

-- ============================================================
-- v4.0: اضافه کردن ستون speaker به posts (سخنرانی‌ها)
-- ============================================================
SET @col_exists_spk = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'posts' AND COLUMN_NAME = 'speaker'
);
SET @sql_spk = IF(@col_exists_spk = 0,
    'ALTER TABLE `posts` ADD COLUMN `speaker` VARCHAR(200) DEFAULT NULL AFTER `summary`',
    'SELECT 1 /* speaker already exists */');
PREPARE stmt_spk FROM @sql_spk; EXECUTE stmt_spk; DEALLOCATE PREPARE stmt_spk;

-- اضافه کردن video_file به جدول lessons
SET @col_exists_lv = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'lessons' AND COLUMN_NAME = 'video_file'
);
SET @sql_lv = IF(@col_exists_lv = 0,
    'ALTER TABLE `lessons` ADD COLUMN `video_file` VARCHAR(500) DEFAULT NULL',
    'SELECT 1 /* video_file already exists */');
PREPARE stmt_lv FROM @sql_lv; EXECUTE stmt_lv; DEALLOCATE PREPARE stmt_lv;

-- اضافه کردن pdf_file به جدول lessons
SET @col_exists_lpf = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'lessons' AND COLUMN_NAME = 'pdf_file'
);
SET @sql_lpf = IF(@col_exists_lpf = 0,
    'ALTER TABLE `lessons` ADD COLUMN `pdf_file` VARCHAR(500) DEFAULT NULL',
    'SELECT 1 /* pdf_file already exists */');
PREPARE stmt_lpf FROM @sql_lpf; EXECUTE stmt_lpf; DEALLOCATE PREPARE stmt_lpf;

-- ============================================================
-- پایان v4.0 — همه تغییرات ایمن (بدون DROP، بدون حذف داده)
-- ============================================================
