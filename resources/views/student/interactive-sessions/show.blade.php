<x-layouts.student-layout>
    {{-- Breadcrumb Navigation --}}
    <nav class="mb-6 text-sm breadcrumbs">
        <ul class="flex items-center space-x-2 text-gray-600">
            <li>
                <a href="{{ route('interactive-courses.index', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy']) }}" class="hover:text-primary-600 transition">
                    <i class="ri-book-2-line"></i> My Courses
                </a>
            </li>
            <li class="text-gray-400">/</li>
            <li>
                <a href="{{ route('interactive-courses.show', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'courseId' => $session->course->id]) }}" class="hover:text-primary-600 transition">
                    {{ $session->course->title }}
                </a>
            </li>
            <li class="text-gray-400">/</li>
            <li class="text-gray-900 font-medium">
                Session {{ $session->session_number }}
            </li>
        </ul>
    </nav>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main Content --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- Session Header Component --}}
            <x-sessions.session-header
                :session="$session"
                viewType="student"
            />

            {{-- LiveKit Meeting Interface (if session is active or joinable) --}}
            @php
                $now = nowInAcademyTimezone();
                $scheduledDateTime = toAcademyTimezone($session->scheduled_at);
                $tenMinutesBefore = $session->scheduled_at->copy()->subMinutes(10);
                $canJoin = $session->status === \App\Enums\SessionStatus::ONGOING ||
                           ($session->status === \App\Enums\SessionStatus::SCHEDULED && now()->gte($tenMinutesBefore) && now()->lte($session->scheduled_at->copy()->addMinutes($session->duration_minutes)));
            @endphp

            @if($canJoin && $session->meeting)
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
                        <i class="ri-vidicon-line text-primary-600 mr-2"></i>
                        Live Session
                    </h2>

                    <x-meetings.livekit-interface
                        :meeting="$session->meeting"
                        :session="$session"
                        participantName="{{ Auth::user()->name }}"
                        participantType="student"
                    />
                </div>
            @elseif($session->status === \App\Enums\SessionStatus::SCHEDULED)
                <div class="bg-blue-50 border border-blue-200 rounded-xl p-6">
                    <div class="flex items-start">
                        <i class="ri-time-line text-blue-600 text-3xl mr-4"></i>
                        <div>
                            <h3 class="text-lg font-semibold text-blue-900 mb-2">Session Starting Soon</h3>
                            <p class="text-blue-700 mb-2">
                                This session is scheduled for {{ formatDateTimeArabic($session->scheduled_at) }}
                            </p>
                            <p class="text-blue-600 text-sm">
                                You can join 10 minutes before the start time.
                            </p>
                            @if($scheduledDateTime->gt($now))
                                <p class="text-blue-800 font-medium mt-3">
                                    <i class="ri-timer-line mr-1"></i>
                                    Starts {{ $scheduledDateTime->diffForHumans() }}
                                </p>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            {{-- Session Content --}}
            @if($session->lesson_content)
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
                        <i class="ri-file-text-line text-primary-600 mr-2"></i>
                        محتوى الجلسة
                    </h2>

                    <div class="prose max-w-none text-gray-700 leading-relaxed bg-gray-50 rounded-lg p-4">
                        {!! nl2br(e($session->lesson_content)) !!}
                    </div>
                </div>
            @endif

            {{-- Homework Section --}}
            @if($session->homework && $session->homework->count() > 0)
                @php
                    $homework = $session->homework->first();
                @endphp
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6" id="homework-section">
                    <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
                        <i class="ri-file-list-3-line text-purple-600 mr-2"></i>
                        Homework Assignment
                    </h2>

                    <x-sessions.homework-display
                        :homework="$homework"
                        :submission="$homeworkSubmission"
                        :session="$session"
                    />
                </div>
            @endif

            {{-- Student Feedback Form (after session completion) --}}
            @if($session->status === \App\Enums\SessionStatus::COMPLETED)
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
                        <i class="ri-feedback-line text-green-600 mr-2"></i>
                        Session Feedback
                    </h2>

                    <form method="POST" action="{{ route('student.interactive-sessions.feedback', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'session' => $session->id]) }}" class="space-y-4">
                        @csrf

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                How would you rate this session?
                            </label>
                            <div class="flex items-center space-x-2">
                                @for($i = 1; $i <= 5; $i++)
                                    <label class="cursor-pointer">
                                        <input type="radio" name="rating" value="{{ $i }}" required class="sr-only peer">
                                        <i class="ri-star-fill text-3xl text-gray-300 peer-checked:text-yellow-400 hover:text-yellow-300 transition"></i>
                                    </label>
                                @endfor
                            </div>
                        </div>

                        <div>
                            <label for="feedback_text" class="block text-sm font-medium text-gray-700 mb-2">
                                Your Feedback (Optional)
                            </label>
                            <textarea
                                id="feedback_text"
                                name="feedback_text"
                                rows="4"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition"
                                placeholder="Share your thoughts about this session..."
                            ></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary w-full sm:w-auto">
                            <i class="ri-send-plane-fill mr-2"></i>
                            Submit Feedback
                        </button>
                    </form>
                </div>
            @endif

        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            <x-interactive.session-info-sidebar
                :session="$session"
                :attendance="$attendance"
            />

            <x-circle.quick-actions
                :circle="$session->course"
                type="group"
                view-type="student"
                context="interactive"
                :is-enrolled="true"
            />
        </div>
    </div>
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-scroll to meeting section when session is about to start (within 5 minutes)
    @if($session->scheduled_at && $session->scheduled_at->diffInMinutes(now()) <= 5 && $session->scheduled_at->diffInMinutes(now()) >= -5)
        setTimeout(() => {
            const meetingContainer = document.getElementById('meetingContainer');
            if (meetingContainer) {
                meetingContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }, 1000);
    @endif
    // Note: "Session starting soon" notification is centralized in livekit-interface component
});
</script>
@endpush
</x-layouts.student-layout>
