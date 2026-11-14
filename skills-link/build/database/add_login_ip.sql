ALTER TABLE loginHistory ADD COLUMN ipAddress VARCHAR(45) NULL;
ALTER TABLE loginHistory ADD INDEX idx_login_date (loginDate);
