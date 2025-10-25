<?php

namespace App\Models;

class Littering extends BaseModel
{
    protected array $fillable = [
        'latitude',
        'longitude',
        'jibun_address',
        'road_address',
        'waste_type',
        'waste_type2',
        'mixed',
        'reg_photo_path',
        'reg_photo_path2',
        'proc_photo_path',
        'status',
        'corrected',
        'note',
        'rejection_reason',
        'created_by',
        'confirmed_by',
        'processed_by',
        'completed_by',
        'deleted_by',
        'created_at',
        'confirmed_at',
        'processed_at',
        'completed_at',
        'deleted_at'
    ];

    protected array $hidden = [
        'rejection_reason'
    ];

    protected array $rules = [
        'latitude' => 'required|numeric',
        'longitude' => 'required|numeric',
        'jibun_address' => 'string|max:255',
        'road_address' => 'string|max:255',
        'waste_type' => 'required|string|max:50',
        'waste_type2' => 'string|max:50',
        'mixed' => 'in:Y,N',
        'reg_photo_path' => 'required|string|max:255',
        'reg_photo_path2' => 'string|max:255',
        'proc_photo_path' => 'string|max:255',
        'status' => 'required|string|max:20',
        'corrected' => 'in:o,x,=',
        'note' => 'string',
        'rejection_reason' => 'string',
        'created_by' => 'integer',
        'confirmed_by' => 'integer',
        'processed_by' => 'integer',
        'completed_by' => 'integer',
        'deleted_by' => 'integer'
    ];

    /**
     * Check if this report is pending approval.
     */
    public function isPending(): bool
    {
        return $this->getAttribute('status') === 'pending';
    }

    /**
     * Check if this report is approved.
     */
    public function isApproved(): bool
    {
        return $this->getAttribute('status') === 'approved';
    }

    /**
     * Check if this report is rejected.
     */
    public function isRejected(): bool
    {
        return $this->getAttribute('status') === 'rejected';
    }

    /**
     * Check if this is a mixed waste disposal.
     */
    public function isMixedWaste(): bool
    {
        return $this->getAttribute('mixed') === 'Y';
    }

    /**
     * Check if the issue has been corrected.
     */
    public function isCorrected(): bool
    {
        return $this->getAttribute('corrected') === 'o';
    }

    /**
     * Check if the issue is not corrected.
     */
    public function isNotCorrected(): bool
    {
        return $this->getAttribute('corrected') === 'x';
    }

    /**
     * Check if the waste has disappeared.
     */
    public function hasDisappeared(): bool
    {
        return $this->getAttribute('corrected') === '=';
    }

    /**
     * Check if the report has been processed.
     */
    public function isProcessed(): bool
    {
        return !empty($this->getAttribute('processed_at'));
    }

    /**
     * Get the location coordinates.
     */
    public function getCoordinates(): array
    {
        return [
            'latitude' => (float) $this->getAttribute('latitude'),
            'longitude' => (float) $this->getAttribute('longitude')
        ];
    }

    /**
     * Get all waste types (primary and secondary).
     */
    public function getWasteTypes(): array
    {
        $types = [$this->getAttribute('waste_type')];
        
        $secondaryType = $this->getAttribute('waste_type2');
        if ($secondaryType) {
            $types[] = $secondaryType;
        }
        
        return array_filter($types);
    }

    /**
     * Get all photo paths.
     */
    public function getPhotos(): array
    {
        $photos = [];
        
        if ($this->getAttribute('reg_photo_path')) {
            $photos['before'] = $this->getAttribute('reg_photo_path');
        }
        
        if ($this->getAttribute('reg_photo_path2')) {
            $photos['after'] = $this->getAttribute('reg_photo_path2');
        }
        
        if ($this->getAttribute('proc_photo_path')) {
            $photos['process'] = $this->getAttribute('proc_photo_path');
        }
        
        return $photos;
    }

    /**
     * Get correction status description.
     */
    public function getCorrectionStatusDescription(): string
    {
        switch ($this->getAttribute('corrected')) {
            case 'o':
                return '개선됨';
            case 'x':
                return '미개선';
            case '=':
                return '사라짐';
            default:
                return '미처리';
        }
    }

    /**
     * Validate littering report data with business rules.
     */
    public function validate(): bool
    {
        $isValid = parent::validate();

        // Business rule: latitude should be within valid range
        $latitude = $this->getAttribute('latitude');
        if ($latitude !== null && ($latitude < -90 || $latitude > 90)) {
            $this->errors['latitude'] = '위도는 -90도에서 90도 사이여야 합니다.';
            $isValid = false;
        }

        // Business rule: longitude should be within valid range
        $longitude = $this->getAttribute('longitude');
        if ($longitude !== null && ($longitude < -180 || $longitude > 180)) {
            $this->errors['longitude'] = '경도는 -180도에서 180도 사이여야 합니다.';
            $isValid = false;
        }

        // Business rule: if mixed is Y, waste_type2 should be provided
        if ($this->getAttribute('mixed') === 'Y' && empty($this->getAttribute('waste_type2'))) {
            $this->errors['waste_type2'] = '혼합배출인 경우 부성상을 입력해야 합니다.';
            $isValid = false;
        }

        // Business rule: corrected status can only be set if status is approved
        $corrected = $this->getAttribute('corrected');
        $status = $this->getAttribute('status');
        if ($corrected && $status !== 'approved') {
            $this->errors['corrected'] = '승인된 신고에만 개선 여부를 설정할 수 있습니다.';
            $isValid = false;
        }

        // Business rule: process_photo should be provided if corrected is set
        if ($corrected && empty($this->getAttribute('proc_photo_path'))) {
            $this->errors['proc_photo_path'] = '개선 여부가 설정된 경우 처리 사진이 필요합니다.';
            $isValid = false;
        }

        return $isValid;
    }

    /**
     * Calculate distance from another point in kilometers.
     */
    public function distanceFrom(float $latitude, float $longitude): float
    {
        $myLat = (float) $this->getAttribute('latitude');
        $myLng = (float) $this->getAttribute('longitude');

        $earthRadius = 6371; // Earth's radius in kilometers

        $latDelta = deg2rad($latitude - $myLat);
        $lngDelta = deg2rad($longitude - $myLng);

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
             cos(deg2rad($myLat)) * cos(deg2rad($latitude)) *
             sin($lngDelta / 2) * sin($lngDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}