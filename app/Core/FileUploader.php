<?php
// app/Core/FileUploader.php
namespace App\Core;

use Exception;

/**
 * 파일 업로드를 처리하는 유틸리티 클래스입니다.
 * 설정은 config/config.php 파일의 전역 상수를 사용합니다.
 */
class FileUploader {
    /**
     * 업로드된 파일을 검증하고 지정된 하위 디렉토리로 이동시킵니다.
     *
     * @param array $file $_FILES 배열의 단일 파일 요소
     * @param string $subDirectory UPLOAD_DIR 내부에 생성될 하위 디렉토리명 (예: 'littering')
     * @param string $prefix 파일명 앞에 붙일 접두사
     * @return string 저장된 파일의 경로 (하위 디렉토리 포함)
     * @throws Exception 파일 업로드 오류, 타입 또는 크기 불일치 시
     */
    public static function validateAndUpload(array $file, string $subDirectory, string $prefix = ''): string {
        // 파일 업로드 관련 상수는 config/config.php에서 로드됩니다.

        if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
            $uploadErrors = [
                UPLOAD_ERR_INI_SIZE   => 'php.ini의 upload_max_filesize 보다 큽니다.',
                UPLOAD_ERR_FORM_SIZE  => 'HTML 폼의 MAX_FILE_SIZE 보다 큽니다.',
                UPLOAD_ERR_PARTIAL    => '파일이 일부만 전송되었습니다.',
                UPLOAD_ERR_NO_FILE    => '파일이 전송되지 않았습니다.',
                UPLOAD_ERR_NO_TMP_DIR => '임시 폴더가 없습니다.',
                UPLOAD_ERR_CANT_WRITE => '디스크에 파일을 쓸 수 없습니다.',
                UPLOAD_ERR_EXTENSION  => 'PHP 확장 기능에 의해 파일 업로드가 중단되었습니다.',
            ];
            $message = $uploadErrors[$file['error']] ?? '알 수 없는 업로드 오류가 발생했습니다.';
            throw new Exception($message, $file['error']);
        }

        if (!in_array($file['type'], ALLOWED_MIMES)) {
            throw new Exception("허용되지 않는 파일 형식입니다.", 400);
        }

        if ($file['size'] > MAX_FILE_SIZE) {
            throw new Exception("파일 크기가 5MB를 초과할 수 없습니다.", 400);
        }

        // 기본 업로드 디렉토리와 하위 디렉토리를 조합하여 전체 경로 생성
        $targetDirectory = UPLOAD_DIR . rtrim($subDirectory, '/') . '/';

        if (!file_exists($targetDirectory) && !mkdir($targetDirectory, 0777, true)) {
            throw new Exception("업로드 디렉토리({$targetDirectory})를 생성할 수 없습니다.", 500);
        }

        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $fileName = $prefix . time() . '_' . uniqid() . '.' . $extension;
        $filePath = $targetDirectory . $fileName;

        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            throw new Exception("파일 업로드에 실패했습니다.", 500);
        }

        // 웹 접근이 가능한 상대 URL 경로 반환 (예: /uploads/littering/image.jpg)
        return UPLOAD_URL_PATH . '/' . rtrim($subDirectory, '/') . '/' . $fileName;
    }
}
