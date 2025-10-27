<?php

namespace App\Models;

class Leave extends BaseModel
{
    protected array $fillable = [
        'employee_id',
        'leave_type',
        'start_date',
        'end_date',
        'days_count',
        'reason',
        'status',
        'approver_employee_id',
        'rejection_reason',
        'cancellation_reason'
    ];

    protected array $hidden = [
        'rejection_reason',
        'cancellation_reason'
    ];

    protected array $rules = [
        'employee_id' => 'required|integer',
        'leave_type' => 'required|in:연차,병가,특별휴가,기타,반차',
        'start_date' => 'required|date',
        'end_date' => 'required|date',
        'days_count' => 'required|numeric',
        'reason' => 'string',
        'status' => 'in:대기,승인,반려,취소,취소요청',
        'approver_employee_id' => 'integer'
    ];

    /**
     * 이것이 보류 중인 휴가 요청인지 확인합니다.
     */
    public function isPending(): bool
    {
        return $this->getAttribute('status') === '대기';
    }

    /**
     * 이 휴가가 승인되었는지 확인합니다.
     */
    public function isApproved(): bool
    {
        return $this->getAttribute('status') === '승인';
    }

    /**
     * 이 휴가가 거부되었는지 확인합니다.
     */
    public function isRejected(): bool
    {
        return $this->getAttribute('status') === '반려';
    }

    /**
     * 이 휴가가 취소되었는지 확인합니다.
     */
    public function isCancelled(): bool
    {
        return $this->getAttribute('status') === '취소';
    }

    /**
     * 취소가 요청되었는지 확인합니다.
     */
    public function isCancellationRequested(): bool
    {
        return $this->getAttribute('status') === '취소요청';
    }

    /**
     * 이것이 반차 휴가인지 확인합니다.
     */
    public function isHalfDay(): bool
    {
        return $this->getAttribute('leave_type') === '반차' ||
               $this->getAttribute('days_count') == 0.5;
    }

    /**
     * 이것이 연차 휴가인지 확인합니다.
     */
    public function isAnnualLeave(): bool
    {
        return $this->getAttribute('leave_type') === '연차';
    }

    /**
     * 휴가 기간을 일 단위로 가져옵니다.
     */
    public function getDuration(): float
    {
        return (float) $this->getAttribute('days_count');
    }

    /**
     * 시작 날짜를 DateTime 객체로 가져옵니다.
     */
    public function getStartDateAsDateTime(): ?\DateTime
    {
        $date = $this->getAttribute('start_date');
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
     * 종료 날짜를 DateTime 객체로 가져옵니다.
     */
    public function getEndDateAsDateTime(): ?\DateTime
    {
        $date = $this->getAttribute('end_date');
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
     * 휴가 기간이 다른 기간과 겹치는지 확인합니다.
     */
    public function overlapsWith(\DateTime $startDate, \DateTime $endDate): bool
    {
        $leaveStart = $this->getStartDateAsDateTime();
        $leaveEnd = $this->getEndDateAsDateTime();

        if (!$leaveStart || !$leaveEnd) {
            return false;
        }

        return $leaveStart <= $endDate && $leaveEnd >= $startDate;
    }

    /**
     * 이 휴가가 과거인지 확인합니다.
     */
    public function isPast(): bool
    {
        $endDate = $this->getEndDateAsDateTime();
        if (!$endDate) {
            return false;
        }

        return $endDate < new \DateTime();
    }

    /**
     * 이 휴가가 미래인지 확인합니다.
     */
    public function isFuture(): bool
    {
        $startDate = $this->getStartDateAsDateTime();
        if (!$startDate) {
            return false;
        }

        return $startDate > new \DateTime();
    }

    /**
     * 이 휴가가 현재 활성 상태인지 확인합니다.
     */
    public function isActive(): bool
    {
        $startDate = $this->getStartDateAsDateTime();
        $endDate = $this->getEndDateAsDateTime();
        $now = new \DateTime();

        if (!$startDate || !$endDate) {
            return false;
        }

        return $startDate <= $now && $endDate >= $now && $this->isApproved();
    }

    /**
     * 비즈니스 규칙으로 휴가 데이터를 확인합니다.
     */
    public function validate(): bool
    {
        $isValid = parent::validate();

        // 비즈니스 규칙: 종료일은 시작일보다 늦거나 같아야 합니다.
        $startDate = $this->getStartDateAsDateTime();
        $endDate = $this->getEndDateAsDateTime();

        if ($startDate && $endDate && $endDate < $startDate) {
            $this->errors['end_date'] = '종료일은 시작일보다 늦거나 같아야 합니다.';
            $isValid = false;
        }

        // 비즈니스 규칙: days_count는 실제 날짜 범위와 일치해야 합니다.
        if ($startDate && $endDate) {
            $calculatedDays = $this->calculateBusinessDays($startDate, $endDate);
            $providedDays = $this->getAttribute('days_count');

            if (abs($calculatedDays - $providedDays) > 0.1) { // 작은 부동 소수점 차이 허용
                $this->errors['days_count'] = '신청 일수가 날짜 범위와 일치하지 않습니다.';
                $isValid = false;
            }
        }

        // 비즈니스 규칙: half_day 휴가 유형은 0.5일이어야 합니다.
        if ($this->getAttribute('leave_type') === '반차' && $this->getAttribute('days_count') != 0.5) {
            $this->errors['days_count'] = '반차는 0.5일이어야 합니다.';
            $isValid = false;
        }

        // 비즈니스 규칙: 과거 날짜로는 휴가를 신청할 수 없습니다(병가 제외).
        if ($startDate && $startDate < new \DateTime() && $this->getAttribute('leave_type') !== '병가') {
            $this->errors['start_date'] = '과거 날짜로는 연차를 신청할 수 없습니다. (병가 제외)';
            $isValid = false;
        }

        return $isValid;
    }

    /**
     * 두 날짜 사이의 영업일을 계산합니다.
     */
    protected function calculateBusinessDays(\DateTime $startDate, \DateTime $endDate): float
    {
        $days = 0;
        $current = clone $startDate;

        while ($current <= $endDate) {
            // 주말 건너뛰기 (토요일 = 6, 일요일 = 0)
            $dayOfWeek = (int) $current->format('w');
            if ($dayOfWeek !== 0 && $dayOfWeek !== 6) {
                $days++;
            }
            $current->add(new \DateInterval('P1D'));
        }

        return (float) $days;
    }
}
