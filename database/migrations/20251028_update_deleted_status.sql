-- Update existing 'deleted' status to 'pending_deleted' for data consistency.
UPDATE `illegal_disposal_cases2`
SET `status` = '대기삭제'
WHERE `status` = '삭제';
