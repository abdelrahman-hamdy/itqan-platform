<?php

namespace App\Services\Scheduling;

use App\Enums\SessionStatus;

/**
 * Value object representing a validation result
 */
class ValidationResult
{
    private function __construct(
        private bool $valid,
        private string $message = '',
        private string $level = 'error', // 'error', 'warning', 'info'
        private array $data = []
    ) {}

    public static function success(string $message = '', array $data = []): self
    {
        return new self(true, $message, 'info', $data);
    }

    public static function error(string $message, array $data = []): self
    {
        return new self(false, $message, 'error', $data);
    }

    public static function warning(string $message, array $data = []): self
    {
        return new self(true, $message, 'warning', $data);
    }

    public function isValid(): bool
    {
        return $this->valid;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getLevel(): string
    {
        return $this->level;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function isError(): bool
    {
        return $this->level === 'error';
    }

    public function isWarning(): bool
    {
        return $this->level === 'warning';
    }

    public function toArray(): array
    {
        return [
            'valid' => $this->valid,
            'message' => $this->message,
            'level' => $this->level,
            'data' => $this->data,
        ];
    }
}
