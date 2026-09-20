# مدرسه علمیه جامعه‌الهدی

[![Deploy with Vercel](https://vercel.com/button)](https://vercel.com/new/clone?repository-url=https%3A%2F%2Fgithub.com%2Fhajiahmadandishmand41-code%2FJametulhoda&repository-name=Jametulhoda)

وب‌سایت آموزشی و محتوایی مدرسه علمیه جامعه‌الهدی، با PHP و MySQL.

> **امنیت:** اطلاعات اتصال دیتابیس نباید داخل Git ذخیره شود. مقادیر دیتابیس از Environment Variables خوانده می‌شوند.

## اجرای روی Vercel

این پروژه برای اجرای کانتینری روی Vercel با `Dockerfile.vercel` و `vercel.json` آماده شده است.

متغیرهای لازم:
- `DB_HOST`
- `DB_PORT`
- `DB_NAME`
- `DB_USER`
- `DB_PASS`
- `DB_CHARSET`
- `SITE_URL`

## اجرای InfinityFree

در InfinityFree نیز همین متغیرها باید در محیط مناسب سرور تنظیم شوند. اگر سرویس شما Environment Variables ارائه نمی‌کند، اطلاعات اتصال را در خارج از مخزن و طبق تنظیمات همان هاست مدیریت کنید.

## ساختار اصلی

```
/
├── config/
├── includes/
├── admin/
├── uploads/
├── assets/
├── database.sql
├── install.php
├── Dockerfile.vercel
└── vercel.json
```

## صفحات

| صفحه | آدرس |
|---|---|
| صفحه اصلی | `/` |
| اخبار | `/news.php` |
| درس‌ها | `/lessons.php` |
| مقالات | `/articles.php` |
| اطلاعیه‌ها | `/announcements.php` |
| سخنرانی‌ها | `/speeches.php` |
| برنامه‌ها | `/programs.php` |
| فعالیت‌های مذهبی | `/religious-activities.php` |
| درباره ما | `/about.php` |
| تماس | `/contact.php` |
| جستجو | `/search.php` |
| پنل مدیریت | `/admin/login.php` |

## نکته مهم دیتابیس

نسخه فعلی برنامه بر پایه **MySQL/PDO MySQL** نوشته شده است. بنابراین اتصال مستقیم آن به Neon PostgreSQL بدون تبدیل Schema و Queryهای MySQL باعث خطا می‌شود. مهاجرت به Neon باید به‌صورت جداگانه و کامل انجام شود.
