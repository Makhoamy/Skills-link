SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE provinces (
    id      TINYINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name    VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB;

INSERT INTO provinces (name) VALUES
('Eastern Cape'),('Free State'),('Gauteng'),('KwaZulu-Natal'),
('Limpopo'),('Mpumalanga'),('North West'),('Northern Cape'),('Western Cape');

CREATE TABLE job_categories (
    id          SMALLINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    group_name  VARCHAR(60)  NOT NULL,
    name        VARCHAR(80)  NOT NULL UNIQUE,
    sort_order  SMALLINT UNSIGNED DEFAULT 0
) ENGINE=InnoDB;

INSERT INTO job_categories (group_name, name) VALUES
('Cleaning','Cleaner'),('Cleaning','Housekeeper'),('Cleaning','Domestic Worker'),('Cleaning','Laundry Worker'),
('Construction','General Worker'),('Construction','Construction Labourer'),('Construction','Painter'),
('Construction','Bricklayer Assistant'),('Construction','Plumber Assistant'),('Construction','Electrician Assistant'),
('Construction','Handyman'),
('Retail & Hospitality','Cashier'),('Retail & Hospitality','Shop Assistant'),('Retail & Hospitality','Kitchen Assistant'),
('Retail & Hospitality','Waiter/Waitress'),
('Logistics','Warehouse Worker'),('Logistics','Packer'),('Logistics','Loader/Offloader'),
('Logistics','Driver Assistant'),('Logistics','Delivery Rider'),
('Security','Security Guard'),
('Outdoor','Gardener'),('Outdoor','Car Washer'),('Outdoor','Farm Worker'),('Outdoor','Fruit Picker'),
('Care','Nanny/Babysitter'),('Care','Caregiver'),
('Other','Waste Collector'),('Other','Other');

CREATE TABLE nmw_rates (
    id                 SMALLINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    effective_from     DATE NOT NULL,
    hourly_rate_zar    DECIMAL(6,2) NOT NULL,
    source_note        VARCHAR(255) NULL,
    created_at         TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_nmw_effective (effective_from)
) ENGINE=InnoDB;

INSERT INTO nmw_rates (effective_from, hourly_rate_zar, source_note)
VALUES ('2025-03-01', 28.79, 'PLACEHOLDER - verify against the latest Dept. of Employment & Labour Gazette before launch');

CREATE TABLE users (
    id                   BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    email                VARCHAR(190) NOT NULL UNIQUE,
    password_hash        VARCHAR(255) NOT NULL,
    role                 ENUM('jobseeker','recruiter','admin') NOT NULL,
    phone                VARCHAR(20)  NULL,
    is_active            TINYINT(1) NOT NULL DEFAULT 1,
    is_verified          TINYINT(1) NOT NULL DEFAULT 0,
    popia_consent_at     DATETIME NULL,
    popia_policy_version VARCHAR(20) NULL,
    marketing_consent    TINYINT(1) NOT NULL DEFAULT 0,
    created_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at           DATETIME NULL,
    INDEX idx_users_deleted (deleted_at)
) ENGINE=InnoDB;

CREATE TABLE auth_tokens (
    id          BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id     BIGINT UNSIGNED NOT NULL,
    token_hash  CHAR(64) NOT NULL,
    expires_at  DATETIME NOT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_token_hash (token_hash)
) ENGINE=InnoDB;

CREATE TABLE password_resets (
    id          BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id     BIGINT UNSIGNED NOT NULL,
    token_hash  CHAR(64) NOT NULL,
    expires_at  DATETIME NOT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_reset_token (token_hash)
) ENGINE=InnoDB;

CREATE TABLE email_verifications (
    id          BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id     BIGINT UNSIGNED NOT NULL,
    code_hash   CHAR(64) NOT NULL,
    expires_at  DATETIME NOT NULL,
    attempts    TINYINT UNSIGNED NOT NULL DEFAULT 0,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY uq_verification_user (user_id)
) ENGINE=InnoDB;

CREATE TABLE loginHistory (
    loginID INT AUTO_INCREMENT PRIMARY KEY,
    userID BIGINT UNSIGNED NOT NULL,
    loginDate DATETIME DEFAULT CURRENT_TIMESTAMP,
    ipAddress VARCHAR(45) NULL,
    FOREIGN KEY (userID) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_login_date (loginDate)
) ENGINE=InnoDB;

CREATE TABLE consent_log (
    id              BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id         BIGINT UNSIGNED NULL,
    user_ref        CHAR(64) NULL,
    consent_type    VARCHAR(50) NOT NULL,
    consent_given   TINYINT(1) NOT NULL,
    policy_version  VARCHAR(20) NULL,
    ip_address      VARBINARY(16) NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_consent_user (user_id),
    INDEX idx_consent_ref (user_ref)
) ENGINE=InnoDB;

CREATE TABLE data_subject_requests (
    id            BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id       BIGINT UNSIGNED NULL,
    user_ref      CHAR(64) NULL,
    request_type  ENUM('access','correction','deletion','objection') NOT NULL,
    status        ENUM('pending','in_progress','completed','rejected') NOT NULL DEFAULT 'pending',
    notes         TEXT NULL,
    requested_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resolved_at   DATETIME NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_dsr_status (status),
    INDEX idx_dsr_user (user_id)
) ENGINE=InnoDB;

CREATE TABLE worker_profiles (
    user_id             BIGINT UNSIGNED PRIMARY KEY,
    first_name          VARCHAR(80) NOT NULL,
    last_name           VARCHAR(80) NOT NULL,
    gender              ENUM('Female','Male','Other','Prefer not to say') NULL,
    date_of_birth       DATE NULL,
    citizenship         ENUM('South African','Non-SA with work permit','Non-SA, no work permit') NULL,
    
    id_number_encrypted VARBINARY(512) NULL,
   
    id_number_hash      CHAR(64) NULL,
    drivers_license     VARCHAR(40) NULL,
    province_id         TINYINT UNSIGNED NULL,
    city                VARCHAR(80) NULL,
    suburb              VARCHAR(80) NULL,
    bio                 VARCHAR(600) NULL,
    years_experience    TINYINT UNSIGNED NULL,
    availability        ENUM('Immediately','Within 2 weeks','Temporary/casual only','Not available') NULL,
    profile_photo_path  VARCHAR(255) NULL,
    cv_path             VARCHAR(255) NULL,
    avg_rating          DECIMAL(3,2) NOT NULL DEFAULT 0.00,
    rating_count        INT UNSIGNED NOT NULL DEFAULT 0,
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (province_id) REFERENCES provinces(id),
    UNIQUE KEY uq_worker_id_hash (id_number_hash),
    INDEX idx_worker_location (province_id, city)
) ENGINE=InnoDB;
CREATE TABLE worker_skills (
    id          BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id     BIGINT UNSIGNED NOT NULL,
    category_id SMALLINT UNSIGNED NOT NULL,
    FOREIGN KEY (user_id) REFERENCES worker_profiles(user_id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES job_categories(id),
    UNIQUE KEY uq_worker_skill (user_id, category_id)
) ENGINE=InnoDB;

CREATE TABLE worker_equity_info (
    user_id           BIGINT UNSIGNED PRIMARY KEY,
    ethnicity         VARCHAR(40) NULL,
    has_disability    ENUM('Yes','No','Prefer not to say') NULL,
    disability_detail VARCHAR(255) NULL,
    consented_at      DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES worker_profiles(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE recruiter_profiles (
    user_id                 BIGINT UNSIGNED PRIMARY KEY,
    company_name            VARCHAR(150) NOT NULL,
    registration_number     VARCHAR(50) NULL,
    company_description     VARCHAR(1000) NULL,
    address                 VARCHAR(200) NULL,
    city                    VARCHAR(80) NULL,
    province_id             TINYINT UNSIGNED NULL,
    zip_code                VARCHAR(10) NULL,
    website                 VARCHAR(255) NULL,
    registration_doc_path   VARCHAR(255) NULL,
    contact_first_name      VARCHAR(80) NULL,
    contact_last_name       VARCHAR(80) NULL,
    contact_position        VARCHAR(80) NULL,
    verification_status     ENUM('unverified','pending','verified','rejected') NOT NULL DEFAULT 'pending',
    verified_at             DATETIME NULL,
    avg_rating              DECIMAL(3,2) NOT NULL DEFAULT 0.00,
    rating_count            INT UNSIGNED NOT NULL DEFAULT 0,
    created_at              TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at              TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (province_id) REFERENCES provinces(id),
    INDEX idx_recruiter_verification (verification_status)
) ENGINE=InnoDB;

CREATE TABLE jobs (
    id                BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    recruiter_id      BIGINT UNSIGNED NOT NULL,
    category_id       SMALLINT UNSIGNED NOT NULL,
    title             VARCHAR(120) NOT NULL,
    description       TEXT NOT NULL,
    province_id       TINYINT UNSIGNED NOT NULL,
    city              VARCHAR(80) NOT NULL,
    suburb            VARCHAR(80) NULL,
    employment_type   ENUM('full_time','part_time','temporary','once_off') NOT NULL,
    pay_amount        DECIMAL(10,2) NOT NULL,
    pay_period        ENUM('hourly','daily','weekly','monthly') NOT NULL,
    pay_currency      CHAR(3) NOT NULL DEFAULT 'ZAR',
    below_nmw_flag    TINYINT(1) NOT NULL DEFAULT 0,
    status            ENUM('draft','open','closed','filled') NOT NULL DEFAULT 'open',
    closes_at         DATE NULL,
    created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (recruiter_id) REFERENCES recruiter_profiles(user_id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES job_categories(id),
    FOREIGN KEY (province_id) REFERENCES provinces(id),
    CHECK (pay_amount >= 0),
    INDEX idx_jobs_location (province_id, city),
    INDEX idx_jobs_category (category_id),
    INDEX idx_jobs_status (status, created_at),
    FULLTEXT INDEX idx_jobs_search (title, description)
) ENGINE=InnoDB;

CREATE TABLE job_applications (
    id            BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    job_id        BIGINT UNSIGNED NOT NULL,
    worker_id     BIGINT UNSIGNED NOT NULL,
    status        ENUM('applied','shortlisted','rejected','hired','withdrawn') NOT NULL DEFAULT 'applied',
    cover_note    VARCHAR(500) NULL,
    applied_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
    FOREIGN KEY (worker_id) REFERENCES worker_profiles(user_id) ON DELETE CASCADE,
    UNIQUE KEY uq_one_application (job_id, worker_id)
) ENGINE=InnoDB;

CREATE TABLE work_adverts (
    id             BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    worker_id      BIGINT UNSIGNED NOT NULL,
    category_id    SMALLINT UNSIGNED NOT NULL,
    title          VARCHAR(120) NOT NULL,
    description    VARCHAR(800) NOT NULL,
    province_id    TINYINT UNSIGNED NOT NULL,
    city           VARCHAR(80) NOT NULL,
    rate_amount    DECIMAL(10,2) NULL,
    rate_period    ENUM('hourly','daily','weekly','per_job') NULL,
    contact_phone  VARCHAR(20) NOT NULL,
    photo_path     VARCHAR(255) NULL,
    status         ENUM('active','paused','expired') NOT NULL DEFAULT 'active',
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at     DATE NULL,
    FOREIGN KEY (worker_id) REFERENCES worker_profiles(user_id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES job_categories(id),
    FOREIGN KEY (province_id) REFERENCES provinces(id),
    CHECK (rate_amount IS NULL OR rate_amount >= 0),
    INDEX idx_adverts_location (province_id, city),
    INDEX idx_adverts_status (status, expires_at)
) ENGINE=InnoDB;

CREATE TABLE reviews (
    id                BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    job_id            BIGINT UNSIGNED NULL,
    reviewer_user_id  BIGINT UNSIGNED NOT NULL,
    reviewee_user_id  BIGINT UNSIGNED NOT NULL,
    rating            TINYINT UNSIGNED NOT NULL,
    comment           VARCHAR(600) NULL,
    created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE SET NULL,
    FOREIGN KEY (reviewer_user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewee_user_id) REFERENCES users(id) ON DELETE CASCADE,
    CHECK (rating BETWEEN 1 AND 5),
    UNIQUE KEY uq_one_review_per_job (job_id, reviewer_user_id, reviewee_user_id),
    INDEX idx_reviews_reviewee (reviewee_user_id)
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;
