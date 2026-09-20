<?php
require_once __DIR__ . '/config.php';

/** A single PostgreSQL connection contract, compatible with Neon pooled URLs. */
function newDatabaseConnection(): PDO {
    $url = parse_url(env_value('DATABASE_URL'));
    if (!$url || !in_array($url['scheme'] ?? '', ['postgres', 'postgresql'], true)) {
        throw new RuntimeException('DATABASE_URL must be a PostgreSQL URL.');
    }
    parse_str($url['query'] ?? '', $options);
    $ssl = $options['sslmode'] ?? 'verify-full';
    if (!in_array($ssl, ['disable', 'require', 'verify-ca', 'verify-full'], true) ||
        (APP_ENV === 'production' && !in_array($ssl, ['verify-ca', 'verify-full'], true))) {
        throw new RuntimeException('Production database requires verified TLS.');
    }
    $host = $url['host'] ?? '';
    $name = rawurldecode(ltrim($url['path'] ?? '', '/'));
    if (!preg_match('/^[a-zA-Z0-9.:-]+$/', $host) || !preg_match('/^[\w-]+$/', $name)) {
        throw new RuntimeException('Invalid database host or name.');
    }
    $dsn = 'pgsql:host='.$host.';port='.(int)($url['port'] ?? 5432).';dbname='.$name.';sslmode='.$ssl.';connect_timeout=10';
    if ($ssl === 'verify-full' || $ssl === 'verify-ca') $dsn .= ';sslrootcert=/etc/ssl/certs/ca-certificates.crt';
    $pdo = new PDO($dsn, rawurldecode($url['user'] ?? ''), rawurldecode($url['pass'] ?? ''), [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_PERSISTENT => false,
    ]);
    $pdo->exec("SET TIME ZONE 'Asia/Kabul'");
    return $pdo;
}
function getDB(): PDO {
    static $pdo;
    return $pdo ??= newDatabaseConnection();
}
