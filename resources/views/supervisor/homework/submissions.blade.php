<x-layouts.supervisor>

@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';
@endphp

<div>
    <x-ui.breadcrumb
        :items="[
            ['label' => __('supervisor.homework.page_title'), 'url' => route('manage.homework.index', ['subdomain' => $subdomain])],
            ['label' => $homeworkTitle],
        ]"
        view-type="supervisor"
    />

    <!-- Page Header -->
    <div class="mb-6 md:mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-900">{{ $homeworkTitle }}</h1>
            @if($sessionInfo)
                <p class="mt-1 md:mt-2 text-sm md:text-base text-gray-600">{{ $sessionInfo }}</p>
            @endif
        </div>
        <a href="{{ route('manage.homework.index', ['subdomain' => $subdomain]) }}"
           class="cursor-pointer min-h-[44px] inline-flex items-center gap-2 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors text-sm font-medium">
            <i class="ri-arrow-right-line"></i>
            {{ __('supervisor.homework.back_to_list') }}
        </a>
    </div>

    @if($type === 'quran' && $session)
        {{-- Quran Homework Info (no formal submissions) --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                <i class="ri-book-read-line text-green-500"></i>
                {{ __('supervisor.homework.quran_homework_details') }}
            </h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <span class="text-sm text-gray-500">{{ __('supervisor.homework.teacher') }}</span>
                    <p class="font-medium text-gray-900">{{ $session->quranTeacher?->name ?? '-' }}</p>
                </div>
                <div>
                    <span class="text-sm text-gray-500">{{ __('supervisor.homework.student') }}</span>
                    <p class="font-medium text-gray-900">{{ $session->student?->name ?? '-' }}</p>
                </div>
                @if($session->homework_details)
                    <div class="sm:col-span-2">
                        <span class="text-sm text-gray-500">{{ __('supervisor.homework.details') }}</span>
                        <p class="text-gray-700 mt-1">{{ $session->homework_details }}</p>
                    </div>
                @endif
            </div>
        </div>
    @endif

    @if($homework && ($type === 'academic' || $type === 'interactive'))
        {{-- Homework Details Card --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                <i class="ri-file-text-line text-violet-500"></i>
                {{ __('supervisor.homework.homework_details') }}
            </h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @if($homework->description)
                    <div class="sm:col-span-2 lg:col-span-3">
                        <span class="text-sm text-gray-500">{{ __('supervisor.homework.description') }}</span>
                        <p class="text-gray-700 mt-1">{{ $homework->description }}</p>
                    </div>
                @endif
                @if($homework->instructions)
                    <div class="sm:col-span-2 lg:col-span-3">
                        <span class="text-sm text-gray-500">{{ __('supervisor.homework.instructions') }}</span>
                        <p class="text-gray-700 mt-1">{{ $homework->instructions }}</p>
                    </div>
                @endif
                <div>
                    <span class="text-sm text-gray-500">{{ __('supervisor.homework.max_score') }}</span>
                    <p class="font-medium text-gray-900">{{ $homework->max_score ?? 10 }}</p>
                </div>
                @if($homework->due_date)
                    <div>
                        <span class="text-sm text-gray-500">{{ __('supervisor.homework.due_date') }}</span>
                        <p class="font-medium {{ $homework->due_date->isPast() ? 'text-red-600' : 'text-gray-900' }}">
                            {{ $homework->due_date->format('Y-m-d H:i') }}
                        </p>
                    </div>
                @endif
                <div>
                    <span class="text-sm text-gray-500">{{ __('supervisor.homework.teacher') }}</span>
                    <p class="font-medium text-gray-900">{{ $homework->teacher?->name ?? '-' }}</p>
                </div>
            </div>
        </div>
    @endif

    {{-- Submissions Table --}}
    @if($submissions->count() > 0)
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            <div class="px-4 md:px-6 py-3 md:py-4 border-b border-gray-200">
                <h2 class="text-base md:text-lg font-semibold text-gray-900">
                    {{ __('supervisor.homework.submissions_title') }} ({{ $submissions->count() }})
                </h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-600">
                        <tr>
                            <th class="px-4 md:px-6 py-3 text-start font-medium">{{ __('supervisor.homework.student') }}</th>
                            <th class="px-4 md:px-6 py-3 text-start font-medium">{{ __('supervisor.homework.submitted_at') }}</th>
                            <th class="px-4 md:px-6 py-3 text-start font-medium hidden md:table-cell">{{ __('supervisor.homework.files') }}</th>
                            <th class="px-4 md:px-6 py-3 text-start font-medium">{{ __('supervisor.homework.grade') }}</th>
                            <th class="px-4 md:px-6 py-3 text-start font-medium hidden lg:table-cell">{{ __('supervisor.homework.feedback') }}</th>
                            <th class="px-4 md:px-6 py-3 text-start font-medium">{{ __('supervisor.homework.status') }}</th>
                            <th class="px-4 md:px-6 py-3 text-start font-medium">{{ __('supervisor.homework.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($submissions as $submission)
                            @php
                                $statusValue = $submission->submission_status instanceof \App\Enums\HomeworkSubmissionStatus
                                    ? $submission->submission_status->value
                                    : $submission->submission_status;
                                $statusBadges = [
                                    'pending' => 'bg-gray-100 text-gray-700',
                                    'draft' => 'bg-gray-100 text-gray-700',
                                    'submitted' => 'bg-blue-100 text-blue-700',
                                    'late' => 'bg-red-100 text-red-700',
                                    'graded' => 'bg-green-100 text-green-700',
                                    'revision_requested' => 'bg-yellow-100 text-yellow-700',
                                    'resubmitted' => 'bg-blue-100 text-blue-700',
                                ];
                                $files = $submission->submission_files ?? [];
                            @endphp
                            <tr class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-4 md:px-6 py-3">
                                    <div class="flex items-center gap-2">
                                        <div class="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center text-xs font-bold text-gray-600">
                                            {{ mb_substr($submission->student?->name ?? '?', 0, 1) }}
                                        </div>
                                        <div>
                                            <div class="font-medium text-gray-900">{{ $submission->student?->name ?? '-' }}</div>
                                            @if($submission->is_late)
                                                <span class="text-xs text-red-600 font-medium">{{ __('supervisor.homework.late') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 md:px-6 py-3 text-gray-600">
                                    {{ $submission->submitted_at?->format('Y-m-d H:i') ?? '-' }}
                                </td>
                                <td class="px-4 md:px-6 py-3 hidden md:table-cell text-gray-600">
                                    @if(count($files) > 0)
                                        <span class="flex items-center gap-1">
                                            <i class="ri-attachment-line text-gray-400"></i>
                                            {{ count($files) }}
                                        </span>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="px-4 md:px-6 py-3">
                                    @if($submission->score !== null)
                                        <span class="font-semibold text-gray-900">{{ number_format($submission->score, 1) }}</span>
                                        <span class="text-xs text-gray-500">/{{ $submission->max_score ?? 10 }}</span>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="px-4 md:px-6 py-3 hidden lg:table-cell">
                                    @if($submission->teacher_feedback)
                                        <span class="text-gray-600 max-w-[200px] truncate block">{{ $submission->teacher_feedback }}</span>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="px-4 md:px-6 py-3">
                                    <span class="px-2 py-1 text-xs rounded-full {{ $statusBadges[$statusValue] ?? 'bg-gray-100 text-gray-700' }}">
                                        {{ $submission->submission_status instanceof \App\Enums\HomeworkSubmissionStatus ? $submission->submission_status->label() : $statusValue }}
                                    </span>
                                </td>
                                <td class="px-4 md:px-6 py-3">
                                    @if($submission->score === null && in_array($statusValue, ['submitted', 'late', 'resubmitted']))
                                        <button type="button"
                                            onclick="window.dispatchEvent(new CustomEvent('open-modal-grade-{{ $submission->id }}'))"
                                            class="cursor-pointer inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium rounded-lg bg-green-50 text-green-700 hover:bg-green-100 transition-colors">
                                            <i class="ri-edit-line"></i>
                                            {{ __('supervisor.homework.grade_btn') }}
                                        </button>
                                    @elseif($submission->score !== null)
                                        <span class="text-xs text-green-600 font-medium flex items-center gap-1">
                                            <i class="ri-checkbox-circle-line"></i>
                                            {{ __('supervisor.homework.graded') }}
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Grade Modals --}}
        @foreach($submissions as $submission)
            @if($submission->score === null && in_array($submission->submission_status instanceof \App\Enums\HomeworkSubmissionStatus ? $submission->submission_status->value : $submission->submission_status, ['submitted', 'late', 'resubmitted']))
                <x-responsive.modal id="grade-{{ $submission->id }}" :title="__('supervisor.homework.grade_submission')" size="sm">
                    <form method="POST" action="{{ route('manage.homework.grade', ['subdomain' => $subdomain, 'submission' => $submission->id]) }}">
                        @csrf
                        <input type="hidden" name="type" value="{{ $type }}">
                        <div class="space-y-4">
                            <p class="text-sm text-gray-600">
                                {{ __('supervisor.homework.grading_student', ['name' => $submission->student?->name ?? '-']) }}
                            </p>
                            <div>
                                <label for="score_{{ $submission->id }}" class="block text-sm font-medium text-gray-700 mb-1">
                                    {{ __('supervisor.homework.score') }} (0-10)
                                </label>
                                <input type="number" name="score" id="score_{{ $submission->id }}"
                                       min="0" max="10" step="0.5" required
                                       class="min-h-[44px] w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-green-500 focus:border-green-500"
                                       placeholder="0-10">
                            </div>
                            <div>
                                <label for="feedback_{{ $submission->id }}" class="block text-sm font-medium text-gray-700 mb-1">
                                    {{ __('supervisor.homework.teacher_feedback') }}
                                </label>
                                <textarea name="teacher_feedback" id="feedback_{{ $submission->id }}" rows="3"
                                          class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-green-500 focus:border-green-500"
                                          placeholder="{{ __('supervisor.homework.feedback_placeholder') }}"></textarea>
                            </div>
                        </div>
                        <x-slot:footer>
                            <div class="flex items-center justify-end gap-3">
                                <button type="button" @click="open = false"
                                    class="cursor-pointer px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">
                                    {{ __('common.cancel') }}
                                </button>
                                <button type="submit"
                                    class="cursor-pointer px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700">
                                    {{ __('supervisor.homework.submit_grade') }}
                                </button>
                            </div>
                        </x-slot:footer>
                    </form>
                </x-responsive.modal>
            @endif
        @endforeach
    @elseif($type !== 'quran')
        {{-- No Submissions --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8 md:p-12 text-center">
            <div class="w-14 h-14 md:w-16 md:h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3 md:mb-4">
                <i class="ri-inbox-line text-xl md:text-2xl text-gray-400"></i>
            </div>
            <h3 class="text-base md:text-lg font-bold text-gray-900 mb-1 md:mb-2">{{ __('supervisor.homework.no_submissions') }}</h3>
            <p class="text-gray-600 text-xs md:text-sm">{{ __('supervisor.homework.no_submissions_description') }}</p>
        </div>
    @endif
</div>

{{-- Flash Messages --}}
@if(session('success'))
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
        class="fixed bottom-4 start-4 z-50 bg-green-600 text-white px-4 py-3 rounded-lg shadow-lg text-sm font-medium flex items-center gap-2">
        <i class="ri-checkbox-circle-line"></i>
        {{ session('success') }}
        <button @click="show = false" class="cursor-pointer ms-2 hover:opacity-80"><i class="ri-close-line"></i></button>
    </div>
@endif

</x-layouts.supervisor>
