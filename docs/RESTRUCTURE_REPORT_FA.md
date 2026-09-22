# گزارش بازسازی ساختار — Jametulhoda

> تاریخ: ۲۰۲۶-۰۹-۲۲ • شاخه: `arena/01a0c541-jametulhoda` • مبنای کار: `3ca7dff`
> نگاشت کامل فایل‌ها و مسیرها: [FILE_ROUTE_MAP.md](FILE_ROUTE_MAP.md) • فهرست موجودی: [INVENTORY.md](INVENTORY.md)

## ۱. خلاصه

ساختار پوشه‌ها به الگوی خواسته‌شده منتقل شد: ریشهٔ وب فقط `index.php`، `router.php`،
`robots.php`، `sitemap.php` و `.htaccess`؛ صفحات عمومی در `pages/`؛ اسکیم‌ها در `database/`؛
ابزار CLI در `bin/`؛ نصاب مرورگری در `php/install.php`؛ `storage/{logs,cache}`؛
`uploads/{images,books,videos,audios,documents}`؛ `assets/{css,js,img,fonts}`.
**هیچ قابلیت سالمی حذف نشد**؛ ۱۴۶ تغییر (۵۳ جابه‌جایی/تغییر نام فایل، بقیه اصلاح ارجاع و
hardening). مسیردهی در یک مرجع واحد (`config/routes.php`) یکپارچه شد و `.htaccess` و
`router.php` فقط از همان جدول استفاده می‌کنند.

**نتیجه آزمون: ۲۹۶ بررسی خودکار، همه سبز** (۲۴۱ مسیر/پنل/CRUD + ۱۴ آپلود و ذخیره‌سازی +
۳۱ امنیتی + ۱۰ بازیابی ذخیره‌سازی + lint سینتکس ۹۱ فایل + اجرای دوم migration)، بدون هیچ
PHP Warning/Fatal در لاگ سرور. جریان «سایت هنوز نصب نشده» هم آزمون شد: با نبودِ
`config/local.php` صفحهٔ راهنما با لینک `/php/install` نمایش داده می‌شود و خودِ
`/php/install` فرم نصب را با ۲۰۰ برمی‌گرداند.

## ۲. فایل‌های منتقل‌شده

| از | به | نکته |
|---|---|---|
| `about.php` … `topics.php` (۲۲ کنترل‌کننده عمومی) | `pages/*.php` | همه `require`ها به `__DIR__.'/../includes/…'` و `'/../config/…'` اصلاح شد |
| `includes/home-intro.php` | `content/home-intro.php` | require در `index.php` اصلاح شد |
| `admin/users.php` | `admin/users/index.php` | مثل بقیه بخش‌ها پوشه‌ای شد؛ `/admin/users.php` همچنان کار می‌کند |
| `admin/messages.php` | redirect به `admin/messages/` | حذف منطق تکراری؛ قابلیت «همه را خوانده علامت بزن» به `admin/messages/index.php` منتقل شد |
| `database.mysql.sql` | `database/database.mysql.sql` | مسیر در `bin/migrate.php` و `php/install.php` اصلاح شد |
| `database.sql` | `database/database.postgres.sql` | schema مرجع PostgreSQL (CI) حفظ شد |
| `update.sql` | `database/migrations/0001-legacy-update-note.sql` | پوشه `migrations/` ساخته شد |
| `install.php` (ریشه) | `bin/install-cli.php` | `/install.php` باید ۴۰۴ بماند (آزمون CI) |
| `db-test.php` (ریشه) | `bin/db-test.php` | require اصلاح شد |
| `migrate-sections.php` (ریشه) | `bin/migrate-sections.php` | require اصلاح شد |
| `assets/images/` | `assets/img/` | ۸ ارجاع PHP + `assets/js/main.js` اصلاح شد؛ alias `/assets/images/*` در router باقی است |
| `admin.htaccess`, `uploads.htaccess`, `admin/{categories,lessons,media,posts}.htaccess`, `uploads/{audio,books,video}.htaccess` | `.htaccess` واقعی در همان پوشه‌ها | این کپی‌ها به‌دلیل نداشتن نقطهٔ ابتدایی **بی‌اثر** بودند؛ محتوا ادغام شد |
| — (جدید) | `config/local.example.php` | الگوی نصب دستی |
| — (جدید) | `uploads/{images,books,videos,audios,documents}/`، `storage/{logs,cache}/`، `.htaccess` برای `config/ includes/ pages/ content/ database/ storage/ bin/ php/ tests/ docs/ admin/` | ساختار خواسته‌شده + مسدودسازی دسترسی وب |

## ۳. مسیرهای اصلاح‌شده

* **یکپارچه‌سازی:** `config/routes.php` اکنون `routes` + `aliases` + `patterns` را برمی‌گرداند و
  `router.php` هر نشانی اصلی را به سه شکل `/x`، `/x/`، `/x.php` (و `/x/index.php` برای پوشه‌ها)
  گسترش می‌دهد → ۲۲۵ نشانی به ۶۴ اسکریپت، بدون تکرار دستی.
* **مسیرهای تازه که قبلاً ۴۰۴ بودند:** `/videos`، `/audios`، `/admin/users/`،
  `/admin/users/index.php`، `/book/{slug}`، `/lessons/{collection}/{volume}`، `/search/{q}`،
  `/php/install/`، `/media-library`.
* **مسیرهای پویا (بدون ۴۰۴):** `/post/{slug}`، `/lesson/{slug}`، `/speech/{slug}`،
  `/topic/{slug}`، `/category/{slug}`، `/book/{id}`، `/book/{slug}`، `/lessons/{collection}` +
  همه با query string (`?page=`, `?q=`, `?kind=`, `?download=pdf`).
* **مسیرهای لازم (همه ۲۰۰):** `/php/install`، `/php/install.php`، `/admin`، `/admin/login`،
  `/admin/logout`، `/admin/change-password`، `/admin/users`، `/admin/articles`، `/admin/books`،
  `/admin/lessons`، `/admin/media`، `/admin/categories`، `/admin/topics`، `/admin/settings` و
  صفحات عمومی `/`، `/about`، `/contact`، `/articles`، `/books`، `/lessons`، `/videos`،
  `/audios`، `/search`، `/sitemap.xml`، `/robots.txt`.
* **پیوندهای داخلی:** ۲۹۷ نشانی در ۵۳ فایل از `siteUrl('about.php')` به `siteUrl('about')`
  و شکل تمیز معادل تبدیل شد (منوها، فوتر، breadcrumb، صفحه‌بندی، فرم‌ها، دکمه‌های پنل،
  ریدایرکت‌های `requireLogin`/`logoutAdmin`). شکل‌های `.php` به‌عنوان alias کار می‌کنند، پس
  بوک‌مارک‌ها و لینک‌های قدیمی نمی‌شکنند.
* **canonical:** `includes/header.php` به‌جای `SCRIPT_NAME` (که اکنون `pages/about.php` است) از
  `JHD_ROUTE_PATH` (نشانی عمومی) استفاده می‌کند؛ ساختار داخلی در HTML نشت نمی‌کند.
* **sitemap.xml:** نشانی‌های تمیز (`topic/{slug}`، `post/{slug}`، `lesson/{slug}`،
  `book/{slug|id}`، `lessons/{collection}`، `category/{slug}`) + `/videos` و `/audios`.
* **static:** `/assets/*` و `/uploads/*` با ETag، Cache-Control، Range (۲۰۶) و
  `Content-Disposition: attachment` برای PDF/DOC؛ در Apache فایل‌های استاتیک موجود **بدون
  اجرای PHP** سرو می‌شوند (`RewriteCond %{REQUEST_FILENAME} -f`).

## ۴. حساب مدیر

* پیش‌فرض: **`admin` / `JH@2026#Admin`** (`DEFAULT_ADMIN_USERNAME` / `DEFAULT_ADMIN_PASSWORD`
  در `config/config.php`، قابل override با env).
* ذخیره **فقط** با `password_hash()`؛ متن ساده هرگز در دیتابیس، `config/local.php` یا لاگ نمی‌نشیند.
* اگر حساب وجود داشته باشد: `php/install.php` و `bin/create-admin.php` رمز را بازنشانی،
  حساب را فعال و نقش را `superadmin` می‌کنند و با `auth_version + 1` **همه نشست‌های فعال را
  باطل** می‌کنند (هر دو حالت «ایجاد» و «بازنشانی» به‌صورت واقعی آزمون شد).
* در فرم نصاب، خالی‌گذاشتن فیلد رمز = استفاده از رمز پیش‌فرض مستند؛ پیام موفقیت این را
  یادآوری می‌کند و توصیه می‌کند پس از اولین ورود از `/admin/change-password` عوض شود.

## ۵. محافظت از فایل‌های حساس

* `config/local.php` و `config/install.lock`: در `.gitignore`، خارج از allowlist مسیرها
  (→ ۴۰۴) و `config/.htaccess` با `Require all denied` (+ fallback برای Apache 2.2).
  آزمون شد: `/config/local.php`، `/config/install.lock`، `/config/config.php`،
  `/config/routes.php`، `/config/local.example.php` → **۴۰۴**.
* همین محافظت برای `includes/`, `pages/`, `content/`, `database/`, `storage/`, `bin/`, `php/`,
  `tests/`, `docs/`, `admin/includes/` و فایل‌های `*.sql|lock|log|ini|md|json|yml|env` در ریشه.
* `uploads/`: اجرای اسکریپت ممنوع (`Options -Indexes -ExecCGI` + FilesMatch)، و router فقط
  پسوندهای مجاز را سرو می‌کند. آزمون شد: `/uploads/test.php`، `/uploads/images/test.php`،
  `/uploads/audios/../../config/local.php` → **۴۰۴**.

## ۶. خطاهایی که هنگام بازسازی پیدا و رفع شد

| خطا | علت | رفع |
|---|---|---|
| ۵۰۳ در `/search?q=ا` و هر فهرست با query فارسی و بیش از یک صفحه | `paginate()` با `sprintf()` روی الگویی که مقدار percent-encoded داشت (`%D8%A7` → «Unknown format specifier D») → `ValueError` | جای‌گذاری فقط placeholder صفحه (`str_replace('%d', …)`) با fallback امن |
| ۵۰۳ + PHP Warning در `POST /admin/topics` | `$_POST['parent_id']` بدون `??` خوانده می‌شد و `parent_id=0` کلید خارجی را نقض می‌کرد | `(int)($_POST['parent_id'] ?? 0) > 0 ? … : null` |
| فایل‌های `.htaccess` بی‌اثر | با نام‌های `admin.htaccess`، `uploads.htaccess`، `admin/posts.htaccess` و… (بدون نقطهٔ ابتدایی) کامیت شده بودند | تبدیل به `.htaccess` واقعی در همان پوشه‌ها و ادغام محتوا |
| دو صفحهٔ موازی پیام‌ها | `admin/messages.php` و `admin/messages/index.php` | یکی‌سازی؛ نشانی قدیمی redirect است و قابلیت «علامت همه» حفظ شد |
| نشت مسیر داخلی در canonical | `SCRIPT_NAME` پس از انتقال به `pages/` مقدار `pages/about.php` می‌گرفت | افزودن `JHD_ROUTE_PATH` و استفاده از آن در canonical |
| نبود `/videos` و `/audios` | فقط `videos.php`/`audios.php` در allowlist بود | افزودن نشانی‌های تمیز با `kind` پیش‌فرض (بدون حذف مقدار صریح query) |

## ۷. خطاها و محدودیت‌های باقی‌مانده (صادقانه)

1. **MySQL به‌صورت واقعی اجرا نشد.** در این محیط هیچ سرور MySQL/MariaDB قابل نصب نبود
   (همه mirrorها و CDNها مسدود). آزمون‌ها روی PostgreSQL 16 با درایور `pgsql` خود پروژه انجام شد.
   `database/database.mysql.sql` تغییر محتوایی نداشت (فقط جابه‌جا شد) و با schema
   PostgreSQL مقایسه شد: **همان ۲۲ جدول**، ۲۸ ایندکس، idempotent. نصب روی InfinityFree
   باید یک بار روی خود میزبان تأیید نهایی شود.
2. **آپلود multipart از طریق HTTP در این محیط آزمون نشد** — محدودیت runtime محلی PHP در
   sandbox (تجزیهٔ `multipart/form-data` در `$_POST`)، نه مشکل برنامه. در عوض کل زنجیرهٔ
   ذخیره‌سازی مستقیماً آزمون شد: `storeValidatedFile()` برای image/audio/video/pdf، قرارگیری
   صحیح در `uploads/{posts,images,book-covers,audios,videos,books,documents}`، سرو HTTP هر
   هفت فایل (۲۰۰ + content-type درست + Range/206 + attachment برای PDF)، مسدودبودن
   `/uploads/.htaccess` و `/uploads/*/.htaccess`، و اجرای `tests/storage-recovery.php`
   (۱۰ بررسی: journal، rollback، grace period، حذف فایل همراه با محتوا).
   تست‌های multipart واقعی در CI (`tests/http.mjs` با Playwright) اجرا می‌شوند.
3. **`tests/browser.mjs` اجرا نشد** (نیاز به دانلود Chromium؛ در این محیط مسدود). در CI اجرا می‌شود.
4. **رفتار واقعی `.htaccess` روی Apache آزمون نشد** (سرور Apache در دسترس نبود). همهٔ
   دستورهای استفاده‌شده استاندارد و با `<IfModule>` محافظت‌شده‌اند (`mod_rewrite`،
   `mod_authz_core` با fallback `Order/Deny` برای Apache 2.2، `mod_headers`) و flag `[END]`
   که از قبل در پروژه بود حفظ شد.
5. **`/admin/topics/create` و `/admin/topics/edit` ریدایرکت (۳۰۲) به `/admin/topics/` هستند** —
   ایجاد/ویرایش موضوع به‌صورت inline در همان صفحه است (طرح قبلی، بدون تغییر نگه داشته شد).
6. **`storage/cache/` ساخته شد ولی هنوز مصرف‌کننده ندارد** (لایهٔ cache در پروژه پیاده نشده)؛
   `storage/logs/php-error.log` فقط در `APP_ENV=production` و در صورت writable بودن استفاده
   می‌شود تا در development/CI هشدارها در stderr دیده شوند.
7. **رمز پیش‌فرض مدیر در مخزن مستند است** — این خواستهٔ صریح کارفرماست؛ حتماً پس از اولین
   ورود تغییر داده شود (`/admin/change-password`).
8. `admin/lesson-collections/` و `admin/banners/` فرم inline دارند (بدون `create.php`) — دست نخورد.

## ۸. چطور دوباره آزمون کنیم

```sh
# ۱) lint همه فایل‌های PHP
find . -name '*.php' -not -path './vendor/*' -not -path './node_modules/*' -print0 | xargs -0 -n1 php -l
# ۲) آزمون‌های واحد/امنیتی و migration تکرارپذیر
php tests/security.php && php bin/migrate.php && php bin/migrate.php
ALLOW_DESTRUCTIVE_TESTS=1 php tests/storage-recovery.php
# ۳) ساخت مدیر (پیش‌فرض admin / JH@2026#Admin یا با env دلخواه)
php bin/create-admin.php
# ۴) سرور محلی و آزمون‌های HTTP/مرورگر/لینک‌ها
php -S 0.0.0.0:8080 router.php &
npm ci && npm run test:http && npm run test:browser && node tests/links.mjs
```
