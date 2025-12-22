-- Click Fingerprint Table for tracking without parameters
-- Run this SQL in phpMyAdmin or MySQL
-- NO DUPLICATE IPs - same user = update only, not new row

-- Step 1: Create fingerprint table with unique constraint
CREATE TABLE IF NOT EXISTS `click_fingerprints` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `click_id` VARCHAR(64) NOT NULL,
    `campaign_id` INT NOT NULL,
    `publisher_id` INT NOT NULL,
    `ip_address` VARCHAR(45) NOT NULL,
    `user_agent_hash` VARCHAR(64) NOT NULL,
    `fingerprint` VARCHAR(128) NOT NULL,
    `click_time` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `converted` BOOLEAN DEFAULT FALSE,
    `conversion_time` TIMESTAMP NULL,
    UNIQUE KEY `unique_fingerprint` (`campaign_id`, `publisher_id`, `fingerprint`),
    INDEX `idx_fingerprint` (`fingerprint`),
    INDEX `idx_click_time` (`click_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Step 2: Add attribution_window column to campaigns (safe for all MySQL versions)
-- This checks if column exists before adding it
DELIMITER $$

DROP PROCEDURE IF EXISTS AddAttributionWindow$$
CREATE PROCEDURE AddAttributionWindow()
BEGIN
    IF NOT EXISTS (
        SELECT * FROM information_schema.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'campaigns' 
        AND COLUMN_NAME = 'attribution_window'
    ) THEN
        ALTER TABLE campaigns 
        ADD COLUMN `attribution_window` INT DEFAULT 24 COMMENT 'Hours to attribute conversion after click';
    END IF;
END$$

DELIMITER ;

CALL AddAttributionWindow();
DROP PROCEDURE AddAttributionWindow;

-- Step 3: Create event to auto-delete old fingerprints (optional - run if you have EVENT privileges)
-- This keeps the table small by removing entries older than 48 hours
-- DROP EVENT IF EXISTS cleanup_old_fingerprints;
-- CREATE EVENT cleanup_old_fingerprints
-- ON SCHEDULE EVERY 1 HOUR
-- DO DELETE FROM click_fingerprints WHERE click_time < DATE_SUB(NOW(), INTERVAL 48 HOUR);
