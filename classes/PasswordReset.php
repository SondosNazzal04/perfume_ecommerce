<?php

class PasswordReset
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function createToken(int $userId): string
    {
        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);

        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare(
                "DELETE FROM password_resets WHERE user_id = ?"
            );
            $stmt->execute([$userId]);

            $stmt = $this->db->prepare(
                "INSERT INTO password_resets
                 (user_id, token_hash, expires_at)
                 VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 15 MINUTE))"
            );

            $stmt->execute([$userId, $tokenHash]);

            $this->db->commit();

            return $token;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $e;
        }
    }

    public function resetPassword(string $token, string $newPassword): bool
    {
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
            return false;
        }

        $tokenHash = hash('sha256', $token);

        $this->db->beginTransaction();

        try {
            $sql = "SELECT id, user_id
                FROM password_resets
                WHERE token_hash = ?
                AND expires_at > NOW()
                FOR UPDATE";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$tokenHash]);

            $reset = $stmt->fetch();

            if (!$reset) {
                $this->db->rollBack();
                return false;
            }

            $hashedPassword = password_hash(
                $newPassword,
                PASSWORD_DEFAULT
            );

            $stmt = $this->db->prepare(
                "UPDATE users SET password = ? WHERE id = ?"
            );

            $stmt->execute([
                $hashedPassword,
                $reset['user_id']
            ]);

            $stmt = $this->db->prepare(
                "DELETE FROM password_resets WHERE user_id = ?"
            );

            $stmt->execute([$reset['user_id']]);

            $this->db->commit();

            return true;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $e;
        }
    }
}
