<x-filament-widgets::widget>
    <x-filament::card class="max-w-4xl mx-auto">
        @if($is_super_admin)
            @if($is_global_view)
                {{-- Super Admin in Global View Mode --}}
                <div class="p-6 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800">
                    <div class="flex items-center gap-4">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-green-500 rounded-full flex items-center justify-center">
                                <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/>
                                    <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                        </div>
                        
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold text-green-900 dark:text-green-100">
                                العرض الشامل - جميع الأكاديميات
                            </h3>
                            <p class="text-sm text-green-700 dark:text-green-300 mt-1">
                                يتم عرض البيانات المجمعة من جميع الأكاديميات في المنصة
                            </p>
                            <p class="text-xs text-green-600 dark:text-green-400 mt-2">
                                إجمالي {{ $available_academies_count }} أكاديمية مُفعلة
                            </p>
                        </div>
                        
                        <div class="flex-shrink-0">
                            <x-filament::badge color="success" size="lg">
                                <svg class="w-4 h-4 ml-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/>
                                    <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/>
                                </svg>
                                عرض شامل
                            </x-filament::badge>
                        </div>
                    </div>
                </div>
            @elseif($has_academy_selected && $current_academy)
                {{-- Super Admin with Academy Selected --}}
                @php
                    $primaryColor = $current_academy->brand_color ?? '#3B82F6';
                    $rgbColor = sscanf($primaryColor, "#%02x%02x%02x");
                    $backgroundColor = "rgba({$rgbColor[0]}, {$rgbColor[1]}, {$rgbColor[2]}, 0.1)";
                    $borderColor = "rgba({$rgbColor[0]}, {$rgbColor[1]}, {$rgbColor[2]}, 0.3)";
                    $textColor = $primaryColor;
                @endphp
                
                <div class="flex flex-col lg:flex-row items-start lg:items-center gap-4 p-4 rounded-lg border-2" 
                     style="background-color: {{ $backgroundColor }}; border-color: {{ $borderColor }};">
                    <div class="flex-shrink-0">
                        @if($current_academy->logo)
                            <div class="w-12 h-12 rounded-lg border-2 overflow-hidden" style="border-color: {{ $primaryColor }};">
                                <img src="{{ $current_academy->logo }}" alt="{{ $current_academy->name }}" 
                                     class="w-full h-full object-cover"
                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                <div class="w-full h-full flex items-center justify-center" 
                                     style="background-color: {{ $primaryColor }}; display: none;">
                                    <span class="text-white font-bold text-lg">{{ substr($current_academy->name, 0, 1) }}</span>
                                </div>
                            </div>
                        @else
                            <div class="w-12 h-12 rounded-lg flex items-center justify-center border-2" 
                                 style="background-color: {{ $primaryColor }}; border-color: {{ $primaryColor }};">
                                <span class="text-white font-bold text-lg">{{ substr($current_academy->name, 0, 1) }}</span>
                            </div>
                        @endif
                    </div>
                    
                    <div class="flex-1 min-w-0">
                        <h3 class="text-base lg:text-lg font-semibold dark:text-white mb-1" style="color: {{ $textColor }};">
                            إدارة أكاديمية: {{ $current_academy->name }}
                        </h3>
                        <p class="text-sm opacity-75 dark:text-gray-300 mb-2" style="color: {{ $textColor }};">
                            النطاق الفرعي: {{ $current_academy->subdomain }}.itqan.com
                        </p>
                        @if($current_academy->brand_color)
                            <div class="flex items-center gap-2 mb-2">
                                <div class="w-3 h-3 rounded-full border border-gray-300" style="background-color: {{ $primaryColor }};"></div>
                                <span class="text-xs opacity-60 dark:text-gray-400" style="color: {{ $textColor }};">
                                    ألوان الأكاديمية: {{ $primaryColor }}
                                </span>
                            </div>
                        @endif
                        <p class="text-xs opacity-50 dark:text-gray-400" style="color: {{ $textColor }};">
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
                @php
                    $primaryColor = $current_academy->brand_color ?? '#10B981';
                    $rgbColor = sscanf($primaryColor, "#%02x%02x%02x");
                    $backgroundColor = "rgba({$rgbColor[0]}, {$rgbColor[1]}, {$rgbColor[2]}, 0.1)";
                    $borderColor = "rgba({$rgbColor[0]}, {$rgbColor[1]}, {$rgbColor[2]}, 0.3)";
                    $textColor = $primaryColor;
                @endphp
                
                <div class="flex flex-col lg:flex-row items-start lg:items-center gap-4 p-4 rounded-lg border" 
                     style="background-color: {{ $backgroundColor }}; border-color: {{ $borderColor }};">
                    <div class="flex-shrink-0">
                        @if($current_academy->logo)
                            <div class="w-10 h-10 rounded-lg border-2 overflow-hidden" style="border-color: {{ $primaryColor }};">
                                <img src="{{ $current_academy->logo }}" alt="{{ $current_academy->name }}" 
                                     class="w-full h-full object-cover"
                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                <div class="w-full h-full flex items-center justify-center" 
                                     style="background-color: {{ $primaryColor }}; display: none;">
                                    <span class="text-white font-bold">{{ substr($current_academy->name, 0, 1) }}</span>
                                </div>
                            </div>
                        @else
                            <div class="w-10 h-10 rounded-lg flex items-center justify-center border-2" 
                                 style="background-color: {{ $primaryColor }}; border-color: {{ $primaryColor }};">
                                <span class="text-white font-bold">{{ substr($current_academy->name, 0, 1) }}</span>
                            </div>
                        @endif
                    </div>
                    
                    <div class="flex-1 min-w-0">
                        <h3 class="font-semibold dark:text-white mb-1" style="color: {{ $textColor }};">
                            {{ $current_academy->name }}
                        </h3>
                        <p class="text-sm opacity-75 dark:text-gray-300" style="color: {{ $textColor }};">
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