<?php

namespace App\Models;

class Littering extends BaseModel
{
    protected array $fillable = [
        'latitude',
        'longitude',
        'address',
        'waste_type',
        'waste_type2',
        'mixed',
        'photo1',
        'photo2',
        'corrected',
        'process_photo',
        'note',
        'status',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'created_by',
        'processed_at'
    ];

    protected array $hidden = [
        'rejection_reason'
    ];

    protected array $rules = [
        'latitude' => 'required|numeric',
        'longitude' => 'required|numeric',
        'address' => 'string|max:500',
        'waste_type' => 'required|string|max:50',
        'waste_type2' => 'string|max:50',
        'mixed' => 'in:Y,N',
        'photo1' => 'string|max:255',
        'photo2' => 'string|max:255',
        'corrected' => 'in:o,x,=',
        'process_photo' => 'string|max:255',
        'note' => 'string',
        'status' => 'in:pending,approved,rejected',
        'approved_by' => 'integer',
        'created_by' => 'integer'
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
        
        if ($this->getAttribute('photo1')) {
            $photos['before'] = $this->getAttribute('photo1');
        }
        
        if ($this->getAttribute('photo2')) {
            $photos['after'] = $this->getAttribute('photo2');
        }
        
        if ($this->getAttribute('process_photo')) {
            $photos['process'] = $this->getAttribute('process_photo');
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
        if ($corrected && empty($this->getAttribute('process_photo'))) {
            $this->errors['process_photo'] = '개선 여부가 설정된 경우 처리 사진이 필요합니다.';
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