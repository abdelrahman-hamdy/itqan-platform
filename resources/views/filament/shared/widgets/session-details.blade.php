@php
    use App\Enums\CalendarSessionType;
    use App\Enums\SessionStatus;
    use App\Services\AcademyContextService;

    $timezone = AcademyContextService::getTimezone();
    $scheduledAt = $session->scheduled_at?->setTimezone($timezone);
    $status = $session->status instanceof SessionStatus
        ? $session->status
        : SessionStatus::tryFrom($session->status);
@endphp

<div class="space-y-6 p-4">
    {{-- Session Type Badge --}}
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-2">
            <span
                class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-sm font-medium text-white"
                style="background-color: {{ $type->hexColor() }}"
            >
                <x-dynamic-component :component="$type->icon()" class="w-4 h-4" />
                {{ $type->fallbackLabel() }}
            </span>
        </div>

        @if($status)
            <span
                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium text-white"
                style="background-color: {{ $status->hexColor() }}"
            >
                {{ $status->label() }}
            </span>
        @endif
    </div>

    {{-- Session Details Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        {{-- Date & Time --}}
        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
            <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">
                {{ __('calendar.session.date_time') ?: 'التاريخ والوقت' }}
            </h4>
            <div class="space-y-1">
                @if($scheduledAt)
                    <p class="text-gray-900 dark:text-white font-medium">
                        {{ $scheduledAt->translatedFormat('l j F Y') }}
                    </p>
                    <p class="text-gray-600 dark:text-gray-300">
                        {{ $scheduledAt->format('h:i A') }}
                    </p>
                @else
                    <p class="text-gray-500">{{ __('calendar.session.not_scheduled') ?: 'غير مجدولة' }}</p>
                @endif
            </div>
        </div>

        {{-- Duration --}}
        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
            <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">
                {{ __('calendar.session.duration') ?: 'المدة' }}
            </h4>
            <p class="text-gray-900 dark:text-white font-medium">
                {{ $session->duration_minutes ?? 60 }} {{ __('calendar.session.minutes') ?: 'دقيقة' }}
            </p>
        </div>

        {{-- Student/Circle --}}
        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
            <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">
                @if($type === CalendarSessionType::QURAN_GROUP)
                    {{ __('calendar.session.circle') ?: 'الحلقة' }}
                @elseif($type === CalendarSessionType::INTERACTIVE_COURSE)
                    {{ __('calendar.session.course') ?: 'الدورة' }}
                @else
                    {{ __('calendar.session.student') ?: 'الطالب' }}
                @endif
            </h4>
            <p class="text-gray-900 dark:text-white font-medium">
                @switch($type)
                    @case(CalendarSessionType::QURAN_INDIVIDUAL)
                        {{ $session->student?->name ?? '-' }}
                        @break
                    @case(CalendarSessionType::QURAN_GROUP)
                        {{ $session->circle?->name_ar ?? $session->circle?->name ?? '-' }}
                        @break
                    @case(CalendarSessionType::QURAN_TRIAL)
                        {{ $session->trialRequest?->student_name ?? $session->student?->name ?? '-' }}
                        @break
                    @case(CalendarSessionType::ACADEMIC_PRIVATE)
                        {{ $session->student?->name ?? '-' }}
                        @break
                    @case(CalendarSessionType::INTERACTIVE_COURSE)
                        {{ $session->course?->title ?? '-' }}
                        @break
                @endswitch
            </p>
        </div>

        {{-- Subject (for Academic types) --}}
        @if($type->isAcademic())
            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">
                    {{ __('calendar.session.subject') ?: 'المادة' }}
                </h4>
                <p class="text-gray-900 dark:text-white font-medium">
                    @if($type === CalendarSessionType::ACADEMIC_PRIVATE)
                        {{ $session->academicIndividualLesson?->subject?->name_ar ?? $session->academicIndividualLesson?->subject?->name ?? '-' }}
                    @else
                        {{ $session->course?->subject?->name_ar ?? $session->course?->subject?->name ?? '-' }}
                    @endif
                </p>
            </div>
        @endif
    </div>

    {{-- Session Title/Description --}}
    @if($session->title || $session->description)
        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
            @if($session->title)
                <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-1">
                    {{ $session->title }}
                </h4>
            @endif
            @if($session->description)
                <p class="text-gray-600 dark:text-gray-300 text-sm">
                    {{ $session->description }}
                </p>
            @endif
        </div>
    @endif

    {{-- Quran-specific details --}}
    @if($type->isQuran() && $type !== CalendarSessionType::QURAN_TRIAL)
        @if($session->current_surah || $session->current_page)
            <div class="bg-emerald-50 dark:bg-emerald-900/20 rounded-lg p-4">
                <h4 class="text-sm font-medium text-emerald-700 dark:text-emerald-300 mb-2">
                    {{ __('calendar.session.quran_progress') ?: 'تقدم القرآن' }}
                </h4>
                <div class="grid grid-cols-2 gap-4">
                    @if($session->current_surah)
                        <div>
                            <span class="text-sm text-emerald-600 dark:text-emerald-400">
                                {{ __('calendar.session.surah') ?: 'السورة' }}:
                            </span>
                            <span class="font-medium text-emerald-800 dark:text-emerald-200 ms-1">
                                {{ $session->current_surah }}
                            </span>
                        </div>
                    @endif
                    @if($session->current_page)
                        <div>
                            <span class="text-sm text-emerald-600 dark:text-emerald-400">
                                {{ __('calendar.session.page') ?: 'الصفحة' }}:
                            </span>
                            <span class="font-medium text-emerald-800 dark:text-emerald-200 ms-1">
                                {{ $session->current_page }}
                            </span>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    @endif

    {{-- Meeting Link (if available) --}}
    @if($scheduledAt && !$scheduledAt->isPast() && $status?->canStart() && $session->meeting_link)
        <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
            <a
                href="{{ $session->meeting_link }}"
                target="_blank"
                class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors w-full justify-center"
            >
                <x-heroicon-m-video-camera class="w-5 h-5" />
                {{ __('calendar.session.join_meeting') ?: 'انضمام للاجتماع' }}
            </a>
        </div>
    @endif
</div>
