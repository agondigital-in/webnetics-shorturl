-- Click Fingerprint Table for tracking without parameters
-- This enables conversion tracking even when advertiser doesn't pass click_id

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
    INDEX `idx_fingerprint` (`fingerprint`),
    INDEX `idx_ip_campaign` (`ip_address`, `campaign_id`),
    INDEX `idx_click_time` (`click_time`),
    INDEX `idx_converted` (`converted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add attribution_window column to campaigns (skip if already exists)
-- This defines how many hours after click a conversion can be attributed
SET @dbname = DATABASE();
SET @tablename = 'campaigns';
SET @columnname = 'attribution_window';
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
   WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN `', @columnname, '` INT DEFAULT 24 COMMENT "Hours to attribute conversion after click"')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;
