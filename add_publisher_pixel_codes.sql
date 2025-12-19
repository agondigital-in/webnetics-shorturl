-- Create publisher_pixel_codes table for publisher-wise conversion tracking
CREATE TABLE IF NOT EXISTS `publisher_pixel_codes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `campaign_id` INT NOT NULL,
    `publisher_id` INT NOT NULL,
    `pixel_code` VARCHAR(50) NOT NULL UNIQUE,
    `conversion_count` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`campaign_id`) REFERENCES `campaigns`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`publisher_id`) REFERENCES `publishers`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_campaign_publisher` (`campaign_id`, `publisher_id`),
    INDEX `idx_pixel_code` (`pixel_code`)
);

-- Update conversions table to include publisher_id
ALTER TABLE `conversions` ADD COLUMN `publisher_id` INT NULL AFTER `campaign_id`;
ALTER TABLE `conversions` ADD INDEX `idx_publisher_id` (`publisher_id`);
