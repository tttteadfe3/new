#!/usr/bin/env php
<?php
/**
 * 데이터베이스 스키마 추출 스크립트 (PHP PDO 사용)
 * 
 * mysqldump 없이 PHP PDO를 사용하여 스키마를 추출합니다.
 */

// 환경 설정 로드
require_once dirname(__DIR__, 2) . '/config/config.php';

echo "=== 데이터베이스 스키마 추출 스크립트 (PDO) ===\n\n";

// 데이터베이스 연결 정보 표시
echo "데이터베이스 연결 정보:\n";
echo "  Host: " . DB_HOST . "\n";
echo "  Database: " . DB_NAME . "\n";
echo "  User: " . DB_USER . "\n";
echo "  Charset: " . DB_CHARSET . "\n\n";

try {
    // PDO 연결
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    echo "✓ 데이터베이스 연결 성공\n\n";
    
    // 출력 파일 경로
    $outputFile = dirname(__DIR__) . '/schema_new.sql';
    
    // SQL 덤프 시작
    $sql = "-- Database Schema Export\n";
    $sql .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
    $sql .= "-- Database: " . DB_NAME . "\n\n";
    
    $sql .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
    $sql .= "START TRANSACTION;\n";
    $sql .= "SET time_zone = \"+00:00\";\n\n";
    
    $sql .= "/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;\n";
    $sql .= "/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;\n";
    $sql .= "/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;\n";
    $sql .= "/*!40101 SET NAMES utf8mb4 */;\n\n";
    
    $sql .= "--\n";
    $sql .= "-- Database: `" . DB_NAME . "`\n";
    $sql .= "--\n\n";
    
    // 테이블 목록 가져오기
    $stmt = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "발견된 테이블: " . count($tables) . "개\n";
    
    foreach ($tables as $table) {
        echo "  - $table\n";
        
        $sql .= "-- --------------------------------------------------------\n\n";
        $sql .= "--\n";
        $sql .= "-- Table structure for table `$table`\n";
        $sql .= "--\n\n";
        
        // CREATE TABLE 구문 가져오기
        $stmt = $pdo->query("SHOW CREATE TABLE `$table`");
        $row = $stmt->fetch();
        $createTable = $row['Create Table'];
        
        $sql .= "$createTable;\n\n";
    }
    
    // 뷰 목록 가져오기
    $stmt = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'VIEW'");
    $views = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (!empty($views)) {
        echo "\n발견된 뷰: " . count($views) . "개\n";
        
        foreach ($views as $view) {
            echo "  - $view\n";
            
            $sql .= "-- --------------------------------------------------------\n\n";
            $sql .= "--\n";
            $sql .= "-- View structure for view `$view`\n";
            $sql .= "--\n\n";
            
            // 기존 뷰 삭제
            $sql .= "DROP TABLE IF EXISTS `$view`;\n\n";
            
            // CREATE VIEW 구문 가져오기
            $stmt = $pdo->query("SHOW CREATE VIEW `$view`");
            $row = $stmt->fetch();
            $createView = $row['Create View'];
            
            $sql .= "$createView;\n\n";
        }
    }
    
    // 인덱스 추가
    $sql .= "--\n";
    $sql .= "-- Indexes for dumped tables\n";
    $sql .= "--\n\n";
    
    foreach ($tables as $table) {
        // 인덱스 정보는 이미 CREATE TABLE에 포함되어 있음
        // 추가 인덱스가 필요한 경우 여기서 처리
    }
    
    // AUTO_INCREMENT 설정
    $sql .= "--\n";
    $sql .= "-- AUTO_INCREMENT for dumped tables\n";
    $sql .= "--\n\n";
    
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLE STATUS LIKE '$table'");
        $status = $stmt->fetch();
        
        if ($status && !is_null($status['Auto_increment'])) {
            $sql .= "--\n";
            $sql .= "-- AUTO_INCREMENT for table `$table`\n";
            $sql .= "--\n";
            $sql .= "ALTER TABLE `$table`\n";
            $sql .= "  MODIFY ";
            
            // AUTO_INCREMENT 컬럼 찾기
            $stmt = $pdo->query("SHOW COLUMNS FROM `$table` WHERE Extra LIKE '%auto_increment%'");
            $autoCol = $stmt->fetch();
            
            if ($autoCol) {
                $sql .= "`" . $autoCol['Field'] . "` " . strtoupper($autoCol['Type']);
                $sql .= " NOT NULL AUTO_INCREMENT";
                $sql .= ", AUTO_INCREMENT=" . $status['Auto_increment'] . ";\n\n";
            }
        }
    }
    
    // 외래 키 제약 조건
    $sql .= "--\n";
    $sql .= "-- Constraints for dumped tables\n";
    $sql .= "--\n\n";
    
    foreach ($tables as $table) {
        $stmt = $pdo->query("
            SELECT 
                CONSTRAINT_NAME,
                COLUMN_NAME,
                REFERENCED_TABLE_NAME,
                REFERENCED_COLUMN_NAME
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = '" . DB_NAME . "'
                AND TABLE_NAME = '$table'
                AND REFERENCED_TABLE_NAME IS NOT NULL
        ");
        
        $foreignKeys = $stmt->fetchAll();
        
        if (!empty($foreignKeys)) {
            $sql .= "--\n";
            $sql .= "-- Constraints for table `$table`\n";
            $sql .= "--\n";
            $sql .= "ALTER TABLE `$table`\n";
            
            $constraints = [];
            foreach ($foreignKeys as $fk) {
                $constraints[] = "  ADD CONSTRAINT `" . $fk['CONSTRAINT_NAME'] . "` " .
                                "FOREIGN KEY (`" . $fk['COLUMN_NAME'] . "`) " .
                                "REFERENCES `" . $fk['REFERENCED_TABLE_NAME'] . "` (`" . $fk['REFERENCED_COLUMN_NAME'] . "`)";
            }
            
            $sql .= implode(",\n", $constraints) . ";\n\n";
        }
    }
    
    $sql .= "COMMIT;\n\n";
    $sql .= "/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;\n";
    $sql .= "/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;\n";
    $sql .= "/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;\n";
    
    // 파일에 저장
    file_put_contents($outputFile, $sql);
    
    echo "\n✓ 스키마 추출 완료!\n";
    echo "  파일: $outputFile\n";
    echo "  크기: " . number_format(filesize($outputFile)) . " bytes\n";
    
    // 기존 파일과 비교
    $oldFile = dirname(__DIR__) . '/schema.sql';
    if (file_exists($oldFile)) {
        $oldSize = filesize($oldFile);
        $newSize = filesize($outputFile);
        echo "\n비교:\n";
        echo "  기존 파일: " . number_format($oldSize) . " bytes\n";
        echo "  새 파일: " . number_format($newSize) . " bytes\n";
        echo "  차이: " . number_format($newSize - $oldSize) . " bytes\n";
    }
    
    echo "\n새로운 스키마 파일이 생성되었습니다.\n";
    echo "확인 후 schema.sql을 교체하세요.\n";
    
} catch (PDOException $e) {
    echo "✗ 오류 발생: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n=== 완료 ===\n";
