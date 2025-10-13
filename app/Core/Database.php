<?php
// app/Core/Database.php
namespace App\Core;

use PDO;
use PDOStatement;

class Database {
    private PDO $pdo;

    public function __construct() {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            DB_HOST,
            DB_NAME,
            DB_CHARSET
        );
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
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
