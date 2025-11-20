<?php

use App\Core\Database;

class CreateDistributionDocumentsTables
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function up()
    {
        $this->db->execute("
            CREATE TABLE `distribution_documents` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `title` VARCHAR(255) NOT NULL,
                `created_by` INT NOT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (`created_by`) REFERENCES `users`(`id`)
            )
        ");

        $this->db->execute("
            CREATE TABLE `distribution_document_items` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `document_id` INT NOT NULL,
                `item_id` INT NOT NULL,
                `quantity` INT NOT NULL,
                FOREIGN KEY (`document_id`) REFERENCES `distribution_documents`(`id`) ON DELETE CASCADE,
                FOREIGN KEY (`item_id`) REFERENCES `supply_items`(`id`)
            )
        ");

        $this->db->execute("
            CREATE TABLE `distribution_document_employees` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `document_id` INT NOT NULL,
                `employee_id` INT NOT NULL,
                FOREIGN KEY (`document_id`) REFERENCES `distribution_documents`(`id`) ON DELETE CASCADE,
                FOREIGN KEY (`employee_id`) REFERENCES `hr_employees`(`id`)
            )
        ");
    }

    public function down()
    {
        $this->db->execute("DROP TABLE IF EXISTS `distribution_document_employees`");
        $this->db->execute("DROP TABLE IF EXISTS `distribution_document_items`");
        $this->db->execute("DROP TABLE IF EXISTS `distribution_documents`");
    }
}
