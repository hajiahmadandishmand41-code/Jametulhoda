<section class="jhd-hero"><div class="container">
<div class="jhd-hero-grid"><div class="jhd-hero-copy">
<span class="jhd-eyebrow">مدرسه علمیه جامعه‌الهدی</span>
<h1>چراغی برای دانایی،<br><em>راهی به سوی تعالی</em></h1>
<p>اینجا، دانش با معنویت پیوند می‌خورد. همراه ما در مسیر فهم عمیق معارف اسلامی، پژوهش و پرورش اندیشه قدم بردارید.</p>
<div class="jhd-hero-cta"><a class="jhd-button" href="<?= siteUrl('lessons.php') ?>">کاوش در درس‌ها <i class="bi bi-arrow-left"></i></a><a class="jhd-text-link" href="<?= siteUrl('about.php') ?>">آشنایی با مدرسه <i class="bi bi-arrow-up-left"></i></a></div>
<p class="jhd-hero-note"><i class="bi bi-flower1"></i> علم، معرفت و تهذیب در پرتو قرآن و عترت</p>
</div><div class="jhd-hero-art" aria-label="نقش هندسی اسلامی و آیه اقرأ باسم ربک"><div class="jhd-arch">
<svg viewBox="0 0 400 400" aria-hidden="true"><defs><pattern id="islamic-tile" width="80" height="80" patternUnits="userSpaceOnUse"><path d="M40 0 80 40 40 80 0 40Z M12 12H68V68H12Z M0 0 80 80M80 0 0 80" fill="none" stroke="#d9bf83" stroke-width=".7"/></pattern></defs><rect width="400" height="400" fill="url(#islamic-tile)"/></svg>
<span class="jhd-calligraphy" lang="ar">اِقْرَأْ</span><p lang="ar">بِاسْمِ رَبِّكَ الَّذِي خَلَقَ</p><small>سوره علق · آیه نخست</small></div>
<div class="jhd-art-label"><i class="bi bi-book"></i><span><strong>دانش، دریچه‌ای به روشنایی</strong><small>منابع آموزشی برای جویندگان معرفت</small></span></div>
</div></div>
<div class="jhd-principles"><div><i class="bi bi-mortarboard"></i><span>آموزش اصیل علوم اسلامی</span></div><div><i class="bi bi-journal-richtext"></i><span>پژوهش و اندیشه‌ورزی</span></div><div><i class="bi bi-flower1"></i><span>اخلاق و پرورش معنوی</span></div></div>
</div></section>
<section class="jhd-resources"><div class="container">
<div class="jhd-section-heading"><div><span class="jhd-eyebrow">مسیرهای یادگیری</span><h2>دریچه‌ای به جهان معرفت</h2><p>از مطالعه و پژوهش تا شنیدن و آموختن؛ مسیر خود را انتخاب کنید.</p></div><a class="jhd-text-link" href="<?= siteUrl('search.php') ?>">همه منابع <i class="bi bi-arrow-left"></i></a></div>
<div class="jhd-resource-grid">
<?php foreach ([['lessons.php','mortarboard','دروس و آموزش','جلسات درسی و مباحث آموزشی علوم اسلامی','مشاهده درس‌ها'],['articles.php','journal-text','مقالات و پژوهش','نوشته‌ها و تأملاتی در معارف اسلامی','مطالعه مقالات'],['books.php','book','کتابخانه دیجیتال','کتاب‌ها و فایل‌های آموزشی برای مطالعه','ورود به کتابخانه'],['speeches.php','headphones','صوت و تصویر','سخنرانی‌ها و جلسات صوتی و تصویری','مشاهده رسانه‌ها']] as [$link,$icon,$title,$desc,$cta]): ?>
<a class="jhd-resource-card" href="<?= siteUrl($link) ?>"><i class="bi bi-<?= $icon ?>"></i><h3><?= $title ?></h3><p><?= $desc ?></p><span><?= $cta ?><i class="bi bi-arrow-left"></i></span></a>
<?php endforeach; ?>
</div></div></section>
