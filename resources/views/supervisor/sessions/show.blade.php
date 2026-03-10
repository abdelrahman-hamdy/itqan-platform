<x-layouts.supervisor>

@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';
    $t = 'supervisor.sessions.';
    $status = $session->status;
    $isLive = in_array($status, [\App\Enums\SessionStatus::READY, \App\Enums\SessionStatus::ONGOING]);
    $isFinal = $status->isFinal();

    // Session type info
    $typeConfig = match($sessionType) {
        'academic' => ['label' => __($t.'type_academic'), 'icon' => 'ri-graduation-cap-line', 'bg' => 'bg-violet-50', 'text' => 'text-violet-600'],
        'interactive' => ['label' => __($t.'type_interactive'), 'icon' => 'ri-video-chat-line', 'bg' => 'bg-blue-50', 'text' => 'text-blue-600'],
        default => ['label' => __($t.'type_quran'), 'icon' => 'ri-book-read-line', 'bg' => 'bg-green-50', 'text' => 'text-green-600'],
    };

    // Teacher name
    $teacherName = match($sessionType) {
        'academic' => $session->academicTeacher?->user?->name ?? '-',
        'interactive' => $session->course?->assignedTeacher?->user?->name ?? '-',
        default => $session->quranTeacher?->name ?? '-',
    };

    // Student / group name
    $studentName = match($sessionType) {
        'academic' => $session->student?->name ?? '-',
        'interactive' => $session->course?->title ?? '-',
        default => $session->circle?->name ?? $session->student?->name ?? '-',
    };
@endphp

<div x-data="sessionDetail()" class="space-y-6">
    {{-- Breadcrumb --}}
    <x-ui.breadcrumb
        :items="[
            ['label' => __($t.'breadcrumb'), 'route' => route('manage.sessions.index', ['subdomain' => $subdomain])],
            ['label' => $session->session_code ?? $typeConfig['label']],
        ]"
        view-type="supervisor"
    />

    {{-- Session Header --}}
    <x-sessions.session-header :session="$session" view-type="supervisor" />

    {{-- Action Bar --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
        <div class="flex flex-wrap items-center gap-3">
            {{-- Observer / Participant Mode Toggle --}}
            @if($canObserve && $isLive)
                <div class="flex items-center bg-gray-100 rounded-lg p-1">
                    <a href="{{ route('manage.sessions.show', ['subdomain' => $subdomain, 'sessionType' => $sessionType, 'sessionId' => $session->id, 'mode' => 'observer']) }}"
                       class="px-3 py-1.5 text-xs font-medium rounded-md transition-colors {{ $mode === 'observer' ? 'bg-white shadow text-indigo-700' : 'text-gray-600 hover:text-gray-800' }}">
                        <i class="ri-eye-line me-1"></i>
                        {{ __('supervisor.observation.observer_mode') }}
                    </a>
                    <a href="{{ route('manage.sessions.show', ['subdomain' => $subdomain, 'sessionType' => $sessionType, 'sessionId' => $session->id, 'mode' => 'participant']) }}"
                       class="px-3 py-1.5 text-xs font-medium rounded-md transition-colors {{ $mode === 'participant' ? 'bg-white shadow text-indigo-700' : 'text-gray-600 hover:text-gray-800' }}">
                        <i class="ri-video-chat-line me-1"></i>
                        {{ __('supervisor.observation.participant_mode') }}
                    </a>
                </div>
            @endif

            {{-- Edit button --}}
            @if(!$isFinal)
                <button @click="showEditModal = true" class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-medium rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-700 transition-colors">
                    <i class="ri-edit-line"></i>
                    {{ __($t.'edit_session') }}
                </button>
            @endif

            {{-- Cancel button --}}
            @if($status->canCancel())
                <button @click="showCancelModal = true" class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-medium rounded-lg bg-red-50 hover:bg-red-100 text-red-600 transition-colors">
                    <i class="ri-close-circle-line"></i>
                    {{ __($t.'cancel_session') }}
                </button>
            @endif

            {{-- View in Panel --}}
            @if($filamentUrl)
                <a href="{{ $filamentUrl }}" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-medium rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-700 transition-colors ms-auto">
                    <i class="ri-external-link-line"></i>
                    {{ __($t.'view_in_panel') }}
                </a>
            @endif
        </div>
    </div>

    {{-- Meeting Interface --}}
    @if($isLive)
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            @if($mode === 'observer' && $canObserve)
                <x-meetings.observer-interface :session="$session" :session-type="$sessionType" />
            @else
                <x-meetings.livekit-interface :session="$session" user-type="supervisor" />
            @endif
        </div>
    @endif

    {{-- Content Grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main Content (2/3) --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Participants --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                <h3 class="text-base font-semibold text-gray-900 mb-4 flex items-center gap-2">
                    <i class="ri-group-line text-gray-400"></i>
                    {{ __($t.'participants') }}
                </h3>
                <div class="space-y-3">
                    {{-- Teacher --}}
                    <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                        <div class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center">
                            <i class="ri-user-star-line text-indigo-600"></i>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-900">{{ $teacherName }}</p>
                            <p class="text-xs text-gray-500">{{ __('supervisor.observation.role_teacher') }}</p>
                        </div>
                    </div>

                    {{-- Students --}}
                    @if($sessionType === 'interactive' && $session->course?->enrolledStudents)
                        @foreach($session->course->enrolledStudents as $enrollment)
                            <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                                <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
                                    <i class="ri-user-line text-blue-600"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900">{{ $enrollment->student?->user?->name ?? '-' }}</p>
                                    <p class="text-xs text-gray-500">{{ __('supervisor.observation.role_student') }}</p>
                                </div>
                            </div>
                        @endforeach
                    @elseif($sessionType === 'quran' && $session->circle)
                        @foreach($session->circle->students ?? [] as $student)
                            <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                                <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
                                    <i class="ri-user-line text-blue-600"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900">{{ $student->name ?? '-' }}</p>
                                    <p class="text-xs text-gray-500">{{ __('supervisor.observation.role_student') }}</p>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                            <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
                                <i class="ri-user-line text-blue-600"></i>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ $studentName }}</p>
                                <p class="text-xs text-gray-500">{{ __('supervisor.observation.role_student') }}</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Session Content --}}
            @if($session->lesson_content)
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                    <h3 class="text-base font-semibold text-gray-900 mb-3 flex items-center gap-2">
                        <i class="ri-file-text-line text-gray-400"></i>
                        {{ __($t.'session_content') }}
                    </h3>
                    <div class="prose prose-sm max-w-none text-gray-700">
                        {!! nl2br(e($session->lesson_content)) !!}
                    </div>
                </div>
            @endif

            {{-- Homework --}}
            @if($sessionType === 'quran')
                <x-sessions.homework-display :session="$session" view-type="supervisor" />
            @endif

            {{-- Learning Outcomes (Academic) --}}
            @if($sessionType === 'academic' && $session->learning_outcomes)
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                    <h3 class="text-base font-semibold text-gray-900 mb-3 flex items-center gap-2">
                        <i class="ri-lightbulb-line text-gray-400"></i>
                        {{ __($t.'learning_outcomes') }}
                    </h3>
                    <div class="prose prose-sm max-w-none text-gray-700">
                        {!! nl2br(e($session->learning_outcomes)) !!}
                    </div>
                </div>
            @endif
        </div>

        {{-- Sidebar (1/3) --}}
        <div class="space-y-6">
            {{-- Session Details Card --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                <h3 class="text-base font-semibold text-gray-900 mb-4 flex items-center gap-2">
                    <i class="ri-information-line text-gray-400"></i>
                    {{ __($t.'session_info') }}
                </h3>
                <dl class="space-y-3">
                    <div class="flex justify-between items-center">
                        <dt class="text-xs text-gray-500">{{ __($t.'col_status') }}</dt>
                        <dd><x-sessions.status-badge :status="$status" size="sm" /></dd>
                    </div>
                    @if($session->session_code)
                    <div class="flex justify-between items-center">
                        <dt class="text-xs text-gray-500">{{ __($t.'col_session_code') }}</dt>
                        <dd class="text-sm font-mono text-gray-700">{{ $session->session_code }}</dd>
                    </div>
                    @endif
                    <div class="flex justify-between items-center">
                        <dt class="text-xs text-gray-500">{{ __($t.'col_type') }}</dt>
                        <dd class="flex items-center gap-1">
                            <i class="{{ $typeConfig['icon'] }} text-xs {{ $typeConfig['text'] }}"></i>
                            <span class="text-sm text-gray-700">{{ $typeConfig['label'] }}</span>
                        </dd>
                    </div>
                    @if($session->scheduled_at)
                    <div class="flex justify-between items-center">
                        <dt class="text-xs text-gray-500">{{ __($t.'col_scheduled') }}</dt>
                        <dd class="text-sm text-gray-700">{{ toAcademyTimezone($session->scheduled_at)->translatedFormat('d M Y - h:i A') }}</dd>
                    </div>
                    @endif
                    @if($session->duration_minutes)
                    <div class="flex justify-between items-center">
                        <dt class="text-xs text-gray-500">{{ __($t.'col_duration') }}</dt>
                        <dd class="text-sm text-gray-700">{{ __($t.'duration_minutes', ['count' => $session->duration_minutes]) }}</dd>
                    </div>
                    @endif
                    @if($session->started_at)
                    <div class="flex justify-between items-center">
                        <dt class="text-xs text-gray-500">{{ __('enums.session_status.ongoing') }}</dt>
                        <dd class="text-sm text-gray-700">{{ toAcademyTimezone($session->started_at)->translatedFormat('h:i A') }}</dd>
                    </div>
                    @endif
                    @if($session->ended_at)
                    <div class="flex justify-between items-center">
                        <dt class="text-xs text-gray-500">{{ __('enums.session_status.completed') }}</dt>
                        <dd class="text-sm text-gray-700">{{ toAcademyTimezone($session->ended_at)->translatedFormat('h:i A') }}</dd>
                    </div>
                    @endif
                </dl>
            </div>

            {{-- Supervisor Notes --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                <h3 class="text-base font-semibold text-gray-900 mb-3 flex items-center gap-2">
                    <i class="ri-sticky-note-line text-gray-400"></i>
                    {{ __($t.'supervisor_notes') }}
                </h3>
                <textarea
                    x-model="supervisorNotes"
                    rows="4"
                    placeholder="{{ __($t.'notes_placeholder') }}"
                    class="w-full text-sm rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 resize-none"
                ></textarea>
                <div class="mt-2 flex items-center justify-between">
                    <button @click="saveNotes()" :disabled="savingNotes"
                        class="px-3 py-1.5 text-xs font-medium rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white transition-colors disabled:opacity-50">
                        <span x-show="!savingNotes">{{ __($t.'save_notes') }}</span>
                        <span x-show="savingNotes" x-cloak>{{ __('supervisor.observation.saving') }}...</span>
                    </button>
                    <span x-show="notesSaved" x-transition x-cloak class="text-xs text-green-600">
                        <i class="ri-check-line"></i> {{ __($t.'notes_saved') }}
                    </span>
                </div>
            </div>

            {{-- Meeting Info --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                <h3 class="text-base font-semibold text-gray-900 mb-3 flex items-center gap-2">
                    <i class="ri-video-line text-gray-400"></i>
                    {{ __($t.'meeting_info') }}
                </h3>
                <div class="space-y-2 text-sm">
                    @if($isLive)
                        <div class="flex items-center gap-2 text-green-600">
                            <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                            {{ __($t.'meeting_active') }}
                        </div>
                    @elseif($isFinal)
                        <div class="flex items-center gap-2 text-gray-500">
                            <i class="ri-stop-circle-line"></i>
                            {{ __($t.'meeting_ended') }}
                        </div>
                    @else
                        <div class="flex items-center gap-2 text-gray-500">
                            <i class="ri-time-line"></i>
                            {{ __($t.'meeting_not_started') }}
                        </div>
                    @endif
                    @if($session->meeting_room_name)
                        <p class="text-xs text-gray-400 font-mono">{{ $session->meeting_room_name }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Edit Modal --}}
    <div x-show="showEditModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div x-show="showEditModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-black/50" @click="showEditModal = false"></div>

            <div x-show="showEditModal" x-transition class="relative bg-white rounded-xl shadow-xl max-w-lg w-full p-6 z-10">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ __($t.'edit_modal_title') }}</h3>

                <form @submit.prevent="submitEdit()">
                    <div class="space-y-4">
                        {{-- Status --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __($t.'edit_status') }}</label>
                            <select x-model="editForm.status" class="w-full text-sm rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                @foreach(\App\Enums\SessionStatus::cases() as $statusEnum)
                                    <option value="{{ $statusEnum->value }}">{{ $statusEnum->label() }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Scheduled At --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __($t.'edit_scheduled_at') }}</label>
                            <input type="datetime-local" x-model="editForm.scheduled_at"
                                class="w-full text-sm rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                        </div>

                        {{-- Duration --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __($t.'edit_duration') }}</label>
                            <input type="number" x-model="editForm.duration_minutes" min="15" max="300"
                                class="w-full text-sm rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                        </div>

                        {{-- Notes --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __($t.'edit_notes') }}</label>
                            <textarea x-model="editForm.supervisor_notes" rows="3"
                                class="w-full text-sm rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 resize-none"></textarea>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-3">
                        <button type="button" @click="showEditModal = false"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
                            {{ __('supervisor.common.back_to_list') }}
                        </button>
                        <button type="submit" :disabled="submittingEdit"
                            class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition-colors disabled:opacity-50">
                            <span x-show="!submittingEdit">{{ __($t.'edit_save') }}</span>
                            <span x-show="submittingEdit" x-cloak>{{ __('supervisor.observation.saving') }}...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Cancel Confirmation Modal --}}
    <div x-show="showCancelModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div x-show="showCancelModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-black/50" @click="showCancelModal = false"></div>

            <div x-show="showCancelModal" x-transition class="relative bg-white rounded-xl shadow-xl max-w-md w-full p-6 z-10">
                <div class="text-center mb-4">
                    <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-3">
                        <i class="ri-error-warning-line text-2xl text-red-600"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900">{{ __($t.'cancel_confirm_title') }}</h3>
                    <p class="text-sm text-gray-500 mt-1">{{ __($t.'cancel_confirm_message') }}</p>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __($t.'cancel_reason_label') }}</label>
                    <textarea x-model="cancellationReason" rows="3" required
                        class="w-full text-sm rounded-lg border-gray-300 focus:border-red-500 focus:ring-red-500 resize-none"></textarea>
                </div>

                <div class="flex justify-end gap-3">
                    <button @click="showCancelModal = false"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
                        {{ __('supervisor.common.back_to_list') }}
                    </button>
                    <button @click="submitCancel()" :disabled="submittingCancel || !cancellationReason.trim()"
                        class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg transition-colors disabled:opacity-50">
                        <span x-show="!submittingCancel">{{ __($t.'cancel_session') }}</span>
                        <span x-show="submittingCancel" x-cloak>{{ __('supervisor.observation.saving') }}...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@php
    $updateUrl = route('manage.sessions.update', ['subdomain' => $subdomain, 'sessionType' => $sessionType, 'sessionId' => $session->id]);
    $cancelUrl = route('manage.sessions.cancel', ['subdomain' => $subdomain, 'sessionType' => $sessionType, 'sessionId' => $session->id]);
    $initialNotes = $session->supervisor_notes ?? '';
    $initialStatus = $session->status->value;
    $initialScheduledAt = $session->scheduled_at ? toAcademyTimezone($session->scheduled_at)->format('Y-m-d\TH:i') : '';
    $initialDuration = $session->duration_minutes ?? '';
@endphp

<script>
function sessionDetail() {
    return {
        showEditModal: false,
        showCancelModal: false,
        submittingEdit: false,
        submittingCancel: false,
        savingNotes: false,
        notesSaved: false,
        supervisorNotes: @json($initialNotes),
        cancellationReason: '',

        editForm: {
            status: @json($initialStatus),
            scheduled_at: @json($initialScheduledAt),
            duration_minutes: @json($initialDuration),
            supervisor_notes: @json($initialNotes),
        },

        async submitEdit() {
            this.submittingEdit = true;
            try {
                const response = await fetch(@json($updateUrl), {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(this.editForm),
                });
                const data = await response.json();
                if (response.ok) {
                    window.location.reload();
                } else {
                    alert(data.message || 'Error');
                }
            } catch (e) {
                alert('Error: ' + e.message);
            } finally {
                this.submittingEdit = false;
            }
        },

        async submitCancel() {
            if (!this.cancellationReason.trim()) return;
            this.submittingCancel = true;
            try {
                const response = await fetch(@json($cancelUrl), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ cancellation_reason: this.cancellationReason }),
                });
                const data = await response.json();
                if (response.ok) {
                    window.location.reload();
                } else {
                    alert(data.message || 'Error');
                }
            } catch (e) {
                alert('Error: ' + e.message);
            } finally {
                this.submittingCancel = false;
            }
        },

        async saveNotes() {
            this.savingNotes = true;
            this.notesSaved = false;
            try {
                const response = await fetch(@json($updateUrl), {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ supervisor_notes: this.supervisorNotes }),
                });
                if (response.ok) {
                    this.notesSaved = true;
                    setTimeout(() => this.notesSaved = false, 3000);
                }
            } catch (e) {
                alert('Error: ' + e.message);
            } finally {
                this.savingNotes = false;
            }
        },

        init() {
            // Open edit modal if hash is #edit
            if (window.location.hash === '#edit') {
                this.showEditModal = true;
            }
            if (window.location.hash === '#cancel') {
                this.showCancelModal = true;
            }
        }
    }
}
</script>

</x-layouts.supervisor>
