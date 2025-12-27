@extends('components.layouts.teacher')

@section('title', 'الدروس الخاصة - ' . config('app.name', 'منصة إتقان'))

@section('content')
@php
    use App\Enums\SubscriptionStatus;

    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';

    $breadcrumbs = [
        ['label' => 'الدروس الخاصة'],
    ];

    $filterOptions = [
        '' => 'جميع الاشتراكات',
        SubscriptionStatus::ACTIVE->value => 'نشطة',
        SubscriptionStatus::PENDING->value => 'في انتظار الدفع',
        SubscriptionStatus::EXPIRED->value => 'منتهية',
        SubscriptionStatus::COMPLETED->value => 'مكتملة',
        SubscriptionStatus::CANCELLED->value => 'ملغية',
    ];

    $stats = [
        [
            'icon' => 'ri-user-3-line',
            'bgColor' => 'bg-violet-100',
            'iconColor' => 'text-violet-600',
            'value' => $subscriptions->total(),
            'label' => 'إجمالي الاشتراكات',
        ],
        [
            'icon' => 'ri-play-circle-line',
            'bgColor' => 'bg-green-100',
            'iconColor' => 'text-green-600',
            'value' => $subscriptions->filter(fn($s) => $s->status?->value === SubscriptionStatus::ACTIVE->value || $s->status === SubscriptionStatus::ACTIVE->value)->count(),
            'label' => 'اشتراكات نشطة',
        ],
        [
            'icon' => 'ri-time-line',
            'bgColor' => 'bg-yellow-100',
            'iconColor' => 'text-yellow-600',
            'value' => $subscriptions->filter(fn($s) => $s->status?->value === SubscriptionStatus::PENDING->value || $s->status === SubscriptionStatus::PENDING->value)->count(),
            'label' => 'في انتظار الدفع',
        ],
        [
            'icon' => 'ri-check-double-line',
            'bgColor' => 'bg-violet-100',
            'iconColor' => 'text-violet-600',
            'value' => $subscriptions->filter(fn($s) => $s->status?->value === SubscriptionStatus::COMPLETED->value || $s->status === SubscriptionStatus::COMPLETED->value)->count(),
            'label' => 'مكتملة',
        ],
    ];
@endphp

<x-teacher.entity-list-page
    title="الدروس الخاصة"
    subtitle="إدارة ومتابعة الدروس الخاصة والاشتراكات الأكاديمية"
    :items="$subscriptions"
    :stats="$stats"
    :filter-options="$filterOptions"
    :breadcrumbs="$breadcrumbs"
    theme-color="violet"
    list-title="قائمة الدروس الخاصة"
    empty-icon="ri-user-3-line"
    empty-title="لا توجد دروس خاصة"
    empty-description="لم يتم تعيين أي دروس خاصة لك بعد"
    empty-filter-description="لا توجد اشتراكات بالحالة المحددة"
    :clear-filter-route="route('teacher.academic.lessons.index', ['subdomain' => $subdomain])"
    clear-filter-text="عرض جميع الاشتراكات"
>
    @foreach($subscriptions as $subscription)
        @php
            // Handle both enum and string status
            $statusValue = is_object($subscription->status) ? $subscription->status->value : $subscription->status;
            $statusConfig = match($statusValue) {
                SubscriptionStatus::ACTIVE->value => ['class' => 'bg-green-100 text-green-800', 'text' => 'نشط'],
                SubscriptionStatus::PENDING->value => ['class' => 'bg-yellow-100 text-yellow-800', 'text' => 'في انتظار الدفع'],
                SubscriptionStatus::EXPIRED->value => ['class' => 'bg-red-100 text-red-800', 'text' => 'منتهي'],
                SubscriptionStatus::COMPLETED->value => ['class' => 'bg-violet-100 text-violet-800', 'text' => 'مكتمل'],
                SubscriptionStatus::CANCELLED->value => ['class' => 'bg-gray-100 text-gray-800', 'text' => 'ملغي'],
                default => ['class' => 'bg-gray-100 text-gray-800', 'text' => $statusValue ?? 'غير محدد']
            };

            $metadata = [];
            if ($subscription->subject) {
                $metadata[] = ['icon' => 'ri-book-line', 'text' => $subscription->subject->name];
            }
            if ($subscription->gradeLevel) {
                $metadata[] = ['icon' => 'ri-graduation-cap-line', 'text' => $subscription->gradeLevel->name];
            }
            $metadata[] = ['icon' => 'ri-calendar-line', 'text' => $subscription->created_at->format('Y/m/d')];
            if ($subscription->sessions_per_week) {
                $metadata[] = ['icon' => 'ri-time-line', 'text' => $subscription->sessions_per_week . ' جلسة/أسبوع'];
            }

            $actions = [
                [
                    'href' => route('teacher.academic.lessons.show', ['subdomain' => request()->route('subdomain'), 'lesson' => $subscription->id]),
                    'icon' => 'ri-eye-line',
                    'label' => 'عرض التفاصيل',
                    'shortLabel' => 'عرض',
                    'class' => 'bg-violet-600 hover:bg-violet-700 text-white',
                ],
            ];

            // Chat action for active subscriptions
            if ($statusValue === SubscriptionStatus::ACTIVE->value && $subscription->student) {
                $studentUser = ($subscription->student instanceof \App\Models\User) ? $subscription->student : ($subscription->student->user ?? null);
                if ($studentUser) {
                    $actions[] = [
                        'href' => route('chat.start-with', ['subdomain' => request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy', 'user' => $studentUser->id]),
                        'icon' => 'ri-message-line',
                        'label' => 'راسل الطالب',
                        'shortLabel' => 'راسل',
                        'class' => 'bg-purple-600 hover:bg-purple-700 text-white',
                        'title' => 'راسل الطالب',
                    ];
                }
            }
        @endphp

        <x-teacher.entity-list-item
            :title="$subscription->student->name ?? 'طالب غير محدد'"
            :status-badge="$statusConfig['text']"
            :status-class="$statusConfig['class']"
            :metadata="$metadata"
            :actions="$actions"
            :avatar="$subscription->student"
        />
    @endforeach
</x-teacher.entity-list-page>
@endsection
