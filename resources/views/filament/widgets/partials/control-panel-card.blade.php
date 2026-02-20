@php
    $iconBg = match($item['color']) {
        'danger' => 'bg-danger-50 dark:bg-danger-500/10',
        'warning' => 'bg-warning-50 dark:bg-warning-500/10',
        'info' => 'bg-info-50 dark:bg-info-500/10',
        default => 'bg-gray-100 dark:bg-gray-500/10',
    };
    $iconColor = match($item['color']) {
        'danger' => 'text-danger-600 dark:text-danger-400',
        'warning' => 'text-warning-600 dark:text-warning-400',
        'info' => 'text-info-600 dark:text-info-400',
        default => 'text-gray-500 dark:text-gray-400',
    };
    $countColor = match($item['color']) {
        'danger' => 'text-danger-600 dark:text-danger-400',
        'warning' => 'text-warning-600 dark:text-warning-400',
        'info' => 'text-info-600 dark:text-info-400',
        default => 'text-gray-600 dark:text-gray-400',
    };
@endphp

@if($item['url'])
    <a href="{{ $item['url'] }}"
       class="group flex items-center gap-3 p-3 rounded-xl bg-white dark:bg-white/5 ring-1 ring-gray-950/5 dark:ring-white/10 hover:ring-gray-950/10 dark:hover:ring-white/20 hover:shadow-sm transition-all">
@else
    <div class="flex items-center gap-3 p-3 rounded-xl bg-white dark:bg-white/5 ring-1 ring-gray-950/5 dark:ring-white/10">
@endif
        <div class="flex-shrink-0 w-10 h-10 rounded-lg flex items-center justify-center {{ $iconBg }}">
            <x-dynamic-component :component="$item['icon']" class="w-5 h-5 {{ $iconColor }}" />
        </div>
        <div class="min-w-0 flex-1">
            <div class="text-2xl font-extrabold {{ $countColor }}">{{ $item['count'] }}</div>
            <div class="text-xs font-semibold text-gray-500 dark:text-gray-400 leading-tight">{{ $item['label'] }}</div>
        </div>
        @if($item['url'])
            <x-heroicon-m-chevron-left class="w-4 h-4 text-gray-300 dark:text-gray-600 group-hover:text-gray-500 dark:group-hover:text-gray-400 transition-colors flex-shrink-0" />
        @endif
@if($item['url'])
    </a>
@else
    </div>
@endif
