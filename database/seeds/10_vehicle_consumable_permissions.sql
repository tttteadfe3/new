-- Add Vehicle Consumables permissions
INSERT INTO `sys_permissions` (`id`, `key`, `description`) VALUES
(157, 'vehicle.consumable.view', '차량 소모품 조회'),
(158, 'vehicle.consumable.manage', '차량 소모품 관리 (등록/수정/삭제)'),
(159, 'vehicle.consumable.stock', '차량 소모품 입출고 처리');
