-- SIGNAL — leads table
-- Created: 2026-05-07
-- Used by: orbit/save_lead.php

CREATE TABLE IF NOT EXISTS leads (
    id              BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    source          ENUM('partner_quiz','catalog_product','catalog_brand','direct') NOT NULL DEFAULT 'partner_quiz',
    source_ref      VARCHAR(120) DEFAULT NULL,
    name            VARCHAR(120) NOT NULL,
    email           VARCHAR(160) NOT NULL,
    phone           VARCHAR(40) DEFAULT NULL,
    market          ENUM('ua','pl','ro','tr') NOT NULL,
    role            VARCHAR(40) NOT NULL,
    channels        JSON NOT NULL,
    intent_text     TEXT NOT NULL,
    lang            ENUM('uk','pl','ro','tr','en') NOT NULL DEFAULT 'uk',
    matched_to      JSON DEFAULT NULL,
    match_method    ENUM('manual','ai','none') NOT NULL DEFAULT 'none',
    status          ENUM('new','reviewed','matched','contacted','closed','spam') NOT NULL DEFAULT 'new',
    consent_pdpa    TINYINT(1) NOT NULL DEFAULT 0,
    user_agent      VARCHAR(300) DEFAULT NULL,
    ip_hash         CHAR(64) NOT NULL,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    matched_at      TIMESTAMP NULL DEFAULT NULL,
    INDEX idx_status (status, created_at),
    INDEX idx_email  (email),
    INDEX idx_ip_hash (ip_hash, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
