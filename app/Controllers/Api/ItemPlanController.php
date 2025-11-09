<?php

namespace App\Controllers\Api;

use App\Services\ItemPlanService;
use Exception;
use InvalidArgumentException;
use App\Core\Request;
use App\Services\AuthService;
use App\Services\ViewDataService;
use App\Services\ActivityLogger;
use App\Repositories\EmployeeRepository;
use App\Core\JsonResponse;
use App\Repositories\LogRepository;

class ItemPlanController extends BaseApiController
{
    private ItemPlanService $itemPlanService;
    private LogRepository $logRepository;

    public function __construct(
        Request $request,
        AuthService $authService,
        ViewDataService $viewDataService,
        ActivityLogger $activityLogger,
        EmployeeRepository $employeeRepository,
        JsonResponse $jsonResponse,
        ItemPlanService $itemPlanService,
        LogRepository $logRepository
    ) {
        parent::__construct($request, $authService, $viewDataService, $activityLogger, $employeeRepository, $jsonResponse);
        $this->itemPlanService = $itemPlanService;
        $this->logRepository = $logRepository;
    }

    /**
     * 특정 연도의 계획 목록을 가져옵니다.
     */
    public function index(): void
    {
        try {
            $year = $this->request->input('year');
            if (!$year) {
                $this->apiBadRequest('year는 필수입니다.');
                return;
            }
            $plans = $this->itemPlanService->getPlansByYear((int)$year);
            $this->apiSuccess($plans);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 새 계획을 생성합니다.
     */
    public function store(): void
    {
        try {
            $data = $this->getJsonInput();
            $newPlanId = $this->itemPlanService->createPlan($data);

            if ($newPlanId) {
                $currentUser = $this->authService->user();
                $this->logRepository->insert([
                    'user_id' => $currentUser['id'],
                    'employee_id' => $currentUser['employee_id'],
                    'action' => 'item_plan_create',
                    'details' => json_encode(['id' => $newPlanId, 'data' => $data]),
                ]);
                $this->apiSuccess(['id' => $newPlanId], '계획이 성공적으로 생성되었습니다.');
            } else {
                $this->apiError('계획 생성에 실패했습니다.');
            }
        } catch (InvalidArgumentException $e) {
            $this->apiBadRequest($e->getMessage());
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 특정 계획을 업데이트합니다.
     * @param int $id
     */
    public function update(int $id): void
    {
        try {
            $data = $this->getJsonInput();
            $success = $this->itemPlanService->updatePlan($id, $data);

            if ($success) {
                $currentUser = $this->authService->user();
                $this->logRepository->insert([
                    'user_id' => $currentUser['id'],
                    'employee_id' => $currentUser['employee_id'],
                    'action' => 'item_plan_update',
                    'details' => json_encode(['id' => $id, 'data' => $data]),
                ]);
                $this->apiSuccess(null, '계획이 성공적으로 수정되었습니다.');
            } else {
                $this->apiError('계획 수정에 실패했거나 변경된 내용이 없습니다.');
            }
        } catch (InvalidArgumentException $e) {
            $this->apiBadRequest($e->getMessage());
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 특정 계획을 삭제합니다.
     * @param int $id
     */
    public function destroy(int $id): void
    {
        try {
            $success = $this->itemPlanService->deletePlan($id);
            if ($success) {
                $currentUser = $this->authService->user();
                $this->logRepository->insert([
                    'user_id' => $currentUser['id'],
                    'employee_id' => $currentUser['employee_id'],
                    'action' => 'item_plan_delete',
                    'details' => json_encode(['id' => $id]),
                ]);
                $this->apiSuccess(null, '계획이 성공적으로 삭제되었습니다.');
            } else {
                $this->apiError('계획 삭제에 실패했습니다.');
            }
        } catch (InvalidArgumentException $e) {
            $this->apiBadRequest($e->getMessage());
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 계획 목록을 CSV로 내보냅니다.
     */
    public function export(): void
    {
        try {
            $year = $this->request->input('year');
            if (!$year) {
                // Not using apiBadRequest because this is not an AJAX call
                http_response_code(400);
                echo "year는 필수입니다.";
                return;
            }
            $plans = $this->itemPlanService->getPlansByYear((int)$year);

            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="plans_' . $year . '.csv"');

            $output = fopen('php://output', 'w');
            fputcsv($output, ["분류", "품목명", "단가", "예정 수량", "예산 금액", "비고", "등록자"]);

            foreach ($plans as $plan) {
                fputcsv($output, [
                    $plan['category_name'],
                    $plan['item_name'],
                    $plan['unit_price'],
                    $plan['quantity'],
                    $plan['budget'],
                    $plan['note'],
                    $plan['creator_name'],
                ]);
            }
            fclose($output);

        } catch (Exception $e) {
            http_response_code(500);
            echo "Export failed: " . $e->getMessage();
        }
    }

    /**
     * CSV 파일로 계획을 일괄 등록합니다.
     */
    public function import(): void
    {
        try {
            if (empty($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
                $this->apiBadRequest("파일이 업로드되지 않았거나 오류가 발생했습니다.");
                return;
            }

            $file = $_FILES['import_file']['tmp_name'];
            $year = $this->request->input('year');
            if (!$year) {
                $this->apiBadRequest("연도를 지정해야 합니다.");
                return;
            }

            $result = $this->itemPlanService->createPlansFromCsv($file, (int)$year);

            $currentUser = $this->authService->user();
            $this->logRepository->insert([
                'user_id' => $currentUser['id'],
                'employee_id' => $currentUser['employee_id'],
                'action' => 'item_plan_import',
                'details' => json_encode(['year' => $year, 'created_count' => $result['created']]),
            ]);

            $this->apiSuccess($result, "{$result['created']}개의 계획을 성공적으로 등록했습니다.");

        } catch (InvalidArgumentException $e) {
            $this->apiBadRequest($e->getMessage());
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }
}
