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

    // Resolve teacher User object
    $teacherUser = match($sessionType) {
        'academic' => $session->academicTeacher?->user,
        'interactive' => $session->course?->assignedTeacher?->user,
        default => $session->quranTeacher,
    };
    $teacherName = $teacherUser?->name ?? '-';

    // Collect student User objects for participants list
    $studentUsers = collect();
    if ($sessionType === 'interactive' && $session->course?->enrolledStudents) {
        $studentUsers = $session->course->enrolledStudents->map(fn ($enrollment) => $enrollment->student?->user)->filter();
    } elseif ($sessionType === 'quran' && $session->circle) {
        $studentUsers = collect($session->circle->students ?? []);
    } elseif ($sessionType === 'academic') {
        $studentUsers = collect([$session->student])->filter();
    } else {
        $studentUsers = collect([$session->student])->filter();
    }

    // Check user roles
    $currentUser = auth()->user();
    $isSupervisor = $currentUser->hasRole('supervisor');
    $isAdmin = $currentUser->isAdmin(); // admin or super_admin
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

    {{-- Cancellation Reason (shown when session is cancelled) --}}
    @if($session->status === \App\Enums\SessionStatus::CANCELLED && $session->cancellation_reason)
        <div class="bg-red-50 border border-red-200 rounded-xl p-4">
            <div class="flex items-start gap-3">
                <i class="ri-error-warning-line text-red-500 text-lg mt-0.5"></i>
                <div>
                    <p class="text-sm font-semibold text-red-900">{{ __($t.'cancel_reason_label') }}</p>
                    <p class="text-sm text-red-800 mt-1">{{ $session->cancellation_reason }}</p>
                    @if($session->cancelledBy)
                        <p class="text-xs text-red-500 mt-1">{{ $session->cancelledBy->name }} &middot; {{ toAcademyTimezone($session->cancelled_at)->translatedFormat('d M Y - h:i A') }}</p>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- Action Bar --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
        <div class="flex flex-wrap items-center gap-3">
            {{-- Observer / Participant Mode Toggle --}}
            @if($canObserve && $isLive)
                <div class="flex items-center bg-gray-100 rounded-lg p-1">
                    <a href="{{ route('manage.sessions.show', ['subdomain' => $subdomain, 'sessionType' => $sessionType, 'sessionId' => $session->id, 'mode' => 'observer']) }}"
                       class="px-3 py-1.5 text-xs font-medium rounded-md transition-colors cursor-pointer {{ $mode === 'observer' ? 'bg-white shadow text-indigo-700' : 'text-gray-600 hover:text-gray-800' }}">
                        <i class="ri-eye-line me-1"></i>
                        {{ __('supervisor.observation.observer_mode') }}
                    </a>
                    <a href="{{ route('manage.sessions.show', ['subdomain' => $subdomain, 'sessionType' => $sessionType, 'sessionId' => $session->id, 'mode' => 'participant']) }}"
                       class="px-3 py-1.5 text-xs font-medium rounded-md transition-colors cursor-pointer {{ $mode === 'participant' ? 'bg-white shadow text-indigo-700' : 'text-gray-600 hover:text-gray-800' }}">
                        <i class="ri-video-chat-line me-1"></i>
                        {{ __('supervisor.observation.participant_mode') }}
                    </a>
                </div>
            @endif

            {{-- Cancel button --}}
            @if($status->canCancel())
                <button @click="showCancelModal = true" class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-medium rounded-lg bg-red-50 hover:bg-red-100 text-red-600 transition-colors cursor-pointer">
                    <i class="ri-close-circle-line"></i>
                    {{ __($t.'cancel_session') }}
                </button>
            @endif

            {{-- View in Panel --}}
            @if($filamentUrl)
                <a href="{{ $filamentUrl }}" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-medium rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-700 transition-colors cursor-pointer ms-auto">
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
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5" x-data="{ showAll: false }">
                <h3 class="text-base font-semibold text-gray-900 mb-4">
                    {{ __($t.'participants') }}
                    <span class="text-xs text-gray-400 font-normal">({{ 1 + $studentUsers->count() }})</span>
                </h3>
                <div class="space-y-3">
                    {{-- Teacher --}}
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div class="flex items-center gap-3">
                            <x-avatar :user="$teacherUser" size="xs" />
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ $teacherName }}</p>
                                <p class="text-xs text-gray-500">{{ __('supervisor.observation.role_teacher') }}</p>
                            </div>
                        </div>
                        @if($isSupervisor && $teacherUser)
                            <a href="{{ route('chats', ['subdomain' => $subdomain]) }}" class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium rounded-md bg-blue-50 text-blue-600 hover:bg-blue-100 transition-colors cursor-pointer">
                                <i class="ri-message-3-line"></i>
                                {{ __($t.'message') }}
                            </a>
                        @endif
                    </div>

                    {{-- Students --}}
                    @foreach($studentUsers as $index => $studentUser)
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg"
                             x-show="showAll || {{ $index }} < 10" x-transition>
                            <div class="flex items-center gap-3">
                                <x-avatar :user="$studentUser" size="xs" />
                                <div>
                                    <p class="text-sm font-medium text-gray-900">{{ $studentUser->name ?? '-' }}</p>
                                    <p class="text-xs text-gray-500">{{ __('supervisor.observation.role_student') }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <a href="{{ route('manage.session-reports.index', ['subdomain' => $subdomain, 'session_id' => $session->id]) }}"
                                   class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium rounded-md bg-emerald-50 text-emerald-600 hover:bg-emerald-100 transition-colors cursor-pointer">
                                    <i class="ri-file-list-3-line"></i>
                                    {{ __($t.'view_report') }}
                                </a>
                                @if($isSupervisor)
                                    <a href="{{ route('chats', ['subdomain' => $subdomain]) }}" class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium rounded-md bg-blue-50 text-blue-600 hover:bg-blue-100 transition-colors cursor-pointer">
                                        <i class="ri-message-3-line"></i>
                                        {{ __($t.'message') }}
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endforeach

                    {{-- Show more/less toggle for large lists --}}
                    @if($studentUsers->count() > 10)
                        <button @click="showAll = !showAll" class="w-full text-center py-2 text-sm font-medium text-indigo-600 hover:text-indigo-700 transition-colors cursor-pointer">
                            <span x-show="!showAll"><i class="ri-arrow-down-s-line"></i> {{ __($t.'show_more') }} ({{ $studentUsers->count() - 10 }})</span>
                            <span x-show="showAll" x-cloak><i class="ri-arrow-up-s-line"></i> {{ __($t.'show_less') }}</span>
                        </button>
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
            {{-- Meeting Info (moved above notes) --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                <h3 class="text-base font-semibold text-gray-900 mb-3 flex items-center gap-2">
                    <i class="ri-video-line text-gray-400"></i>
                    {{ __($t.'meeting_info') }}
                </h3>
                <div class="space-y-3 text-sm">
                    @if($isLive)
                        <div class="flex items-center gap-2 text-green-600">
                            <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                            {{ __($t.'meeting_active') }}
                        </div>
                        <div class="space-y-2">
                            <a href="#" onclick="document.querySelector('.bg-white.rounded-xl.border.border-gray-200.shadow-sm.overflow-hidden')?.scrollIntoView({behavior:'smooth'}); return false;"
                               class="w-full inline-flex items-center justify-center gap-2 px-3 py-2 text-sm font-medium rounded-lg bg-green-600 hover:bg-green-700 text-white transition-colors cursor-pointer">
                                <i class="ri-video-chat-line"></i>
                                {{ __($t.'join_meeting_btn') }}
                            </a>
                            @if($canObserve)
                                <a href="{{ route('manage.sessions.show', ['subdomain' => $subdomain, 'sessionType' => $sessionType, 'sessionId' => $session->id, 'mode' => 'observer']) }}"
                                   class="w-full inline-flex items-center justify-center gap-2 px-3 py-2 text-sm font-medium rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 transition-colors cursor-pointer">
                                    <i class="ri-eye-line"></i>
                                    {{ __($t.'observe_silently') }}
                                </a>
                            @endif
                        </div>
                    @elseif($isFinal)
                        <div class="flex items-center gap-2 text-gray-500">
                            <i class="ri-stop-circle-line"></i>
                            @if($status === \App\Enums\SessionStatus::CANCELLED)
                                {{ __($t.'session_cancelled_text') }}
                            @else
                                {{ __($t.'meeting_ended') }}
                            @endif
                        </div>
                    @else
                        {{-- Scheduled / not started --}}
                        <div class="flex items-center gap-2 text-gray-500">
                            <i class="ri-time-line"></i>
                            {{ __($t.'meeting_not_started') }}
                        </div>
                        @if($session->scheduled_at)
                            <div x-data="sessionCountdown(@json($session->scheduled_at->toIso8601String()))">
                                <p class="text-sm text-amber-600 font-medium">
                                    <i class="ri-timer-line me-1"></i>
                                    {{ __($t.'starts_in') }}: <span x-text="remaining"></span>
                                </p>
                            </div>
                        @endif
                        <button disabled class="w-full inline-flex items-center justify-center gap-2 px-3 py-2 text-sm font-medium rounded-lg bg-gray-100 text-gray-400 cursor-not-allowed">
                            <i class="ri-video-chat-line"></i>
                            {{ __($t.'session_not_started_yet') }}
                        </button>
                    @endif
                    @if($session->meeting_room_name)
                        <p class="text-xs text-gray-400 font-mono">{{ $session->meeting_room_name }}</p>
                    @endif
                </div>
            </div>

            {{-- Notes --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                <h3 class="text-base font-semibold text-gray-900 mb-4 flex items-center gap-2">
                    <i class="ri-sticky-note-line text-gray-400"></i>
                    {{ __($t.'supervisor_notes') }}
                </h3>
                <div class="space-y-4">
                    {{-- Supervisor Notes --}}
                    <div>
                        <textarea x-model="editForm.supervisor_notes" rows="3"
                            placeholder="{{ __($t.'notes_placeholder') }}"
                            class="w-full text-sm rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 resize-none"></textarea>
                    </div>

                    {{-- Save button --}}
                    <div class="flex items-center justify-between">
                        <button @click="saveNotes()" :disabled="savingNotes"
                            class="px-4 py-2 text-xs font-medium rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white transition-colors disabled:opacity-50 cursor-pointer">
                            <span x-show="!savingNotes">{{ __($t.'save_notes') }}</span>
                            <span x-show="savingNotes" x-cloak>{{ __('supervisor.observation.saving') }}...</span>
                        </button>
                        <span x-show="notesSaved" x-transition x-cloak class="text-xs text-green-600">
                            <i class="ri-check-line"></i> {{ __($t.'notes_saved') }}
                        </span>
                    </div>
                </div>
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
                    <p class="text-xs text-gray-500 mt-1.5">
                        <i class="ri-information-line"></i>
                        {{ __($t.'cancel_reason_note') }}
                    </p>
                </div>

                <div class="flex justify-end gap-3">
                    <button @click="showCancelModal = false"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors cursor-pointer">
                        {{ __($t.'cancel_modal_close') }}
                    </button>
                    <button @click="submitCancel()" :disabled="submittingCancel || !cancellationReason.trim()"
                        class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg transition-colors disabled:opacity-50 cursor-pointer">
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
@endphp

<script>
function sessionCountdown(targetIso) {
    return {
        remaining: '',
        init() {
            const target = new Date(targetIso);
            const soonText = @json(__($t.'session_starting_soon'));
            const hLabel = @json(__($t.'hours_short'));
            const mLabel = @json(__($t.'minutes_short'));
            const sLabel = @json(__($t.'seconds_short'));
            const update = () => {
                const diff = target - Date.now();
                if (diff <= 0) { this.remaining = soonText; return; }
                const h = Math.floor(diff / 3600000);
                const m = Math.floor((diff % 3600000) / 60000);
                const s = Math.floor((diff % 60000) / 1000);
                this.remaining = (h > 0 ? h + ' ' + hLabel + ' ' : '') + m + ' ' + mLabel + ' ' + s + ' ' + sLabel;
            };
            update();
            setInterval(update, 1000);
        }
    };
}

function sessionDetail() {
    return {
        showCancelModal: false,
        savingNotes: false,
        notesSaved: false,
        submittingCancel: false,
        cancellationReason: '',

        editForm: {
            supervisor_notes: @json($initialNotes),
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
                    body: JSON.stringify({ supervisor_notes: this.editForm.supervisor_notes }),
                });
                if (response.ok) {
                    this.notesSaved = true;
                    setTimeout(() => this.notesSaved = false, 3000);
                } else {
                    const data = await response.json();
                    alert(data.message || 'Error');
                }
            } catch (e) {
                alert('Error: ' + e.message);
            } finally {
                this.savingNotes = false;
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

        init() {
            if (window.location.hash === '#cancel') {
                this.showCancelModal = true;
            }
        }
    }
}
</script>

</x-layouts.supervisor>
