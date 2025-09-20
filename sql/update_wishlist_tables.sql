-- Update wishlist_items table to include all required columns
ALTER TABLE `wishlist_items` 
ADD COLUMN `item_type` ENUM('equipment', 'location', 'guide') NOT NULL AFTER `wishlist_id`,
ADD COLUMN `item_id` int(11) NOT NULL AFTER `item_type`,
ADD COLUMN `description` text DEFAULT NULL AFTER `name`,
ADD COLUMN `image_url` varchar(500) DEFAULT NULL AFTER `description`,
ADD COLUMN `price` decimal(10,2) DEFAULT NULL AFTER `image_url`;

-- Add indexes for better performance
ALTER TABLE `wishlist_items` 
ADD INDEX `idx_item_type_id` (`item_type`, `item_id`),
ADD INDEX `idx_wishlist_id` (`wishlist_id`);

-- Update the primary key to be auto-increment
ALTER TABLE `wishlist_items` 
MODIFY COLUMN `wishlist_item_id` int(11) NOT NULL AUTO_INCREMENT;

-- Update the wishlists table primary key to be auto-increment
ALTER TABLE `wishlists` 
MODIFY COLUMN `wishlist_id` int(11) NOT NULL AUTO_INCREMENT;
