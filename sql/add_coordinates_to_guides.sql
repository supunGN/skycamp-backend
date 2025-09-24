-- Add latitude and longitude columns to guides table
-- This matches the structure used in the renters table

ALTER TABLE `guides` 
ADD COLUMN `latitude` decimal(10,8) DEFAULT NULL AFTER `price_per_day`,
ADD COLUMN `longitude` decimal(11,8) DEFAULT NULL AFTER `latitude`;

-- Add indexes for better performance on location-based queries
ALTER TABLE `guides` 
ADD INDEX `idx_guides_location` (`latitude`, `longitude`);
