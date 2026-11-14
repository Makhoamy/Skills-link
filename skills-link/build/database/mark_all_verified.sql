-- Run once in phpMyAdmin (skills_link > SQL) so accounts created before this change can log in.
UPDATE users SET is_verified = 1 WHERE is_verified = 0;
