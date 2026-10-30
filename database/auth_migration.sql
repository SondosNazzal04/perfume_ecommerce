USE perfume_ecommerce;

-- Run this SELECT separately before the migration.
-- If it returns rows, stop and resolve duplicates first.
SELECT user_id, COUNT(*) AS record_count
FROM email_verifications
GROUP BY user_id
HAVING COUNT(*) > 1;



ALTER TABLE users
    MODIFY COLUMN phone VARCHAR(20) NULL;

ALTER TABLE email_verifications
    ADD CONSTRAINT uq_email_verifications_user UNIQUE (user_id);

UPDATE email_verifications
SET attempts = 0
WHERE attempts IS NULL;

ALTER TABLE email_verifications
    MODIFY COLUMN attempts TINYINT UNSIGNED NOT NULL DEFAULT 0;

ALTER TABLE email_verifications
    ADD COLUMN otp_request_count TINYINT UNSIGNED NOT NULL DEFAULT 1,
    ADD COLUMN otp_last_sent_at DATETIME NULL,
    ADD COLUMN otp_resend_locked_until DATETIME NULL DEFAULT NULL;

UPDATE email_verifications
SET otp_last_sent_at = COALESCE(created_at, CURRENT_TIMESTAMP)
WHERE otp_last_sent_at IS NULL;

ALTER TABLE email_verifications
    MODIFY COLUMN otp_last_sent_at DATETIME NOT NULL;

CREATE TABLE password_resets (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE
);

CREATE TABLE login_attempts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    attempt_key CHAR(64) NOT NULL,
    attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_attempt_key_time (attempt_key, attempted_at)
);