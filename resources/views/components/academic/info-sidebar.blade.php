@props([
    'subscription',
    'viewType' => 'student',
    'context' => 'individual'
])

@php
    $student = $subscription->student;
    $teacher = $subscription->teacher;
    $isTeacher = $viewType === 'teacher';
@endphp

<!-- Academic Subscription Info Sidebar -->
<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-gray-900 flex items-center">
            <i class="ri-information-line text-blue-600 ml-2"></i>
            معلومات الاشتراك
        </h3>
    </div>

    <div class="space-y-4">
        <!-- Subject and Grade -->
        <div class="flex items-center justify-between py-2 border-b border-gray-100">
            <span class="text-sm font-medium text-gray-600">المادة</span>
            <span class="text-sm text-gray-900">{{ $subscription->subject->name ?? $subscription->subject_name ?? 'غير محدد' }}</span>
        </div>

        <div class="flex items-center justify-between py-2 border-b border-gray-100">
            <span class="text-sm font-medium text-gray-600">الصف</span>
            <span class="text-sm text-gray-900">{{ $subscription->gradeLevel->name ?? $subscription->grade_level_name ?? 'غير محدد' }}</span>
        </div>

        <!-- Sessions per Week -->
        <div class="flex items-center justify-between py-2 border-b border-gray-100">
            <span class="text-sm font-medium text-gray-600">الجلسات أسبوعياً</span>
            <span class="text-sm text-gray-900">{{ $subscription->sessions_per_week ?? 0 }} جلسة</span>
        </div>

        <!-- Monthly Fee -->
        @if($subscription->monthly_fee)
        <div class="flex items-center justify-between py-2 border-b border-gray-100">
            <span class="text-sm font-medium text-gray-600">الرسوم الشهرية</span>
            <span class="text-sm text-gray-900">{{ number_format($subscription->monthly_fee, 2) }} ريال</span>
        </div>
        @endif

        <!-- Start Date -->
        @if($subscription->start_date)
        <div class="flex items-center justify-between py-2 border-b border-gray-100">
            <span class="text-sm font-medium text-gray-600">تاريخ البداية</span>
            <span class="text-sm text-gray-900">{{ $subscription->start_date->format('Y-m-d') }}</span>
        </div>
        @endif

        <!-- End Date -->
        @if($subscription->end_date)
        <div class="flex items-center justify-between py-2 border-b border-gray-100">
            <span class="text-sm font-medium text-gray-600">تاريخ الانتهاء</span>
            <span class="text-sm text-gray-900">{{ $subscription->end_date->format('Y-m-d') }}</span>
        </div>
        @endif

        <!-- Student Info (for teacher view) -->
        @if($isTeacher && $student)
        <div class="mt-6 pt-4 border-t border-gray-200">
            <h4 class="text-sm font-semibold text-gray-700 mb-3 flex items-center">
                <i class="ri-user-3-line text-green-600 ml-1"></i>
                معلومات الطالب
            </h4>
            
            <div class="flex items-center justify-between py-2 border-b border-gray-100">
                <span class="text-sm font-medium text-gray-600">الاسم</span>
                <span class="text-sm text-gray-900">{{ $student->name }}</span>
            </div>

            @if($student->email)
            <div class="flex items-center justify-between py-2 border-b border-gray-100">
                <span class="text-sm font-medium text-gray-600">البريد الإلكتروني</span>
                <span class="text-sm text-gray-900">{{ $student->email }}</span>
            </div>
            @endif

            @if($student->phone)
            <div class="flex items-center justify-between py-2 border-b border-gray-100">
                <span class="text-sm font-medium text-gray-600">رقم الجوال</span>
                <span class="text-sm text-gray-900">{{ $student->phone }}</span>
            </div>
            @endif
        </div>
        @endif

        <!-- Teacher Info (for student view) -->
        @if(!$isTeacher && $teacher)
        <div class="mt-6 pt-4 border-t border-gray-200">
            <h4 class="text-sm font-semibold text-gray-700 mb-3 flex items-center">
                <i class="ri-user-star-line text-purple-600 ml-1"></i>
                معلومات المعلم
            </h4>
            
            <div class="flex items-center justify-between py-2 border-b border-gray-100">
                <span class="text-sm font-medium text-gray-600">الاسم</span>
                <span class="text-sm text-gray-900">{{ $teacher->first_name }} {{ $teacher->last_name }}</span>
            </div>

            @if($teacher->experience_years)
            <div class="flex items-center justify-between py-2 border-b border-gray-100">
                <span class="text-sm font-medium text-gray-600">سنوات الخبرة</span>
                <span class="text-sm text-gray-900">{{ $teacher->experience_years }} سنة</span>
            </div>
            @endif
        </div>
        @endif

        <!-- Subscription Status -->
        <div class="mt-6 pt-4 border-t border-gray-200">
            <div class="flex items-center justify-between">
                <span class="text-sm font-medium text-gray-600">الحالة</span>
                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                    {{ $subscription->status === 'active' ? 'bg-green-100 text-green-800' : 
                       ($subscription->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800') }}">
                    {{ $subscription->status === 'active' ? 'نشط' : 
                       ($subscription->status === 'pending' ? 'قيد الانتظار' : 'مكتمل') }}
                </span>
            </div>
        </div>
    </div>
</div>
