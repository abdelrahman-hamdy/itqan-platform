@extends('components.layouts.student')

@section('title', $session->title ?? 'تفاصيل الجلسة')

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

    // Show notification if session is starting soon
    @if($session->scheduled_at && $session->scheduled_at->isFuture() && $session->scheduled_at->diffInMinutes(now()) <= 10)
        @php
            $timeData = formatTimeRemaining($session->scheduled_at);
        @endphp
        @if(!$timeData['is_past'])
            showNotification('الجلسة ستبدأ خلال {{ $timeData['formatted'] }}', 'info', 8000);
        @endif
    @endif
});

function showNotification(message, type = 'info', duration = 5000) {
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg max-w-sm z-50 transform translate-x-full transition-transform duration-300`;

    const colors = {
        success: 'bg-green-500 text-white',
        error: 'bg-red-500 text-white',
        warning: 'bg-yellow-500 text-white',
        info: 'bg-blue-500 text-white'
    };

    notification.className += ` ${colors[type] || colors.info}`;

    notification.innerHTML = `
        <div class="flex items-center justify-between">
            <span>${message}</span>
            <button onclick="this.parentElement.parentElement.remove()" class="ml-2 hover:opacity-70">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;

    document.body.appendChild(notification);

    setTimeout(() => notification.classList.remove('translate-x-full'), 100);
    setTimeout(() => {
        notification.classList.add('translate-x-full');
        setTimeout(() => notification.remove(), 300);
    }, duration);
}
</script>
@endpush
