<?php

namespace App\Models;

class Employee extends BaseModel
{
    protected array $fillable = [
        'name',
        'employee_number',
        'hire_date',
        'termination_date',
        'clothing_top_size',
        'clothing_bottom_size',
        'shoe_size',
        'profile_update_status',
        'profile_update_rejection_reason',
        'pending_profile_data',
        'phone_number',
        'address',
        'emergency_contact_name',
        'emergency_contact_relation',
        'department_id',
        'position_id'
    ];

    protected array $hidden = [
        'pending_profile_data',
        'profile_update_rejection_reason'
    ];

    protected array $rules = [
        'name' => 'required|string|max:255',
        'employee_number' => 'string|max:50',
        'hire_date' => 'date',
        'termination_date' => 'date',
        'clothing_top_size' => 'string|max:50',
        'clothing_bottom_size' => 'string|max:50',
        'shoe_size' => 'string|max:50',
        'profile_update_status' => 'in:none,pending,rejected',
        'phone_number' => 'string|max:255',
        'address' => 'string',
        'emergency_contact_name' => 'string|max:255',
        'emergency_contact_relation' => 'string|max:50',
        'department_id' => 'integer',
        'position_id' => 'integer'
    ];

    /**
     * 직원이 현재 재직 중인지 확인합니다 (퇴사하지 않음).
     */
    public function isActive(): bool
    {
        return empty($this->getAttribute('termination_date'));
    }

    /**
     * 직원에게 보류 중인 프로필 업데이트가 있는지 확인합니다.
     */
    public function hasPendingProfileUpdate(): bool
    {
        return $this->getAttribute('profile_update_status') === 'pending';
    }

    /**
     * 직원의 전체 연락처 정보를 가져옵니다.
     */
    public function getContactInfo(): array
    {
        return [
            'phone_number' => $this->getAttribute('phone_number'),
            'address' => $this->getAttribute('address'),
            'emergency_contact_name' => $this->getAttribute('emergency_contact_name'),
            'emergency_contact_relation' => $this->getAttribute('emergency_contact_relation')
        ];
    }

    /**
     * 직원의 의류 사이즈를 가져옵니다.
     */
    public function getClothingSizes(): array
    {
        return [
            'top_size' => $this->getAttribute('clothing_top_size'),
            'bottom_size' => $this->getAttribute('clothing_bottom_size'),
            'shoe_size' => $this->getAttribute('shoe_size')
        ];
    }

    /**
     * 근속 연수를 계산합니다.
     */
    public function getYearsOfService(): int
    {
        $hireDate = $this->getAttribute('hire_date');
        if (!$hireDate) {
            return 0;
        }

        $hire = new \DateTime($hireDate);
        $endDate = $this->getAttribute('termination_date') 
            ? new \DateTime($this->getAttribute('termination_date'))
            : new \DateTime();

        return $hire->diff($endDate)->y;
    }

    /**
     * 사번 고유성을 확인합니다 (비즈니스 규칙).
     */
    public function validate(): bool
    {
        $isValid = parent::validate();

        // 추가 비즈니스 규칙: 사번은 제공된 경우 고유해야 합니다
        $employeeNumber = $this->getAttribute('employee_number');
        if ($employeeNumber && $this->isDuplicateEmployeeNumber($employeeNumber)) {
            $this->errors['employee_number'] = '이미 사용 중인 사번입니다.';
            $isValid = false;
        }

        // 비즈니스 규칙: 퇴사일은 입사일보다 늦어야 합니다
        $hireDate = $this->getAttribute('hire_date');
        $terminationDate = $this->getAttribute('termination_date');
        
        if ($hireDate && $terminationDate) {
            $hire = new \DateTime($hireDate);
            $termination = new \DateTime($terminationDate);
            
            if ($termination <= $hire) {
                $this->errors['termination_date'] = '퇴사일은 입사일보다 늦어야 합니다.';
                $isValid = false;
            }
        }

        return $isValid;
    }

    /**
     * 사번이 중복되는지 확인합니다 (플레이스홀더 - 리포지토리가 필요함).
     */
    protected function isDuplicateEmployeeNumber(string $employeeNumber): bool
    {
        // 이것은 일반적으로 리포지토리를 통해 데이터베이스를 확인합니다
        // 지금은 플레이스홀더로 false를 반환합니다
        return false;
    }
}
