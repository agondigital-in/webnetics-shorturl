-- Add security_pin column to users table
-- Run this if you already have the database created

ALTER TABLE `users` ADD COLUMN `security_pin` VARCHAR(255) DEFAULT NULL AFTER `password`;

-- Update super admin with default PIN (1234) - hashed
-- You should change this after first login
UPDATE `users` SET `security_pin` = '$2y$10$8K1p/a0dR1Xy5.SBCGJQyO8q.5qFLQFhCXvZ8xKd3Y.lB2YmhQ5Aq' WHERE `role` = 'super_admin';
