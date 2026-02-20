@php
    $bgClasses = match($item['color']) {
        'danger' => 'bg-danger-50 dark:bg-danger-400/10 ring-danger-200 dark:ring-danger-400/20 hover:ring-danger-400 dark:hover:ring-danger-400/40',
        'warning' => 'bg-warning-50 dark:bg-warning-400/10 ring-warning-200 dark:ring-warning-400/20 hover:ring-warning-400 dark:hover:ring-warning-400/40',
        'info' => 'bg-info-50 dark:bg-info-400/10 ring-info-200 dark:ring-info-400/20 hover:ring-info-400 dark:hover:ring-info-400/40',
        default => 'bg-gray-50 dark:bg-white/5 ring-gray-200 dark:ring-white/10 hover:ring-gray-400 dark:hover:ring-white/20',
    };
    $countClasses = match($item['color']) {
        'danger' => 'text-danger-600 dark:text-danger-400',
        'warning' => 'text-warning-600 dark:text-warning-400',
        'info' => 'text-info-600 dark:text-info-400',
        default => 'text-gray-600 dark:text-gray-400',
    };
    $iconBg = match($item['color']) {
        'danger' => 'bg-danger-500',
        'warning' => 'bg-warning-500',
        'info' => 'bg-info-500',
        default => 'bg-gray-500',
    };
@endphp

@if($item['url'])
    <a href="{{ $item['url'] }}"
       class="group flex items-center gap-3 p-3 rounded-xl ring-1 transition-all duration-200 hover:shadow-md {{ $bgClasses }}">
@else
    <div class="flex items-center gap-3 p-3 rounded-xl ring-1 {{ $bgClasses }}">
@endif
        <div class="flex-shrink-0 w-10 h-10 rounded-lg flex items-center justify-center transition-transform group-hover:scale-110 {{ $iconBg }}">
            <x-dynamic-component :component="$item['icon']" class="w-5 h-5 text-white" />
        </div>
        <div class="min-w-0 flex-1">
            <div class="text-2xl font-extrabold {{ $countClasses }}">{{ $item['count'] }}</div>
            <div class="text-xs font-semibold text-gray-600 dark:text-gray-400 leading-tight">{{ $item['label'] }}</div>
        </div>
        @if($item['url'])
            <x-heroicon-m-chevron-left class="w-5 h-5 text-gray-400 opacity-50 group-hover:opacity-100 transition-opacity flex-shrink-0" />
        @endif
@if($item['url'])
    </a>
@else
    </div>
@endif
