-- Add order_id field to carts table for PayHere integration
ALTER TABLE carts ADD COLUMN order_id VARCHAR(100) DEFAULT NULL AFTER cart_id;

-- Add index for order_id lookups
ALTER TABLE carts ADD INDEX idx_carts_order_id (order_id);
