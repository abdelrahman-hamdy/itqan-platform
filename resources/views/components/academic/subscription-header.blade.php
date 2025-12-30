@props([
    'subscription',
    'viewType' => 'student' // 'student' or 'teacher'
])

@php
    use App\Enums\SubscriptionStatus;

    $student = $subscription->student;
    $teacher = $subscription->teacher;
    $isTeacher = $viewType === 'teacher';
    $statusValue = is_object($subscription->status) ? $subscription->status->value : $subscription->status;
@endphp

<!-- Enhanced Academic Subscription Header -->
<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
    <div class="flex items-center justify-between">
        <!-- Subscription Identity -->
        <div class="flex-1">
            <div class="flex items-center justify-between mb-2">
                <h1 class="text-3xl font-bold text-gray-900">
                    @if($isTeacher)
                        {{ __('components.academic.subscription_header.private_lesson') }} - {{ $student->name ?? __('components.academic.subscription_header.student') }}
                    @else
                        {{ $subscription->subject->name ?? $subscription->subject_name ?? __('components.academic.subscription_header.private_lesson') }}
                    @endif
                </h1>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                    {{ $statusValue === SubscriptionStatus::ACTIVE->value ? 'bg-green-100 text-green-800' :
                       ($statusValue === SubscriptionStatus::PENDING->value ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800') }}">
                    {{ $statusValue === SubscriptionStatus::ACTIVE->value ? __('components.academic.subscription_header.status_active') :
                       ($statusValue === SubscriptionStatus::PENDING->value ? __('components.academic.subscription_header.status_pending') : __('components.academic.subscription_header.status_completed')) }}
                </span>
            </div>
            
            <!-- Subscription Description -->
            <p class="text-gray-600 mb-4 leading-relaxed">
                @if($isTeacher)
                    {{ __('components.academic.subscription_header.private_lesson_in') }} {{ $subscription->subject->name ?? $subscription->subject_name ?? __('components.academic.subscription_header.the_subject') }}
                    {{ __('components.academic.subscription_header.for_grade') }} {{ $subscription->gradeLevel->name ?? $subscription->grade_level_name ?? __('components.academic.subscription_header.the_level') }}
                    {{ __('components.academic.subscription_header.with_student') }} {{ $student->name ?? '' }}
                @else
                    {{ __('components.academic.subscription_header.private_lesson_in') }} {{ $subscription->subject->name ?? $subscription->subject_name ?? __('components.academic.subscription_header.the_subject') }}
                @endif
            </p>
            
            <!-- Session Progress -->
            <div class="flex items-center space-x-4 space-x-reverse">
                <span class="inline-flex items-center px-3 py-1 bg-blue-50 text-blue-700 text-sm font-medium rounded-full">
                    <i class="ri-calendar-check-line ms-1"></i>
                    {{ $subscription->sessions_per_week ?? 0 }} {{ __('components.academic.subscription_header.sessions_per_week') }}
                </span>
                <span class="inline-flex items-center px-3 py-1 bg-orange-50 text-orange-700 text-sm font-medium rounded-full">
                    <i class="ri-percent-line ms-1"></i>
                    {{ number_format($subscription->completion_rate ?? 0, 1) }}% {{ __('components.academic.subscription_header.completed') }}
                </span>
            </div>
        </div>
    </div>

    <!-- Student/Teacher Info Card (for teacher view) -->
    @if($isTeacher && $student)
        <div class="mt-6 pt-6 border-t border-gray-200">
            <div class="flex items-center space-x-4 space-x-reverse p-4 bg-gray-50 rounded-lg">
                <x-avatar
                    :user="$student"
                    size="lg"
                    userType="student"
                    :gender="$student->gender ?? $student->studentProfile?->gender ?? 'male'" />
                <div class="flex-1">
                    <h3 class="text-lg font-semibold text-gray-900">
                        {{ $student->name ?? __('components.academic.subscription_header.student') }}
                    </h3>
                    <p class="text-sm text-gray-500">
                        {{ $subscription->subject->name ?? $subscription->subject_name ?? __('components.academic.subscription_header.the_subject') }} -
                        {{ $subscription->gradeLevel->name ?? $subscription->grade_level_name ?? __('components.academic.subscription_header.the_level') }}
                    </p>
                    <div class="flex items-center space-x-3 space-x-reverse mt-2">
                        @if($student->email)
                            <span class="text-xs text-gray-400">{{ $student->email }}</span>
                        @endif
                        @if($subscription->expires_at)
                            <span class="text-xs text-gray-400">{{ __('components.academic.subscription_header.expires') }} {{ $subscription->expires_at->format('Y-m-d') }}</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Admin Notes -->
    @if($subscription->admin_notes && ($isTeacher || (auth()->user() && auth()->user()->hasRole(['admin', 'super_admin']))))
        <x-common.admin-notes :notes="$subscription->admin_notes" :visibilityNote="__('components.academic.subscription_header.admin_visibility_note')" />
    @endif
</div>
