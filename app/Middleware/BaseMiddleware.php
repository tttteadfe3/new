<?php

namespace App\Middleware;

abstract class BaseMiddleware
{
    /**
     * 미들웨어 로직을 처리합니다.
     * 
     * @param mixed $parameter 미들웨어를 위한 선택적 매개변수
     */
    abstract public function handle($parameter = null): void;

    /**
     * 현재 요청이 API 요청인지 확인합니다.
     */
    protected function isApiRequest(): bool
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        return str_starts_with(trim($uri, '/'), 'api/');
    }

    /**
     * API 요청에 대한 JSON 응답을 보냅니다.
     */
    protected function jsonResponse(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
    }

    /**
     * URL로 리디렉션합니다 (웹 요청용).
     */
    protected function redirect(string $url): void
    {
        header("Location: {$url}");
        exit;
    }

    /**
     * HTML 오류 응답을 보냅니다 (웹 요청용).
     */
    protected function htmlError(int $status, string $title, string $message): void
    {
        http_response_code($status);
        echo "<h1>{$status} {$title}</h1>";
        echo "<p>{$message}</p>";
        exit;
    }
}
