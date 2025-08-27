<?php

namespace App\Forms\Components;

use Filament\Forms\Components\TextInput;

class CustomFileUploadField extends TextInput
{
    protected string $view = 'forms.components.custom-file-upload-field';

    protected string $disk = 'public';

    protected string $directory = '';

    protected array $acceptedFileTypes = [];

    protected int $maxSize = 10240; // 10MB default

    protected bool $multiple = false;

    public function disk(string $disk): static
    {
        $this->disk = $disk;

        return $this;
    }

    public function directory(string $directory): static
    {
        $this->directory = $directory;

        return $this;
    }

    public function acceptedFileTypes(array $types): static
    {
        $this->acceptedFileTypes = $types;

        return $this;
    }

    public function maxSize(int $size): static
    {
        $this->maxSize = $size;

        return $this;
    }

    public function multiple(bool $multiple = true): static
    {
        $this->multiple = $multiple;

        return $this;
    }

    public function getDisk(): string
    {
        return $this->disk;
    }

    public function getDirectory(): string
    {
        return $this->directory;
    }

    public function getAcceptedFileTypes(): array
    {
        return $this->acceptedFileTypes;
    }

    public function getMaxSize(): int
    {
        return $this->maxSize;
    }

    public function isMultiple(): bool
    {
        return $this->multiple;
    }
}
