<?php

/**
 * Validator Class
 * Simple validation helpers for form data
 */

class Validator
{
    private array $errors = [];

    /**
     * Validate data against rules
     */
    public function validate(array $data, array $rules): array
    {
        $this->errors = [];

        foreach ($rules as $field => $fieldRules) {
            $value = $data[$field] ?? null;
            $this->validateField($field, $value, $fieldRules);
        }

        return $this->errors;
    }

    /**
     * Validate individual field
     */
    private function validateField(string $field, mixed $value, array $rules): void
    {
        foreach ($rules as $rule) {
            if (is_string($rule)) {
                $this->applyRule($field, $value, $rule);
            } elseif (is_array($rule)) {
                $ruleName = array_shift($rule);
                $this->applyRule($field, $value, $ruleName, $rule);
            }
        }
    }

    /**
     * Apply validation rule
     */
    private function applyRule(string $field, mixed $value, string $rule, array $params = []): void
    {
        switch ($rule) {
            case 'required':
                if (empty($value)) {
                    $this->addError($field, 'This field is required');
                }
                break;

            case 'email':
                if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->addError($field, 'Invalid email format');
                }
                break;

            case 'min':
                $min = $params[0] ?? 0;
                if (!empty($value) && strlen($value) < $min) {
                    $this->addError($field, "Must be at least {$min} characters");
                }
                break;

            case 'max':
                $max = $params[0] ?? 0;
                if (!empty($value) && strlen($value) > $max) {
                    $this->addError($field, "Must not exceed {$max} characters");
                }
                break;

            case 'numeric':
                if (!empty($value) && !is_numeric($value)) {
                    $this->addError($field, 'Must be a number');
                }
                break;

            case 'alpha':
                if (!empty($value) && !ctype_alpha($value)) {
                    $this->addError($field, 'Must contain only letters');
                }
                break;

            case 'alphanumeric':
                if (!empty($value) && !ctype_alnum($value)) {
                    $this->addError($field, 'Must contain only letters and numbers');
                }
                break;

            case 'phone':
                if (!empty($value) && !preg_match('/^0[1-9][0-9]{8}$/', $value)) {
                    $this->addError($field, 'Invalid phone number format');
                }
                break;

            case 'password':
                if (!empty($value)) {
                    if (strlen($value) < 8) {
                        $this->addError($field, 'Password must be at least 8 characters');
                    } elseif (!preg_match('/[A-Z]/', $value)) {
                        $this->addError($field, 'Password must contain at least one uppercase letter');
                    } elseif (!preg_match('/[a-z]/', $value)) {
                        $this->addError($field, 'Password must contain at least one lowercase letter');
                    } elseif (!preg_match('/[0-9]/', $value)) {
                        $this->addError($field, 'Password must contain at least one number');
                    }
                }
                break;

            case 'confirm':
                $confirmField = $params[0] ?? $field . '_confirm';
                $confirmValue = $_POST[$confirmField] ?? null;
                if ($value !== $confirmValue) {
                    $this->addError($field, 'Confirmation does not match');
                }
                break;

            case 'unique':
                $table = $params[0] ?? '';
                $column = $params[1] ?? $field;
                if (!empty($value) && $this->isValueExists($table, $column, $value)) {
                    $this->addError($field, 'This value already exists');
                }
                break;

            case 'in':
                $allowedValues = $params;
                if (!empty($value) && !in_array($value, $allowedValues)) {
                    $this->addError($field, 'Invalid value selected');
                }
                break;

            case 'nic':
                if (!empty($value) && !preg_match('/^[0-9]{9}[vVxX]$|^[0-9]{12}$/', $value)) {
                    $this->addError($field, 'Invalid NIC number format');
                }
                break;

            case 'price':
                if (!empty($value)) {
                    if (!is_numeric($value) || $value < 0) {
                        $this->addError($field, 'Price must be a positive number');
                    } elseif (round($value, 2) != $value) {
                        $this->addError($field, 'Price must have at most 2 decimal places');
                    }
                }
                break;
        }
    }

    /**
     * Add validation error
     */
    private function addError(string $field, string $message): void
    {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        $this->errors[$field][] = $message;
    }

    /**
     * Check if value exists in database
     */
    private function isValueExists(string $table, string $column, mixed $value): bool
    {
        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM {$table} WHERE {$column} = ?");
            $stmt->execute([$value]);
            return $stmt->fetchColumn() > 0;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Check if validation passed
     */
    public function passes(): bool
    {
        return empty($this->errors);
    }

    /**
     * Check if validation failed
     */
    public function fails(): bool
    {
        return !$this->passes();
    }

    /**
     * Get validation errors
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get first error for field
     */
    public function getFirstError(string $field): ?string
    {
        return $this->errors[$field][0] ?? null;
    }

    /**
     * Validate travel plan data
     */
    public function validateTravelPlan(array $data): array
    {
        $this->errors = [];

        // Required fields
        if (empty($data['destination'])) {
            $this->addError('destination', 'Destination is required');
        } elseif (strlen($data['destination']) > 255) {
            $this->addError('destination', 'Destination is too long (max 255 characters)');
        }

        if (empty($data['travel_date'])) {
            $this->addError('travel_date', 'Travel date is required');
        } elseif (!$this->isValidDate($data['travel_date'])) {
            $this->addError('travel_date', 'Invalid travel date format');
        } elseif (!$this->isFutureDate($data['travel_date'])) {
            $this->addError('travel_date', 'Travel date must be in the future');
        }

        if (empty($data['companions_needed'])) {
            $this->addError('companions_needed', 'Number of companions needed is required');
        } elseif (!is_numeric($data['companions_needed']) || (int)$data['companions_needed'] < 1) {
            $this->addError('companions_needed', 'At least 1 companion is required');
        } elseif ((int)$data['companions_needed'] > 10) {
            $this->addError('companions_needed', 'Maximum 10 companions allowed');
        }

        // Optional fields
        if (!empty($data['notes']) && strlen($data['notes']) > 1000) {
            $this->addError('notes', 'Notes are too long (max 1000 characters)');
        }

        return $this->errors;
    }

    /**
     * Validate travel request data
     */
    public function validateTravelRequest(array $data): array
    {
        $this->errors = [];

        if (empty($data['plan_id'])) {
            $this->addError('plan_id', 'Plan ID is required');
        } elseif (!is_numeric($data['plan_id'])) {
            $this->addError('plan_id', 'Invalid plan ID');
        }

        return $this->errors;
    }

    /**
     * Validate travel message data
     */
    public function validateTravelMessage(array $data): array
    {
        $this->errors = [];

        if (empty($data['message'])) {
            $this->addError('message', 'Message is required');
        } elseif (strlen($data['message']) > 1000) {
            $this->addError('message', 'Message is too long (max 1000 characters)');
        }

        if (empty($data['plan_id'])) {
            $this->addError('plan_id', 'Plan ID is required');
        } elseif (!is_numeric($data['plan_id'])) {
            $this->addError('plan_id', 'Invalid plan ID');
        }

        return $this->errors;
    }

    /**
     * Check if date is valid format
     */
    private function isValidDate(string $date): bool
    {
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }

    /**
     * Check if date is in the future
     */
    private function isFutureDate(string $date): bool
    {
        $travelDate = new DateTime($date);
        $today = new DateTime();
        return $travelDate > $today;
    }
}
