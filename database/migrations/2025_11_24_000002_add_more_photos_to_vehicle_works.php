<?php

use App\Core\Database;

class AddMorePhotosToVehicleWorks
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function up(): void
    {
        $this->db->execute("
            ALTER TABLE `vehicle_works`
            ADD COLUMN `photo2_path` VARCHAR(255) DEFAULT NULL COMMENT '추가 사진 2' AFTER `photo_path`,
            ADD COLUMN `photo3_path` VARCHAR(255) DEFAULT NULL COMMENT '추가 사진 3' AFTER `photo2_path`
        ");
    }

    public function down(): void
    {
        $this->db->execute("
            ALTER TABLE `vehicle_works`
            DROP COLUMN `photo3_path`,
            DROP COLUMN `photo2_path`
        ");
    }
}
