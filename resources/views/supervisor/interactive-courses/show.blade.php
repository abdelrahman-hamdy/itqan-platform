<x-layouts.supervisor>

@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';
    $statusValue = is_object($course->status) ? $course->status->value : $course->status;
    $statusClass = match($statusValue) {
        'active' => 'bg-blue-100 text-blue-800',
        'published' => 'bg-green-100 text-green-800',
        'completed' => 'bg-purple-100 text-purple-800',
        default => 'bg-gray-100 text-gray-800',
    };
    $statusLabel = is_object($course->status) ? $course->status->label() : $statusValue;
@endphp

<div>
    @if($teacher)
        <x-supervisor.teacher-info-banner :teacher="$teacher" type="academic" />
    @endif

    <x-ui.breadcrumb
        :items="[
            ['label' => __('supervisor.interactive_courses.breadcrumb'), 'route' => route('manage.interactive-courses.index', ['subdomain' => $subdomain])],
            ['label' => $course->title, 'truncate' => true],
        ]"
        view-type="supervisor"
    />

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 md:gap-6 lg:gap-8" data-sticky-container>
        {{-- Main Content --}}
        <div class="lg:col-span-2 space-y-4 md:space-y-6">
            {{-- Course Header --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6">
                <h2 class="text-lg md:text-xl font-bold text-gray-900 mb-2">{{ $course->title }}</h2>
                @if($course->description)
                    <p class="text-sm text-gray-600 mb-4">{{ $course->description }}</p>
                @endif
                <div class="flex flex-wrap gap-2">
                    <span class="text-xs px-2.5 py-1 rounded-full {{ $statusClass }}">{{ $statusLabel }}</span>
                    @if($course->subject)
                        <span class="text-xs px-2.5 py-1 rounded-full bg-gray-100 text-gray-700">{{ $course->subject->name }}</span>
                    @endif
                    @if($course->gradeLevel)
                        <span class="text-xs px-2.5 py-1 rounded-full bg-gray-100 text-gray-700">{{ $course->gradeLevel->getDisplayName() }}</span>
                    @endif
                </div>
            </div>

            @php
                $allSessions = $course->sessions;
                $totalStudents = $course->enrollments->count();
            @endphp

            <x-tabs id="course-tabs" default-tab="sessions" variant="default" color="primary">
                <x-slot name="tabs">
                    <x-tabs.tab id="sessions" :label="__('teacher.circles.tabs.sessions')" icon="ri-calendar-line" :badge="$allSessions->count()" />
                    <x-tabs.tab id="students" :label="__('teacher.circles.tabs.students')" icon="ri-user-3-line" :badge="$totalStudents" />
                    <x-tabs.tab id="quizzes" :label="__('teacher.circles.tabs.quizzes')" icon="ri-file-list-3-line" />
                    <x-tabs.tab id="certificates" :label="__('teacher.circles.tabs.certificates')" icon="ri-award-line" />
                </x-slot>

                <x-slot name="panels">
                    {{-- Sessions Tab --}}
                    <x-tabs.panel id="sessions">
                        @if($allSessions->isNotEmpty())
                            <div class="space-y-3">
                                @foreach($allSessions as $session)
                                    @php
                                        $sessionStatus = is_object($session->status) ? $session->status->value : $session->status;
                                        $sessionStatusClass = match($sessionStatus) {
                                            'completed' => 'bg-green-100 text-green-700',
                                            'scheduled' => 'bg-blue-100 text-blue-700',
                                            'ongoing' => 'bg-yellow-100 text-yellow-700',
                                            'cancelled' => 'bg-red-100 text-red-700',
                                            default => 'bg-gray-100 text-gray-700',
                                        };
                                    @endphp
                                    <a href="{{ route('manage.sessions.show', ['subdomain' => $subdomain, 'sessionType' => 'interactive', 'sessionId' => $session->id]) }}"
                                       class="block bg-white rounded-lg border border-gray-200 p-3 md:p-4 hover:bg-gray-50 transition-colors">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                                <i class="ri-calendar-line text-blue-600"></i>
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <p class="text-sm font-medium text-gray-900">{{ $session->title ?? __('teacher.circles.tabs.sessions') }}</p>
                                                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-gray-500">
                                                    <span>{{ $session->scheduled_at?->format('Y/m/d H:i') }}</span>
                                                    @if($session->session_duration_minutes)
                                                        <span>{{ $session->session_duration_minutes }} {{ __('common.minutes') }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                            <span class="text-xs px-2 py-1 rounded-full {{ $sessionStatusClass }}">{{ $sessionStatus }}</span>
                                            <i class="ri-arrow-left-s-line text-gray-400 hidden sm:block"></i>
                                        </div>
                                    </a>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-8">
                                <div class="w-14 h-14 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                                    <i class="ri-calendar-line text-2xl text-gray-400"></i>
                                </div>
                                <p class="text-sm text-gray-500">{{ __('supervisor.common.no_data') }}</p>
                            </div>
                        @endif
                    </x-tabs.panel>

                    {{-- Students Tab --}}
                    <x-tabs.panel id="students">
                        @if(isset($isAdmin) && $isAdmin && isset($availableStudents) && $availableStudents->isNotEmpty())
                            <div class="mb-4 p-3 bg-blue-50 rounded-lg border border-blue-100">
                                <form method="POST" action="{{ route('manage.interactive-courses.add-enrollment', ['subdomain' => $subdomain, 'course' => $course->id]) }}" class="flex items-center gap-2">
                                    @csrf
                                    <select name="student_id" required class="flex-1 rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                                        <option value="">{{ __('supervisor.interactive_courses.select_student') }}</option>
                                        @foreach($availableStudents as $s)
                                            <option value="{{ $s->studentProfile?->id }}">{{ $s->name }}</option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors whitespace-nowrap">
                                        <i class="ri-user-add-line"></i> {{ __('supervisor.interactive_courses.add_student') }}
                                    </button>
                                </form>
                            </div>
                        @endif

                        @if($course->enrollments->isNotEmpty())
                            <div class="space-y-3">
                                @foreach($course->enrollments as $enrollment)
                                    @php
                                        $enrollStatus = is_object($enrollment->status) ? $enrollment->status->value : $enrollment->status;
                                        $enrollStatusClass = match($enrollStatus) {
                                            'active', 'enrolled' => 'bg-green-100 text-green-700',
                                            'completed' => 'bg-purple-100 text-purple-700',
                                            'cancelled' => 'bg-red-100 text-red-700',
                                            'pending' => 'bg-yellow-100 text-yellow-700',
                                            default => 'bg-gray-100 text-gray-700',
                                        };
                                    @endphp
                                    <div class="bg-white rounded-lg border border-gray-200 p-3 md:p-4 flex items-center gap-3">
                                        <x-avatar :user="$enrollment->student" size="sm" user-type="student" />
                                        <div class="min-w-0 flex-1">
                                            <p class="text-sm font-medium text-gray-900 truncate">{{ $enrollment->student?->name ?? '' }}</p>
                                            <p class="text-xs text-gray-500">{{ $enrollment->created_at?->format('Y/m/d') }}</p>
                                        </div>
                                        <span class="text-xs px-2 py-1 rounded-full {{ $enrollStatusClass }}">
                                            {{ $enrollStatus }}
                                        </span>
                                        @if(isset($isAdmin) && $isAdmin)
                                            <form method="POST" action="{{ route('manage.interactive-courses.remove-enrollment', ['subdomain' => $subdomain, 'course' => $course->id, 'enrollment' => $enrollment->id]) }}"
                                                  onsubmit="return confirm('{{ __('supervisor.interactive_courses.confirm_remove') }}')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-500 hover:text-red-700 p-1">
                                                    <i class="ri-delete-bin-line text-sm"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-8">
                                <div class="w-14 h-14 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                                    <i class="ri-user-3-line text-2xl text-gray-400"></i>
                                </div>
                                <p class="text-sm text-gray-500">{{ __('supervisor.common.no_data') }}</p>
                            </div>
                        @endif
                    </x-tabs.panel>

                    {{-- Quizzes Tab --}}
                    <x-tabs.panel id="quizzes">
                        <livewire:teacher-quizzes-widget :assignable="$course" />
                    </x-tabs.panel>

                    {{-- Certificates Tab --}}
                    <x-tabs.panel id="certificates">
                        @if($certificates->count() > 0)
                            <div class="space-y-3">
                                @foreach($certificates as $certificate)
                                    <div class="bg-white rounded-lg border border-gray-200 p-3 md:p-4 flex items-center gap-3">
                                        <x-avatar :user="$certificate->student" size="sm" user-type="student" />
                                        <div class="min-w-0 flex-1">
                                            <p class="text-sm font-medium text-gray-900 truncate">{{ $certificate->student->name }}</p>
                                            <p class="text-xs text-gray-500">{{ $certificate->certificate_number }}</p>
                                        </div>
                                        <div class="flex items-center gap-1.5 text-xs text-gray-500">
                                            <i class="ri-calendar-line text-amber-500"></i>
                                            {{ $certificate->issued_at->format('Y/m/d') }}
                                        </div>
                                        <a href="{{ route('student.certificate.view', ['subdomain' => $subdomain, 'certificate' => $certificate->id]) }}"
                                           target="_blank"
                                           class="inline-flex items-center gap-1 px-3 py-1.5 bg-blue-500 hover:bg-blue-600 text-white text-xs font-medium rounded-lg transition-colors">
                                            <i class="ri-eye-line"></i>
                                            {{ __('supervisor.certificates.view_certificate') }}
                                        </a>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-8">
                                <div class="w-14 h-14 bg-amber-100 rounded-full flex items-center justify-center mx-auto mb-3">
                                    <i class="ri-award-line text-2xl text-amber-500"></i>
                                </div>
                                <h3 class="text-sm font-medium text-gray-900 mb-1">{{ __('teacher.circles_list.group.show.no_certificates') }}</h3>
                                <p class="text-xs text-gray-600">{{ __('teacher.circles_list.group.show.no_certificates_issued') }}</p>
                            </div>
                        @endif
                    </x-tabs.panel>
                </x-slot>
            </x-tabs>
        </div>

        {{-- Sidebar --}}
        <div class="lg:col-span-1" data-sticky-sidebar>
            <div class="space-y-4 md:space-y-6">
                {{-- Course Info Card --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-5">
                    <h3 class="text-sm font-bold text-gray-900 mb-3">{{ __('supervisor.common.teacher_info') }}</h3>
                    <div class="space-y-2 text-sm text-gray-600">
                        <div class="flex items-center gap-2"><i class="ri-user-line text-gray-400"></i> {{ $course->assignedTeacher?->user?->name ?? '' }}</div>
                        @if($course->start_date)
                            <div class="flex items-center gap-2"><i class="ri-calendar-line text-gray-400"></i> {{ $course->start_date->format('Y/m/d') }}</div>
                        @endif
                        @if($course->end_date)
                            <div class="flex items-center gap-2"><i class="ri-calendar-check-line text-gray-400"></i> {{ $course->end_date->format('Y/m/d') }}</div>
                        @endif
                        @if($course->max_students)
                            <div class="flex items-center gap-2"><i class="ri-group-line text-gray-400"></i> {{ $course->enrollments->count() }} / {{ $course->max_students }}</div>
                        @endif
                        @if($course->total_sessions)
                            <div class="flex items-center gap-2"><i class="ri-play-list-line text-gray-400"></i> {{ $course->sessions->count() }} / {{ $course->total_sessions }}</div>
                        @endif
                        @if($course->student_price)
                            <div class="flex items-center gap-2"><i class="ri-money-dollar-circle-line text-gray-400"></i> {{ number_format($course->student_price, 0) }}</div>
                        @endif
                    </div>
                </div>

                {{-- Actions Widget --}}
                @if($canManage || $isAdmin)
                    {{-- Hidden action forms --}}
                    @if($isAdmin)
                        <form id="delete-course-form" method="POST" action="{{ route('manage.interactive-courses.destroy', ['subdomain' => $subdomain, 'course' => $course->id]) }}" class="hidden">
                            @csrf @method('DELETE')
                        </form>
                    @endif

                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 md:p-6">
                        <h3 class="text-sm font-bold text-gray-900 mb-3">{{ __('supervisor.interactive_courses.course_actions') }}</h3>
                        <div class="grid grid-cols-2 gap-2">
                            {{-- Change Teacher --}}
                            @if($canManage)
                                <button type="button"
                                    onclick="window.dispatchEvent(new CustomEvent('open-modal-change-teacher'))"
                                    class="flex items-center justify-center gap-1.5 px-2 py-2.5 text-xs font-medium rounded-lg bg-blue-50 text-blue-700 hover:bg-blue-100 border border-blue-200 transition-colors cursor-pointer">
                                    <i class="ri-user-settings-line"></i>
                                    {{ $course->assigned_teacher_id ? __('supervisor.interactive_courses.change_teacher') : __('supervisor.interactive_courses.assign_teacher') }}
                                </button>
                            @endif

                            {{-- Delete (admin only) --}}
                            @if($isAdmin)
                                <button type="button"
                                    onclick="window.confirmAction({
                                        title: @js(__('supervisor.interactive_courses.delete_course')),
                                        message: @js(__('supervisor.interactive_courses.confirm_delete')),
                                        confirmText: @js(__('supervisor.interactive_courses.delete_course')),
                                        isDangerous: true,
                                        icon: 'ri-delete-bin-line',
                                        onConfirm: () => document.getElementById('delete-course-form').submit()
                                    })"
                                    class="flex items-center justify-center gap-1.5 px-2 py-2.5 text-xs font-medium rounded-lg bg-red-50 text-red-700 hover:bg-red-100 border border-red-200 transition-colors cursor-pointer">
                                    <i class="ri-delete-bin-line"></i>
                                    {{ __('supervisor.interactive_courses.delete_course') }}
                                </button>
                            @endif
                        </div>
                    </div>
                @endif

                {{-- Edit Details Widget --}}
                @if($canManage)
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 md:p-6">
                        <h3 class="text-sm font-bold text-gray-900 mb-4">{{ __('supervisor.interactive_courses.edit_details') }}</h3>
                        <form method="POST" action="{{ route('manage.interactive-courses.update', ['subdomain' => $subdomain, 'course' => $course->id]) }}">
                            @csrf
                            @method('PUT')
                            <div class="space-y-4">

                                {{-- Basic Info --}}
                                <div>
                                    <h4 class="text-xs font-bold text-blue-700 mb-3">{{ __('supervisor.interactive_courses.basic_info') }}</h4>
                                    <div class="grid grid-cols-1 gap-3">
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('supervisor.interactive_courses.title') }}</label>
                                            <input type="text" name="title" value="{{ old('title', $course->title) }}" maxlength="255"
                                                   class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500" required>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('supervisor.interactive_courses.description') }}</label>
                                            <textarea name="description" rows="2" maxlength="2000"
                                                      class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">{{ old('description', $course->description) }}</textarea>
                                        </div>
                                    </div>
                                </div>

                                {{-- Course Settings --}}
                                <div class="border-t border-gray-100 pt-4">
                                    <h4 class="text-xs font-bold text-blue-700 mb-3">{{ __('supervisor.interactive_courses.course_settings') }}</h4>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('supervisor.interactive_courses.max_students') }}</label>
                                            <input type="number" name="max_students" value="{{ old('max_students', $course->max_students) }}" min="1" max="50"
                                                   class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500" required>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('supervisor.interactive_courses.total_sessions') }}</label>
                                            <input type="number" name="total_sessions" value="{{ old('total_sessions', $course->total_sessions) }}" min="1" max="200"
                                                   class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500" required>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('supervisor.interactive_courses.session_duration') }}</label>
                                            <select name="session_duration_minutes" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500" required>
                                                @foreach(\App\Enums\SessionDuration::options() as $value => $label)
                                                    <option value="{{ $value }}" {{ old('session_duration_minutes', $course->session_duration_minutes) == $value ? 'selected' : '' }}>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('supervisor.interactive_courses.difficulty_level') }}</label>
                                            <select name="difficulty_level" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                                                <option value="">--</option>
                                                @foreach(\App\Enums\DifficultyLevel::options() as $value => $label)
                                                    <option value="{{ $value }}" {{ old('difficulty_level', $course->difficulty_level) == $value ? 'selected' : '' }}>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                {{-- Financial --}}
                                <div class="border-t border-gray-100 pt-4">
                                    <h4 class="text-xs font-bold text-blue-700 mb-3">{{ __('supervisor.interactive_courses.financial_settings') }}</h4>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('supervisor.interactive_courses.student_price') }}</label>
                                            <input type="number" name="student_price" value="{{ old('student_price', (int) $course->student_price) }}" min="0" step="0.01"
                                                   class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500" required>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('supervisor.interactive_courses.teacher_payment') }}</label>
                                            <input type="number" name="teacher_payment" value="{{ old('teacher_payment', (int) $course->teacher_payment) }}" min="0" step="0.01"
                                                   class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('supervisor.interactive_courses.payment_type') }}</label>
                                            <select name="payment_type" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                                                <option value="fixed_amount" {{ old('payment_type', $course->payment_type) === 'fixed_amount' ? 'selected' : '' }}>{{ __('supervisor.interactive_courses.payment_type_fixed') }}</option>
                                                <option value="per_student" {{ old('payment_type', $course->payment_type) === 'per_student' ? 'selected' : '' }}>{{ __('supervisor.interactive_courses.payment_type_per_student') }}</option>
                                                <option value="per_session" {{ old('payment_type', $course->payment_type) === 'per_session' ? 'selected' : '' }}>{{ __('supervisor.interactive_courses.payment_type_per_session') }}</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                {{-- Recording --}}
                                <div class="border-t border-gray-100 pt-4">
                                    <h4 class="text-xs font-bold text-blue-700 mb-3">{{ __('supervisor.interactive_courses.recording_settings') }}</h4>
                                    <div class="space-y-2">
                                        <label class="inline-flex items-center gap-2 cursor-pointer">
                                            <input type="hidden" name="recording_enabled" value="0">
                                            <input type="checkbox" name="recording_enabled" value="1"
                                                   {{ old('recording_enabled', $course->recording_enabled) ? 'checked' : '' }}
                                                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 w-4 h-4">
                                            <span class="text-sm text-gray-700">{{ __('supervisor.interactive_courses.recording_enabled') }}</span>
                                        </label>
                                        <br>
                                        <label class="inline-flex items-center gap-2 cursor-pointer">
                                            <input type="hidden" name="show_recording_to_teacher" value="0">
                                            <input type="checkbox" name="show_recording_to_teacher" value="1"
                                                   {{ old('show_recording_to_teacher', $course->show_recording_to_teacher) ? 'checked' : '' }}
                                                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 w-4 h-4">
                                            <span class="text-sm text-gray-700">{{ __('supervisor.interactive_courses.show_to_teacher') }}</span>
                                        </label>
                                        <br>
                                        <label class="inline-flex items-center gap-2 cursor-pointer">
                                            <input type="hidden" name="show_recording_to_student" value="0">
                                            <input type="checkbox" name="show_recording_to_student" value="1"
                                                   {{ old('show_recording_to_student', $course->show_recording_to_student) ? 'checked' : '' }}
                                                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 w-4 h-4">
                                            <span class="text-sm text-gray-700">{{ __('supervisor.interactive_courses.show_to_student') }}</span>
                                        </label>
                                    </div>
                                </div>

                                {{-- Notes --}}
                                <div class="border-t border-gray-100 pt-4">
                                    <h4 class="text-xs font-bold text-blue-700 mb-3">{{ __('supervisor.interactive_courses.notes_section') }}</h4>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('supervisor.interactive_courses.supervisor_notes') }}</label>
                                        <textarea name="supervisor_notes" rows="2" maxlength="2000"
                                                  class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">{{ old('supervisor_notes', $course->supervisor_notes) }}</textarea>
                                    </div>
                                </div>

                                <button type="submit"
                                        class="w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors cursor-pointer">
                                    {{ __('supervisor.common.save') }}
                                </button>
                            </div>
                        </form>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

</x-layouts.supervisor>

{{-- Change Teacher Modal --}}
@if($canManage && $academicTeachers->isNotEmpty())
@push('modals')
<div x-data="{ open: false }"
     @open-modal-change-teacher.window="open = true"
     @keydown.escape.window="open && (open = false)"
     x-show="open" x-cloak
     class="fixed inset-0 z-[9998]">
    <div x-show="open" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         @click="open = false" class="fixed inset-0 z-[9998] bg-black/50 backdrop-blur-sm"></div>
    <div class="fixed inset-0 z-[9999] flex items-end md:items-center justify-center p-0 md:p-4" @click="open = false">
        <form method="POST" action="{{ route('manage.interactive-courses.change-teacher', ['subdomain' => $subdomain, 'course' => $course->id]) }}"
              x-show="open" @click.stop
              x-transition:enter="transition ease-out duration-200"
              x-transition:enter-start="opacity-0 translate-y-full md:translate-y-0 md:scale-95"
              x-transition:enter-end="opacity-100 translate-y-0 md:scale-100"
              x-transition:leave="transition ease-in duration-150"
              x-transition:leave-start="opacity-100 translate-y-0 md:scale-100"
              x-transition:leave-end="opacity-0 translate-y-full md:translate-y-0 md:scale-95"
              class="relative bg-white w-full max-w-sm rounded-t-2xl md:rounded-2xl shadow-xl overflow-hidden">
            @csrf
            <div class="flex items-center justify-between p-4 md:p-5 border-b border-gray-100">
                <div class="md:hidden absolute top-2 left-1/2 -translate-x-1/2 w-10 h-1 rounded-full bg-gray-300"></div>
                <h3 class="text-lg font-bold text-gray-900 mt-2 md:mt-0">{{ $course->assigned_teacher_id ? __('supervisor.interactive_courses.change_teacher') : __('supervisor.interactive_courses.assign_teacher') }}</h3>
                <button type="button" @click="open = false" class="p-2 min-h-[44px] min-w-[44px] flex items-center justify-center rounded-lg hover:bg-gray-100 text-gray-500 transition-colors">
                    <i class="ri-close-line text-xl"></i>
                </button>
            </div>
            <div class="p-4 md:p-6 space-y-4">
                <p class="text-sm text-gray-600">{{ __('supervisor.interactive_courses.select_teacher') }}</p>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('supervisor.interactive_courses.teacher') }}</label>
                    <select name="assigned_teacher_id" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500" required>
                        @foreach($academicTeachers as $t)
                            @php $profileId = $t->academicTeacherProfile?->id; @endphp
                            <option value="{{ $profileId }}" {{ $course->assigned_teacher_id == $profileId ? 'selected' : '' }}>{{ $t->first_name }} {{ $t->last_name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="p-4 md:p-5 border-t border-gray-100 bg-gray-50 flex justify-end gap-3">
                <button type="button" @click="open = false"
                        class="inline-flex items-center px-4 py-2 bg-white hover:bg-gray-100 text-gray-700 border border-gray-300 text-sm font-medium rounded-lg transition-colors cursor-pointer">
                    {{ __('common.actions.cancel') }}
                </button>
                <button type="submit"
                        class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors cursor-pointer">
                    {{ $course->assigned_teacher_id ? __('supervisor.interactive_courses.change_teacher') : __('supervisor.interactive_courses.assign_teacher') }}
                </button>
            </div>
        </form>
    </div>
</div>
@endpush
@endif
