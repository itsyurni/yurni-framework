<?php

namespace yurni\Http;

/**
 * Validator
 *
 * Validates an array of data against a set of rules.
 *
 * Supported rules:
 *   required, nullable, string, numeric, integer, boolean, email,
 *   min:N, max:N, min_length:N, max_length:N, between:N,M,
 *   in:a,b,c, not_in:a,b,c, regex:pattern,
 *   confirmed, same:field, different:field,
 *   url, ip, alpha, alpha_num, date, json
 */
class Validator
{

    private array $data;
    private array $rules;
    private array $messages;
    private array $errors = [];

    // -------------------------------------------------------------------------
    //  Factory
    // -------------------------------------------------------------------------

    private function __construct(array $data, array $rules, array $messages = [])
    {
        $this->data = $data;
        $this->rules = $rules;
        $this->messages = $messages;
    }

    /**
     * Create and run the validator.
     *
     * @param array $data     Input data (e.g. $request->inputs())
     * @param array $rules    Rules keyed by field name
     * @param array $messages Custom error messages (optional)
     */
    public static function make(array $data, array $rules, array $messages = []): self
    {
        $validator = new self($data, $rules, $messages);
        $validator->run();
        return $validator;
    }

    // -------------------------------------------------------------------------
    //  Status
    // -------------------------------------------------------------------------

    public function fails(): bool
    {
        return !empty($this->errors);
    }
    public function passes(): bool
    {
        return empty($this->errors);
    }

    /** All errors keyed by field name. */
    public function errors(): array
    {
        return $this->errors;
    }

    /** First error message for a field. */
    public function first(string $field): ?string
    {
        return $this->errors[$field][0] ?? null;
    }

    /** Flat list of all error messages. */
    public function all(): array
    {
        $list = [];
        foreach ($this->errors as $messages) {
            foreach ($messages as $msg) {
                $list[] = $msg;
            }
        }
        return $list;
    }

    // -------------------------------------------------------------------------
    //  Core validation loop
    // -------------------------------------------------------------------------

    private function run(): void
    {
        foreach ($this->rules as $field => $ruleString) {
            $rules = array_map('trim', explode('|', $ruleString));
            $value = $this->data[$field] ?? null;

            $nullable = in_array('nullable', $rules, true);
            $isPresent = array_key_exists($field, $this->data);

            // Skip all rules (except required) when value is empty and nullable
            $isEmpty = $value === null || $value === '';
            if ($isEmpty && $nullable && !in_array('required', $rules, true)) {
                continue;
            }

            foreach ($rules as $rule) {
                if ($rule === 'nullable') {
                    continue;
                }

                [$ruleName, $param] = str_contains($rule, ':')
                    ? explode(':', $rule, 2)
                    : [$rule, null];

                $this->applyRule($field, $value, $ruleName, $param, $isPresent);
            }
        }
    }

    // -------------------------------------------------------------------------
    //  Rule dispatch
    // -------------------------------------------------------------------------

    private function applyRule(string $field, mixed $value, string $rule, ?string $param, bool $isPresent): void
    {
        $ok = match ($rule) {
            // Presence
            'required' => $isPresent && $value !== null && $value !== '',
            'present' => $isPresent,

            // Type checks
            'string' => is_string($value),
            'numeric' => is_numeric($value),
            'integer' => filter_var($value, FILTER_VALIDATE_INT) !== false,
            'boolean' => in_array($value, [true, false, 1, 0, '1', '0', 'true', 'false'], true),

            // Format
            'email' => filter_var($value, FILTER_VALIDATE_EMAIL) !== false,
            'url' => filter_var($value, FILTER_VALIDATE_URL) !== false,
            'ip' => filter_var($value, FILTER_VALIDATE_IP) !== false,
            'alpha' => is_string($value) && ctype_alpha($value),
            'alpha_num' => is_string($value) && ctype_alnum($value),
            'date' => $this->isValidDate($value),
            // json_validate() متاحة فقط في PHP >= 8.3.
            // للتوافق مع PHP 8.0+ نستخدم json_decode + json_last_error.
            'json' => is_string($value) && json_decode($value) !== null && json_last_error() === JSON_ERROR_NONE,

            // Size / length
            'min' => $this->checkMin($value, (float) $param),
            'max' => $this->checkMax($value, (float) $param),
            'min_length' => is_string($value) && mb_strlen($value) >= (int) $param,
            'max_length' => is_string($value) && mb_strlen($value) <= (int) $param,
            'between' => $this->checkBetween($value, $param),
            'size' => $this->checkSize($value, (float) $param),

            // List
            'in' => in_array((string) $value, array_map('trim', explode(',', $param ?? '')), true),
            'not_in' => !in_array((string) $value, array_map('trim', explode(',', $param ?? '')), true),

            // Comparison
            'same' => isset($this->data[$param]) && $value === $this->data[$param],
            'different' => isset($this->data[$param]) && $value !== $this->data[$param],
            'confirmed' => isset($this->data[$field . '_confirmation']) && $value === $this->data[$field . '_confirmation'],

            // Regex
            'regex' => is_string($value) && preg_match($param, $value) === 1,
            'not_regex' => is_string($value) && preg_match($param, $value) === 0,

            default => true, // unknown rules are silently skipped
        };

        if (!$ok) {
            $this->addError($field, $rule, $param);
        }
    }

    // -------------------------------------------------------------------------
    //  Helpers
    // -------------------------------------------------------------------------

    private function checkMin(mixed $value, float $min): bool
    {
        return is_numeric($value) ? (float) $value >= $min : mb_strlen((string) $value) >= $min;
    }

    private function checkMax(mixed $value, float $max): bool
    {
        return is_numeric($value) ? (float) $value <= $max : mb_strlen((string) $value) <= $max;
    }

    private function checkSize(mixed $value, float $size): bool
    {
        return is_numeric($value) ? (float) $value === $size : mb_strlen((string) $value) === (int) $size;
    }

    private function checkBetween(mixed $value, ?string $param): bool
    {
        if ($param === null)
            return false;
        [$min, $max] = array_map('trim', explode(',', $param, 2));
        return $this->checkMin($value, (float) $min) && $this->checkMax($value, (float) $max);
    }

    private function isValidDate(mixed $value): bool
    {
        if (!is_string($value))
            return false;
        $d = \DateTime::createFromFormat('Y-m-d', $value);
        return $d && $d->format('Y-m-d') === $value;
    }

    private function addError(string $field, string $rule, ?string $param): void
    {
        $key = "{$field}.{$rule}";

        // Check for a custom message: field.rule or just field
        $message = $this->messages[$key]
            ?? $this->messages[$field]
            ?? $this->defaultMessage($field, $rule, $param);

        $this->errors[$field][] = $message;
    }

    private function defaultMessage(string $field, string $rule, ?string $param): string
    {
        $label = str_replace('_', ' ', $field);

        return match ($rule) {
            'required' => "The {$label} field is required.",
            'present' => "The {$label} field must be present.",
            'string' => "The {$label} must be a string.",
            'numeric' => "The {$label} must be a number.",
            'integer' => "The {$label} must be an integer.",
            'boolean' => "The {$label} must be true or false.",
            'email' => "The {$label} must be a valid email address.",
            'url' => "The {$label} must be a valid URL.",
            'ip' => "The {$label} must be a valid IP address.",
            'alpha' => "The {$label} may only contain letters.",
            'alpha_num' => "The {$label} may only contain letters and numbers.",
            'date' => "The {$label} must be a valid date (YYYY-MM-DD).",
            'json' => "The {$label} must be a valid JSON string.",
            'min' => "The {$label} must be at least {$param}.",
            'max' => "The {$label} must not exceed {$param}.",
            'min_length' => "The {$label} must be at least {$param} characters.",
            'max_length' => "The {$label} must not exceed {$param} characters.",
            'between' => "The {$label} must be between {$param}.",
            'size' => "The {$label} must be exactly {$param}.",
            'in' => "The selected {$label} is invalid.",
            'not_in' => "The selected {$label} is invalid.",
            'same' => "The {$label} must match the {$param} field.",
            'different' => "The {$label} must be different from the {$param} field.",
            'confirmed' => "The {$label} confirmation does not match.",
            'regex' => "The {$label} format is invalid.",
            'not_regex' => "The {$label} format is invalid.",
            default => "The {$label} is invalid.",
        };
    }
}
