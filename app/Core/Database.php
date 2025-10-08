<?php
// app/Core/Database.php
namespace App\Core;

use PDO;
use PDOStatement;

class Database {
    private static ?PDO $pdo = null;

    private static function getConnection(): PDO {
        if (self::$pdo === null) {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            self::$pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        }
        return self::$pdo;
    }

    private static function executeQuery(string $sql, array $params = []): PDOStatement {
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public static function query(string $sql, array $params = []): array {
        return self::executeQuery($sql, $params)->fetchAll();
    }

    public static function fetchOne(string $sql, array $params = []) {
        return self::executeQuery($sql, $params)->fetch();
    }

    public static function execute(string $sql, array $params = []): int {
        return self::executeQuery($sql, $params)->rowCount();
    }
    
    public static function fetchAll(string $sql, array $params = []): array {
        return self::executeQuery($sql, $params)->fetchAll();
    }
    
    public static function insert(string $sql, array $params = []): int {
        self::executeQuery($sql, $params);
        return (int)self::getConnection()->lastInsertId();
    }

    public static function lastInsertId(): string {
        return self::getConnection()->lastInsertId();
    }

    public static function beginTransaction() {
        self::getConnection()->beginTransaction();
    }

    public static function commit() {
        self::getConnection()->commit();
    }

    public static function rollBack() {
        self::getConnection()->rollBack();
    }
}