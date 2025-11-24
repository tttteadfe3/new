<?php

use App\Core\Database\Migration;

class AddPhotoColumnsToVehicleWorks extends Migration
{
    public function up(): void
    {
        $sql = "ALTER TABLE vehicle_works 
                ADD COLUMN photo2_path VARCHAR(255) NULL AFTER photo_path,
                ADD COLUMN photo3_path VARCHAR(255) NULL AFTER photo2_path";
        $this->db->execute($sql);
    }

    public function down(): void
    {
        $sql = "ALTER TABLE vehicle_works 
                DROP COLUMN photo2_path,
                DROP COLUMN photo3_path";
        $this->db->execute($sql);
    }
}
