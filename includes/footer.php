<?php if (($currentPage ?? '') === 'index.php'): ?>
<div class="container"><section class="jhd-invitation"><div><h2>آغاز یک مسیر روشن</h2><p>برای آشنایی با برنامه‌های آموزشی و شرایط پذیرش، با ما در ارتباط باشید.</p></div><a class="jhd-button" href="<?= siteUrl('contact.php') ?>">گفت‌وگو با مدرسه <i class="bi bi-arrow-left"></i></a></section></div>
<?php endif; ?>
</main>
<?php
/**
 * footer.php - فوتر عمومی سایت — نسخه ۲.۱ (+ Plyr.js)
 */
$footerCats    = getCategories();
$recentPosts   = getPosts(['limit' => 4]);
$siteName      = getSetting('site_name', SITE_NAME);
$siteAddress   = getSetting('address', SITE_ADDRESS);
$sitePhone     = getSetting('phone', SITE_PHONE);
$siteEmail     = getSetting('email', SITE_EMAIL);
?>
<footer class="main-footer">
    <div class="footer-top">
        <div class="container">
            <div class="row g-4">
                <!-- About -->
                <div class="col-lg-4 col-md-6">
                    <div class="footer-widget">
                        <div class="d-flex align-items-center mb-3 gap-3">
                            <img src="<?= imgUrl(getSetting('site_logo', 'assets/images/logo.jpg')) ?>" alt="لوگو" class="footer-logo" onerror="this.src='<?= siteUrl('assets/images/placeholder.svg') ?>'">
                            <h5 class="footer-title mb-0"><?= sanitize($siteName) ?></h5>
                        </div>
                        <p class="footer-text"><?= sanitize(getSetting('site_slogan', SITE_SLOGAN)) ?></p>
                        <p class="footer-text small"><?= sanitize(getSetting('about_short', 'مدرسه علمیه جامعه‌الهدی یکی از مراکز علوم دینی در افغانستان است.')) ?></p>
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
                            <li><a href="<?= siteUrl() ?>"><i class="bi bi-chevron-left"></i>صفحه اصلی</a></li>
                            <li><a href="<?= siteUrl('about.php') ?>"><i class="bi bi-chevron-left"></i>درباره ما</a></li>
                            <li><a href="<?= siteUrl('news.php') ?>"><i class="bi bi-chevron-left"></i>اخبار</a></li>
                            <li><a href="<?= siteUrl('lessons.php') ?>"><i class="bi bi-chevron-left"></i>درس‌ها</a></li>
                            <li><a href="<?= siteUrl('religious-activities.php') ?>"><i class="bi bi-chevron-left"></i>فعالیت‌های مذهبی</a></li>
                            <li><a href="<?= siteUrl('articles.php') ?>"><i class="bi bi-chevron-left"></i>مقالات</a></li>
                            <li><a href="<?= siteUrl('speeches.php') ?>"><i class="bi bi-chevron-left"></i>سخنرانی‌ها</a></li>
                            <li><a href="<?= siteUrl('books.php') ?>"><i class="bi bi-chevron-left"></i>کتاب‌ها</a></li>
                            <li><a href="<?= siteUrl('contact.php') ?>"><i class="bi bi-chevron-left"></i>تماس با ما</a></li>
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
                                <a href="<?= siteUrl('post.php?slug=' . urlencode($rp['slug'])) ?>">
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
                                <span>شنبه تا چهارشنبه: ۸ تا ۵</span>
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
                        سازنده: <strong>حاجی احمد صالحی</strong>
                        <span class="d-block" style="font-size:.72rem">فعالیت کننده</span>
                    </p>
                    <p class="mb-0 mt-1">
                        <a href="<?= siteUrl('admin/login.php') ?>" class="footer-admin-link" style="font-size:.75rem;opacity:.7;color:inherit;text-decoration:none">
                            <i class="bi bi-shield-lock ms-1"></i>مدیریت
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</footer>

<!-- Scroll To Top -->
<button id="scrollTop" title="بازگشت به بالا"><i class="bi bi-chevron-up"></i></button>

<!-- Scripts -->
<script src="<?= siteUrl('assets/vendor/bootstrap.bundle.min.js') ?>"></script>
<!-- Plyr Video Player JS -->
<script src="<?= siteUrl('assets/vendor/plyr.js') ?>"></script>
<script src="<?= siteUrl('assets/js/main.js') ?>"></script>
<script src="<?= siteUrl('assets/js/media-player.js') ?>" defer></script>
</body>
</html>
