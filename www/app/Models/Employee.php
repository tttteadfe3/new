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
     * Check if employee is currently active (not terminated).
     */
    public function isActive(): bool
    {
        return empty($this->getAttribute('termination_date'));
    }

    /**
     * Check if employee has pending profile updates.
     */
    public function hasPendingProfileUpdate(): bool
    {
        return $this->getAttribute('profile_update_status') === 'pending';
    }

    /**
     * Get employee's full contact information.
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
     * Get employee's clothing sizes.
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
     * Calculate years of service.
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
     * Validate employee number uniqueness (business rule).
     */
    public function validate(): bool
    {
        $isValid = parent::validate();

        // Additional business rule: employee number should be unique if provided
        $employeeNumber = $this->getAttribute('employee_number');
        if ($employeeNumber && $this->isDuplicateEmployeeNumber($employeeNumber)) {
            $this->errors['employee_number'] = '이미 사용 중인 사번입니다.';
            $isValid = false;
        }

        // Business rule: termination date should be after hire date
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
     * Check if employee number is duplicate (placeholder - would need repository).
     */
    protected function isDuplicateEmployeeNumber(string $employeeNumber): bool
    {
        // This would typically check against the database via repository
        // For now, return false as placeholder
        return false;
    }
}