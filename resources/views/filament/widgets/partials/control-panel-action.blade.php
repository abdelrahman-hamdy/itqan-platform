@php
    $bgColor = match($action['color']) {
        'success' => 'bg-emerald-500 hover:bg-emerald-600 dark:bg-emerald-600 dark:hover:bg-emerald-500',
        'info' => 'bg-blue-500 hover:bg-blue-600 dark:bg-blue-600 dark:hover:bg-blue-500',
        'primary' => 'bg-indigo-500 hover:bg-indigo-600 dark:bg-indigo-600 dark:hover:bg-indigo-500',
        'warning' => 'bg-amber-500 hover:bg-amber-600 dark:bg-amber-600 dark:hover:bg-amber-500',
        default => 'bg-gray-500 hover:bg-gray-600 dark:bg-gray-600 dark:hover:bg-gray-500',
    };
@endphp

<a href="{{ $action['url'] }}"
   class="group flex items-center gap-2 p-2.5 rounded-lg {{ $bgColor }} shadow-sm hover:shadow-md transition-all">
    <div class="flex-shrink-0 w-8 h-8 rounded-lg flex items-center justify-center bg-white/20">
        <x-dynamic-component :component="$action['icon']" class="w-4 h-4 text-white" />
    </div>
    <span class="text-xs font-bold text-white leading-tight flex-1 min-w-0">
        {{ $action['label'] }}
    </span>
    <x-heroicon-m-chevron-left class="w-3 h-3 text-white/60 group-hover:text-white transition-colors flex-shrink-0" />
</a>
