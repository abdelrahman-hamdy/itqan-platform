<x-layouts.teacher 
    :title="'تفاصيل الجلسة - ' . config('app.name', 'منصة إتقان')"
    :description="'إدارة تفاصيل جلسة القرآن الكريم'">

<div class="max-w-7xl mx-auto">
    <!-- Breadcrumb -->
    <nav class="mb-8">
        <ol class="flex items-center space-x-2 space-x-reverse text-sm text-gray-600">
            <li>
                <a href="{{ route('teacher.individual-circles.show', ['subdomain' => $academy->subdomain ?? 'itqan-academy', 'circle' => $session->individualCircle->id]) }}" 
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
                        <p class="text-gray-600 mt-1">الجلسة رقم {{ $session->session_sequence }} مع {{ $session->student->name }}</p>
                        @if($session->scheduled_at)
                            <p class="text-sm text-gray-500 mt-2">
                                <i class="ri-calendar-line ml-1"></i>
                                {{ $session->scheduled_at->format('l، d F Y - H:i A') }}
                            </p>
                        @endif
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="flex items-center space-x-2 space-x-reverse">
                        @if($session->status === 'scheduled' && $session->scheduled_at && $session->scheduled_at->isFuture())
                            @php
                                $minutesUntilSession = now()->diffInMinutes($session->scheduled_at);
                                $canJoin = $minutesUntilSession <= 30;
                            @endphp
                            
                            @if($canJoin)
                                <a href="{{ route('meetings.join', ['subdomain' => $academy->subdomain ?? 'itqan-academy', 'session' => $session->id]) }}" 
                                   class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                                    <i class="ri-video-line ml-2"></i>
                                    بدء الجلسة
                                </a>
                            @endif
                        @endif
                        
                        @if($session->status === 'scheduled')
                            <button type="button" id="markCompleteBtn" 
                                class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                <i class="ri-check-line ml-2"></i>
                                إنهاء الجلسة
                            </button>
                        @endif
                        
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
                                <i class="ri-draft-line ml-1"></i>
                                قالب
                            @endif
                        </span>
                    </div>
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
            </div>

            <!-- Session Notes Form -->
            <div class="bg-white rounded-xl shadow-sm p-6 mb-8">
                <h3 class="text-lg font-bold text-gray-900 mb-6">
                    <i class="ri-edit-line text-blue-600 ml-2"></i>
                    ملاحظات الجلسة
                </h3>
                
                <form id="notesForm" class="space-y-6">
                    <div>
                        <label for="teacher_notes" class="block text-sm font-medium text-gray-700 mb-2">ملاحظات المعلم</label>
                        <textarea name="teacher_notes" id="teacher_notes" rows="4"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500"
                            placeholder="اكتب ملاحظاتك على الجلسة والطالب...">{{ $session->teacher_notes }}</textarea>
                    </div>
                    
                    <div>
                        <label for="student_progress" class="block text-sm font-medium text-gray-700 mb-2">تقدم الطالب</label>
                        <textarea name="student_progress" id="student_progress" rows="3"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500"
                            placeholder="اذكر التقدم المحرز في هذه الجلسة...">{{ $session->student_progress }}</textarea>
                    </div>
                    
                    <div>
                        <label for="homework_assigned" class="block text-sm font-medium text-gray-700 mb-2">الواجب المنزلي</label>
                        <textarea name="homework_assigned" id="homework_assigned" rows="3"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500"
                            placeholder="اكتب الواجب المنزلي المطلوب للجلسة القادمة...">{{ $session->homework_assigned }}</textarea>
                    </div>
                    
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
                        <i class="ri-save-line ml-2"></i>
                        حفظ الملاحظات
                    </button>
                </form>
            </div>

            <!-- Student Feedback (if available) -->
            @if($session->status === 'completed' && $session->student_feedback)
                <div class="bg-white rounded-xl shadow-sm p-6 mb-8">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">
                        <i class="ri-feedback-line text-yellow-600 ml-2"></i>
                        تقييم الطالب
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

            <!-- Recording (if available) -->
            @if($session->status === 'completed' && $session->recording_url)
                <div class="bg-white rounded-xl shadow-sm p-6 mb-8">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">
                        <i class="ri-video-line text-red-600 ml-2"></i>
                        تسجيل الجلسة
                    </h3>
                    
                    <a href="{{ $session->recording_url }}" target="_blank"
                       class="inline-flex items-center px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                        <i class="ri-play-circle-line ml-2"></i>
                        مشاهدة التسجيل
                    </a>
                </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="lg:col-span-1">
            <!-- Student Info -->
            <div class="bg-white rounded-xl shadow-sm p-6 mb-6 sticky top-4">
                <h3 class="font-bold text-gray-900 mb-4">معلومات الطالب</h3>
                
                <div class="flex items-center space-x-3 space-x-reverse mb-4">
                    @if($session->student->avatar)
                        <img src="{{ asset('storage/' . $session->student->avatar) }}" alt="{{ $session->student->name }}" 
                             class="w-12 h-12 rounded-full object-cover">
                    @else
                        <div class="w-12 h-12 rounded-full bg-primary-100 flex items-center justify-center">
                            <span class="text-lg font-bold text-primary-600">{{ substr($session->student->name, 0, 1) }}</span>
                        </div>
                    @endif
                    <div>
                        <h4 class="font-medium text-gray-900">{{ $session->student->name }}</h4>
                        @if($session->student->email)
                            <p class="text-sm text-gray-600">{{ $session->student->email }}</p>
                        @endif
                    </div>
                </div>
                
                <div class="space-y-3">
                    <div>
                        <span class="text-sm text-gray-600">نوع الاشتراك:</span>
                        <p class="font-medium text-gray-900">{{ $session->individualCircle->subscription->package->name ?? 'غير محدد' }}</p>
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

                <div class="mt-6 pt-4 border-t border-gray-200">
                    <a href="{{ route('teacher.individual-circles.show', ['subdomain' => $academy->subdomain ?? 'itqan-academy', 'circle' => $session->individualCircle->id]) }}" 
                       class="text-primary-600 hover:text-primary-700 text-sm font-medium flex items-center">
                        <i class="ri-arrow-right-line ml-1"></i>
                        العودة للحلقة
                    </a>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="font-bold text-gray-900 mb-4">إجراءات سريعة</h3>
                
                <div class="space-y-3">
                    <a href="mailto:{{ $session->student->email }}" 
                       class="w-full flex items-center justify-center px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors">
                        <i class="ri-mail-line ml-2"></i>
                        إرسال رسالة للطالب
                    </a>
                    
                    @if($session->individualCircle)
                        <a href="{{ route('teacher.individual-circles.progress', ['subdomain' => $academy->subdomain ?? 'itqan-academy', 'circle' => $session->individualCircle->id]) }}" 
                           class="w-full flex items-center justify-center px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors">
                            <i class="ri-line-chart-line ml-2"></i>
                            تقرير التقدم
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Notes form submission
    document.getElementById('notesForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;
        
        submitBtn.disabled = true;
        submitBtn.textContent = 'جاري الحفظ...';

        fetch(`{{ route('sessions.update-notes', ['subdomain' => $academy->subdomain ?? 'itqan-academy', 'sessionId' => $session->id]) }}`, {
            method: 'PUT',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast(data.message, 'success');
            } else {
                showToast('خطأ: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('حدث خطأ في حفظ الملاحظات', 'error');
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        });
    });

    // Mark complete functionality
    @if($session->status === 'scheduled')
    document.getElementById('markCompleteBtn').addEventListener('click', function() {
        if (confirm('هل أنت متأكد من إنهاء هذه الجلسة؟ لن تتمكن من التراجع عن هذا الإجراء.')) {
            const btn = this;
            const originalText = btn.textContent;
            
            btn.disabled = true;
            btn.textContent = 'جاري الإنهاء...';

            fetch(`{{ route('sessions.complete', ['subdomain' => $academy->subdomain ?? 'itqan-academy', 'sessionId' => $session->id]) }}`, {
                method: 'PUT',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message, 'success');
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    showToast('خطأ: ' + data.message, 'error');
                    btn.disabled = false;
                    btn.textContent = originalText;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('حدث خطأ في إنهاء الجلسة', 'error');
                btn.disabled = false;
                btn.textContent = originalText;
            });
        }
    });
    @endif
});
</script>

</x-layouts.teacher>
