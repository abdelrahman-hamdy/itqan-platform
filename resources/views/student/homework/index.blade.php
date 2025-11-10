<x-layouts.student-layout title="واجباتي">
    <div class="container mx-auto px-4 py-8">
        <!-- Page Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 flex items-center">
                        <i class="ri-book-2-line text-blue-600 ml-3"></i>
                        واجباتي
                    </h1>
                    <p class="text-gray-600 mt-2">عرض وإدارة جميع الواجبات الدراسية</p>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        @if(isset($statistics))
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-xl shadow-md p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-blue-100 text-sm font-medium">إجمالي الواجبات</p>
                        <p class="text-4xl font-bold mt-2">{{ $statistics['total'] }}</p>
                    </div>
                    <div class="bg-white bg-opacity-20 rounded-full p-4">
                        <i class="ri-file-list-line text-3xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-r from-yellow-500 to-yellow-600 rounded-xl shadow-md p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-yellow-100 text-sm font-medium">قيد الانتظار</p>
                        <p class="text-4xl font-bold mt-2">{{ $statistics['pending'] }}</p>
                    </div>
                    <div class="bg-white bg-opacity-20 rounded-full p-4">
                        <i class="ri-time-line text-3xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-xl shadow-md p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-green-100 text-sm font-medium">تم التسليم</p>
                        <p class="text-4xl font-bold mt-2">{{ $statistics['submitted'] }}</p>
                        <p class="text-sm text-green-100 mt-1">{{ $statistics['submission_rate'] }}%</p>
                    </div>
                    <div class="bg-white bg-opacity-20 rounded-full p-4">
                        <i class="ri-check-double-line text-3xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-r from-purple-500 to-purple-600 rounded-xl shadow-md p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-purple-100 text-sm font-medium">المعدل</p>
                        <p class="text-4xl font-bold mt-2">{{ number_format($statistics['average_score'], 1) }}%</p>
                    </div>
                    <div class="bg-white bg-opacity-20 rounded-full p-4">
                        <i class="ri-star-line text-3xl"></i>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Filters -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
            <form method="GET" action="{{ route('student.homework.index') }}" class="flex flex-wrap gap-4 items-end">
                <div class="flex-1 min-w-[200px]">
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-2">الحالة</label>
                    <select name="status" id="status" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                        <option value="">جميع الحالات</option>
                        <option value="not_submitted" {{ request('status') === 'not_submitted' ? 'selected' : '' }}>لم يتم التسليم</option>
                        <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>مسودة</option>
                        <option value="submitted" {{ request('status') === 'submitted' ? 'selected' : '' }}>تم التسليم</option>
                        <option value="late" {{ request('status') === 'late' ? 'selected' : '' }}>تسليم متأخر</option>
                        <option value="graded" {{ request('status') === 'graded' ? 'selected' : '' }}>تم التصحيح</option>
                    </select>
                </div>

                <div class="flex-1 min-w-[200px]">
                    <label for="type" class="block text-sm font-medium text-gray-700 mb-2">نوع الواجب</label>
                    <select name="type" id="type" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                        <option value="">جميع الأنواع</option>
                        <option value="academic" {{ request('type') === 'academic' ? 'selected' : '' }}>أكاديمي</option>
                        <option value="quran" {{ request('type') === 'quran' ? 'selected' : '' }}>قرآن</option>
                        <option value="interactive" {{ request('type') === 'interactive' ? 'selected' : '' }}>دورة تفاعلية</option>
                    </select>
                </div>

                <div class="flex gap-2">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition-colors">
                        <i class="ri-search-line ml-1"></i>
                        بحث
                    </button>
                    <a href="{{ route('student.homework.index') }}" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-6 py-2 rounded-lg transition-colors">
                        <i class="ri-refresh-line ml-1"></i>
                        إعادة تعيين
                    </a>
                </div>
            </form>
        </div>

        <!-- Homework List -->
        @if(isset($homework) && count($homework) > 0)
        <div class="space-y-4">
            @foreach($homework as $hw)
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 hover:shadow-md transition-shadow">
                <div class="p-6">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <!-- Type Badge -->
                            <div class="flex items-center gap-3 mb-3">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium
                                    {{ $hw['type'] === 'academic' ? 'bg-purple-100 text-purple-800' :
                                       ($hw['type'] === 'quran' ? 'bg-green-100 text-green-800' :
                                       'bg-blue-100 text-blue-800') }}">
                                    <i class="{{ $hw['type'] === 'academic' ? 'ri-book-line' :
                                                 ($hw['type'] === 'quran' ? 'ri-book-open-line' :
                                                 'ri-presentation-line') }} ml-1"></i>
                                    {{ $hw['type'] === 'academic' ? 'أكاديمي' :
                                       ($hw['type'] === 'quran' ? 'قرآن' :
                                       'دورة تفاعلية') }}
                                </span>

                                <!-- Status Badge -->
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium
                                    {{ $hw['status'] === 'not_submitted' ? 'bg-gray-100 text-gray-800' :
                                       ($hw['status'] === 'draft' ? 'bg-yellow-100 text-yellow-800' :
                                       (in_array($hw['status'], ['submitted', 'late']) ? 'bg-blue-100 text-blue-800' :
                                       'bg-green-100 text-green-800')) }}">
                                    {{ $hw['status_text'] }}
                                </span>

                                @if($hw['is_late'])
                                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-red-100 text-red-800">
                                    <i class="ri-error-warning-line ml-1"></i>
                                    متأخر
                                </span>
                                @endif
                            </div>

                            <!-- Title & Description -->
                            <h3 class="text-lg font-bold text-gray-900 mb-2">{{ $hw['title'] }}</h3>
                            @if($hw['description'])
                            <p class="text-sm text-gray-600 mb-3 line-clamp-2">{{ $hw['description'] }}</p>
                            @endif

                            <!-- Info Row -->
                            <div class="flex flex-wrap items-center gap-4 text-sm text-gray-600">
                                @if($hw['due_date'])
                                <div class="flex items-center">
                                    <i class="ri-calendar-line ml-1"></i>
                                    <span>موعد التسليم: {{ $hw['due_date']->format('Y-m-d h:i A') }}</span>
                                </div>
                                @endif

                                @if($hw['submitted_at'])
                                <div class="flex items-center">
                                    <i class="ri-send-plane-line ml-1"></i>
                                    <span>تم التسليم: {{ $hw['submitted_at']->format('Y-m-d h:i A') }}</span>
                                </div>
                                @endif

                                @if($hw['score'] !== null)
                                <div class="flex items-center">
                                    <i class="ri-star-line ml-1"></i>
                                    <span class="font-semibold {{ $hw['score_percentage'] >= 80 ? 'text-green-600' :
                                                                   ($hw['score_percentage'] >= 60 ? 'text-yellow-600' :
                                                                   'text-red-600') }}">
                                        {{ $hw['score'] }}/{{ $hw['max_score'] }} ({{ number_format($hw['score_percentage'], 1) }}%)
                                    </span>
                                </div>
                                @endif
                            </div>

                            <!-- Teacher Feedback -->
                            @if($hw['teacher_feedback'])
                            <div class="mt-4 p-3 bg-blue-50 rounded-lg border border-blue-200">
                                <p class="text-sm font-medium text-blue-900 mb-1 flex items-center">
                                    <i class="ri-feedback-line ml-1"></i>
                                    ملاحظات المعلم:
                                </p>
                                <p class="text-sm text-blue-800">{{ $hw['teacher_feedback'] }}</p>
                            </div>
                            @endif
                        </div>

                        <!-- Actions -->
                        <div class="flex flex-col gap-2 mr-4">
                            @if(in_array($hw['status'], ['not_submitted', 'draft']))
                            <a href="{{ route('student.homework.submit', ['id' => $hw['homework_id'], 'type' => $hw['type']]) }}"
                               class="inline-flex items-center justify-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
                                <i class="ri-upload-line ml-1"></i>
                                تسليم الواجب
                            </a>
                            @else
                            <a href="{{ route('student.homework.view', ['id' => $hw['id'], 'type' => $hw['type']]) }}"
                               class="inline-flex items-center justify-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-lg transition-colors">
                                <i class="ri-eye-line ml-1"></i>
                                عرض التفاصيل
                            </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        <!-- Pagination -->
        {{-- If using pagination, add here --}}

        @else
        <!-- Empty State -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
            <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="ri-inbox-line text-gray-400 text-4xl"></i>
            </div>
            <h3 class="text-xl font-semibold text-gray-900 mb-2">لا توجد واجبات</h3>
            <p class="text-gray-600">
                @if(request('status') || request('type'))
                    لم يتم العثور على واجبات تطابق المعايير المحددة.
                @else
                    لم يتم تعيين أي واجبات لك بعد.
                @endif
            </p>
        </div>
        @endif
    </div>
</x-layouts.student-layout>
