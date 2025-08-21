@extends('layouts.app')

@section('title', 'جلسة ' . ($session->title ?? 'القرآن الكريم'))

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Meeting Header -->
    <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
        <div class="flex justify-between items-start">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 mb-2">{{ $session->title ?? 'جلسة القرآن الكريم' }}</h1>
                <p class="text-gray-600">{{ $session->description ?? 'جلسة تعليم القرآن الكريم' }}</p>
                <div class="mt-4 space-y-2">
                    <p class="text-sm text-gray-500">
                        <i class="fas fa-calendar mr-2"></i>
                        التاريخ: {{ $session->scheduled_at ? $session->scheduled_at->format('Y-m-d H:i') : 'غير محدد' }}
                    </p>
                    <p class="text-sm text-gray-500">
                        <i class="fas fa-clock mr-2"></i>
                        المدة: {{ $session->duration_minutes ?? 60 }} دقيقة
                    </p>
                    <p class="text-sm text-gray-500">
                        <i class="fas fa-user mr-2"></i>
                        المعلم: {{ $session->quranTeacher->full_name ?? 'غير محدد' }}
                    </p>
                </div>
            </div>
            <div class="text-right">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                    @if($session->status === 'scheduled') bg-blue-100 text-blue-800
                    @elseif($session->status === 'ongoing') bg-green-100 text-green-800
                    @elseif($session->status === 'completed') bg-gray-100 text-gray-800
                    @else bg-yellow-100 text-yellow-800 @endif">
                    {{ ucfirst($session->status->value ?? $session->status) }}
                </span>
            </div>
        </div>
    </div>

    <!-- Meeting Interface -->
    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <div class="p-4 bg-gradient-to-r from-blue-600 to-purple-600">
            <div class="flex items-center justify-between text-white">
                <div class="flex items-center">
                    <i class="fas fa-video mr-3 text-lg"></i>
                    <span class="font-semibold">جلسة مباشرة - LiveKit Meet</span>
                </div>
                <div class="flex items-center space-x-4 space-x-reverse">
                    <span class="text-sm">{{ auth()->user()->name }}</span>
                    <div class="w-3 h-3 bg-green-400 rounded-full animate-pulse"></div>
                </div>
            </div>
        </div>
        
        <!-- LiveKit Meet iframe -->
        <div id="meeting-container" style="height: 600px;">
            <iframe 
                id="meeting-frame"
                src="{{ $meetingUrl }}"
                width="100%" 
                height="100%"
                frameborder="0"
                allow="camera; microphone; display-capture; autoplay; clipboard-read; clipboard-write"
                allowfullscreen>
            </iframe>
        </div>
        
        <!-- Loading overlay -->
        <div id="loading-overlay" class="absolute inset-0 bg-gray-900 bg-opacity-75 flex items-center justify-center hidden">
            <div class="bg-white rounded-lg p-6 text-center">
                <i class="fas fa-spinner fa-spin text-3xl text-blue-600 mb-4"></i>
                <p class="text-gray-700">جاري تحضير الاجتماع...</p>
            </div>
        </div>
    </div>

    <!-- Meeting Controls -->
    <div class="mt-6 bg-white rounded-lg shadow-lg p-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4 space-x-reverse">
                <button onclick="toggleFullscreen()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-expand mr-2"></i>
                    ملء الشاشة
                </button>
                <button onclick="copyMeetingLink()" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                    <i class="fas fa-link mr-2"></i>
                    نسخ رابط الاجتماع
                </button>
            </div>
            <div class="flex items-center space-x-4 space-x-reverse">
                @if(auth()->user()->user_type === 'quran_teacher' || auth()->user()->user_type === 'academic_teacher')
                    <button onclick="endMeeting()" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                        <i class="fas fa-phone-slash mr-2"></i>
                        إنهاء الاجتماع
                    </button>
                @endif
                <a href="{{ url()->previous() }}" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                    <i class="fas fa-arrow-right mr-2"></i>
                    العودة
                </a>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('LiveKit Meet iframe initialized');
    console.log('Meeting URL:', @json($meetingUrl));
    
    // Show loading overlay initially
    const loadingOverlay = document.getElementById('loading-overlay');
    const meetingFrame = document.getElementById('meeting-frame');
    
    // Hide loading when iframe loads
    meetingFrame.addEventListener('load', function() {
        if (loadingOverlay) {
            loadingOverlay.style.display = 'none';
        }
        console.log('LiveKit Meet loaded successfully');
    });
    
    // Show loading initially
    if (loadingOverlay) {
        loadingOverlay.style.display = 'flex';
    }
});

function toggleFullscreen() {
    const container = document.getElementById('meeting-container');
    if (!document.fullscreenElement) {
        container.requestFullscreen().catch(err => {
            console.error('Error attempting to enable fullscreen:', err);
        });
    } else {
        document.exitFullscreen();
    }
}

function copyMeetingLink() {
    const meetingUrl = @json($meetingUrl);
    navigator.clipboard.writeText(meetingUrl).then(function() {
        // Show success notification
        showNotification('تم نسخ رابط الاجتماع', 'success');
    }, function(err) {
        console.error('Could not copy meeting link: ', err);
        showNotification('فشل في نسخ الرابط', 'error');
    });
}

function endMeeting() {
    if (confirm('هل أنت متأكد من إنهاء الاجتماع؟')) {
        // Add end meeting logic here if needed
        showNotification('تم إنهاء الاجتماع', 'info');
        setTimeout(() => {
            window.location.href = '{{ url()->previous() }}';
        }, 2000);
    }
}

function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg text-white z-50 transform translate-x-full transition-transform duration-300 ${
        type === 'success' ? 'bg-green-500' : 
        type === 'error' ? 'bg-red-500' : 
        type === 'warning' ? 'bg-yellow-500' : 'bg-blue-500'
    }`;
    
    notification.innerHTML = `
        <div class="flex items-center justify-between">
            <span>${message}</span>
            <button onclick="this.parentElement.parentElement.remove()" class="ml-2 text-white hover:text-gray-200">
                ×
            </button>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => notification.classList.remove('translate-x-full'), 100);
    setTimeout(() => {
        notification.classList.add('translate-x-full');
        setTimeout(() => notification.remove(), 300);
    }, 5000);
}

// Handle iframe communication if needed
window.addEventListener('message', function(event) {
    // Handle messages from LiveKit Meet if needed
    console.log('Message from iframe:', event.data);
});
</script>

<style>
#meeting-container {
    position: relative;
    background: #1a1a1a;
    border-radius: 0 0 0.5rem 0.5rem;
}

#meeting-frame {
    border-radius: 0 0 0.5rem 0.5rem;
}

#loading-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    border-radius: 0 0 0.5rem 0.5rem;
}

.notification {
    animation: slideIn 0.3s ease-out;
}

@keyframes slideIn {
    from {
        transform: translateX(100%);
    }
    to {
        transform: translateX(0);
    }
}
</style>
@endsection
