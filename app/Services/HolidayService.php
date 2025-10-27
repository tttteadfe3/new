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
     * 부서 정보와 함께 모든 휴일을 가져옵니다.
     * @return array
     */
    public function getAllHolidays(): array
    {
        $visibleDeptIds = $this->organizationService->getVisibleDepartmentIdsForCurrentUser();
        return $this->holidayRepository->getAll($visibleDeptIds);
    }

    /**
     * 드롭다운을 위해 모든 부서를 가져옵니다.
     * @return array
     */
    public function getAllDepartments(): array
    {
        return $this->departmentRepository->getAll();
    }

    /**
     * ID로 휴일을 가져옵니다.
     * @param int $id
     * @return array|null
     */
    public function getHoliday(int $id): ?array
    {
        return $this->holidayRepository->findById($id);
    }

    /**
     * 새 휴일을 만듭니다.
     * @param array $data
     * @return array
     * @throws Exception
     */
    public function createHoliday(array $data): array
    {
        // 모델 생성 및 유효성 검사
        $holiday = Holiday::make($data);
        
        if (!$holiday->validate()) {
            throw new Exception('유효성 검사 실패: ' . implode(', ', $holiday->getErrors()));
        }

        // 중복 휴일 확인
        if ($this->isDuplicateHoliday($data['date'], $data['department_id'] ?? null)) {
            throw new Exception('해당 날짜에 이미 휴일이 설정되어 있습니다.');
        }

        // 비즈니스 규칙: deduct_leave는 근무일이 아닌 휴일에만 의미가 있습니다.
        if ($data['type'] === 'workday' && !empty($data['deduct_leave'])) {
            throw new Exception('특정 근무일에는 연차 차감 설정을 할 수 없습니다.');
        }

        // 리포지토리용 데이터 준비
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
     * 기존 휴일을 업데이트합니다.
     * @param int $id
     * @param array $data
     * @return array
     * @throws Exception
     */
    public function updateHoliday(int $id, array $data): array
    {
        // 휴일이 존재하는지 확인
        $existingHoliday = $this->holidayRepository->findById($id);
        if (!$existingHoliday) {
            throw new Exception('휴일을 찾을 수 없습니다.');
        }

        // 모델 생성 및 유효성 검사
        $holiday = Holiday::make($data);
        
        if (!$holiday->validate()) {
            throw new Exception('유효성 검사 실패: ' . implode(', ', $holiday->getErrors()));
        }

        // 중복 휴일 확인 (현재 휴일 제외)
        if ($this->isDuplicateHoliday($data['date'], $data['department_id'] ?? null, $id)) {
            throw new Exception('해당 날짜에 이미 휴일이 설정되어 있습니다.');
        }

        // 비즈니스 규칙: deduct_leave는 근무일이 아닌 휴일에만 의미가 있습니다.
        if ($data['type'] === 'workday' && !empty($data['deduct_leave'])) {
            throw new Exception('특정 근무일에는 연차 차감 설정을 할 수 없습니다.');
        }

        // 리포지토리용 데이터 준비
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
     * 휴일을 삭제합니다.
     * @param int $id
     * @return bool
     * @throws Exception
     */
    public function deleteHoliday(int $id): bool
    {
        // 휴일이 존재하는지 확인
        $existingHoliday = $this->holidayRepository->findById($id);
        if (!$existingHoliday) {
            throw new Exception('휴일을 찾을 수 없습니다.');
        }

        return $this->holidayRepository->delete($id);
    }

    /**
     * 특정 날짜 범위 및 부서의 휴일을 가져옵니다.
     * @param string $startDate
     * @param string $endDate
     * @param int|null $departmentId
     * @return array
     */
    public function getHolidaysForDateRange(string $startDate, string $endDate, ?int $departmentId = null): array
    {
        return $this->holidayRepository->findForDateRange($startDate, $endDate, $departmentId);
    }

    /**
     * 주어진 날짜와 부서에 대해 휴일이 이미 존재하는지 확인합니다.
     * @param string $date
     * @param int|null $departmentId
     * @param int|null $excludeId
     * @return bool
     */
    private function isDuplicateHoliday(string $date, ?int $departmentId = null, ?int $excludeId = null): bool
    {
        $holidays = $this->holidayRepository->findForDateRange($date, $date, $departmentId);
        
        foreach ($holidays as $holiday) {
            // 업데이트 중인 동일한 휴일인 경우 건너뛰기
            if ($excludeId && $holiday['id'] == $excludeId) {
                continue;
            }
            
            // 날짜와 부서가 정확히 일치하는지 확인
            if ($holiday['date'] === $date) {
                // 둘 다 null(전체)이거나 둘 다 동일한 부서와 일치하는 경우
                if (($holiday['department_id'] === null && $departmentId === null) ||
                    ($holiday['department_id'] == $departmentId)) {
                    return true;
                }
            }
        }
        
        return false;
    }

    /**
     * 특정 날짜가 부서의 휴일인지 확인합니다.
     * @param string $date
     * @param int|null $departmentId
     * @return bool
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
     * 특정 날짜가 부서의 특별 근무일인지 확인합니다.
     * @param string $date
     * @param int|null $departmentId
     * @return bool
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
