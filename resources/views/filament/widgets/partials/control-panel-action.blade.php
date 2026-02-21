@php
    $iconBg = match($action['color']) {
        'success' => 'bg-emerald-50 dark:bg-emerald-500/10',
        'info' => 'bg-blue-50 dark:bg-blue-500/10',
        'primary' => 'bg-indigo-50 dark:bg-indigo-500/10',
        'warning' => 'bg-amber-50 dark:bg-amber-500/10',
        default => 'bg-gray-100 dark:bg-gray-500/10',
    };
    $iconColor = match($action['color']) {
        'success' => 'text-emerald-600 dark:text-emerald-400',
        'info' => 'text-blue-600 dark:text-blue-400',
        'primary' => 'text-indigo-600 dark:text-indigo-400',
        'warning' => 'text-amber-600 dark:text-amber-400',
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
