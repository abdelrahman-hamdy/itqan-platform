@props(['item'])

<a href="{{ $item['route'] }}" class="block bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:border-primary-300 hover:shadow-md transition-all duration-200">
    <div class="flex items-start justify-between mb-4">
        <div class="flex items-center space-x-3 space-x-reverse flex-1">
            <!-- Icon -->
            <div class="w-12 h-12 {{ $item['icon_bg'] }} rounded-lg flex items-center justify-center flex-shrink-0">
                <i class="{{ $item['icon'] }} {{ $item['icon_color'] }} text-xl"></i>
            </div>

            <!-- Title and Description -->
            <div class="flex-1 min-w-0">
                <h3 class="font-semibold text-gray-900 mb-1 truncate">{{ $item['title'] }}</h3>
                @if($item['description'])
                    <p class="text-sm text-gray-600 line-clamp-2">{{ $item['description'] }}</p>
                @endif
            </div>
        </div>

        <!-- Enrolled Badge -->
        @if($item['is_enrolled'])
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 flex-shrink-0">
                <i class="ri-check-line ml-1"></i>
                مشترك
            </span>
        @endif
    </div>

    <!-- Teacher Name (if applicable) -->
    @if($item['teacher_name'])
        <div class="flex items-center text-sm text-gray-600 mb-3">
            <i class="ri-user-line ml-2"></i>
            <span>{{ $item['teacher_name'] }}</span>
        </div>
    @endif

    <!-- Meta Information -->
    @if($item['meta'])
        <div class="space-y-2">
            @switch($item['type'])
                @case('quran_circle')
                @case('individual_circle')
                    @if(isset($item['meta']['students_count']))
                        <div class="flex items-center text-sm text-gray-600">
                            <i class="ri-group-line ml-2"></i>
                            <span>{{ $item['meta']['students_count'] }}@if(isset($item['meta']['max_students']))/{{ $item['meta']['max_students'] }}@endif طالب</span>
                        </div>
                    @endif
                    @if(isset($item['meta']['schedule']) && $item['meta']['schedule'])
                        <div class="flex items-center text-sm text-gray-600">
                            <i class="ri-calendar-line ml-2"></i>
                            <span>{{ $item['meta']['schedule'] }}</span>
                        </div>
                    @endif
                    @if(isset($item['meta']['monthly_fee']) && $item['meta']['monthly_fee'])
                        <div class="flex items-center text-sm text-gray-600">
                            <i class="ri-money-dollar-circle-line ml-2"></i>
                            <span>{{ $item['meta']['monthly_fee'] }} ر.س / شهرياً</span>
                        </div>
                    @endif

                    <!-- Progress bar for enrolled circles -->
                    @if($item['is_enrolled'] && isset($item['meta']['progress_percentage']))
                        <div class="mt-3">
                            <div class="flex items-center justify-between text-xs text-gray-600 mb-1">
                                <span>التقدم</span>
                                <span>{{ $item['meta']['progress_percentage'] }}%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-primary-600 h-2 rounded-full transition-all duration-300"
                                     style="width: {{ min(100, max(0, $item['meta']['progress_percentage'])) }}%"></div>
                            </div>
                        </div>
                    @endif
                    @break

                @case('interactive_course')
                @case('academic_session')
                    @if(isset($item['meta']['subject']) && $item['meta']['subject'])
                        <div class="flex items-center text-sm text-gray-600">
                            <i class="ri-bookmark-line ml-2"></i>
                            <span>{{ $item['meta']['subject'] }}</span>
                        </div>
                    @endif
                    @if(isset($item['meta']['grade_level']) && $item['meta']['grade_level'])
                        <div class="flex items-center text-sm text-gray-600">
                            <i class="ri-graduation-cap-line ml-2"></i>
                            <span>{{ $item['meta']['grade_level'] }}</span>
                        </div>
                    @endif
                    @if(isset($item['meta']['total_sessions']) && $item['meta']['total_sessions'])
                        <div class="flex items-center text-sm text-gray-600">
                            <i class="ri-calendar-line ml-2"></i>
                            <span>{{ $item['meta']['total_sessions'] }} جلسة</span>
                        </div>
                    @endif
                    @if(isset($item['meta']['duration_weeks']) && $item['meta']['duration_weeks'])
                        <div class="flex items-center text-sm text-gray-600">
                            <i class="ri-time-line ml-2"></i>
                            <span>{{ $item['meta']['duration_weeks'] }} أسبوع</span>
                        </div>
                    @endif
                    @if(isset($item['meta']['student_price']) && $item['meta']['student_price'])
                        <div class="flex items-center text-sm text-gray-600">
                            <i class="ri-money-dollar-circle-line ml-2"></i>
                            <span>{{ $item['meta']['student_price'] }} ر.س</span>
                        </div>
                    @endif

                    <!-- Progress bar for enrolled courses -->
                    @if($item['is_enrolled'] && isset($item['meta']['progress_percentage']) && $item['meta']['progress_percentage'] > 0)
                        <div class="mt-3">
                            <div class="flex items-center justify-between text-xs text-gray-600 mb-1">
                                <span>التقدم</span>
                                <span>{{ $item['meta']['progress_percentage'] }}%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-primary-600 h-2 rounded-full transition-all duration-300"
                                     style="width: {{ min(100, max(0, $item['meta']['progress_percentage'])) }}%"></div>
                            </div>
                        </div>
                    @endif
                    @break

                @case('recorded_course')
                    @if(isset($item['meta']['subject']) && $item['meta']['subject'])
                        <div class="flex items-center text-sm text-gray-600">
                            <i class="ri-bookmark-line ml-2"></i>
                            <span>{{ $item['meta']['subject'] }}</span>
                        </div>
                    @endif
                    @if(isset($item['meta']['grade_level']) && $item['meta']['grade_level'])
                        <div class="flex items-center text-sm text-gray-600">
                            <i class="ri-graduation-cap-line ml-2"></i>
                            <span>{{ $item['meta']['grade_level'] }}</span>
                        </div>
                    @endif
                    @if(isset($item['meta']['lessons_count']) && $item['meta']['lessons_count'])
                        <div class="flex items-center text-sm text-gray-600">
                            <i class="ri-play-list-line ml-2"></i>
                            <span>{{ $item['meta']['lessons_count'] }} درس</span>
                        </div>
                    @endif
                    @if(isset($item['meta']['duration_hours']) && $item['meta']['duration_hours'])
                        <div class="flex items-center text-sm text-gray-600">
                            <i class="ri-time-line ml-2"></i>
                            <span>{{ $item['meta']['duration_hours'] }} ساعة</span>
                        </div>
                    @endif
                    @if(isset($item['meta']['price']) && $item['meta']['price'])
                        <div class="flex items-center text-sm text-gray-600">
                            <i class="ri-money-dollar-circle-line ml-2"></i>
                            <span>{{ $item['meta']['price'] }} ر.س</span>
                        </div>
                    @endif

                    <!-- Progress bar for enrolled courses -->
                    @if($item['is_enrolled'] && isset($item['meta']['progress_percentage']) && $item['meta']['progress_percentage'] > 0)
                        <div class="mt-3">
                            <div class="flex items-center justify-between text-xs text-gray-600 mb-1">
                                <span>التقدم</span>
                                <span>{{ $item['meta']['progress_percentage'] }}%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-primary-600 h-2 rounded-full transition-all duration-300"
                                     style="width: {{ min(100, max(0, $item['meta']['progress_percentage'])) }}%"></div>
                            </div>
                        </div>
                    @endif
                    @break

                @case('quran_teacher')
                @case('academic_teacher')
                    @if(isset($item['meta']['specializations']) && $item['meta']['specializations'])
                        <div class="flex items-center text-sm text-gray-600">
                            <i class="ri-award-line ml-2"></i>
                            <span>{{ $item['meta']['specializations'] }}</span>
                        </div>
                    @endif
                    @if(isset($item['meta']['experience_years']) && $item['meta']['experience_years'])
                        <div class="flex items-center text-sm text-gray-600">
                            <i class="ri-time-line ml-2"></i>
                            <span>{{ $item['meta']['experience_years'] }} سنوات خبرة</span>
                        </div>
                    @endif
                    @if(isset($item['meta']['hourly_rate']) && $item['meta']['hourly_rate'])
                        <div class="flex items-center text-sm text-gray-600">
                            <i class="ri-money-dollar-circle-line ml-2"></i>
                            <span>{{ $item['meta']['hourly_rate'] }} ر.س / ساعة</span>
                        </div>
                    @endif
                    @if(isset($item['meta']['subjects']) && $item['meta']['subjects'])
                        <div class="flex items-center text-sm text-gray-600">
                            <i class="ri-bookmark-line ml-2"></i>
                            <span>{{ $item['meta']['subjects'] }}</span>
                        </div>
                    @endif
                    @if(isset($item['meta']['circles_count']) && $item['meta']['circles_count'])
                        <div class="flex items-center text-sm text-gray-600">
                            <i class="ri-group-line ml-2"></i>
                            <span>{{ $item['meta']['circles_count'] }} حلقة</span>
                        </div>
                    @endif
                    @break
            @endswitch
        </div>
    @endif

    <!-- Action Indicator -->
    <div class="mt-4 pt-3 border-t border-gray-100">
        <div class="flex items-center justify-between">
            <span class="text-sm text-gray-600">
                @if($item['is_enrolled'])
                    <i class="ri-arrow-left-s-line ml-1"></i>
                    متابعة
                @else
                    <i class="ri-arrow-left-s-line ml-1"></i>
                    عرض التفاصيل
                @endif
            </span>
            @if($item['is_enrolled'])
                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                    <i class="ri-star-fill ml-1"></i>
                    نشط
                </span>
            @endif
        </div>
    </div>
</a>
