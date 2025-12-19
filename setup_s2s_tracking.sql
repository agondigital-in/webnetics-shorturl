-- Setup S2S (Server-to-Server) Conversion Tracking

-- Add click_id column to track clicks for S2S
ALTER TABLE `clicks` ADD COLUMN IF NOT EXISTS `click_id` VARCHAR(64) UNIQUE NULL AFTER `id`;
ALTER TABLE `clicks` ADD INDEX IF NOT EXISTS `idx_click_id` (`click_id`);

-- Create clicks table if not exists (for storing click data with click_id)
CREATE TABLE IF NOT EXISTS `clicks` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `click_id` VARCHAR(64) UNIQUE NOT NULL,
    `campaign_id` INT NOT NULL,
    `publisher_id` INT NOT NULL,
    `ip_address` VARCHAR(45),
    `user_agent` TEXT,
    `referrer` TEXT,
    `clicked_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_click_id` (`click_id`),
    INDEX `idx_campaign_publisher` (`campaign_id`, `publisher_id`)
);

-- Create S2S conversions table
CREATE TABLE IF NOT EXISTS `s2s_conversions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `click_id` VARCHAR(64) NOT NULL,
    `campaign_id` INT NOT NULL,
    `publisher_id` INT NOT NULL,
    `transaction_id` VARCHAR(100) NULL,
    `payout` DECIMAL(10,2) NULL,
    `status` ENUM('pending', 'approved', 'rejected') DEFAULT 'approved',
    `ip_address` VARCHAR(45),
    `converted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_click_conversion` (`click_id`),
    INDEX `idx_campaign_id` (`campaign_id`),
    INDEX `idx_publisher_id` (`publisher_id`),
    INDEX `idx_status` (`status`)
);
