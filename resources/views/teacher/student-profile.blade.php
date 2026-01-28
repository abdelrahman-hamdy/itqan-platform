<x-layouts.teacher
    :title="__('teacher.student_profile.title_with_name', ['name' => $student->name]) . ' - ' . config('app.name', __('common.app_name'))"
    :description="__('teacher.student_profile.description', ['name' => $student->name])">

<div class="p-4 md:p-6">
    <!-- Breadcrumbs -->
    <x-ui.breadcrumb
        :items="[
            ['label' => __('teacher.student_profile.breadcrumb_students'), 'route' => route('teacher.students', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy'])],
            ['label' => $student->name, 'truncate' => true],
        ]"
        view-type="teacher"
    />

    <!-- Student Header -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 md:p-6 mb-4 md:mb-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-start sm:items-center gap-3 md:gap-4">
                <x-avatar
                    :user="$student"
                    size="lg"
                    userType="student"
                    :gender="$student->gender ?? $student->studentProfile?->gender ?? 'male'" />
                <div class="flex-1 min-w-0">
                    <h1 class="text-lg sm:text-xl md:text-2xl font-bold text-gray-900 truncate">{{ $student->name }}</h1>
                    @if($student->studentProfile && $student->studentProfile->birth_date)
                        <div class="mt-1 md:mt-2">
                            <p class="text-xs md:text-sm text-gray-500">
                                <i class="ri-calendar-line ms-1"></i>
                                {{ __('teacher.student_profile.birth_date') }}: {{ $student->studentProfile->birth_date->format('Y-m-d') }}
                            </p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Message button removed - use supervised chat from subscription/circle pages --}}
        </div>
    </div>



    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 md:gap-6">
        <!-- Circles & Progress -->
        <div class="lg:col-span-2 space-y-4 md:space-y-6">
            <!-- Circles -->
            @if(count($progressData['circles']) > 0)
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 md:p-6">
                    <h3 class="text-base md:text-lg font-semibold text-gray-900 mb-3 md:mb-4">{{ __('teacher.student_profile.enrolled_circles') }}</h3>
                    <div class="space-y-3 md:space-y-4">
                        @foreach($progressData['circles'] as $circle)
                            @php
                                $circleRoute = $circle['type'] === 'individual'
                                    ? route('teacher.individual-circles.show', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'circle' => $circle['id']])
                                    : route('teacher.group-circles.show', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'circle' => $circle['id']]);
                            @endphp
                            <a href="{{ $circleRoute }}" class="block border border-gray-200 rounded-lg p-3 md:p-4 hover:border-primary-300 hover:bg-gray-50 transition-colors min-h-[56px]">
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-2">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <div class="w-3 h-3 rounded-full flex-shrink-0 {{ $circle['type'] === 'individual' ? 'bg-blue-500' : 'bg-green-500' }}"></div>
                                        <h4 class="font-medium text-gray-900 text-sm md:text-base">{{ $circle['name'] }}</h4>
                                        <span class="text-xs px-2 py-0.5 rounded-full {{ $circle['type'] === 'individual' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800' }}">
                                            {{ $circle['type'] === 'individual' ? __('teacher.student_profile.individual_circle') : __('teacher.student_profile.group_circle') }}
                                        </span>
                                        <i class="ri-external-link-line text-gray-400 text-sm hidden sm:inline"></i>
                                    </div>

                                    @if(isset($circle['progress_percentage']))
                                        <span class="text-xs md:text-sm font-medium text-gray-600">{{ $circle['progress_percentage'] }}%</span>
                                    @endif
                                </div>

                                @if(isset($circle['progress_percentage']))
                                    <div class="w-full bg-gray-200 rounded-full h-2 mb-2 md:mb-3">
                                        <div class="bg-primary-600 h-2 rounded-full" style="width: {{ $circle['progress_percentage'] }}%"></div>
                                    </div>
                                @endif

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-1.5 md:gap-4 text-xs md:text-sm text-gray-600">
                                    @if(isset($circle['sessions_completed']))
                                        <div>{{ __('teacher.student_profile.sessions_completed_label') }} {{ $circle['sessions_completed'] }}/{{ $circle['total_sessions'] ?? 0 }}</div>
                                    @endif
                                    @if(isset($circle['verses_memorized']))
                                        <div>{{ __('teacher.student_profile.verses_memorized_label') }} {{ $circle['verses_memorized'] }}</div>
                                    @endif
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="space-y-4 md:space-y-6">
            <!-- Student Information -->
            @if($student->studentProfile)
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 md:p-6">
                    <h3 class="text-base md:text-lg font-semibold text-gray-900 mb-3 md:mb-4">{{ __('teacher.student_profile.student_information') }}</h3>
                    <div class="space-y-2 md:space-y-3 text-sm md:text-base">
                        @if($student->studentProfile->grade_level_id)
                            <div class="flex justify-between gap-2">
                                <span class="text-gray-600">{{ __('teacher.student_profile.education_level_label') }}</span>
                                <span class="font-medium text-end">{{ $student->studentProfile->gradeLevel?->getDisplayName() ?? __('teacher.student_profile.not_specified') }}</span>
                            </div>
                        @endif
                        @if($student->studentProfile->gender)
                            <div class="flex justify-between gap-2">
                                <span class="text-gray-600">{{ __('teacher.student_profile.gender_label') }}</span>
                                <span class="font-medium">{{ $student->studentProfile->gender === 'male' ? __('teacher.student_profile.gender_male') : __('teacher.student_profile.gender_female') }}</span>
                            </div>
                        @endif
                        @if($student->studentProfile->nationality)
                            <div class="flex justify-between gap-2">
                                <span class="text-gray-600">{{ __('teacher.student_profile.nationality_label') }}</span>
                                <span class="font-medium">{{ \App\Helpers\CountryList::getLabel($student->studentProfile->nationality) }}</span>
                            </div>
                        @endif
                        @if($student->studentProfile->enrollment_date)
                            <div class="flex justify-between gap-2">
                                <span class="text-gray-600">{{ __('teacher.student_profile.enrollment_date_label') }}</span>
                                <span class="font-medium">{{ $student->studentProfile->enrollment_date->format('Y-m-d') }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            <!-- Recent Activity -->
            @if(count($progressData['recentActivity']) > 0)
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 md:p-6">
                    <h3 class="text-base md:text-lg font-semibold text-gray-900 mb-3 md:mb-4">{{ __('teacher.student_profile.recent_activity') }}</h3>
                    <div class="space-y-3">
                        @foreach(array_slice($progressData['recentActivity'], 0, 5) as $activity)
                            <div class="flex items-start gap-3">
                                <div class="w-2 h-2 rounded-full bg-green-500 mt-1.5 flex-shrink-0"></div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 truncate">{{ $activity['title'] }}</p>
                                    <p class="text-xs text-gray-500 truncate">{{ $activity['circle_name'] }}</p>
                                    <p class="text-xs text-gray-400">{{ $activity['date']->diffForHumans() }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif


        </div>
    </div>
</div>

</x-layouts.teacher>
