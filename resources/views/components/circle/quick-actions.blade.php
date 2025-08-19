@props([
    'circle',
    'viewType' => 'teacher', // 'student', 'teacher', 'supervisor'
    'isEnrolled' => false,
    'canEnroll' => false
])

@if($viewType === 'teacher')
    <!-- Quick Actions -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h3 class="font-bold text-gray-900 mb-4">إجراءات سريعة</h3>
        
        <div class="space-y-3">
            <!-- Schedule functionality removed - now handled in Filament dashboard -->
            
            <a href="{{ route('teacher.group-circles.progress', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'circle' => $circle->id]) }}" 
               class="w-full flex items-center justify-center px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors">
                <i class="ri-line-chart-line ml-2"></i>
                عرض التقارير التفصيلية
            </a>

            <button type="button" onclick="updateCircleSettings()" 
                class="w-full flex items-center justify-center px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors">
                <i class="ri-settings-line ml-2"></i>
                إعدادات الحلقة
            </button>
        </div>
    </div>
@elseif($viewType === 'student')
    <!-- Student Quick Actions -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h3 class="font-bold text-gray-900 mb-4">إجراءات سريعة</h3>
        
        <div class="space-y-3">
            @if($circle->room_link)
                <a href="{{ $circle->room_link }}" target="_blank"
                   class="w-full flex items-center justify-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors">
                    <i class="ri-video-line ml-2"></i>
                    دخول الجلسة
                </a>
            @endif
            
            @if($isEnrolled)
                <button onclick="showLeaveModal({{ $circle->id }})"
                        class="w-full flex items-center justify-center px-4 py-2 bg-red-100 text-red-700 text-sm font-medium rounded-lg hover:bg-red-200 transition-colors">
                    <i class="ri-user-unfollow-line ml-2"></i>
                    إلغاء التسجيل
                </button>
            @elseif($canEnroll)
                <button onclick="showEnrollModal({{ $circle->id }})"
                        class="w-full flex items-center justify-center px-4 py-2 bg-primary text-white text-sm font-medium rounded-lg hover:bg-secondary transition-colors">
                    <i class="ri-user-add-line ml-2"></i>
                    انضم للحلقة
                </button>
            @endif
            
            <a href="{{ route('student.quran-circles', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy']) }}" 
               class="w-full flex items-center justify-center px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors">
                <i class="ri-group-line ml-2"></i>
                جميع الحلقات
            </a>
            
            <a href="{{ route('student.quran', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy']) }}" 
               class="w-full flex items-center justify-center px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors">
                <i class="ri-user-line ml-2"></i>
                ملفي الشخصي
            </a>
        </div>
    </div>
@endif

@if($viewType === 'teacher')
<script>
    function updateCircleSettings() {
        // This will be implemented when we create the settings functionality
        alert('سيتم تنفيذ إعدادات الحلقة قريباً');
    }
</script>
@endif
