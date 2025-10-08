<?php
namespace App\Models;

class WasteCollection extends BaseModel
{
    protected array $fillable = [
        'latitude',
        'longitude', 
        'address',
        'photo_path',
        'user_id',
        'employee_id',
        'issue_date',
        'discharge_number',
        'submitter_name',
        'submitter_phone',
        'fee',
        'status',
        'type',
        'geocoding_status',
        'admin_memo'
    ];

    protected array $hidden = [];

    protected array $rules = [
        'latitude' => 'required|numeric',
        'longitude' => 'required|numeric',
        'address' => 'required|string|max:500',
        'user_id' => 'required|integer',
        'issue_date' => 'required|datetime',
        'type' => 'required|in:field,online',
        'fee' => 'integer|min:0'
    ];

    /**
     * Validate waste collection data
     */
    public function validate(): bool
    {
        $errors = [];

        // Required fields validation
        if (empty($this->attributes['latitude'])) {
            $errors[] = '위도가 필요합니다.';
        } elseif (!is_numeric($this->attributes['latitude'])) {
            $errors[] = '위도는 숫자여야 합니다.';
        }

        if (empty($this->attributes['longitude'])) {
            $errors[] = '경도가 필요합니다.';
        } elseif (!is_numeric($this->attributes['longitude'])) {
            $errors[] = '경도는 숫자여야 합니다.';
        }

        if (empty($this->attributes['address'])) {
            $errors[] = '주소가 필요합니다.';
        } elseif (strlen($this->attributes['address']) > 500) {
            $errors[] = '주소는 500자를 초과할 수 없습니다.';
        }

        if (empty($this->attributes['user_id'])) {
            $errors[] = '사용자 ID가 필요합니다.';
        } elseif (!is_numeric($this->attributes['user_id'])) {
            $errors[] = '사용자 ID는 숫자여야 합니다.';
        }

        if (empty($this->attributes['issue_date'])) {
            $errors[] = '발생일시가 필요합니다.';
        }

        if (empty($this->attributes['type'])) {
            $errors[] = '타입이 필요합니다.';
        } elseif (!in_array($this->attributes['type'], ['field', 'online'])) {
            $errors[] = '타입은 field 또는 online이어야 합니다.';
        }

        // Optional field validation
        if (isset($this->attributes['fee']) && (!is_numeric($this->attributes['fee']) || $this->attributes['fee'] < 0)) {
            $errors[] = '수수료는 0 이상의 숫자여야 합니다.';
        }

        if (isset($this->attributes['submitter_phone']) && !empty($this->attributes['submitter_phone'])) {
            if (!preg_match('/^[0-9-]+$/', $this->attributes['submitter_phone'])) {
                $errors[] = '전화번호 형식이 올바르지 않습니다.';
            }
        }

        if (!empty($errors)) {
            throw new \Exception(implode(' ', $errors));
        }

        return true;
    }

    /**
     * Get formatted address
     */
    public function getFormattedAddress(): string
    {
        return $this->attributes['address'] ?? '';
    }

    /**
     * Get formatted fee
     */
    public function getFormattedFee(): string
    {
        $fee = $this->attributes['fee'] ?? 0;
        return number_format($fee) . '원';
    }

    /**
     * Check if collection is processed
     */
    public function isProcessed(): bool
    {
        return ($this->attributes['status'] ?? 'unprocessed') === 'processed';
    }

    /**
     * Check if collection is from field
     */
    public function isFieldType(): bool
    {
        return ($this->attributes['type'] ?? 'field') === 'field';
    }

    /**
     * Check if collection is from online
     */
    public function isOnlineType(): bool
    {
        return ($this->attributes['type'] ?? 'field') === 'online';
    }

    /**
     * Get photo URL if exists
     */
    public function getPhotoUrl(): ?string
    {
        if (empty($this->attributes['photo_path'])) {
            return null;
        }
        
        return BASE_ASSETS_URL . '/uploads/' . $this->attributes['photo_path'];
    }

    /**
     * Get submitter info
     */
    public function getSubmitterInfo(): array
    {
        return [
            'name' => $this->attributes['submitter_name'] ?? '',
            'phone' => $this->attributes['submitter_phone'] ?? ''
        ];
    }
}