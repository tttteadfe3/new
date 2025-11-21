-- Add status columns to supply_distribution_documents table
ALTER TABLE `supply_distribution_documents`
ADD COLUMN `status` VARCHAR(20) NOT NULL DEFAULT '완료' AFTER `distribution_date`,
ADD COLUMN `cancel_reason` TEXT NULL AFTER `status`,
ADD COLUMN `cancelled_at` DATETIME NULL AFTER `cancel_reason`;

-- Add index for status
CREATE INDEX `idx_status` ON `supply_distribution_documents` (`status`);
