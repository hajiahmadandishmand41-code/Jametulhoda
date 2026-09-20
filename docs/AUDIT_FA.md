# گزارش Audit و اصلاحات — ۲۰ سپتامبر ۲۰۲۶

## جمع‌بندی صریح

**این تحویل هنوز تأیید نهایی production-ready و Deploy موفق نیست.** زیرساخت PHP/PostgreSQL، بخش مهمی از امنیت و upload، routing و رابط سایت اصلاح شده و تست محلی واقعی دارد. Vercel، Neon واقعی و S3 واقعی بدون دسترسی تنظیم‌شده قابل تأیید نبودند. قابلیت‌های جدیدی که در پروژه اولیه وجود نداشتند، همگی در این مرحله پیاده نشده‌اند. موارد باقی‌مانده در پایان گزارش آمده‌اند.

مبنای بررسی: commit `ac9c7e7`، مخزن واقعی در ریشه، نه پروژه جدید داخل پوشه تو‌در‌تو. پس از دریافت تاریخچه کامل قابل دسترسی، مخزن یک commit اولیه داشت. صفحات و فرم‌های سالم حفظ شدند؛ حذف کدها عمدتاً مربوط به schema mutation هنگام درخواست وب، installer ناامن، مسیرهای آپلود پراکنده و هدر جایگزین‌شده است.

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
14. ثبت‌نام عمومی، پروفایل دانشجو و مدیریت دیدگاه‌ها در مخزن اولیه وجود نداشتند؛ فایل یا دیتابیسی برای آن‌ها مشاهده نشد.

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

- **۷۷ فایل PHP** در زمان اجرای lint: بدون خطای syntax. فایل‌های vendor و node_modules جزو شمارش نبودند.
- **۲۲ assertion امنیت/URL/MIME/CSRF/XSS/تاریخ** در `tests/security.php`: موفق.
- schema دوبار روی PGlite/PostgreSQL و بارها روی PostgreSQL بومی بدون خطا اعمال شد؛ داده‌های تست حفظ شدند.
- **۷۱ assertion HTTP** در `tests/http.mjs`: موفق؛ شامل صفحات عمومی، alias رسانه/فایل، category/search، 404های امنیتی، redirect ورود، login/logout و dashboard، پنل‌ها، ایجاد پست/مقاله/خبر/درس/کتاب، ویرایش، مشاهده، لایک با/بدون CSRF، کاربر editor و رد دسترسی آن، تماس، حذف GET بدون mutation، POST حذف، حذف واقعی URL فایل‌ها.
- آپلود محلی **PNG، PDF، MP3 و MP4 کوچک**، دریافت فایل از URL و سپس 404 پس از حذف: موفق. تصویر جعلی با نام jpg و محتوای PHP رد شد. runtime محلی فاقد GD WebP بود و fallback PNG آزموده شد؛ خروجی WebP در Docker هنوز نیاز به تست دارد.
- مرورگر Chromium/Playwright: خانه دسکتاپ، موبایل 390px، باز/بسته‌شدن منو، حفظ dark mode پس از reload؛ بدون خطای JS یا پاسخ 4xx/5xx در بارگذاری خانه نهایی.
- عرض‌های 320، 360، 390، 768، 1024 و 1440 بررسی شدند؛ overflow 320px شناسایی و اصلاح شد.
- تمام آزمون‌های فوق روی داده مصنوعی واضح `qa-*` اجرا شدند؛ هیچ اتصال/تغییری در دیتابیس واقعی کاربر انجام نشد.

آزمون HTTP تکرارشدنی است ولی داده تولید می‌کند؛ روی production اجرا نشود. نمونه PDF صرفاً fixture کنترل upload است، نه تأیید parsing همه ساختارهای PDF. اولین آزمون multipart حدود 90KB در harness آزمایشی Node/PHP timeout شد؛ fixtureهای کوچک بعدی موفق بودند. این محدودیت harness و عدم تست بار/آپلود حجیم نباید به عنوان موفقیت upload بزرگ گزارش شود.

## Vercel، Build و GitHub

- `Dockerfile.vercel` و `vercel.json` مطابق مستندات container بررسی و اصلاح شدند؛ container پشتیبانی‌شده beta است، نه فرض runtime PHP سنتی Vercel.
- دستور `vercel whoami` اجرا شد: **login معتبر وجود ندارد**. URL نهایی و deployment production ایجاد نشده است.
- Neon URL و S3 credential در محیط نبودند؛ اتصال Neon/TLS و put/get/delete واقعی S3 تأیید نشده‌اند.
- Docker daemon/CLI در sandbox موجود نبود؛ Docker build محلی اجرا نشده است. Composer binary نیز از میزبان دانلود در دسترس نبود؛ نصب SDK و lockfile در این محیط تأیید نشده بود.
- CI برای PHP 8.3، PostgreSQL 16، Composer، HTTP، browser و Docker build اضافه شده است. نتیجه اجرای واقعی CI، در صورت در دسترس بودن پس از push، جداگانه به همین گزارش افزوده می‌شود.

## امنیت و Rotate

اطلاعات حساس جاری از `.env.example` و `INSTALL_GUIDE_FA.md` حذف شد. schema و installer دیگر حساب با رمز پیش‌فرض ایجاد نمی‌کنند. **credential DB قدیمی در commit اولیه همچنان در تاریخچه است و باید فوراً rotate/revoke شود.** حساب‌های مدیریت ساخته‌شده با رمزهای نمونه یا hash پیش‌فرض قدیمی نیز باید reset و sessionهای قبلی باطل شوند. تاریخچه عمداً force-rewrite نشده؛ حذف از HEAD به معنای پاک‌شدن history، clone یا logهای قبلی نیست. مقدار secretها در این گزارش تکرار نشده است.

## خطاها و کارهای باقی‌مانده — مانع تأیید production

1. Deploy واقعی Vercel، تست URL نهایی، console و server logs محیط production، اتصال Neon و S3 واقعی.
2. تأیید Docker build PHP 8.3 و Composer dependencies/lockfile در محیط build قابل دسترسی.
3. **آپلود مستقیم presigned/multipart برای فایل‌های بزرگ**؛ فرم فعلی server-upload است و محدودیت بدنه Vercel را دور نمی‌زند. ویدیو 200MB روی Vercel هنوز پشتیبانی/تست نشده است.
4. تکمیل transaction و recovery در تمام فرم‌های ویرایش/آپلود چندتایی؛ برخی uploadهای جزئی می‌توانند فایل جدید بدون مرجع باقی بگذارند. حذف فایل قدیمی در edit اکنون صف‌شده و با بررسی مراجع محافظت می‌شود، اما rollback کامل همه مراحل هنوز تکمیل نیست.
5. مهاجرت واقعی داده‌ها و فایل‌های هاست قبلی، بررسی orphanها، FK/charset/sequenceهای داده واردشده، backup و restore.
6. ثبت‌نام عمومی، پروفایل/داشبورد دانشجو، تأیید ایمیل و بازیابی رمز؛ پنل دیدگاه‌ها/schema دیدگاه. موجود نبودند و تکمیل‌شده اعلام نمی‌شوند.
7. ارزیابی کامل accessibility/contrast تمام صفحات و مرورگرهای مختلف، تمام فرم‌های edit و همه codecها/Word، تست بار و هم‌زمانی، external broken links، و audit نفوذ مستقل.
8. headers فعلی CSP محافظت پایه دارند، نه policy کامل nonce-based برای تمام inline scriptها. ضدبدافزار فایل‌ها، مانیتورینگ، alert و سیاست backup عملیاتی هنوز نیاز به تنظیم دارند.
9. SEO در صفحات اصلی/هدر مشترک تکمیل پایه دارد؛ داده ساختاریافته اختصاصی Article/VideoObject و sitemap index در مقیاس بزرگ هنوز کامل نیست.

به دلیل موارد بالا، وضعیت درست این تحویل **«اصلاحات گسترده و تست محلی موفق، با موانع مشخص پیش از production»** است، نه «تمام ۲۴ بند بدون نقص انجام شد».
