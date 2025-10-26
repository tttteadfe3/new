<?php
// app/Validators/PositionValidator.php
namespace App\Validators;

/**
 * 직책 데이터의 유효성을 검사하는 클래스입니다.
 */
class PositionValidator {
    /**
     * 직책 데이터를 검증합니다.
     *
     * @param array $data 검증할 데이터 배열. 'name'과 'level' 키를 포함해야 합니다.
     * @return array 유효성 검사 오류 메시지 배열. 오류가 없으면 비어 있습니다.
     */
    public static function validate(array $data): array {
        $errors = [];

        if (empty($data['name'])) {
            $errors['name'] = '직급명은 필수입니다.';
        } elseif (strlen($data['name']) > 255) {
            $errors['name'] = '직급명은 255자 미만이어야 합니다.';
        }

        if (!isset($data['level'])) {
            $errors['level'] = '레벨은 필수입니다.';
        } elseif (!is_numeric($data['level'])) {
            $errors['level'] = '레벨은 숫자여야 합니다.';
        } elseif ($data['level'] < 0) {
            $errors['level'] = '레벨은 음수가 될 수 없습니다.';
        }


        return $errors;
    }
}
