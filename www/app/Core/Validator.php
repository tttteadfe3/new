<?php
// app/Core/Validator.php
namespace App\Core;

use DateTime;

/**
 * 데이터 유효성 검사를 위한 유틸리티 클래스입니다.
 */
class Validator {
    /**
     * 위도와 경도가 유효한 범위 내에 있는지 확인합니다.
     *
     * @param float $lat 위도
     * @param float $lng 경도
     * @return bool
     */
    public static function validateLatLng(float $lat, float $lng): bool {
        return ($lat >= -90 && $lat <= 90) && ($lng >= -180 && $lng <= 180);
    }

    /**
     * 입력된 문자열의 양쪽 공백을 제거하고 HTML 특수 문자를 이스케이프 처리합니다.
     *
     * @param string $input
     * @return string
     */
    public static function sanitizeString(string $input): string {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }

    /**
     * 주어진 날짜 문자열이 유효한 형식인지 확인합니다.
     *
     * @param string $date 날짜 문자열
     * @param string $format 날짜 형식 (기본값: 'Y-m-d')
     * @return bool
     */
    public static function validateDate(string $date, string $format = 'Y-m-d'): bool {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }
}
