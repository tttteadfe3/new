<?php

namespace App\Controllers\Api;

use App\Services\HolidayService;
use Exception;
use App\Core\Request;
use App\Services\AuthService;
use App\Services\ViewDataService;
use App\Services\ActivityLogger;
use App\Repositories\EmployeeRepository;
use App\Core\JsonResponse;

class HolidayApiController extends BaseApiController
{
    private HolidayService $holidayService;

    public function __construct(
        Request $request,
        AuthService $authService,
        ViewDataService $viewDataService,
        ActivityLogger $activityLogger,
        EmployeeRepository $employeeRepository,
        JsonResponse $jsonResponse,
        HolidayService $holidayService
    ) {
        parent::__construct(
            $request,
            $authService,
            $viewDataService,
            $activityLogger,
            $employeeRepository,
            $jsonResponse
        );
        $this->holidayService = $holidayService;
    }

    /**
     * Get all holidays and departments.
     */
    public function index(): void
    {

        try {
            $holidays = $this->holidayService->getAllHolidays();
            $departments = $this->holidayService->getAllDepartments();

            $this->apiSuccess([
                'holidays' => $holidays,
                'departments' => $departments
            ]);
        } catch (Exception $e) {
            $this->apiError('데이터를 불러오는 중 오류가 발생했습니다.', 'SERVER_ERROR', 500);
        }
    }

    /**
     * Get a specific holiday.
     */
    public function show(int $id): void
    {

        try {
            $holiday = $this->holidayService->getHoliday($id);
            
            if (!$holiday) {
                $this->apiNotFound('휴일을 찾을 수 없습니다.');
                return;
            }

            $this->apiSuccess($holiday);
        } catch (Exception $e) {
            $this->apiError('데이터를 불러오는 중 오류가 발생했습니다.', 'SERVER_ERROR', 500);
        }
    }

    /**
     * Create a new holiday.
     */
    public function store(): void
    {

        try {
            $data = $this->getJsonInput();
            
            // Basic validation
            if (empty(trim($data['name']))) {
                $this->apiError('이름은 필수입니다.', 'VALIDATION_ERROR', 422);
                return;
            }
            if (empty($data['date'])) {
                $this->apiError('날짜는 필수입니다.', 'VALIDATION_ERROR', 422);
                return;
            }
            if (empty($data['type']) || !in_array($data['type'], ['holiday', 'workday'])) {
                $this->apiError('유효한 타입을 선택해주세요.', 'VALIDATION_ERROR', 422);
                return;
            }

            // Convert boolean to integer for validation and database storage
            $data['deduct_leave'] = (int)(isset($data['deduct_leave']) && $data['deduct_leave']);

            $holiday = $this->holidayService->createHoliday($data);

            $this->apiSuccess($holiday, '성공적으로 생성되었습니다.', 201);
        } catch (Exception $e) {
            $this->apiError($e->getMessage(), 'VALIDATION_ERROR', 422);
        }
    }

    /**
     * Update an existing holiday.
     */
    public function update(int $id): void
    {

        try {
            $data = $this->getJsonInput();
            
            // Basic validation
            if (empty(trim($data['name']))) {
                $this->apiError('이름은 필수입니다.', 'VALIDATION_ERROR', 422);
                return;
            }
            if (empty($data['date'])) {
                $this->apiError('날짜는 필수입니다.', 'VALIDATION_ERROR', 422);
                return;
            }
            if (empty($data['type']) || !in_array($data['type'], ['holiday', 'workday'])) {
                $this->apiError('유효한 타입을 선택해주세요.', 'VALIDATION_ERROR', 422);
                return;
            }

            // Convert boolean to integer for validation and database storage
            $data['deduct_leave'] = (int)(isset($data['deduct_leave']) && $data['deduct_leave']);

            $holiday = $this->holidayService->updateHoliday($id, $data);

            $this->apiSuccess($holiday, '성공적으로 수정되었습니다.');
        } catch (Exception $e) {
            $this->apiError($e->getMessage(), 'VALIDATION_ERROR', 422);
        }
    }

    /**
     * Delete a holiday.
     */
    public function destroy(int $id): void
    {

        try {
            $this->holidayService->deleteHoliday($id);
            $this->apiSuccess(null, '성공적으로 삭제되었습니다.');
        } catch (Exception $e) {
            $this->apiError('삭제 중 오류가 발생했습니다.', 'SERVER_ERROR', 404);
        }
    }
}
