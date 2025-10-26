<?php

namespace App\Controllers\Api;

use App\Controllers\Web\BaseController;
use App\Core\JsonResponse;
use App\Core\Request;
use App\Services\ActivityLogger;
use App\Services\AuthService;
use App\Services\ViewDataService;
use App\Repositories\EmployeeRepository;

abstract class BaseApiController extends BaseController
{
    protected EmployeeRepository $employeeRepository;
    protected JsonResponse $jsonResponse;

    public function __construct(
        // 부모 BaseController에 대한 종속성
        Request $request,
        AuthService $authService,
        ViewDataService $viewDataService,
        ActivityLogger $activityLogger,
        // BaseApiController에 대한 종속성
        EmployeeRepository $employeeRepository,
        JsonResponse $jsonResponse
    ) {
        parent::__construct($request, $authService, $viewDataService, $activityLogger);

        $this->employeeRepository = $employeeRepository;
        $this->jsonResponse = $jsonResponse;
        
        // 모든 API 요청이 AJAX 요청인지 확인합니다
        if (!$this->isAjaxRequest()) {
            $this->apiError('API 엔드포인트는 AJAX 요청만 허용합니다', 'INVALID_REQUEST', 400);
        }
        
        // 모든 API 응답에 대해 JSON 콘텐츠 유형을 설정합니다
        header('Content-Type: application/json; charset=utf-8');
    }

    /**
     * 요청이 AJAX 요청인지 확인합니다
     */
    protected function isAjaxRequest(): bool
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * 성공적인 API 응답을 보냅니다
     */
    protected function apiSuccess($data = null, string $message = 'Success'): void
    {
        $this->jsonResponse->success($data, $message);
    }

    /**
     * 오류 API 응답을 보냅니다
     */
    protected function apiError(string $message, string $errorCode = null, int $httpStatus = 400): void
    {
        $this->jsonResponse->error($message, $errorCode, $httpStatus);
    }

    /**
     * 찾을 수 없음 API 응답을 보냅니다
     */
    protected function apiNotFound(string $message = 'Resource not found'): void
    {
        $this->jsonResponse->notFound($message);
    }

    /**
     * 금지된 API 응답을 보냅니다
     */
    protected function apiForbidden(string $message = 'Access forbidden'): void
    {
        $this->jsonResponse->forbidden($message);
    }

    /**
     * 잘못된 요청 API 응답을 보냅니다
     */
    protected function apiBadRequest(string $message = 'Bad request'): void
    {
        $this->jsonResponse->badRequest($message);
    }

    /**
     * 요청 본문에서 JSON 입력 데이터를 가져옵니다
     */
    protected function getJsonInput(): array
    {
        $input = json_decode(file_get_contents('php://input'), true);
        return $input ?? [];
    }

    /**
     * 요청에서 작업 매개변수를 가져옵니다
     */
    protected function getAction(): string
    {
        return $_GET['action'] ?? $_POST['action'] ?? '';
    }

    /**
     * 입력 데이터에서 필수 필드를 확인합니다
     */
    protected function validateRequired(array $data, array $requiredFields): bool
    {
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $this->apiBadRequest("필수 필드 '{$field}'가 누락되었거나 비어 있습니다");
                return false;
            }
        }
        return true;
    }

    /**
     * 현재 사용자의 직원 ID를 가져옵니다
     */
    protected function getCurrentEmployeeId(): ?int
    {
        $user = $this->authService->user();
        if (!$user) {
            return null;
        }

        // 사용자 데이터에서 직원 ID를 가져오려고 시도합니다
        if (isset($user['employee_id'])) {
            return (int)$user['employee_id'];
        }

        // 사용할 수 없는 경우 EmployeeRepository를 통해 찾으려고 시도합니다
        try {
            $employee = $this->employeeRepository->findByUserId($user['id']);
            return $employee ? (int)$employee['id'] : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * 일반적인 API 예외를 처리합니다
     */
    protected function handleException(\Exception $e): void
    {
        $code = is_int($e->getCode()) && $e->getCode() >= 400 ? $e->getCode() : 500;
        $this->apiError($e->getMessage(), 'SERVER_ERROR', $code);
    }
}
