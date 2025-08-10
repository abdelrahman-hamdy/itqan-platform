@extends('layouts.app')

@section('title', 'إدارة جدول الحلقة الجماعية')

@push('styles')
<style>
    .schedule-day-card {
        border: 2px solid #e5e7eb;
        border-radius: 12px;
        transition: all 0.3s ease;
    }
    
    .schedule-day-card.active {
        border-color: #3b82f6;
        background-color: #eff6ff;
    }
    
    .schedule-preview {
        background: #f8fafc;
        border-radius: 8px;
        max-height: 400px;
        overflow-y: auto;
    }
    
    .time-slot {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 6px;
        padding: 0.75rem;
        margin-bottom: 0.5rem;
    }
    
    .weekday-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 1rem;
    }
    
    .form-section {
        background: white;
        border-radius: 12px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }
</style>
@endpush

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">إعداد جدول الحلقة</h1>
                    <p class="mt-2 text-gray-600">{{ $circle->name_ar ?? 'حلقة القرآن الكريم' }}</p>
                </div>
                <div class="flex space-x-3 space-x-reverse">
                    <a href="{{ route('teacher.group-circles.index') }}" class="bg-gray-200 text-gray-700 px-6 py-3 rounded-lg hover:bg-gray-300 transition-colors font-medium">
                        عودة للحلقات
                    </a>
                </div>
            </div>
        </div>

        <form id="scheduleForm" action="{{ route('teacher.group-circles.store-schedule', $circle) }}" method="POST">
            @csrf
            
            <!-- Circle Information -->
            <div class="form-section">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">معلومات الحلقة</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">اسم الحلقة</label>
                        <input type="text" value="{{ $circle->name_ar }}" disabled class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">عدد الطلاب المسجلين</label>
                        <input type="text" value="{{ $circle->enrolled_students }}" disabled class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">الحالة الحالية</label>
                        <span class="inline-flex px-3 py-2 rounded-lg text-sm font-medium 
                            {{ $circle->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                            {{ $circle->status === 'active' ? 'نشطة' : 'غير مجدولة' }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Weekly Schedule Configuration -->
            <div class="form-section">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">الجدول الأسبوعي</h2>
                <p class="text-gray-600 mb-6">اختر الأيام والأوقات التي ستُعقد فيها الحلقة أسبوعياً</p>
                
                <div class="weekday-grid" id="weeklySchedule">
                    @php
                        $weekdays = [
                            'sunday' => ['name' => 'الأحد', 'short' => 'أحد'],
                            'monday' => ['name' => 'الاثنين', 'short' => 'اثنين'],
                            'tuesday' => ['name' => 'الثلاثاء', 'short' => 'ثلاثاء'],
                            'wednesday' => ['name' => 'الأربعاء', 'short' => 'أربعاء'],
                            'thursday' => ['name' => 'الخميس', 'short' => 'خميس'],
                            'friday' => ['name' => 'الجمعة', 'short' => 'جمعة'],
                            'saturday' => ['name' => 'السبت', 'short' => 'سبت'],
                        ];
                        $existingSchedule = $circle->schedule?->weekly_schedule ?? [];
                    @endphp
                    
                    @foreach($weekdays as $day => $info)
                        @php
                            $daySchedule = collect($existingSchedule)->firstWhere('day', $day);
                        @endphp
                        <div class="schedule-day-card p-4 {{ $daySchedule ? 'active' : '' }}" data-day="{{ $day }}">
                            <div class="flex items-center justify-between mb-3">
                                <h3 class="font-medium text-gray-900">{{ $info['name'] }}</h3>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" 
                                           class="sr-only day-toggle" 
                                           data-day="{{ $day }}"
                                           {{ $daySchedule ? 'checked' : '' }}>
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:right-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                </label>
                            </div>
                            
                            <div class="day-schedule-fields" style="{{ !$daySchedule ? 'display: none;' : '' }}">
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">الوقت</label>
                                        <input type="time" 
                                               name="schedule[{{ $day }}][time]" 
                                               value="{{ $daySchedule['time'] ?? '16:00' }}"
                                               class="w-full px-2 py-1 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent day-time">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">المدة (دقيقة)</label>
                                        <select name="schedule[{{ $day }}][duration]" 
                                                class="w-full px-2 py-1 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent day-duration">
                                            <option value="30" {{ ($daySchedule['duration'] ?? 60) == 30 ? 'selected' : '' }}>30</option>
                                            <option value="60" {{ ($daySchedule['duration'] ?? 60) == 60 ? 'selected' : '' }}>60</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Schedule Dates -->
            <div class="form-section">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">تواريخ الجدول</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="schedule_starts_at" class="block text-sm font-medium text-gray-700 mb-1">تاريخ البداية *</label>
                        <input type="date" 
                               id="schedule_starts_at" 
                               name="schedule_starts_at" 
                               value="{{ $circle->schedule?->schedule_starts_at?->format('Y-m-d') ?? now()->format('Y-m-d') }}"
                               min="{{ now()->format('Y-m-d') }}"
                               required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div>
                        <label for="schedule_ends_at" class="block text-sm font-medium text-gray-700 mb-1">تاريخ النهاية (اختياري)</label>
                        <input type="date" 
                               id="schedule_ends_at" 
                               name="schedule_ends_at" 
                               value="{{ $circle->schedule?->schedule_ends_at?->format('Y-m-d') }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">اتركه فارغاً للجلسات المستمرة</p>
                    </div>
                </div>
            </div>

            <!-- Session Templates -->
            <div class="form-section">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">قوالب الجلسة</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="session_title_template" class="block text-sm font-medium text-gray-700 mb-1">قالب عنوان الجلسة</label>
                        <input type="text" 
                               id="session_title_template" 
                               name="session_title_template" 
                               value="{{ $circle->schedule?->session_title_template ?? 'حلقة {circle_name} - {day}' }}"
                               placeholder="حلقة {circle_name} - {day}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">استخدم: {circle_name}, {date}, {time}, {day}, {teacher_name}</p>
                    </div>
                    <div>
                        <label for="generate_ahead_days" class="block text-sm font-medium text-gray-700 mb-1">إنشاء الجلسات مسبقاً (أيام)</label>
                        <select id="generate_ahead_days" 
                                name="generate_ahead_days" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="7" {{ ($circle->schedule?->generate_ahead_days ?? 30) == 7 ? 'selected' : '' }}>7 أيام</option>
                            <option value="14" {{ ($circle->schedule?->generate_ahead_days ?? 30) == 14 ? 'selected' : '' }}>14 يوم</option>
                            <option value="30" {{ ($circle->schedule?->generate_ahead_days ?? 30) == 30 ? 'selected' : '' }}>30 يوم</option>
                            <option value="60" {{ ($circle->schedule?->generate_ahead_days ?? 30) == 60 ? 'selected' : '' }}>60 يوم</option>
                            <option value="90" {{ ($circle->schedule?->generate_ahead_days ?? 30) == 90 ? 'selected' : '' }}>90 يوم</option>
                        </select>
                    </div>
                </div>
                
                <div class="mt-4">
                    <label for="session_description_template" class="block text-sm font-medium text-gray-700 mb-1">قالب وصف الجلسة</label>
                    <textarea id="session_description_template" 
                              name="session_description_template" 
                              rows="3"
                              placeholder="جلسة حلقة القرآن الكريم - {circle_name} يوم {day} الساعة {time}"
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">{{ $circle->schedule?->session_description_template }}</textarea>
                </div>
            </div>

            <!-- Meeting Settings -->
            <div class="form-section">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">إعدادات الاجتماع</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="meeting_link" class="block text-sm font-medium text-gray-700 mb-1">رابط الاجتماع</label>
                        <input type="url" 
                               id="meeting_link" 
                               name="meeting_link" 
                               value="{{ $circle->schedule?->meeting_link }}"
                               placeholder="https://meet.google.com/..."
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div>
                        <label for="meeting_id" class="block text-sm font-medium text-gray-700 mb-1">معرف الاجتماع</label>
                        <input type="text" 
                               id="meeting_id" 
                               name="meeting_id" 
                               value="{{ $circle->schedule?->meeting_id }}"
                               placeholder="123-456-789"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
                    <div>
                        <label for="meeting_password" class="block text-sm font-medium text-gray-700 mb-1">كلمة مرور الاجتماع</label>
                        <input type="password" 
                               id="meeting_password" 
                               name="meeting_password" 
                               value="{{ $circle->schedule?->meeting_password }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div class="flex items-center mt-6">
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" 
                                   name="recording_enabled" 
                                   value="1"
                                   {{ $circle->schedule?->recording_enabled ? 'checked' : '' }}
                                   class="sr-only">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:right-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                            <span class="mr-3 text-sm font-medium text-gray-700">تفعيل التسجيل</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Preview Section -->
            <div class="form-section">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">معاينة الجدول</h2>
                <div class="flex space-x-4 space-x-reverse mb-4">
                    <button type="button" 
                            id="previewBtn" 
                            class="bg-blue-100 text-blue-800 px-4 py-2 rounded-lg hover:bg-blue-200 transition-colors font-medium">
                        معاينة الجلسات القادمة
                    </button>
                    <span id="previewLoading" class="text-gray-500 hidden">جاري التحميل...</span>
                </div>
                
                <div id="schedulePreview" class="schedule-preview p-4 hidden">
                    <!-- Preview content will be loaded here -->
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="form-section">
                <div class="flex justify-between items-center">
                    <div>
                        @if($circle->schedule && $circle->schedule->is_active)
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                <svg class="w-4 h-4 ml-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                                الجدول نشط حالياً
                            </span>
                        @else
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800">
                                <svg class="w-4 h-4 ml-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                </svg>
                                الجدول غير نشط
                            </span>
                        @endif
                    </div>
                    
                    <div class="flex space-x-3 space-x-reverse">
                        @if($circle->schedule && $circle->schedule->is_active)
                            <button type="button" 
                                    id="deactivateBtn"
                                    class="bg-red-600 text-white px-6 py-3 rounded-lg hover:bg-red-700 transition-colors font-medium">
                                إلغاء تفعيل الجدول
                            </button>
                        @endif
                        
                        <button type="submit" 
                                class="bg-blue-600 text-white px-8 py-3 rounded-lg hover:bg-blue-700 transition-colors font-medium">
                            {{ $circle->schedule ? 'تحديث الجدول' : 'حفظ وتفعيل الجدول' }}
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle day toggle
    document.querySelectorAll('.day-toggle').forEach(function(toggle) {
        toggle.addEventListener('change', function() {
            const day = this.dataset.day;
            const card = document.querySelector(`[data-day="${day}"]`);
            const fields = card.querySelector('.day-schedule-fields');
            
            if (this.checked) {
                card.classList.add('active');
                fields.style.display = 'block';
            } else {
                card.classList.remove('active');
                fields.style.display = 'none';
            }
        });
    });

    // Handle preview
    document.getElementById('previewBtn').addEventListener('click', function() {
        previewSchedule();
    });

    // Handle form submission
    document.getElementById('scheduleForm').addEventListener('submit', function(e) {
        e.preventDefault();
        submitSchedule();
    });

    // Handle deactivate button
    const deactivateBtn = document.getElementById('deactivateBtn');
    if (deactivateBtn) {
        deactivateBtn.addEventListener('click', function() {
            deactivateSchedule();
        });
    }

    // Initialize existing schedule
    initializeExistingSchedule();
});

function initializeExistingSchedule() {
    // Set up existing day toggles based on saved data
    document.querySelectorAll('.day-toggle').forEach(function(toggle) {
        const day = toggle.dataset.day;
        const card = document.querySelector(`[data-day="${day}"]`);
        const fields = card.querySelector('.day-schedule-fields');
        
        if (toggle.checked) {
            card.classList.add('active');
            fields.style.display = 'block';
        }
    });
}

function collectScheduleData() {
    const scheduleData = [];
    
    document.querySelectorAll('.day-toggle:checked').forEach(function(toggle) {
        const day = toggle.dataset.day;
        const card = document.querySelector(`[data-day="${day}"]`);
        const timeInput = card.querySelector('.day-time');
        const durationSelect = card.querySelector('.day-duration');
        
        if (timeInput.value) {
            scheduleData.push({
                day: day,
                time: timeInput.value,
                duration: parseInt(durationSelect.value)
            });
        }
    });
    
    return scheduleData;
}

function previewSchedule() {
    const loadingEl = document.getElementById('previewLoading');
    const previewEl = document.getElementById('schedulePreview');
    const btn = document.getElementById('previewBtn');
    
    btn.disabled = true;
    loadingEl.classList.remove('hidden');
    
    const scheduleData = collectScheduleData();
    const formData = new FormData(document.getElementById('scheduleForm'));
    
    const requestData = {
        weekly_schedule: scheduleData,
        schedule_starts_at: formData.get('schedule_starts_at'),
        schedule_ends_at: formData.get('schedule_ends_at'),
        preview_days: 30
    };

    fetch(`{{ route('teacher.group-circles.preview-sessions', $circle) }}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify(requestData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            displayPreview(data);
        } else {
            alert('خطأ في معاينة الجدول: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('حدث خطأ في معاينة الجدول');
    })
    .finally(() => {
        btn.disabled = false;
        loadingEl.classList.add('hidden');
    });
}

function displayPreview(data) {
    const previewEl = document.getElementById('schedulePreview');
    
    let html = `
        <div class="mb-4">
            <h3 class="font-semibold text-gray-900">معاينة الجلسات (${data.sessions_count} جلسة في ${data.preview_period.days} يوم)</h3>
            <p class="text-sm text-gray-600">من ${data.preview_period.start} إلى ${data.preview_period.end}</p>
        </div>
    `;
    
    if (data.upcoming_sessions && data.upcoming_sessions.length > 0) {
        html += '<div class="space-y-2">';
        data.upcoming_sessions.forEach(session => {
            html += `
                <div class="time-slot">
                    <div class="flex justify-between items-center">
                        <div>
                            <div class="font-medium">${session.title}</div>
                            <div class="text-sm text-gray-600">${session.date} في ${session.time}</div>
                        </div>
                        <div class="text-sm text-gray-500">${session.duration} دقيقة</div>
                    </div>
                </div>
            `;
        });
        html += '</div>';
        
        if (data.sessions_count > data.upcoming_sessions.length) {
            html += `<p class="text-sm text-gray-500 mt-3">... و ${data.sessions_count - data.upcoming_sessions.length} جلسة أخرى</p>`;
        }
    } else {
        html += '<p class="text-gray-500">لا توجد جلسات في هذه الفترة</p>';
    }
    
    previewEl.innerHTML = html;
    previewEl.classList.remove('hidden');
}

function submitSchedule() {
    const form = document.getElementById('scheduleForm');
    const formData = new FormData(form);
    const scheduleData = collectScheduleData();
    
    if (scheduleData.length === 0) {
        alert('يجب اختيار يوم واحد على الأقل للجدول');
        return;
    }
    
    // Add schedule data to form
    formData.append('weekly_schedule', JSON.stringify(scheduleData));
    
    fetch(form.action, {
        method: 'POST',
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
        alert('حدث خطأ في حفظ الجدول');
    });
}

function deactivateSchedule() {
    if (!confirm('هل أنت متأكد من إلغاء تفعيل جدول الحلقة؟ سيتم إلغاء جميع الجلسات المجدولة مستقبلاً.')) {
        return;
    }
    
    fetch(`{{ route('teacher.group-circles.deactivate', $circle) }}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
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
        alert('حدث خطأ في إلغاء تفعيل الجدول');
    });
}
</script>
@endpush