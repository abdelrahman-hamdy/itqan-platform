@php
use App\Enums\SessionStatus;
use App\Enums\AttendanceStatus;

    $subdomain = request()->route('subdomain') ?? auth()->user()->academy?->subdomain ?? 'itqan-academy';
@endphp

<x-layouts.parent-layout :title="__('parent.sessions.title')">
    <div class="space-y-4 md:space-y-6">
        <!-- Back Button -->
        <div>
            <a href="{{ url()->previous() }}" class="min-h-[44px] inline-flex items-center text-blue-600 hover:text-blue-700 font-bold text-sm md:text-base">
                <i class="ri-arrow-right-line rtl:ri-arrow-right-line ltr:ri-arrow-left-line ms-1.5 md:ms-2 rtl:ms-1.5 rtl:md:ms-2 ltr:me-1.5 ltr:md:me-2"></i>
                {{ __('parent.sessions.back') }}
            </a>
        </div>

        <!-- Session Header -->
        <div class="bg-white rounded-lg md:rounded-xl shadow p-4 md:p-6">
            <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-3 md:gap-4">
                <div class="flex items-start gap-3 md:gap-4">
                    @if($session instanceof \App\Models\QuranSession)
                        <div class="bg-green-100 rounded-lg p-3 md:p-4 flex-shrink-0">
                            <i class="ri-book-read-line text-2xl md:text-3xl text-green-600"></i>
                        </div>
                    @else
                        <div class="bg-blue-100 rounded-lg p-3 md:p-4 flex-shrink-0">
                            <i class="ri-book-2-line text-2xl md:text-3xl text-blue-600"></i>
                        </div>
                    @endif
                    <div class="min-w-0">
                        <h1 class="text-lg sm:text-xl md:text-3xl font-bold text-gray-900">
                            @if($session instanceof \App\Models\QuranSession)
                                {{ __('parent.sessions.quran_session_type', ['type' => $session->subscription_type === 'individual' ? __('parent.sessions.individual') : __('parent.sessions.group_circle')]) }}
                            @else
                                {{ __('parent.sessions.academic_lesson_subject', ['subject' => $session->subject_name ?? __('parent.sessions.subject')]) }}
                            @endif
                        </h1>
                        @if(!($session instanceof \App\Models\QuranSession))
                            <p class="text-sm md:text-base text-gray-600 mt-0.5 md:mt-1">{{ $session->grade_level_name ?? __('parent.sessions.level') }}</p>
                        @endif
                    </div>
                </div>
                <span class="self-start px-3 md:px-4 py-1.5 md:py-2 text-xs md:text-sm font-bold rounded-full flex-shrink-0
                    {{ $session->status === SessionStatus::SCHEDULED->value ? 'bg-blue-100 text-blue-800' : '' }}
                    {{ $session->status === SessionStatus::ONGOING->value ? 'bg-green-100 text-green-800' : '' }}
                    {{ $session->status === SessionStatus::COMPLETED->value ? 'bg-gray-100 text-gray-800' : '' }}
                    {{ $session->status === SessionStatus::CANCELLED->value ? 'bg-red-100 text-red-800' : '' }}">
                    {{ $session->status === SessionStatus::SCHEDULED->value ? __('parent.sessions.status.scheduled') : ($session->status === SessionStatus::ONGOING->value ? __('parent.sessions.status.ongoing') : ($session->status === SessionStatus::COMPLETED->value ? __('parent.sessions.status.completed') : __('parent.sessions.status.cancelled'))) }}
                </span>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 md:gap-6">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-4 md:space-y-6">
                <!-- Session Information -->
                <div class="bg-white rounded-lg md:rounded-xl shadow">
                    <div class="p-4 md:p-6 border-b border-gray-200">
                        <h2 class="text-base md:text-xl font-bold text-gray-900">{{ __('parent.sessions.session_info') }}</h2>
                    </div>
                    <div class="p-4 md:p-6 space-y-3 md:space-y-4">
                        <!-- Date & Time -->
                        <div class="flex items-center gap-2.5 md:gap-3">
                            <div class="bg-blue-100 rounded-lg p-2.5 md:p-3 flex-shrink-0">
                                <i class="ri-calendar-line text-lg md:text-xl text-blue-600"></i>
                            </div>
                            <div class="min-w-0">
                                <p class="text-xs md:text-sm text-gray-500">{{ __('parent.sessions.date_time') }}</p>
                                <p class="font-bold text-sm md:text-base text-gray-900">{{ formatDateTimeArabic($session->scheduled_at) }}</p>
                            </div>
                        </div>

                        <!-- Duration -->
                        <div class="flex items-center gap-2.5 md:gap-3">
                            <div class="bg-purple-100 rounded-lg p-2.5 md:p-3 flex-shrink-0">
                                <i class="ri-time-line text-lg md:text-xl text-purple-600"></i>
                            </div>
                            <div class="min-w-0">
                                <p class="text-xs md:text-sm text-gray-500">{{ __('parent.sessions.duration') }}</p>
                                <p class="font-bold text-sm md:text-base text-gray-900">{{ $session->duration_minutes }} {{ __('parent.sessions.duration_minutes') }}</p>
                                @if($session->status === SessionStatus::COMPLETED->value && $session->actual_duration_minutes)
                                    <p class="text-xs md:text-sm text-gray-600">{{ __('parent.sessions.actual_duration', ['minutes' => $session->actual_duration_minutes]) }}</p>
                                @endif
                            </div>
                        </div>

                        <!-- Teacher -->
                        <div class="flex items-center gap-2.5 md:gap-3">
                            <div class="bg-green-100 rounded-lg p-2.5 md:p-3 flex-shrink-0">
                                <i class="ri-user-line text-lg md:text-xl text-green-600"></i>
                            </div>
                            <div class="min-w-0">
                                <p class="text-xs md:text-sm text-gray-500">{{ __('parent.sessions.teacher') }}</p>
                                <p class="font-bold text-sm md:text-base text-gray-900 truncate">
                                    @if($session instanceof \App\Models\QuranSession)
                                        {{ $session->quranTeacher->user->name }}
                                    @else
                                        {{ $session->academicTeacher->user->name }}
                                    @endif
                                </p>
                            </div>
                        </div>

                        <!-- Student -->
                        <div class="flex items-center gap-2.5 md:gap-3">
                            <div class="bg-yellow-100 rounded-lg p-2.5 md:p-3 flex-shrink-0">
                                <i class="ri-user-smile-line text-lg md:text-xl text-yellow-600"></i>
                            </div>
                            <div class="min-w-0">
                                <p class="text-xs md:text-sm text-gray-500">{{ __('parent.sessions.student') }}</p>
                                <p class="font-bold text-sm md:text-base text-gray-900 truncate">{{ $session->student->name ?? '-' }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quran-specific Content -->
                @if($session instanceof \App\Models\QuranSession && $session->status === SessionStatus::COMPLETED->value)
                    <div class="bg-white rounded-lg md:rounded-xl shadow">
                        <div class="p-4 md:p-6 border-b border-gray-200">
                            <h2 class="text-base md:text-xl font-bold text-gray-900">{{ __('parent.sessions.quran_details_title') }}</h2>
                        </div>
                        <div class="p-4 md:p-6">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 md:gap-4">
                                @if($session->pages_memorized_from || $session->pages_memorized_to)
                                    <div class="p-3 md:p-4 bg-green-50 rounded-lg">
                                        <p class="text-xs md:text-sm text-gray-600 mb-0.5 md:mb-1">{{ __('parent.sessions.new_memorization') }}</p>
                                        <p class="font-bold text-sm md:text-base text-gray-900">
                                            {{ __('parent.sessions.page_from_to', ['from' => $session->pages_memorized_from ?? '-', 'to' => $session->pages_memorized_to ?? '-']) }}
                                        </p>
                                    </div>
                                @endif

                                @if($session->pages_reviewed_from || $session->pages_reviewed_to)
                                    <div class="p-3 md:p-4 bg-blue-50 rounded-lg">
                                        <p class="text-xs md:text-sm text-gray-600 mb-0.5 md:mb-1">{{ __('parent.sessions.review') }}</p>
                                        <p class="font-bold text-sm md:text-base text-gray-900">
                                            {{ __('parent.sessions.page_from_to', ['from' => $session->pages_reviewed_from ?? '-', 'to' => $session->pages_reviewed_to ?? '-']) }}
                                        </p>
                                    </div>
                                @endif

                                @if($session->tajweed_score)
                                    <div class="p-3 md:p-4 bg-purple-50 rounded-lg">
                                        <p class="text-xs md:text-sm text-gray-600 mb-0.5 md:mb-1">{{ __('parent.sessions.tajweed_score') }}</p>
                                        <p class="font-bold text-sm md:text-base text-gray-900">{{ $session->tajweed_score }}/10</p>
                                    </div>
                                @endif

                                @if($session->memorization_quality_score)
                                    <div class="p-3 md:p-4 bg-yellow-50 rounded-lg">
                                        <p class="text-xs md:text-sm text-gray-600 mb-0.5 md:mb-1">{{ __('parent.sessions.memorization_quality') }}</p>
                                        <p class="font-bold text-sm md:text-base text-gray-900">{{ $session->memorization_quality_score }}/10</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Academic-specific Content -->
                @if(!($session instanceof \App\Models\QuranSession) && $session->status === SessionStatus::COMPLETED->value)
                    @if($session->lesson_topic || $session->learning_outcomes)
                        <div class="bg-white rounded-lg md:rounded-xl shadow">
                            <div class="p-4 md:p-6 border-b border-gray-200">
                                <h2 class="text-base md:text-xl font-bold text-gray-900">{{ __('parent.sessions.lesson_content_title') }}</h2>
                            </div>
                            <div class="p-4 md:p-6 space-y-3 md:space-y-4">
                                @if($session->lesson_topic)
                                    <div>
                                        <p class="text-xs md:text-sm font-bold text-gray-700 mb-1 md:mb-2">{{ __('parent.sessions.lesson_topic') }}</p>
                                        <p class="text-sm md:text-base text-gray-900">{{ $session->lesson_topic }}</p>
                                    </div>
                                @endif

                                @if($session->learning_outcomes)
                                    <div>
                                        <p class="text-xs md:text-sm font-bold text-gray-700 mb-1 md:mb-2">{{ __('parent.sessions.learning_outcomes') }}</p>
                                        <p class="text-sm md:text-base text-gray-900">{{ $session->learning_outcomes }}</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif

                    @if($session->homework_description)
                        <div class="bg-white rounded-lg md:rounded-xl shadow">
                            <div class="p-4 md:p-6 border-b border-gray-200">
                                <h2 class="text-base md:text-xl font-bold text-gray-900">{{ __('parent.sessions.homework_title') }}</h2>
                            </div>
                            <div class="p-4 md:p-6">
                                <p class="text-sm md:text-base text-gray-900">{{ $session->homework_description }}</p>
                                @if($session->homework_file)
                                    <a href="{{ Storage::url($session->homework_file) }}" target="_blank" class="min-h-[44px] inline-flex items-center mt-2 md:mt-3 text-sm md:text-base text-blue-600 hover:text-blue-700">
                                        <i class="ri-download-line me-1"></i>
                                        {{ __('parent.sessions.download_attachment') }}
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endif
                @endif

                <!-- Teacher Notes -->
                @if($session->teacher_notes && $session->status === SessionStatus::COMPLETED->value)
                    <div class="bg-white rounded-lg md:rounded-xl shadow">
                        <div class="p-4 md:p-6 border-b border-gray-200">
                            <h2 class="text-base md:text-xl font-bold text-gray-900">{{ __('parent.sessions.teacher_notes_title') }}</h2>
                        </div>
                        <div class="p-4 md:p-6">
                            <p class="text-sm md:text-base text-gray-900 whitespace-pre-line">{{ $session->teacher_notes }}</p>
                        </div>
                    </div>
                @endif

                <!-- Cancellation Reason -->
                @if($session->status === SessionStatus::CANCELLED->value && $session->cancellation_reason)
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4 md:p-6">
                        <div class="flex items-start gap-2.5 md:gap-3">
                            <i class="ri-error-warning-line text-xl md:text-2xl text-red-600 flex-shrink-0"></i>
                            <div class="min-w-0">
                                <p class="font-bold text-sm md:text-base text-red-900 mb-0.5 md:mb-1">{{ __('parent.sessions.cancellation_reason') }}</p>
                                <p class="text-sm md:text-base text-red-800">{{ $session->cancellation_reason }}</p>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Sidebar -->
            <div class="space-y-4 md:space-y-6">
                <!-- Attendance Info -->
                @if($attendance)
                    <div class="bg-white rounded-lg md:rounded-xl shadow">
                        <div class="p-4 md:p-6 border-b border-gray-200">
                            <h3 class="text-sm md:text-lg font-bold text-gray-900">{{ __('parent.sessions.attendance_status_title') }}</h3>
                        </div>
                        <div class="p-4 md:p-6 space-y-3 md:space-y-4">
                            <div class="text-center p-3 md:p-4 rounded-lg {{ $attendance->status === AttendanceStatus::ATTENDED->value ? 'bg-green-100' : ($attendance->status === AttendanceStatus::ABSENT->value ? 'bg-red-100' : ($attendance->status === AttendanceStatus::LEFT->value ? 'bg-orange-100' : 'bg-yellow-100')) }}">
                                <i class="ri-user-{{ $attendance->status === AttendanceStatus::ATTENDED->value ? 'check' : ($attendance->status === AttendanceStatus::ABSENT->value ? 'unfollow' : ($attendance->status === AttendanceStatus::LEFT->value ? 'minus' : 'clock')) }}-line text-3xl md:text-4xl mb-1.5 md:mb-2 {{ $attendance->status === AttendanceStatus::ATTENDED->value ? 'text-green-600' : ($attendance->status === AttendanceStatus::ABSENT->value ? 'text-red-600' : ($attendance->status === AttendanceStatus::LEFT->value ? 'text-orange-600' : 'text-yellow-600')) }}"></i>
                                <p class="font-bold text-sm md:text-lg {{ $attendance->status === AttendanceStatus::ATTENDED->value ? 'text-green-900' : ($attendance->status === AttendanceStatus::ABSENT->value ? 'text-red-900' : ($attendance->status === AttendanceStatus::LEFT->value ? 'text-orange-900' : 'text-yellow-900')) }}">
                                    {{ $attendance->status === AttendanceStatus::ATTENDED->value ? __('parent.sessions.attendance_status.attended') : ($attendance->status === AttendanceStatus::ABSENT->value ? __('parent.sessions.attendance_status.absent') : ($attendance->status === AttendanceStatus::LEFT->value ? __('parent.sessions.attendance_status.left_early') : __('parent.sessions.attendance_status.late'))) }}
                                </p>
                            </div>

                            @if($attendance->attended_at)
                                <div class="flex items-center justify-between text-xs md:text-sm">
                                    <span class="text-gray-600">{{ __('parent.sessions.entry_time') }}</span>
                                    <span class="font-bold text-gray-900">{{ $attendance->attended_at->format('h:i A') }}</span>
                                </div>
                            @endif

                            @if($attendance->left_at)
                                <div class="flex items-center justify-between text-xs md:text-sm">
                                    <span class="text-gray-600">{{ __('parent.sessions.exit_time') }}</span>
                                    <span class="font-bold text-gray-900">{{ $attendance->left_at->format('h:i A') }}</span>
                                </div>
                            @endif

                            @if($attendance->duration_minutes)
                                <div class="flex items-center justify-between text-xs md:text-sm">
                                    <span class="text-gray-600">{{ __('parent.sessions.attendance_duration') }}</span>
                                    <span class="font-bold text-gray-900">{{ $attendance->duration_minutes }} {{ __('parent.sessions.duration_minutes') }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                <!-- Quick Stats -->
                <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg md:rounded-xl shadow-lg p-4 md:p-6 text-white">
                    <h3 class="text-sm md:text-lg font-bold mb-3 md:mb-4">{{ __('parent.sessions.quick_stats_title') }}</h3>
                    <div class="space-y-2 md:space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs md:text-sm text-blue-100">{{ __('parent.sessions.total_sessions_count') }}</span>
                            <span class="font-bold text-xl md:text-2xl">{{ $stats['total_sessions'] }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs md:text-sm text-blue-100">{{ __('parent.sessions.completed_sessions_count') }}</span>
                            <span class="font-bold text-xl md:text-2xl">{{ $stats['completed_sessions'] }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs md:text-sm text-blue-100">{{ __('parent.sessions.attendance_rate') }}</span>
                            <span class="font-bold text-xl md:text-2xl">{{ $stats['attendance_rate'] }}%</span>
                        </div>
                    </div>
                </div>

                <!-- Related Links -->
                <div class="bg-white rounded-lg md:rounded-xl shadow p-4 md:p-6">
                    <h3 class="text-sm md:text-lg font-bold text-gray-900 mb-3 md:mb-4">{{ __('parent.sessions.related_links') }}</h3>
                    <div class="space-y-2">
                        <a href="{{ route('parent.calendar.index', ['subdomain' => $subdomain]) }}" class="min-h-[44px] flex items-center justify-between p-3 bg-gray-50 hover:bg-gray-100 rounded-lg transition-colors">
                            <div class="flex items-center gap-2">
                                <i class="ri-calendar-event-line text-blue-600"></i>
                                <span class="text-sm md:text-base text-gray-900">{{ __('parent.sessions.upcoming_sessions') }}</span>
                            </div>
                            <i class="ri-arrow-left-s-line rtl:ri-arrow-left-s-line ltr:ri-arrow-right-s-line text-gray-400"></i>
                        </a>
                        <a href="{{ route('parent.calendar.index', ['subdomain' => $subdomain]) }}" class="min-h-[44px] flex items-center justify-between p-3 bg-gray-50 hover:bg-gray-100 rounded-lg transition-colors">
                            <div class="flex items-center gap-2">
                                <i class="ri-history-line text-blue-600"></i>
                                <span class="text-sm md:text-base text-gray-900">{{ __('parent.sessions.session_history') }}</span>
                            </div>
                            <i class="ri-arrow-left-s-line rtl:ri-arrow-left-s-line ltr:ri-arrow-right-s-line text-gray-400"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.parent-layout>
