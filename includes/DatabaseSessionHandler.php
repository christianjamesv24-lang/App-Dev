<?php

final class DatabaseSessionHandler implements SessionUpdateTimestampHandlerInterface {
    private mysqli $connection;

    public function __construct(mysqli $connection) {
        $this->connection = $connection;
        if (!$this->connection->query('SELECT 1 FROM php_sessions LIMIT 0')) {
            throw new RuntimeException(
                'Persistent session storage is unavailable. Apply database/schema.sql before serving requests.'
            );
        }
    }

    public function open(string $path, string $name): bool {
        return true;
    }

    public function close(): bool {
        return true;
    }

    public function validateId(string $id): bool {
        $expiry = time() - (int) ini_get('session.gc_maxlifetime');
        $stmt = $this->connection->prepare(
            'SELECT 1 FROM php_sessions WHERE id = ? AND last_activity >= ?'
        );
        if (!$stmt) {
            throw new RuntimeException('Unable to validate the session.');
        }
        $stmt->bind_param('si', $id, $expiry);
        if (!$stmt->execute()) {
            throw new RuntimeException('Unable to validate the session.');
        }
        $valid = $stmt->get_result()->num_rows === 1;
        $stmt->close();
        return $valid;
    }

    public function read(string $id): string|false {
        $stmt = $this->connection->prepare('SELECT data FROM php_sessions WHERE id = ?');
        if (!$stmt) {
            throw new RuntimeException('Unable to read the session.');
        }
        $stmt->bind_param('s', $id);
        if (!$stmt->execute()) {
            throw new RuntimeException('Unable to read the session.');
        }
        $row = $stmt->get_result()->fetch_row();
        $stmt->close();
        return $row[0] ?? '';
    }

    public function write(string $id, string $data): bool {
        $lastActivity = time();
        $stmt = $this->connection->prepare(
            'INSERT INTO php_sessions (id, data, last_activity) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE data = VALUES(data), last_activity = VALUES(last_activity)'
        );
        if (!$stmt) {
            throw new RuntimeException('Unable to store the session.');
        }
        $stmt->bind_param('ssi', $id, $data, $lastActivity);
        $saved = $stmt->execute();
        $stmt->close();
        if (!$saved) {
            throw new RuntimeException('Unable to store the session.');
        }
        return true;
    }

    public function updateTimestamp(string $id, string $data): bool {
        return $this->write($id, $data);
    }

    public function destroy(string $id): bool {
        $stmt = $this->connection->prepare('DELETE FROM php_sessions WHERE id = ?');
        if (!$stmt) {
            throw new RuntimeException('Unable to remove the session.');
        }
        $stmt->bind_param('s', $id);
        $deleted = $stmt->execute();
        $stmt->close();
        if (!$deleted) {
            throw new RuntimeException('Unable to remove the session.');
        }
        return true;
    }

    public function gc(int $max_lifetime): int|false {
        $expiry = time() - $max_lifetime;
        $stmt = $this->connection->prepare('DELETE FROM php_sessions WHERE last_activity < ?');
        if (!$stmt) {
            throw new RuntimeException('Unable to clean expired sessions.');
        }
        $stmt->bind_param('i', $expiry);
        if (!$stmt->execute()) {
            throw new RuntimeException('Unable to clean expired sessions.');
        }
        $deleted = $stmt->affected_rows;
        $stmt->close();
        return $deleted;
    }
}
