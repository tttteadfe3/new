#!/usr/bin/env php
<?php
/**
 * 데이터베이스 스키마 분석 스크립트
 * 
 * 외래 키, 인덱스, 제약조건을 분석합니다.
 */

// 환경 설정 로드
require_once dirname(__DIR__, 2) . '/config/config.php';

echo "=== 데이터베이스 스키마 분석 ===\n\n";

try {
    // PDO 연결
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    echo "✓ 데이터베이스 연결 성공\n\n";
    
    // 테이블 목록 가져오기
    $stmt = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "총 테이블 수: " . count($tables) . "개\n\n";
    
    // 외래 키 분석
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "외래 키 제약조건 분석\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    
    $foreignKeyQuery = "
        SELECT 
            TABLE_NAME,
            COLUMN_NAME,
            CONSTRAINT_NAME,
            REFERENCED_TABLE_NAME,
            REFERENCED_COLUMN_NAME
        FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
        WHERE TABLE_SCHEMA = :dbname
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ORDER BY TABLE_NAME, CONSTRAINT_NAME
    ";
    
    $stmt = $pdo->prepare($foreignKeyQuery);
    $stmt->execute(['dbname' => DB_NAME]);
    $foreignKeys = $stmt->fetchAll();
    
    echo "총 외래 키: " . count($foreignKeys) . "개\n\n";
    
    $fkByTable = [];
    foreach ($foreignKeys as $fk) {
        $tableName = $fk['TABLE_NAME'];
        if (!isset($fkByTable[$tableName])) {
            $fkByTable[$tableName] = [];
        }
        $fkByTable[$tableName][] = $fk;
    }
    
    foreach ($fkByTable as $tableName => $fks) {
        echo "테이블: {$tableName} (" . count($fks) . "개 외래 키)\n";
        foreach ($fks as $fk) {
            echo "  - {$fk['CONSTRAINT_NAME']}: ";
            echo "{$fk['COLUMN_NAME']} → {$fk['REFERENCED_TABLE_NAME']}.{$fk['REFERENCED_COLUMN_NAME']}\n";
        }
        echo "\n";
    }
    
    // 인덱스 분석
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "인덱스 분석\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    
    $indexStats = [];
    
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW INDEX FROM `$table`");
        $indexes = $stmt->fetchAll();
        
        $indexStats[$table] = [
            'total' => count($indexes),
            'unique' => 0,
            'primary' => 0,
            'regular' => 0,
            'details' => []
        ];
        
        $indexGroups = [];
        foreach ($indexes as $idx) {
            $keyName = $idx['Key_name'];
            
            if (!isset($indexGroups[$keyName])) {
                $indexGroups[$keyName] = [
                    'columns' => [],
                    'non_unique' => $idx['Non_unique'],
                    'type' => $idx['Index_type']
                ];
            }
            
            $indexGroups[$keyName]['columns'][] = $idx['Column_name'];
        }
        
        foreach ($indexGroups as $keyName => $info) {
            if ($keyName === 'PRIMARY') {
                $indexStats[$table]['primary']++;
            } elseif ($info['non_unique'] == 0) {
                $indexStats[$table]['unique']++;
            } else {
                $indexStats[$table]['regular']++;
            }
            
            $indexStats[$table]['details'][$keyName] = $info;
        }
    }
    
    // 인덱스 요약
    echo "테이블별 인덱스 요약:\n\n";
    
    $totalIndexes = 0;
    $totalPrimary = 0;
    $totalUnique = 0;
    $totalRegular = 0;
    
    foreach ($indexStats as $tableName => $stats) {
        $indexCount = $stats['primary'] + $stats['unique'] + $stats['regular'];
        $totalIndexes += $indexCount;
        $totalPrimary += $stats['primary'];
        $totalUnique += $stats['unique'];
        $totalRegular += $stats['regular'];
        
        echo "{$tableName}: {$indexCount}개 ";
        echo "(PK: {$stats['primary']}, UNIQUE: {$stats['unique']}, INDEX: {$stats['regular']})\n";
        
        foreach ($stats['details'] as $idxName => $idxInfo) {
            $columns = implode(', ', $idxInfo['columns']);
            $type = $idxName === 'PRIMARY' ? 'PRIMARY KEY' : 
                    ($idxInfo['non_unique'] == 0 ? 'UNIQUE' : 'INDEX');
            echo "  - [{$type}] {$idxName}: ({$columns})\n";
        }
        echo "\n";
    }
    
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "전체 통계\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    echo "총 인덱스 수: {$totalIndexes}개\n";
    echo "  - PRIMARY KEY: {$totalPrimary}개\n";
    echo "  - UNIQUE INDEX: {$totalUnique}개\n";
    echo "  - REGULAR INDEX: {$totalRegular}개\n\n";
    
    // 외래 키가 있지만 인덱스가 없는 컬럼 찾기
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "외래 키 인덱스 검증\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    
    $missingIndexes = [];
    
    foreach ($foreignKeys as $fk) {
        $tableName = $fk['TABLE_NAME'];
        $columnName = $fk['COLUMN_NAME'];
        
        // 해당 컬럼에 인덱스가 있는지 확인
        $hasIndex = false;
        
        if (isset($indexStats[$tableName]['details'])) {
            foreach ($indexStats[$tableName]['details'] as $idxName => $idxInfo) {
                // 복합 인덱스의 첫 번째 컬럼이거나 단일 컬럼 인덱스인 경우
                if ($idxInfo['columns'][0] === $columnName) {
                    $hasIndex = true;
                    break;
                }
            }
        }
        
        if (!$hasIndex) {
            $missingIndexes[] = [
                'table' => $tableName,
                'column' => $columnName,
                'references' => "{$fk['REFERENCED_TABLE_NAME']}.{$fk['REFERENCED_COLUMN_NAME']}"
            ];
        }
    }
    
    if (empty($missingIndexes)) {
        echo "✓ 모든 외래 키 컬럼에 인덱스가 존재합니다.\n\n";
    } else {
        echo "⚠ 인덱스가 필요한 외래 키 컬럼: " . count($missingIndexes) . "개\n\n";
        foreach ($missingIndexes as $missing) {
            echo "  - {$missing['table']}.{$missing['column']} → {$missing['references']}\n";
        }
        echo "\n";
    }
    
    // 테이블별 컬럼 수 및 인덱스 비율
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "테이블 통계\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW COLUMNS FROM `$table`");
        $columns = $stmt->fetchAll();
        $columnCount = count($columns);
        
        $indexCount = $indexStats[$table]['primary'] + 
                     $indexStats[$table]['unique'] + 
                     $indexStats[$table]['regular'];
        
        echo "{$table}:\n";
        echo "  컬럼: {$columnCount}개, 인덱스: {$indexCount}개\n";
        
        // 외래 키 수
        $fkCount = isset($fkByTable[$table]) ? count($fkByTable[$table]) : 0;
        if ($fkCount > 0) {
            echo "  외래 키: {$fkCount}개\n";
        }
        echo "\n";
    }
    
} catch (PDOException $e) {
    echo "✗ 오류 발생: " . $e->getMessage() . "\n";
    exit(1);
}

echo "=== 분석 완료 ===\n";
