<?php

namespace App\Controllers\Api;

use App\Services\HolidayService;
use App\Repositories\HolidayRepository;

class HolidayApiController extends BaseApiController
{
    private HolidayService $holidayService;

    public function __construct()
    {
        parent::__construct();
        $this->holidayService = new HolidayService();
    }

    /**
     * Get all holidays
     */
    public function index(): void
    {
        $this->requireAuth('holiday_admin');
        
        try {
            $holidays = HolidayRepository::getAll();
            $this->apiSuccess($holidays);
        } catch (\Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * Get a specific holiday
     */
    public function show(int $id): void
    {
        $this->requireAuth('holiday_admin');
        
        try {
            $holiday = HolidayRepository::findById($id);
            
            if ($holiday) {
                $this->apiSuccess($holiday);
            } else {
                $this->apiNotFound('Holiday not found');
            }
        } catch (\Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * Create a new holiday
     */
    public function store(): void
    {
        $this->requireAuth('holiday_admin');
        
        try {
            $data = $_POST;
            
            if (empty($data)) {
                $this->apiBadRequest('Holiday data is required');
                return;
            }
            
            $result = $this->holidayService->createHoliday($data);
            
            if ($result) {
                $this->apiSuccess(['new_id' => $result], '휴일이 성공적으로 생성되었습니다.');
            } else {
                $this->apiError('휴일 생성 중 오류가 발생했습니다.');
            }
        } catch (\Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * Update an existing holiday
     */
    public function update(int $id): void
    {
        $this->requireAuth('holiday_admin');
        
        try {
            $data = $_POST;
            
            if (empty($data)) {
                $this->apiBadRequest('Holiday data is required');
                return;
            }
            
            $result = $this->holidayService->updateHoliday($id, $data);
            
            if ($result) {
                $this->apiSuccess(null, '휴일이 성공적으로 수정되었습니다.');
            } else {
                $this->apiError('휴일 수정 중 오류가 발생했습니다.');
            }
        } catch (\Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * Delete a holiday
     */
    public function destroy(int $id): void
    {
        $this->requireAuth('holiday_admin');
        
        try {
            $result = $this->holidayService->deleteHoliday($id);
            
            if ($result) {
                $this->apiSuccess(null, '휴일이 성공적으로 삭제되었습니다.');
            } else {
                $this->apiError('휴일 삭제 중 오류가 발생했습니다.');
            }
        } catch (\Exception $e) {
            $this->handleException($e);
        }
    }
}