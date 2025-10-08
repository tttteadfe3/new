<?php
// app/Core/helpers.php

/**
 * HTML 출력 시 XSS 방지를 위한 유틸리티 함수
 * 이 함수는 전역적으로 사용될 수 있습니다.
 */
function e(?string $string): string {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}