@php
    $textColor = match($session['color']) {
        'success' => 'text-emerald-600 dark:text-emerald-400',
        'info' => 'text-blue-600 dark:text-blue-400',
        'primary' => 'text-indigo-600 dark:text-indigo-400',
        default => 'text-gray-600 dark:text-gray-400',
    };
    $iconColor = match($session['color']) {
        'success' => 'text-emerald-500',
        'info' => 'text-blue-500',
        'primary' => 'text-indigo-500',
        default => 'text-gray-500',
    };
    $borderColor = match($session['color']) {
        'success' => 'border-s-emerald-400',
        'info' => 'border-s-blue-400',
        'primary' => 'border-s-indigo-400',
        default => 'border-s-gray-300',
    };
@endphp

<div class="flex items-center justify-between p-3 rounded-lg bg-white dark:bg-white/5 ring-1 ring-gray-950/5 dark:ring-white/10 border-s-[3px] {{ $borderColor }}">
    <div class="min-w-0 flex-1">
        <div class="flex items-center gap-2 mb-1.5">
            <x-dynamic-component :component="$session['icon']" class="w-4 h-4 {{ $iconColor }} flex-shrink-0" />
            <span class="text-sm font-bold text-gray-700 dark:text-gray-300">{{ $session['label'] }}</span>
            <span class="text-lg font-extrabold {{ $textColor }}">{{ $session['total'] }}</span>
        </div>
        <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
            @if($session['scheduled'] > 0)
                <span class="flex items-center gap-1 text-xs text-gray-500 dark:text-gray-400">
                    <span class="w-1.5 h-1.5 rounded-full bg-blue-500 flex-shrink-0"></span>
                    {{ $session['scheduled'] }} مجدولة
                </span>
            @endif
            @if($session['ongoing'] > 0)
                <span class="flex items-center gap-1 text-xs font-bold text-amber-600 dark:text-amber-400">
                    <span class="relative flex h-1.5 w-1.5 flex-shrink-0">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-amber-500"></span>
                    </span>
                    {{ $session['ongoing'] }} جارية
                </span>
            @endif
            @if($session['completed'] > 0)
                <span class="flex items-center gap-1 text-xs text-gray-500 dark:text-gray-400">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 flex-shrink-0"></span>
                    {{ $session['completed'] }} مكتملة
                </span>
            @endif
            @if($session['cancelled'] > 0)
                <span class="flex items-center gap-1 text-xs text-gray-500 dark:text-gray-400">
                    <span class="w-1.5 h-1.5 rounded-full bg-red-500 flex-shrink-0"></span>
                    {{ $session['cancelled'] }} ملغاة
                </span>
            @endif
        </div>
    </div>
    <a href="{{ $session['url'] }}" class="text-xs font-semibold {{ $textColor }} hover:underline flex-shrink-0 ms-3">
        عرض
    </a>
</div>
