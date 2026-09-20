# راهنمای نصب سریع — مدرسه علمیه جامعه‌الهدی
## هاست: mjametulhoda.kesug.com (InfinityFree)

> ✅ **این پروژه قبلاً روی محیط تست نصب و اجرا شده و همه چیز کار می‌کند.**

---

## 📋 مشخصات اتصال (قبلاً تنظیم شده)

| فیلد | مقدار |
|------|-------|
| MySQL Host | `sql103.infinityfree.com` |
| MySQL Port | `3306` |
| MySQL User | `if0_42520887` |
| MySQL Pass | `0pJGn8htMbl` |
| MySQL DB | `if0_42520887_jametulhoda` |

> ⚠️ **نکته امنیتی:** پسورد DB در فایل `config/config.php` به صورت متن ساده ذخیره شده. پس از نصب موفق، **حتماً از پنل InfinityFree پسورد را عوض کنید** و در `config/config.php` نیز جایگزین کنید.

---

## 🚀 مراحل نصب (۵ دقیقه)

### مرحله ۱ — دانلود و آماده‌سازی
- فایل `jamiatalhoda_final.zip` را دانلود کنید
- در کامپیوتر خود Extract کنید
- **محتویات** پوشه `jamiatalhoda_final/` را برای آپلود آماده کنید (نه خود پوشه را، بلکه فایل‌های داخل آن را)

### مرحله ۲ — آپلود به هاست
- وارد **File Manager** در پنل InfinityFree شوید
- به پوشه `htdocs/` بروید
- **اگر فایل `index.html` پیش‌فرض InfinityFree وجود دارد، آن را پاک کنید**
- همه فایل‌ها و پوشه‌های پروژه را **به داخل `htdocs/`** آپلود کنید

ساختار نهایی باید این شکلی باشد:
```
htdocs/
├── index.php
├── post.php
├── about.php
├── contact.php
├── news.php
├── ... (بقیه فایل‌های PHP)
├── config/
├── includes/
├── admin/
├── assets/
├── uploads/
├── install.php
├── database.sql
└── .htaccess
```

### مرحله ۳ — اجرای install.php
- مرورگر را باز کنید
- بروید به: `https://mjametulhoda.kesug.com/install.php`
- باید صفحه‌ای سبز رنگ با گزارش موفقیت ببینید (۱۱ تیک سبز ✅)

اگر خطا دیدید:
- مطمئن شوید نام دیتابیس در `config/config.php` با نام واقعی در cPanel match می‌کند
- اگر cPanel دیتابیس را `if0_42520887_jametulhoda` ساخته، همین مقدار در `DB_NAME` باشد

### مرحله ۴ — ورود به پنل
- بروید به: `https://mjametulhoda.kesug.com/admin/login.php`
- **نام کاربری:** `admin`
- **رمز عبور:** `Admin@1234`

### مرحله ۵ — تغییر رمز عبور
- بعد از ورود، به منوی **تغییر رمز** بروید
- رمز قوی جدید بگذارید (مثل `J@miat2026!Ulama`)

### مرحله ۶ — پاکسازی امنیتی (مهم!)
از File Manager هاست، **این دو فایل را حذف کنید**:
- `install.php`
- `db-test.php`

### مرحله ۷ — اختیاری ولی توصیه‌شده
- رمز عبور MySQL را از پنل InfinityFree عوض کنید
- مقدار جدید را در `config/config.php` (خط `DB_PASS`) جایگزین کنید

---

## ✅ سایت آماده استفاده است!

| دسترسی | آدرس |
|--------|------|
| صفحه اصلی | `https://mjametulhoda.kesug.com/` |
| اخبار | `https://mjametulhoda.kesug.com/news.php` |
| مقالات | `https://mjametulhoda.kesug.com/articles.php` |
| درس‌ها | `https://mjametulhoda.kesug.com/lessons.php` |
| درباره ما | `https://mjametulhoda.kesug.com/about.php` |
| تماس | `https://mjametulhoda.kesug.com/contact.php` |
| پنل ادمین | `https://mjametulhoda.kesug.com/admin/login.php` |

---

## 🔧 عیب‌یابی (اگر مشکلی پیش آمد)

### مشکل: صفحه سفید / خطای 500
1. از File Manager هاست، فایل `config/config.php` را باز کنید
2. خط `ini_set('display_errors', '0');` را به `'1'` تغییر دهید
3. صفحه را رفرش کنید تا خطا نمایش داده شود
4. اگر مشکل پیدا شد، آن را برطرف کنید و display_errors را برگردانید

### مشکل: خطای اتصال به دیتابیس
- مطمئن شوید دیتابیس در cPanel ساخته شده
- مقادیر `DB_NAME`, `DB_USER`, `DB_PASS` را با پنل Match کنید
- توجه: در InfinityFree نام دیتابیس با پیش‌وند `if0_xxxxxxx_` کامل می‌شود

### مشکل: فایل آپلود نمی‌شود
- پوشه `uploads/` باید دسترسی ۷۵۵ داشته باشد (مالک باشید)
- File Manager → راست‌کلیک روی `uploads/` → Permissions → 755

### مشکل: لوگو/تصاویر نمایش داده نمی‌شوند
- مطمئن شوید پوشه `assets/images/` آپلود شده
- فایل `logo.jpg` باید در `assets/images/` باشد

---

## 📞 پشتیبانی

اگر مشکلی پیش آمد، لاگ خطا را ذخیره کنید و بفرستید.
