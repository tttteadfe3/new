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

class LitteringAdminApiController extends BaseApiController
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
     * 상태에 따라 관리자를 위한 무단투기 보고서를 가져옵니다.
     * GET /api/littering_admin/reports에 해당합니다.
     */
    public function listReports(): void
    {
        $status = $this->request->input('status', '대기'); // '대기', '처리완료', '삭제'

        try {
            $data = [];
            switch ($status) {
                case '대기':
                    $data = $this->litteringService->getPendingLittering();
                    break;
                case '처리완료':
                    $data = $this->litteringService->getProcessedLitteringForApproval();
                    break;
                case '대기삭제':
                case '처리삭제':
                    $data = $this->litteringService->getDeletedLittering($status);
                    break;
                default:
                    $this->apiError('잘못된 상태 값입니다.', 'INVALID_INPUT', 400);
                    return;
            }
            $this->apiSuccess($data);
        } catch (Exception $e) {
            $this->apiError('목록을 불러오는 중 오류가 발생했습니다.', 'SERVER_ERROR', 500);
        }
    }

    /**
     * 무단투기 보고서를 확인합니다.
     * POST /api/littering_admin/reports/{id}/confirm에 해당합니다.
     */
    public function confirm(int $id): void
    {
        $adminId = $this->user()['employee_id'];
        
        try {
            $data = $this->getJsonInput();
            $data['id'] = $id; // URL의 ID가 사용되도록 합니다.

            $result = $this->litteringService->confirmLittering($data, $adminId);
            $this->apiSuccess($result, '민원 정보가 성공적으로 확인되었습니다.');
        } catch (Exception $e) {
            $this->apiError($e->getMessage(), 'OPERATION_FAILED', 422);
        }
    }

    /**
     * 처리된 무단투기 보고서를 승인합니다.
     * POST /api/littering_admin/reports/{id}/approve에 해당합니다.
     */
    public function approve(int $id): void
    {
        $adminId = $this->user()['employee_id'];

        try {
            $input = $this->getJsonInput();
            $correctedStatus = $input['corrected'] ?? 'o'; // 기본값을 'o' (개선)으로 설정

            $data = [
                'id' => $id,
                'corrected' => $correctedStatus
            ];

            $result = $this->litteringService->approveLittering($data, $adminId);
            $this->apiSuccess($result, '처리가 최종 승인되었습니다.');
        } catch (\InvalidArgumentException $e) {
            $this->apiBadRequest($e->getMessage());
        } catch (Exception $e) {
            $this->apiError($e->getMessage(), 'OPERATION_FAILED', 422);
        }
    }

    /**
     * 무단투기 보고서를 삭제(소프트 삭제)합니다.
     * DELETE /api/littering_admin/reports/{id}에 해당합니다.
     */
    public function destroy(int $id): void
    {
        $adminId = $this->user()['employee_id'];

        try {
            $data = $this->getJsonInput();
            $data['id'] = $id;

            $result = $this->litteringService->deleteLittering($data, $adminId);
            $this->apiSuccess($result, '민원 정보가 성공적으로 삭제되었습니다.');
        } catch (Exception $e) {
            $this->apiError($e->getMessage(), 'OPERATION_FAILED', 422);
        }
    }

    /**
     * 삭제된 무단투기 보고서를 복원합니다.
     * POST /api/littering_admin/reports/{id}/restore에 해당합니다.
     */
    public function restore(int $id): void
    {
        
        try {
            $data = ['id' => $id];
            $result = $this->litteringService->restoreLittering($data);
            $this->apiSuccess($result, '민원 정보가 성공적으로 복원되었습니다.');
        } catch (Exception $e) {
            $this->apiError($e->getMessage(), 'OPERATION_FAILED', 422);
        }
    }

    /**
     * 무단투기 보고서를 영구적으로 삭제합니다.
     * DELETE /api/littering_admin/reports/{id}/permanent에 해당합니다.
     */
    public function permanentlyDelete(int $id): void
    {

        try {
            $data = ['id' => $id];
            $result = $this->litteringService->permanentlyDeleteLittering($data);
            $this->apiSuccess($result, '민원 정보가 영구적으로 삭제되었습니다.');
        } catch (Exception $e) {
            $this->apiError($e->getMessage(), 'OPERATION_FAILED', 422);
        }
    }
}
