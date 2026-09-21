-- MySQL 8+/MariaDB schema for InfinityFree. The PostgreSQL schema remains in database.sql.
-- JametulHoda Content-Centered Architecture v2 — topics, lesson collections, enhanced books

BEGIN;

CREATE TABLE IF NOT EXISTS users (
  id         INT AUTO_INCREMENT,
  username   VARCHAR(80)      NOT NULL,
  email      VARCHAR(180)     NOT NULL DEFAULT '',
  password   VARCHAR(255)     NOT NULL,
  full_name  VARCHAR(120)     NOT NULL DEFAULT '',
  role       VARCHAR(30) NOT NULL DEFAULT 'admin' CHECK (role IN ('superadmin','admin','editor')),
  is_active  SMALLINT       NOT NULL DEFAULT 1,
  last_login DATETIME             NULL,
  created_at DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS categories (
  id          INT AUTO_INCREMENT,
  name        VARCHAR(120) NOT NULL,
  slug        VARCHAR(160) NOT NULL,
  description TEXT             NULL,
  post_type   VARCHAR(30)  NOT NULL DEFAULT 'all',
  sort_order  INT          NOT NULL DEFAULT 0,
  created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ── Topics: hierarchical, dynamic, core of site ──
CREATE TABLE IF NOT EXISTS topics (
  id          INT AUTO_INCREMENT,
  parent_id   INTEGER REFERENCES topics(id) ON DELETE SET NULL,
  name        VARCHAR(150) NOT NULL,
  slug        VARCHAR(200) NOT NULL,
  description TEXT,
  intro       TEXT,
  cover_image VARCHAR(500),
  sort_order  INT NOT NULL DEFAULT 0,
  is_active   SMALLINT NOT NULL DEFAULT 1,
  is_featured SMALLINT NOT NULL DEFAULT 0,
  created_at  DATETIME NOT NULL DEFAULT NOW(),
  updated_at  DATETIME NOT NULL DEFAULT NOW(),
  PRIMARY KEY (id),
  UNIQUE (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX topics_parent_idx ON topics(parent_id);
CREATE INDEX topics_active_sort ON topics(is_active, sort_order, id);
CREATE INDEX topics_featured ON topics(is_featured) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS posts (
  id              INT AUTO_INCREMENT,
  title           VARCHAR(400)     NOT NULL,
  slug            VARCHAR(450)     NOT NULL,
  summary         TEXT                 NULL,
  content         TEXT             NULL,
  featured_image  VARCHAR(350)         NULL,
  featured_video  VARCHAR(500)         NULL,
  post_type       VARCHAR(30) NOT NULL DEFAULT 'news' CHECK (post_type IN ('news','article','research','report','announcement','speech','program','religious','qa','book_note')),
  page_section       VARCHAR(300) NOT NULL DEFAULT 'home,news',
  speaker           VARCHAR(200) NULL,
  sources           TEXT NULL,
  author_name       VARCHAR(250) NULL,
  category_id     INTEGER REFERENCES categories(id) ON DELETE SET NULL,
  author_id       INTEGER REFERENCES users(id) ON DELETE SET NULL,
  status          VARCHAR(30) NOT NULL DEFAULT 'draft' CHECK (status IN ('published','draft')),
  is_featured     SMALLINT       NOT NULL DEFAULT 0,
  published_at    DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  created_at      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX posts_idx_status_type ON posts (status, post_type);
CREATE INDEX posts_idx_category ON posts (category_id);
CREATE INDEX posts_idx_published ON posts (published_at) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS post_images (
  id         INT AUTO_INCREMENT,
  post_id    INTEGER NOT NULL REFERENCES posts(id) ON DELETE CASCADE,
  image_path VARCHAR(350) NOT NULL,
  alt_text   VARCHAR(200)     NULL,
  created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX post_images_idx_post ON post_images (post_id) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Junction: posts <-> topics (many-to-many)
CREATE TABLE IF NOT EXISTS post_topics (
  post_id  INTEGER NOT NULL REFERENCES posts(id) ON DELETE CASCADE,
  topic_id INTEGER NOT NULL REFERENCES topics(id) ON DELETE CASCADE,
  PRIMARY KEY (post_id, topic_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX post_topics_topic_idx ON post_topics(topic_id) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Lesson collections & volumes (content-centered)
CREATE TABLE IF NOT EXISTS lesson_collections (
  id          INT AUTO_INCREMENT,
  title       VARCHAR(300) NOT NULL,
  slug        VARCHAR(300) NOT NULL,
  description TEXT,
  cover_image VARCHAR(500),
  sort_order  INT NOT NULL DEFAULT 0,
  is_active   SMALLINT NOT NULL DEFAULT 1,
  is_featured SMALLINT NOT NULL DEFAULT 0,
  created_at  DATETIME NOT NULL DEFAULT NOW(),
  updated_at  DATETIME NOT NULL DEFAULT NOW(),
  PRIMARY KEY (id),
  UNIQUE (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX lesson_collections_active_sort ON lesson_collections(is_active, sort_order) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS lesson_volumes (
  id            INT AUTO_INCREMENT,
  collection_id INTEGER NOT NULL REFERENCES lesson_collections(id) ON DELETE CASCADE,
  title         VARCHAR(300) NOT NULL,
  slug          VARCHAR(300) NOT NULL,
  description   TEXT,
  sort_order    INT NOT NULL DEFAULT 0,
  created_at    DATETIME NOT NULL DEFAULT NOW(),
  updated_at    DATETIME NOT NULL DEFAULT NOW(),
  PRIMARY KEY (id),
  UNIQUE (collection_id, slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX lesson_volumes_collection_sort ON lesson_volumes(collection_id, sort_order) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS lessons (
  id             INT AUTO_INCREMENT,
  title          VARCHAR(400) NOT NULL,
  slug           VARCHAR(450) NOT NULL,
  subject        VARCHAR(150)     NULL,
  teacher        VARCHAR(150)     NULL,
  content        TEXT         NULL,
  summary        TEXT             NULL,
  audio_file     VARCHAR(350)     NULL,
  video_file     TEXT,
  pdf_file       TEXT,
  sources        TEXT,
  featured_image VARCHAR(350)     NULL,
  level          VARCHAR(30) DEFAULT 'beginner',
  status         VARCHAR(30) NOT NULL DEFAULT 'draft' CHECK (status IN ('published','draft')),
  page_section   VARCHAR(255) NOT NULL DEFAULT 'home',
  sort_order     INT          NOT NULL DEFAULT 0,
  lesson_number  INT,
  collection_id  INTEGER REFERENCES lesson_collections(id) ON DELETE SET NULL,
  volume_id      INTEGER REFERENCES lesson_volumes(id) ON DELETE SET NULL,
  is_featured    SMALLINT NOT NULL DEFAULT 0,
  created_by     INTEGER     NULL,
  created_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX lessons_idx_status ON lessons (status);
CREATE INDEX lessons_idx_collection ON lessons(collection_id, volume_id, lesson_number);
CREATE INDEX lessons_featured ON lessons(is_featured) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Junction: lessons <-> topics
CREATE TABLE IF NOT EXISTS lesson_topics (
  lesson_id INTEGER NOT NULL REFERENCES lessons(id) ON DELETE CASCADE,
  topic_id  INTEGER NOT NULL REFERENCES topics(id) ON DELETE CASCADE,
  PRIMARY KEY (lesson_id, topic_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX lesson_topics_topic_idx ON lesson_topics(topic_id) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS contact_messages (
  id         INT AUTO_INCREMENT,
  name       VARCHAR(200) NOT NULL,
  email      VARCHAR(200)     NULL,
  phone      VARCHAR(50)      NULL,
  subject    VARCHAR(300)     NULL,
  message    TEXT         NOT NULL,
  ip_address VARCHAR(45)      NULL,
  is_read    SMALLINT   NOT NULL DEFAULT 0,
  created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX contact_messages_idx_is_read ON contact_messages (is_read);
CREATE INDEX contact_messages_idx_created ON contact_messages (created_at) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS media_files (
  id         INT AUTO_INCREMENT,
  ref_type   VARCHAR(30)  NOT NULL DEFAULT 'post',
  ref_id     INTEGER NOT NULL,
  kind       VARCHAR(30) NOT NULL DEFAULT 'image',
  file_path  VARCHAR(500) NOT NULL,
  title      VARCHAR(300)     NULL,
  created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX media_files_idx_ref ON media_files (ref_type, ref_id);
CREATE INDEX media_files_idx_kind ON media_files (kind) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS settings (
  id         INT AUTO_INCREMENT,
  key        VARCHAR(100) NOT NULL,
  value      TEXT             NULL,
  created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE (key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS books (
  id          INT AUTO_INCREMENT,
  title       VARCHAR(400) NOT NULL,
  slug        VARCHAR(300) NULL,
  description TEXT             NULL,
  cover_image VARCHAR(350)     NULL,
  pdf_file    VARCHAR(350)     NULL,
  word_file   VARCHAR(350)     NULL,
  author      VARCHAR(250),
  translator  VARCHAR(250),
  publisher   VARCHAR(250),
  publish_year VARCHAR(20),
  pages       INTEGER,
  toc         TEXT,
  is_featured SMALLINT NOT NULL DEFAULT 0,
  sort_order  INT NOT NULL DEFAULT 0,
  status      VARCHAR(30) NOT NULL DEFAULT 'published' CHECK (status IN ('published','draft')),
  downloads   INTEGER NOT NULL DEFAULT 0,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX books_idx_created ON books (created_at);
CREATE INDEX books_idx_featured ON books(is_featured) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Junction: books <-> topics
CREATE TABLE IF NOT EXISTS book_topics (
  book_id  INTEGER NOT NULL REFERENCES books(id) ON DELETE CASCADE,
  topic_id INTEGER NOT NULL REFERENCES topics(id) ON DELETE CASCADE,
  PRIMARY KEY (book_id, topic_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX book_topics_topic_idx ON book_topics(topic_id) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Site banners / special announcements (dynamic hero banner)
CREATE TABLE IF NOT EXISTS site_banners (
  id         INT AUTO_INCREMENT,
  title      VARCHAR(300) NOT NULL,
  content    TEXT,
  link_url   VARCHAR(500),
  link_text  VARCHAR(100),
  banner_type VARCHAR(30) NOT NULL DEFAULT 'info',
  is_active  SMALLINT NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 0,
  starts_at  DATETIME,
  ends_at    DATETIME,
  created_at DATETIME NOT NULL DEFAULT NOW(),
  updated_at DATETIME NOT NULL DEFAULT NOW(),
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX site_banners_active ON site_banners(is_active, sort_order) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- MySQL fresh-install schema: legacy cleanup/backfill is folded into the definitions below.


-- Ensure book slug exists for SEO friendly URLs
CREATE UNIQUE INDEX books_slug_unique ON books(slug);
-- Featured banners (dynamic special announcement)
CREATE TABLE IF NOT EXISTS featured_banners (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(300) NOT NULL,
  description TEXT,
  image VARCHAR(500),
  link_url VARCHAR(500),
  button_text VARCHAR(100),
  is_active SMALLINT NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT NOW(),
  updated_at DATETIME NOT NULL DEFAULT NOW()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


INSERT IGNORE INTO settings (key, value) VALUES
('site_name',        'مدرسه علمیه جامعه‌الهدی'),
('site_slogan',      'علم، معرفت و تهذیب در پرتو قرآن و عترت'),
('site_email',       'hajiahmads299@gmail.com'),
('address',          'کابل، افغانستان'),
('phone',            '0798228441'),
('email',            'hajiahmads299@gmail.com'),
('social_telegram',  ''),
('social_youtube',   ''),
('social_instagram', '') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO categories (name, slug, description, post_type, sort_order) VALUES
('فقه و اصول',         'fiqh-osul',         'مباحث فقه و اصول فقه',              'all',      1),
('تفسیر و قرآن',       'tafsir-quran',       'تفسیر آیات قرآن کریم',              'all',      2),
('فلسفه و کلام',       'falsafe-kalam',      'فلسفه اسلامی و علم کلام',           'all',      3),
('اخلاق و معرفت',      'akhlaq-marefat',     'اخلاق اسلامی و معرفت دینی',         'all',      4),
('اخبار مدرسه',        'akhbar-madrasa',     'اخبار و رویدادهای مدرسه',           'news',     5),
('مقالات علمی',        'maqalat-elmi',       'مقالات علمی و پژوهشی',              'article',  6),
('برنامه‌های تابستانه','baraname-tabestane', 'دوره‌های تابستانه',                 'program',  7),
('فعالیت‌های مذهبی',   'faaliyet-mazhabie',  'مراسم، محافل و فعالیت‌های مذهبی',  'religious',8) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed core topics as content backbone (migrated / canonical)
INSERT IGNORE INTO topics (name, slug, description, intro, sort_order, is_featured) VALUES
('مهدویت', 'mahdaviat', 'مباحث مرتبط با مهدویت و امام زمان (عج)', 'بررسی عقیده مهدویت، انتظار و ظهور در منابع اسلامی', 1, 1),
('امام حسین (ع)', 'imam-hussein', 'زندگی، قیام و پیام‌های عاشورا', 'شناخت نهضت حسینی و درس‌های عاشورا برای جامعه امروز', 2, 1),
('قرآن و حدیث', 'quran-hadith', 'علوم قرآن، تفسیر، حدیث و نهج‌البلاغه', 'آشنایی با معارف قرآنی و روایی اهل بیت', 3, 1),
('فقه و اصول', 'fiqh-osool', 'احکام، فقه استدلالی و اصول فقه', 'مبانی اجتهاد و استنباط احکام شرعی', 4, 0),
('اخلاق و تربیت', 'akhlaq-tarbiat', 'اخلاق اسلامی و سیر و سلوک', 'تهذیب نفس و پرورش معنوی بر اساس قرآن و عترت', 5, 0),
('تاریخ اسلام', 'tarikh-islam', 'سیره معصومین و تاریخ تشیع', 'بررسی تاریخی اسلام و نقش اهل بیت', 6, 0)
ON CONFLICT (slug) DO NOTHING;

-- Subtopics examples
INSERT IGNORE INTO topics (parent_id, name, slug, description, sort_order) VALUES
((SELECT id FROM topics WHERE slug='mahdaviat' LIMIT 1), 'امام مهدی (عج)', 'imam-mahdi', 'شناخت حضرت مهدی (عج)', 1),
((SELECT id FROM topics WHERE slug='mahdaviat' LIMIT 1), 'غیبت', 'ghaybat', 'غیبت صغری و کبری', 2),
((SELECT id FROM topics WHERE slug='mahdaviat' LIMIT 1), 'ظهور', 'zohour', 'نشانه‌ها و شرایط ظهور', 3),
((SELECT id FROM topics WHERE slug='mahdaviat' LIMIT 1), 'انتظار', 'entezar', 'وظایف منتظران', 4),
((SELECT id FROM topics WHERE slug='imam-hussein' LIMIT 1), 'زندگی امام حسین (ع)', 'zendegi-imam-hussein', 'دوران زندگی و امامت', 1),
((SELECT id FROM topics WHERE slug='imam-hussein' LIMIT 1), 'قیام عاشورا', 'qiyam-ashura', 'تحلیل قیام عاشورا', 2),
((SELECT id FROM topics WHERE slug='imam-hussein' LIMIT 1), 'پیام‌های عاشورا', 'payam-ashura', 'درس‌ها و عبرت‌های عاشورا', 3),
((SELECT id FROM topics WHERE slug='quran-hadith' LIMIT 1), 'علوم قرآن', 'oloum-quran', 'مبانی علوم قرآنی', 1),
((SELECT id FROM topics WHERE slug='quran-hadith' LIMIT 1), 'تفسیر', 'tafsir', 'تفسیر آیات قرآن', 2),
((SELECT id FROM topics WHERE slug='quran-hadith' LIMIT 1), 'نهج‌البلاغه', 'nahjolbalaghe', 'شرح نهج‌البلاغه', 3)
ON CONFLICT (slug) DO NOTHING;

CREATE TABLE IF NOT EXISTS app_sessions (
 id VARCHAR(128) PRIMARY KEY, data TEXT NOT NULL, expires_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX app_sessions_expiry ON app_sessions(expires_at);
CREATE TABLE IF NOT EXISTS login_limits (
 key VARCHAR(64) PRIMARY KEY, attempts INTEGER NOT NULL DEFAULT 0, expires_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS stored_files (
 id INT AUTO_INCREMENT PRIMARY KEY,
 file_key TEXT NOT NULL UNIQUE, url TEXT NOT NULL UNIQUE, mime TEXT NOT NULL,
 size BIGINT NOT NULL, created_at DATETIME NOT NULL DEFAULT NOW()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX contact_rate_limit ON contact_messages(ip_address,created_at);
CREATE TABLE IF NOT EXISTS storage_deletions (reference TEXT PRIMARY KEY, created_at DATETIME NOT NULL DEFAULT NOW());
ALTER TABLE storage_deletions ADD COLUMN IF NOT EXISTS not_before DATETIME NOT NULL DEFAULT NOW();
CREATE TABLE IF NOT EXISTS pending_uploads (
 reference TEXT PRIMARY KEY,
 not_before DATETIME NOT NULL DEFAULT (NOW()+INTERVAL '24 hours'),
 created_at DATETIME NOT NULL DEFAULT NOW()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX pending_uploads_due ON pending_uploads(not_before);
CREATE INDEX posts_public_listing ON posts(status,post_type,published_at DESC);
CREATE INDEX lessons_public_listing ON lessons(status,created_at DESC);
CREATE INDEX media_ordered_reference ON media_files(ref_type,ref_id,kind,sort_order,id);
COMMIT;
