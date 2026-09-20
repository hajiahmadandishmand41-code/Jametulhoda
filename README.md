# Deploy with Vercel

[![Deploy with Vercel](https://vercel.com/button)](https://vercel.com/new/clone?repository-url=https%3A%2F%2Fgithub.com%2Fhajiahmadandishmand41-code%2Fjametulhoda%2Ftree%2Farena%2F01a0bf9d-jametulhoda&env=DATABASE_URL,SITE_URL,APP_ENV,UPLOAD_STORAGE,UPLOAD_BASE_URL,S3_ENDPOINT,S3_REGION,S3_BUCKET,S3_ACCESS_KEY_ID,S3_SECRET_ACCESS_KEY)

> **وضعیت:** نسخه اصلاح‌شده در شاخه `arena/01a0bf9d-jametulhoda` است. GitHub CI شامل Docker build موفق شده و Vercel از اتصال GitHub یک Preview ساخته است. این Preview محافظت‌شده است و صحت runtime production هنوز تأیید نشده است. قبل از انتشار، [گزارش بررسی و محدودیت‌های باقی‌مانده](docs/AUDIT_FA.md) و [راهنمای استقرار](docs/DEPLOYMENT_FA.md) را بخوانید. دکمه بالا جایگزین تنظیم دیتابیس و storage نیست.

> **وضعیت همگام‌سازی:** اتصال GitHub برقرار شد و commitهای باقی‌مانده push شدند. اصلاح بازیابی آپلودها نیز با [CI موفق روی `ff3aafd`](https://github.com/hajiahmadandishmand41-code/jametulhoda/actions/runs/35526743172) تأیید شد. پیش از استفاده، migration جدید و برنامه زمان‌بندی پاک‌سازی را طبق راهنما اجرا کنید.

[مشاهده Preview واقعی Vercel — نیازمند ورود مجاز به Vercel](https://jametulhoda-git-arena-01a0bf9d-jametulhoda-eshop4.vercel.app)

# مدرسه علمیه جامعه‌الهدی

وب‌سایت فارسی و RTL با PHP، PostgreSQL، کتابخانه، دروس، اخبار، مقالات، صوت و ویدیو و پنل مدیریت. صفحات PHP موجود حفظ شده‌اند؛ یک front controller قابل‌اعتماد، ذخیره‌سازی مرکزی و هویت بصری مشترک به آن‌ها افزوده شده است.

## معماری

- **PHP 8.3+**؛ production با Apache داخل `Dockerfile.vercel`، بدون استفاده از PHP built-in server در production.
- **فقط PostgreSQL**؛ PDO با prepared statements بومی، `DATABASE_URL` و TLS تأییدشده برای Neon.
- **Session مشترک در PostgreSQL**؛ session محلی فقط برای آزمون/development.
- **Storage:** local در development، S3-compatible در production. آدرس عمومی با مسیر فیزیکی جداست.
- **رابط:** فونت Vazirmatn و Bootstrap RTL محلی، پوسته روشن/تاریک، منوی موبایل، بدون ابزار JS سنگین.
- **Routing:** `router.php` و allowlist در `config/routes.php`؛ مسیر PHP قدیمی معتبر است و aliasهای خوانا نیز وجود دارد.

## اجرای محلی

نیازمندی‌ها: PHP 8.3+ با `pdo_pgsql`, `mbstring`, `fileinfo`, `gd` (JPEG/WebP), `dom`, `curl`, `zip`؛ Composer 2؛ PostgreSQL 15+. `composer.lock` و `package-lock.json` نسخه وابستگی‌ها را قفل می‌کنند.

```sh
composer install --no-dev
# مقادیر واقعی را در محیط خود تنظیم کنید؛ فایل .env خودکار خوانده نمی‌شود.
export APP_ENV=development
export DATABASE_URL='postgresql://YOUR_USER:YOUR_PASSWORD@127.0.0.1:5432/YOUR_DATABASE?sslmode=disable'
export UPLOAD_STORAGE=local
export SESSION_DRIVER=database
php bin/migrate.php
php -S 0.0.0.0:8080 router.php
```

برای اجرای زیرمسیر، مثلاً `/school`، `BASE_PATH=/school` قرار دهید و تمام درخواست‌ها را به router بفرستید. دامنه در لینک‌های داخلی hard-code نمی‌شود. برای canonical و sitemap، `SITE_URL` را برابر URL کامل عمومی (شامل زیرمسیر در صورت وجود) قرار دهید.

**هیچ username/password پیش‌فرضی نصب نمی‌شود.** برای ایجاد نخستین مدیر، `ADMIN_USERNAME`، `ADMIN_PASSWORD` (حداقل ۱۴ کاراکتر) و در صورت نیاز `ADMIN_EMAIL` را در محیط محلی امن تنظیم کنید و اجرا کنید:

```sh
php bin/create-admin.php
unset ADMIN_PASSWORD
```

ورود کارکنان: `/admin/login.php`. مدیر ارشد در `/admin/users.php` حساب کارکنان، نقش و فعال‌بودن را مدیریت می‌کند. ویراستار به محتوا دسترسی دارد؛ تنظیمات، پیام‌ها و مدیریت رسانه برای مدیر/مدیر ارشد است. ثبت‌نام عمومی و پروفایل دانشجو هنوز پیاده‌سازی نشده‌اند؛ فرم تماس، مسیر درخواست پذیرش است، نه ثبت‌نام حساب.

## دیتابیس و داده‌های قبلی

`database.sql` schema مرجع PostgreSQL است. `php bin/migrate.php` غیرمخرب و قابل اجرای مجدد روی همین schema است. ایجاد/تغییر جدول در درخواست وب انجام نمی‌شود. `install.php`, `migrate-sections.php`, `db-test.php` فقط از CLI اجرا می‌شوند.

**این schema، داده‌های MySQL موجود را خودکار منتقل نمی‌کند.** پیش از انتقال داده، نسخه پشتیبان بگیرید، داده‌ها را در یک دیتابیس آزمایشی PostgreSQL import کنید، enum/زمان/encoding/کلیدها را تطبیق دهید، sequenceها را پس از حفظ IDها تنظیم کنید و شمار رکوردها و تمام فایل‌های قدیمی را مقایسه کنید. هیچ اتصال به دیتابیس واقعی قدیمی یا Neon در این بررسی انجام نشده است.

## متغیرهای محیطی

فهرست بدون secret در [`.env.example`](.env.example) است. شرح، مقادیر مناسب هر محیط، تنظیم TLS، bucket policy و محدودیت آپلود در [راهنمای Deployment](docs/DEPLOYMENT_FA.md) آمده است. `.env` را commit نکنید؛ secrets را فقط در dashboard میزبان یا secret manager نگهداری کنید.

## آزمون‌ها

آزمون‌های HTTP در دیتابیس **آزمایشی و قابل حذف** محتوا ایجاد می‌کنند؛ روی سایت واقعی اجرا نکنید.

```sh
find . -name '*.php' -not -path './vendor/*' -not -path './node_modules/*' -print0 | xargs -0 -n1 php -l
php tests/security.php
# فقط با DATABASE_URL آزمایشی، APP_ENV=development و UPLOAD_STORAGE=local:
ALLOW_DESTRUCTIVE_TESTS=1 php tests/storage-recovery.php
npm ci
# TEST_BASE_URL، TEST_ADMIN_USERNAME، TEST_ADMIN_PASSWORD را برای محیط آزمایش تنظیم کنید.
npm run test:http
npx playwright install chromium
npm run test:browser
node tests/links.mjs
```

نتایج و تصاویر آزمون‌ها در `test-results/` تولید می‌شوند و وارد Git نمی‌شوند. Workflow در `.github/workflows/ci.yml` برای lint، schema تکرارپذیر، HTTP، مرورگر، Docker build و اجرای آزمون‌ها روی Apache با PORT سفارشی فراهم شده است. وجود workflow به‌تنهایی به معنی موفق‌بودن CI نیست؛ وضعیت واقعی در گزارش ثبت می‌شود.

## مستندات

- [Audit و وضعیت واقعی تکمیل](docs/AUDIT_FA.md)
- [Vercel، Neon، محیط‌ها و Storage](docs/DEPLOYMENT_FA.md)
- [فهرست فایل‌ها و routeها](docs/INVENTORY.md)
- [فایل‌های تغییرکرده](docs/CHANGES.md)

فونت و کتابخانه‌های frontend با مجوز اصلی در `assets/fonts/` و `assets/vendor/` نگهداری شده‌اند. تاریخچه مخزن دارای credential قدیمی است؛ **حتماً آن را rotate کنید**، حتی اگر فایل فعلی پاک‌سازی شده باشد.
