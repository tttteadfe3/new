<?php

namespace App\Controllers\Api;

use App\Services\VehicleMaintenanceService;

use App\Core\Request;
use App\Services\AuthService;
use App\Services\ViewDataService;
use App\Services\ActivityLogger;
use App\Repositories\EmployeeRepository;
use App\Core\JsonResponse;
use App\Core\FileUploader;
use Exception;

class VehicleMaintenanceApiController extends BaseApiController
{
    private VehicleMaintenanceService $workService;


    public function __construct(
        Request $request,
        AuthService $authService,
        ViewDataService $viewDataService,
        ActivityLogger $activityLogger,
        EmployeeRepository $employeeRepository,
        JsonResponse $jsonResponse,
        VehicleMaintenanceService $workService,

    ) {
        parent::__construct($request, $authService, $viewDataService, $activityLogger, $employeeRepository, $jsonResponse);
        $this->workService = $workService;

    }

    /**
     * 작업 목록 조회
     */
    public function index(): void
    {
        try {
            $filters = [
                'type' => $this->request->input('type'), // '고장' or '정비'
                'status' => $this->request->input('status'),
                'vehicle_id' => $this->request->input('vehicle_id')
            ];

            // 빈 값 제거
            $filters = array_filter($filters, function($value) {
                return $value !== null && $value !== '';
            });

            // DataScope는 Repository에서 자동 적용됨
            $works = $this->workService->getAllWorks($filters);
            $this->apiSuccess($works);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 작업 상세 조회
     */
    public function show(int $id): void
    {
        try {
            $work = $this->workService->getWork($id);
            
            if (!$work) {
                $this->apiError('작업을 찾을 수 없습니다', 'NOT_FOUND', 404);
                return;
            }
            
            $this->apiSuccess($work);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 작업 신고 (고장 또는 정비)
     */
    /**
     * 작업 신고 (고장 또는 정비)
     */
    public function store(): void
    {
        try {
            $user = $this->authService->user();
            if (!$user || empty($user['employee_id'])) {
                $this->apiError('직원 정보가 없습니다.', 'UNAUTHORIZED', 401);
                return;
            }

            $type = $this->request->input('type'); // '고장' or '정비'
            
            // 파일 업로드 처리
            $photoPaths = $this->uploadPhotos($type);

            $data = [
                'vehicle_id' => $this->request->input('vehicle_id'),
                'type' => $type,
                'reporter_id' => $user['employee_id'],
                'work_item' => $this->request->input('work_item'),
                'description' => $this->request->input('description'),
                'mileage' => $this->request->input('mileage'),
                'status' => $this->request->input('status'),
                'photo_path' => $photoPaths[0] ?? null,
                'photo2_path' => $photoPaths[1] ?? null,
                'photo3_path' => $photoPaths[2] ?? null
            ];

            $id = $this->workService->reportWork($data);
            $this->apiSuccess(['id' => $id], '작업이 신고되었습니다', 201);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 수리 방법 결정 (고장만 해당)
     */
    public function decide(int $id): void
    {
        try {
            $repairType = $this->request->input('repair_type'); // '자체수리' or '외부수리'
            
            if (!in_array($repairType, ['자체수리', '외부수리'])) {
                $this->apiError('올바른 수리 유형을 선택하세요', 'INVALID_INPUT', 400);
                return;
            }

            $this->workService->decideRepairType($id, $repairType);
            $this->apiSuccess(null, '수리 방법이 결정되었습니다');
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 작업 시작
     */
    public function start(int $id): void
    {
        try {
            $data = [
                'worker_id' => $this->request->input('worker_id'),
                'repair_shop' => $this->request->input('repair_shop')
            ];

            $this->workService->startWork($id, $data);
            $this->apiSuccess(null, '작업이 시작되었습니다');
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 작업 완료
     */
    public function complete(int $id): void
    {
        try {
            $data = [
                'parts_used' => $this->request->input('parts_used'),
                'cost' => $this->request->input('cost')
            ];

            $this->workService->completeWork($id, $data);
            $this->apiSuccess(null, '작업이 완료되었습니다');
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 작업 확인 (Manager)
     */
    public function confirm(int $id): void
    {
        try {
            $this->workService->confirmWork($id);
            $this->apiSuccess(null, '작업이 확인되었습니다');
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 작업 삭제
     */
    public function destroy(int $id): void
    {
        try {
            $this->workService->deleteWork($id);
            $this->apiSuccess(null, '작업이 삭제되었습니다');
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 작업 수정 (통합)
     */
    public function update(int $id): void
    {
        try {
            $data = $this->request->all();
            
            // 파일 업로드 처리 (타입 정보가 있으면 전달)
            $type = $data['type'] ?? null;
            $photoPaths = $this->uploadPhotos($type);
            
            if (isset($photoPaths[0])) $data['photo_path'] = $photoPaths[0];
            if (isset($photoPaths[1])) $data['photo2_path'] = $photoPaths[1];
            if (isset($photoPaths[2])) $data['photo3_path'] = $photoPaths[2];

            $this->workService->updateWork($id, $data);
            $this->apiSuccess(null, '작업이 수정되었습니다');
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 다중 파일 업로드 처리
     */
    private function uploadPhotos(?string $type = null): array
    {
        $paths = [];
        $files = $this->request->files();
        $keys = ['photo', 'photo2', 'photo3'];
        
        // 타입에 따라 prefix 결정
        $prefix = match($type) {
            '고장' => 'repair_',
            '정비' => 'maintenance_',
            default => 'work_'
        };
        
        foreach ($keys as $index => $key) {
            if (isset($files[$key]) && $files[$key]['error'] === UPLOAD_ERR_OK) {
                try {
                    $paths[$index] = FileUploader::validateAndUpload(
                        $files[$key],
                        'vehicle',
                        $prefix
                    );
                } catch (Exception $e) {
                    // 에러 로깅하고 다음 파일 처리
                    error_log("Photo upload failed for {$key}: " . $e->getMessage());
                }
            }
        }
        
        return $paths;
    }
}
