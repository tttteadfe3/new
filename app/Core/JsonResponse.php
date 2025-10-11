<?php
// app/Core/JsonResponse.php
namespace App\Core;

/**
 * API의 JSON 응답을 표준화하고 쉽게 생성하기 위한 헬퍼 클래스
 */
class JsonResponse {
    /**
     * JSON 응답을 전송하는 핵심 private 메소드
     *
     * @param array $payload 전송할 데이터 배열
     * @param int $httpStatusCode HTTP 상태 코드
     */
    private function send(array $payload, int $httpStatusCode = 200) {
        // 이미 헤더가 전송되었는지 확인 (에러 방지)
        if (headers_sent()) {
            // 헤더가 이미 전송된 경우, 더 이상 아무것도 할 수 없으므로 종료
            return;
        }
        
        // HTTP 응답 코드 설정
        http_response_code($httpStatusCode);
        // 컨텐츠 타입을 JSON으로 설정
        header('Content-Type: application/json; charset=utf-8');
        // JSON 인코딩하여 출력 (유니코드 깨짐 방지)
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        // 추가 출력을 막기 위해 스크립트 실행 종료
        exit;
    }

    /**
     * 성공 응답을 전송합니다. (HTTP 200 OK)
     *
     * @param mixed|null $data 클라이언트에게 전송할 데이터
     * @param string $message 성공 메시지
     */
    public function success($data = null, string $message = 'Success') {
        $this->send([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], 200);
    }

    /**
     * 에러 응답을 전송합니다.
     *
     * @param string $message 클라이언트에게 보여줄 에러 메시지
     * @param string|null $errorCode 프론트엔드가 참고할 수 있는 커스텀 에러 코드
     * @param int $httpStatusCode HTTP 상태 코드 (기본값: 400 Bad Request)
     */
    public function error(string $message, ?string $errorCode = null, int $httpStatusCode = 400) {
        $this->send([
            'success' => false,
            'message' => $message,
            'error_code' => $errorCode,
            'data' => null
        ], $httpStatusCode);
    }

    /**
     * 'Not Found' (404) 에러를 위한 단축 메소드
     *
     * @param string $message
     */
    public function notFound(string $message = 'Resource not found') {
        $this->error($message, 'NOT_FOUND', 404);
    }

    /**
     * 'Forbidden' (403) 에러를 위한 단축 메소드
     *
     * @param string $message
     */
    public function forbidden(string $message = 'Forbidden') {
        $this->error($message, 'FORBIDDEN', 403);
    }

    /**
     * 'Bad Request' (400) 에러를 위한 단축 메소드
     *
     * @param string $message
     */
    public function badRequest(string $message = 'Bad Request') {
        $this->error($message, 'BAD_REQUEST', 400);
    }
}
