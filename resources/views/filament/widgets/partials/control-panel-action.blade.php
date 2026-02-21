@php
    $colorMap = [
        'success' => ['bg' => 'bg-success-50 dark:bg-success-500/10', 'icon' => 'text-success-600 dark:text-success-400', 'hover' => 'hover:ring-success-500/30'],
        'info' => ['bg' => 'bg-info-50 dark:bg-info-500/10', 'icon' => 'text-info-600 dark:text-info-400', 'hover' => 'hover:ring-info-500/30'],
        'primary' => ['bg' => 'bg-primary-50 dark:bg-primary-500/10', 'icon' => 'text-primary-600 dark:text-primary-400', 'hover' => 'hover:ring-primary-500/30'],
        'warning' => ['bg' => 'bg-warning-50 dark:bg-warning-500/10', 'icon' => 'text-warning-600 dark:text-warning-400', 'hover' => 'hover:ring-warning-500/30'],
    ];
    $colors = $colorMap[$action['color']] ?? $colorMap['primary'];
@endphp

<a href="{{ $action['url'] }}"
   class="group flex items-center gap-3 p-3 rounded-xl bg-white dark:bg-white/5 ring-1 ring-gray-950/5 dark:ring-white/10 {{ $colors['hover'] }} hover:shadow-sm transition-all">
    <div class="flex-shrink-0 w-9 h-9 rounded-lg flex items-center justify-center {{ $colors['bg'] }}">
        <x-dynamic-component :component="$action['icon']" class="w-4.5 h-4.5 {{ $colors['icon'] }}" />
    </div>
    <span class="text-sm font-semibold text-gray-700 dark:text-gray-300 group-hover:text-gray-900 dark:group-hover:text-gray-100 leading-tight">
        {{ $action['label'] }}
    </span>
    <x-heroicon-m-chevron-left class="w-4 h-4 text-gray-300 dark:text-gray-600 group-hover:text-gray-500 dark:group-hover:text-gray-400 transition-colors flex-shrink-0 ms-auto" />
</a>
