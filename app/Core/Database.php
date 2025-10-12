<?php
// app/Core/Database.php
namespace App\Core;

use PDO;
use PDOStatement;

class Database {
    private PDO $pdo;

    public function __construct() {
        // Use $_ENV for consistency with the rest of the application
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=utf8mb4',
            $_ENV['DB_HOST'],
            $_ENV['DB_NAME']
        );
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        // Use $_ENV for credentials
        $this->pdo = new PDO($dsn, $_ENV['DB_USER'], $_ENV['DB_PASS'], $options);
    }

    private function executeQuery(string $sql, array $params = []): PDOStatement {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function query(string $sql, array $params = []): array {
        return $this->executeQuery($sql, $params)->fetchAll();
    }

    public function fetchOne(string $sql, array $params = []) {
        return $this->executeQuery($sql, $params)->fetch();
    }

    public function execute(string $sql, array $params = []): int {
        return $this->executeQuery($sql, $params)->rowCount();
    }
    
    public function fetchAll(string $sql, array $params = []): array {
        return $this->executeQuery($sql, $params)->fetchAll();
    }
    
    public function insert(string $sql, array $params = []): int {
        $this->executeQuery($sql, $params);
        return (int)$this->pdo->lastInsertId();
    }

    public function lastInsertId(): string {
        return $this->pdo->lastInsertId();
    }

    public function beginTransaction() {
        $this->pdo->beginTransaction();
    }

    public function commit() {
        $this->pdo->commit();
    }

    public function rollBack() {
        $this->pdo->rollBack();
    }
}
