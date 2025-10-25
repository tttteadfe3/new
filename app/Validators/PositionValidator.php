<?php
// app/Validators/PositionValidator.php
namespace App\Validators;

class PositionValidator {
    public static function validate(array $data): array {
        $errors = [];

        if (empty($data['name'])) {
            $errors['name'] = 'Position name is required.';
        } elseif (strlen($data['name']) > 255) {
            $errors['name'] = 'Position name must be less than 255 characters.';
        }

        if (!isset($data['level'])) {
            $errors['level'] = 'Level is required.';
        } elseif (!is_numeric($data['level'])) {
            $errors['level'] = 'Level must be a number.';
        } elseif ($data['level'] < 0) {
            $errors['level'] = 'Level must be a non-negative number.';
        }


        return $errors;
    }
}
