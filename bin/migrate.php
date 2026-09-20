<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../config/database.php';
getDB()->exec(file_get_contents(__DIR__ . '/../database.sql'));
echo "PostgreSQL schema applied. Existing content is not deleted.\n";
