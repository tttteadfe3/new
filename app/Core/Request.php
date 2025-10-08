<?php

namespace App\Core;

class Request
{
    private array $data = [];

    public function __construct()
    {
        $this->parseRequestData();
    }

    /**
     * Parse request data from various sources.
     */
    private function parseRequestData(): void
    {
        // Parse GET parameters
        $this->data = $_GET;

        // Parse POST data
        if ($this->method() === 'POST') {
            $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
            
            if (strpos($contentType, 'application/json') !== false) {
                // Handle JSON input
                $json = file_get_contents('php://input');
                $jsonData = json_decode($json, true);
                if ($jsonData !== null) {
                    $this->data = array_merge($this->data, $jsonData);
                }
            } else {
                // Handle form data
                $this->data = array_merge($this->data, $_POST);
            }
        }

        // Handle other HTTP methods (PUT, PATCH, DELETE)
        if (in_array($this->method(), ['PUT', 'PATCH', 'DELETE'])) {
            $input = file_get_contents('php://input');
            $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
            
            if (strpos($contentType, 'application/json') !== false) {
                $jsonData = json_decode($input, true);
                if ($jsonData !== null) {
                    $this->data = array_merge($this->data, $jsonData);
                }
            } else {
                // Parse form-encoded data
                parse_str($input, $parsedData);
                $this->data = array_merge($this->data, $parsedData);
            }
        }
    }

    /**
     * Get the current request URI path.
     */
    public static function uri(): string
    {
        return trim(
            parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH),
            '/'
        );
    }

    /**
     * Get the current request method.
     */
    public static function method(): string
    {
        return $_SERVER['REQUEST_METHOD'];
    }

    /**
     * Get all request data.
     * 
     * @return array All request data
     */
    public function all(): array
    {
        return $this->data;
    }

    /**
     * Get a specific input value.
     * 
     * @param string $key The input key
     * @param mixed $default Default value if key doesn't exist
     * @return mixed The input value or default
     */
    public function input(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * Get multiple input values.
     * 
     * @param array $keys Array of keys to retrieve
     * @return array Array of key-value pairs
     */
    public function only(array $keys): array
    {
        return array_intersect_key($this->data, array_flip($keys));
    }

    /**
     * Get all input except specified keys.
     * 
     * @param array $keys Array of keys to exclude
     * @return array Array of key-value pairs
     */
    public function except(array $keys): array
    {
        return array_diff_key($this->data, array_flip($keys));
    }

    /**
     * Check if input key exists.
     * 
     * @param string $key The input key
     * @return bool True if key exists, false otherwise
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    /**
     * Check if input key exists and is not empty.
     * 
     * @param string $key The input key
     * @return bool True if key exists and is not empty, false otherwise
     */
    public function filled(string $key): bool
    {
        return $this->has($key) && !empty($this->data[$key]);
    }

    /**
     * Get request headers.
     * 
     * @param string|null $key Specific header key (optional)
     * @return array|string|null All headers or specific header value
     */
    public function headers(string $key = null): array|string|null
    {
        $headers = getallheaders();
        
        if ($key !== null) {
            // Case-insensitive header lookup
            $headers = array_change_key_case($headers, CASE_LOWER);
            return $headers[strtolower($key)] ?? null;
        }
        
        return $headers;
    }

    /**
     * Check if request is AJAX.
     * 
     * @return bool True if AJAX request, false otherwise
     */
    public function isAjax(): bool
    {
        return strtolower($this->headers('X-Requested-With') ?? '') === 'xmlhttprequest';
    }

    /**
     * Check if request expects JSON response.
     * 
     * @return bool True if JSON expected, false otherwise
     */
    public function expectsJson(): bool
    {
        $accept = $this->headers('Accept') ?? '';
        return strpos($accept, 'application/json') !== false || $this->isAjax();
    }

    /**
     * Get uploaded files.
     * 
     * @param string|null $key Specific file key (optional)
     * @return array|null All files or specific file
     */
    public function files(string $key = null): array|null
    {
        if ($key !== null) {
            return $_FILES[$key] ?? null;
        }
        
        return $_FILES;
    }

    /**
     * Validate input data.
     * 
     * @param array $rules Validation rules
     * @return array Validation errors (empty if valid)
     */
    public function validate(array $rules): array
    {
        $errors = [];
        
        foreach ($rules as $field => $rule) {
            $value = $this->input($field);
            $ruleList = is_string($rule) ? explode('|', $rule) : $rule;
            
            foreach ($ruleList as $singleRule) {
                $error = $this->validateField($field, $value, $singleRule);
                if ($error) {
                    $errors[$field][] = $error;
                }
            }
        }
        
        return $errors;
    }

    /**
     * Validate a single field.
     * 
     * @param string $field Field name
     * @param mixed $value Field value
     * @param string $rule Validation rule
     * @return string|null Error message or null if valid
     */
    private function validateField(string $field, mixed $value, string $rule): ?string
    {
        if ($rule === 'required' && empty($value)) {
            return "The {$field} field is required.";
        }
        
        if ($rule === 'email' && !empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return "The {$field} field must be a valid email address.";
        }
        
        if (strpos($rule, 'min:') === 0) {
            $min = (int) substr($rule, 4);
            if (!empty($value) && strlen($value) < $min) {
                return "The {$field} field must be at least {$min} characters.";
            }
        }
        
        if (strpos($rule, 'max:') === 0) {
            $max = (int) substr($rule, 4);
            if (!empty($value) && strlen($value) > $max) {
                return "The {$field} field must not exceed {$max} characters.";
            }
        }
        
        return null;
    }
}