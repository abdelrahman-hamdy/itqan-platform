@props([
    'circle',
    'variant' => 'default', // 'default', 'compact', 'detailed'
    'showTitle' => true
])

@if($circle->current_surah || $circle->verses_memorized || $circle->current_verse)
    <div class="bg-gradient-to-r from-green-50 to-emerald-50 rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
        @if($showTitle)
            <div class="flex items-center mb-4">
                <div class="p-2 bg-green-200 rounded-lg ml-3">
                    <i class="ri-book-open-line text-xl text-green-600"></i>
                </div>
                <h4 class="text-lg font-bold text-gray-900">التقدم في الحفظ</h4>
            </div>
        @endif
        
        @if($variant === 'detailed')
            <!-- Detailed Progress View -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                @if($circle->current_surah)
                    <div class="bg-white rounded-xl p-6 border border-green-100 text-center">
                        <div class="w-16 h-16 bg-gradient-to-r from-green-400 to-green-500 rounded-full flex items-center justify-center mx-auto mb-3">
                            <i class="ri-book-mark-line text-2xl text-white"></i>
                        </div>
                        <p class="text-sm text-gray-600 mb-1">السورة الحالية</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $circle->current_surah }}</p>
                        <p class="text-xs text-gray-500 mt-1">سورة رقم</p>
                    </div>
                @endif
                
                @if($circle->current_verse)
                    <div class="bg-white rounded-xl p-6 border border-green-100 text-center">
                        <div class="w-16 h-16 bg-gradient-to-r from-blue-400 to-blue-500 rounded-full flex items-center justify-center mx-auto mb-3">
                            <i class="ri-file-text-line text-2xl text-white"></i>
                        </div>
                        <p class="text-sm text-gray-600 mb-1">الآية الحالية</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $circle->current_verse }}</p>
                        <p class="text-xs text-gray-500 mt-1">آية رقم</p>
                    </div>
                @endif
                
                @if($circle->verses_memorized)
                    <div class="bg-white rounded-xl p-6 border border-green-100 text-center">
                        <div class="w-16 h-16 bg-gradient-to-r from-green-400 to-green-500 rounded-full flex items-center justify-center mx-auto mb-3">
                            <i class="ri-medal-line text-2xl text-white"></i>
                        </div>
                        <p class="text-sm text-gray-600 mb-1">إجمالي الآيات</p>
                        <p class="text-2xl font-bold text-green-600">{{ $circle->verses_memorized }}</p>
                        <p class="text-xs text-gray-500 mt-1">آية محفوظة</p>
                    </div>
                @endif
            </div>
        @elseif($variant === 'compact')
            <!-- Compact Progress View -->
            <div class="flex items-center justify-between">
                @if($circle->current_surah)
                    <div class="flex items-center space-x-2 space-x-reverse">
                        <i class="ri-book-mark-line text-green-600"></i>
                        <span class="text-sm text-gray-600">سورة {{ $circle->current_surah }}</span>
                    </div>
                @endif
                
                @if($circle->current_verse)
                    <div class="flex items-center space-x-2 space-x-reverse">
                        <i class="ri-file-text-line text-blue-600"></i>
                        <span class="text-sm text-gray-600">آية {{ $circle->current_verse }}</span>
                    </div>
                @endif
                
                @if($circle->verses_memorized)
                    <div class="flex items-center space-x-2 space-x-reverse">
                        <i class="ri-medal-line text-green-600"></i>
                        <span class="text-sm font-medium text-green-600">{{ $circle->verses_memorized }} آية محفوظة</span>
                    </div>
                @endif
            </div>
        @else
            <!-- Default Progress View -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                @if($circle->current_surah)
                    <div class="bg-white rounded-lg p-4 border border-green-100">
                        <div class="text-center">
                            <i class="ri-book-mark-line text-2xl text-green-600 mb-2"></i>
                            <p class="text-xs text-gray-600 mb-1">السورة الحالية</p>
                            <p class="text-lg font-bold text-gray-900">{{ $circle->current_surah }}</p>
                        </div>
                    </div>
                @endif
                
                @if($circle->current_verse)
                    <div class="bg-white rounded-lg p-4 border border-green-100">
                        <div class="text-center">
                            <i class="ri-file-text-line text-2xl text-blue-600 mb-2"></i>
                            <p class="text-xs text-gray-600 mb-1">الآية الحالية</p>
                            <p class="text-lg font-bold text-gray-900">{{ $circle->current_verse }}</p>
                        </div>
                    </div>
                @endif
                
                @if($circle->verses_memorized)
                    <div class="bg-white rounded-lg p-4 border border-green-100">
                        <div class="text-center">
                            <i class="ri-medal-line text-2xl text-green-600 mb-2"></i>
                            <p class="text-xs text-gray-600 mb-1">إجمالي الآيات</p>
                            <p class="text-lg font-bold text-green-600">{{ $circle->verses_memorized }}</p>
                            <p class="text-xs text-gray-500">آية محفوظة</p>
                        </div>
                    </div>
                @endif
            </div>
        @endif
        
        <!-- Additional Progress Information -->
        @if($circle->progress_notes || $circle->improvement_areas)
            <div class="mt-4 pt-4 border-t border-green-200">
                @if($circle->progress_notes)
                    <div class="mb-3">
                        <p class="text-sm font-medium text-green-700 mb-1">ملاحظات التقدم:</p>
                        <p class="text-sm text-gray-700">{{ $circle->progress_notes }}</p>
                    </div>
                @endif
                
                @if($circle->improvement_areas && is_array($circle->improvement_areas))
                    <div>
                        <p class="text-sm font-medium text-green-700 mb-2">نقاط التحسين:</p>
                        <div class="flex flex-wrap gap-1">
                            @foreach($circle->improvement_areas as $area)
                                <span class="inline-flex items-center px-2 py-1 bg-yellow-100 text-yellow-800 text-xs rounded">
                                    {{ $area }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @endif
    </div>
@endif
