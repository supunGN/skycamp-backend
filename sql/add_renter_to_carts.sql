-- Add renter_id and renter_name columns to carts table
ALTER TABLE `carts` 
ADD COLUMN `renter_id` int(11) DEFAULT NULL AFTER `customer_id`,
ADD COLUMN `renter_name` varchar(200) DEFAULT NULL AFTER `renter_id`;

-- Add foreign key constraint
ALTER TABLE `carts` 
ADD CONSTRAINT `fk_carts_renter_id` 
FOREIGN KEY (`renter_id`) REFERENCES `renters`(`renter_id`) 
ON DELETE SET NULL ON UPDATE CASCADE;
