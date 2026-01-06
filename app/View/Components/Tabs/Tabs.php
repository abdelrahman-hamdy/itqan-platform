<?php

namespace App\View\Components\Tabs;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\View\Component;

class Tabs extends Component
{
    public string $id;

    public ?string $defaultTab;

    public string $variant;

    public string $size;

    public string $color;

    public bool $fullWidth;

    public bool $sticky;

    public string $stickyOffset;

    public bool $persistState;

    public bool $urlSync;

    public bool $lazy;

    public bool $animated;

    public array $colorClasses;

    public string $sizeClasses;

    public function __construct(
        ?string $id = null,
        ?string $defaultTab = null,
        string $variant = 'default',
        string $size = 'md',
        string $color = 'primary',
        bool $fullWidth = false,
        bool $sticky = false,
        string $stickyOffset = '0',
        bool $persistState = false,
        bool $urlSync = false,
        bool $lazy = false,
        bool $animated = true,
    ) {
        $this->id = $id ?? 'tabs-'.Str::random(8);
        $this->defaultTab = $defaultTab;
        $this->variant = $variant;
        $this->size = $size;
        $this->color = $color;
        $this->fullWidth = $fullWidth;
        $this->sticky = $sticky;
        $this->stickyOffset = $stickyOffset;
        $this->persistState = $persistState;
        $this->urlSync = $urlSync;
        $this->lazy = $lazy;
        $this->animated = $animated;

        // Compute color and size classes
        $this->colorClasses = $this->getColorClasses();
        $this->sizeClasses = $this->getSizeClasses();
    }

    protected function getColorClasses(): array
    {
        return [
            'primary' => [
                'active' => 'border-primary text-primary',
                'inactive' => 'text-gray-500 hover:text-gray-700 hover:border-gray-300',
            ],
            'green' => [
                'active' => 'border-green-600 text-green-600',
                'inactive' => 'text-gray-500 hover:text-gray-700 hover:border-gray-300',
            ],
            'blue' => [
                'active' => 'border-blue-600 text-blue-600',
                'inactive' => 'text-gray-500 hover:text-gray-700 hover:border-gray-300',
            ],
            'purple' => [
                'active' => 'border-purple-600 text-purple-600',
                'inactive' => 'text-gray-500 hover:text-gray-700 hover:border-gray-300',
            ],
        ][$this->color] ?? [
            'active' => 'border-primary text-primary',
            'inactive' => 'text-gray-500 hover:text-gray-700 hover:border-gray-300',
        ];
    }

    protected function getSizeClasses(): string
    {
        return [
            'sm' => 'text-sm py-2 px-1',
            'md' => 'text-sm py-4 px-1',
            'lg' => 'text-base py-5 px-2',
        ][$this->size] ?? 'text-sm py-4 px-1';
    }

    public function render(): View|Closure|string
    {
        return view('components.tabs.tabs');
    }
}
