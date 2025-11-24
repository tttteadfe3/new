<?php

namespace App\Core;

class Request
{
    private array $data = [];

    public function __construct()
    {
        $this->parseRequestData();
    }

    /**
     * 다양한 소스에서 요청 데이터를 구문 분석합니다.
     */
    private function parseRequestData(): void
    {
        // GET 매개변수 구문 분석
        $this->data = $_GET;

        // POST 데이터 구문 분석
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
            
            if (strpos($contentType, 'application/json') !== false) {
                // JSON 입력 처리
                $json = file_get_contents('php://input');
                $jsonData = json_decode($json, true);
                if ($jsonData !== null) {
                    $this->data = array_merge($this->data, $jsonData);
                }
            } else {
                // 양식 데이터 처리
                $this->data = array_merge($this->data, $_POST);
            }
        }

        // 다른 HTTP 메서드(PUT, PATCH, DELETE) 처리
        if (in_array($_SERVER['REQUEST_METHOD'], ['PUT', 'PATCH', 'DELETE'])) {
            $input = file_get_contents('php://input');
            $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
            
            if (strpos($contentType, 'application/json') !== false) {
                $jsonData = json_decode($input, true);
                if ($jsonData !== null) {
                    $this->data = array_merge($this->data, $jsonData);
                }
            } else {
                // 양식 인코딩 데이터 구문 분석
                parse_str($input, $parsedData);
                $this->data = array_merge($this->data, $parsedData);
            }
        }
    }

    /**
     * 현재 요청 URI 경로를 가져옵니다.
     */
    public static function uri(): string
    {
        return trim(
            parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH),
            '/'
        );
    }

    /**
     * 현재 요청 메서드를 가져옵니다.
     */
    public static function method(): string
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['_method'])) {
            return strtoupper($_POST['_method']);
        }
        return $_SERVER['REQUEST_METHOD'] ?? 'GET';
    }

    /**
     * 모든 요청 데이터를 가져옵니다.
     * 
     * @return array 모든 요청 데이터
     */
    public function all(): array
    {
        return $this->data;
    }

    /**
     * 특정 입력 값을 가져옵니다.
     * 
     * @param string $key 입력 키
     * @param mixed $default 키가 존재하지 않을 경우의 기본값
     * @return mixed 입력 값 또는 기본값
     */
    public function input(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * 여러 입력 값을 가져옵니다.
     * 
     * @param array $keys 검색할 키 배열
     * @return array 키-값 쌍 배열
     */
    public function only(array $keys): array
    {
        return array_intersect_key($this->data, array_flip($keys));
    }

    /**
     * 지정된 키를 제외한 모든 입력을 가져옵니다.
     * 
     * @param array $keys 제외할 키 배열
     * @return array 키-값 쌍 배열
     */
    public function except(array $keys): array
    {
        return array_diff_key($this->data, array_flip($keys));
    }

    /**
     * 입력 키가 있는지 확인합니다.
     * 
     * @param string $key 입력 키
     * @return bool 키가 있으면 true, 그렇지 않으면 false
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    /**
     * 입력 키가 있고 비어 있지 않은지 확인합니다.
     * 
     * @param string $key 입력 키
     * @return bool 키가 있고 비어 있지 않으면 true, 그렇지 않으면 false
     */
    public function filled(string $key): bool
    {
        return $this->has($key) && !empty($this->data[$key]);
    }

    /**
     * 요청 헤더를 가져옵니다.
     * 
     * @param string|null $key 특정 헤더 키 (선택 사항)
     * @return array|string|null 모든 헤더 또는 특정 헤더 값
     */
    public function headers(string $key = null): array|string|null
    {
        $headers = getallheaders();
        
        if ($key !== null) {
            // 대소문자를 구분하지 않는 헤더 조회
            $headers = array_change_key_case($headers, CASE_LOWER);
            return $headers[strtolower($key)] ?? null;
        }
        
        return $headers;
    }

    /**
     * 요청이 AJAX인지 확인합니다.
     * 
     * @return bool AJAX 요청이면 true, 그렇지 않으면 false
     */
    public function isAjax(): bool
    {
        return strtolower($this->headers('X-Requested-With') ?? '') === 'xmlhttprequest';
    }

    /**
     * 요청이 JSON 응답을 예상하는지 확인합니다.
     * 
     * @return bool JSON이 예상되면 true, 그렇지 않으면 false
     */
    public function expectsJson(): bool
    {
        $accept = $this->headers('Accept') ?? '';
        return strpos($accept, 'application/json') !== false || $this->isAjax();
    }

    /**
     * 업로드된 파일을 가져옵니다.
     * 
     * @param string|null $key 특정 파일 키 (선택 사항)
     * @return array|null 모든 파일 또는 특정 파일
     */
    public function files(string $key = null): array|null
    {
        if ($key !== null) {
            return $_FILES[$key] ?? null;
        }
        
        return $_FILES;
    }

    /**
     * 입력 데이터를 확인합니다.
     * 
     * @param array $rules 유효성 검사 규칙
     * @return array 유효성 검사 오류 (유효한 경우 비어 있음)
     */
    public function validate(array $rules): array
    {
        $errors = [];
        
        foreach ($rules as $field => $rule) {
            $value = $this->input($field);
            $ruleList = is_string($rule) ? explode('|', $rule) : $rule;
            
            foreach ($ruleList as $singleRule) {
                $error = $this->validateField($field, $value, $singleRule);
                if ($error) {
                    $errors[$field][] = $error;
                }
            }
        }
        
        return $errors;
    }

    /**
     * 단일 필드를 확인합니다.
     * 
     * @param string $field 필드 이름
     * @param mixed $value 필드 값
     * @param string $rule 유효성 검사 규칙
     * @return string|null 오류 메시지 또는 유효한 경우 null
     */
    private function validateField(string $field, mixed $value, string $rule): ?string
    {
        if ($rule === 'required' && empty($value)) {
            return "{$field} 필드는 필수입니다.";
        }
        
        if ($rule === 'email' && !empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return "{$field} 필드는 유효한 이메일 주소여야 합니다.";
        }
        
        if (strpos($rule, 'min:') === 0) {
            $min = (int) substr($rule, 4);
            if (!empty($value) && strlen($value) < $min) {
                return "{$field} 필드는 최소 {$min}자여야 합니다.";
            }
        }
        
        if (strpos($rule, 'max:') === 0) {
            $max = (int) substr($rule, 4);
            if (!empty($value) && strlen($value) > $max) {
                return "{$field} 필드는 {$max}자를 초과할 수 없습니다.";
            }
        }
        
        return null;
    }
}
