<?php

use App\Core\Database;

class AddDetailsToVehicles
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function up(): void
    {
        $this->db->execute("
            ALTER TABLE `vehicles`
            ADD COLUMN `release_date` DATE DEFAULT NULL COMMENT '출고일자' AFTER `year`,
            ADD COLUMN `vehicle_type` VARCHAR(50) DEFAULT NULL COMMENT '차종' AFTER `release_date`,
            ADD KEY `idx_vehicle_type` (`vehicle_type`)
        ");
    }

    public function down(): void
    {
        $this->db->execute("
            ALTER TABLE `vehicles`
            DROP COLUMN `vehicle_type`,
            DROP COLUMN `release_date`
        ");
    }
}
