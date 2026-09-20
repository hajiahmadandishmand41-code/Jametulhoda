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
        $db->beginTransaction();
        $db->prepare("INSERT INTO app_sessions (id,data,expires_at) VALUES (?, '', NOW()) ON CONFLICT DO NOTHING")->execute([$id]);
        $s = $db->prepare('SELECT data, expires_at>NOW() AS valid FROM app_sessions WHERE id=? FOR UPDATE');
        $s->execute([$id]); $row = $s->fetch();
        return $row && $row['valid'] ? (base64_decode($row['data'], true) ?: '') : '';
    }
    public function write(string $id, string $data): bool {
        $s = $this->connection()->prepare("INSERT INTO app_sessions (id,data,expires_at) VALUES (?, ?, NOW() + INTERVAL '2 hours') ON CONFLICT (id) DO UPDATE SET data=EXCLUDED.data, expires_at=EXCLUDED.expires_at");
        return $s->execute([$id, base64_encode($data)]);
    }
    public function destroy(string $id): bool {
        return $this->connection()->prepare('DELETE FROM app_sessions WHERE id=?')->execute([$id]);
    }
    public function gc(int $max_lifetime): int|false {
        return $this->connection()->exec('DELETE FROM app_sessions WHERE expires_at<NOW()');
    }
    public function updateTimestamp(string $id, string $data): bool { return $this->write($id, $data); }
}
