<?php
namespace App\Models;

/**
 * 폐기물 수거 모델 클래스
 *
 * @property int $id
 * @property string $type
 * @property float $latitude
 * @property float $longitude
 * @property string $address
 * @property string $geocoding_status
 * @property string $issue_date
 * @property string|null $photo_path
 * @property string|null $discharge_number
 * @property string|null $submitter_name
 * @property string|null $submitter_phone
 * @property int|null $fee
 * @property string $status
 * @property string|null $admin_memo
 * @property int|null $created_by
 * @property int|null $completed_by
 * @property string $created_at
 * @property string|null $completed_at
 */
class WasteCollection extends BaseModel
{
    protected array $fillable = [
        'type',
        'latitude',
        'longitude',
        'address',
        'geocoding_status',
        'issue_date',
        'photo_path',
        'discharge_number',
        'submitter_name',
        'submitter_phone',
        'fee',
        'status',
        'admin_memo',
        'created_by',
        'completed_by',
        'created_at',
        'completed_at'
    ];

    protected array $hidden = [];

    /**
     * BaseModel의 유효성 검사기와 호환되는 유효성 검사 규칙입니다.
     * 예외를 발생시키는 사용자 지정 validate() 메서드는
     * 모든 모델에서 일관성을 보장하기 위해 제거되었습니다.
     */
    protected array $rules = [
        'latitude' => 'required|numeric',
        'longitude' => 'required|numeric',
        'address' => 'required|string|max:500',
        'issue_date' => 'required|date',
        'type' => 'required|in:field,online',
        'status' => 'in:unprocessed,processed',
        'fee' => 'numeric|min:0',
        'submitter_phone' => 'string|max:20'
    ];

    /**
     * 서식이 지정된 주소를 가져옵니다.
     */
    public function getFormattedAddress(): string
    {
        return $this->attributes['address'] ?? '';
    }

    /**
     * 서식이 지정된 수수료를 가져옵니다.
     */
    public function getFormattedFee(): string
    {
        $fee = $this->attributes['fee'] ?? 0;
        return number_format($fee) . '원';
    }

    /**
     * 수거가 처리되었는지 확인합니다.
     */
    public function isProcessed(): bool
    {
        return ($this->attributes['status'] ?? 'unprocessed') === 'processed';
    }

    /**
     * 수거가 현장 유형인지 확인합니다.
     */
    public function isFieldType(): bool
    {
        return ($this->attributes['type'] ?? 'field') === 'field';
    }

    /**
     * 수거가 온라인 유형인지 확인합니다.
     */
    public function isOnlineType(): bool
    {
        return ($this->attributes['type'] ?? 'field') === 'online';
    }

    /**
     * 사진 URL이 있는 경우 가져옵니다.
     */
    public function getPhotoUrl(): ?string
    {
        if (empty($this->attributes['photo_path'])) {
            return null;
        }
        
        $baseUrl = defined('BASE_ASSETS_URL') ? BASE_ASSETS_URL : '';
        return $baseUrl . '/uploads/' . $this->attributes['photo_path'];
    }

    /**
     * 제출자 정보를 가져옵니다.
     */
    public function getSubmitterInfo(): array
    {
        return [
            'name' => $this->attributes['submitter_name'] ?? '',
            'phone' => $this->attributes['submitter_phone'] ?? ''
        ];
    }
}
