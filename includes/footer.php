<?php
// `$currentPage` was never set by any controller, so this invitation band never
// rendered. The homepage is now detected from the resolved route ('/' and the
// legacy '/index.php' spelling both count).
$jhdCurrentPath = function_exists('current_path') ? rtrim(current_path(), '/') : '';
$isHomePage = (($currentPage ?? '') === 'index.php')
    || in_array($jhdCurrentPath === '' ? '/' : $jhdCurrentPath, ['/', '/index.php'], true);
?>
<?php if ($isHomePage): ?>
<div class="container"><section class="jhd-invitation"><div><h2>آغاز یک مسیر روشن علمی و معرفتی</h2><p>برای پیگیری مطالب علمی و آموزشی عضو شوید یا با مدرسه در ارتباط باشید.</p></div><div class="d-flex flex-wrap gap-2"><a class="jhd-button" href="<?= registerUrl() ?>">ثبت‌نام <i class="bi bi-arrow-left"></i></a><a class="jhd-button jhd-button-ghost" href="<?= url('contact') ?>">گفت‌وگو با مدرسه</a></div></section></div>
<?php endif; ?>
</main>
<?php
/**
 * footer.php - فوتر عمومی سایت جامع جامعه‌الهدی
 */
$footerCats    = getCategories();
$recentPosts   = getPosts(['limit' => 4]);
$siteName      = getSetting('site_name', SITE_NAME);
if ($siteName === 'مدرسه علمیه جامعه‌الهدی') $siteName = SITE_NAME; // normalize the legacy installation default
$siteSlogan    = getSetting('site_slogan', SITE_SLOGAN);
if ($siteSlogan === 'علم، معرفت و تهذیب در پرتو قرآن و عترت') $siteSlogan = SITE_SLOGAN; // normalize the legacy installation default
$siteAddress   = getSetting('address', SITE_ADDRESS);
$sitePhone     = getSetting('phone', SITE_PHONE);
$siteEmail     = getSetting('email', SITE_EMAIL);
?>
<footer class="main-footer" role="contentinfo">
    <div class="footer-top">
        <div class="container">
            <div class="row g-4">
                <!-- About -->
                <div class="col-lg-4 col-md-6">
                    <div class="footer-widget">
                        <div class="d-flex align-items-center mb-3 gap-3">
                            <img src="<?= imgUrl(getSetting('site_logo', 'assets/img/logo.jpg')) ?>" alt="نشان <?= sanitize($siteName) ?>" class="footer-logo" onerror="this.src='<?= asset('img/placeholder.svg') ?>'">
                            <h5 class="footer-title mb-0"><?= sanitize($siteName) ?></h5>
                        </div>
                        <p class="footer-text"><?= sanitize($siteSlogan) ?></p>
                        <p class="footer-text small"><?= sanitize(getSetting('about_short', 'مدرسه علمیه جامعه‌الهدی یکی از مراکز علوم و معارف اسلامی در کابل، افغانستان است.')) ?></p>
                        <p class="footer-text small mt-2">
                            <i class="bi bi-person-fill ms-1 text-gold"></i>
                            مؤسس: آیت‌الله محمدحسین حلیمی
                        </p>
                        <div class="footer-social mt-3">
                            <?php if ($t = getSetting('social_telegram')): ?><a href="<?= sanitize(safeExternalUrl($t)) ?>" target="_blank" rel="noopener noreferrer" class="social-link" title="تلگرام"><i class="bi bi-telegram"></i></a><?php endif; ?>
                            <?php if ($y = getSetting('social_youtube')): ?><a href="<?= sanitize(safeExternalUrl($y)) ?>" target="_blank" rel="noopener noreferrer" class="social-link" title="یوتیوب"><i class="bi bi-youtube"></i></a><?php endif; ?>
                            <?php if ($i = getSetting('social_instagram')): ?><a href="<?= sanitize(safeExternalUrl($i)) ?>" target="_blank" rel="noopener noreferrer" class="social-link" title="اینستاگرام"><i class="bi bi-instagram"></i></a><?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Quick Links -->
                <div class="col-lg-2 col-md-6">
                    <div class="footer-widget">
                        <h5 class="footer-title">دسترسی سریع</h5>
                        <ul class="footer-links">
                            <li><a href="<?= url() ?>"><i class="bi bi-chevron-left"></i>صفحه اصلی</a></li>
                            <li><a href="<?= url('news') ?>"><i class="bi bi-chevron-left"></i>اخبار مدرسه</a></li>
                            <li><a href="<?= url('articles') ?>"><i class="bi bi-chevron-left"></i>مقالات علمی</a></li>
                            <li><a href="<?= url('reports') ?>"><i class="bi bi-chevron-left"></i>گزارش‌ها</a></li>
                            <li><a href="<?= url('events') ?>"><i class="bi bi-chevron-left"></i>رویدادها</a></li>
                            <li><a href="<?= url('books') ?>"><i class="bi bi-chevron-left"></i>کتابخانه</a></li>
                            <li><a href="<?= url('lessons') ?>"><i class="bi bi-chevron-left"></i>دروس حوزوی</a></li>
                            <li><a href="<?= url('research') ?>"><i class="bi bi-chevron-left"></i>پژوهش‌ها</a></li>
                            <li><a href="<?= url('media') ?>"><i class="bi bi-chevron-left"></i>رسانه (صوت و ویدیو)</a></li>
                            <li><a href="<?= url('topics') ?>"><i class="bi bi-chevron-left"></i>موضوعات دینی</a></li>
                            <li><a href="<?= url('qa') ?>"><i class="bi bi-chevron-left"></i>پرسش و پاسخ</a></li>
                            <li><a href="<?= url('about') ?>"><i class="bi bi-chevron-left"></i>درباره ما</a></li>
                            <li><a href="<?= url('contact') ?>"><i class="bi bi-chevron-left"></i>تماس با ما</a></li>
                        </ul>
                    </div>
                </div>

                <!-- Recent Posts -->
                <div class="col-lg-3 col-md-6">
                    <div class="footer-widget">
                        <h5 class="footer-title">آخرین مطالب</h5>
                        <ul class="footer-posts">
                            <?php foreach ($recentPosts as $rp): ?>
                            <li>
                                <a href="<?= postUrl($rp) ?>">
                                    <span class="footer-post-title"><?= sanitize($rp['title']) ?></span>
                                    <span class="footer-post-date"><i class="bi bi-calendar3 ms-1"></i><?= persianDate($rp['published_at'] ?? $rp['created_at']) ?></span>
                                </a>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>

                <!-- Contact -->
                <div class="col-lg-3 col-md-6">
                    <div class="footer-widget">
                        <h5 class="footer-title">تماس با ما</h5>
                        <ul class="footer-contact">
                            <li>
                                <i class="bi bi-geo-alt-fill"></i>
                                <span><?= sanitize($siteAddress) ?></span>
                            </li>
                            <li>
                                <i class="bi bi-telephone-fill"></i>
                                <a href="tel:<?= sanitize($sitePhone) ?>"><?= sanitize($sitePhone) ?></a>
                            </li>
                            <li>
                                <i class="bi bi-envelope-fill"></i>
                                <a href="mailto:<?= sanitize($siteEmail) ?>"><?= sanitize($siteEmail) ?></a>
                            </li>
                            <li>
                                <i class="bi bi-clock-fill"></i>
                                <span>شنبه تا چهارشنبه: ۸:۰۰ الی ۱۶:۳۰</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="footer-bottom">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-5 text-center text-md-start mb-2 mb-md-0">
                    <p class="mb-0">&copy; <?= date('Y') ?> <?= sanitize($siteName) ?> — تمامی حقوق محفوظ است.</p>
                </div>
                <div class="col-md-4 text-center mb-2 mb-md-0">
                    <p class="mb-0 small opacity-75">
                        <i class="bi bi-book-fill ms-1 text-gold"></i>
                        «طلب العلم فريضة على كل مسلم»
                    </p>
                </div>
                <div class="col-md-3 text-center text-md-end">
                    <p class="mb-0 footer-builder">
                        پورتال جامع: <strong><?= sanitize($siteName) ?></strong>
                    </p>
                    <p class="mb-0 mt-1 d-flex gap-2 justify-content-center justify-content-md-end" style="font-size:.78rem;opacity:.85">
                        <a href="<?= url('sitemap.xml') ?>" style="color:inherit;text-decoration:none">نقشه سایت</a>
                        <span>·</span>
                        <a href="<?= url('search') ?>" style="color:inherit;text-decoration:none">جستجو</a>
                        <span>·</span>
                        <a href="<?= url('login') ?>" style="color:inherit;text-decoration:none"><i class="bi bi-shield-lock ms-1"></i>ورود مدیریت</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</footer>

<!-- Scroll To Top -->
<button id="scrollTop" title="بازگشت به بالا" aria-label="بازگشت به بالای صفحه"><i class="bi bi-chevron-up"></i></button>

<!-- Scripts -->
<script src="<?= asset('vendor/bootstrap.bundle.min.js') ?>"></script>
<script src="<?= asset('vendor/plyr.js') ?>"></script>
<script src="<?= asset('js/main.js') ?>"></script>
<script src="<?= asset('js/media-player.js') ?>" defer></script>
</body>
</html>
