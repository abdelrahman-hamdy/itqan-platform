@php
    $iconBg = match($item['color']) {
        'danger' => 'bg-danger-50 dark:bg-danger-500/10',
        'warning' => 'bg-warning-50 dark:bg-warning-500/10',
        'info' => 'bg-info-50 dark:bg-info-500/10',
        'success' => 'bg-success-50 dark:bg-success-500/10',
        'primary' => 'bg-primary-50 dark:bg-primary-500/10',
        default => 'bg-gray-100 dark:bg-gray-500/10',
    };
    $iconColor = match($item['color']) {
        'danger' => 'text-danger-600 dark:text-danger-400',
        'warning' => 'text-warning-600 dark:text-warning-400',
        'info' => 'text-info-600 dark:text-info-400',
        'success' => 'text-success-600 dark:text-success-400',
        'primary' => 'text-primary-600 dark:text-primary-400',
        default => 'text-gray-500 dark:text-gray-400',
    };
    $countColor = match($item['color']) {
        'danger' => 'text-danger-600 dark:text-danger-400',
        'warning' => 'text-warning-600 dark:text-warning-400',
        'info' => 'text-info-600 dark:text-info-400',
        'success' => 'text-success-600 dark:text-success-400',
        'primary' => 'text-primary-600 dark:text-primary-400',
        default => 'text-gray-600 dark:text-gray-400',
    };
    $borderColor = match($item['color']) {
        'danger' => 'border-s-danger-400',
        'warning' => 'border-s-warning-400',
        'info' => 'border-s-info-400',
        'success' => 'border-s-success-400',
        'primary' => 'border-s-primary-400',
        default => 'border-s-gray-300 dark:border-s-gray-600',
    };
@endphp

@if($item['url'])
    <a href="{{ $item['url'] }}"
       class="group flex items-center gap-2.5 p-2.5 rounded-lg bg-white dark:bg-white/5 ring-1 ring-gray-950/5 dark:ring-white/10 border-s-[3px] {{ $borderColor }} hover:shadow-sm hover:ring-gray-950/10 dark:hover:ring-white/20 transition-all">
@else
    <div class="flex items-center gap-2.5 p-2.5 rounded-lg bg-white dark:bg-white/5 ring-1 ring-gray-950/5 dark:ring-white/10 border-s-[3px] {{ $borderColor }}">
@endif
        <div class="flex-shrink-0 w-9 h-9 rounded-lg flex items-center justify-center {{ $iconBg }}">
            <x-dynamic-component :component="$item['icon']" class="w-4 h-4 {{ $iconColor }}" />
        </div>
        <div class="min-w-0 flex-1">
            <div class="text-xl font-extrabold leading-none {{ $countColor }}">{{ $item['count'] }}</div>
            <div class="text-[11px] font-semibold text-gray-500 dark:text-gray-400 leading-tight mt-0.5">{{ $item['label'] }}</div>
        </div>
        @if($item['url'])
            <x-heroicon-m-chevron-left class="w-3.5 h-3.5 text-gray-300 dark:text-gray-600 group-hover:text-gray-500 dark:group-hover:text-gray-400 transition-colors flex-shrink-0" />
        @endif
@if($item['url'])
    </a>
@else
    </div>
@endif
