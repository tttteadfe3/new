<?php

namespace App\Validators;

class LeaveRequestValidator
{
    public static function validateStore(array $data): array
    {
        $errors = [];
        if (empty($data['start_date']) || !self::isValidDate($data['start_date'])) {
            $errors['start_date'] = '유효한 시작일을 입력해주세요.';
        }
        if (empty($data['end_date']) || !self::isValidDate($data['end_date'])) {
            $errors['end_date'] = '유효한 종료일을 입력해주세요.';
        }
        if (empty($data['days_count']) || !is_numeric($data['days_count']) || $data['days_count'] <= 0) {
            $errors['days_count'] = '신청일수는 0보다 커야 합니다.';
        }
        if (empty($data['leave_subtype']) || !in_array($data['leave_subtype'], ['full_day', 'half_day_am', 'half_day_pm'])) {
            $errors['leave_subtype'] = '유효하지 않은 휴가 종류입니다.';
        }
        if (empty($data['reason'])) {
            $errors['reason'] = '신청 사유는 필수입니다.';
        }
        return $errors;
    }

    public static function validateAdjustment(array $data): array
    {
        $errors = [];
        if (empty($data['employee_id']) || !is_numeric($data['employee_id'])) {
            $errors['employee_id'] = '유효한 직원 ID를 입력해주세요.';
        }
        if (empty($data['year']) || !is_numeric($data['year']) || $data['year'] < 2000) {
            $errors['year'] = '유효한 연도를 입력해주세요.';
        }
        if (!isset($data['days']) || !is_numeric($data['days'])) {
            $errors['days'] = '조정일수를 숫자로 입력해주세요.';
        }
        if (empty($data['reason'])) {
            $errors['reason'] = '조정 사유는 필수입니다.';
        }
        return $errors;
    }

    private static function isValidDate(string $date, string $format = 'Y-m-d'): bool
    {
        $d = \DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }
}
