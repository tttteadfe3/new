<?php

namespace App\Models;

class SupplyDistribution extends BaseModel
{
    protected array $fillable = [
        'item_id',
        'employee_id',
        'department_id',
        'distribution_date',
        'quantity',
        'notes',
        'distributed_by',
        'is_cancelled',
        'cancelled_at',
        'cancelled_by',
        'cancel_reason'
    ];

    protected array $rules = [
        'item_id' => 'required|integer',
        'employee_id' => 'required|integer',
        'department_id' => 'required|integer',
        'distribution_date' => 'required|date',
        'quantity' => 'required|integer|min:1',
        'notes' => 'string',
        'distributed_by' => 'required|integer',
        'is_cancelled' => 'integer|in:0,1',
        'cancelled_at' => 'date',
        'cancelled_by' => 'integer',
        'cancel_reason' => 'string'
    ];

    /**
     * 지급받은 직원 정보를 가져옵니다.
     */
    public function getEmployee(): ?Employee
    {
        // 이 메서드는 리포지토리에서 구현됩니다
        return null;
    }

    /**
     * 지급받은 부서 정보를 가져옵니다.
     */
    public function getDepartment(): ?Department
    {
        // 이 메서드는 리포지토리에서 구현됩니다
        return null;
    }

    /**
     * 지급된 품목 정보를 가져옵니다.
     */
    public function getItem(): ?SupplyItem
    {
        // 이 메서드는 리포지토리에서 구현됩니다
        return null;
    }

    /**
     * 지급이 취소되었는지 확인합니다.
     */
    public function isCancelled(): bool
    {
        return (bool) $this->getAttribute('is_cancelled');
    }

    /**
     * 지급을 취소합니다.
     */
    public function cancel(int $cancelledBy, string $reason): void
    {
        $this->setAttribute('is_cancelled', 1);
        $this->setAttribute('cancelled_at', date('Y-m-d H:i:s'));
        $this->setAttribute('cancelled_by', $cancelledBy);
        $this->setAttribute('cancel_reason', $reason);
    }

    /**
     * 지급 수량을 가져옵니다.
     */
    public function getQuantity(): int
    {
        return (int) $this->getAttribute('quantity');
    }

    /**
     * 지급일을 가져옵니다.
     */
    public function getDistributionDate(): string
    {
        return $this->getAttribute('distribution_date');
    }

    /**
     * 지급 데이터의 유효성을 검증합니다.
     */
    public function validate(bool $isUpdate = false): bool
    {
        $isValid = parent::validate($isUpdate);

        // 비즈니스 규칙: 지급일은 미래일 수 없음
        $distributionDate = $this->getAttribute('distribution_date');
        if ($distributionDate && strtotime($distributionDate) > time()) {
            $this->errors['distribution_date'] = '지급일은 미래일 수 없습니다.';
            $isValid = false;
        }

        // 비즈니스 규칙: 취소된 지급은 수정할 수 없음
        if ($this->isCancelled() && !$this->getAttribute('cancelled_at')) {
            $this->errors['is_cancelled'] = '취소된 지급은 취소 일시가 필요합니다.';
            $isValid = false;
        }

        return $isValid;
    }
}