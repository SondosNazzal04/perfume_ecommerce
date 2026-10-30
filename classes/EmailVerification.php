<?php

class EmailVerification
{
    private PDO $db;
    private const OTP_TTL = 300;
    private const MAX_ATTEMPTS = 3;
    private const MAX_REQUESTS = 5;
    private const RESEND_COOLDOWN = 60;
    private const LOCK_DURATION = 900;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    private function generateOtp(): string
    {
        return (string) random_int(100000, 999999);
    }


    public function createOtp(int $userId): string|false
    {
        $sql = "SELECT id FROM email_verifications WHERE user_id = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);

        $existingOtp = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($existingOtp) {
            return false;
        }
        $otp = $this->generateOtp();


        $otpHash = password_hash($otp, PASSWORD_DEFAULT);
        $expiresAt = date('Y-m-d H:i:s', time() + self::OTP_TTL);
        $sentAt = date('Y-m-d H:i:s');

        $sql = "INSERT INTO email_verifications
        (user_id, otp_hash, expires_at, otp_last_sent_at)
        VALUES (?, ?, ?, ?)";


        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId, $otpHash, $expiresAt, $sentAt]);

            return $otp;
        } catch (PDOException $e) {
            if (($e->errorInfo[1] ?? null) == 1062) {
                return false;
            }

            throw $e;
        }
    }
    public function resendOtp(int $userId): string|false
    {
        $this->db->beginTransaction();

        try {
            $sql = "SELECT * FROM email_verifications
                WHERE user_id = ?
                FOR UPDATE";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);

            $record = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$record) {
                $this->db->rollBack();
                return false;
            }

            $now = time();

            if ($record['otp_resend_locked_until'] !== null) {
                if (strtotime($record['otp_resend_locked_until']) > $now) {
                    $this->db->rollBack();
                    return false;
                }

                $sql = "UPDATE email_verifications
                    SET otp_request_count = 0,
                        otp_resend_locked_until = NULL
                    WHERE user_id = ?";

                $stmt = $this->db->prepare($sql);
                $stmt->execute([$userId]);

                $record['otp_request_count'] = 0;
            }

            if ($now - strtotime($record['otp_last_sent_at']) < self::RESEND_COOLDOWN) {
                $this->db->rollBack();
                return false;
            }

            if ($record['otp_request_count'] >= self::MAX_REQUESTS) {
                $this->db->rollBack();
                return false;
            }

            $lockedUntil = null;

            if ((int)$record['otp_request_count'] + 1 === self::MAX_REQUESTS) {
                $lockedUntil = date('Y-m-d H:i:s', $now + self::LOCK_DURATION);
            }

            $otp = $this->generateOtp();
            $otpHash = password_hash($otp, PASSWORD_DEFAULT);
            $expiresAt = date('Y-m-d H:i:s', $now + self::OTP_TTL);
            $sentAt = date('Y-m-d H:i:s', $now);

            $sql = "UPDATE email_verifications
                SET otp_hash = ?,
                    expires_at = ?,
                    attempts = 0,
                    otp_request_count = otp_request_count + 1,
                    otp_last_sent_at = ?,
                    otp_resend_locked_until = ?
                WHERE user_id = ?";

            $stmt = $this->db->prepare($sql);

            $stmt->execute([
                $otpHash,
                $expiresAt,
                $sentAt,
                $lockedUntil,
                $userId
            ]);

            $this->db->commit();

            return $otp;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $e;
        }
    }
    public function verifyOtp(int $userId, string $otp): bool
    {
        $this->db->beginTransaction();

        try {
            $sql = "SELECT * FROM email_verifications
                WHERE user_id = ?
                FOR UPDATE";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);

            $record = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$record) {
                $this->db->rollBack();
                return false;
            }

            if (strtotime($record['expires_at']) <= time()) {
                $this->db->rollBack();
                return false;
            }

            if ($record['attempts'] >= self::MAX_ATTEMPTS) {
                $this->db->rollBack();
                return false;
            }

            if (!password_verify($otp, $record['otp_hash'])) {
                $sql = "UPDATE email_verifications
                    SET attempts = attempts + 1
                    WHERE user_id = ?";

                $stmt = $this->db->prepare($sql);
                $stmt->execute([$userId]);

                $this->db->commit();
                return false;
            }

            $verifiedAt = date('Y-m-d H:i:s');

            $sql = "UPDATE users
                SET email_verified_at = ?
                WHERE id = ?";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$verifiedAt, $userId]);

            $sql = "DELETE FROM email_verifications
                WHERE user_id = ?";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);

            $this->db->commit();

            return true;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $e;
        }
    }
    public function isEmailVerified(int $userId): bool
    {
        $sql = "SELECT email_verified_at
            FROM users
            WHERE id = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            return false;
        }

        return $user['email_verified_at'] !== null;
    }
}
