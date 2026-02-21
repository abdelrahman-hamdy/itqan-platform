@php
    $iconBg = match($action['color']) {
        'success' => 'bg-success-50 dark:bg-success-500/10',
        'info' => 'bg-info-50 dark:bg-info-500/10',
        'primary' => 'bg-primary-50 dark:bg-primary-500/10',
        'warning' => 'bg-warning-50 dark:bg-warning-500/10',
        default => 'bg-gray-100 dark:bg-gray-500/10',
    };
    $iconColor = match($action['color']) {
        'success' => 'text-success-600 dark:text-success-400',
        'info' => 'text-info-600 dark:text-info-400',
        'primary' => 'text-primary-600 dark:text-primary-400',
        'warning' => 'text-warning-600 dark:text-warning-400',
        default => 'text-gray-500 dark:text-gray-400',
    };
@endphp

<a href="{{ $action['url'] }}"
   class="group flex items-center gap-2 p-2.5 rounded-lg bg-white dark:bg-white/5 ring-1 ring-gray-950/5 dark:ring-white/10 hover:shadow-sm hover:ring-gray-950/10 dark:hover:ring-white/20 transition-all">
    <div class="flex-shrink-0 w-8 h-8 rounded-lg flex items-center justify-center {{ $iconBg }}">
        <x-dynamic-component :component="$action['icon']" class="w-4 h-4 {{ $iconColor }}" />
    </div>
    <span class="text-xs font-semibold text-gray-600 dark:text-gray-400 group-hover:text-gray-800 dark:group-hover:text-gray-200 leading-tight flex-1 min-w-0">
        {{ $action['label'] }}
    </span>
    <x-heroicon-m-chevron-left class="w-3 h-3 text-gray-300 dark:text-gray-600 group-hover:text-gray-400 transition-colors flex-shrink-0" />
</a>
