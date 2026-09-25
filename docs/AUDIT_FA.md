# گزارش Audit و اصلاحات — ۲۴ سپتامبر ۲۰۲۶

## وضعیت شاخهٔ در حال بازبینی

**این گزارش وضعیت تغییرات شاخهٔ `arena/01a0d390-jametulhoda` را از commit پایهٔ `ade9821` ثبت می‌کند. این شاخه هنوز برای production یا نصب واقعی روی InfinityFree تأیید نشده است.** تغییرات فعلی بر رفع routing و canonicalها، تمرکز لینک‌سازی در helperها، هدر/ناوبری RTL، metadata و structured data، قواعد اجرای upload، sitemap و آزمون مسیرها متمرکز است.

### آزمون‌های همین working tree

- `python3 tests/verify_routes.py`: **70/70** آزمون ایستای resolution مسیر و وجود فایل‌ها موفق.
- `python3 tests/crawl_links.py`: **تمام لینک‌ها و مسیرهای داخلی کشف‌شده** معتبر؛ این خزندهٔ منبعی، پاسخ HTTP را نمی‌سازد.
- `node --check` برای `assets/js/interface.js` و آزمون‌های HTTP/browser/route-smoke، `python3 -m py_compile` برای audit scriptها، بررسی ساختاری delimiterهای CSS در ۷ stylesheet/template و `git diff --check`: موفق در اجرای نهایی همین working tree.
- اسکن لینک‌های `href`/`action` در صفحهٔ اصلی، `includes/` و `pages/` (۳۴ template) هیچ لینک مستقیمی به `pages/*.php`, `includes/*.php` یا `config/*.php` پیدا نکرد.
- لایهٔ ظاهری مشترک برای فهرست‌ها، جزئیات، فرم‌ها، کارت‌ها و blank stateها گسترش یافت؛ پنل مدیریت و login، و نصب‌کنندهٔ فارسی نیز polish شدند. این‌ها بررسی ایستای CSS/markup هستند و screenshot/accessibility browser audit نیستند.
- **PHP در محیط محلی در دسترس نیست**؛ بنابراین PHP lint، اجرای محلی HTTP، تست Apache/.htaccess، مرورگر، route-smoke جدید، آزمون dynamic DB-backed و probe فایل PHP در uploads هنوز برای این تغییرات اجرا نشده‌اند.
- درخواست‌های پیشین به `https://jametulhoda.gt.tc/` و `/news` در TLS handshake شکست خوردند. در نتیجه status میزبان عمومی نامعلوم است و **برای `/news` ادعای HTTP 200 نمی‌شود**.
- آزمون Playwright گسترش یافته تا علاوه بر خانه، ۱۶ مسیر عمومی و detail URLهای ساخته‌شده در آزمون HTTP را در عرض‌های ۳۶۰/۷۶۸/۱۲۸۰ پیکسل از نظر status، عنوان قابل‌مشاهده، description و overflow بسنجد؛ **کد آزمون syntax-checked است اما به علت نبود PHP/مرورگر اجرا نشده است**.
- کد route-smoke که CI روی PHP server و کانتینر Apache فراخوانی می‌کند، ۱۷ مسیر عمومی، پیوند «اخبار» صفحهٔ خانه تا `/news`، و ۶ مسیر نامعتبر/خصوصی (از جمله `install.php` ریشه) را می‌سنجد. workflow همچنین assertionهای شکست و probe فایل PHP در uploads را دارد؛ **این workflow برای همین تغییرات هنوز اجرا/تأیید نشده است**.

### مسیر، امنیت و SEO — تغییرات موجود و نیازمند runtime verification

- مسیر `/news` همچنان از `.htaccess → router.php → config/routes.php → pages/news.php` عبور می‌کند. `config/routes.php` جدول اصلی مسیرها، aliasها و dynamic patternها باقی مانده است.
- `.htaccess` مسیرهای عمومی را به router می‌فرستد و برای فایل‌های upload فقط فهرست محدودی از image/audio/video را مستقیم می‌خواند؛ اسناد از PHP stream می‌شوند و extension اجرایی باید به 404 برسد. قاعدهٔ Apache و subdirectory/InfinityFree هنوز روی host واقعی بررسی نشده است.
- canonical، description، noindex برای search/404، canonical aliasهای media، robots و sitemap تنظیم شده‌اند؛ Article/Book/Media JSON-LD افزوده شده است. صحت HTML/HTTP و داده‌های DB-backed هنوز در runtime نیازمند آزمون است.
- مقدار نام جدید برای نصب‌های تازه در defaults آمده است؛ مقدار custom قدیمی در settings عمداً overwrite نمی‌شود. فقط مقدار legacy پیش‌فرض نمایش عمومی normalize می‌شود.
- credentials دیتابیس واقعی در این بررسی استفاده نشده‌اند. تنظیمات موجود را پیش از استقرار در محیط آزمایشی بررسی کنید.

## پیشینهٔ بررسی نسخهٔ پایه

بخش‌های بعدی این فایل خلاصهٔ audit و CI مربوط به **نسخهٔ پایه/تغییرات پیشین** هستند، نه شواهد اجرای تغییرات این شاخه. نتیجه‌های CI یا مرورگر آن قسمت‌ها را به عنوان تأیید این working tree تلقی نکنید؛ برای این شاخه لازم است workflow مجدداً اجرا شود.

مبنای audit پیشین: commit `ac9c7e7`، مخزن واقعی در ریشه، نه پروژهٔ تو‌در‌تو. صفحات و فرم‌های سالم حفظ شدند؛ تغییرات گستردهٔ پیشین مربوط به دیتابیس، storage، session، uploader و front controller بوده‌اند.

## مشکلات پیدا شده

1. `.env.example` دارای اطلاعات واقعی DB بود و راهنمای نصب اطلاعات حساب هاست/DB و credential پیش‌فرض منتشر می‌کرد.
2. PDO و همه schemaها MySQL بودند؛ schema mutation در include و فرم‌ها با `SHOW COLUMNS`، `AUTO_INCREMENT`، `ENUM` و `ENGINE` اجرا می‌شد. schema رسانه در دو محل متفاوت بود و `sort_order` تضمین نمی‌شد.
3. تعریف تکراری بدون guard برای `countMediaFor` می‌توانست پس از include رسانه fatal error بدهد.
4. queryهای `FIND_IN_SET`, `YEAR`, `INSERT IGNORE`, upsert و alias در HAVING با PostgreSQL سازگار نبودند.
5. مسیر URL پیش‌فرض از Host ورودی ساخته می‌شد؛ فایل‌های S3 توسط برخی فراخوانی‌های `siteUrl()` خراب می‌شدند.
6. session فقط روی دیسک محلی بود؛ AJAX حتی نام session متفاوتی استفاده می‌کرد. محدودیت ورود فقط وابسته به cookie/session بود.
7. حذف محتوا/رسانه/پیام‌ها و logout به GET متکی بودند. tokenهای CSRF در query string قرار می‌گرفتند.
8. آپلود PDF/Word به extension/MIME مرورگر اعتماد می‌کرد؛ نام تصاویر از extension کاربر گرفته می‌شد؛ base64 thumbnail بررسی کافی نداشت.
9. همه فایل‌ها در filesystem ذخیره می‌شدند؛ لوگو در asset دیپلوی‌شده overwrite می‌شد؛ حذف فایل‌های برخی انواع ناقص بود.
10. متن غنی DB بدون sanitization چاپ می‌شد و چند فرم جزئیات exception را نشان می‌دادند. URL شبکه‌های اجتماعی محدود به scheme امن نبود.
11. document root کل مخزن بود و built-in PHP server بدون router امن می‌توانست فایل‌های SQL و راهنما را سرو کند. فایل‌های `*.htaccess` با نام نادرست حفاظت واقعی ایجاد نمی‌کردند.
12. فونت، Bootstrap و player به CDN وابسته بودند؛ سیستم پوسته هماهنگ وجود نداشت؛ منوی قبلی دسترس‌پذیری و responsive ناقص داشت.
13. محاسبه تاریخ جلالی offset اشتباه داشت. جستجو فقط پست‌ها را پوشش می‌داد.
14. صفحه خانه به `book.php` لینک می‌داد اما این فایل در مخزن نبود؛ در خزیدن لینک‌ها 404 آن پیدا شد. صفحه جزئیات و دانلود امن کتاب اضافه و به کتابخانه و جستجو متصل شد.
15. ثبت‌نام عمومی، پروفایل دانشجو و مدیریت دیدگاه‌ها در مخزن اولیه وجود نداشتند؛ فایل یا دیتابیسی برای آن‌ها مشاهده نشد.

## انجام شد — قابل مشاهده در کد

### Routing و اجرا

- `router.php` + allowlist، حفظ URLهای PHP، aliasهای `/login`, `/dashboard`, `/library`, `/audio`, `/video`, `/files` و مسیر detail با slug.
- URL داخلی root-relative با `BASE_PATH`؛ canonical فقط از `SITE_URL` تنظیم‌شده؛ عدم اعتماد به Host برای ساخت لینک.
- deny-by-default برای config، SQL، Git، installer وب، vendor و فایل اجرایی در uploads؛ 404 واقعی.
- static MIME، ETag، Cache-Control، HTTP Range برای رسانه local؛ production container با Apache و PORT قابل تنظیم.

### Database

- معماری runtime **فقط PostgreSQL/PDO pgsql**؛ MySQL fallback یا دو دیتابیس موازی وجود ندارد.
- schema مرجع با identity، TIMESTAMPTZ، unique/index، FK در پست/تصاویر/لایک‌ها و CLI migration تکرارپذیر.
- queryهای شناخته‌شده MySQL تبدیل شدند؛ INSERT ID با RETURNING، LIKE با ILIKE، HAVING با expression.
- `app_sessions`, `login_limits`, `stored_files`, `storage_deletions` اضافه شدند. DDL از درخواست‌های وب خارج شد.
- جستجو پست، درس و کتاب را یکجا پوشش می‌دهد. آرشیو صوت و ویدیو فقط محتوای published را برمی‌گرداند.

### Upload و امنیت

- API مرکزی storage، local فقط development، adapter S3 از AWS SDK برای production.
- MIME واقعی، کنترل اندازه، تصویر decode/re-encode، PDF signature، ساختار DOCX، نام تصادفی و reject traversal/executable.
- ثبت URL و metadata در DB، مدیریت رسانه با registry به جای scandir دیسک.
- حذف محتوای اصلی تراکنشی به‌همراه outbox فایل؛ retry از CLI.
- Session مشترک row-locked، HttpOnly/SameSite/secure در production، regeneration، idle timeout، کنترل نقش/فعال‌بودن در هر درخواست.
- محدودیت ورود مشترک DB، ابطال session با نسخه احراز هویت پس از تغییر رمز، POST/CSRF برای حذف و logout، حذف token از URLهای اقدام.
- مدیریت کاربران کارکنان توسط superadmin، ممانعت از حذف دسترسی حساب فعلی؛ editor حق مدیریت settings/users/messages/media ندارد.
- rich-text allowlist، URL خارجی HTTPS، پیام خطای عمومی غیر‌افشاگر، حذف credential از نسخه جاری و ignore محیط‌ها.

### UI، SEO و assets

- هدر، معرفی اصلی خانه و مسیرهای یادگیری بازطراحی شدند؛ صفحات محتوای موجود و بخش‌های خانه حفظ شدند.
- طراحی RTL سرمه‌ای/سبز/طلایی/کرم، Vazirmatn محلی، typography و کارت‌های مشترک، dark/light با preference پایدار و اجرای زودهنگام.
- منوی موبایل و keyboard Escape، skip link، focus-visible و reduced-motion؛ پوسته مشترک پنل مدیریت.
- کتابخانه صوت/ویدیو responsive با native controls و preload=none.
- meta description، canonical تنظیم‌پذیر، Open Graph/Twitter، structured data سازمان آموزشی، robots و sitemap دینامیک.
- وابستگی‌های frontend محلی با مجوز اصلی؛ بدون نیاز به CDN برای render اولیه.

## تست‌های واقعاً اجراشده

تست محیط محلی، نه Vercel: PHP بومی **8.4.11-dev** از runtime آزمایشی خارج از repository با `pdo_pgsql` و PostgreSQL بومی **18.4** روی دیتابیس disposable. محیط native استاندارد PHP 8.3/Apache در این sandbox موجود نبود. برای lint و unit نیز PHP WebAssembly استفاده شد. این تفاوت با Docker هدف عمداً ذکر می‌شود.

- **۷۸ فایل PHP** در زمان اجرای lint: بدون خطای syntax. فایل‌های vendor و node_modules جزو شمارش نبودند.
- **۲۵ assertion امنیت/URL/MIME/CSRF/XSS/تاریخ** در `tests/security.php`: موفق.
- schema دوبار روی PGlite/PostgreSQL و بارها روی PostgreSQL بومی بدون خطا اعمال شد؛ داده‌های تست حفظ شدند.
- **۷۴ assertion HTTP** در `tests/http.mjs`: موفق؛ شامل صفحات عمومی، alias رسانه/فایل، category/search، 404های امنیتی، redirect ورود، login/logout و dashboard، پنل‌ها، ایجاد پست/مقاله/خبر/درس/کتاب، جزئیات/دانلود کتاب، ویرایش، مشاهده، لایک با/بدون CSRF، کاربر editor و رد دسترسی آن، تماس، حذف GET بدون mutation، POST حذف، حذف واقعی URL فایل‌ها.
- آپلود محلی **PNG، PDF، MP3 و MP4 کوچک**، دریافت فایل از URL و سپس 404 پس از حذف: موفق. تصویر جعلی با نام jpg و محتوای PHP رد شد. runtime محلی فاقد GD WebP بود و fallback PNG آزموده شد؛ همین جریان upload در کانتینر دارای GD WebP نیز در CI موفق شد. این آزمون به معنی پوشش همه تصاویر/codecها نیست.
- مرورگر Chromium/Playwright: خانه دسکتاپ، موبایل 390px، باز/بسته‌شدن منو، حفظ dark mode پس از reload؛ ورود واقعی کارکنان در مرورگر، منوی موبایل پنل، کتابخانه رسانه و حفظ پوسته پنل نیز آزموده شدند؛ بدون خطای JS یا پاسخ 4xx/5xx در صفحات مرورشده.
- عرض‌های 320، 360، 390، 768، 1024 و 1440 بررسی شدند؛ overflow 320px شناسایی و اصلاح شد.
- خزیدن لینک‌های HTML داخلی و assets: در آخرین اجرای محلی ۸۹ URL بررسی شد و لینک شکسته‌ای باقی نماند؛ این آزمون لینک‌های خارجی را پوشش نمی‌دهد.
- زیرمسیر `/school`، URL اصلی/asset/login/sitemap/robots/404، canonical تنظیم‌شده و HTTP Range با پاسخ 206 برای ۲۰ بایت بررسی شدند.
- تمام آزمون‌های فوق روی داده مصنوعی واضح `qa-*` اجرا شدند؛ هیچ اتصال/تغییری در دیتابیس واقعی کاربر انجام نشد.

آزمون HTTP تکرارشدنی است ولی داده تولید می‌کند؛ روی production اجرا نشود. نمونه PDF صرفاً fixture کنترل upload است، نه تأیید parsing همه ساختارهای PDF. اولین آزمون multipart حدود 90KB در harness آزمایشی Node/PHP timeout شد؛ fixtureهای کوچک بعدی موفق بودند. این محدودیت harness و عدم تست بار/آپلود حجیم نباید به عنوان موفقیت upload بزرگ گزارش شود.

## Vercel، Build و GitHub

- `Dockerfile.vercel` و `vercel.json` مطابق مستندات container بررسی و اصلاح شدند؛ container پشتیبانی‌شده beta است، نه فرض runtime PHP سنتی Vercel.
- دستور `vercel whoami` و `vercel deploy --yes` اجرا شدند: **credential CLI وجود ندارد**. پس از push، اتصال GitHub خودکار deployment Preview ایجاد کرد؛ GitHub status برای commit `4fe20e3` عبارت **Deployment has completed / success** را ثبت کرد. URL واقعی: https://jametulhoda-git-arena-01a0bf9d-jametulhoda-eshop4.vercel.app . این URL محافظت‌شده است و درخواست مشاهده به login Vercel رفت؛ موفقیت build معادل تأیید runtime نیست.
- Neon URL و S3 credential در محیط نبودند؛ اتصال Neon/TLS و put/get/delete واقعی S3 تأیید نشده‌اند.
- Docker daemon/CLI و Composer بومی در sandbox موجود نبودند، اما در GitHub CI واقعی نصب Composer dependencies، PHP 8.3، migration روی PostgreSQL 16، تست‌های HTTP و browser و **Docker build همگی موفق شدند**. `composer.lock` دقیق خروجی CI با checksum SHA-256 تأییدشده به مخزن اضافه شد.
- اجرای موفق CI: https://github.com/hajiahmadandishmand41-code/jametulhoda/actions/runs/35524059191 . دو اجرای اولیه ناموفق بودند؛ نقص metadata Composer و مشکلات اجرای آزمایشی پیگیری شدند و اجرای فوق سبز شد. برای commit نهایی نیز workflow مجدداً اجرا می‌شود؛ نتیجه اجرای تکمیلی در انتهای گزارش ثبت شده است.

## امنیت و Rotate

اطلاعات حساس جاری از `.env.example` و `INSTALL_GUIDE_FA.md` حذف شد. schema و installer دیگر حساب با رمز پیش‌فرض ایجاد نمی‌کنند. **credential DB قدیمی در commit اولیه همچنان در تاریخچه است و باید فوراً rotate/revoke شود.** حساب‌های مدیریت ساخته‌شده با رمزهای نمونه یا hash پیش‌فرض قدیمی نیز باید reset و sessionهای قبلی باطل شوند. تاریخچه عمداً force-rewrite نشده؛ حذف از HEAD به معنای پاک‌شدن history، clone یا logهای قبلی نیست. مقدار secretها در این گزارش تکرار نشده است.

## خطاها و کارهای باقی‌مانده — مانع تأیید production

1. دسترسی به Preview محافظت‌شده و تست محتوای واقعی، console و server logs، اتصال Neon و S3 واقعی؛ سپس انتشار production. Preview deployment از GitHub ساخته شده است، ولی end-to-end production قابل تأیید نیست.
2. آزمون runtime container در محیط نهایی، به‌ویژه session بین instanceها و S3 واقعی. Build و اجرای Apache/PHP 8.3 روی PORT سفارشی در CI تأیید شده‌اند؛ این مورد دیگر مانع build نیست.
3. **آپلود مستقیم presigned/multipart برای فایل‌های بزرگ**؛ فرم فعلی server-upload است و محدودیت بدنه Vercel را دور نمی‌زند. ویدیو 200MB روی Vercel هنوز پشتیبانی/تست نشده است.
4. تکمیل transaction و recovery در تمام فرم‌های ویرایش/آپلود چندتایی؛ بازیابی فایل‌های جدید بدون مرجع اکنون با journal اضافه شده؛ فایل‌های تاریخی و حالت‌های خطای واقعی provider همچنان باید بررسی شوند. حذف فایل قدیمی در edit اکنون صف‌شده و با بررسی مراجع محافظت می‌شود، اما rollback کامل همه تغییرات DB در فرم‌های چندمرحله‌ای هنوز تکمیل نیست.
5. مهاجرت واقعی داده‌ها و فایل‌های هاست قبلی، بررسی orphanها، FK/charset/sequenceهای داده واردشده، backup و restore.
6. ثبت‌نام عمومی، پروفایل/داشبورد دانشجو، تأیید ایمیل و بازیابی رمز؛ پنل دیدگاه‌ها/schema دیدگاه. موجود نبودند و تکمیل‌شده اعلام نمی‌شوند.
7. ارزیابی کامل accessibility/contrast تمام صفحات و مرورگرهای مختلف، تمام فرم‌های edit و همه codecها/Word، تست بار و هم‌زمانی، external broken links، و audit نفوذ مستقل.
8. headers فعلی CSP محافظت پایه دارند، نه policy کامل nonce-based برای تمام inline scriptها. ضدبدافزار فایل‌ها، مانیتورینگ، alert و سیاست backup عملیاتی هنوز نیاز به تنظیم دارند.
9. SEO در صفحات اصلی/هدر مشترک تکمیل پایه دارد؛ داده ساختاریافته اختصاصی Article/VideoObject و sitemap index در مقیاس بزرگ هنوز کامل نیست.

به دلیل موارد بالا، وضعیت درست این تحویل **«اصلاحات گسترده و تست محلی موفق، با موانع مشخص پیش از production»** است، نه «تمام ۲۴ بند بدون نقص انجام شد».


## شواهد تکمیلی و وضعیت تحویل نهایی

- CI واقعی برای commit `479e9af` کاملاً موفق شد: [اجرای 35525330749](https://github.com/hajiahmadandishmand41-code/jametulhoda/actions/runs/35525330749). شامل PHP 8.3، PostgreSQL 16، Composer lock، lint، ۲۵ آزمون امنیتی، migration تکراری، ۷۴ assertion HTTP، مرورگر و خزیدن لینک‌ها است.
- علاوه بر build، **کانتینر Apache واقعاً اجرا شد**: `PORT=8081`، دیتابیس آزمایشی و local storage در `/tmp/jhd-uploads`، با `APP_ENV=development`. مجموعه HTTP و مرورگر مجدداً روی کانتینر موفق شد و بررسی log آن خطای PHP پیدا نکرد. این تست جایگزین Neon/S3/HTTPS و چند instance در production نیست.
- CI میانی مشکل auto-flush بافر پیش‌فرض PHP پیش از redirect را آشکار کرد. بافر پاسخ مستقل اضافه شد و آزمون‌ها با `output_buffering=4096` موفق شدند. هشدار JIT مربوط به ابزار coverage در CI نیز با تنظیم مناسب محیط آزمون رفع شد.
- برخی اجرای میانی integration ناموفق بودند و برای همه آن‌ها تشخیص قطعی قابل بازیابی نبود. diagnostics خزنده تقویت شد. پس از آخرین CI سبز، تخلیه کامل stream پاسخ‌های fetch نیز به ابزار خزنده افزوده شد تا اتصال‌های رهاشده باعث توقف PHP تک‌worker نشوند؛ نسخه اصلاح‌شده در محیط محلی **۸۹ URL بدون خطا** داشت.
- کتابخانه رسانه اکنون برای PDF/صوت/ویدیو آیکن متناسب دارد، به‌جای بارگذاری آن‌ها به‌عنوان تصویر؛ pagination اضافه شد. منوی موبایل پنل از keyboard/Escape و aria-expanded پشتیبانی می‌کند.
- اعتبارسنجی IP مرکزی شد: هدرهای forwarding فقط در محیط Vercel مورد اعتمادند؛ تغییرات اطلاعات و رمز کاربران در یک transaction انجام می‌شود.
- [Draft PR #1](https://github.com/hajiahmadandishmand41-code/jametulhoda/pull/1) برای بررسی باز شد. main تغییر نکرده است. در پایان مرحله قبل آخرین push تأییدشده `479e9af` بود؛ commitهای `5074f07` و `e3e3780` اکنون پس از reconnect با موفقیت push شده‌اند.
- خطای `401 Bad credentials` مرحله قبل رفع شد؛ اتصال GitHub برقرار است و تغییرات این مرحله نیز به شاخه session push شده‌اند.
- جدیدترین deployment مستقیماً تأییدشده روی `ff3aafd` موفق است: [Vercel Deployment](https://vercel.com/eshop4/jametulhoda/GJRRqSEi8JyMTAH3NeRgmxtxTpA6). مشاهده مجدد URL عمومی همچنان به login Vercel هدایت شد؛ runtime production قابل تأیید نیست.


شرح نام‌به‌نام فایل‌ها در [CHANGES.md](CHANGES.md) و راهنمای عملیاتی در [DEPLOYMENT_FA.md](DEPLOYMENT_FA.md) آمده است.


## ادامه کار — بازیابی آپلود و رفع مانع GitHub

- تمام commitهای باقی‌مانده مرحله قبل push شدند. main دست‌نخورده و PR همچنان Draft است.
- پیش از ایجاد فایلِ محتوا، قصد پاک‌سازی در `pending_uploads` با connection مستقل ثبت می‌شود تا rollback تراکنش محتوا آن را حذف نکند. پس از پایان درخواست، مرجعها بررسی می‌شوند: فایل متصل حفظ و فایل بدون مرجع پاک می‌شود.
- درخواست ناتمام مهلت ۲۴ساعته دارد. jobهای رسیده به‌صورت اتمیک و با `SKIP LOCKED` به صف حذف انتقال می‌یابند. فایل‌های آپلودشده به‌صورت مستقل در کتابخانه رسانه عمداً وارد این چرخه نمی‌شوند.
- **CI واقعی روی `ff3aafd` کاملاً موفق شد:** [35526743172](https://github.com/hajiahmadandishmand41-code/jametulhoda/actions/runs/35526743172)، و اجرای PR نیز موفق بود. شامل lint تمام **۷۹ فایل PHP**، **۲۵ آزمون امنیت**، **۱۰ آزمون بازیابی فایل/rollback**، **۷۸ assertion HTTP**، browser، crawl و build/اجرای Apache روی PORT=8081؛ HTTP/browser روی کانتینر هم تکرار شدند.
- تست منفی multipart جدید در harness محلی Node/PHP به خطای CSRF رسید؛ بررسی نشان داد parser آن مقدار token ورودی را با طول نادرست تحویل می‌دهد. این نتیجه محلی موفق اعلام نمی‌شود. همین آزمون در PHP استاندارد CI و Apache کانتینر موفق شد و پاک‌سازی فایل اول پس از رد فایل دوم و حفظ فایل مستقل کتابخانه را تأیید کرد.
- migration جدید و اجرای زمان‌بندی‌شده `php bin/storage-gc.php` ضروری‌اند؛ هیچ migration روی Neon واقعی انجام نشد. بدون worker، پاک‌سازی پس از قطع ناگهانی تضمین نمی‌شود.
- این اصلاح، تراکنش توزیع‌شده S3/PostgreSQL یا atomicity کامل تمام فرم‌های چندمرحله‌ای نیست. S3 واقعی، خطاهای provider، upload بزرگ، عضویت دانشجو و سایر موارد production همچنان باقی‌اند.
