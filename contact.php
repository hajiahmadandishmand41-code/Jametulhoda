<?php
/**
 * contact.php — فرم تماس با مدیر
 * اصلاح‌شده: ذخیره در دیتابیس با is_read، CSRF، اعتبارسنجی کامل
 */
$pageTitle = 'تماس با ما';
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
startSecureSession();

// ─── اطمینان از وجود جدول پیام‌ها (سازگار با is_read) ────────────────────────
function ensureContactTable(): void {
    static $done = false;
    if ($done) return;
    try {
        $db = getDB();
        $db->exec(
            "CREATE TABLE IF NOT EXISTS `contact_messages` (
                `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `name`       VARCHAR(200) NOT NULL,
                `email`      VARCHAR(200) DEFAULT NULL,
                `phone`      VARCHAR(50)  DEFAULT NULL,
                `subject`    VARCHAR(300) DEFAULT NULL,
                `message`    TEXT         NOT NULL,
                `ip_address` VARCHAR(45)  DEFAULT NULL,
                `is_read`    TINYINT(1)   NOT NULL DEFAULT 0,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_is_read` (`is_read`),
                KEY `idx_created` (`created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        // Migration ایمن: اگر جدول قدیمی با ستون status وجود دارد،
        // ستون is_read را اضافه می‌کنیم (اگر وجود نداشت)
        try {
            $check = $db->query("SHOW COLUMNS FROM contact_messages LIKE 'is_read'");
            if ($check->rowCount() === 0) {
                $db->exec("ALTER TABLE contact_messages ADD COLUMN `is_read` TINYINT(1) NOT NULL DEFAULT 0 AFTER `ip_address`");
                // مهاجرت از status به is_read اگر ستون status وجود داشت
                $statusCheck = $db->query("SHOW COLUMNS FROM contact_messages LIKE 'status'");
                if ($statusCheck->rowCount() > 0) {
                    $db->exec("UPDATE contact_messages SET is_read = CASE WHEN status IN ('read','replied') THEN 1 ELSE 0 END");
                }
            }
        } catch (\Throwable $e) { /* بی‌صدا رد شو */ }

        // اضافه کردن ip_address اگر وجود نداشت
        try {
            $ipCheck = $db->query("SHOW COLUMNS FROM contact_messages LIKE 'ip_address'");
            if ($ipCheck->rowCount() === 0) {
                $db->exec("ALTER TABLE contact_messages ADD COLUMN `ip_address` VARCHAR(45) DEFAULT NULL AFTER `message`");
            }
        } catch (\Throwable $e) { /* بی‌صدا رد شو */ }

        $done = true;
    } catch (PDOException $e) {
        error_log('ensureContactTable error: ' . $e->getMessage());
    }
}

ensureContactTable();

$success = false;
$errors  = [];
$formData = [];

// ─── پردازش فرم ──────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // بررسی CSRF
    if (!verifyCsrfToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $errors[] = 'خطای امنیتی. لطفاً صفحه را رفرش کرده و دوباره تلاش کنید.';
    } else {
        $name    = trim($_POST['name']    ?? '');
        $email   = trim($_POST['email']   ?? '');
        $phone   = trim($_POST['phone']   ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $message = trim($_POST['message'] ?? '');

        $formData = compact('name','email','phone','subject','message');

        // اعتبارسنجی
        if (mb_strlen($name, 'UTF-8') < 2) {
            $errors[] = 'نام باید حداقل ۲ حرف باشد.';
        }
        if (!$subject) {
            $errors[] = 'موضوع پیام الزامی است.';
        }
        if (mb_strlen($message, 'UTF-8') < 10) {
            $errors[] = 'متن پیام باید حداقل ۱۰ کاراکتر باشد.';
        }
        if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'آدرس ایمیل معتبر نیست.';
        }

        // دریافت IP
        $ip = trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']
            ?? $_SERVER['HTTP_X_REAL_IP']
            ?? $_SERVER['REMOTE_ADDR']
            ?? '0.0.0.0')[0]);

        // محدودیت نرخ: حداکثر ۳ پیام در ۳۰ دقیقه از یک IP
        if (empty($errors) && $ip) {
            try {
                $db     = getDB();
                // بررسی وجود ستون ip_address قبل از کوئری
                $rstmt  = $db->prepare(
                    "SELECT COUNT(*) FROM contact_messages
                     WHERE ip_address = ? AND created_at > DATE_SUB(NOW(), INTERVAL 30 MINUTE)"
                );
                $rstmt->execute([$ip]);
                if ((int)$rstmt->fetchColumn() >= 3) {
                    $errors[] = 'تعداد پیام‌های ارسالی بیش از حد مجاز است. لطفاً ۳۰ دقیقه دیگر تلاش کنید.';
                }
            } catch (PDOException $e) {
                // اگر DB مشکل داشت، ادامه بده
                error_log('Rate limit check error: ' . $e->getMessage());
            }
        }

        if (empty($errors)) {
            try {
                $db   = getDB();
                $stmt = $db->prepare(
                    "INSERT INTO contact_messages (name, email, phone, subject, message, ip_address, is_read, created_at)
                     VALUES (?, ?, ?, ?, ?, ?, 0, NOW())"
                );
                $stmt->execute([
                    $name,
                    $email   ?: null,
                    $phone   ?: null,
                    $subject ?: null,
                    $message,
                    $ip      ?: null,
                ]);
                $success  = true;
                $formData = []; // پاک کردن فرم
                // ابطال CSRF token برای جلوگیری از ارسال مجدد
                unset($_SESSION[CSRF_TOKEN_NAME]);
            } catch (PDOException $e) {
                error_log('Contact form save error: ' . $e->getMessage());
                $errors[] = 'خطا در ذخیره پیام. لطفاً دوباره تلاش کنید.';
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="breadcrumb-bar">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= siteUrl() ?>">صفحه اصلی</a></li>
                <li class="breadcrumb-item active">تماس با ما</li>
            </ol>
        </nav>
    </div>
</div>

<main class="py-5">
    <div class="container">
        <div class="row g-5">
            <!-- فرم تماس -->
            <div class="col-lg-7">
                <div class="page-header mb-4">
                    <h1 class="page-title"><i class="bi bi-envelope-fill ms-2 text-gold"></i>تماس با مدیریت</h1>
                    <div class="section-divider"></div>
                    <p class="text-muted mt-2">برای ارسال پیام، سوال یا پیشنهاد فرم زیر را پر کنید. در اسرع وقت پاسخ خواهیم داد.</p>
                </div>

                <?php if ($success): ?>
                <div class="alert alert-success d-flex align-items-center gap-2 mb-4" role="alert">
                    <i class="bi bi-check-circle-fill fs-5"></i>
                    <div>
                        <strong>پیام شما با موفقیت ارسال شد!</strong><br>
                        <span class="small">با تشکر از توجه شما. ما به زودی پاسخ خواهیم داد.</span>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($errors)): ?>
                <div class="alert alert-danger mb-4" role="alert">
                    <i class="bi bi-exclamation-triangle-fill ms-2"></i>
                    <strong>لطفاً خطاهای زیر را برطرف کنید:</strong>
                    <ul class="mb-0 mt-2">
                        <?php foreach ($errors as $e): ?>
                        <li><?= sanitize($e) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <form method="post" id="contactForm" novalidate>
                    <?= csrfField() ?>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold" for="cf_name">
                                نام و نام خانوادگی <span class="text-danger">*</span>
                            </label>
                            <input type="text" id="cf_name" name="name" class="form-control <?= (!empty($errors) && mb_strlen($formData['name']??'','UTF-8') < 2) ? 'is-invalid' : '' ?>"
                                value="<?= sanitize($formData['name'] ?? '') ?>"
                                placeholder="نام کامل شما"
                                required minlength="2" maxlength="200">
                            <div class="invalid-feedback">نام باید حداقل ۲ حرف باشد.</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold" for="cf_phone">شماره تماس</label>
                            <input type="tel" id="cf_phone" name="phone" class="form-control"
                                value="<?= sanitize($formData['phone'] ?? '') ?>"
                                placeholder="شماره تلفن (اختیاری)"
                                maxlength="30">
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-bold" for="cf_email">ایمیل</label>
                            <input type="email" id="cf_email" name="email" class="form-control"
                                value="<?= sanitize($formData['email'] ?? '') ?>"
                                placeholder="آدرس ایمیل (اختیاری)"
                                maxlength="200">
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-bold" for="cf_subject">
                                موضوع <span class="text-danger">*</span>
                            </label>
                            <input type="text" id="cf_subject" name="subject" class="form-control <?= (!empty($errors) && empty($formData['subject'])) ? 'is-invalid' : '' ?>"
                                value="<?= sanitize($formData['subject'] ?? '') ?>"
                                placeholder="موضوع پیام خود را بنویسید"
                                required maxlength="300">
                            <div class="invalid-feedback">موضوع پیام الزامی است.</div>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-bold" for="cf_message">
                                متن پیام <span class="text-danger">*</span>
                            </label>
                            <textarea id="cf_message" name="message" rows="6"
                                class="form-control <?= (!empty($errors) && mb_strlen($formData['message']??'','UTF-8') < 10) ? 'is-invalid' : '' ?>"
                                placeholder="پیام خود را اینجا بنویسید..."
                                required minlength="10"><?= sanitize($formData['message'] ?? '') ?></textarea>
                            <div class="invalid-feedback">متن پیام باید حداقل ۱۰ کاراکتر باشد.</div>
                        </div>

                        <div class="col-12">
                            <button type="submit" class="btn btn-primary btn-lg px-5" id="submitBtn">
                                <i class="bi bi-send-fill ms-2"></i>ارسال پیام
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- اطلاعات تماس -->
            <div class="col-lg-5">
                <div class="contact-info-box p-4 bg-soft rounded-xl h-100">
                    <h4 class="fw-bold mb-4"><i class="bi bi-info-circle ms-2 text-gold"></i>اطلاعات تماس</h4>

                    <div class="contact-item d-flex align-items-start gap-3 mb-4">
                        <div class="contact-icon-box">
                            <i class="bi bi-geo-alt-fill text-primary fs-4"></i>
                        </div>
                        <div>
                            <div class="fw-bold mb-1">آدرس</div>
                            <div class="text-muted"><?= sanitize(getSetting('address', SITE_ADDRESS)) ?></div>
                        </div>
                    </div>

                    <div class="contact-item d-flex align-items-start gap-3 mb-4">
                        <div class="contact-icon-box">
                            <i class="bi bi-telephone-fill text-success fs-4"></i>
                        </div>
                        <div>
                            <div class="fw-bold mb-1">تلفن</div>
                            <a href="tel:<?= sanitize(getSetting('phone', SITE_PHONE)) ?>" class="text-decoration-none text-muted">
                                <?= sanitize(getSetting('phone', SITE_PHONE)) ?>
                            </a>
                        </div>
                    </div>

                    <div class="contact-item d-flex align-items-start gap-3 mb-4">
                        <div class="contact-icon-box">
                            <i class="bi bi-envelope-fill text-warning fs-4"></i>
                        </div>
                        <div>
                            <div class="fw-bold mb-1">ایمیل</div>
                            <a href="mailto:<?= sanitize(getSetting('email', SITE_EMAIL)) ?>" class="text-decoration-none text-muted">
                                <?= sanitize(getSetting('email', SITE_EMAIL)) ?>
                            </a>
                        </div>
                    </div>

                    <div class="contact-item d-flex align-items-start gap-3">
                        <div class="contact-icon-box">
                            <i class="bi bi-clock-fill text-info fs-4"></i>
                        </div>
                        <div>
                            <div class="fw-bold mb-1">ساعات کاری</div>
                            <div class="text-muted">شنبه تا چهارشنبه: ۸ صبح تا ۵ عصر</div>
                        </div>
                    </div>

                    <!-- شبکه‌های اجتماعی -->
                    <?php $telegram = getSetting('social_telegram'); $youtube = getSetting('social_youtube'); ?>
                    <?php if ($telegram || $youtube): ?>
                    <hr>
                    <div class="social-links d-flex gap-3 mt-3">
                        <?php if ($telegram): ?>
                        <a href="<?= sanitize($telegram) ?>" target="_blank" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-telegram ms-1"></i>تلگرام
                        </a>
                        <?php endif; ?>
                        <?php if ($youtube): ?>
                        <a href="<?= sanitize($youtube) ?>" target="_blank" class="btn btn-outline-danger btn-sm">
                            <i class="bi bi-youtube ms-1"></i>یوتیوب
                        </a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
// اعتبارسنجی سمت کلاینت
(function() {
    var form = document.getElementById('contactForm');
    if (!form) return;
    form.addEventListener('submit', function(e) {
        if (!form.checkValidity()) {
            e.preventDefault();
            e.stopPropagation();
        }
        form.classList.add('was-validated');
        // غیرفعال کردن دکمه برای جلوگیری از ارسال مجدد
        var btn = document.getElementById('submitBtn');
        if (btn && form.checkValidity()) {
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm ms-2" role="status"></span>در حال ارسال...';
        }
    });
})();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
