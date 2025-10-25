<?php

namespace App\Services;

use App\Repositories\HolidayRepository;
use App\Repositories\DepartmentRepository;
use App\Models\Holiday;
use Exception;

class HolidayService
{
    private HolidayRepository $holidayRepository;
    private DepartmentRepository $departmentRepository;
    private OrganizationService $organizationService;

    public function __construct(
        HolidayRepository $holidayRepository,
        DepartmentRepository $departmentRepository,
        OrganizationService $organizationService
    ) {
        $this->holidayRepository = $holidayRepository;
        $this->departmentRepository = $departmentRepository;
        $this->organizationService = $organizationService;
    }

    /**
     * Get all holidays with department information.
     */
    public function getAllHolidays(): array
    {
        $visibleDeptIds = $this->organizationService->getVisibleDepartmentIdsForCurrentUser();
        return $this->holidayRepository->getAll($visibleDeptIds);
    }

    /**
     * Get all departments for dropdown.
     */
    public function getAllDepartments(): array
    {
        return $this->departmentRepository->getAll();
    }

    /**
     * Get holiday by ID.
     */
    public function getHoliday(int $id): ?array
    {
        return $this->holidayRepository->findById($id);
    }

    /**
     * Create a new holiday.
     */
    public function createHoliday(array $data): array
    {
        // Create and validate model
        $holiday = Holiday::make($data);
        
        if (!$holiday->validate()) {
            throw new Exception('Validation failed: ' . implode(', ', $holiday->getErrors()));
        }

        // Check for duplicate holidays
        if ($this->isDuplicateHoliday($data['date'], $data['department_id'] ?? null)) {
            throw new Exception('해당 날짜에 이미 휴일이 설정되어 있습니다.');
        }

        // Business rule: deduct_leave only makes sense for holidays, not workdays
        if ($data['type'] === 'workday' && !empty($data['deduct_leave'])) {
            throw new Exception('특정 근무일에는 연차 차감 설정을 할 수 없습니다.');
        }

        // Prepare data for repository
        $holidayData = [
            'name' => $data['name'],
            'date' => $data['date'],
            'type' => $data['type'],
            'department_id' => !empty($data['department_id']) ? (int)$data['department_id'] : null,
            'deduct_leave' => $data['deduct_leave'] ? 1 : 0
        ];

        $newId = $this->holidayRepository->create($holidayData);
        return $this->holidayRepository->findById($newId);
    }

    /**
     * Update an existing holiday.
     */
    public function updateHoliday(int $id, array $data): array
    {
        // Check if holiday exists
        $existingHoliday = $this->holidayRepository->findById($id);
        if (!$existingHoliday) {
            throw new Exception('Holiday not found.');
        }

        // Create and validate model
        $holiday = Holiday::make($data);
        
        if (!$holiday->validate()) {
            throw new Exception('Validation failed: ' . implode(', ', $holiday->getErrors()));
        }

        // Check for duplicate holidays (excluding current one)
        if ($this->isDuplicateHoliday($data['date'], $data['department_id'] ?? null, $id)) {
            throw new Exception('해당 날짜에 이미 휴일이 설정되어 있습니다.');
        }

        // Business rule: deduct_leave only makes sense for holidays, not workdays
        if ($data['type'] === 'workday' && !empty($data['deduct_leave'])) {
            throw new Exception('특정 근무일에는 연차 차감 설정을 할 수 없습니다.');
        }

        // Prepare data for repository
        $holidayData = [
            'name' => $data['name'],
            'date' => $data['date'],
            'type' => $data['type'],
            'department_id' => !empty($data['department_id']) ? (int)$data['department_id'] : null,
            'deduct_leave' => $data['deduct_leave'] ? 1 : 0
        ];

        $this->holidayRepository->update($id, $holidayData);
        return $this->holidayRepository->findById($id);
    }

    /**
     * Delete a holiday.
     */
    public function deleteHoliday(int $id): bool
    {
        // Check if holiday exists
        $existingHoliday = $this->holidayRepository->findById($id);
        if (!$existingHoliday) {
            throw new Exception('Holiday not found.');
        }

        return $this->holidayRepository->delete($id);
    }

    /**
     * Get holidays for a specific date range and department.
     */
    public function getHolidaysForDateRange(string $startDate, string $endDate, ?int $departmentId = null): array
    {
        return $this->holidayRepository->findForDateRange($startDate, $endDate, $departmentId);
    }

    /**
     * Check if a holiday already exists for the given date and department.
     Gpt-4-1106-preview
     */
    private function isDuplicateHoliday(string $date, ?int $departmentId = null, ?int $excludeId = null): bool
    {
        $holidays = $this->holidayRepository->findForDateRange($date, $date, $departmentId);
        
        foreach ($holidays as $holiday) {
            // Skip if this is the same holiday we're updating
            if ($excludeId && $holiday['id'] == $excludeId) {
                continue;
            }
            
            // Check for exact match on date and department
            if ($holiday['date'] === $date) {
                // If both are null (global) or both match the same department
                if (($holiday['department_id'] === null && $departmentId === null) ||
                    ($holiday['department_id'] == $departmentId)) {
                    return true;
                }
            }
        }
        
        return false;
    }

    /**
     * Check if a specific date is a holiday for a department.
     */
    public function isHoliday(string $date, ?int $departmentId = null): bool
    {
        $holidays = $this->getHolidaysForDateRange($date, $date, $departmentId);
        
        foreach ($holidays as $holiday) {
            if ($holiday['type'] === 'holiday') {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Check if a specific date is a special workday for a department.
     */
    public function isWorkday(string $date, ?int $departmentId = null): bool
    {
        $holidays = $this->getHolidaysForDateRange($date, $date, $departmentId);
        
        foreach ($holidays as $holiday) {
            if ($holiday['type'] === 'workday') {
                return true;
            }
        }
        
        return false;
    }
}
