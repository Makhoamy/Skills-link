-- Email verification codes (run once on an existing database; schema.sql already has this for new installs).
CREATE TABLE IF NOT EXISTS email_verifications (
    id          BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id     BIGINT UNSIGNED NOT NULL,
    code_hash   CHAR(64) NOT NULL,
    expires_at  DATETIME NOT NULL,
    attempts    TINYINT UNSIGNED NOT NULL DEFAULT 0,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY uq_verification_user (user_id)
) ENGINE=InnoDB;

-- Accounts that already exist were never verified. Each person will be asked for a code the next time they use the site.
-- If you would rather trust them, uncomment the next line:
-- UPDATE users SET is_verified = 1 WHERE is_verified = 0;

-- Before going live, check who has an admin account. Anyone could have created one through the old sign-up form:
--   SELECT id, email, created_at FROM users WHERE role = 'admin';
-- Remove any you do not recognise:
--   DELETE FROM users WHERE id = <id>;
