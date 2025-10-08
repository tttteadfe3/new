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
     * Check if this is a holiday (not a workday).
     */
    public function isHoliday(): bool
    {
        return $this->getAttribute('type') === 'holiday';
    }

    /**
     * Check if this is a special workday.
     */
    public function isWorkday(): bool
    {
        return $this->getAttribute('type') === 'workday';
    }

    /**
     * Check if this holiday deducts leave days.
     */
    public function deductsLeave(): bool
    {
        return (bool) $this->getAttribute('deduct_leave');
    }

    /**
     * Check if this holiday applies to all departments.
     */
    public function appliesToAllDepartments(): bool
    {
        return empty($this->getAttribute('department_id'));
    }

    /**
     * Check if this holiday applies to a specific department.
     */
    public function appliesToDepartment(int $departmentId): bool
    {
        if ($this->appliesToAllDepartments()) {
            return true;
        }
        
        return $this->getAttribute('department_id') === $departmentId;
    }

    /**
     * Get the holiday date as DateTime object.
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
     * Check if this holiday is in the past.
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
     * Check if this holiday is in the future.
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
     * Check if this holiday is today.
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
     * Validate holiday data with business rules.
     */
    public function validate(): bool
    {
        $isValid = parent::validate();

        // Business rule: deduct_leave only makes sense for holidays, not workdays
        if ($this->getAttribute('type') === 'workday' && $this->getAttribute('deduct_leave')) {
            $this->errors['deduct_leave'] = '특정 근무일에는 연차 차감 설정을 할 수 없습니다.';
            $isValid = false;
        }

        // Business rule: check for duplicate holidays on the same date for same department
        if ($this->isDuplicateHoliday()) {
            $this->errors['date'] = '해당 날짜에 이미 휴일이 설정되어 있습니다.';
            $isValid = false;
        }

        return $isValid;
    }

    /**
     * Check if this is a duplicate holiday (placeholder - would need repository).
     */
    protected function isDuplicateHoliday(): bool
    {
        // This would typically check against the database via repository
        // For now, return false as placeholder
        return false;
    }
}