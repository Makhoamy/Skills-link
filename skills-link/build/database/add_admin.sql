-- Add an admin straight into the database (instead of using create_admin.php).
--
-- 1. Make a bcrypt hash of the password you want (this prints it; do not store the plain password anywhere):
--      php -r "echo password_hash('Your-Strong-Pass1!', PASSWORD_BCRYPT), PHP_EOL;"
-- 2. Paste the hash below, change the email, then run this file against the skills_link database.
--
-- Admins cannot be created from the website. The sign-up page only offers jobseeker and recruiter.

INSERT INTO users (email, password_hash, role, is_active, is_verified, popia_consent_at)
VALUES ('admin@Skillslink.co.za', '$2y$10$Tt7TsB37oc.fJMeBbgyE2ugJEuzGyFnz/DC8yhSqfJk2stOePoTG6', 'admin', 1, 1, NOW());
