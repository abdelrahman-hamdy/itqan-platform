<x-layouts.supervisor>

@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';
@endphp

<div>
    <!-- Teacher Info Banner -->
    @if($teacher)
        <x-supervisor.teacher-info-banner :teacher="$teacher" type="quran" />
    @endif

    <!-- Breadcrumb -->
    <x-ui.breadcrumb
        :items="[
            ['label' => __('supervisor.group_circles.breadcrumb'), 'route' => route('manage.group-circles.index', ['subdomain' => $subdomain])],
            ['label' => $circle->name, 'truncate' => true],
        ]"
        view-type="supervisor"
    />

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 md:gap-6 lg:gap-8" data-sticky-container>
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-4 md:space-y-6">
            <!-- Circle Header -->
            <x-circle.circle-header :circle="$circle" type="group" view-type="supervisor" />

            @php
                $allSessions = $circle->sessions()->orderBy('scheduled_at', 'desc')->get();
                $totalStudents = $circle->students()->count();
                $studentsWithCertificates = $circle->students()->wherePivot('certificate_issued', true)->count();
            @endphp

            <x-tabs id="circle-tabs" default-tab="sessions" variant="default" color="primary">
                <x-slot name="tabs">
                    <x-tabs.tab id="sessions" :label="__('teacher.circles.tabs.sessions')" icon="ri-calendar-line" :badge="$allSessions->count()" />
                    <x-tabs.tab id="students" :label="__('teacher.circles.tabs.students')" icon="ri-user-3-line" :badge="$totalStudents" />
                    <x-tabs.tab id="quizzes" :label="__('teacher.circles.tabs.quizzes')" icon="ri-file-list-3-line" />
                    @if($circle->allow_sponsored_requests)
                        <x-tabs.tab id="sponsored" :label="__('supervisor.group_circles.sponsored_requests_tab')" icon="ri-heart-line" :badge="$pendingSponsoredCount" />
                    @endif
                    <x-tabs.tab id="certificates" :label="__('teacher.circles.tabs.certificates')" icon="ri-award-line" :badge="$studentsWithCertificates" />
                </x-slot>

                <x-slot name="panels">
                    <x-tabs.panel id="sessions">
                        <x-sessions.sessions-list :sessions="$allSessions" view-type="supervisor" :circle="$circle" :show-tabs="false" />
                    </x-tabs.panel>

                    <x-tabs.panel id="students">
                        <x-circle.group-students-list :circle="$circle" view-type="supervisor" />
                    </x-tabs.panel>

                    <x-tabs.panel id="quizzes">
                        <livewire:teacher-quizzes-widget :assignable="$circle" />
                    </x-tabs.panel>

                    {{-- Sponsored Requests Tab --}}
                    @if($circle->allow_sponsored_requests)
                    <x-tabs.panel id="sponsored">
                        @if($sponsoredRequests->count() > 0)
                            <div class="space-y-3">
                                @foreach($sponsoredRequests as $req)
                                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                                        <div class="flex items-center justify-between gap-3 flex-wrap">
                                            <div class="flex items-center gap-3">
                                                <x-avatar :user="$req->student" size="sm" user-type="student" />
                                                <div>
                                                    <p class="font-medium text-gray-900 text-sm">{{ $req->student->name }}</p>
                                                    <p class="text-xs text-gray-500">{{ __('supervisor.group_circles.requested_at') }}: {{ $req->created_at->format('Y/m/d') }}</p>
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                @if($req->status === \App\Models\SponsoredEnrollmentRequest::STATUS_PENDING)
                                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                        {{ __('supervisor.group_circles.request_pending') }}
                                                    </span>
                                                @elseif($req->status === \App\Models\SponsoredEnrollmentRequest::STATUS_APPROVED)
                                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                        {{ __('supervisor.group_circles.request_approved_status') }}
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                        {{ __('supervisor.group_circles.request_rejected_status') }}
                                                    </span>
                                                @endif
                                            </div>
                                        </div>

                                        @if($req->status === \App\Models\SponsoredEnrollmentRequest::STATUS_PENDING)
                                            <div class="flex items-center gap-2 mt-3 pt-3 border-t border-gray-100">
                                                <form method="POST" action="{{ route('manage.group-circles.sponsored-requests.approve', ['subdomain' => $subdomain, 'circle' => $circle->id, 'sponsoredRequest' => $req->id]) }}">
                                                    @csrf
                                                    <button type="button"
                                                        onclick="window.confirmAction({
                                                            title: @js(__('supervisor.group_circles.approve_request')),
                                                            message: @js(__('supervisor.group_circles.confirm_approve_sponsored')),
                                                            confirmText: @js(__('supervisor.group_circles.approve_request')),
                                                            isDangerous: false,
                                                            icon: 'ri-check-line',
                                                            onConfirm: () => this.closest('form').submit()
                                                        })"
                                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg bg-green-50 text-green-700 hover:bg-green-100 transition-colors cursor-pointer">
                                                        <i class="ri-check-line"></i>
                                                        {{ __('supervisor.group_circles.approve_request') }}
                                                    </button>
                                                </form>

                                                <button type="button"
                                                    @click="$dispatch('open-modal-reject-sponsored-{{ $req->id }}')"
                                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg bg-red-50 text-red-700 hover:bg-red-100 transition-colors cursor-pointer">
                                                    <i class="ri-close-line"></i>
                                                    {{ __('supervisor.group_circles.reject_request') }}
                                                </button>

                                                <x-responsive.modal id="reject-sponsored-{{ $req->id }}" :title="__('supervisor.group_circles.reject_request')" size="sm">
                                                    <form method="POST" action="{{ route('manage.group-circles.sponsored-requests.reject', ['subdomain' => $subdomain, 'circle' => $circle->id, 'sponsoredRequest' => $req->id]) }}">
                                                        @csrf
                                                        <div class="space-y-4">
                                                            <div>
                                                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('supervisor.group_circles.rejection_reason') }}</label>
                                                                <textarea name="rejection_reason" rows="3" required
                                                                          placeholder="{{ __('supervisor.group_circles.rejection_reason_placeholder') }}"
                                                                          class="w-full rounded-lg border-gray-300 text-sm focus:border-red-500 focus:ring-red-500"></textarea>
                                                            </div>
                                                        </div>
                                                        <x-slot:footer>
                                                            <div class="flex justify-end gap-3">
                                                                <button type="submit"
                                                                        class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors cursor-pointer">
                                                                    {{ __('supervisor.group_circles.reject_request') }}
                                                                </button>
                                                            </div>
                                                        </x-slot:footer>
                                                    </form>
                                                </x-responsive.modal>
                                            </div>
                                        @endif

                                        @if($req->status === \App\Models\SponsoredEnrollmentRequest::STATUS_REJECTED && $req->rejection_reason)
                                            <div class="mt-3 pt-3 border-t border-gray-100">
                                                <p class="text-xs text-red-600"><span class="font-medium">{{ __('supervisor.group_circles.rejection_reason') }}:</span> {{ $req->rejection_reason }}</p>
                                                @if($req->reviewer)
                                                    <p class="text-xs text-gray-500 mt-1">{{ __('supervisor.group_circles.reviewed_by_label') }}: {{ $req->reviewer->name }} — {{ $req->reviewed_at->format('Y/m/d') }}</p>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-8 md:py-12">
                                <div class="w-16 h-16 md:w-20 md:h-20 bg-pink-100 rounded-full flex items-center justify-center mx-auto mb-3 md:mb-4">
                                    <i class="ri-heart-line text-2xl md:text-3xl text-pink-500"></i>
                                </div>
                                <h3 class="text-base md:text-lg font-bold text-gray-900 mb-1 md:mb-2">{{ __('supervisor.group_circles.no_sponsored_requests') }}</h3>
                            </div>
                        @endif
                    </x-tabs.panel>
                    @endif

                    <x-tabs.panel id="certificates">
                        @php
                            $certificates = \App\Models\Certificate::whereIn('student_id', $circle->students->pluck('id'))
                                ->where('certificate_type', 'quran_subscription')
                                ->latest('issued_at')
                                ->get();
                        @endphp

                        @if($certificates->count() > 0)
                            <div class="bg-green-50 rounded-lg p-3 md:p-4 mb-4 md:mb-6 border border-green-200">
                                <p class="text-xs md:text-sm text-green-800 font-medium flex items-center gap-1">
                                    <i class="ri-checkbox-circle-fill flex-shrink-0"></i>
                                    <span>{{ __('teacher.circles_list.group.show.certificates_issued_count', ['count' => $certificates->count()]) }}</span>
                                </p>
                            </div>

                            <div class="space-y-3 md:space-y-4">
                                @foreach($certificates as $certificate)
                                    <div class="bg-white rounded-lg md:rounded-xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow md:flex md:items-center">
                                        <div class="bg-gradient-to-r from-amber-50 to-yellow-50 px-3 md:px-4 py-2.5 md:py-3 border-b md:border-b-0 md:border-e border-amber-100 md:min-w-[200px] md:self-stretch md:flex md:items-center">
                                            <div class="flex items-center gap-2 md:gap-3">
                                                <x-avatar :user="$certificate->student" size="sm" user-type="student" />
                                                <div class="min-w-0 flex-1">
                                                    <p class="font-bold text-gray-900 text-sm truncate">{{ $certificate->student->name }}</p>
                                                    <p class="text-xs text-gray-600 truncate">{{ $certificate->certificate_number }}</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="p-3 md:p-4 flex-1 md:flex md:items-center md:justify-between md:gap-4">
                                            <div class="flex items-center gap-1.5 md:gap-2 text-xs md:text-sm text-gray-600 mb-2 md:mb-0">
                                                <i class="ri-calendar-line text-amber-500"></i>
                                                <span>{{ $certificate->issued_at->locale(app()->getLocale())->translatedFormat('d F Y') }}</span>
                                            </div>
                                            <div class="flex gap-2 md:shrink-0">
                                                <a href="{{ route('student.certificate.view', ['subdomain' => $subdomain, 'certificate' => $certificate->id]) }}"
                                                   target="_blank"
                                                   class="min-h-[40px] md:min-h-[44px] flex-1 md:flex-initial inline-flex items-center justify-center gap-1 px-3 md:px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white text-xs font-medium rounded-lg transition-colors">
                                                    <i class="ri-eye-line"></i>
                                                    {{ __('supervisor.certificates.view_certificate') }}
                                                </a>
                                                <a href="{{ route('student.certificate.download', ['subdomain' => $subdomain, 'certificate' => $certificate->id]) }}"
                                                   class="min-h-[40px] md:min-h-[44px] flex-1 md:flex-initial inline-flex items-center justify-center gap-1 px-3 md:px-4 py-2 bg-green-500 hover:bg-green-600 text-white text-xs font-medium rounded-lg transition-colors">
                                                    <i class="ri-download-line"></i>
                                                    {{ __('supervisor.certificates.download_certificate') }}
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-8 md:py-12">
                                <div class="w-16 h-16 md:w-20 md:h-20 bg-amber-100 rounded-full flex items-center justify-center mx-auto mb-3 md:mb-4">
                                    <i class="ri-award-line text-2xl md:text-3xl text-amber-500"></i>
                                </div>
                                <h3 class="text-base md:text-lg font-bold text-gray-900 mb-1 md:mb-2">{{ __('teacher.circles_list.group.show.no_certificates') }}</h3>
                                <p class="text-gray-600 text-xs md:text-sm">{{ __('teacher.circles_list.group.show.no_certificates_issued') }}</p>
                            </div>
                        @endif
                    </x-tabs.panel>
                </x-slot>
            </x-tabs>
        </div>

        <!-- Sidebar -->
        <div class="lg:col-span-1" data-sticky-sidebar>
            <div class="space-y-4 md:space-y-6">
                <x-circle.info-sidebar :circle="$circle" view-type="supervisor" />

                {{-- Circle Actions Widget --}}
                @if(isset($isAdmin) || isset($quranTeachers))
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 md:p-6">
                    <h3 class="text-sm font-bold text-gray-900 mb-4">{{ __('supervisor.group_circles.circle_actions') }}</h3>
                    <div class="flex flex-col gap-2">
                        {{-- Toggle Status --}}
                        <form id="toggle-status-form" method="POST"
                              action="{{ route('manage.group-circles.toggle-status', ['subdomain' => $subdomain, 'circle' => $circle->id]) }}">
                            @csrf
                        </form>
                        <button type="button"
                            onclick="window.confirmAction({
                                title: @js($circle->status ? __('supervisor.group_circles.deactivate') : __('supervisor.group_circles.activate')),
                                message: @js($circle->status ? __('supervisor.group_circles.confirm_deactivate') : __('supervisor.group_circles.confirm_activate')),
                                confirmText: @js($circle->status ? __('supervisor.group_circles.deactivate') : __('supervisor.group_circles.activate')),
                                isDangerous: {{ $circle->status ? 'true' : 'false' }},
                                icon: '{{ $circle->status ? 'ri-pause-circle-line' : 'ri-play-circle-line' }}',
                                onConfirm: () => document.getElementById('toggle-status-form').submit()
                            })"
                            class="flex items-center justify-center gap-1.5 w-full px-3 py-2 text-xs md:text-sm font-medium rounded-lg transition-colors cursor-pointer
                                {{ $circle->status ? 'bg-orange-50 text-orange-700 hover:bg-orange-100' : 'bg-green-50 text-green-700 hover:bg-green-100' }}">
                            <i class="{{ $circle->status ? 'ri-pause-circle-line' : 'ri-play-circle-line' }}"></i>
                            {{ $circle->status ? __('supervisor.group_circles.deactivate') : __('supervisor.group_circles.activate') }}
                        </button>

                        {{-- Toggle Enrollment --}}
                        <form id="toggle-enrollment-form" method="POST"
                              action="{{ route('manage.group-circles.toggle-enrollment', ['subdomain' => $subdomain, 'circle' => $circle->id]) }}">
                            @csrf
                        </form>
                        @php $isOpen = $circle->enrollment_status === \App\Enums\CircleEnrollmentStatus::OPEN; @endphp
                        <button type="button"
                            onclick="window.confirmAction({
                                title: @js($isOpen ? __('supervisor.group_circles.close_enrollment') : __('supervisor.group_circles.open_enrollment')),
                                message: @js($isOpen ? __('supervisor.group_circles.confirm_close_enrollment') : __('supervisor.group_circles.confirm_open_enrollment')),
                                confirmText: @js($isOpen ? __('supervisor.group_circles.close_enrollment') : __('supervisor.group_circles.open_enrollment')),
                                isDangerous: {{ $isOpen ? 'true' : 'false' }},
                                icon: '{{ $isOpen ? 'ri-door-closed-line' : 'ri-door-open-line' }}',
                                onConfirm: () => document.getElementById('toggle-enrollment-form').submit()
                            })"
                            class="flex items-center justify-center gap-1.5 w-full px-3 py-2 text-xs md:text-sm font-medium rounded-lg transition-colors cursor-pointer
                                {{ $isOpen ? 'bg-amber-50 text-amber-700 hover:bg-amber-100' : 'bg-teal-50 text-teal-700 hover:bg-teal-100' }}">
                            <i class="{{ $isOpen ? 'ri-door-closed-line' : 'ri-door-open-line' }}"></i>
                            {{ $isOpen ? __('supervisor.group_circles.close_enrollment') : __('supervisor.group_circles.open_enrollment') }}
                        </button>

                        {{-- Change Teacher --}}
                        <button type="button"
                            onclick="window.dispatchEvent(new CustomEvent('open-modal-change-teacher'))"
                            class="flex items-center justify-center gap-1.5 w-full px-3 py-2 text-xs md:text-sm font-medium rounded-lg bg-blue-50 text-blue-700 hover:bg-blue-100 transition-colors cursor-pointer">
                            <i class="ri-user-settings-line"></i>
                            {{ $circle->quran_teacher_id ? __('supervisor.group_circles.change_teacher') : __('supervisor.group_circles.assign_teacher') }}
                        </button>

                        {{-- Delete (admin only) --}}
                        @if($isAdmin)
                            <form id="delete-circle-form" method="POST"
                                  action="{{ route('manage.group-circles.destroy', ['subdomain' => $subdomain, 'circle' => $circle->id]) }}">
                                @csrf
                                @method('DELETE')
                            </form>
                            <button type="button"
                                onclick="window.confirmAction({
                                    title: @js(__('supervisor.group_circles.delete_circle')),
                                    message: @js(__('supervisor.group_circles.confirm_delete')),
                                    confirmText: @js(__('supervisor.group_circles.delete_circle')),
                                    isDangerous: true,
                                    icon: 'ri-delete-bin-line',
                                    onConfirm: () => document.getElementById('delete-circle-form').submit()
                                })"
                                class="flex items-center justify-center gap-1.5 w-full px-3 py-2 text-xs md:text-sm font-medium rounded-lg bg-red-50 text-red-700 hover:bg-red-100 transition-colors cursor-pointer">
                                <i class="ri-delete-bin-line"></i>
                                {{ __('supervisor.group_circles.delete_circle') }}
                            </button>
                        @endif
                    </div>
                </div>

                {{-- Change Teacher Modal --}}
                <x-responsive.modal id="change-teacher" :title="__('supervisor.group_circles.change_teacher')" size="sm">
                    <form method="POST" action="{{ route('manage.group-circles.change-teacher', ['subdomain' => $subdomain, 'circle' => $circle->id]) }}">
                        @csrf
                        <div class="space-y-4">
                            <p class="text-sm text-gray-600">{{ __('supervisor.group_circles.select_teacher') }}</p>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('supervisor.group_circles.quran_teacher') }}</label>
                                <select name="quran_teacher_id" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500" required>
                                    @foreach($quranTeachers as $t)
                                        <option value="{{ $t->id }}" {{ $circle->quran_teacher_id == $t->id ? 'selected' : '' }}>{{ $t->first_name }} {{ $t->last_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <x-slot:footer>
                            <div class="flex justify-end gap-3">
                                <button type="submit"
                                        class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors cursor-pointer">
                                    {{ __('supervisor.group_circles.change_teacher') }}
                                </button>
                            </div>
                        </x-slot:footer>
                    </form>
                </x-responsive.modal>

                {{-- Edit Details Widget --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 md:p-6">
                    <h3 class="text-sm font-bold text-gray-900 mb-4">{{ __('supervisor.common.edit_details') }}</h3>
                    <form method="POST" action="{{ route('manage.group-circles.update', ['subdomain' => $subdomain, 'circle' => $circle->id]) }}">
                        @csrf
                        @method('PUT')
                        <div class="space-y-4">

                            {{-- Section 1: Basic Circle Info --}}
                            <div>
                                <h4 class="text-xs font-bold text-blue-700 mb-3">{{ __('supervisor.group_circles.basic_info') }}</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <div class="md:col-span-2">
                                        <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('supervisor.group_circles.name') }}</label>
                                        <input type="text" name="name" value="{{ old('name', $circle->name) }}" maxlength="150"
                                               class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500" required>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('supervisor.group_circles.age_group') }}</label>
                                        <select name="age_group" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500" required>
                                            @foreach(\App\Filament\Shared\Resources\BaseQuranCircleResource::getAgeGroupOptionsStatic() as $value => $label)
                                                <option value="{{ $value }}" {{ old('age_group', $circle->age_group) == $value ? 'selected' : '' }}>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('supervisor.group_circles.gender_type') }}</label>
                                        <select name="gender_type" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500" required>
                                            @foreach(\App\Filament\Shared\Resources\BaseQuranCircleResource::getGenderTypeOptionsStatic() as $value => $label)
                                                <option value="{{ $value }}" {{ old('gender_type', $circle->gender_type) == $value ? 'selected' : '' }}>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('supervisor.group_circles.specialization_label') }}</label>
                                        <select name="specialization" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500" required>
                                            @foreach(\App\Filament\Shared\Resources\BaseQuranCircleResource::getSpecializationOptionsStatic() as $value => $label)
                                                <option value="{{ $value }}" {{ old('specialization', $circle->specialization) == $value ? 'selected' : '' }}>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('supervisor.group_circles.memorization_level') }}</label>
                                        <select name="memorization_level" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500" required>
                                            @foreach(\App\Enums\DifficultyLevel::options() as $value => $label)
                                                <option value="{{ $value }}" {{ old('memorization_level', $circle->memorization_level) == $value ? 'selected' : '' }}>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="md:col-span-2">
                                        <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('supervisor.group_circles.description') }}</label>
                                        <textarea name="description" rows="2" maxlength="500"
                                                  class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">{{ old('description', $circle->description) }}</textarea>
                                    </div>
                                </div>
                            </div>

                            {{-- Section 2: Circle Settings --}}
                            <div class="border-t border-gray-100 pt-4">
                                <h4 class="text-xs font-bold text-blue-700 mb-3">{{ __('supervisor.group_circles.circle_settings') }}</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('supervisor.group_circles.max_students') }}</label>
                                        <input type="number" name="max_students" value="{{ old('max_students', $circle->max_students) }}" min="1" max="20"
                                               class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500" required>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('supervisor.group_circles.monthly_fee') }}</label>
                                        <input type="number" name="monthly_fee" value="{{ old('monthly_fee', (int) $circle->monthly_fee) }}" min="0" step="1"
                                               class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500" required>
                                    </div>
                                    <div class="md:col-span-2">
                                        <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('supervisor.group_circles.monthly_sessions_count') }}</label>
                                        <select name="monthly_sessions_count" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500" required>
                                            @foreach(\App\Filament\Shared\Resources\BaseQuranCircleResource::getMonthlySessionsOptionsStatic() as $value => $label)
                                                <option value="{{ $value }}" {{ old('monthly_sessions_count', $circle->monthly_sessions_count) == $value ? 'selected' : '' }}>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="mt-3 space-y-2">
                                    <label class="inline-flex items-center gap-2 cursor-pointer">
                                        <input type="hidden" name="allow_sponsored_requests" value="0">
                                        <input type="checkbox" name="allow_sponsored_requests" value="1"
                                               {{ old('allow_sponsored_requests', $circle->allow_sponsored_requests) ? 'checked' : '' }}
                                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 w-4 h-4">
                                        <span class="text-sm text-gray-700">{{ __('supervisor.group_circles.allow_sponsored_requests') }}</span>
                                    </label>
                                    <p class="text-xs text-gray-500 ms-6">{{ __('supervisor.group_circles.allow_sponsored_requests_help') }}</p>
                                    <label class="inline-flex items-center gap-2 cursor-pointer">
                                        <input type="hidden" name="is_enrolled_only" value="0">
                                        <input type="checkbox" name="is_enrolled_only" value="1"
                                               {{ old('is_enrolled_only', $circle->is_enrolled_only) ? 'checked' : '' }}
                                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 w-4 h-4">
                                        <span class="text-sm text-gray-700">{{ __('supervisor.group_circles.is_enrolled_only') }}</span>
                                    </label>
                                    <p class="text-xs text-gray-500 ms-6">{{ __('supervisor.group_circles.is_enrolled_only_help') }}</p>
                                </div>
                            </div>

                            {{-- Section 3: Schedule --}}
                            <div class="border-t border-gray-100 pt-4">
                                <h4 class="text-xs font-bold text-blue-700 mb-3">{{ __('supervisor.group_circles.schedule_section') }}</h4>
                                <div class="space-y-3">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-2">{{ __('supervisor.group_circles.schedule_days') }}</label>
                                        @php $currentDays = old('schedule_days', $circle->schedule_days ?? []); @endphp
                                        <div class="flex flex-wrap gap-2">
                                            @foreach(\App\Enums\WeekDays::options() as $value => $label)
                                                <label class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border text-xs cursor-pointer transition-colors
                                                    {{ in_array($value, $currentDays) ? 'bg-blue-50 border-blue-300 text-blue-700' : 'bg-white border-gray-200 text-gray-600 hover:border-gray-300' }}">
                                                    <input type="checkbox" name="schedule_days[]" value="{{ $value }}"
                                                           {{ in_array($value, $currentDays) ? 'checked' : '' }}
                                                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 w-3.5 h-3.5">
                                                    {{ $label }}
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('supervisor.group_circles.schedule_time') }}</label>
                                        <select name="schedule_time" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                                            <option value="">--</option>
                                            @foreach(\App\Filament\Shared\Resources\BaseQuranCircleResource::getScheduleTimeOptionsStatic() as $value => $label)
                                                <option value="{{ $value }}" {{ old('schedule_time', $circle->schedule_time) == $value ? 'selected' : '' }}>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>

                            {{-- Section 4: Recording --}}
                            <div class="border-t border-gray-100 pt-4">
                                <h4 class="text-xs font-bold text-blue-700 mb-3">{{ __('recordings.recording_settings') }}</h4>
                                <div class="space-y-2">
                                    <label class="inline-flex items-center gap-2 cursor-pointer">
                                        <input type="hidden" name="recording_enabled" value="0">
                                        <input type="checkbox" name="recording_enabled" value="1"
                                               {{ old('recording_enabled', $circle->recording_enabled) ? 'checked' : '' }}
                                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 w-4 h-4">
                                        <span class="text-sm text-gray-700">{{ __('recordings.enable_audio_recording') }}</span>
                                    </label>
                                    <label class="inline-flex items-center gap-2 cursor-pointer">
                                        <input type="hidden" name="show_recording_to_teacher" value="0">
                                        <input type="checkbox" name="show_recording_to_teacher" value="1"
                                               {{ old('show_recording_to_teacher', $circle->show_recording_to_teacher ?? false) ? 'checked' : '' }}
                                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 w-4 h-4">
                                        <span class="text-sm text-gray-700">{{ __('recordings.show_to_teacher') }}</span>
                                    </label>
                                    <label class="inline-flex items-center gap-2 cursor-pointer">
                                        <input type="hidden" name="show_recording_to_student" value="0">
                                        <input type="checkbox" name="show_recording_to_student" value="1"
                                               {{ old('show_recording_to_student', $circle->show_recording_to_student ?? false) ? 'checked' : '' }}
                                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 w-4 h-4">
                                        <span class="text-sm text-gray-700">{{ __('recordings.show_to_student') }}</span>
                                    </label>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">{{ __('recordings.enable_audio_recording_help') }}</p>
                            </div>

                            {{-- Section 5: Notes --}}
                            <div class="border-t border-gray-100 pt-4">
                                <h4 class="text-xs font-bold text-blue-700 mb-3">{{ __('supervisor.group_circles.status_and_notes') }}</h4>
                                <div class="space-y-3">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('supervisor.group_circles.supervisor_notes') }}</label>
                                        @if($isAdmin)
                                            <div class="w-full rounded-lg border border-gray-200 bg-gray-50 text-sm text-gray-600 p-2 min-h-[3.5rem]">{{ $circle->supervisor_notes ?: '—' }}</div>
                                        @else
                                            <textarea name="supervisor_notes" rows="2" maxlength="2000"
                                                      class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">{{ old('supervisor_notes', $circle->supervisor_notes) }}</textarea>
                                        @endif
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('supervisor.group_circles.admin_notes') }}</label>
                                        @if($isAdmin)
                                            <textarea name="admin_notes" rows="2" maxlength="1000"
                                                      class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">{{ old('admin_notes', $circle->admin_notes) }}</textarea>
                                        @else
                                            <div class="w-full rounded-lg border border-gray-200 bg-gray-50 text-sm text-gray-600 p-2 min-h-[3.5rem]">{{ $circle->admin_notes ?: '—' }}</div>
                                        @endif
                                    </div>
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
