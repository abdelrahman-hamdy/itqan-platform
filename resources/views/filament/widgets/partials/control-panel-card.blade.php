@php
    $bgClasses = match($item['color']) {
        'danger' => 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800 hover:border-red-400 dark:hover:border-red-600',
        'warning' => 'bg-amber-50 dark:bg-amber-900/20 border-amber-200 dark:border-amber-800 hover:border-amber-400 dark:hover:border-amber-600',
        'info' => 'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800 hover:border-blue-400 dark:hover:border-blue-600',
        default => 'bg-gray-50 dark:bg-gray-900/20 border-gray-200 dark:border-gray-700 hover:border-gray-400 dark:hover:border-gray-500',
    };
    $countClasses = match($item['color']) {
        'danger' => 'text-red-700 dark:text-red-300',
        'warning' => 'text-amber-700 dark:text-amber-300',
        'info' => 'text-blue-700 dark:text-blue-300',
        default => 'text-gray-700 dark:text-gray-300',
    };
    $iconBg = match($item['color']) {
        'danger' => 'bg-red-500',
        'warning' => 'bg-amber-500',
        'info' => 'bg-blue-500',
        default => 'bg-gray-500',
    };
@endphp

@if($item['url'])
    <a href="{{ $item['url'] }}"
       class="group flex items-center gap-3 p-3 rounded-xl border-2 transition-all duration-200 hover:shadow-md {{ $bgClasses }}">
@else
    <div class="flex items-center gap-3 p-3 rounded-xl border-2 {{ $bgClasses }}">
@endif
        <div class="flex-shrink-0 w-10 h-10 rounded-lg flex items-center justify-center {{ $iconBg }}">
            <x-dynamic-component :component="$item['icon']" class="w-5 h-5 text-white" />
        </div>
        <div class="min-w-0 flex-1">
            <div class="text-2xl font-bold {{ $countClasses }}">{{ $item['count'] }}</div>
            <div class="text-xs text-gray-600 dark:text-gray-400 leading-tight">{{ $item['label'] }}</div>
        </div>
        @if($item['url'])
            <x-heroicon-o-chevron-left class="w-4 h-4 text-gray-400 opacity-50 group-hover:opacity-100 transition-opacity flex-shrink-0" />
        @endif
@if($item['url'])
    </a>
@else
    </div>
@endif
