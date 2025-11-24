-- Add Vehicle Consumables menu
INSERT INTO `sys_menus` (`id`, `parent_id`, `name`, `url`, `icon`, `permission_key`, `display_order`) VALUES
(505, 500, '소모품 관리', '/vehicles/consumables', 'ri-shopping-cart-2-line', 'vehicle.consumable.view', 50);
