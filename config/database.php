<?php
require_once __DIR__ . '/config.php';

/**
 * Database connection:
 * - PostgreSQL/Neon remains supported through DATABASE_URL.
 * - MySQL/MariaDB is supported for InfinityFree through DB_* settings.
 * PostgreSQL-specific query fragments used by the existing application are
 * normalized transparently when the active driver is MySQL.
 */
final class JametulhodaMySqlPDO extends PDO {
    public static function normalizeSql(string $sql): string {
        // PostgreSQL ILIKE is equivalent for the site's UTF-8 case-insensitive MySQL collation.
        $sql = preg_replace('/\bILIKE\b/i', 'LIKE', $sql) ?? $sql;

        // page_section stores comma-separated sections; replace PostgreSQL ANY(string_to_array(...)).
        $sql = preg_replace(
            "/\?\s*=\s*ANY\(string_to_array\(REPLACE\(([^,]+),\s*' ',\s*''\),\s*','\)\)/i",
            "FIND_IN_SET(?, REPLACE($1, ' ', '')) > 0",
            $sql
        ) ?? $sql;

        // ON CONFLICT DO NOTHING -> INSERT IGNORE for MySQL.
        if (preg_match('/\bON\s+CONFLICT(?:\s*\([^)]*\))?\s+DO\s+NOTHING\b/i', $sql)) {
            $sql = preg_replace('/\bON\s+CONFLICT(?:\s*\([^)]*\))?\s+DO\s+NOTHING\s*;?\s*$/i', '', $sql) ?? $sql;
            $sql = preg_replace('/^\s*INSERT\s+INTO\b/i', 'INSERT IGNORE INTO', $sql) ?? $sql;
        }

        // PostgreSQL interval literal used by the upload journal.
        $sql = preg_replace("/INTERVAL\s+'(\d+)\s+hours?'?/i", 'INTERVAL $1 HOUR', $sql) ?? $sql;

        return $sql;
    }

    public function prepare(string $query, array $options = []) {
        return parent::prepare(self::normalizeSql($query), $options);
    }

    public function query(string $query, ?int $fetchMode = null, mixed ...$fetchModeArgs) {
        $query = self::normalizeSql($query);
        if ($fetchMode === null) return parent::query($query);
        return parent::query($query, $fetchMode, ...$fetchModeArgs);
    }

    public function exec(string $statement) {
        return parent::exec(self::normalizeSql($statement));
    }
}

function databaseDriver(): string {
    $configured = strtolower(trim(env_value('DB_DRIVER')));
    if ($configured === 'mysql' || $configured === 'pgsql') return $configured;
    return env_value('DATABASE_URL') !== '' ? 'pgsql' : 'mysql';
}

function newDatabaseConnection(): PDO {
    if (databaseDriver() === 'mysql') {
        $host = env_value('DB_HOST', 'sql304.infinityfree.com');
        $port = (int)env_value('DB_PORT', '3306');
        $name = env_value('DB_NAME', 'if0_42959770_jametulhoda');
        $user = env_value('DB_USER', 'if0_42959770');
        $pass = env_value('DB_PASS');

        if ($pass === '') {
            throw new RuntimeException('MySQL password is not configured.');
        }
        if (!preg_match('/^[a-zA-Z0-9._:-]+$/', $host) || $port < 1 || $port > 65535) {
            throw new RuntimeException('Invalid MySQL connection settings.');
        }

        $dsn = 'mysql:host=' . $host . ';port=' . $port . ';dbname=' . rawurlencode($name) . ';charset=utf8mb4';
        return new JametulhodaMySqlPDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_PERSISTENT => false,
        ]);
    }

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
    $dsn = 'pgsql:host=' . $host . ';port=' . (int)($url['port'] ?? 5432) . ';dbname=' . $name . ';sslmode=' . $ssl . ';connect_timeout=10';
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
