<div class="relative" x-data="{ open: false }" 
     x-init="
        $wire.on('academy-selected', (event) => {
            // Reload the page to refresh all resources and navigation
            window.location.reload();
        });
        $wire.on('academy-cleared', () => {
            // Reload the page to refresh all resources and navigation
            window.location.reload();
        });
        $wire.on('global-view-enabled', () => {
            // Reload page when global view is enabled
            setTimeout(() => {
                window.location.reload();
            }, 100);
        });
     ">
    <!-- Academy Selector Button -->
    <button 
        @click="open = !open"
        class="flex items-center gap-2 px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700"
    >
        @if($isGlobalView)
            <!-- Global View State -->
            <svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/>
                <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/>
            </svg>
            <span class="font-semibold text-green-600">جميع الأكاديميات</span>
        @elseif($currentAcademy)
            <!-- Academy Logo/Icon -->
            @if($currentAcademy->logo)
                <div class="w-5 h-5 rounded overflow-hidden">
                    <img src="{{ $currentAcademy->logo }}" alt="{{ $currentAcademy->name }}" 
                         class="w-full h-full object-cover"
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="w-full h-full bg-blue-500 rounded flex items-center justify-center" style="display: none;">
                        <span class="text-xs text-white font-bold">{{ substr($currentAcademy->name, 0, 1) }}</span>
                    </div>
                </div>
            @else
                <div class="w-5 h-5 bg-blue-500 rounded flex items-center justify-center">
                    <span class="text-xs text-white font-bold">{{ substr($currentAcademy->name, 0, 1) }}</span>
                </div>
            @endif
            
            <!-- Academy Name -->
            <span class="truncate max-w-32">{{ $currentAcademy->name }}</span>
        @else
            <!-- Default State -->
            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
            </svg>
            <span>اختر الأكاديمية</span>
        @endif
        
        <!-- Dropdown Arrow -->
        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
        </svg>
    </button>

    <!-- Dropdown Menu -->
    <div 
        x-show="open" 
        @click.away="open = false"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="transform opacity-0 scale-95"
        x-transition:enter-end="transform opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="transform opacity-100 scale-100"
        x-transition:leave-end="transform opacity-0 scale-95"
        class="absolute right-0 mt-2 w-72 bg-white rounded-md shadow-lg ring-1 ring-black ring-opacity-5 z-50 dark:bg-gray-800 dark:ring-white/10"
    >
        <div class="py-1">
            <!-- Header -->
            <div class="px-4 py-2 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-sm font-medium text-gray-900 dark:text-white">الأكاديميات المتاحة</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400">اختر أكاديمية لإدارة محتواها</p>
            </div>

            <!-- Global View Option -->
            <button 
                wire:click="selectAcademy('global')"
                type="button"
                class="w-full text-left px-4 py-3 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors {{ $isGlobalView ? 'bg-green-50 dark:bg-green-900/50 border-l-4 border-green-600 dark:border-green-400' : '' }}"
            >
                <div class="flex items-center gap-3">
                    <!-- Global View Icon -->
                    <div class="w-8 h-8 bg-green-500 rounded flex items-center justify-center">
                        <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/>
                            <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    
                    <!-- Global View Info -->
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium truncate {{ $isGlobalView ? 'text-green-900 dark:text-green-100' : 'text-gray-900 dark:text-white' }}">
                            جميع الأكاديميات
                        </p>
                        <p class="text-xs truncate {{ $isGlobalView ? 'text-green-700 dark:text-green-200' : 'text-gray-500 dark:text-gray-400' }}">
                            عرض شامل لجميع البيانات عبر كل الأكاديميات
                        </p>
                    </div>
                    
                    <!-- Selected Indicator -->
                    @if($isGlobalView)
                        <svg class="w-4 h-4 text-green-600 dark:text-green-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                        </svg>
                    @endif
                </div>
            </button>

            <!-- Separator -->
            <div class="border-t border-gray-200 dark:border-gray-700 my-1"></div>

            <!-- Academy List -->
            @forelse($academies as $academy)
                <button 
                    wire:click="selectAcademy({{ $academy->id }})"
                    type="button"
                    class="w-full text-left px-4 py-3 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors {{ $selectedAcademyId == $academy->id ? 'bg-blue-50 dark:bg-blue-900/50 border-l-4 border-blue-600 dark:border-blue-400' : '' }}"
                >
                    <div class="flex items-center gap-3">
                        <!-- Academy Logo/Icon -->
                        @if($academy->logo)
                            <div class="w-8 h-8 rounded overflow-hidden">
                                <img src="{{ $academy->logo }}" alt="{{ $academy->name }}" 
                                     class="w-full h-full object-cover"
                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                <div class="w-full h-full bg-blue-500 rounded flex items-center justify-center" style="display: none;">
                                    <span class="text-sm text-white font-bold">{{ substr($academy->name, 0, 1) }}</span>
                                </div>
                            </div>
                        @else
                            <div class="w-8 h-8 bg-blue-500 rounded flex items-center justify-center">
                                <span class="text-sm text-white font-bold">{{ substr($academy->name, 0, 1) }}</span>
                            </div>
                        @endif
                        
                        <!-- Academy Info -->
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium truncate {{ $selectedAcademyId == $academy->id ? 'text-blue-900 dark:text-blue-100' : 'text-gray-900 dark:text-white' }}">
                                {{ $academy->name }}
                            </p>
                            <p class="text-xs truncate {{ $selectedAcademyId == $academy->id ? 'text-blue-700 dark:text-blue-200' : 'text-gray-500 dark:text-gray-400' }}">
                                {{ $academy->subdomain }}.{{ config('app.domain', 'itqan-platform.test') }}
                            </p>
                        </div>
                        
                        <!-- Selected Indicator -->
                        @if($selectedAcademyId == $academy->id)
                            <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                        @endif
                    </div>
                </button>
            @empty
                <div class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                    لا توجد أكاديميات متاحة
                </div>
            @endforelse

            {{-- Clear Selection option removed to prevent dashboard pages from disappearing --}}
        </div>
    </div>
</div> 