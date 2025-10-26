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
        'reg_photo_path',
        'reg_photo_path2',
        'proc_photo_path',
        'status',
        'corrected',
        'note',
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

    protected array $hidden = [];

    protected array $rules = [
        'latitude' => 'required|numeric',
        'longitude' => 'required|numeric',
        'jibun_address' => 'string|max:255',
        'road_address' => 'string|max:255',
        'waste_type' => 'required|string|max:50',
        'waste_type2' => 'string|max:50',
        'reg_photo_path' => 'required|string|max:255',
        'reg_photo_path2' => 'string|max:255',
        'proc_photo_path' => 'string|max:255',
        'status' => 'required|string|max:20',
        'corrected' => 'in:o,x,=',
        'note' => 'string',
        'created_by' => 'integer',
        'confirmed_by' => 'integer',
        'processed_by' => 'integer',
        'completed_by' => 'integer',
        'deleted_by' => 'integer'
    ];

    /**
     * 이 보고서가 승인 대기 중인지 확인합니다.
     */
    public function isPending(): bool
    {
        return $this->getAttribute('status') === 'pending';
    }

    /**
     * 이 보고서가 승인되었는지 확인합니다.
     */
    public function isApproved(): bool
    {
        return $this->getAttribute('status') === 'approved';
    }

    /**
     * 이 보고서가 거부되었는지 확인합니다.
     */
    public function isRejected(): bool
    {
        return $this->getAttribute('status') === 'rejected';
    }

    /**
     * 문제가 해결되었는지 확인합니다.
     */
    public function isCorrected(): bool
    {
        return $this->getAttribute('corrected') === 'o';
    }

    /**
     * 문제가 해결되지 않았는지 확인합니다.
     */
    public function isNotCorrected(): bool
    {
        return $this->getAttribute('corrected') === 'x';
    }

    /**
     * 쓰레기가 사라졌는지 확인합니다.
     */
    public function hasDisappeared(): bool
    {
        return $this->getAttribute('corrected') === '=';
    }

    /**
     * 보고서가 처리되었는지 확인합니다.
     */
    public function isProcessed(): bool
    {
        return !empty($this->getAttribute('processed_at'));
    }

    /**
     * 위치 좌표를 가져옵니다.
     */
    public function getCoordinates(): array
    {
        return [
            'latitude' => (float) $this->getAttribute('latitude'),
            'longitude' => (float) $this->getAttribute('longitude')
        ];
    }

    /**
     * 모든 폐기물 유형(주 및 보조)을 가져옵니다.
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
     * 모든 사진 경로를 가져옵니다.
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
     * 수정 상태 설명을 가져옵니다.
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
     * 비즈니스 규칙으로 무단투기 보고서 데이터를 확인합니다.
     */
    public function validate(): bool
    {
        $isValid = parent::validate();

        // 비즈니스 규칙: 위도는 유효한 범위 내에 있어야 합니다.
        $latitude = $this->getAttribute('latitude');
        if ($latitude !== null && ($latitude < -90 || $latitude > 90)) {
            $this->errors['latitude'] = '위도는 -90도에서 90도 사이여야 합니다.';
            $isValid = false;
        }

        // 비즈니스 규칙: 경도는 유효한 범위 내에 있어야 합니다.
        $longitude = $this->getAttribute('longitude');
        if ($longitude !== null && ($longitude < -180 || $longitude > 180)) {
            $this->errors['longitude'] = '경도는 -180도에서 180도 사이여야 합니다.';
            $isValid = false;
        }

        // 비즈니스 규칙: 수정된 상태는 상태가 승인된 경우에만 설정할 수 있습니다.
        $corrected = $this->getAttribute('corrected');
        $status = $this->getAttribute('status');
        if ($corrected && $status !== 'approved') {
            $this->errors['corrected'] = '승인된 신고에만 개선 여부를 설정할 수 있습니다.';
            $isValid = false;
        }

        // 비즈니스 규칙: 수정된 경우 처리 사진을 제공해야 합니다.
        if ($corrected && empty($this->getAttribute('proc_photo_path'))) {
            $this->errors['proc_photo_path'] = '개선 여부가 설정된 경우 처리 사진이 필요합니다.';
            $isValid = false;
        }

        return $isValid;
    }

    /**
     * 다른 지점과의 거리를 킬로미터 단위로 계산합니다.
     */
    public function distanceFrom(float $latitude, float $longitude): float
    {
        $myLat = (float) $this->getAttribute('latitude');
        $myLng = (float) $this->getAttribute('longitude');

        $earthRadius = 6371; // 지구의 반지름(킬로미터)

        $latDelta = deg2rad($latitude - $myLat);
        $lngDelta = deg2rad($longitude - $myLng);

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
             cos(deg2rad($myLat)) * cos(deg2rad($latitude)) *
             sin($lngDelta / 2) * sin($lngDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
