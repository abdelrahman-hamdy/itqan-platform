@php
    $colorMap = [
        'success' => ['bg' => 'bg-success-50 dark:bg-success-500/10', 'text' => 'text-success-600 dark:text-success-400', 'icon' => 'text-success-500', 'bar' => 'bg-success-500'],
        'info' => ['bg' => 'bg-info-50 dark:bg-info-500/10', 'text' => 'text-info-600 dark:text-info-400', 'icon' => 'text-info-500', 'bar' => 'bg-info-500'],
        'primary' => ['bg' => 'bg-primary-50 dark:bg-primary-500/10', 'text' => 'text-primary-600 dark:text-primary-400', 'icon' => 'text-primary-500', 'bar' => 'bg-primary-500'],
        'warning' => ['bg' => 'bg-warning-50 dark:bg-warning-500/10', 'text' => 'text-warning-600 dark:text-warning-400', 'icon' => 'text-warning-500', 'bar' => 'bg-warning-500'],
    ];
    $colors = $colorMap[$session['color']] ?? $colorMap['info'];
    $progress = $session['total'] > 0 ? round(($session['completed'] / $session['total']) * 100) : 0;
@endphp

<div class="rounded-xl bg-white dark:bg-white/5 ring-1 ring-gray-950/5 dark:ring-white/10 p-4">
    {{-- Header --}}
    <div class="flex items-center justify-between mb-3">
        <div class="flex items-center gap-2">
            <div class="w-8 h-8 rounded-lg flex items-center justify-center {{ $colors['bg'] }}">
                <x-dynamic-component :component="$session['icon']" class="w-4 h-4 {{ $colors['icon'] }}" />
            </div>
            <div>
                <h4 class="text-sm font-bold text-gray-700 dark:text-gray-300">{{ $session['label'] }}</h4>
                <span class="text-xs text-gray-500 dark:text-gray-400">{{ $session['total'] }} جلسة</span>
            </div>
        </div>
        <a href="{{ $session['url'] }}" class="text-xs font-semibold {{ $colors['text'] }} hover:underline">
            عرض الكل
        </a>
    </div>

    {{-- Status Breakdown --}}
    <div class="grid grid-cols-2 gap-2 mb-3">
        @if($session['scheduled'] > 0)
            <div class="flex items-center gap-1.5 text-xs">
                <span class="w-2 h-2 rounded-full bg-info-500 flex-shrink-0"></span>
                <span class="text-gray-600 dark:text-gray-400">مجدولة:</span>
                <span class="font-bold text-gray-700 dark:text-gray-300">{{ $session['scheduled'] }}</span>
            </div>
        @endif
        @if($session['ongoing'] > 0)
            <div class="flex items-center gap-1.5 text-xs">
                <span class="relative flex h-2 w-2 flex-shrink-0">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-primary-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-primary-500"></span>
                </span>
                <span class="text-gray-600 dark:text-gray-400">جارية:</span>
                <span class="font-bold text-primary-600 dark:text-primary-400">{{ $session['ongoing'] }}</span>
            </div>
        @endif
        @if($session['completed'] > 0)
            <div class="flex items-center gap-1.5 text-xs">
                <span class="w-2 h-2 rounded-full bg-success-500 flex-shrink-0"></span>
                <span class="text-gray-600 dark:text-gray-400">مكتملة:</span>
                <span class="font-bold text-success-600 dark:text-success-400">{{ $session['completed'] }}</span>
            </div>
        @endif
        @if($session['cancelled'] > 0)
            <div class="flex items-center gap-1.5 text-xs">
                <span class="w-2 h-2 rounded-full bg-danger-500 flex-shrink-0"></span>
                <span class="text-gray-600 dark:text-gray-400">ملغاة:</span>
                <span class="font-bold text-danger-600 dark:text-danger-400">{{ $session['cancelled'] }}</span>
            </div>
        @endif
    </div>

    {{-- Progress Bar --}}
    @if($session['total'] > 0)
        <div class="w-full bg-gray-100 dark:bg-gray-700 rounded-full h-1.5">
            <div class="{{ $colors['bar'] }} h-1.5 rounded-full transition-all duration-500" style="width: {{ $progress }}%"></div>
        </div>
        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1 text-left" dir="ltr">{{ $progress }}%</div>
    @endif
</div>
