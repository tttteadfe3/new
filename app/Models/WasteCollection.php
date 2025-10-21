<?php
namespace App\Models;

class WasteCollection extends BaseModel
{
    protected array $fillable = [
        'latitude',
        'longitude',
        'address',
        'photo_path',
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

    /**
     * Validation rules that are compatible with the BaseModel's validator.
     * The custom validate() method that threw exceptions has been removed
     * to ensure consistency across all models.
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
        
        $baseUrl = defined('BASE_ASSETS_URL') ? BASE_ASSETS_URL : '';
        return $baseUrl . '/uploads/' . $this->attributes['photo_path'];
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