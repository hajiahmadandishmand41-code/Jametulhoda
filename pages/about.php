<?php
/**
 * about.php — درباره مدرسه
 */
$pageTitle = 'درباره ما';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="breadcrumb-bar">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= siteUrl() ?>">صفحه اصلی</a></li>
                <li class="breadcrumb-item active">درباره ما</li>
            </ol>
        </nav>
    </div>
</div>
<div class="py-5">
    <div class="container">
        <div class="page-header mb-5">
            <h1 class="page-title"><i class="bi bi-info-circle ms-2 text-gold"></i>درباره مدرسه علمیه جامعه‌الهدی</h1>
            <div class="section-divider"></div>
        </div>
        <div class="row g-5 align-items-start">
            <div class="col-lg-7">
                <div class="about-content">
                    <h3 class="text-primary mb-3">معرفی مدرسه</h3>
                    <p><?= sanitize(getSetting('about_short', 'مدرسه علمیه جامعه‌الهدی یکی از مراکز علوم دینی در افغانستان است.')) ?></p>
                    <p>این مدرسه با هدف آموزش علوم اسلامی، تربیت طلاب، و ترویج فرهنگ قرآنی و اهل بیت (ع) تأسیس گردیده است. در این مدرسه علوم مختلف اسلامی از جمله فقه، اصول، تفسیر قرآن، حدیث، کلام، فلسفه و ادبیات عرب تدریس می‌شود.</p>

                    <h4 class="mt-4 mb-3 text-primary">اهداف مدرسه</h4>
                    <ul class="about-list">
                        <li><i class="bi bi-check-circle-fill text-success ms-2"></i>تربیت طلاب متعهد و آگاه به علوم اسلامی</li>
                        <li><i class="bi bi-check-circle-fill text-success ms-2"></i>ترویج فرهنگ قرآنی و ارزش‌های اهل بیت (ع)</li>
                        <li><i class="bi bi-check-circle-fill text-success ms-2"></i>ایجاد بستر مناسب برای تحقیق و پژوهش دینی</li>
                        <li><i class="bi bi-check-circle-fill text-success ms-2"></i>برگزاری برنامه‌های فرهنگی و مذهبی</li>
                        <li><i class="bi bi-check-circle-fill text-success ms-2"></i>تقویت هویت اسلامی در جامعه</li>
                    </ul>

                    <h4 class="mt-4 mb-3 text-primary">رشته‌های تحصیلی</h4>
                    <div class="row g-3">
                        <?php
                        $subjects = [
                            ['فقه و اصول','bi bi-book','text-primary'],
                            ['تفسیر قرآن','bi bi-journal-text','text-success'],
                            ['حدیث شناسی','bi bi-chat-quote','text-info'],
                            ['کلام و فلسفه','bi bi-lightbulb','text-warning'],
                            ['ادبیات عرب','bi bi-translate','text-danger'],
                            ['تاریخ اسلام','bi bi-clock-history','text-secondary'],
                        ];
                        foreach ($subjects as [$name, $icon, $color]):
                        ?>
                        <div class="col-6 col-md-4">
                            <div class="subject-box">
                                <i class="<?= $icon ?> <?= $color ?> mb-2 d-block" style="font-size:1.4rem"></i>
                                <span><?= $name ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="about-sidebar">
                    <div class="about-info-card mb-4">
                        <h5 class="about-card-title"><i class="bi bi-person-fill ms-2 text-gold"></i>مؤسس مدرسه</h5>
                        <p class="mb-0">آیت‌الله محمدحسین حلیمی</p>
                    </div>
                    <div class="contact-info-card">
                        <h5 class="about-card-title"><i class="bi bi-geo-alt-fill ms-2 text-gold"></i>اطلاعات تماس</h5>
                        <ul class="contact-info-list">
                            <li><i class="bi bi-geo-alt text-primary"></i><span><?= sanitize(getSetting('address', SITE_ADDRESS)) ?></span></li>
                            <li><i class="bi bi-telephone text-primary"></i><a href="tel:<?= getSetting('phone', SITE_PHONE) ?>"><?= sanitize(getSetting('phone', SITE_PHONE)) ?></a></li>
                            <li><i class="bi bi-envelope text-primary"></i><a href="mailto:<?= getSetting('email', SITE_EMAIL) ?>"><?= sanitize(getSetting('email', SITE_EMAIL)) ?></a></li>
                        </ul>
                        <div class="mt-3">
                            <?php if ($t = getSetting('social_telegram')): ?><a href="<?= sanitize($t) ?>" class="btn btn-sm me-1" style="background:#2ca5e0;color:#fff" target="_blank"><i class="bi bi-telegram ms-1"></i>تلگرام</a><?php endif; ?>
                            <?php if ($y = getSetting('social_youtube')): ?><a href="<?= sanitize($y) ?>" class="btn btn-sm me-1" style="background:#ff0000;color:#fff" target="_blank"><i class="bi bi-youtube ms-1"></i>یوتیوب</a><?php endif; ?>
                        </div>
                    </div>
                    <div class="mt-4">
                        <a href="<?= siteUrl('contact') ?>" class="btn btn-primary w-100"><i class="bi bi-envelope ms-1"></i>تماس با ما</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
