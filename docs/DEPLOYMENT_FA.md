# راهنمای Deployment و عملیات

## ۱. مسیر اجرای سایت

Root پروژه تغییر نکرده است. `index.php` محتوای خانه است؛ همه درخواست‌های وب از `router.php` عبور می‌کنند. `config/routes.php` تنها فهرست مجاز اجرای PHP است. فایل SQL، `.env`، config، vendor، ابزار CLI و Git از این مسیر سرو نمی‌شوند. `.htaccess` ریشه همه درخواست‌ها را به router می‌فرستد. فایل‌های قدیمی با نام `admin.htaccess` یا `uploads.htaccess` فایل فعال Apache نیستند؛ حفاظت به آن‌ها متکی نیست.

محیط development: `php -S 0.0.0.0:8080 router.php`؛ **اجرای `php -S ... -t .` بدون router امن نیست.**

Container: PHP 8.3 Apache، `pdo_pgsql`, mbstring/GD/zip/dom، Composer و OPcache. پورت از `PORT` گرفته می‌شود (پیش‌فرض 80). Apache درخواست PHP، asset و 404 را به router می‌فرستد؛ فایل‌های بزرگ دائمی از S3/CDN خوانده می‌شوند، نه دیسک container.

## ۲. Environment variables

`.env.example` صرفاً فهرست نام‌ها است، نه loader. مقادیر را در محیط shell یا Vercel Project Settings → Environment Variables وارد کنید. هیچ credential را در فایل versioned یا chat قرار ندهید.

| نام | کاربرد |
|---|---|
| `APP_ENV` | `development` برای محلی؛ `production` برای انتشار |
| `DATABASE_URL` | تنها URL PostgreSQL؛ روی Neon با `sslmode=verify-full`؛ local آزمایشی می‌تواند `sslmode=disable` باشد |
| `SITE_URL` | URL عمومی HTTPS، بدون slash انتهایی؛ مبنای canonical/sitemap، نه Host ورودی |
| `BASE_PATH` | محلی اختیاری مثل `/school`؛ روی Vercel معمولاً خالی |
| `SITE_EMAIL`, `SITE_PHONE`, `SITE_ADDRESS` | مقادیر تماس پیش‌فرض؛ تنظیمات DB می‌توانند آن‌ها را override کنند |
| `SESSION_DRIVER` | `database`؛ `files` فقط development/آزمون |
| `UPLOAD_STORAGE` | `local` برای development یا `s3` برای production |
| `UPLOAD_LOCAL_PATH` | مسیر فیزیکی local اختیاری؛ پیش‌فرض پوشه uploads ریشه |
| `UPLOAD_BASE_URL` | local معمولاً خالی (پیش‌فرض BASE_PATH/uploads)؛ production ریشه HTTPS عمومی bucket/CDN |
| `S3_ENDPOINT` | endpoint HTTPS سرویس S3-compatible |
| `S3_REGION` | region سرویس؛ برای R2 معمولاً `auto` |
| `S3_BUCKET` | نام bucket |
| `S3_ACCESS_KEY_ID`, `S3_SECRET_ACCESS_KEY` | credential محدود به همان bucket، مجوز put/get/head/delete |
| `S3_PATH_STYLE` | `true` یا `false` متناسب با ارائه‌دهنده |
| `ADMIN_USERNAME`, `ADMIN_PASSWORD`, `ADMIN_EMAIL` | فقط برای ابزار اولیه CLI؛ پس از ایجاد مدیر از محیط حذف شوند |
| `PORT`, `VERCEL` | متغیرهای میزبان؛ معمولاً به صورت خودکار تنظیم می‌شوند |

`APP_ENV=production` با session فایل یا local storage برای نوشتن فایل، fail-closed است. Production DB نیز TLS بدون تأیید گواهی را قبول نمی‌کند. `DATABASE_URL` از Neon ممکن است با `sslmode=require` ارائه شود؛ برای این پروژه به `verify-full` تغییر دهید. فایل CA سیستم در container نصب می‌شود.

## ۳. Neon / PostgreSQL

1. branch دیتابیس staging جدا بسازید؛ endpoint مناسب را از dashboard Neon بگیرید.
2. با اتصال مستقیم و نقش مالک schema، `php bin/migrate.php` را اجرا کنید. از پنل وب migration اجرا نمی‌شود.
3. نقش runtime را از نقش migration جدا کنید. Runtime به SELECT/INSERT/UPDATE/DELETE جداول و USAGE/SELECT sequenceها نیاز دارد؛ CREATE/ALTER/DROP لازم ندارد.
4. برای اپ می‌توان از endpoint pooled استفاده کرد. ایجاد محتوا از `INSERT ... RETURNING id` استفاده می‌کند، نه lastval وابسته به connection.
5. sessionها در `app_sessions` با connection جدا و row lock نگهداری می‌شوند. connection pool را متناسب با concurrency و دو connection هر درخواست دارای session تنظیم کنید. PHP session تا پایان درخواست lock دارد؛ فرم‌های concurrent یک مرورگر سری می‌شوند.
6. جدول‌های `app_sessions` منقضی و `login_limits` قدیمی را با job زمان‌بندی‌شده پاک‌سازی کنید. Session GC داخلی نیز وجود دارد.
7. ابتدا backup و تمرین restore انجام دهید. انتقال داده MySQL واقعی هنوز انجام/تأیید نشده است؛ نصب schema جدید با مهاجرت داده یکسان نیست.

## ۴. Storage

API مرکزی در `includes/storage.php` است:

- کلید تصادفی ۲۰ بایتی؛ پسوند از محتوای MIME و نه filename کاربر.
- `finfo`، اندازه واقعی، decode تصویر و بازکدگذاری آن؛ PDF signature؛ کنترل ساختار DOCX و رد فایل macro/executable در آن.
- JPEG/PNG/GIF/WebP، صوت و ویدیو مجاز، PDF/Word پشتیبانی می‌شوند. این کنترل، antivirus یا اعتبارسنجی کامل codec نیست؛ برای فایل‌های غیرقابل‌اعتماد، اسکن ضدبدافزار نیز اضافه کنید.
- تصویر به WebP تبدیل می‌شود؛ در محیط بدون WebP به PNG. تصویر متحرک به فریم ثابت تبدیل می‌شود. حداکثر ۲۰ میلیون پیکسل برای پیشگیری از مصرف نامحدود حافظه.
- پس از S3 put، وجود object و پاسخ HTTP عمومی بررسی می‌شود؛ URL عمومی در ستون محتوای مربوط و `stored_files` ثبت می‌شود.
- `UPLOAD_BASE_URL` باید دقیقاً ریشه‌ای باشد که همان keyها را سرو می‌کند؛ read عمومی یا CDN عمومی با TLS و Content-Type صحیح را تنظیم کنید. List عمومی bucket را باز نکنید. Write عمومی را هرگز فعال نکنید.
- تنظیمات CORS bucket برای GET/HEAD از دامنه سایت و پشتیبانی Range برای video/audio را بررسی کنید؛ S3 credentials فقط سمت سرور هستند.
- حذف محتوا تراکنشی است و فایل‌ها ابتدا در `storage_deletions` صف می‌شوند. شکست remote در صف باقی می‌ماند. `php bin/storage-gc.php` را زمان‌بندی کنید.
- تعویض فایل در فرم‌های edit اکنون حذف قبلی را صف می‌کند؛ worker پیش از حذف تمام مراجع را بررسی می‌کند و در صورت شکست UPDATE یا استفاده مشترک، حذف را لغو می‌کند. مدیریت رسانه نیز فایل در حال استفاده را حذف نمی‌کند. برای فایل جدیدِ محتوا، journal مستقل `pending_uploads` قبل از ذخیره فایل نوشته می‌شود. پایان درخواست، فایل بدون مرجع پاک و فایل متصل نگه داشته می‌شود؛ در قطع درخواست، job پس از ۲۴ ساعت قابل پردازش می‌شود. آپلود مستقل کتابخانه رسانه وارد این چرخه نمی‌شود. **محدودیت باقی‌مانده:** atomicity کامل همه تغییرات چندمرحله‌ای محتوا، reconciliation فایل‌های تاریخی و رفتار شکست واقعی provider هنوز نیاز به تکمیل/آزمون دارد.
- فایل‌های legacy را با حفظ key از uploads به bucket منتقل و صحت URLها را بررسی کنید. URLهای قدیمی `uploads/...` به ریشه مرکزی تبدیل می‌شوند. تغییر bucket/CDN برای URLهای مطلق ذخیره‌شده نیازمند migration URL است، نه حدس دامنه قبلی.

### محدودیت مهم آپلود Vercel

Vercel Functions محدودیت اندازه درخواست دارد؛ فرم‌های multipart فعلی **آپلود مستقیم مرورگر به S3 نیستند**. حتی اگر حد ویدیو در کد 200MB باشد، محدودیت پلتفرم قبل از PHP اعمال می‌شود. در محیط Vercel درخواست بیش از 4MiB نیز از سمت برنامه رد می‌شود. مقادیر `php.ini` مستقل از سقف Vercel هستند.

**برای ویدیو/صوت بزرگ، پیش از production باید presigned direct/multipart upload همراه با مرحله نهایی‌سازی و اعتبارسنجی server-side پیاده‌سازی و با CORS واقعی تست شود.** در این مرحله ادعای کارکرد 200MB روی Vercel نمی‌شود. فرم‌های کوچک محلی تست شده‌اند؛ S3 واقعی هنوز نیاز به آزمون دارد.

## ۵. Vercel

مستندات رسمی بررسی‌شده در 2026-09-20:

- https://vercel.com/docs/functions/container-images
- https://vercel.com/docs/functions/runtimes

Container Images اکنون beta مستندشده است. ساختار `services.web.root` و `entrypoint: Dockerfile.vercel` حفظ شده و گزینه غیرضروری runtime حذف شده است. بازنویسی همه درخواست‌ها به سرویس web باقی است. برای project/plan خود فعال‌بودن feature را بررسی کنید.

1. repository و شاخه اصلاح‌شده را در Vercel import کنید؛ Framework Preset سفارشی/Other و root همان ریشه مخزن، نه زیرپوشه تکراری.
2. محیط Preview را به DB و bucket آزمایشی جدا وصل کنید؛ production به منابع production.
3. envهای بالا را تنظیم و schema و حساب مدیر را با ابزار CLI امن ایجاد کنید.
4. ابتدا `docker build -f Dockerfile.vercel -t jametulhoda .` و سپس `docker run --rm -p 8080:80 --env-file /path/to/private.env jametulhoda` را بررسی کنید.
5. با اتصال Vercel فعال، `vercel deploy` برای preview و پس از تأیید `vercel --prod` اجرا کنید.
6. URL واقعی، logs، صفحات اصلی، فایل‌ها، login/logout، CSRF، session بین instanceها، upload/delete S3 و headers را روی deployment تست کنید. URL واقعی را فقط پس از موفقیت در README قرار دهید.

**نتیجه این محیط:** CLI login معتبر ندارد و deploy مستقیم CLI رد شد. با push روی شاخه، اتصال موجود GitHub به Vercel به‌صورت خودکار Preview ساخت و وضعیت success برای commit `4fe20e3` ثبت شد. [آدرس واقعی Preview](https://jametulhoda-git-arena-01a0bf9d-jametulhoda-eshop4.vercel.app) پشت Deployment Protection است؛ تلاش برای مشاهده به صفحه ورود Vercel رفت. بنابراین runtime سایت، Neon و S3 واقعی و production logs هنوز قابل تأیید نیستند. Docker build و اجرای آزمون‌ها روی Apache با PORT=8081 در GitHub CI موفق شد. برای ادامه، محیط Vercel و دسترسی مشاهده/لاگ را از حساب خود تنظیم کنید؛ secret را در گفتگو ارسال نکنید.

## ۶. انتشار و بازگشت

- پیش از merge، CI و بررسی دستی گزارش Audit را انجام دهید. تغییرات عمداً فقط در شاخه session هستند.
- credentialهای قدیمی DB و حساب‌های default را rotate و sessionهای قبلی را باطل کنید.
- backup فایل‌ها و DB را قبل از مهاجرت نگه دارید. Rollback کد PostgreSQL به نسخه MySQL بدون بازگردانی معماری DB امکان‌پذیر نیست.
- Runtime logs نباید body فرم، رمز، token یا URL دارای credential را ثبت کنند. خطاهای عمومی فقط شناسه پیگیری دارند.
- برای ثبت‌نام عمومی، ایمیل تأیید، بازیابی رمز، نقش دانشجو و مدیریت دیدگاه‌ها هنوز پیاده‌سازی لازم است؛ مسیر یا لینک نمایشی ساختگی برای آن‌ها اضافه نشده است.


## وضعیت اتصال در پایان این مرحله

اتصال GitHub دوباره برقرار شد و commitهای قبلی push شدند. CI کامل روی `ff3aafd` موفق است:
https://github.com/hajiahmadandishmand41-code/jametulhoda/actions/runs/35526743172
Vercel نیز برای همین commit وضعیت Deployment has completed ثبت کرد؛ مشاهده مجدد Preview همچنان به login محافظت‌شده رفت.

### migration و job ضروری برای بازیابی آپلود

1. روی دیتابیس هدف، با backup و از محیط امن، `php bin/migrate.php` را **پیش از استفاده از کد جدید** اجرا کنید؛ جدول `pending_uploads` و ستون `storage_deletions.not_before` اضافه شده‌اند. این migration در این مرحله فقط روی دیتابیس آزمایشی اجرا شده، نه Neon واقعی شما.
2. worker را از همین نسخه کد اجرا کنید و `php bin/storage-gc.php` را مثلاً هر پنج دقیقه از یک scheduler امن با envهای همان DB/bucket زمان‌بندی کنید؛ CLI worker مسیر عمومی HTTP نیست.
3. پردازش هر فراخوانی محدود است؛ با حجم زیاد یا صف عقب‌افتاده، اجرای بیشتری لازم است. شکست حذف فایل در صف باقی می‌ماند و باید مانیتور شود.
4. فایلِ حاصل از درخواست ناتمام حداکثر پس از رسیدن مهلت ۲۴ساعته واجد شرایط بررسی است، نه تضمین حذف در ساعت دقیق؛ اجرای worker و دسترسی storage لازم‌اند. فایل هنوز متصل به محتوا حذف نمی‌شود.
5. journal مستقل از transaction محتواست. این مکانیسم جایگزین backup، lifecycle مناسب bucket یا تضمین تراکنش توزیع‌شده بین PostgreSQL و S3 نیست.

IP مشتری فقط در صورت وجود متغیر سیستمی `VERCEL` از هدر کنترل‌شده پلتفرم خوانده می‌شود؛ در سایر محیط‌ها `REMOTE_ADDR` معیار است. برای reverse proxy دیگری، سیاست trust صریح لازم است؛ هدر دلخواه کاربر را معتبر نکنید. [مستند هدرهای Vercel](https://vercel.com/docs/headers/request-headers).
