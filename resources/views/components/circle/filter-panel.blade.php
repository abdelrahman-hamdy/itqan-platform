@props([
    'route', // Form action route
    'filters' => ['search', 'enrollment_status', 'memorization_level', 'teacher_id', 'schedule_day'], // Available filters
    'teachers' => collect(), // Available teachers for dropdown
    'showHeader' => true,
    'title' => 'تصفية النتائج'
])

@php
    $hasActiveFilters = request()->hasAny(['enrollment_status', 'memorization_level', 'teacher_id', 'schedule_day', 'search', 'specialization']);
    $subdomain = auth()->user()->academy->subdomain ?? 'itqan-academy';
@endphp

<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
    <form method="GET" action="{{ $route }}" class="space-y-4">
        @if($showHeader)
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900">
                <i class="ri-filter-3-line ml-2"></i>
                {{ $title }}
            </h3>
            @if($hasActiveFilters)
            <a href="{{ $route }}"
               class="text-sm text-gray-600 hover:text-primary transition-colors">
                <i class="ri-close-circle-line ml-1"></i>
                إعادة تعيين الفلاتر
            </a>
            @endif
        </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Search -->
            @if(in_array('search', $filters))
            <div class="lg:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="ri-search-line ml-1"></i>
                    البحث
                </label>
                <input type="text"
                       name="search"
                       value="{{ request('search') }}"
                       placeholder="ابحث..."
                       class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary focus:border-primary transition-colors">
            </div>
            @endif

            <!-- Enrollment Status (Group circles) -->
            @if(in_array('enrollment_status', $filters))
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="ri-user-follow-line ml-1"></i>
                    حالة التسجيل
                </label>
                <select name="enrollment_status"
                        class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary focus:border-primary transition-colors">
                    <option value="">الكل</option>
                    <option value="enrolled" {{ request('enrollment_status') === 'enrolled' ? 'selected' : '' }}>حلقاتي</option>
                    <option value="available" {{ request('enrollment_status') === 'available' ? 'selected' : '' }}>متاحة للتسجيل</option>
                    <option value="open" {{ request('enrollment_status') === 'open' ? 'selected' : '' }}>مفتوحة</option>
                    <option value="full" {{ request('enrollment_status') === 'full' ? 'selected' : '' }}>مكتملة</option>
                </select>
            </div>
            @endif

            <!-- Memorization Level (Quran circles) -->
            @if(in_array('memorization_level', $filters))
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="ri-bar-chart-line ml-1"></i>
                    مستوى الحفظ
                </label>
                <select name="memorization_level"
                        class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary focus:border-primary transition-colors">
                    <option value="">جميع المستويات</option>
                    <option value="beginner" {{ request('memorization_level') === 'beginner' ? 'selected' : '' }}>مبتدئ</option>
                    <option value="intermediate" {{ request('memorization_level') === 'intermediate' ? 'selected' : '' }}>متوسط</option>
                    <option value="advanced" {{ request('memorization_level') === 'advanced' ? 'selected' : '' }}>متقدم</option>
                </select>
            </div>
            @endif

            <!-- Teacher -->
            @if(in_array('teacher_id', $filters))
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="ri-user-star-line ml-1"></i>
                    المعلم
                </label>
                <select name="teacher_id"
                        class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary focus:border-primary transition-colors">
                    <option value="">جميع المعلمين</option>
                    @foreach($teachers as $teacher)
                    <option value="{{ $teacher->user_id ?? $teacher->id }}" {{ request('teacher_id') == ($teacher->user_id ?? $teacher->id) ? 'selected' : '' }}>
                        {{ $teacher->user->full_name ?? $teacher->full_name ?? $teacher->name ?? 'معلم' }}
                    </option>
                    @endforeach
                </select>
            </div>
            @endif

            <!-- Schedule Day (Group circles) -->
            @if(in_array('schedule_day', $filters))
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="ri-calendar-line ml-1"></i>
                    يوم الدراسة
                </label>
                <select name="schedule_day"
                        class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary focus:border-primary transition-colors">
                    <option value="">جميع الأيام</option>
                    <option value="السبت" {{ request('schedule_day') === 'السبت' ? 'selected' : '' }}>السبت</option>
                    <option value="الأحد" {{ request('schedule_day') === 'الأحد' ? 'selected' : '' }}>الأحد</option>
                    <option value="الإثنين" {{ request('schedule_day') === 'الإثنين' ? 'selected' : '' }}>الإثنين</option>
                    <option value="الثلاثاء" {{ request('schedule_day') === 'الثلاثاء' ? 'selected' : '' }}>الثلاثاء</option>
                    <option value="الأربعاء" {{ request('schedule_day') === 'الأربعاء' ? 'selected' : '' }}>الأربعاء</option>
                    <option value="الخميس" {{ request('schedule_day') === 'الخميس' ? 'selected' : '' }}>الخميس</option>
                    <option value="الجمعة" {{ request('schedule_day') === 'الجمعة' ? 'selected' : '' }}>الجمعة</option>
                </select>
            </div>
            @endif

            <!-- Specialization (Individual circles) -->
            @if(in_array('specialization', $filters))
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="ri-book-line ml-1"></i>
                    التخصص
                </label>
                <select name="specialization"
                        class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary focus:border-primary transition-colors">
                    <option value="">جميع التخصصات</option>
                    <option value="quran" {{ request('specialization') === 'quran' ? 'selected' : '' }}>القرآن الكريم</option>
                    <option value="academic" {{ request('specialization') === 'academic' ? 'selected' : '' }}>الدروس الأكاديمية</option>
                </select>
            </div>
            @endif

            <!-- Apply Button -->
            <div class="lg:col-span-2 flex items-end">
                <button type="submit"
                        class="w-full bg-primary text-white px-6 py-2.5 rounded-lg text-sm font-medium hover:bg-secondary transition-colors">
                    <i class="ri-search-line ml-1"></i>
                    تطبيق الفلاتر
                </button>
            </div>
        </div>
    </form>
</div>
