# فایل‌های تغییرکرده

فهرست فایل‌های این مرحله، نسبت به commit اولیه. شرح‌های گروهی به معنی آزموده‌شدن تک‌تک قابلیت‌ها نیست؛ نتیجه واقعی تست در Audit آمده است.

| فایل | تغییر |
|---|---|
| `.dockerignore` | عدم کپی secrets، Git، uploadها و ابزار تست به image. |
| `.env.example` | حذف credential و قرار دادن فقط نام envها. |
| `.github/workflows/ci.yml` | Workflow بررسی PHP/PostgreSQL، HTTP/browser و Docker build. |
| `.gitignore` | نادیده‌گرفتن env، فایل آپلود، vendor runtime، نتایج تست و logs. |
| `.htaccess` | ارسال تمام درخواست‌ها به router؛ بدون directory listing. |
| `Dockerfile.vercel` | PHP 8.3 Apache، extensions، Composer، پورت و document root. |
| `INSTALL_GUIDE_FA.md` | مستندات وضعیت واقعی، نصب، inventory و محدودیت‌ها؛ پاک‌سازی راهنمای قدیمی. |
| `MERGE_NOTES.md` | مستندات وضعیت واقعی، نصب، inventory و محدودیت‌ها؛ پاک‌سازی راهنمای قدیمی. |
| `README.md` | مستندات وضعیت واقعی، نصب، inventory و محدودیت‌ها؛ پاک‌سازی راهنمای قدیمی. |
| `about.php` | سازگاری URL/SQL، خروجی متن امن، metadata رسانه یا اصلاح landmark در صفحه موجود. |
| `admin/articles/create.php` | SQL PostgreSQL، RETURNING، مسیر storage، خطای امن، action POST یا assets/پوسته مشترک بر حسب فرم. |
| `admin/articles/delete.php` | حذف مشترک تراکنشی، outbox فایل و POST-CSRF. |
| `admin/articles/edit.php` | SQL PostgreSQL، RETURNING، مسیر storage، خطای امن، action POST یا assets/پوسته مشترک بر حسب فرم. |
| `admin/articles/index.php` | SQL PostgreSQL، RETURNING، مسیر storage، خطای امن، action POST یا assets/پوسته مشترک بر حسب فرم. |
| `admin/books/create.php` | SQL PostgreSQL، RETURNING، مسیر storage، خطای امن، action POST یا assets/پوسته مشترک بر حسب فرم. |
| `admin/books/delete.php` | حذف مشترک تراکنشی، outbox فایل و POST-CSRF. |
| `admin/books/edit.php` | SQL PostgreSQL، RETURNING، مسیر storage، خطای امن، action POST یا assets/پوسته مشترک بر حسب فرم. |
| `admin/books/index.php` | SQL PostgreSQL، RETURNING، مسیر storage، خطای امن، action POST یا assets/پوسته مشترک بر حسب فرم. |
| `admin/categories/index.php` | SQL PostgreSQL، RETURNING، مسیر storage، خطای امن، action POST یا assets/پوسته مشترک بر حسب فرم. |
| `admin/change-password.php` | SQL PostgreSQL، RETURNING، مسیر storage، خطای امن، action POST یا assets/پوسته مشترک بر حسب فرم. |
| `admin/includes/footer.php` | SQL PostgreSQL، RETURNING، مسیر storage، خطای امن، action POST یا assets/پوسته مشترک بر حسب فرم. |
| `admin/includes/header.php` | SQL PostgreSQL، RETURNING، مسیر storage، خطای امن، action POST یا assets/پوسته مشترک بر حسب فرم. |
| `admin/lessons/create.php` | SQL PostgreSQL، RETURNING، مسیر storage، خطای امن، action POST یا assets/پوسته مشترک بر حسب فرم. |
| `admin/lessons/delete.php` | حذف مشترک تراکنشی، outbox فایل و POST-CSRF. |
| `admin/lessons/edit.php` | SQL PostgreSQL، RETURNING، مسیر storage، خطای امن، action POST یا assets/پوسته مشترک بر حسب فرم. |
| `admin/lessons/index.php` | SQL PostgreSQL، RETURNING، مسیر storage، خطای امن، action POST یا assets/پوسته مشترک بر حسب فرم. |
| `admin/login.php` | SQL PostgreSQL، RETURNING، مسیر storage، خطای امن، action POST یا assets/پوسته مشترک بر حسب فرم. |
| `admin/logout.php` | صفحه تأیید خروج و POST-CSRF. |
| `admin/media/index.php` | نمایش registry مستقل از دیسک و حذف مرکزی. |
| `admin/messages.php` | SQL PostgreSQL، RETURNING، مسیر storage، خطای امن، action POST یا assets/پوسته مشترک بر حسب فرم. |
| `admin/messages/index.php` | SQL PostgreSQL، RETURNING، مسیر storage، خطای امن، action POST یا assets/پوسته مشترک بر حسب فرم. |
| `admin/news/create.php` | SQL PostgreSQL، RETURNING، مسیر storage، خطای امن، action POST یا assets/پوسته مشترک بر حسب فرم. |
| `admin/news/delete.php` | حذف مشترک تراکنشی، outbox فایل و POST-CSRF. |
| `admin/news/edit.php` | SQL PostgreSQL، RETURNING، مسیر storage، خطای امن، action POST یا assets/پوسته مشترک بر حسب فرم. |
| `admin/news/index.php` | SQL PostgreSQL، RETURNING، مسیر storage، خطای امن، action POST یا assets/پوسته مشترک بر حسب فرم. |
| `admin/posts/create.php` | SQL PostgreSQL، RETURNING، مسیر storage، خطای امن، action POST یا assets/پوسته مشترک بر حسب فرم. |
| `admin/posts/delete.php` | حذف مشترک تراکنشی، outbox فایل و POST-CSRF. |
| `admin/posts/edit.php` | SQL PostgreSQL، RETURNING، مسیر storage، خطای امن، action POST یا assets/پوسته مشترک بر حسب فرم. |
| `admin/posts/index.php` | SQL PostgreSQL، RETURNING، مسیر storage، خطای امن، action POST یا assets/پوسته مشترک بر حسب فرم. |
| `admin/settings.php` | upsert PostgreSQL و ذخیره URL لوگو به جای overwrite asset. |
| `admin/speeches/create.php` | SQL PostgreSQL، RETURNING، مسیر storage، خطای امن، action POST یا assets/پوسته مشترک بر حسب فرم. |
| `admin/speeches/delete.php` | حذف مشترک تراکنشی، outbox فایل و POST-CSRF. |
| `admin/speeches/edit.php` | SQL PostgreSQL، RETURNING، مسیر storage، خطای امن، action POST یا assets/پوسته مشترک بر حسب فرم. |
| `admin/speeches/index.php` | SQL PostgreSQL، RETURNING، مسیر storage، خطای امن، action POST یا assets/پوسته مشترک بر حسب فرم. |
| `admin/users.php` | ایجاد/ویرایش/غیرفعال‌سازی کارکنان با دسترسی superadmin. |
| `ajax/like.php` | session واحد، CSRF و حذف DDL در درخواست. |
| `announcements.php` | سازگاری URL/SQL، خروجی متن امن، metadata رسانه یا اصلاح landmark در صفحه موجود. |
| `articles.php` | سازگاری URL/SQL، خروجی متن امن، metadata رسانه یا اصلاح landmark در صفحه موجود. |
| `assets/css/design-system.css` | Design tokens، RTL، responsive، dark mode یا حفظ CSS کامپوننت‌های موجود. |
| `assets/css/legacy-components.css` | Design tokens، RTL، responsive، dark mode یا حفظ CSS کامپوننت‌های موجود. |
| `assets/fonts/OFL.txt` | فونت فارسی Vazirmatn محلی یا مجوز OFL. |
| `assets/fonts/Vazirmatn-Bold.woff2` | فونت فارسی Vazirmatn محلی یا مجوز OFL. |
| `assets/fonts/Vazirmatn-Regular.woff2` | فونت فارسی Vazirmatn محلی یا مجوز OFL. |
| `assets/js/interface.js` | پوسته/منوی accessible یا header CSRF درخواست لایک. |
| `assets/js/main.js` | پوسته/منوی accessible یا header CSRF درخواست لایک. |
| `assets/js/theme.js` | پوسته/منوی accessible یا header CSRF درخواست لایک. |
| `assets/vendor/bootstrap.LICENSE` | وابستگی frontend محلی یا مجوز اصلی، برای حذف نیاز به CDN. |
| `assets/vendor/bootstrap.bundle.min.js` | وابستگی frontend محلی یا مجوز اصلی، برای حذف نیاز به CDN. |
| `assets/vendor/bootstrap.rtl.min.css` | وابستگی frontend محلی یا مجوز اصلی، برای حذف نیاز به CDN. |
| `assets/vendor/icons.LICENSE` | وابستگی frontend محلی یا مجوز اصلی، برای حذف نیاز به CDN. |
| `assets/vendor/icons/bootstrap-icons.min.css` | وابستگی frontend محلی یا مجوز اصلی، برای حذف نیاز به CDN. |
| `assets/vendor/icons/fonts/bootstrap-icons.woff` | وابستگی frontend محلی یا مجوز اصلی، برای حذف نیاز به CDN. |
| `assets/vendor/icons/fonts/bootstrap-icons.woff2` | وابستگی frontend محلی یا مجوز اصلی، برای حذف نیاز به CDN. |
| `assets/vendor/plyr.LICENSE` | وابستگی frontend محلی یا مجوز اصلی، برای حذف نیاز به CDN. |
| `assets/vendor/plyr.css` | وابستگی frontend محلی یا مجوز اصلی، برای حذف نیاز به CDN. |
| `assets/vendor/plyr.js` | وابستگی frontend محلی یا مجوز اصلی، برای حذف نیاز به CDN. |
| `bin/create-admin.php` | عملیات CLI امن برای schema، نخستین مدیر یا retry حذف فایل. |
| `bin/migrate.php` | عملیات CLI امن برای schema، نخستین مدیر یا retry حذف فایل. |
| `bin/storage-gc.php` | عملیات CLI امن برای schema، نخستین مدیر یا retry حذف فایل. |
| `books.php` | سازگاری URL/SQL، خروجی متن امن، metadata رسانه یا اصلاح landmark در صفحه موجود. |
| `category.php` | سازگاری URL/SQL، خروجی متن امن، metadata رسانه یا اصلاح landmark در صفحه موجود. |
| `composer.json` | نیازمندی extensions و AWS SDK adapter S3. |
| `config/config.php` | Environment-based config، ریشه URL/storage، headerهای امنیتی و خطای عمومی. |
| `config/database.php` | تنها PDO PostgreSQL، prepared native و TLS تأییدشده. |
| `config/production.ini` | تنظیم خطا، OPcache، session و سقف منابع PHP production. |
| `config/routes.php` | فهرست صریح routeها و aliasهای مجاز. |
| `contact.php` | حذف DDL وب و تبدیل query rate-limit به PostgreSQL. |
| `database.sql` | schema مرجع PostgreSQL و جداول session/rate-limit/storage/outbox. |
| `db-test.php` | جلوگیری از اجرای وب و انتقال عملیات به CLI PostgreSQL. |
| `docs/AUDIT_FA.md` | مستندات وضعیت واقعی، نصب، inventory و محدودیت‌ها؛ پاک‌سازی راهنمای قدیمی. |
| `docs/CHANGES.md` | مستندات وضعیت واقعی، نصب، inventory و محدودیت‌ها؛ پاک‌سازی راهنمای قدیمی. |
| `docs/DEPLOYMENT_FA.md` | مستندات وضعیت واقعی، نصب، inventory و محدودیت‌ها؛ پاک‌سازی راهنمای قدیمی. |
| `docs/INVENTORY.md` | مستندات وضعیت واقعی، نصب، inventory و محدودیت‌ها؛ پاک‌سازی راهنمای قدیمی. |
| `includes/admin-actions.php` | تبدیل actionهای GET به تأیید و mutation با POST-CSRF. |
| `includes/auth.php` | session مشترک، role/active/version، limiter DB و POST-CSRF. |
| `includes/content-delete.php` | حذف تراکنشی content و outbox فایل با retry. |
| `includes/footer.php` | CTA خانه، پایان landmark، assets محلی و URL اجتماعی امن. |
| `includes/functions.php` | URL، CSRF، upload wrappers، SQL PostgreSQL، Jalali و rich text امن. |
| `includes/header.php` | هدر جدید RTL، جستجو/منو/پوسته، metadata، assets محلی. |
| `includes/home-intro.php` | Hero هندسی و مسیرهای یادگیری با لینک واقعی. |
| `includes/media.php` | حذف تعریف تکراری و DDL، metadata فایل با join و حذف مرکزی. |
| `includes/session.php` | SessionHandler PostgreSQL با row lock و expiry. |
| `includes/storage.php` | local/S3، validation محتوا، نام امن، registry و حذف فایل. |
| `index.php` | افزودن طراحی معرفی خانه، حفظ محتوا، query PostgreSQL و کاهش autoplay. |
| `install.php` | جلوگیری از اجرای وب و انتقال عملیات به CLI PostgreSQL. |
| `lesson.php` | سازگاری URL/SQL، خروجی متن امن، metadata رسانه یا اصلاح landmark در صفحه موجود. |
| `lessons.php` | سازگاری URL/SQL، خروجی متن امن، metadata رسانه یا اصلاح landmark در صفحه موجود. |
| `media-library.php` | آرشیو صوت/ویدیو published با pagination و player responsive. |
| `migrate-sections.php` | جلوگیری از اجرای وب و انتقال عملیات به CLI PostgreSQL. |
| `news.php` | سازگاری URL/SQL، خروجی متن امن، metadata رسانه یا اصلاح landmark در صفحه موجود. |
| `package-lock.json` | نسخه‌های قفل‌شده وابستگی‌های آزمون JS. |
| `package.json` | دستورهای تست HTTP/browser و وابستگی dev. |
| `post.php` | سازگاری URL/SQL، خروجی متن امن، metadata رسانه یا اصلاح landmark در صفحه موجود. |
| `programs.php` | سازگاری URL/SQL، خروجی متن امن، metadata رسانه یا اصلاح landmark در صفحه موجود. |
| `religious-activities.php` | سازگاری URL/SQL، خروجی متن امن، metadata رسانه یا اصلاح landmark در صفحه موجود. |
| `robots.php` | robots وابسته به محیط و آدرس sitemap. |
| `router.php` | Front controller، محافظت فایل، static MIME/cache/range و 404. |
| `search.php` | جستجوی یکپارچه پست/درس/کتاب با query پارامتری. |
| `sitemap.php` | sitemap پویا با محتوای published و SITE_URL. |
| `speech.php` | سازگاری URL/SQL، خروجی متن امن، metadata رسانه یا اصلاح landmark در صفحه موجود. |
| `speeches.php` | سازگاری URL/SQL، خروجی متن امن، metadata رسانه یا اصلاح landmark در صفحه موجود. |
| `tests/browser.mjs` | آزمون تکرارپذیر امنیت، HTTP یا browser؛ fixture مصنوعی کوچک. |
| `tests/fixtures/audio.mp3` | آزمون تکرارپذیر امنیت، HTTP یا browser؛ fixture مصنوعی کوچک. |
| `tests/fixtures/image.png` | آزمون تکرارپذیر امنیت، HTTP یا browser؛ fixture مصنوعی کوچک. |
| `tests/fixtures/video.mp4` | آزمون تکرارپذیر امنیت، HTTP یا browser؛ fixture مصنوعی کوچک. |
| `tests/http.mjs` | آزمون تکرارپذیر امنیت، HTTP یا browser؛ fixture مصنوعی کوچک. |
| `tests/security.php` | آزمون تکرارپذیر امنیت، HTTP یا browser؛ fixture مصنوعی کوچک. |
| `update.sql` | حذف SQL MySQL منسوخ و ارجاع به migration مرجع. |
| `uploads/.htaccess` | قانون دفاعی عدم اجرای upload روی Apache؛ بدون فایل کاربر در Git. |
| `vercel.json` | حفظ سرویس container و route forwarding؛ حذف runtime غیرضروری. |

## اصلاحات پس از نخستین دور CI

| فایل | تغییر تکمیلی |
|---|---|
| `composer.lock` | خروجی نصب موفق Composer در CI، بازیابی‌شده با تأیید SHA-256؛ pin تمام وابستگی‌های PHP. |
| `Dockerfile.vercel` | نصب بر اساس lockfile. |
| `book.php` | تکمیل controller مفقود جزئیات کتاب و دانلود فقط از storage مجاز. |
| `books.php` | اتصال عنوان کتاب و دکمه دریافت به controller واقعی. |
| `assets/vendor/plyr.svg` | sprite محلی player؛ حذف وابستگی آیکن پلیر به CDN. |
| `tests/links.mjs` | خزیدن لینک‌های داخلی و assetهای HTML و شکست آزمون در صورت 4xx/5xx. |
| `includes/storage.php` | پیش‌فرض امن env خالی، تشخیص مسیر legacy و صف حذف پس از بررسی ارجاعات. |
| `includes/content-delete.php` | لغو حذف فایل در صورت استفاده مشترک یا شکست ویرایش. |
| `admin/*/edit.php` | به تعویق انداختن حذف فایل قدیمی تا پس از ذخیره موفق. |
| `admin/media/index.php` | جلوگیری از حذف فایل مورد استفاده در محتوا. |
| `.github/workflows/ci.yml` | metadata صحیح، wait/readiness، diagnostics، تست لینک و خطاهای log؛ حذف snapshot موقت پس از ثبت lock. |

تغییرات فقط روی شاخه session push شده‌اند؛ main جابه‌جا یا بازنویسی نشده است.


### اصلاحات تکمیلی اعتبارسنجی نهایی

- `config/config.php`: بافر پاسخ مستقل برای جلوگیری از headers-already-sent و helper اعتبارسنجی IP.
- `includes/auth.php`، `includes/functions.php`، `contact.php`: استفاده از IP معتبر؛ اولویت محدودیت IP پیش از ایجاد bucket نام کاربری.
- `admin/users.php`: transaction مشترک برای اطلاعات کاربر، رمز و نسخه session.
- `admin/includes/header.php`، `assets/js/interface.js`: منوی موبایل پنل بدون overflow، کنترل صفحه‌کلید و وضعیت ARIA.
- `admin/login.php`: اتصال label/input و autocomplete صحیح ورود.
- `admin/media/index.php`: نمایش جداگانه image/audio/video/document، pagination و کپی لینک بدون تزریق مستقیم URL داخل JavaScript.
- `tests/browser.mjs`: ورود واقعی کارکنان، پوسته و منوی موبایل و رسانه پنل علاوه بر آزمون عمومی.
- `tests/security.php`: ۲۵ assertion شامل رد forwarded IP غیرقابل اعتماد.
- `tests/links.mjs`: timeout و مصرف stream پاسخ برای آزادسازی اتصال، خروجی JSON خطا با مسیر دقیق.
- `.github/workflows/ci.yml`: اجرای واقعی image ساخته‌شده روی PORT=8081 و تکرار آزمون HTTP/browser؛ diagnostics و log کانتینر.
- `README.md`، `docs/AUDIT_FA.md`، `docs/DEPLOYMENT_FA.md`: شواهد قابل ردیابی، محدودیت deployment و قطع اعتبار اتصال GitHub در آخرین push.

خطای authentication مرحله قبل رفع شد و commitهای محلی باقی‌مانده push شدند.


### ادامه کار: بازیابی آپلود پس از شکست ذخیره

| فایل | تغییر |
|---|---|
| `database.sql` | journal مستقل `pending_uploads`، index زمان پردازش و مهلت صف حذف. |
| `includes/storage.php` | ثبت قصد پاک‌سازی پیش از نوشتن فایل با connection مستقل؛ نگهداری فایل متصل و آزادسازی فایل بی‌مرجع در پایان درخواست. |
| `includes/content-delete.php` | انتقال اتمیک jobهای رسیده از journal به صف حذف با `SKIP LOCKED`؛ رعایت مهلت درخواست‌های ناتمام. |
| `admin/books/create.php`، `admin/books/edit.php` | فعال‌کردن چرخه بازیابی آپلود کتاب. |
| `admin/lessons/create.php`، `admin/lessons/edit.php` | فعال‌کردن چرخه بازیابی آپلود درس. |
| `admin/posts/create.php`، `admin/posts/edit.php` | فعال‌کردن چرخه بازیابی فایل‌های پست. |
| `admin/news/create.php`، `admin/news/edit.php` | فعال‌کردن چرخه بازیابی فایل‌های خبر. |
| `admin/speeches/create.php`، `admin/speeches/edit.php` | فعال‌کردن چرخه بازیابی فایل‌های سخنرانی. |
| `admin/settings.php` | بازیابی آپلود لوگوی بدون مرجع. |
| `tests/http.mjs` | چهار بررسی جدید برای شکست فایل دوم، پاک‌سازی فایل اول و حفظ آپلود مستقل کتابخانه. |
| `tests/storage-recovery.php` | ده بررسی DB/storage برای rollback، journal مستقل، مهلت پردازش و حفظ مرجع؛ opt-in صریح برای محیط آزمایشی. |
| `.github/workflows/ci.yml` | اجرای تست بازیابی در PostgreSQL واقعی پیش از تست‌های HTTP و کانتینر. |

---

## مرحله سازگاری کامل با MySQL / InfinityFree (2026-09)

رفع خطای سراسری «لطفاً کمی بعد دوباره تلاش کنید» (503) روی میزبان‌های MySQL. علت‌ها و اصلاح‌ها:

| فایل | تغییر |
|---|---|
| `database.mysql.sql` | ستون‌های `key` به `setting_key`/`limit_key` تغییر نام یافتند (در MySQL کلمه کلیدی هستند و نصب را با خطای نحوی شکست می‌دادند). حذف `START TRANSACTION`/`COMMIT` دور DDL. |
| `database.sql` | تغییر نام هم‌زمان ستون‌ها در طرحوارهٔ PostgreSQL + بلوک `DO` برای rename ستون‌های قدیمی، تا دیتابیس‌های موجود سالم بمانند. |
| `config/database.php` | تبدیل `NULLS FIRST/LAST` و فاصله‌های زمانی (`INTERVAL '30 minutes'` و…) در `normalizeSql`؛ اتصال صحیح پارامترهای `LIMIT ?`/`OFFSET ?` با نوع صحیح؛ `DB_EMULATE_PREPARES` و connect timeout. |
| `includes/auth.php` | upsert محدودکنندهٔ ورود بر پایهٔ درایور؛ پرچم Secure کوکی سشن تابع اسکیم واقعی درخواست شد (جلوگیری از حلقهٔ ریدایرکت ورود در HTTP ساده). |
| `includes/session.php`، `includes/content-delete.php`، `admin/settings.php` | upsert/حذف سازگار با هر دو درایور. |
| `includes/storage.php` | ذخیره/حذف فایل محلی در میزبان‌های سنتی (نه فقط توسعه) مجاز شد؛ آپلود در InfinityFree کار می‌کند. |
| `includes/functions.php` | خواندن تنظیمات با `setting_key`. |
| `bin/migrate.php`، `php/install.php` | مهاجرت/نصب تکرارپذیر روی MySQL (نادیده‌گرفتن «از قبل موجود» برای ایندکس‌ها) و ردشدن از `;` داخل کامنت. |
| `admin/settings.php`، `admin/topics/create.php` | upsert تنظیمات با کلید جدید؛ رفع فراخوانی `redirect()` بدون بارگذاری توابع. |
| `Dockerfile.vercel`، `vercel.json` | افزودن `pdo_mysql` به image و اصلاح ساختار `services` برای runtime کانتینری. |

---

## مرحله بازسازی ساختار (2026-09-22)

بازچینی ساختار پوشه‌ها بدون حذف هیچ قابلیت سالم. نگاشت کامل «فایل قدیم → جدید» و
جدول مسیرها در [FILE_ROUTE_MAP.md](FILE_ROUTE_MAP.md) و گزارش نهایی در
[RESTRUCTURE_REPORT_FA.md](RESTRUCTURE_REPORT_FA.md).

| فایل / پوشه | تغییر |
|---|---|
| ۲۲ کنترل‌کننده عمومی ریشه (`about.php` … `topics.php`) | انتقال به `pages/` و اصلاح همه `require`ها به `__DIR__.'/../…'`. |
| `index.php`, `router.php`, `robots.php`, `sitemap.php`, `.htaccess` | تنها فایل‌های باقی‌مانده در ریشه وب. |
| `includes/home-intro.php` | انتقال به `content/home-intro.php` (قطعه نمایشی) + اصلاح require در `index.php`. |
| `admin/users.php` | انتقال به `admin/users/index.php` (ساختار پوشه‌ای مثل بقیه بخش‌ها). |
| `admin/messages.php` | حذف منطق تکراری؛ تبدیل به redirect به `admin/messages/`. قابلیت «همه را خوانده علامت بزن» به `admin/messages/index.php` منتقل شد. |
| `database.sql`, `database.mysql.sql`, `update.sql` | انتقال به `database/database.postgres.sql`، `database/database.mysql.sql` و `database/migrations/0001-legacy-update-note.sql`. |
| `install.php`, `db-test.php`, `migrate-sections.php` (ریشه) | انتقال به `bin/install-cli.php`، `bin/db-test.php`، `bin/migrate-sections.php` (فقط CLI؛ از وب ۴۰۴). |
| `assets/images/` | تغییر نام به `assets/img/`؛ router نگاشت `/assets/images/*` را به‌عنوان alias نگه می‌دارد. |
| `admin.htaccess`, `uploads.htaccess`, `admin/*.htaccess`, `uploads/*.htaccess` | این کپی‌های بدون نقطهٔ ابتدایی بی‌اثر بودند؛ به `.htaccess` واقعی در همان پوشه‌ها تبدیل/ادغام شدند. |
| `uploads/` | ساخت `images/ books/ videos/ audios/ documents/` با `.htaccess` و `.gitkeep`؛ `UPLOAD_AUDIO`/`UPLOAD_VIDEO` به `audios`/`videos` تغییر کردند و پوشه‌های قدیمی `audio`/`video` در allowlist ماندند. |
| `storage/logs/`, `storage/cache/` | ایجاد شدند؛ در production خطاها در `storage/logs/php-error.log` ثبت می‌شوند (در development همان stderr تا CI هشدارها را ببیند). |
| `config/routes.php` | بازنویسی به‌عنوان تنها مرجع مسیرها: `routes` + `aliases` + `patterns`؛ افزودن `/videos`، `/audios`، `/admin/users/`، `/lessons/{collection}/{volume}`، `/book/{slug}`، `/search/{q}`. |
| `router.php` | گسترش خودکار هر نشانی به `/x`، `/x/`، `/x.php` و `/x/index.php`؛ سرو static با alias تصویر؛ تنظیم `JHD_ROUTE_PATH`؛ توابع جدا برای ۴۰۴ و سرو فایل. |
| `.htaccess` | سرو مستقیم فایل‌های واقعی `assets/` و `uploads/` توسط Apache (بدون مصرف PHP) و ارجاع بقیهٔ درخواست‌ها به `router.php`؛ هیچ `Require all denied`‌ای ندارد (Apache آن را پیش از mod_rewrite اجرا می‌کند و ۴۰۳ به‌جای ۴۰۴ می‌داد). |
| `.htaccess`های جدید | پوشه‌های داخلی (`config/`, `includes/`, `pages/`, `content/`, `database/`, `database/migrations/`, `storage/`, `storage/logs/`, `storage/cache/`, `bin/`, `php/`, `tests/`, `docs/`, `admin/`, `admin/includes/`, `admin/*/`) → `Options -Indexes`؛ `uploads/` و `uploads/*/` → `Options -Indexes -ExecCGI` + `RemoveHandler`/`RemoveType` برای اسکریپت‌ها. پاسخ همهٔ مسیرهای غیرمجاز از طرف `router.php` با ۴۰۴ داده می‌شود. |
| `config/config.php` | `BASE_DIR`/`STORAGE_DIR`، `DEFAULT_ADMIN_USERNAME`/`DEFAULT_ADMIN_PASSWORD`، مسیر error_log در production، پوشه‌های آپلود صوت/ویدیو. |
| `config/local.example.php` | **جدید** — الگوی فایل تنظیمات خصوصی برای نصب دستی. |
| `php/install.php` | مسیر schema به `database/`؛ اگر حساب مدیر وجود داشته باشد رمز آن با `password_hash()` بازنشانی و `auth_version` افزایش می‌یابد؛ فیلد رمز خالی = رمز پیش‌فرض مستند؛ لینک‌های تمیز. |
| `bin/create-admin.php` | پیش‌فرض `admin` / `JH@2026#Admin`؛ ایجاد **یا** بازنشانی امن رمز + `auth_version`؛ پیام دقیق‌تر. |
| `bin/migrate.php` | انتخاب schema از `database/` بر اساس درایور. |
| همه پیوندهای داخلی (۲۹۷ مورد در ۵۳ فایل) | `siteUrl('about.php')` → `siteUrl('about')` و به‌همین ترتیب برای همه صفحات عمومی، admin و رسانه؛ شکل `.php` همچنان به‌عنوان alias کار می‌کند. |
| `includes/header.php` | canonical از `JHD_ROUTE_PATH` (مسیر عمومی) به‌جای `SCRIPT_NAME` (مسیر داخلی `pages/…`)؛ `search?q=` در JSON-LD. |
| `includes/functions.php` | رفع `ValueError` در `paginate()`: مقدارهای percent-encoded داخل الگو (مثل `q=%D8%A7`) دیگر باعث ۵۰۳ نمی‌شوند. |
| `includes/storage.php` | پوشه‌های `audios`/`videos` به allowlist کلید ذخیره‌سازی اضافه شدند (پوشه‌های قدیمی هم ماندند). |
| `admin/topics/index.php` | رفع هشدار «Undefined array key parent_id» و خطای ۵۰۳ وقتی `parent_id` فرستاده نمی‌شد. |
| `sitemap.php` | نشانی‌های تمیز (`topic/{slug}`، `post/{slug}`، `book/{slug|id}`، `lessons/{collection}`، `category/{slug}`) + `/videos` و `/audios`. |
| `tests/http.mjs` | پوشش مسیرهای تمیز و `.php`، مسیرهای محافظت‌شده جدید، همه شکل‌های `/admin/users`، کشف کتاب با نشانی تمیز. |
| `tests/security.php` | بررسی‌های تازه برای پوشه‌های `videos`/`audios`، کلیدهای قدیمی و نشانی‌های تمیز. |
| `README.md`, `INSTALL_GUIDE_FA.md`, `docs/INVENTORY.md` | به‌روزرسانی ساختار، جدول مسیرها، حساب مدیر پیش‌فرض و مسیر فایل‌های schema. |
