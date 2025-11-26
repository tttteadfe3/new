#!/usr/bin/env php
<?php
/**
 * 데이터베이스 스키마 추출 스크립트
 * 
 * 이 스크립트는 데이터베이스에 접속하여 스키마를 추출합니다.
 */

// 환경 설정 로드
require_once dirname(__DIR__, 2) . '/config/config.php';

echo "=== 데이터베이스 스키마 추출 스크립트 ===\n\n";

// 데이터베이스 연결 정보 표시
echo "데이터베이스 연결 정보:\n";
echo "  Host: " . DB_HOST . "\n";
echo "  Database: " . DB_NAME . "\n";
echo "  User: " . DB_USER . "\n";
echo "  Charset: " . DB_CHARSET . "\n\n";

// mysqldump 사용 가능 여부 확인
$mysqldumpPath = 'mysqldump';

// Windows 환경에서 mysqldump 경로 찾기
if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    $possiblePaths = [
        'C:\\xampp\\mysql\\bin\\mysqldump.exe',
        'C:\\Program Files\\MySQL\\MySQL Server 8.0\\bin\\mysqldump.exe',
        'C:\\Program Files\\MySQL\\MySQL Server 5.7\\bin\\mysqldump.exe',
        'mysqldump',
    ];
    
    foreach ($possiblePaths as $path) {
        if (file_exists($path)) {
            $mysqldumpPath = $path;
            echo "mysqldump 발견: $path\n";
            break;
        }
    }
}

// 출력 파일 경로
$outputFile = dirname(__DIR__) . '/schema.sql';
$outputFileNew = dirname(__DIR__) . '/schema_new.sql';

echo "\n스키마 추출 시작...\n";

// mysqldump 명령어 구성
$command = sprintf(
    '%s --host=%s --user=%s --password=%s --no-data --routines --triggers --events --single-transaction %s > %s 2>&1',
    escapeshellcmd($mysqldumpPath),
    escapeshellarg(DB_HOST),
    escapeshellarg(DB_USER),
    escapeshellarg(DB_PASS),
    escapeshellarg(DB_NAME),
    escapeshellarg($outputFileNew)
);

// 비밀번호가 없는 경우 처리
if (empty(DB_PASS)) {
    $command = sprintf(
        '%s --host=%s --user=%s --no-data --routines --triggers --events --single-transaction %s > %s 2>&1',
        escapeshellcmd($mysqldumpPath),
        escapeshellarg(DB_HOST),
        escapeshellarg(DB_USER),
        escapeshellarg(DB_NAME),
        escapeshellarg($outputFileNew)
    );
}

echo "실행 명령어:\n";
echo "mysqldump --host=" . DB_HOST . " --user=" . DB_USER . " --no-data --routines --triggers --events --single-transaction " . DB_NAME . "\n\n";

// 명령 실행
exec($command, $output, $returnCode);

if ($returnCode === 0) {
    echo "✓ 스키마 추출 완료: $outputFileNew\n";
    
    // 파일 크기 확인
    if (file_exists($outputFileNew)) {
        $fileSize = filesize($outputFileNew);
        echo "  파일 크기: " . number_format($fileSize) . " bytes\n";
        
        if ($fileSize > 0) {
            // 기존 파일과 비교
            if (file_exists($outputFile)) {
                $oldSize = filesize($outputFile);
                echo "  기존 파일 크기: " . number_format($oldSize) . " bytes\n";
                
                if ($fileSize != $oldSize) {
                    echo "\n주의: 파일 크기가 변경되었습니다.\n";
                }
            }
            
            echo "\n새로운 스키마 파일이 생성되었습니다: schema_new.sql\n";
            echo "기존 schema.sql과 비교 후 교체하세요.\n";
        } else {
            echo "\n오류: 생성된 파일이 비어있습니다.\n";
        }
    } else {
        echo "\n오류: 파일이 생성되지 않았습니다.\n";
    }
} else {
    echo "✗ 스키마 추출 실패 (코드: $returnCode)\n";
    if (!empty($output)) {
        echo "출력:\n" . implode("\n", $output) . "\n";
    }
}

echo "\n=== 완료 ===\n";
