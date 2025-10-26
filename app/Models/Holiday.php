<?php

namespace App\Models;

class Holiday extends BaseModel
{
    protected array $fillable = [
        'name',
        'date',
        'type',
        'department_id',
        'deduct_leave'
    ];

    protected array $rules = [
        'name' => 'required|string|max:255',
        'date' => 'required|date',
        'type' => 'required|in:holiday,workday',
        'department_id' => 'integer',
        'deduct_leave' => 'integer|in:0,1'
    ];

    /**
     * 이것이 휴일인지 확인합니다(근무일 아님).
     */
    public function isHoliday(): bool
    {
        return $this->getAttribute('type') === 'holiday';
    }

    /**
     * 이것이 특별 근무일인지 확인합니다.
     */
    public function isWorkday(): bool
    {
        return $this->getAttribute('type') === 'workday';
    }

    /**
     * 이 휴일이 휴가 일수를 차감하는지 확인합니다.
     */
    public function deductsLeave(): bool
    {
        return (bool) $this->getAttribute('deduct_leave');
    }

    /**
     * 이 휴일이 모든 부서에 적용되는지 확인합니다.
     */
    public function appliesToAllDepartments(): bool
    {
        return empty($this->getAttribute('department_id'));
    }

    /**
     * 이 휴일이 특정 부서에 적용되는지 확인합니다.
     */
    public function appliesToDepartment(int $departmentId): bool
    {
        if ($this->appliesToAllDepartments()) {
            return true;
        }
        
        return $this->getAttribute('department_id') === $departmentId;
    }

    /**
     * 휴일 날짜를 DateTime 객체로 가져옵니다.
     */
    public function getDateAsDateTime(): ?\DateTime
    {
        $date = $this->getAttribute('date');
        if (!$date) {
            return null;
        }

        try {
            return new \DateTime($date);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * 이 휴일이 과거인지 확인합니다.
     */
    public function isPast(): bool
    {
        $holidayDate = $this->getDateAsDateTime();
        if (!$holidayDate) {
            return false;
        }

        return $holidayDate < new \DateTime();
    }

    /**
     * 이 휴일이 미래인지 확인합니다.
     */
    public function isFuture(): bool
    {
        $holidayDate = $this->getDateAsDateTime();
        if (!$holidayDate) {
            return false;
        }

        return $holidayDate > new \DateTime();
    }

    /**
     * 이 휴일이 오늘인지 확인합니다.
     */
    public function isToday(): bool
    {
        $holidayDate = $this->getDateAsDateTime();
        if (!$holidayDate) {
            return false;
        }

        $today = new \DateTime();
        return $holidayDate->format('Y-m-d') === $today->format('Y-m-d');
    }

    /**
     * 비즈니스 규칙으로 휴일 데이터를 확인합니다.
     */
    public function validate(): bool
    {
        $isValid = parent::validate();

        // 비즈니스 규칙: deduct_leave는 근무일이 아닌 휴일에만 의미가 있습니다.
        if ($this->getAttribute('type') === 'workday' && $this->getAttribute('deduct_leave')) {
            $this->errors['deduct_leave'] = '특정 근무일에는 연차 차감 설정을 할 수 없습니다.';
            $isValid = false;
        }

        // 비즈니스 규칙: 동일한 부서의 동일한 날짜에 중복된 휴일이 있는지 확인합니다.
        if ($this->isDuplicateHoliday()) {
            $this->errors['date'] = '해당 날짜에 이미 휴일이 설정되어 있습니다.';
            $isValid = false;
        }

        return $isValid;
    }

    /**
     * 이것이 중복된 휴일인지 확인합니다(플레이스홀더 - 리포지토리가 필요함).
     */
    protected function isDuplicateHoliday(): bool
    {
        // 이것은 일반적으로 리포지토리를 통해 데이터베이스를 확인합니다.
        // 지금은 플레이스홀더로 false를 반환합니다.
        return false;
    }
}
