@extends('components.layouts.teacher')

@section('title', __('teacher.academic_lessons.title') . ' - ' . config('app.name', 'منصة إتقان'))

@section('content')
@php
    use App\Enums\SubscriptionStatus;

    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';

    $breadcrumbs = [
        ['label' => __('teacher.academic_lessons.title')],
    ];

    $filterOptions = [
        '' => __('teacher.academic_lessons.all_subscriptions'),
        SubscriptionStatus::ACTIVE->value => __('teacher.academic_lessons.active'),
        SubscriptionStatus::PENDING->value => __('teacher.academic_lessons.pending_payment'),
        SubscriptionStatus::EXPIRED->value => __('teacher.academic_lessons.expired'),
        SubscriptionStatus::COMPLETED->value => __('teacher.academic_lessons.completed'),
        SubscriptionStatus::CANCELLED->value => __('teacher.academic_lessons.cancelled'),
    ];

    $stats = [
        [
            'icon' => 'ri-user-3-line',
            'bgColor' => 'bg-violet-100',
            'iconColor' => 'text-violet-600',
            'value' => $subscriptions->total(),
            'label' => __('teacher.academic_lessons.total_subscriptions'),
        ],
        [
            'icon' => 'ri-play-circle-line',
            'bgColor' => 'bg-green-100',
            'iconColor' => 'text-green-600',
            'value' => $subscriptions->filter(fn($s) => $s->status?->value === SubscriptionStatus::ACTIVE->value || $s->status === SubscriptionStatus::ACTIVE->value)->count(),
            'label' => __('teacher.academic_lessons.active_subscriptions'),
        ],
        [
            'icon' => 'ri-time-line',
            'bgColor' => 'bg-yellow-100',
            'iconColor' => 'text-yellow-600',
            'value' => $subscriptions->filter(fn($s) => $s->status?->value === SubscriptionStatus::PENDING->value || $s->status === SubscriptionStatus::PENDING->value)->count(),
            'label' => __('teacher.academic_lessons.pending_subscriptions'),
        ],
        [
            'icon' => 'ri-check-double-line',
            'bgColor' => 'bg-violet-100',
            'iconColor' => 'text-violet-600',
            'value' => $subscriptions->filter(fn($s) => $s->status?->value === SubscriptionStatus::COMPLETED->value || $s->status === SubscriptionStatus::COMPLETED->value)->count(),
            'label' => __('teacher.academic_lessons.completed_subscriptions'),
        ],
    ];
@endphp

<x-teacher.entity-list-page
    :title="__('teacher.academic_lessons.title')"
    :subtitle="__('teacher.academic_lessons.subtitle')"
    :items="$subscriptions"
    :stats="$stats"
    :filter-options="$filterOptions"
    :breadcrumbs="$breadcrumbs"
    theme-color="violet"
    :list-title="__('teacher.academic_lessons.list_title')"
    empty-icon="ri-user-3-line"
    :empty-title="__('teacher.academic_lessons.empty_title')"
    :empty-description="__('teacher.academic_lessons.empty_description')"
    :empty-filter-description="__('teacher.academic_lessons.empty_filter_description')"
    :clear-filter-route="route('teacher.academic.lessons.index', ['subdomain' => $subdomain])"
    :clear-filter-text="__('teacher.academic_lessons.view_all_subscriptions')"
>
    @foreach($subscriptions as $subscription)
        @php
            // Handle both enum and string status
            $statusValue = is_object($subscription->status) ? $subscription->status->value : $subscription->status;
            $statusConfig = match($statusValue) {
                SubscriptionStatus::ACTIVE->value => ['class' => 'bg-green-100 text-green-800', 'text' => __('teacher.academic_lessons.active')],
                SubscriptionStatus::PENDING->value => ['class' => 'bg-yellow-100 text-yellow-800', 'text' => __('teacher.academic_lessons.pending_payment')],
                SubscriptionStatus::EXPIRED->value => ['class' => 'bg-red-100 text-red-800', 'text' => __('teacher.academic_lessons.expired')],
                SubscriptionStatus::COMPLETED->value => ['class' => 'bg-violet-100 text-violet-800', 'text' => __('teacher.academic_lessons.completed')],
                SubscriptionStatus::CANCELLED->value => ['class' => 'bg-gray-100 text-gray-800', 'text' => __('teacher.academic_lessons.cancelled')],
                default => ['class' => 'bg-gray-100 text-gray-800', 'text' => $statusValue ?? __('common.not_specified')]
            };

            $metadata = [];
            if ($subscription->subject) {
                $metadata[] = ['icon' => 'ri-book-line', 'text' => $subscription->subject->name];
            }
            if ($subscription->gradeLevel) {
                $metadata[] = ['icon' => 'ri-graduation-cap-line', 'text' => $subscription->gradeLevel->getDisplayName()];
            }
            $metadata[] = ['icon' => 'ri-calendar-line', 'text' => $subscription->created_at->format('Y/m/d')];
            if ($subscription->sessions_per_week) {
                $metadata[] = ['icon' => 'ri-time-line', 'text' => $subscription->sessions_per_week . ' ' . __('teacher.academic_lessons.sessions_per_week')];
            }

            $actions = [
                [
                    'href' => route('teacher.academic.lessons.show', ['subdomain' => request()->route('subdomain'), 'lesson' => $subscription->id]),
                    'icon' => 'ri-eye-line',
                    'label' => __('teacher.academic_lessons.view_details'),
                    'shortLabel' => __('teacher.academic_lessons.view_short'),
                    'class' => 'bg-violet-600 hover:bg-violet-700 text-white',
                ],
            ];

            // Chat action for active subscriptions (Supervised)
            if ($statusValue === SubscriptionStatus::ACTIVE->value && $subscription->student && auth()->user()->hasSupervisor()) {
                $studentUser = ($subscription->student instanceof \App\Models\User) ? $subscription->student : ($subscription->student->user ?? null);
                if ($studentUser) {
                    $actions[] = [
                        'href' => route('chat.start-supervised', [
                            'subdomain' => request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy',
                            'teacher' => auth()->id(),
                            'student' => $studentUser->id,
                            'entityType' => 'academic_lesson',
                            'entityId' => $subscription->id,
                        ]),
                        'icon' => 'ri-shield-user-line',
                        'label' => __('teacher.academic_lessons.message_student'),
                        'shortLabel' => __('teacher.academic_lessons.message_short'),
                        'class' => 'bg-purple-600 hover:bg-purple-700 text-white',
                        'title' => __('chat.supervised_chat_tooltip'),
                    ];
                }
            }
        @endphp

        <x-teacher.entity-list-item
            :title="$subscription->student->name ?? __('teacher.academic_lessons.student_unspecified')"
            :status-badge="$statusConfig['text']"
            :status-class="$statusConfig['class']"
            :metadata="$metadata"
            :actions="$actions"
            :avatar="$subscription->student"
        />
    @endforeach
</x-teacher.entity-list-page>
@endsection
