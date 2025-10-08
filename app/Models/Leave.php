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
        'approved_by',
        'rejection_reason',
        'cancellation_reason'
    ];

    protected array $hidden = [
        'rejection_reason',
        'cancellation_reason'
    ];

    protected array $rules = [
        'employee_id' => 'required|integer',
        'leave_type' => 'required|in:annual,sick,special,other,half_day',
        'start_date' => 'required|date',
        'end_date' => 'required|date',
        'days_count' => 'required|numeric',
        'reason' => 'string',
        'status' => 'in:pending,approved,rejected,cancelled,cancellation_requested',
        'approved_by' => 'integer'
    ];

    /**
     * Check if this is a pending leave request.
     */
    public function isPending(): bool
    {
        return $this->getAttribute('status') === 'pending';
    }

    /**
     * Check if this leave is approved.
     */
    public function isApproved(): bool
    {
        return $this->getAttribute('status') === 'approved';
    }

    /**
     * Check if this leave is rejected.
     */
    public function isRejected(): bool
    {
        return $this->getAttribute('status') === 'rejected';
    }

    /**
     * Check if this leave is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->getAttribute('status') === 'cancelled';
    }

    /**
     * Check if cancellation is requested.
     */
    public function isCancellationRequested(): bool
    {
        return $this->getAttribute('status') === 'cancellation_requested';
    }

    /**
     * Check if this is a half-day leave.
     */
    public function isHalfDay(): bool
    {
        return $this->getAttribute('leave_type') === 'half_day' || 
               $this->getAttribute('days_count') == 0.5;
    }

    /**
     * Check if this is an annual leave.
     */
    public function isAnnualLeave(): bool
    {
        return $this->getAttribute('leave_type') === 'annual';
    }

    /**
     * Get the leave duration in days.
     */
    public function getDuration(): float
    {
        return (float) $this->getAttribute('days_count');
    }

    /**
     * Get start date as DateTime object.
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
     * Get end date as DateTime object.
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
     * Check if the leave period overlaps with another period.
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
     * Check if this leave is in the past.
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
     * Check if this leave is in the future.
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
     * Check if this leave is currently active.
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
     * Validate leave data with business rules.
     */
    public function validate(): bool
    {
        $isValid = parent::validate();

        // Business rule: end date should be after or equal to start date
        $startDate = $this->getStartDateAsDateTime();
        $endDate = $this->getEndDateAsDateTime();

        if ($startDate && $endDate && $endDate < $startDate) {
            $this->errors['end_date'] = '종료일은 시작일보다 늦거나 같아야 합니다.';
            $isValid = false;
        }

        // Business rule: days_count should match the actual date range
        if ($startDate && $endDate) {
            $calculatedDays = $this->calculateBusinessDays($startDate, $endDate);
            $providedDays = $this->getAttribute('days_count');

            if (abs($calculatedDays - $providedDays) > 0.1) { // Allow small floating point differences
                $this->errors['days_count'] = '신청 일수가 날짜 범위와 일치하지 않습니다.';
                $isValid = false;
            }
        }

        // Business rule: half_day leave type should have 0.5 days
        if ($this->getAttribute('leave_type') === 'half_day' && $this->getAttribute('days_count') != 0.5) {
            $this->errors['days_count'] = '반차는 0.5일이어야 합니다.';
            $isValid = false;
        }

        // Business rule: cannot apply for leave in the past (except for sick leave)
        if ($startDate && $startDate < new \DateTime() && $this->getAttribute('leave_type') !== 'sick') {
            $this->errors['start_date'] = '과거 날짜로는 연차를 신청할 수 없습니다. (병가 제외)';
            $isValid = false;
        }

        return $isValid;
    }

    /**
     * Calculate business days between two dates.
     */
    protected function calculateBusinessDays(\DateTime $startDate, \DateTime $endDate): float
    {
        $days = 0;
        $current = clone $startDate;

        while ($current <= $endDate) {
            // Skip weekends (Saturday = 6, Sunday = 0)
            $dayOfWeek = (int) $current->format('w');
            if ($dayOfWeek !== 0 && $dayOfWeek !== 6) {
                $days++;
            }
            $current->add(new \DateInterval('P1D'));
        }

        return (float) $days;
    }
}