<?php

use App\Core\Database;

class CreateVehicleManagementTables
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function up(): void
    {
        // 1. 차량 기본 정보 테이블
        $this->db->execute("
            CREATE TABLE `vehicles` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `vehicle_number` VARCHAR(20) NOT NULL COMMENT '차량번호',
                `model` VARCHAR(255) NOT NULL COMMENT '차종/모델',
                `payload_capacity` VARCHAR(50) DEFAULT NULL COMMENT '적재량',
                `year` YEAR DEFAULT NULL COMMENT '연식',
                `department_id` INT DEFAULT NULL COMMENT '배정 부서 ID',
                `driver_employee_id` INT DEFAULT NULL COMMENT '담당 운전원 ID',
                `status_code` VARCHAR(50) NOT NULL DEFAULT '정상' COMMENT '차량 상태 (정상, 수리중, 폐차)',
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY `uq_vehicle_number` (`vehicle_number`),
                KEY `fk_vehicle_department` (`department_id`),
                KEY `fk_vehicle_driver` (`driver_employee_id`),
                CONSTRAINT `fk_vehicle_department` FOREIGN KEY (`department_id`) REFERENCES `hr_departments` (`id`) ON DELETE SET NULL,
                CONSTRAINT `fk_vehicle_driver` FOREIGN KEY (`driver_employee_id`) REFERENCES `hr_employees` (`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='차량 기본 정보'
        ");

        // 2. 차량 작업 통합 테이블 (고장 + 정비)
        $this->db->execute("
            CREATE TABLE `vehicle_works` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `vehicle_id` INT NOT NULL COMMENT '차량 ID',
                `type` VARCHAR(20) NOT NULL COMMENT '작업 유형 (고장, 정비)',
                `status` VARCHAR(20) NOT NULL DEFAULT '신고' COMMENT '상태 (신고, 처리결정, 작업중, 완료)',
                
                -- 신고 정보
                `reporter_id` INT NOT NULL COMMENT '신고자 (운전원) ID',
                `work_item` VARCHAR(100) NOT NULL COMMENT '작업 항목',
                `description` TEXT COMMENT '상세 내용',
                `mileage` INT DEFAULT NULL COMMENT '주행거리',
                `photo_path` VARCHAR(255) DEFAULT NULL COMMENT '사진 경로',
                
                -- 처리 정보 (고장만 해당)
                `repair_type` VARCHAR(20) DEFAULT NULL COMMENT '수리 유형 (자체수리, 외부수리)',
                `decided_at` DATETIME DEFAULT NULL COMMENT '처리결정 일시',
                `decided_by` INT DEFAULT NULL COMMENT '결정자 ID',
                
                -- 작업 정보
                `parts_used` TEXT COMMENT '사용 부품',
                `cost` DECIMAL(10, 2) COMMENT '비용',
                `worker_id` INT DEFAULT NULL COMMENT '작업자 ID',
                `repair_shop` VARCHAR(255) DEFAULT NULL COMMENT '외부 수리업체',
                
                -- 완료 정보
                `completed_at` DATETIME DEFAULT NULL COMMENT '작업 완료 일시',
                `confirmed_at` DATETIME DEFAULT NULL COMMENT '확인 일시',
                `confirmed_by` INT DEFAULT NULL COMMENT '확인자 ID',
                
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                
                KEY `idx_type_status` (`type`, `status`),
                KEY `fk_work_vehicle` (`vehicle_id`),
                KEY `fk_work_reporter` (`reporter_id`),
                CONSTRAINT `fk_work_vehicle` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE CASCADE,
                CONSTRAINT `fk_work_reporter` FOREIGN KEY (`reporter_id`) REFERENCES `hr_employees` (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='차량 작업 (고장+정비 통합)'
        ");

        // 3. 차량 소모품 테이블
        $this->db->execute("
            CREATE TABLE `vehicle_consumables` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `name` VARCHAR(100) NOT NULL COMMENT '소모품명',
                `description` TEXT COMMENT '설명',
                `unit` VARCHAR(20) COMMENT '단위',
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='차량 소모품 정보'
        ");

        // 4. 차량 소모품 교체 이력 테이블
        $this->db->execute("
            CREATE TABLE `vehicle_consumable_logs` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `vehicle_id` INT NOT NULL COMMENT '차량 ID',
                `consumable_id` INT NOT NULL COMMENT '소모품 ID',
                `quantity` DECIMAL(10, 2) NOT NULL COMMENT '수량',
                `replaced_by` INT DEFAULT NULL COMMENT '교체자 (직원) ID',
                `replaced_at` DATETIME NOT NULL COMMENT '교체 일시',
                `mileage` INT DEFAULT NULL COMMENT '교체 시 주행거리',
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                KEY `fk_log_vehicle` (`vehicle_id`),
                KEY `fk_log_consumable` (`consumable_id`),
                KEY `fk_log_employee` (`replaced_by`),
                CONSTRAINT `fk_log_vehicle` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE CASCADE,
                CONSTRAINT `fk_log_consumable` FOREIGN KEY (`consumable_id`) REFERENCES `vehicle_consumables` (`id`) ON DELETE CASCADE,
                CONSTRAINT `fk_log_employee` FOREIGN KEY (`replaced_by`) REFERENCES `hr_employees` (`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='차량 소모품 교체 이력'
        ");

        // 5. 차량 정기검사 테이블
        $this->db->execute("
            CREATE TABLE `vehicle_inspections` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `vehicle_id` INT NOT NULL COMMENT '차량 ID',
                `inspection_date` DATE NOT NULL COMMENT '검사일',
                `expiry_date` DATE NOT NULL COMMENT '만료일',
                `inspector_name` VARCHAR(100) COMMENT '검사자',
                `result` VARCHAR(50) NOT NULL COMMENT '검사 결과',
                `cost` DECIMAL(10, 2) COMMENT '검사 비용',
                `document_path` VARCHAR(255) DEFAULT NULL COMMENT '검사 증명서 파일 경로',
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                KEY `fk_inspection_vehicle` (`vehicle_id`),
                KEY `idx_expiry_date` (`expiry_date`),
                CONSTRAINT `fk_inspection_vehicle` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='차량 정기검사'
        ");

        // 6. 차량 관련 문서 테이블
        $this->db->execute("
            CREATE TABLE `vehicle_documents` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `vehicle_id` INT NOT NULL COMMENT '차량 ID',
                `document_type` VARCHAR(50) NOT NULL COMMENT '문서 유형',
                `file_path` VARCHAR(255) NOT NULL COMMENT '파일 경로',
                `uploaded_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                KEY `fk_document_vehicle` (`vehicle_id`),
                CONSTRAINT `fk_document_vehicle` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='차량 관련 문서'
        ");
    }

    public function down(): void
    {
        $this->db->execute("DROP TABLE IF EXISTS `vehicle_documents`");
        $this->db->execute("DROP TABLE IF EXISTS `vehicle_inspections`");
        $this->db->execute("DROP TABLE IF EXISTS `vehicle_consumable_logs`");
        $this->db->execute("DROP TABLE IF EXISTS `vehicle_consumables`");
        $this->db->execute("DROP TABLE IF EXISTS `vehicle_works`");
        $this->db->execute("DROP TABLE IF EXISTS `vehicles`");
    }
}
