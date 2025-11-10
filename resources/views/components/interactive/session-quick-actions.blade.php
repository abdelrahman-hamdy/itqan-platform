@props(['session', 'canJoin' => false])

<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
    <h3 class="font-bold text-lg text-gray-900 mb-4 flex items-center">
        <i class="ri-flashlight-line text-primary-600 mr-2"></i>
        Quick Actions
    </h3>

    <div class="space-y-3">
        {{-- Join Meeting (if active/joinable) --}}
        @if($canJoin && $session->meeting)
            <a href="#meeting-interface"
               onclick="document.getElementById('meeting-interface')?.scrollIntoView({behavior: 'smooth'})"
               class="btn btn-primary w-full flex items-center justify-center group hover:shadow-lg transition-all duration-200">
                <i class="ri-vidicon-line mr-2 group-hover:scale-110 transition-transform"></i>
                Join Live Session
            </a>
        @elseif($session->status === 'scheduled')
            @php
                $scheduledDateTime = \Carbon\Carbon::parse($session->scheduled_date->format('Y-m-d') . ' ' . $session->scheduled_time->format('H:i:s'));
            @endphp
            <button disabled
                    class="btn btn-secondary w-full opacity-60 cursor-not-allowed flex items-center justify-center">
                <i class="ri-time-line mr-2"></i>
                Starts {{ $scheduledDateTime->diffForHumans() }}
            </button>
        @elseif($session->status === 'completed')
            @if($session->meeting && $session->meeting->recording_url)
                <a href="{{ $session->meeting->recording_url }}"
                   target="_blank"
                   class="btn btn-secondary w-full flex items-center justify-center hover:bg-primary-50 hover:text-primary-700 transition-all duration-200">
                    <i class="ri-play-circle-line mr-2"></i>
                    Watch Recording
                </a>
            @endif
        @endif

        {{-- Chat with Teacher --}}
        @if($session->course->teacher && $session->course->teacher->user_id)
            <a href="{{ route('chatify', ['id' => $session->course->teacher->user_id]) }}"
               class="btn btn-secondary w-full flex items-center justify-center hover:bg-blue-50 hover:text-blue-700 transition-all duration-200">
                <i class="ri-message-3-line mr-2"></i>
                Chat with Teacher
            </a>
        @endif

        {{-- View Course Details --}}
        <a href="{{ route('interactive-courses.show', $session->course) }}"
           class="btn btn-secondary w-full flex items-center justify-center hover:bg-purple-50 hover:text-purple-700 transition-all duration-200">
            <i class="ri-book-open-line mr-2"></i>
            View Course
        </a>

        {{-- Submit Homework --}}
        @if($session->homework && $session->homework->count() > 0)
            @php
                $homework = $session->homework->first();
                $hasSubmission = $homework->submissions()->where('student_id', Auth::user()->student->id)->exists();
            @endphp

            @if(!$hasSubmission)
                <a href="#homework-section"
                   onclick="document.getElementById('homework-section')?.scrollIntoView({behavior: 'smooth'})"
                   class="btn btn-accent w-full flex items-center justify-center hover:shadow-lg transition-all duration-200">
                    <i class="ri-file-upload-line mr-2"></i>
                    Submit Homework
                </a>
            @else
                <div class="flex items-center justify-center w-full px-4 py-3 bg-green-50 border border-green-200 rounded-lg text-green-700 text-sm font-medium">
                    <i class="ri-checkbox-circle-line mr-2"></i>
                    Homework Submitted
                </div>
            @endif
        @endif

        {{-- Download Materials (if available) --}}
        @if($session->materials_uploaded)
            <a href="#session-materials"
               class="btn btn-secondary w-full flex items-center justify-center hover:bg-indigo-50 hover:text-indigo-700 transition-all duration-200">
                <i class="ri-download-cloud-line mr-2"></i>
                Download Materials
            </a>
        @endif
    </div>

    {{-- Session Status Info --}}
    <div class="mt-6 pt-6 border-t border-gray-200">
        <div class="text-center">
            @if($session->status === 'scheduled')
                <div class="text-sm text-gray-600">
                    <i class="ri-information-line text-blue-500"></i>
                    Session not started yet
                </div>
            @elseif($session->status === 'in-progress')
                <div class="text-sm text-green-600 font-medium animate-pulse">
                    <i class="ri-radio-button-line"></i>
                    Session is currently live
                </div>
            @elseif($session->status === 'completed')
                <div class="text-sm text-gray-600">
                    <i class="ri-check-double-line text-green-500"></i>
                    Session completed
                </div>
            @elseif($session->status === 'cancelled')
                <div class="text-sm text-red-600">
                    <i class="ri-close-circle-line"></i>
                    Session cancelled
                </div>
            @endif
        </div>
    </div>
</div>
