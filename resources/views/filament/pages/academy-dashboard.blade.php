<x-filament-panels::page>
    @php
        $tenant = \Filament\Facades\Filament::getTenant();
    @endphp

    <!-- Academy Info Header -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
        <div class="flex flex-col lg:flex-row items-start lg:items-center gap-4">
            @if($tenant && $tenant->logo)
                <img src="{{ Storage::url($tenant->logo) }}" alt="{{ $tenant->name }}" class="w-16 h-16 rounded-lg flex-shrink-0 object-contain">
            @else
                <div class="w-16 h-16 bg-blue-500 rounded-lg flex items-center justify-center flex-shrink-0">
                    <span class="text-2xl text-white font-bold">{{ $tenant ? substr($tenant->name, 0, 1) : 'أ' }}</span>
                </div>
            @endif

            <div class="flex-1 min-w-0">
                <h2 class="text-xl lg:text-2xl font-bold text-gray-900 dark:text-white mb-2">{{ $tenant?->name ?? 'الأكاديمية' }}</h2>
                @if($tenant?->description)
                    <p class="text-gray-600 dark:text-gray-400 mb-2 line-clamp-2">{{ $tenant->description }}</p>
                @endif
                <div class="flex flex-col sm:flex-row items-start sm:items-center gap-2 sm:gap-4 text-sm text-gray-500 dark:text-gray-400">
                    @if($tenant)
                        <span class="flex items-center gap-1">
                            <x-heroicon-o-globe-alt class="w-4 h-4" />
                            {{ $tenant->subdomain }}.{{ config('app.domain', 'itqanway.com') }}
                        </span>
                        @if($tenant->email)
                            <span class="flex items-center gap-1">
                                <x-heroicon-o-envelope class="w-4 h-4" />
                                {{ $tenant->email }}
                            </span>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Widgets -->
    <x-filament-widgets::widgets
        :widgets="$this->getVisibleWidgets()"
        :columns="$this->getColumns()"
    />
</x-filament-panels::page>
