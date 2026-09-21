<?php
/** Shared, row-locked sessions; separate connection avoids committing application transactions. */
final class DatabaseSessionHandler implements SessionHandlerInterface, SessionUpdateTimestampHandlerInterface {
    private ?PDO $db = null;
    public function open(string $path, string $name): bool { return true; }
    public function close(): bool {
        if ($this->db?->inTransaction()) $this->db->commit();
        $this->db = null;
        return true;
    }
    private function connection(): PDO { return $this->db ??= newDatabaseConnection(); }
    public function validateId(string $id): bool {
        $s = $this->connection()->prepare('SELECT 1 FROM app_sessions WHERE id=? AND expires_at>NOW()');
        $s->execute([$id]); return (bool)$s->fetchColumn();
    }
    public function read(string $id): string|false {
        $db = $this->connection();
        // PHP may call read() more than once per request (e.g. after
        // session_regenerate_id()); keep the existing row-lock transaction
        // instead of failing with "already an active transaction".
        if (!$db->inTransaction()) $db->beginTransaction();
        // ON CONFLICT DO NOTHING is normalized to INSERT IGNORE on MySQL.
        $db->prepare("INSERT INTO app_sessions (id,data,expires_at) VALUES (?, '', NOW()) ON CONFLICT DO NOTHING")->execute([$id]);
        $s = $db->prepare('SELECT data, expires_at>NOW() AS valid FROM app_sessions WHERE id=? FOR UPDATE');
        $s->execute([$id]); $row = $s->fetch();
        return $row && $row['valid'] ? (base64_decode($row['data'], true) ?: '') : '';
    }
    public function write(string $id, string $data): bool {
        $payload = base64_encode($data);
        if (databaseDriver() === 'mysql') {
            // MySQL/MariaDB upsert; VALUES() refers to the row being inserted.
            $s = $this->connection()->prepare("INSERT INTO app_sessions (id,data,expires_at) VALUES (?,?,NOW()+INTERVAL 2 HOUR) ON DUPLICATE KEY UPDATE data=VALUES(data), expires_at=VALUES(expires_at)");
            return $s->execute([$id, $payload]);
        }
        $s = $this->connection()->prepare("INSERT INTO app_sessions (id,data,expires_at) VALUES (?, ?, NOW() + INTERVAL '2 hours') ON CONFLICT (id) DO UPDATE SET data=EXCLUDED.data, expires_at=EXCLUDED.expires_at");
        return $s->execute([$id, $payload]);
    }
    public function destroy(string $id): bool {
        return $this->connection()->prepare('DELETE FROM app_sessions WHERE id=?')->execute([$id]);
    }
    public function gc(int $max_lifetime): int|false {
        return $this->connection()->exec('DELETE FROM app_sessions WHERE expires_at<NOW()');
    }
    public function updateTimestamp(string $id, string $data): bool { return $this->write($id, $data); }
}
