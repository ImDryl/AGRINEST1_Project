-- Run manually in phpMyAdmin / MySQL if you prefer not to use Doctrine migrations.
-- Adds a dedicated payment_method column on the order table.

ALTER TABLE `order`
    ADD COLUMN payment_method VARCHAR(50) DEFAULT NULL AFTER status;

-- Backfill rows that still store payment inside status (e.g. "Pending · GCash").
-- Adjust the separator in the LIKE clause if your data uses a different character.

UPDATE `order`
SET
    payment_method = TRIM(SUBSTRING_INDEX(REPLACE(status, '·', '|'), '|', -1)),
    status = TRIM(SUBSTRING_INDEX(REPLACE(status, '·', '|'), '|', 1))
WHERE status LIKE '%·%' OR status LIKE '%|%';
