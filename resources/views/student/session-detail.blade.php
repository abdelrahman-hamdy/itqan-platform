@extends('components.layouts.student')

@section('title', $session->title ?? __('student.session_detail.title_default'))

@section('content')
    <x-sessions.quran-session-detail :session="$session" view-type="student" />
@endsection

@push('scripts')
<script>
// Student-specific functionality
document.addEventListener('DOMContentLoaded', function() {
    // Auto-scroll to meeting if session is starting soon
    @if($session->scheduled_at && $session->scheduled_at->diffInMinutes(now()) <= 5 && $session->scheduled_at->diffInMinutes(now()) >= -5)
        setTimeout(() => {
            const meetingContainer = document.getElementById('meetingContainer');
            if (meetingContainer) {
                meetingContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }, 1000);
    @endif

    // Note: "Session starting soon" notification is now centralized in livekit-interface component
});
</script>
@endpush
