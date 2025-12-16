-- Fix Database Structure for Multiple Publishers with Same Short Code
-- Run this SQL in phpMyAdmin or MySQL command line

-- Step 1: Check current indexes
SHOW INDEX FROM publisher_short_codes WHERE Column_name = 'short_code';

-- Step 2: Drop UNIQUE constraint on short_code (if exists)
-- The constraint might be named 'short_code' or 'short_code_UNIQUE' or similar
ALTER TABLE publisher_short_codes DROP INDEX short_code;

-- If above fails, try this:
-- ALTER TABLE publisher_short_codes DROP INDEX short_code_UNIQUE;

-- Step 3: Verify the constraint is removed
SHOW INDEX FROM publisher_short_codes WHERE Column_name = 'short_code';

-- Step 4: Now the PRIMARY KEY should be on (campaign_id, publisher_id) combination
-- Check if it exists:
SHOW INDEX FROM publisher_short_codes WHERE Key_name = 'PRIMARY';

-- If PRIMARY KEY doesn't exist on (campaign_id, publisher_id), add it:
-- First, check if there's an auto-increment id column
-- ALTER TABLE publisher_short_codes DROP PRIMARY KEY;
-- ALTER TABLE publisher_short_codes ADD PRIMARY KEY (campaign_id, publisher_id);

-- Step 5: Verify final structure
DESCRIBE publisher_short_codes;
SHOW INDEX FROM publisher_short_codes;
