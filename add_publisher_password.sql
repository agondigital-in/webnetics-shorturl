-- Add password column to publishers table
ALTER TABLE publishers ADD COLUMN password VARCHAR(255) DEFAULT NULL AFTER email;

-- Update existing publishers with a default password (change this later!)
-- Default password: "publisher123" (hashed)
UPDATE publishers SET password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' WHERE password IS NULL;

-- Note: This is the same hash as the default admin password for testing
-- Password: "password"
-- You should change this for each publisher after adding them
