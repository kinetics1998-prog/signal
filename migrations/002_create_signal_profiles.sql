-- SIGNAL — signal_profiles table
-- Created: 2026-05-10
-- Used by: orbit/save_profile.php (intake from orbit/onboarding.html)
-- DB: u227106289_signal

CREATE TABLE IF NOT EXISTS signal_profiles (
    id              BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name            VARCHAR(120) NOT NULL,
    surname         VARCHAR(120) DEFAULT NULL,
    company         VARCHAR(200) DEFAULT NULL,
    role            VARCHAR(100) DEFAULT NULL,
    email           VARCHAR(160) NOT NULL,
    telegram        VARCHAR(100) DEFAULT NULL,
    markets         VARCHAR(100) DEFAULT NULL,
    niche           VARCHAR(200) DEFAULT NULL,
    about           TEXT,
    scale           VARCHAR(60) DEFAULT NULL,
    looking_types   VARCHAR(500) DEFAULT NULL,
    looking_text    TEXT,
    looking_geo     VARCHAR(200) DEFAULT NULL,
    offering_types  VARCHAR(500) DEFAULT NULL,
    offering_text   TEXT,
    ideal_partner   VARCHAR(300) DEFAULT NULL,
    lang            ENUM('uk','pl','ro','tr','en') NOT NULL DEFAULT 'uk',
    status          ENUM('waitlist','active','paused','removed') NOT NULL DEFAULT 'waitlist',
    tier            ENUM('seed','member','pro') NOT NULL DEFAULT 'member',
    consent_pdpa    TINYINT(1) NOT NULL DEFAULT 0,
    user_agent      VARCHAR(300) DEFAULT NULL,
    ip_hash         CHAR(64) NOT NULL,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_email (email),
    INDEX idx_status (status, created_at),
    INDEX idx_ip_hash (ip_hash, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
