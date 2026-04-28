<?php

namespace yurni\Helpers;

/**
 * كلاس التحقق من صحة المدخلات (Validator)
 */
class Validator {

    protected array $data;
    protected array $rules;
    protected array $errors = [];

    protected function __construct(array $data, array $rules) {
        $this->data = $data;
        $this->rules = $rules;
    }

    /**
     * إنشاء كائن التحقق
     */
    public static function make(array $data, array $rules): self {
        $validator = new self($data, $rules);
        $validator->validate();
        return $validator;
    }

    /**
     * تنفيذ عمليات التحقق
     */
    protected function validate(): void {
        foreach ($this->rules as $field => $fieldRules) {
            $rulesArray = explode('|', $fieldRules);
            $value = $this->data[$field] ?? null;

            foreach ($rulesArray as $rule) {
                if (strpos($rule, ':') !== false) {
                    list($ruleName, $ruleParam) = explode(':', $rule, 2);
                    $this->applyRule($field, $value, $ruleName, $ruleParam);
                } else {
                    $this->applyRule($field, $value, $rule);
                }
            }
        }
    }

    /**
     * تطبيق قاعدة معينة
     */
    protected function applyRule(string $field, $value, string $rule, string $param = null): void {
        switch ($rule) {
            case 'required':
                if (is_null($value) || trim((string)$value) === '') {
                    $this->addError($field, "The {$field} field is required.");
                }
                break;
            case 'email':
                if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->addError($field, "The {$field} must be a valid email address.");
                }
                break;
            case 'min':
                if (!empty($value) && mb_strlen((string)$value, 'UTF-8') < (int)$param) {
                    $this->addError($field, "The {$field} must be at least {$param} characters.");
                }
                break;
            case 'max':
                if (!empty($value) && mb_strlen((string)$value, 'UTF-8') > (int)$param) {
                    $this->addError($field, "The {$field} may not be greater than {$param} characters.");
                }
                break;
            case 'numeric':
                if (!empty($value) && !is_numeric($value)) {
                    $this->addError($field, "The {$field} must be a number.");
                }
                break;
        }
    }

    /**
     * إضافة خطأ
     */
    protected function addError(string $field, string $message): void {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        $this->errors[$field][] = $message;
    }

    /**
     * هل فشل التحقق؟
     */
    public function fails(): bool {
        return !empty($this->errors);
    }

    /**
     * هل نجح التحقق؟
     */
    public function passes(): bool {
        return empty($this->errors);
    }

    /**
     * جلب جميع الأخطاء
     */
    public function errors(): array {
        return $this->errors;
    }

    /**
     * جلب الخطأ الأول لحقل معين
     */
    public function first(string $field): ?string {
        return $this->errors[$field][0] ?? null;
    }
}
