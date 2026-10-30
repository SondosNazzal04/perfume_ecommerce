<?php

class RateLimiter
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function isBlocked(string $key, int $limit = 5): bool
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*)
            FROM login_attempts
            WHERE attempt_key = ?
              AND attempted_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)
        ");

        $stmt->execute([$key]);

        return (int) $stmt->fetchColumn() >= $limit;
    }

    public function recordFailure(string $key): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO login_attempts (attempt_key)
            VALUES (?)
        ");

        $stmt->execute([$key]);
    }

    public function clearAttempts(string $key): void
    {
        $stmt = $this->db->prepare("
            DELETE FROM login_attempts
            WHERE attempt_key = ?
        ");

        $stmt->execute([$key]);
    }

    public function cleanup(): void
    {
        $this->db->exec("
            DELETE FROM login_attempts
            WHERE attempted_at < DATE_SUB(NOW(), INTERVAL 1 DAY)
        ");
    }
}