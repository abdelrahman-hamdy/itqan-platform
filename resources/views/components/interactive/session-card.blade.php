@props(['session', 'attendance' => null])

@php
    use App\Enums\SessionStatus;
    $statusColors = [
        SessionStatus::SCHEDULED->value => 'border-blue-300 bg-blue-50',
        SessionStatus::ONGOING->value => 'border-green-400 bg-green-50',
        SessionStatus::COMPLETED->value => 'border-gray-300 bg-gray-50',
        SessionStatus::CANCELLED->value => 'border-red-300 bg-red-50'
    ];
    $statusValue = $session->status instanceof SessionStatus ? $session->status->value : $session->status;
    $cardClass = $statusColors[$statusValue] ?? 'border-gray-300';

    $scheduledDateTime = $session->scheduled_at;

    // Check if homework exists
    $hasHomework = $session->homework && $session->homework->count() > 0;
    $homeworkSubmitted = false;
    if ($hasHomework && Auth::user()->student) {
        $homework = $session->homework->first();
        $homeworkSubmitted = $homework->submissions()->where('student_id', Auth::user()->student->id)->exists();
    }

    // Determine the correct route based on user role
    $isTeacher = Auth::user() && Auth::user()->isAcademicTeacher();
    $sessionRouteName = $isTeacher ? 'teacher.interactive-sessions.show' : 'student.interactive-sessions.show';
@endphp

<div class="border-2 {{ $cardClass }} rounded-xl p-5 hover:shadow-lg transition-all duration-300 group">
    <div class="flex items-start justify-between gap-4">
        <div class="flex-1 min-w-0">
            {{-- Session Number & Title --}}
            <div class="flex items-center gap-3 mb-2 flex-wrap">
                <span class="text-sm font-semibold text-gray-500 bg-white px-3 py-1 rounded-full border border-gray-200">
                    Session {{ $session->session_number }}
                </span>

                @if($session->status === SessionStatus::ONGOING)
                    <span class="flex items-center text-xs bg-green-500 text-white px-3 py-1 rounded-full animate-pulse shadow-lg">
                        <span class="w-2 h-2 bg-white rounded-full mr-2 animate-ping"></span>
                        <span class="font-bold">LIVE NOW</span>
                    </span>
                @endif
            </div>

            <h4 class="font-bold text-lg text-gray-900 mb-3 group-hover:text-primary-600 transition-colors line-clamp-2">
                {{ $session->title ?? 'Interactive Session' }}
            </h4>

            {{-- Date & Time --}}
            <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-4 mb-3">
                <div class="flex items-center text-sm text-gray-600">
                    <i class="ri-calendar-line mr-2 text-primary-500"></i>
                    <span class="font-medium">{{ $scheduledDateTime->format('M d, Y') }}</span>
                </div>
                <div class="flex items-center text-sm text-gray-600">
                    <i class="ri-time-line mr-2 text-primary-500"></i>
                    <span class="font-medium">{{ $scheduledDateTime->format('g:i A') }}</span>
                </div>
                <div class="flex items-center text-sm text-gray-600">
                    <i class="ri-hourglass-line mr-2 text-primary-500"></i>
                    <span class="font-medium">{{ $session->duration_minutes }} min</span>
                </div>
            </div>

            {{-- Badges --}}
            <div class="flex flex-wrap gap-2">
                {{-- Status Badge --}}
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium border
                    {{ $session->status === SessionStatus::COMPLETED ? 'bg-gray-100 text-gray-700 border-gray-300' :
                       ($session->status === SessionStatus::ONGOING ? 'bg-green-100 text-green-700 border-green-300' :
                       ($session->status === SessionStatus::CANCELLED ? 'bg-red-100 text-red-700 border-red-300' :
                       'bg-blue-100 text-blue-700 border-blue-300')) }}">
                    <i class="mr-1 {{ $session->status === SessionStatus::COMPLETED ? 'ri-check-line' :
                                      ($session->status === SessionStatus::ONGOING ? 'ri-radio-button-line' :
                                      ($session->status === SessionStatus::CANCELLED ? 'ri-close-line' : 'ri-calendar-event-line')) }}"></i>
                    {{ ucfirst($statusValue) }}
                </span>

                {{-- Attendance Badge --}}
                @if($attendance)
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium border
                        {{ $attendance->status === 'present' ? 'bg-green-100 text-green-700 border-green-300' :
                           ($attendance->status === 'late' ? 'bg-yellow-100 text-yellow-700 border-yellow-300' :
                           'bg-red-100 text-red-700 border-red-300') }}">
                        <i class="ri-user-follow-line mr-1"></i>
                        {{ ucfirst($attendance->status) }}
                    </span>
                @endif

                {{-- Homework Badge --}}
                @if($hasHomework)
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium border
                        {{ $homeworkSubmitted ? 'bg-green-100 text-green-700 border-green-300' : 'bg-purple-100 text-purple-700 border-purple-300' }}">
                        <i class="{{ $homeworkSubmitted ? 'ri-checkbox-circle-line' : 'ri-file-text-line' }} mr-1"></i>
                        {{ $homeworkSubmitted ? 'Homework Submitted' : 'Homework Assigned' }}
                    </span>
                @endif
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex flex-col gap-2 flex-shrink-0">
            @if($session->status === SessionStatus::ONGOING)
                <a href="{{ route($sessionRouteName, ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'session' => $session->id]) }}"
                   class="btn btn-sm btn-primary px-4 py-2 whitespace-nowrap shadow-md hover:shadow-lg transform hover:-translate-y-0.5 transition-all duration-200">
                    <i class="ri-vidicon-line mr-1"></i>
                    Join Now
                </a>
            @elseif($session->status === SessionStatus::SCHEDULED)
                <a href="{{ route($sessionRouteName, ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'session' => $session->id]) }}"
                   class="btn btn-sm btn-secondary px-4 py-2 whitespace-nowrap hover:bg-primary-50 hover:text-primary-700 hover:border-primary-300 transition-all duration-200">
                    <i class="ri-eye-line mr-1"></i>
                    View Details
                </a>
            @elseif($session->status === SessionStatus::COMPLETED)
                <a href="{{ route($sessionRouteName, ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'session' => $session->id]) }}"
                   class="btn btn-sm btn-secondary px-4 py-2 whitespace-nowrap hover:bg-gray-100 transition-all duration-200">
                    <i class="ri-history-line mr-1"></i>
                    Review
                </a>
            @else
                <a href="{{ route($sessionRouteName, ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'session' => $session->id]) }}"
                   class="btn btn-sm btn-secondary px-4 py-2 whitespace-nowrap transition-all duration-200">
                    <i class="ri-information-line mr-1"></i>
                    Details
                </a>
            @endif
        </div>
    </div>

    {{-- Quick Info (shown on hover for completed sessions) --}}
    @if($session->status === SessionStatus::COMPLETED && $session->description)
        <div class="mt-4 pt-4 border-t border-gray-200 hidden group-hover:block transition-all duration-300">
            <p class="text-sm text-gray-600 line-clamp-2">
                {{ Str::limit($session->description, 120) }}
            </p>
        </div>
    @endif
</div>
