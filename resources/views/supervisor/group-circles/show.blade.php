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

                @if(isset($isAdmin) && $isAdmin)
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
                                    <div class="md:col-span-2">
                                        <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('supervisor.group_circles.quran_teacher') }}</label>
                                        <select name="quran_teacher_id" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500" required>
                                            @foreach($quranTeachers as $t)
                                                <option value="{{ $t->id }}" {{ old('quran_teacher_id', $circle->quran_teacher_id) == $t->id ? 'selected' : '' }}>{{ $t->first_name }} {{ $t->last_name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('supervisor.group_circles.max_students') }}</label>
                                        <input type="number" name="max_students" value="{{ old('max_students', $circle->max_students) }}" min="1" max="20"
                                               class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500" required>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('supervisor.group_circles.monthly_fee') }}</label>
                                        <input type="number" name="monthly_fee" value="{{ old('monthly_fee', $circle->monthly_fee) }}" min="0" step="0.01"
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

                            {{-- Section 4: Status & Notes --}}
                            <div class="border-t border-gray-100 pt-4">
                                <h4 class="text-xs font-bold text-blue-700 mb-3">{{ __('supervisor.group_circles.status_and_notes') }}</h4>
                                <div class="space-y-3">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('supervisor.group_circles.circle_status') }}</label>
                                        <select name="status" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500" required>
                                            <option value="1" {{ old('status', $circle->status) ? 'selected' : '' }}>{{ __('supervisor.common.active') }}</option>
                                            <option value="0" {{ !old('status', $circle->status) ? 'selected' : '' }}>{{ __('supervisor.common.inactive') }}</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('supervisor.group_circles.supervisor_notes') }}</label>
                                        <textarea name="supervisor_notes" rows="2" maxlength="2000"
                                                  class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">{{ old('supervisor_notes', $circle->supervisor_notes) }}</textarea>
                                    </div>
                                    @if($isAdmin)
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('supervisor.group_circles.admin_notes') }}</label>
                                            <textarea name="admin_notes" rows="2" maxlength="1000"
                                                      class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">{{ old('admin_notes', $circle->admin_notes) }}</textarea>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <button type="submit"
                                    class="w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
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
