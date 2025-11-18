<?php

namespace App\Models;

abstract class BaseModel
{
    protected array $attributes = [];
    protected array $fillable = [];
    protected array $hidden = [];
    protected array $rules = [];
    protected array $errors = [];

    public function __construct(array $attributes = [])
    {
        $this->fill($attributes);
    }

    /**
     * 속성 배열로 모델을 채웁니다.
     */
    public function fill(array $attributes): self
    {
        foreach ($attributes as $key => $value) {
            if (in_array($key, $this->fillable) || empty($this->fillable)) {
                $this->attributes[$key] = $value;
            }
        }
        return $this;
    }

    /**
     * 새 모델 인스턴스를 만듭니다.
     */
    public static function make(array $attributes): self
    {
        return new static($attributes);
    }

    /**
     * 속성 값을 가져옵니다.
     */
    public function getAttribute(string $key): mixed
    {
        return $this->attributes[$key] ?? null;
    }

    /**
     * 속성 값을 설정합니다.
     */
    public function setAttribute(string $key, mixed $value): void
    {
        if (in_array($key, $this->fillable) || empty($this->fillable)) {
            $this->attributes[$key] = $value;
        }
    }

    /**
     * 모든 속성을 배열로 가져옵니다.
     */
    public function toArray(): array
    {
        $attributes = $this->attributes;
        
        // 숨겨진 속성 제거
        foreach ($this->hidden as $hidden) {
            unset($attributes[$hidden]);
        }
        
        return $attributes;
    }

    /**
     * 모델 데이터를 확인합니다.
     * @param bool $isUpdate 업데이트 시나리오인지 여부를 나타냅니다. true이면 현재 설정된 속성만 검사합니다.
     */
    public function validate(bool $isUpdate = false): bool
    {
        $this->errors = [];
        
        $rulesToValidate = $isUpdate ? array_intersect_key($this->rules, $this->attributes) : $this->rules;

        foreach ($rulesToValidate as $field => $rules) {
            $value = $this->getAttribute($field);
            $fieldRules = is_string($rules) ? explode('|', $rules) : $rules;
            
            foreach ($fieldRules as $rule) {
                if (!$this->validateRule($field, $value, $rule)) {
                    break; // 이 필드에 대한 첫 번째 유효성 검사 오류에서 중지
                }
            }
        }
        
        return empty($this->errors);
    }

    /**
     * 유효성 검사 오류를 가져옵니다.
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * 단일 규칙을 확인합니다.
     */
    protected function validateRule(string $field, mixed $value, string $rule): bool
    {
        $ruleParts = explode(':', $rule);
        $ruleName = $ruleParts[0];
        $ruleValue = $ruleParts[1] ?? null;
        
        switch ($ruleName) {
            case 'required':
                if (empty($value) && $value !== '0' && $value !== 0) {
                    $this->errors[$field] = "{$field}은(는) 필수 항목입니다.";
                    return false;
                }
                break;
                
            case 'string':
                if (!is_string($value) && $value !== null) {
                    $this->errors[$field] = "{$field}은(는) 문자열이어야 합니다.";
                    return false;
                }
                break;
                
            case 'integer':
                if (!is_int($value) && !ctype_digit((string)$value) && $value !== null) {
                    $this->errors[$field] = "{$field}은(는) 정수여야 합니다.";
                    return false;
                }
                break;
                
            case 'numeric':
                if (!is_numeric($value) && $value !== null) {
                    $this->errors[$field] = "{$field}은(는) 숫자여야 합니다.";
                    return false;
                }
                break;
                
            case 'email':
                if ($value !== null && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->errors[$field] = "{$field}은(는) 유효한 이메일 주소여야 합니다.";
                    return false;
                }
                break;
                
            case 'date':
                if ($value !== null && !$this->isValidDate($value)) {
                    $this->errors[$field] = "{$field}은(는) 유효한 날짜여야 합니다.";
                    return false;
                }
                break;
                
            case 'max':
                if ($value !== null && strlen((string)$value) > (int)$ruleValue) {
                    $this->errors[$field] = "{$field}은(는) {$ruleValue}자를 초과할 수 없습니다.";
                    return false;
                }
                break;
                
            case 'min':
                if ($value !== null && strlen((string)$value) < (int)$ruleValue) {
                    $this->errors[$field] = "{$field}은(는) 최소 {$ruleValue}자 이상이어야 합니다.";
                    return false;
                }
                break;
                
            case 'in':
                $allowedValues = explode(',', $ruleValue);
                if ($value !== null && !in_array($value, $allowedValues)) {
                    $this->errors[$field] = "{$field}은(는) " . implode(', ', $allowedValues) . " 중 하나여야 합니다.";
                    return false;
                }
                break;
        }
        
        return true;
    }

    /**
     * 값이 유효한 날짜인지 확인합니다.
     */
    protected function isValidDate(string $date): bool
    {
        $formats = ['Y-m-d', 'Y-m-d H:i:s', 'd/m/Y', 'm/d/Y'];
        
        foreach ($formats as $format) {
            $dateTime = \DateTime::createFromFormat($format, $date);
            if ($dateTime && $dateTime->format($format) === $date) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * 속성에 대한 매직 게터입니다.
     */
    public function __get(string $key): mixed
    {
        return $this->getAttribute($key);
    }

    /**
     * 속성에 대한 매직 세터입니다.
     */
    public function __set(string $key, mixed $value): void
    {
        $this->setAttribute($key, $value);
    }

    /**
     * 속성이 있는지 확인합니다.
     */
    public function __isset(string $key): bool
    {
        return isset($this->attributes[$key]);
    }
}
