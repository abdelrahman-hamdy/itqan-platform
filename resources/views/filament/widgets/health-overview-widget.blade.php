<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-heroicon-o-heart class="w-5 h-5 text-primary-500" />
                <span>{{ __('حالة النظام') }}</span>
            </div>
        </x-slot>

        <x-slot name="headerEnd">
            <a href="{{ route('filament.admin.pages.health-check-results') }}"
               class="text-sm text-primary-600 hover:text-primary-500 dark:text-primary-400">
                {{ __('عرض التفاصيل') }}
            </a>
        </x-slot>

        {{-- Summary Stats --}}
        <div class="grid grid-cols-3 gap-4 mb-4">
            <div class="flex items-center gap-3 p-3 bg-emerald-50 dark:bg-emerald-900/20 rounded-lg">
                <div class="flex items-center justify-center w-10 h-10 bg-emerald-100 dark:bg-emerald-800 rounded-full">
                    <x-heroicon-s-check-circle class="w-6 h-6 text-emerald-600 dark:text-emerald-400" />
                </div>
                <div>
                    <div class="text-2xl font-bold text-emerald-700 dark:text-emerald-300">{{ $summary['ok'] }}</div>
                    <div class="text-xs text-emerald-600 dark:text-emerald-400">{{ __('سليم') }}</div>
                </div>
            </div>

            <div class="flex items-center gap-3 p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg">
                <div class="flex items-center justify-center w-10 h-10 bg-yellow-100 dark:bg-yellow-800 rounded-full">
                    <x-heroicon-s-exclamation-circle class="w-6 h-6 text-yellow-600 dark:text-yellow-400" />
                </div>
                <div>
                    <div class="text-2xl font-bold text-yellow-700 dark:text-yellow-300">{{ $summary['warning'] }}</div>
                    <div class="text-xs text-yellow-600 dark:text-yellow-400">{{ __('تحذير') }}</div>
                </div>
            </div>

            <div class="flex items-center gap-3 p-3 bg-red-50 dark:bg-red-900/20 rounded-lg">
                <div class="flex items-center justify-center w-10 h-10 bg-red-100 dark:bg-red-800 rounded-full">
                    <x-heroicon-s-x-circle class="w-6 h-6 text-red-600 dark:text-red-400" />
                </div>
                <div>
                    <div class="text-2xl font-bold text-red-700 dark:text-red-300">{{ $summary['failed'] }}</div>
                    <div class="text-xs text-red-600 dark:text-red-400">{{ __('فشل') }}</div>
                </div>
            </div>
        </div>

        {{-- Health Check Items --}}
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-3">
            @foreach ($checkResults as $result)
                @php
                    $statusClasses = match ($result->status) {
                        \Spatie\Health\Enums\Status::ok()->value => [
                            'bg' => 'bg-emerald-50 dark:bg-emerald-900/20',
                            'icon' => 'text-emerald-500',
                            'iconBg' => 'bg-emerald-100 dark:bg-emerald-800',
                        ],
                        \Spatie\Health\Enums\Status::warning()->value => [
                            'bg' => 'bg-yellow-50 dark:bg-yellow-900/20',
                            'icon' => 'text-yellow-500',
                            'iconBg' => 'bg-yellow-100 dark:bg-yellow-800',
                        ],
                        \Spatie\Health\Enums\Status::failed()->value, \Spatie\Health\Enums\Status::crashed()->value => [
                            'bg' => 'bg-red-50 dark:bg-red-900/20',
                            'icon' => 'text-red-500',
                            'iconBg' => 'bg-red-100 dark:bg-red-800',
                        ],
                        default => [
                            'bg' => 'bg-gray-50 dark:bg-gray-800',
                            'icon' => 'text-gray-500',
                            'iconBg' => 'bg-gray-100 dark:bg-gray-700',
                        ],
                    };
                @endphp

                <div class="flex items-center gap-3 p-3 rounded-lg {{ $statusClasses['bg'] }}">
                    <div class="flex-shrink-0 flex items-center justify-center w-8 h-8 rounded-full {{ $statusClasses['iconBg'] }}">
                        @if($result->status === \Spatie\Health\Enums\Status::ok()->value)
                            <x-heroicon-s-check-circle class="w-5 h-5 {{ $statusClasses['icon'] }}" />
                        @elseif($result->status === \Spatie\Health\Enums\Status::warning()->value)
                            <x-heroicon-s-exclamation-circle class="w-5 h-5 {{ $statusClasses['icon'] }}" />
                        @else
                            <x-heroicon-s-x-circle class="w-5 h-5 {{ $statusClasses['icon'] }}" />
                        @endif
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="text-sm font-medium text-gray-900 dark:text-white truncate">
                            {{ $result->label }}
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400 truncate">
                            {{ $result->shortSummary ?? $result->notificationMessage }}
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Last Updated --}}
        @if ($lastRanAt)
            <div class="mt-4 text-center text-xs {{ $lastRanAt->diffInMinutes() > 5 ? 'text-red-500' : 'text-gray-400 dark:text-gray-500' }}">
                {{ __('آخر تحديث') }}: {{ $lastRanAt->diffForHumans() }}
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
