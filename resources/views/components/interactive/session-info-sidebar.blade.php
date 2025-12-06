@props(['session', 'attendance' => null])

<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
    <h3 class="font-bold text-lg text-gray-900 mb-4 flex items-center">
        <i class="ri-information-line text-primary-600 mr-2"></i>
        Session Information
    </h3>

    {{-- Course Info --}}
    <div class="mb-6">
        @if($session->course->thumbnail)
            <img src="{{ Storage::url($session->course->thumbnail) }}"
                 alt="{{ $session->course->title }}"
                 class="w-full h-32 object-cover rounded-lg mb-3 shadow-sm">
        @else
            <div class="w-full h-32 bg-gradient-to-br from-primary-400 to-primary-600 rounded-lg mb-3 flex items-center justify-center">
                <i class="ri-book-open-line text-white text-4xl"></i>
            </div>
        @endif

        <h4 class="font-semibold text-gray-900 mb-1 leading-tight">
            {{ $session->course->title }}
        </h4>
        <p class="text-sm text-gray-600">
            Session {{ $session->session_number }} @if($session->course->total_sessions) of {{ $session->course->total_sessions }} @endif
        </p>
    </div>

    {{-- Progress Bar --}}
    @if($session->course->total_sessions)
        @php
            $progress = ($session->session_number / $session->course->total_sessions) * 100;
        @endphp
        <div class="mb-6">
            <div class="flex justify-between text-sm mb-2">
                <span class="text-gray-700 font-medium">Course Progress</span>
                <span class="text-primary-600 font-bold">{{ round($progress) }}%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2.5 overflow-hidden">
                <div class="bg-gradient-to-r from-primary-500 to-primary-600 h-2.5 rounded-full transition-all duration-500"
                     style="width: {{ $progress }}%"></div>
            </div>
        </div>
    @endif

    {{-- Attendance Status --}}
    <div class="mb-6 pb-6 border-b border-gray-200">
        <h4 class="font-semibold text-sm text-gray-700 mb-2 flex items-center">
            <i class="ri-user-follow-line text-gray-500 mr-1"></i>
            Attendance
        </h4>
        @if($attendance)
            <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-medium
                {{ $attendance->status === 'present' ? 'bg-green-100 text-green-800 border border-green-200' :
                   ($attendance->status === 'late' ? 'bg-yellow-100 text-yellow-800 border border-yellow-200' :
                   'bg-red-100 text-red-800 border border-red-200') }}">
                <i class="{{ $attendance->status === 'present' ? 'ri-checkbox-circle-line' :
                           ($attendance->status === 'late' ? 'ri-time-line' : 'ri-close-circle-line') }} mr-1"></i>
                {{ ucfirst($attendance->status) }}
            </span>

            @if($attendance->duration_minutes)
                <p class="text-sm text-gray-600 mt-2">
                    Duration: {{ $attendance->duration_minutes }} minutes
                </p>
            @endif
        @else
            <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm bg-gray-100 text-gray-600 border border-gray-200">
                <i class="ri-time-line mr-1"></i>
                Not marked yet
            </span>
        @endif
    </div>

    {{-- Quick Stats --}}
    <div class="space-y-3">
        <div class="flex items-center justify-between text-sm">
            <span class="text-gray-600 flex items-center">
                <i class="ri-user-line text-gray-400 mr-2"></i>
                Teacher
            </span>
            <span class="font-medium text-gray-900 text-right">
                {{ $session->course->assignedTeacher->user->name ?? 'N/A' }}
            </span>
        </div>

        <div class="flex items-center justify-between text-sm">
            <span class="text-gray-600 flex items-center">
                <i class="ri-time-line text-gray-400 mr-2"></i>
                Duration
            </span>
            <span class="font-medium text-gray-900">
                {{ $session->duration_minutes }} minutes
            </span>
        </div>

        <div class="flex items-center justify-between text-sm">
            <span class="text-gray-600 flex items-center">
                <i class="ri-calendar-line text-gray-400 mr-2"></i>
                Date
            </span>
            <span class="font-medium text-gray-900 text-right">
                {{ formatDateArabic($session->scheduled_at, 'M d, Y') }}
            </span>
        </div>

        <div class="flex items-center justify-between text-sm">
            <span class="text-gray-600 flex items-center">
                <i class="ri-time-fill text-gray-400 mr-2"></i>
                Time
            </span>
            <span class="font-medium text-gray-900">
                {{ formatTimeArabic($session->scheduled_at) }}
            </span>
        </div>

        @if($session->course->subject)
            <div class="flex items-center justify-between text-sm">
                <span class="text-gray-600 flex items-center">
                    <i class="ri-book-2-line text-gray-400 mr-2"></i>
                    Subject
                </span>
                <span class="font-medium text-gray-900 text-right">
                    {{ $session->course->subject->name }}
                </span>
            </div>
        @endif

        @if($session->course->gradeLevel)
            <div class="flex items-center justify-between text-sm">
                <span class="text-gray-600 flex items-center">
                    <i class="ri-award-line text-gray-400 mr-2"></i>
                    Grade
                </span>
                <span class="font-medium text-gray-900 text-right">
                    {{ $session->course->gradeLevel->name }}
                </span>
            </div>
        @endif
    </div>
</div>
