    -- Add conversion tracking columns to campaigns table
    ALTER TABLE `campaigns` 
    ADD COLUMN `pixel_code` VARCHAR(50) UNIQUE NULL AFTER `shortcode`,
    ADD COLUMN `conversion_count` INT DEFAULT 0 AFTER `click_count`;

    -- Create conversions table to track individual conversions
    CREATE TABLE IF NOT EXISTS `conversions` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `campaign_id` INT NOT NULL,
        `pixel_code` VARCHAR(50) NOT NULL,
        `ip_address` VARCHAR(45),
        `user_agent` TEXT,
        `referrer` TEXT,
        `converted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`campaign_id`) REFERENCES `campaigns`(`id`) ON DELETE CASCADE,
        INDEX `idx_pixel_code` (`pixel_code`),
        INDEX `idx_converted_at` (`converted_at`)
    );

    -- Create daily conversions table for stats
    CREATE TABLE IF NOT EXISTS `daily_conversions` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `campaign_id` INT NOT NULL,
        `conversion_date` DATE NOT NULL,
        `conversions` INT DEFAULT 0,
        FOREIGN KEY (`campaign_id`) REFERENCES `campaigns`(`id`) ON DELETE CASCADE,
        UNIQUE KEY `unique_campaign_date` (`campaign_id`, `conversion_date`),
        INDEX `idx_conversion_date` (`conversion_date`)
    );
