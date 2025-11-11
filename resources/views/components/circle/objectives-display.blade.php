@props([
    'objectives' => [],
    'title' => 'أهداف الحلقة',
    'variant' => 'default' // 'default', 'compact'
])

@if(!empty($objectives) && is_array($objectives) && count($objectives) > 0)
    @if($variant === 'compact')
        <!-- Compact Objectives Display -->
        <div class="mt-4">
            <h4 class="text-sm font-semibold text-gray-700 mb-3 flex items-center gap-2">
                <div class="w-2 h-2 bg-primary-500 rounded-full"></div>
                {{ $title }}
            </h4>
            <div class="grid grid-cols-1 gap-2">
                @foreach($objectives as $index => $objective)
                    <div class="flex items-start gap-3">
                        <div class="w-5 h-5 bg-primary-100 rounded-full flex items-center justify-center mt-0.5 flex-shrink-0">
                            <span class="text-primary-600 font-semibold text-xs">{{ $index + 1 }}</span>
                        </div>
                        <span class="text-sm text-gray-600 leading-relaxed">{{ $objective }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    @else
        <!-- Full Objectives Display -->
        <div class="bg-gradient-to-br from-primary-50 to-primary-100/50 rounded-xl p-6 border border-primary-200">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 bg-primary-500 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-primary-900">{{ $title }}</h3>
            </div>
            
            <div class="grid grid-cols-1 gap-3">
                @foreach($objectives as $index => $objective)
                    <div class="bg-white rounded-lg p-4 border border-primary-100 shadow-sm hover:shadow-md transition-shadow">
                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 bg-primary-500 rounded-full flex items-center justify-center flex-shrink-0">
                                <span class="text-white font-bold text-sm">{{ $index + 1 }}</span>
                            </div>
                            <p class="text-gray-700 leading-relaxed font-medium">{{ $objective }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
@endif 