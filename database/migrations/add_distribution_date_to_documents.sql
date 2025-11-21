-- Add distribution_date column to supply_distribution_documents table

ALTER TABLE `supply_distribution_documents` 
ADD COLUMN `distribution_date` DATE NOT NULL DEFAULT (CURDATE()) AFTER `title`;

-- Add index on distribution_date for faster queries
CREATE INDEX `idx_distribution_date` ON `supply_distribution_documents` (`distribution_date`);
