-- BioSight AI: Production-Safe MySQL Schema (Non-PII & Transient)
-- Designed for the 2026 Biomedical AI Hackathon

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- 1. Analysis Repository: Stores metadata-stripped research results
CREATE TABLE IF NOT EXISTS `analysis_logs` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `session_hash` CHAR(64) NOT NULL,      -- SHA-256 of PHP Session ID (No PII)
  `image_ref` VARCHAR(128) NOT NULL,     -- Randomized hex filename (No PII)
  `interpretation_blob` VARBINARY(16000),-- AES-256 Encrypted AI JSON (Primary storage)
  `interpretation` TEXT NULL,            -- Plain-text interpretation (Optional, for compatibility)
  `visual_features` JSON NULL,           -- Extracted visual features (Optional)
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `expires_at` TIMESTAMP DEFAULT (CURRENT_TIMESTAMP + INTERVAL 24 HOUR),
  INDEX `idx_session_hash` (`session_hash`),
  INDEX `idx_expiration` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- 2. Compliance Ledger: Tracks disclaimer acceptance for legal audit
CREATE TABLE IF NOT EXISTS `consent_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `session_hash` CHAR(64) NOT NULL,
  `disclaimer_version` VARCHAR(16) DEFAULT '1.0.0',
  `ip_masked` VARCHAR(45),               -- Masked Subnet (e.g. 192.168.1.xxx)
  `agreed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_session_consent` (`session_hash`)
) ENGINE=InnoDB;

-- 3. System Health Audit: Anonymous technical monitoring
CREATE TABLE IF NOT EXISTS `system_audit` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `event_type` ENUM('UPLOAD_SUCCESS', 'API_FAILURE', 'PURGE_JOB', 'SECURITY_ALERT') NOT NULL,
  `details` TEXT,
  `log_time` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 4. Automated Data Lifecycle Job
-- Ensures no research data persists beyond the 24-hour window
SET GLOBAL event_scheduler = ON;

DELIMITER //
CREATE EVENT IF NOT EXISTS `daily_security_cleanup`
ON SCHEDULE EVERY 1 HOUR
COMMENT 'Enforces 24-hour TTL on biomedical specimens'
DO BEGIN
    -- Log the cleanup start
    INSERT INTO `system_audit` (`event_type`, `details`) VALUES ('PURGE_JOB', 'Started hourly cleanup of expired analysis records.');

    -- Delete expired records
    DELETE FROM `analysis_logs` WHERE `expires_at` < NOW();
    
    -- Optimize table after deletion for performance
    OPTIMIZE TABLE `analysis_logs`;
END //
DELIMITER ;
