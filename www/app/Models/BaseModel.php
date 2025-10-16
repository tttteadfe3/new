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
     * Fill the model with an array of attributes.
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
     * Create a new model instance.
     */
    public static function make(array $attributes): self
    {
        return new static($attributes);
    }

    /**
     * Get an attribute value.
     */
    public function getAttribute(string $key): mixed
    {
        return $this->attributes[$key] ?? null;
    }

    /**
     * Set an attribute value.
     */
    public function setAttribute(string $key, mixed $value): void
    {
        if (in_array($key, $this->fillable) || empty($this->fillable)) {
            $this->attributes[$key] = $value;
        }
    }

    /**
     * Get all attributes as array.
     */
    public function toArray(): array
    {
        $attributes = $this->attributes;
        
        // Remove hidden attributes
        foreach ($this->hidden as $hidden) {
            unset($attributes[$hidden]);
        }
        
        return $attributes;
    }

    /**
     * Validate the model data.
     */
    public function validate(): bool
    {
        $this->errors = [];
        
        foreach ($this->rules as $field => $rules) {
            $value = $this->getAttribute($field);
            $fieldRules = is_string($rules) ? explode('|', $rules) : $rules;
            
            foreach ($fieldRules as $rule) {
                if (!$this->validateRule($field, $value, $rule)) {
                    break; // Stop on first validation error for this field
                }
            }
        }
        
        return empty($this->errors);
    }

    /**
     * Get validation errors.
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Validate a single rule.
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
     * Check if a value is a valid date.
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
     * Magic getter for attributes.
     */
    public function __get(string $key): mixed
    {
        return $this->getAttribute($key);
    }

    /**
     * Magic setter for attributes.
     */
    public function __set(string $key, mixed $value): void
    {
        $this->setAttribute($key, $value);
    }

    /**
     * Check if an attribute exists.
     */
    public function __isset(string $key): bool
    {
        return isset($this->attributes[$key]);
    }
}