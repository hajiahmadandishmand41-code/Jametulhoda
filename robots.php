<?php
require_once __DIR__ . '/includes/functions.php';
header('Content-Type: text/plain; charset=utf-8');
echo "User-agent: *\n";
if (APP_ENV !== 'production' || !SITE_URL) { echo "Disallow: /\n"; }
else {
    echo 'Disallow: '.BASE_PATH."/admin/\n";
    echo 'Allow: '.BASE_PATH."/\n";
    echo 'Sitemap: '.rtrim(SITE_URL,'/')."/sitemap.xml\n";
}
