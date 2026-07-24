<?php

namespace App\Core;

/**
 * Small rule-based input validator. Usage:
 *   $v = new Validator($data);
 *   $v->required('email')->email('email')->required('password')->minLength('password', 8);
 *   if ($v->fails()) { ... $v->errors() ... }
 */
class Validator
{
    private array $data;
    private array $errors = [];

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    private function value(string $field): mixed
    {
        return $this->data[$field] ?? null;
    }

    public function required(string $field, string $label = null): static
    {
        $value = $this->value($field);
        if ($value === null || (is_string($value) && trim($value) === '')) {
            $this->addError($field, ($label ?? ucfirst($field)) . ' is required.');
        }
        return $this;
    }

    public function email(string $field): static
    {
        $value = $this->value($field);
        if ($value !== null && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->addError($field, 'Please enter a valid email address.');
        }
        return $this;
    }

    public function minLength(string $field, int $length): static
    {
        $value = $this->value($field);
        if ($value !== null && strlen((string) $value) < $length) {
            $this->addError($field, ucfirst($field) . " must be at least {$length} characters.");
        }
        return $this;
    }

    public function maxLength(string $field, int $length): static
    {
        $value = $this->value($field);
        if ($value !== null && strlen((string) $value) > $length) {
            $this->addError($field, ucfirst($field) . " must not exceed {$length} characters.");
        }
        return $this;
    }

    public function matches(string $field, string $otherField): static
    {
        if ($this->value($field) !== $this->value($otherField)) {
            $this->addError($field, ucfirst($field) . ' does not match.');
        }
        return $this;
    }

    public function in(string $field, array $allowed): static
    {
        $value = $this->value($field);
        if ($value !== null && !in_array($value, $allowed, true)) {
            $this->addError($field, 'Invalid value for ' . $field . '.');
        }
        return $this;
    }

    private function addError(string $field, string $message): void
    {
        $this->errors[$field][] = $message;
    }

    public function fails(): bool
    {
        return !empty($this->errors);
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function firstError(): ?string
    {
        foreach ($this->errors as $fieldErrors) {
            return $fieldErrors[0];
        }
        return null;
    }
}
