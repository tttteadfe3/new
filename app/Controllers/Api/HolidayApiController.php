<?php

namespace App\Controllers\Api;

use App\Services\HolidayService;
use Exception;

class HolidayApiController extends BaseApiController
{
    private HolidayService $holidayService;

    public function __construct()
    {
        $this->holidayService = new HolidayService();
    }

    /**
     * Get all holidays and departments.
     */
    public function index(): void
    {
        $this->requireAuth('holiday_admin');

        try {
            $holidays = $this->holidayService->getAllHolidays();
            $departments = $this->holidayService->getAllDepartments();

            $this->success([
                'holidays' => $holidays,
                'departments' => $departments
            ]);
        } catch (Exception $e) {
            $this->error('데이터를 불러오는 중 오류가 발생했습니다.', ['exception' => $e->getMessage()], 500);
        }
    }

    /**
     * Get a specific holiday.
     */
    public function show(int $id): void
    {
        $this->requireAuth('holiday_admin');

        try {
            $holiday = $this->holidayService->getHoliday($id);
            
            if (!$holiday) {
                $this->notFound('휴일을 찾을 수 없습니다.');
                return;
            }

            $this->success($holiday);
        } catch (Exception $e) {
            $this->error('데이터를 불러오는 중 오류가 발생했습니다.', ['exception' => $e->getMessage()], 500);
        }
    }

    /**
     * Create a new holiday.
     */
    public function store(): void
    {
        $this->requireAuth('holiday_admin');

        try {
            $data = $this->request->all();
            
            if (empty(trim($data['name']))) {
                $this->validationError(['name' => '이름은 필수입니다.'], '입력값이 올바르지 않습니다.');
                return;
            }
            if (empty($data['date'])) {
                $this->validationError(['date' => '날짜는 필수입니다.'], '입력값이 올바르지 않습니다.');
                return;
            }
            if (empty($data['type']) || !in_array($data['type'], ['holiday', 'workday'])) {
                $this->validationError(['type' => '유효한 타입을 선택해주세요.'], '입력값이 올바르지 않습니다.');
                return;
            }

            $data['deduct_leave'] = isset($data['deduct_leave']) && $data['deduct_leave'];

            $holiday = $this->holidayService->createHoliday($data);

            $this->success($holiday, '성공적으로 생성되었습니다.', 201);
        } catch (Exception $e) {
            $this->error('생성 중 오류가 발생했습니다.', ['exception' => $e->getMessage()], 422);
        }
    }

    /**
     * Update an existing holiday.
     */
    public function update(int $id): void
    {
        $this->requireAuth('holiday_admin');

        try {
            $data = $this->request->all();
            
            if (empty(trim($data['name']))) {
                $this->validationError(['name' => '이름은 필수입니다.'], '입력값이 올바르지 않습니다.');
                return;
            }
            if (empty($data['date'])) {
                $this->validationError(['date' => '날짜는 필수입니다.'], '입력값이 올바르지 않습니다.');
                return;
            }
            if (empty($data['type']) || !in_array($data['type'], ['holiday', 'workday'])) {
                $this->validationError(['type' => '유효한 타입을 선택해주세요.'], '입력값이 올바르지 않습니다.');
                return;
            }

            $data['deduct_leave'] = isset($data['deduct_leave']) && $data['deduct_leave'];

            $holiday = $this->holidayService->updateHoliday($id, $data);

            $this->success($holiday, '성공적으로 수정되었습니다.');
        } catch (Exception $e) {
            $this->error('수정 중 오류가 발생했습니다.', ['exception' => $e->getMessage()], 422);
        }
    }

    /**
     * Delete a holiday.
     */
    public function destroy(int $id): void
    {
        $this->requireAuth('holiday_admin');

        try {
            $this->holidayService->deleteHoliday($id);
            $this->success(null, '성공적으로 삭제되었습니다.');
        } catch (Exception $e) {
            $this->error('삭제 중 오류가 발생했습니다.', ['exception' => $e->getMessage()], 404);
        }
    }
}