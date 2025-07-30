<x-filament-widgets::widget>
    <x-filament::card>
        @if($is_super_admin)
            @if($has_academy_selected && $current_academy)
                {{-- Super Admin with Academy Selected --}}
                <div class="flex items-center gap-4 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                    <div class="flex-shrink-0">
                        @if($current_academy->logo)
                            <img src="{{ $current_academy->logo }}" alt="{{ $current_academy->name }}" class="w-12 h-12 rounded-lg">
                        @else
                            <div class="w-12 h-12 bg-blue-500 rounded-lg flex items-center justify-center">
                                <span class="text-white font-bold text-lg">{{ substr($current_academy->name, 0, 1) }}</span>
                            </div>
                        @endif
                    </div>
                    
                    <div class="flex-1">
                        <h3 class="text-lg font-semibold text-blue-900 dark:text-blue-100">
                            إدارة أكاديمية: {{ $current_academy->name }}
                        </h3>
                        <p class="text-sm text-blue-700 dark:text-blue-300">
                            النطاق الفرعي: {{ $current_academy->subdomain }}.itqan.com
                        </p>
                        <p class="text-xs text-blue-600 dark:text-blue-400 mt-1">
                            جميع العمليات ستتم على هذه الأكاديمية
                        </p>
                    </div>
                    
                    <div class="flex-shrink-0">
                        <x-filament::badge color="success" size="lg">
                            <x-heroicon-m-check-circle class="w-4 h-4 ml-1"/>
                            أكاديمية محددة
                        </x-filament::badge>
                    </div>
                </div>
            @else
                {{-- Super Admin without Academy Selected --}}
                <div class="p-6 bg-amber-50 dark:bg-amber-900/20 rounded-lg border border-amber-200 dark:border-amber-800">
                    <div class="flex items-center gap-4">
                        <div class="flex-shrink-0">
                            <x-heroicon-o-exclamation-triangle class="w-10 h-10 text-amber-500"/>
                        </div>
                        
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold text-amber-900 dark:text-amber-100">
                                يرجى اختيار أكاديمية للإدارة
                            </h3>
                            <p class="text-sm text-amber-700 dark:text-amber-300 mt-1">
                                لعرض وإدارة محتوى أكاديمية معينة، يرجى اختيارها من القائمة المنسدلة في الأعلى
                            </p>
                            <p class="text-xs text-amber-600 dark:text-amber-400 mt-2">
                                متوفر {{ $available_academies_count }} أكاديمية للإدارة
                            </p>
                        </div>
                        
                        <div class="flex-shrink-0">
                            <x-filament::badge color="warning" size="lg">
                                <x-heroicon-m-building-office class="w-4 h-4 ml-1"/>
                                عام
                            </x-filament::badge>
                        </div>
                    </div>
                </div>
            @endif
        @else
            {{-- Regular User --}}
            @if($current_academy)
                <div class="flex items-center gap-4 p-4 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800">
                    <div class="flex-shrink-0">
                        @if($current_academy->logo)
                            <img src="{{ $current_academy->logo }}" alt="{{ $current_academy->name }}" class="w-10 h-10 rounded-lg">
                        @else
                            <div class="w-10 h-10 bg-green-500 rounded-lg flex items-center justify-center">
                                <span class="text-white font-bold">{{ substr($current_academy->name, 0, 1) }}</span>
                            </div>
                        @endif
                    </div>
                    
                    <div class="flex-1">
                        <h3 class="font-semibold text-green-900 dark:text-green-100">
                            {{ $current_academy->name }}
                        </h3>
                        <p class="text-sm text-green-700 dark:text-green-300">
                            مرحباً {{ $user->name }}
                        </p>
                    </div>
                    
                    <div class="flex-shrink-0">
                        <x-filament::badge color="success">
                            <x-heroicon-m-home class="w-4 h-4 ml-1"/>
                            أكاديميتي
                        </x-filament::badge>
                    </div>
                </div>
            @endif
        @endif
    </x-filament::card>
</x-filament-widgets::widget> 