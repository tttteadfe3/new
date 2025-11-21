<?php

use App\Core\Database;

class AddDriverIdToVehicles
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function up()
    {
        $this->db->execute("
            ALTER TABLE `vehicles`
            ADD COLUMN `driver_id` INT NULL COMMENT '운전자 직원 ID' AFTER `status`,
            ADD CONSTRAINT `fk_vehicle_driver` FOREIGN KEY (`driver_id`) REFERENCES `hr_employees`(`id`) ON DELETE SET NULL;
        ");
    }

    public function down()
    {
        $this->db->execute("
            ALTER TABLE `vehicles`
            DROP FOREIGN KEY `fk_vehicle_driver`,
            DROP COLUMN `driver_id`;
        ");
    }
}
