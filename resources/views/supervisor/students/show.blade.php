<x-layouts.supervisor>

@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';
@endphp

<div class="max-w-7xl mx-auto">
    <!-- Breadcrumb -->
    <x-ui.breadcrumb
        :items="[
            ['label' => __('supervisor.students.breadcrumb'), 'route' => route('manage.students.index', ['subdomain' => $subdomain])],
            ['label' => $student->name, 'truncate' => true],
        ]"
        view-type="supervisor"
    />

    <!-- Profile Header -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
        <div class="flex items-center gap-4">
            <x-avatar :user="$student" size="lg" user-type="student" />
            <div class="min-w-0 flex-1">
                <h2 class="text-xl font-bold text-gray-900">{{ $student->name }}</h2>
                <p class="text-gray-500 text-sm">{{ $student->email }}</p>
                @if($student->phone || $student->studentProfile?->phone)
                    <p class="text-gray-500 text-sm mt-1">
                        <i class="ri-phone-line text-gray-400"></i>
                        {{ $student->phone ?? $student->studentProfile?->phone }}
                    </p>
                @endif
                <div class="flex flex-wrap gap-2 mt-2">
                    @if($student->studentProfile?->gradeLevel)
                        <span class="text-xs px-2.5 py-1 rounded-full bg-blue-100 text-blue-800">
                            {{ $student->studentProfile->gradeLevel->getDisplayName() }}
                        </span>
                    @endif
                    @if($student->studentProfile?->gender)
                        <span class="text-xs px-2.5 py-1 rounded-full bg-gray-100 text-gray-700">
                            {{ $student->studentProfile->gender === 'male' ? __('supervisor.students.male') : __('supervisor.students.female') }}
                        </span>
                    @endif
                    <span class="text-xs px-2.5 py-1 rounded-full {{ $student->active_status ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                        {{ $student->active_status ? __('supervisor.students.active') : __('supervisor.students.inactive') }}
                    </span>
                </div>
            </div>
        </div>
        @if($student->studentProfile?->parent_phone)
            <div class="mt-4 pt-4 border-t border-gray-100">
                <p class="text-sm text-gray-600">
                    <i class="ri-parent-line text-gray-400"></i>
                    {{ __('supervisor.students.parent_phone') }}: {{ $student->studentProfile->parent_phone }}
                </p>
            </div>
        @endif

        <!-- Action Buttons -->
        <div class="mt-4 pt-4 border-t border-gray-100">
            <div class="flex flex-wrap items-center gap-2">
                {{-- View Sessions --}}
                <a href="{{ route('manage.sessions.index', ['subdomain' => $subdomain, 'student_id' => $student->id]) }}"
                   class="cursor-pointer inline-flex items-center gap-1.5 px-3 py-2 text-xs md:text-sm font-medium rounded-lg bg-blue-600 hover:bg-blue-700 text-white transition-colors">
                    <i class="ri-calendar-event-line"></i>
                    {{ __('supervisor.students.view_sessions') }}
                </a>

                {{-- Message Student --}}
                <a href="{{ route('chat.start-with', ['subdomain' => $subdomain, 'user' => $student->id]) }}"
                   class="cursor-pointer inline-flex items-center gap-1.5 px-3 py-2 text-xs md:text-sm font-medium rounded-lg bg-green-600 hover:bg-green-700 text-white transition-colors">
                    <i class="ri-message-3-line"></i>
                    {{ __('supervisor.students.message_student') }}
                </a>

                {{-- Admin-only actions --}}
                @if($isAdmin)
                    <span class="hidden md:inline text-gray-300 mx-0.5">|</span>

                    {{-- Edit Student --}}
                    <a href="{{ route('manage.students.edit', ['subdomain' => $subdomain, 'student' => $student->id]) }}"
                       class="cursor-pointer inline-flex items-center gap-1.5 px-3 py-2 text-xs md:text-sm font-medium rounded-lg bg-blue-600 hover:bg-blue-700 text-white transition-colors">
                        <i class="ri-edit-line"></i>
                        {{ __('common.edit') }}
                    </a>

                    {{-- Toggle Status --}}
                    <form id="toggle-form-{{ $student->id }}" method="POST"
                          action="{{ route('manage.students.toggle-status', ['subdomain' => $subdomain, 'student' => $student->id]) }}">
                        @csrf
                    </form>
                    <button type="button"
                        onclick="window.confirmAction({
                            title: @js($student->active_status ? __('supervisor.teachers.deactivate') : __('supervisor.teachers.activate')),
                            message: @js($student->active_status ? __('supervisor.students.confirm_deactivate') : __('supervisor.students.confirm_activate')),
                            confirmText: @js($student->active_status ? __('supervisor.teachers.deactivate') : __('supervisor.teachers.activate')),
                            isDangerous: {{ $student->active_status ? 'true' : 'false' }},
                            icon: '{{ $student->active_status ? 'ri-pause-circle-line' : 'ri-play-circle-line' }}',
                            onConfirm: () => document.getElementById('toggle-form-{{ $student->id }}').submit()
                        })"
                        class="cursor-pointer inline-flex items-center gap-1.5 px-3 py-2 text-xs md:text-sm font-medium rounded-lg transition-colors
                            {{ $student->active_status
                                ? 'bg-orange-50 text-orange-700 hover:bg-orange-100'
                                : 'bg-green-50 text-green-700 hover:bg-green-100' }}">
                        <i class="{{ $student->active_status ? 'ri-pause-circle-line' : 'ri-play-circle-line' }}"></i>
                        {{ $student->active_status ? __('supervisor.teachers.deactivate') : __('supervisor.teachers.activate') }}
                    </button>

                    {{-- Reset Password --}}
                    <button type="button"
                        onclick="window.dispatchEvent(new CustomEvent('open-modal-reset-password-show-{{ $student->id }}'))"
                        class="cursor-pointer inline-flex items-center gap-1.5 px-3 py-2 text-xs md:text-sm font-medium rounded-lg bg-yellow-50 text-yellow-700 hover:bg-yellow-100 transition-colors">
                        <i class="ri-lock-password-line"></i>
                        {{ __('supervisor.teachers.reset_password') }}
                    </button>

                    {{-- Delete --}}
                    <form id="delete-form-{{ $student->id }}" method="POST"
                          action="{{ route('manage.students.destroy', ['subdomain' => $subdomain, 'student' => $student->id]) }}">
                        @csrf
                        @method('DELETE')
                    </form>
                    <button type="button"
                        onclick="window.confirmAction({
                            title: @js(__('supervisor.students.delete_student')),
                            message: @js(__('supervisor.students.confirm_delete')),
                            confirmText: @js(__('supervisor.students.delete_student')),
                            isDangerous: true,
                            icon: 'ri-delete-bin-line',
                            onConfirm: () => document.getElementById('delete-form-{{ $student->id }}').submit()
                        })"
                        class="cursor-pointer inline-flex items-center gap-1.5 px-3 py-2 text-xs md:text-sm font-medium rounded-lg bg-red-50 text-red-700 hover:bg-red-100 transition-colors">
                        <i class="ri-delete-bin-line"></i>
                        {{ __('supervisor.students.delete_student') }}
                    </button>
                @endif
            </div>
        </div>
    </div>

    {{-- Password Reset Modal --}}
    @if($isAdmin)
        <x-responsive.modal id="reset-password-show-{{ $student->id }}" :title="__('supervisor.teachers.reset_password')" size="sm">
            <form method="POST" action="{{ route('manage.students.reset-password', ['subdomain' => $subdomain, 'student' => $student->id]) }}"
                  x-data="{ showPass: false, showConfirm: false }">
                @csrf
                <div class="space-y-4">
                    <p class="text-sm text-gray-600">{{ __('supervisor.students.reset_password_description', ['name' => $student->name]) }}</p>
                    <div>
                        <label for="new_password_show" class="block text-sm font-medium text-gray-700 mb-1">{{ __('supervisor.teachers.new_password') }}</label>
                        <div class="relative">
                            <input :type="showPass ? 'text' : 'password'" name="new_password" id="new_password_show"
                                   class="min-h-[44px] w-full px-3 py-2 pe-10 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500"
                                   placeholder="{{ __('supervisor.teachers.new_password_placeholder') }}"
                                   required minlength="6">
                            <button type="button" @click="showPass = !showPass"
                                class="cursor-pointer absolute inset-y-0 end-0 flex items-center pe-3 text-gray-400 hover:text-gray-600">
                                <i :class="showPass ? 'ri-eye-off-line' : 'ri-eye-line'"></i>
                            </button>
                        </div>
                    </div>
                    <div>
                        <label for="new_password_confirmation_show" class="block text-sm font-medium text-gray-700 mb-1">{{ __('supervisor.teachers.confirm_password') }}</label>
                        <div class="relative">
                            <input :type="showConfirm ? 'text' : 'password'" name="new_password_confirmation" id="new_password_confirmation_show"
                                   class="min-h-[44px] w-full px-3 py-2 pe-10 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500"
                                   placeholder="{{ __('supervisor.teachers.confirm_password_placeholder') }}"
                                   required minlength="6">
                            <button type="button" @click="showConfirm = !showConfirm"
                                class="cursor-pointer absolute inset-y-0 end-0 flex items-center pe-3 text-gray-400 hover:text-gray-600">
                                <i :class="showConfirm ? 'ri-eye-off-line' : 'ri-eye-line'"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <x-slot:footer>
                    <div class="flex items-center justify-end gap-3">
                        <button type="button" @click="open = false"
                            class="cursor-pointer px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">
                            {{ __('common.cancel') }}
                        </button>
                        <button type="submit"
                            class="cursor-pointer px-4 py-2 text-sm font-medium text-white bg-yellow-600 rounded-lg hover:bg-yellow-700">
                            {{ __('supervisor.teachers.reset_password') }}
                        </button>
                    </div>
                </x-slot:footer>
            </form>
        </x-responsive.modal>
    @endif

    <!-- Stats Row -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                    <i class="ri-check-double-line text-green-600"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900">{{ $attendanceRate }}%</p>
                    <p class="text-xs text-gray-500">{{ __('supervisor.students.attendance_rate') }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                    <i class="ri-calendar-check-line text-blue-600"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900">{{ $completedSessions }}</p>
                    <p class="text-xs text-gray-500">{{ __('supervisor.students.sessions_completed') }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                    <i class="ri-booklet-line text-purple-600"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900">{{ $quranSubscriptions->count() + $academicSubscriptions->count() }}</p>
                    <p class="text-xs text-gray-500">{{ __('supervisor.students.subscriptions') }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-amber-100 rounded-lg flex items-center justify-center">
                    <i class="ri-award-line text-amber-600"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900">{{ $certificates->count() }}</p>
                    <p class="text-xs text-gray-500">{{ __('supervisor.students.certificates') }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Active Subscriptions -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ __('supervisor.students.active_subscriptions') }}</h3>

                @if($quranSubscriptions->isNotEmpty() || $academicSubscriptions->isNotEmpty())
                    <div class="space-y-3">
                        @foreach($quranSubscriptions as $sub)
                            <div class="flex items-center gap-3 p-3 rounded-lg border border-gray-200">
                                <div class="w-8 h-8 bg-yellow-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                    <i class="ri-book-read-line text-yellow-600 text-sm"></i>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-medium text-gray-900 truncate">
                                        {{ __('supervisor.students.quran_subscription') }}
                                    </p>
                                    <p class="text-xs text-gray-500">
                                        {{ $sub->individualCircle?->quranTeacher?->name ?? '' }}
                                        @if($sub->sessions_remaining !== null)
                                            &middot; {{ $sub->sessions_remaining }} {{ __('supervisor.students.sessions_remaining') }}
                                        @endif
                                    </p>
                                </div>
                                @php $subStatus = is_object($sub->status) ? $sub->status->value : $sub->status; @endphp
                                <span class="text-xs px-2 py-1 rounded-full {{ $subStatus === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700' }}">
                                    {{ $subStatus }}
                                </span>
                            </div>
                        @endforeach

                        @foreach($academicSubscriptions as $sub)
                            <div class="flex items-center gap-3 p-3 rounded-lg border border-gray-200">
                                <div class="w-8 h-8 bg-violet-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                    <i class="ri-graduation-cap-line text-violet-600 text-sm"></i>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-medium text-gray-900 truncate">
                                        {{ $sub->lesson?->subject?->name ?? __('supervisor.students.academic_subscription') }}
                                    </p>
                                    <p class="text-xs text-gray-500">
                                        {{ $sub->lesson?->academicTeacher?->user?->name ?? '' }}
                                        @if($sub->sessions_remaining !== null)
                                            &middot; {{ $sub->sessions_remaining }} {{ __('supervisor.students.sessions_remaining') }}
                                        @endif
                                    </p>
                                </div>
                                @php $subStatus = is_object($sub->status) ? $sub->status->value : $sub->status; @endphp
                                <span class="text-xs px-2 py-1 rounded-full {{ $subStatus === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700' }}">
                                    {{ $subStatus }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8">
                        <div class="w-14 h-14 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                            <i class="ri-booklet-line text-2xl text-gray-400"></i>
                        </div>
                        <p class="text-sm text-gray-500">{{ __('supervisor.students.no_subscriptions') }}</p>
                    </div>
                @endif
            </div>

            <!-- Recent Sessions Timeline -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ __('supervisor.students.recent_sessions') }}</h3>

                @if($recentSessions->isNotEmpty())
                    <div class="space-y-3">
                        @foreach($recentSessions as $session)
                            <div class="flex items-center gap-3 p-3 rounded-lg border border-gray-200">
                                <div class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0 {{ $session['type'] === 'quran' ? 'bg-yellow-100' : 'bg-violet-100' }}">
                                    <i class="{{ $session['type'] === 'quran' ? 'ri-book-read-line text-yellow-600' : 'ri-graduation-cap-line text-violet-600' }}"></i>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-medium text-gray-900 truncate">{{ $session['title'] }}</p>
                                    <p class="text-xs text-gray-500">
                                        {{ $session['teacher_name'] }}
                                        &middot;
                                        {{ $session['date']?->format('Y/m/d') }}
                                    </p>
                                </div>
                                @php $sessionStatus = is_object($session['status']) ? $session['status']->value : $session['status']; @endphp
                                <span class="text-xs px-2 py-1 rounded-full {{ match($sessionStatus) {
                                    'completed' => 'bg-green-100 text-green-700',
                                    'scheduled' => 'bg-blue-100 text-blue-700',
                                    'cancelled' => 'bg-red-100 text-red-700',
                                    'ongoing', 'live' => 'bg-orange-100 text-orange-700',
                                    default => 'bg-gray-100 text-gray-700',
                                } }}">{{ $sessionStatus }}</span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8">
                        <div class="w-14 h-14 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                            <i class="ri-calendar-line text-2xl text-gray-400"></i>
                        </div>
                        <p class="text-sm text-gray-500">{{ __('supervisor.students.no_recent_sessions') }}</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Certificates -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h3 class="text-sm font-bold text-gray-900 mb-3">{{ __('supervisor.students.certificates') }}</h3>
                @if($certificates->isNotEmpty())
                    <div class="space-y-2">
                        @foreach($certificates as $cert)
                            <div class="flex items-center gap-2 p-2 rounded-lg bg-amber-50 border border-amber-100">
                                <i class="ri-award-fill text-amber-500"></i>
                                <div class="min-w-0 flex-1">
                                    <p class="text-xs font-medium text-gray-900 truncate">{{ $cert->certificate_number }}</p>
                                    <p class="text-xs text-gray-500">{{ $cert->issued_at?->format('Y/m/d') }}</p>
                                </div>
                                <a href="{{ route('student.certificate.view', ['subdomain' => $subdomain, 'certificate' => $cert->id]) }}"
                                   target="_blank" class="text-blue-500 hover:text-blue-700">
                                    <i class="ri-eye-line text-sm"></i>
                                </a>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-gray-500 text-center py-4">{{ __('supervisor.students.no_certificates') }}</p>
                @endif
            </div>

            <!-- Student Info -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h3 class="text-sm font-bold text-gray-900 mb-3">{{ __('supervisor.students.student_info') }}</h3>
                <div class="space-y-2 text-sm text-gray-600">
                    @if($student->studentProfile?->student_code)
                        <div class="flex items-center gap-2">
                            <i class="ri-hashtag text-gray-400"></i>
                            {{ $student->studentProfile->student_code }}
                        </div>
                    @endif
                    @if($student->studentProfile?->enrollment_date)
                        <div class="flex items-center gap-2">
                            <i class="ri-calendar-line text-gray-400"></i>
                            {{ __('supervisor.students.enrolled_at') }}: {{ $student->studentProfile->enrollment_date?->format('Y/m/d') }}
                        </div>
                    @endif
                    @if($student->studentProfile?->birth_date)
                        <div class="flex items-center gap-2">
                            <i class="ri-cake-line text-gray-400"></i>
                            {{ $student->studentProfile->birth_date?->format('Y/m/d') }}
                        </div>
                    @endif
                    @if($student->studentProfile?->nationality)
                        <div class="flex items-center gap-2">
                            <i class="ri-global-line text-gray-400"></i>
                            {{ $student->studentProfile->nationality }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

</x-layouts.supervisor>
