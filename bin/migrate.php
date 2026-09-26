<?php
require_once __DIR__ . '/../config/database.php';

function splitSqlStatements(string $sql): array {
    $sql = preg_replace('/^\xEF\xBB\xBF/', '', $sql) ?? $sql;
    $out = []; $buffer = ''; $quote = null; $len = strlen($sql);
    for ($i = 0; $i < $len; $i++) {
        $ch = $sql[$i]; $next = $i + 1 < $len ? $sql[$i + 1] : '';
        if ($quote !== null) {
            $buffer .= $ch;
            if ($ch === $quote) {
                if ($next === $quote) { $buffer .= $next; $i++; }
                elseif ($i === 0 || $sql[$i - 1] !== '\\') $quote = null;
            }
            continue;
        }
        // Skip -- line comments (MySQL requires whitespace/end after them) so a
        // semicolon inside a comment never splits a statement.
        if ($ch === '-' && $next === '-' && ($i + 2 >= $len || $sql[$i + 2] === ' ' || $sql[$i + 2] === "\t")) {
            while ($i < $len && $sql[$i] !== "\n") $i++;
            $buffer .= "\n";
            continue;
        }
        if ($ch === '#') {
            while ($i < $len && $sql[$i] !== "\n") $i++;
            $buffer .= "\n";
            continue;
        }
        if ($ch === "'" || $ch === '"' || $ch === '`') { $quote = $ch; $buffer .= $ch; continue; }
        if ($ch === ';') { $statement = trim($buffer); if ($statement !== '') $out[] = $statement; $buffer = ''; continue; }
        $buffer .= $ch;
    }
    $statement = trim($buffer); if ($statement !== '') $out[] = $statement;
    return $out;
}

function applyDatabaseSchema(PDO $db, string $schemaPath): int {
    if (!is_file($schemaPath)) throw new RuntimeException('Schema file not found.');
    $schema = file_get_contents($schemaPath);
    if ($schema === false) throw new RuntimeException('Unable to read schema file.');
    $statements = databaseDriver() === 'mysql' ? splitSqlStatements($schema) : [$schema];
    $applied = 0;
    foreach ($statements as $statement) {
        if (trim($statement) === '') continue;
        try {
            $db->exec($statement); $applied++;
        } catch (PDOException $e) {
            if (databaseDriver() !== 'mysql') throw $e;
            // MySQL has no IF NOT EXISTS for indexes; re-running the migrator
            // must not fail on objects that already exist (idempotent installs).
            $message = $e->getMessage();
            if (preg_match('/already exists|duplicate key name|duplicate entry|duplicate column/i', $message)) continue;
            throw $e;
        }
    }
    return $applied;
}

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$driver = databaseDriver();
$schemaPath = __DIR__ . '/../database/' . ($driver === 'mysql' ? 'database.mysql.sql' : 'database.postgres.sql');
$applied = applyDatabaseSchema(getDB(), $schemaPath);

// The canonical SQL is idempotent for new tables, but CREATE TABLE IF NOT EXISTS
// cannot repair a table that already existed on an older deployment. Reconcile
// the unified identity schema explicitly (columns, legacy roles/checks and indexes).
require_once __DIR__ . '/../includes/identity.php';
$identity = jhd_ensure_identity_schema(true);
if (empty($identity['ok'])) {
    throw new RuntimeException((string)($identity['error'] ?? 'Identity schema migration failed.'));
}

echo ($driver === 'mysql' ? 'MySQL' : 'PostgreSQL')
    . " schema applied: {$applied} statement(s); identity schema reconciled. Existing content is not deleted.\n";