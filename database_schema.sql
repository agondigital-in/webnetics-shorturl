-- Ads Platform Database Schema
-- Database: webnetics-shorturl

-- Create database
CREATE DATABASE IF NOT EXISTS `webnetics-shorturl-shorturl`;
USE `webnetics-shorturl-shorturl`;

-- Table: users
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) UNIQUE NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `security_pin` VARCHAR(255) DEFAULT NULL,
    `role` ENUM('super_admin', 'admin', 'publisher') NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table: advertisers
CREATE TABLE IF NOT EXISTS `advertisers` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) UNIQUE NOT NULL,
    `company` VARCHAR(100),
    `phone` VARCHAR(20),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table: publishers
CREATE TABLE IF NOT EXISTS `publishers` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) UNIQUE NOT NULL,
    `website` VARCHAR(255),
    `phone` VARCHAR(20),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table: campaigns
CREATE TABLE IF NOT EXISTS `campaigns` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `shortcode` VARCHAR(20) UNIQUE NOT NULL,
    `target_url` TEXT NOT NULL,
    `start_date` DATE NOT NULL,
    `end_date` DATE NOT NULL,
    `advertiser_payout` DECIMAL(10, 2) DEFAULT 0.00,
    `publisher_payout` DECIMAL(10, 2) DEFAULT 0.00,
    `campaign_type` ENUM('CPR', 'CPL', 'CPC', 'CPM', 'CPS', 'None') DEFAULT 'None',
    `target_leads` INT DEFAULT 0,
    `validated_leads` INT DEFAULT 0,
    `click_count` INT DEFAULT 0,
    `status` ENUM('active', 'inactive') DEFAULT 'active',
    `payment_status` ENUM('pending', 'completed') DEFAULT 'pending',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table: campaign_advertisers (Junction table)
CREATE TABLE IF NOT EXISTS `campaign_advertisers` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `campaign_id` INT NOT NULL,
    `advertiser_id` INT NOT NULL,
    `assigned_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`campaign_id`) REFERENCES `campaigns`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`advertiser_id`) REFERENCES `advertisers`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_campaign_advertiser` (`campaign_id`, `advertiser_id`)
);

-- Table: campaign_publishers (Junction table)
CREATE TABLE IF NOT EXISTS `campaign_publishers` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `campaign_id` INT NOT NULL,
    `publisher_id` INT NOT NULL,
    `assigned_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`campaign_id`) REFERENCES `campaigns`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`publisher_id`) REFERENCES `publishers`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_campaign_publisher` (`campaign_id`, `publisher_id`)
);

-- Insert default super admin user
INSERT INTO `users` (`username`, `password`, `role`) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'super_admin');

-- Sample data (optional)
-- INSERT INTO `advertisers` (`name`, `email`, `company`, `phone`) VALUES 
-- ('Sample Advertiser', 'advertiser@example.com', 'Advertise Inc.', '123-456-7890');
-- 
-- INSERT INTO `publishers` (`name`, `email`, `website`, `phone`) VALUES 
-- ('Sample Publisher', 'publisher@example.com', 'https://publisher.com', '098-765-4321');