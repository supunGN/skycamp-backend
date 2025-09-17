-- Add missing columns to inactive_users table
ALTER TABLE `inactive_users` 
ADD COLUMN `first_name` varchar(100) NOT NULL AFTER `email`,
ADD COLUMN `last_name` varchar(100) NOT NULL AFTER `first_name`,
ADD COLUMN `phone_number` varchar(20) DEFAULT NULL AFTER `last_name`;

-- Update existing record with placeholder values (you may want to update this with actual data)
UPDATE `inactive_users` 
SET `first_name` = 'Unknown', `last_name` = 'User', `phone_number` = '000-000-0000' 
WHERE `first_name` IS NULL OR `first_name` = '';
