<x-layouts.student 
    :title="'تفاصيل الجلسة - ' . config('app.name', 'منصة إتقان')"
    :description="'تفاصيل جلسة القرآن الكريم'">

<div class="max-w-5xl mx-auto">
    <!-- Breadcrumb -->
    <nav class="mb-8">
        <ol class="flex items-center space-x-2 space-x-reverse text-sm text-gray-600">
            <li>
                <a href="{{ route('individual-circles.show', ['subdomain' => $academy->subdomain ?? 'itqan-academy', 'circle' => $session->individualCircle->id]) }}" 
                   class="hover:text-primary">الحلقة الفردية</a>
            </li>
            <li>/</li>
            <li class="text-gray-900">تفاصيل الجلسة</li>
        </ol>
    </nav>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Main Content -->
        <div class="lg:col-span-2">
            <!-- Session Header -->
            <div class="bg-white rounded-xl shadow-sm p-6 mb-8">
                <div class="flex items-start justify-between mb-6">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">{{ $session->title }}</h1>
                        <p class="text-gray-600 mt-1">الجلسة رقم {{ $session->session_sequence }}</p>
                        @if($session->scheduled_at)
                            <p class="text-sm text-gray-500 mt-2">
                                <i class="ri-calendar-line ml-1"></i>
                                {{ $session->scheduled_at->format('l، d F Y - H:i A') }}
                            </p>
                        @endif
                    </div>
                    
                    <!-- Status Badge -->
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                        {{ $session->status === 'completed' ? 'bg-green-100 text-green-800' : 
                           ($session->status === 'scheduled' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800') }}">
                        @if($session->status === 'completed')
                            <i class="ri-check-line ml-1"></i>
                            مكتملة
                                                    @elseif($session->status === 'scheduled')
                                <i class="ri-calendar-line ml-1"></i>
                                مجدولة
                            @else
                                @php $statusData = $session->getStatusDisplayData(); @endphp
                                <i class="{{ $statusData['icon'] }} ml-1"></i>
                                {{ $statusData['label'] }}
                            @endif
                    </span>
                </div>

                <!-- Session Info -->
                @if($session->description || $session->lesson_objectives)
                    <div class="border-t border-gray-200 pt-6">
                        @if($session->description)
                            <div class="mb-4">
                                <h3 class="font-medium text-gray-900 mb-2">وصف الجلسة</h3>
                                <p class="text-gray-700">{{ $session->description }}</p>
                            </div>
                        @endif
                        
                        @if($session->lesson_objectives && count($session->lesson_objectives) > 0)
                            <div>
                                <h3 class="font-medium text-gray-900 mb-2">أهداف الجلسة</h3>
                                <ul class="list-disc list-inside space-y-1 text-gray-700">
                                    @foreach($session->lesson_objectives as $objective)
                                        <li>{{ $objective }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                @endif

                <!-- Meeting Access (for upcoming sessions) -->
                @if($session->status === 'scheduled' && $session->scheduled_at && $session->scheduled_at->isFuture())
                    @php
                        $minutesUntilSession = now()->diffInMinutes($session->scheduled_at);
                        $canJoin = $minutesUntilSession <= 30 && $minutesUntilSession > 0;
                    @endphp
                    
                    <div class="mt-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                        @if($canJoin)
                            <div class="flex items-center justify-between">
                                <div>
                                    <h4 class="font-medium text-blue-900">الجلسة متاحة الآن</h4>
                                    <p class="text-sm text-blue-700">يمكنك الانضمام للجلسة الآن</p>
                                </div>
                                <a href="{{ route('meetings.join', ['subdomain' => $academy->subdomain ?? 'itqan-academy', 'session' => $session->id]) }}" 
                                   class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                    <i class="ri-video-line ml-2"></i>
                                    انضمام للجلسة
                                </a>
                            </div>
                        @else
                            <div class="text-center">
                                <h4 class="font-medium text-blue-900 mb-2">جلسة مجدولة</h4>
                                <p class="text-sm text-blue-700">
                                    ستتمكن من الانضمام قبل 30 دقيقة من موعد الجلسة
                                </p>
                                @if($session->scheduled_at->isToday())
                                    <p class="text-xs text-blue-600 mt-1">
                                        متبقي {{ $minutesUntilSession }} دقيقة
                                    </p>
                                @endif
                            </div>
                        @endif
                    </div>
                @endif
            </div>

            <!-- Teacher Notes (for completed sessions) -->
            @if($session->status === 'completed' && $session->teacher_notes)
                <div class="bg-white rounded-xl shadow-sm p-6 mb-8">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">
                        <i class="ri-user-star-line text-blue-600 ml-2"></i>
                        ملاحظات المعلم
                    </h3>
                    <div class="prose prose-sm max-w-none">
                        <p class="text-gray-700">{{ $session->teacher_notes }}</p>
                    </div>
                </div>
            @endif

            <!-- Student Progress (for completed sessions) -->
            @if($session->status === 'completed' && $session->student_progress)
                <div class="bg-white rounded-xl shadow-sm p-6 mb-8">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">
                        <i class="ri-line-chart-line text-green-600 ml-2"></i>
                        التقدم المحرز
                    </h3>
                    <div class="prose prose-sm max-w-none">
                        <p class="text-gray-700">{{ $session->student_progress }}</p>
                    </div>
                </div>
            @endif

            <!-- Homework (if assigned) -->
            @if($session->homework_assigned)
                <div class="bg-white rounded-xl shadow-sm p-6 mb-8">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">
                        <i class="ri-book-2-line text-purple-600 ml-2"></i>
                        الواجب المنزلي
                    </h3>
                    <div class="prose prose-sm max-w-none">
                        <p class="text-gray-700">{{ $session->homework_assigned }}</p>
                    </div>
                </div>
            @endif

            <!-- Student Feedback Form (for completed sessions without feedback) -->
            @if($session->status === 'completed' && !$session->student_feedback)
                <div class="bg-white rounded-xl shadow-sm p-6 mb-8">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">
                        <i class="ri-feedback-line text-yellow-600 ml-2"></i>
                        تقييم الجلسة
                    </h3>
                    
                    <form id="feedbackForm" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">تقييمك للجلسة</label>
                            <div class="flex items-center space-x-2 space-x-reverse">
                                @for($i = 1; $i <= 5; $i++)
                                    <button type="button" class="star-rating text-2xl text-gray-300 hover:text-yellow-400 focus:outline-none" data-rating="{{ $i }}">
                                        <i class="ri-star-line"></i>
                                    </button>
                                @endfor
                            </div>
                            <input type="hidden" name="rating" id="rating" required>
                        </div>
                        
                        <div>
                            <label for="feedback" class="block text-sm font-medium text-gray-700 mb-2">ملاحظاتك على الجلسة</label>
                            <textarea name="student_feedback" id="feedback" rows="4" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500"
                                placeholder="شاركنا رأيك في الجلسة وكيف يمكن تحسينها..."></textarea>
                        </div>
                        
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
                            <i class="ri-send-plane-line ml-2"></i>
                            إرسال التقييم
                        </button>
                    </form>
                </div>
            @endif

            <!-- Student Feedback (if already submitted) -->
            @if($session->status === 'completed' && $session->student_feedback)
                <div class="bg-white rounded-xl shadow-sm p-6 mb-8">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">
                        <i class="ri-feedback-line text-yellow-600 ml-2"></i>
                        تقييمك للجلسة
                    </h3>
                    
                    <div class="flex items-center space-x-2 space-x-reverse mb-3">
                        @for($i = 1; $i <= 5; $i++)
                            <i class="ri-star-{{ $i <= $session->student_rating ? 'fill' : 'line' }} text-yellow-400"></i>
                        @endfor
                        <span class="text-sm text-gray-600 mr-2">{{ $session->student_rating }}/5</span>
                    </div>
                    
                    <p class="text-gray-700">{{ $session->student_feedback }}</p>
                    <p class="text-xs text-gray-500 mt-2">تم الإرسال {{ $session->feedback_at->diffForHumans() }}</p>
                </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="lg:col-span-1">
            <!-- Session Summary -->
            <div class="bg-white rounded-xl shadow-sm p-6 mb-6 sticky top-4">
                <h3 class="font-bold text-gray-900 mb-4">ملخص الجلسة</h3>
                
                <div class="space-y-3">
                    <div>
                        <span class="text-sm text-gray-600">المعلم:</span>
                        <p class="font-medium text-gray-900">{{ $session->quranTeacher->full_name ?? 'غير محدد' }}</p>
                    </div>
                    
                    <div>
                        <span class="text-sm text-gray-600">المدة:</span>
                        <p class="font-medium text-gray-900">{{ $session->duration_minutes }} دقيقة</p>
                    </div>
                    
                    @if($session->scheduled_at)
                        <div>
                            <span class="text-sm text-gray-600">الموعد:</span>
                            <p class="font-medium text-gray-900">{{ $session->scheduled_at->format('d/m/Y') }}</p>
                            <p class="text-sm text-gray-600">{{ $session->scheduled_at->format('H:i A') }}</p>
                        </div>
                    @endif
                    
                    @if($session->ended_at)
                        <div>
                            <span class="text-sm text-gray-600">انتهت في:</span>
                            <p class="font-medium text-gray-900">{{ $session->ended_at->format('d/m/Y H:i A') }}</p>
                        </div>
                    @endif
                </div>

                @if($session->individualCircle)
                    <div class="mt-6 pt-4 border-t border-gray-200">
                        <a href="{{ route('individual-circles.show', ['subdomain' => $academy->subdomain ?? 'itqan-academy', 'circle' => $session->individualCircle->id]) }}" 
                           class="text-primary-600 hover:text-primary-700 text-sm font-medium flex items-center">
                            <i class="ri-arrow-right-line ml-1"></i>
                            العودة للحلقة
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
// Star rating functionality
document.addEventListener('DOMContentLoaded', function() {
    const stars = document.querySelectorAll('.star-rating');
    const ratingInput = document.getElementById('rating');
    let selectedRating = 0;

    stars.forEach((star, index) => {
        star.addEventListener('click', function() {
            selectedRating = index + 1;
            ratingInput.value = selectedRating;
            updateStars();
        });

        star.addEventListener('mouseenter', function() {
            highlightStars(index + 1);
        });
    });

    document.getElementById('feedbackForm').addEventListener('mouseleave', function() {
        updateStars();
    });

    function highlightStars(rating) {
        stars.forEach((star, index) => {
            if (index < rating) {
                star.querySelector('i').className = 'ri-star-fill';
                star.classList.add('text-yellow-400');
                star.classList.remove('text-gray-300');
            } else {
                star.querySelector('i').className = 'ri-star-line';
                star.classList.add('text-gray-300');
                star.classList.remove('text-yellow-400');
            }
        });
    }

    function updateStars() {
        highlightStars(selectedRating);
    }

    // Feedback form submission
    document.getElementById('feedbackForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (!selectedRating) {
            alert('الرجاء اختيار تقييم للجلسة');
            return;
        }

        const formData = new FormData(this);
        formData.append('rating', selectedRating);

        fetch(`{{ route('student.sessions.feedback', ['subdomain' => $academy->subdomain ?? 'itqan-academy', 'sessionId' => $session->id]) }}`, {
            method: 'PUT',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert('خطأ: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('حدث خطأ في إرسال التقييم');
        });
    });
});
</script>

</x-layouts.student>
