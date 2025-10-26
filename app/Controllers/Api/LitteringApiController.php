<?php

namespace App\Controllers\Api;

use App\Services\LitteringService;
use Exception;
use App\Core\Request;
use App\Services\AuthService;
use App\Services\ViewDataService;
use App\Services\ActivityLogger;
use App\Repositories\EmployeeRepository;
use App\Core\JsonResponse;

class LitteringApiController extends BaseApiController
{
    private LitteringService $litteringService;

    public function __construct(
        Request $request,
        AuthService $authService,
        ViewDataService $viewDataService,
        ActivityLogger $activityLogger,
        EmployeeRepository $employeeRepository,
        JsonResponse $jsonResponse,
        LitteringService $litteringService
    ) {
        parent::__construct(
            $request,
            $authService,
            $viewDataService,
            $activityLogger,
            $employeeRepository,
            $jsonResponse
        );
        $this->litteringService = $litteringService;
    }

    /**
     * 상태에 따라 무단투기 보고서를 가져옵니다.
     * GET /api/littering에 해당합니다.
     */
    public function index(): void
    {
        $status = $this->request->input('status', 'active'); // 'active', 'completed'

        try {
            if ($status === 'active') {
                $data = $this->litteringService->getActiveLittering();
            } elseif ($status === 'completed') {
                $data = $this->litteringService->getCompletedLittering();
            } else {
                $this->apiError('잘못된 상태 값입니다.', 'INVALID_INPUT', 400);
                return;
            }
            $this->apiSuccess($data);
        } catch (Exception $e) {
            $this->apiError('목록을 불러오는 중 오류가 발생했습니다.', 'SERVER_ERROR', 500);
        }
    }

    /**
     * 새 무단투기 보고서를 등록합니다.
     * POST /api/littering에 해당합니다.
     */
    public function store(): void
    {
        $employeeId = $this->user()['employee_id'];
        
        try {
            // 참고: 파일 업로드는 JSON 본문이 아닌 $_FILES를 통해 처리됩니다.
            $result = $this->litteringService->registerLittering(
                $this->request->all(), // POST 데이터가 여기에 적합합니다.
                $_FILES,
                $employeeId
            );
            $this->apiSuccess($result, '부적정배출 정보가 성공적으로 등록되었습니다.');
        } catch (Exception $e) {
            $this->apiError($e->getMessage(), 'VALIDATION_ERROR', 422);
        }
    }

    /**
     * 무단투기 보고서(수거)를 처리합니다.
     * POST /api/littering/{id}/process에 해당합니다.
     */
    public function process(int $id): void
    {
        $employeeId = $this->user()['employee_id'];
        
        try {
            $data = $this->request->all(); // POST 데이터가 여기에 적합합니다.
            $data['id'] = $id; // URL의 ID가 사용되도록 합니다.

            // 참고: 파일 업로드를 위해 $_FILES에 액세스합니다.
            $result = $this->litteringService->processLittering($data, $_FILES, $employeeId);
            $this->apiSuccess($result, '처리 상태가 성공적으로 업데이트되었습니다.');
        } catch (Exception $e) {
            $this->apiError($e->getMessage(), 'VALIDATION_ERROR', 422);
        }
    }
}
