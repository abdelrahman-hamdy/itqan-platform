<div>
    <x-ui.breadcrumb
        :items="[
            ['label' => __('supervisor.subscriptions.page_title'), 'url' => route('manage.subscriptions.index', ['subdomain' => auth()->user()->academy?->subdomain])],
            ['label' => __('subscriptions.create_full_subscription')],
        ]"
        view-type="supervisor"
    />

    <div class="mb-6">
        <h1 class="text-xl sm:text-2xl font-bold text-gray-900">{{ __('subscriptions.create_full_subscription') }}</h1>
    </div>

    @if (session('error'))
        <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-700">{{ session('error') }}</div>
    @endif

    {{-- Step Indicator --}}
    <div class="flex items-center gap-2 mb-8">
        @for ($i = 1; $i <= $totalSteps; $i++)
            <div class="flex items-center">
                <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold {{ $currentStep >= $i ? 'bg-primary-600 text-white' : 'bg-gray-200 text-gray-500' }}">{{ $i }}</div>
                @if ($i < $totalSteps)<div class="w-8 h-0.5 {{ $currentStep > $i ? 'bg-primary-600' : 'bg-gray-200' }}"></div>@endif
            </div>
        @endfor
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">

        {{-- ═══ STEP 1 ═══ --}}
        @if ($currentStep === 1)
            @php
                // Shared variables for step 1
                $isQuranType = in_array($subscription_type, ['quran_individual', 'quran_group']);
                $teacherUserType = $isQuranType ? 'quran_teacher' : 'academic_teacher';
                // Batch-load User models for search results (for <x-avatar> component)
                $searchUserModels = !empty($searchResults) ? \App\Models\User::whereIn('id', collect($searchResults)->pluck('id'))->get()->keyBy('id') : collect();
            @endphp
            <h2 class="text-lg font-semibold mb-4">{{ __('subscriptions.wizard_step1_title') }}</h2>
            <div class="space-y-5">

                {{-- Type --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('subscriptions.subscription_type_label') }}</label>
                    <select wire:model.live="subscription_type" class="w-full rounded-lg border-gray-300">
                        <option value="quran_individual">{{ __('subscriptions.type_quran_individual') }}</option>
                        <option value="quran_group">{{ __('subscriptions.type_quran_group') }}</option>
                        <option value="academic">{{ __('subscriptions.type_academic') }}</option>
                    </select>
                </div>

                {{-- Student (Alpine instant selection) --}}
                <div x-data="{
                    selected: @js($student_id ? ['id' => $student_id, 'name' => $selectedStudentName, 'email' => $selectedStudentEmail] : null),
                    select(id, name, email) {
                        this.selected = { id, name, email };
                        $wire.set('student_id', id);
                        $wire.set('selectedStudentName', name);
                        $wire.set('selectedStudentEmail', email);
                        $wire.set('student_search', '');
                        $wire.set('searchResults', []);
                    },
                    clear() {
                        this.selected = null;
                        $wire.call('clearStudent');
                    }
                }">
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('subscriptions.student_label') }}</label>

                    {{-- Server-rendered chip (with <x-avatar>) when student is already selected --}}
                    @if($student_id)
                        @php $selectedStudentUser = \App\Models\User::find($student_id); @endphp
                        @if($selectedStudentUser)
                            <div class="flex items-center gap-3 p-3 bg-gray-50 border border-gray-200 rounded-lg">
                                <x-avatar :user="$selectedStudentUser" size="xs" userType="student" />
                                <div class="flex-1 min-w-0">
                                    <div class="font-semibold text-gray-900 truncate text-sm">{{ $selectedStudentName }}</div>
                                    <div class="text-xs text-gray-500 truncate">{{ $selectedStudentEmail }}</div>
                                </div>
                                <button type="button" @click="clear()" class="p-1.5 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-lg"><i class="ri-close-line text-lg"></i></button>
                            </div>
                        @endif
                    @endif

                    {{-- Alpine instant chip (client-side, before server re-renders) --}}
                    <template x-if="selected && !{{ $student_id ? 'true' : 'false' }}">
                        <div class="flex items-center gap-3 p-3 bg-gray-50 border border-gray-200 rounded-lg">
                            <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0">
                                <i class="ri-user-line text-blue-600 text-sm"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="font-semibold text-gray-900 truncate text-sm" x-text="selected.name"></div>
                                <div class="text-xs text-gray-500 truncate" x-text="selected.email"></div>
                            </div>
                            <button type="button" @click="clear()" class="p-1.5 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-lg"><i class="ri-close-line text-lg"></i></button>
                        </div>
                    </template>

                    <template x-if="!selected">
                        <div>
                            <div class="relative">
                                <input type="text" wire:model.live.debounce.300ms="student_search" class="w-full rounded-lg border-gray-300 pe-10" placeholder="{{ __('subscriptions.search_student_placeholder') }}">
                                <div class="absolute inset-y-0 end-0 flex items-center pe-3 pointer-events-none"><i class="ri-search-line text-gray-400"></i></div>
                            </div>
                            @if (count($searchResults) > 0)
                                <div class="mt-1 bg-white border rounded-lg shadow-lg max-h-48 overflow-y-auto z-10 relative">
                                    @foreach ($searchResults as $r)
                                        @php $searchUser = $searchUserModels->get($r['id']); @endphp
                                        <button type="button" @click="select({{ $r['id'] }}, {{ Js::from($r['name']) }}, {{ Js::from($r['email']) }})"
                                                class="w-full text-start px-3 py-2.5 hover:bg-gray-50 flex items-center gap-3 border-b border-gray-50 last:border-0">
                                            @if($searchUser)<x-avatar :user="$searchUser" size="xs" userType="student" />@endif
                                            <div class="min-w-0">
                                                <div class="text-sm font-medium text-gray-900 truncate">{{ $r['name'] }}</div>
                                                <div class="text-xs text-gray-500 truncate">{{ $r['email'] }}</div>
                                            </div>
                                        </button>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </template>
                    @error('student_id') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                </div>

                {{-- Teacher (Alpine instant selection) --}}
                @php $selectedTeacherData = $teacher_id ? collect($availableTeachers)->firstWhere('id', $teacher_id) : null; @endphp
                <div x-data="{
                    selected: @js($selectedTeacherData),
                    searchQuery: '',
                    get filtered() {
                        if (!this.searchQuery) return @js($filteredTeachers);
                        const q = this.searchQuery.toLowerCase();
                        return @js($filteredTeachers).filter(t => t.name.toLowerCase().includes(q));
                    },
                    select(teacher) {
                        this.selected = teacher;
                        this.searchQuery = '';
                        $wire.call('selectTeacher', teacher.id);
                    },
                    clear() {
                        this.selected = null;
                        this.searchQuery = '';
                        $wire.call('clearTeacher');
                    }
                }">
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('subscriptions.teacher_label') }}</label>

                    {{-- Server-rendered teacher chip with <x-avatar> --}}
                    @if($teacher_id)
                        @php
                            $selectedTeacherProfile = $isQuranType
                                ? \App\Models\QuranTeacherProfile::with('user')->find($teacher_id)
                                : \App\Models\AcademicTeacherProfile::with('user')->find($teacher_id);
                            $selectedTeacherUser = $selectedTeacherProfile?->user;
                        @endphp
                        @if($selectedTeacherUser)
                            <div class="flex items-center gap-3 p-3 bg-gray-50 border border-gray-200 rounded-lg">
                                <x-avatar :user="$selectedTeacherUser" size="xs" :userType="$teacherUserType" />
                                <div class="flex-1 min-w-0">
                                    <div class="font-semibold text-gray-900 truncate text-sm">{{ trim($selectedTeacherUser->first_name.' '.$selectedTeacherUser->last_name) }}</div>
                                </div>
                                <button type="button" @click="clear()" class="p-1.5 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-lg"><i class="ri-close-line text-lg"></i></button>
                            </div>
                        @endif
                    @endif

                    {{-- Alpine instant teacher chip (before server re-renders) --}}
                    <template x-if="selected && !{{ $teacher_id ? 'true' : 'false' }}">
                        <div class="flex items-center gap-3 p-3 bg-gray-50 border border-gray-200 rounded-lg">
                            <div class="w-8 h-8 rounded-full {{ $teacherUserType === 'quran_teacher' ? 'bg-yellow-100' : 'bg-violet-100' }} flex items-center justify-center flex-shrink-0">
                                <i class="{{ $teacherUserType === 'quran_teacher' ? 'ri-book-read-line text-yellow-600' : 'ri-graduation-cap-line text-violet-600' }} text-sm"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="font-semibold text-gray-900 truncate text-sm" x-text="selected.name"></div>
                            </div>
                            <button type="button" @click="clear()" class="p-1.5 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-lg"><i class="ri-close-line text-lg"></i></button>
                        </div>
                    </template>

                    @if(!$teacher_id)
                        @php
                            // Batch-load teacher profiles with users for <x-avatar>
                            $profileModel = $isQuranType ? \App\Models\QuranTeacherProfile::class : \App\Models\AcademicTeacherProfile::class;
                            $teacherProfileModels = $profileModel::whereIn('id', collect($filteredTeachers)->pluck('id'))
                                ->with('user')->get()->keyBy('id');
                        @endphp
                        <div class="border rounded-lg overflow-hidden" x-data="{ q: '' }">
                            <div class="p-2 border-b bg-gray-50">
                                <input type="text" x-model="q" class="w-full rounded-lg border-gray-300 text-sm" placeholder="{{ __('subscriptions.search_teacher_placeholder') }}">
                            </div>
                            <div class="max-h-48 overflow-y-auto p-1 space-y-0.5">
                                @forelse ($filteredTeachers as $t)
                                    @php $tUser = $teacherProfileModels->get($t['id'])?->user; @endphp
                                    <button type="button"
                                            x-show="!q || '{{ mb_strtolower($t['name']) }}'.includes(q.toLowerCase())"
                                            @click="selected = {{ Js::from($t) }}; searchQuery = ''; $wire.call('selectTeacher', {{ $t['id'] }})"
                                            class="w-full flex items-center gap-3 p-2.5 rounded-lg hover:bg-gray-50 text-start">
                                        @if($tUser)<x-avatar :user="$tUser" size="xs" :userType="$teacherUserType" />@endif
                                        <span class="text-sm font-medium text-gray-900">{{ $t['name'] }}</span>
                                    </button>
                                @empty
                                    <div class="p-3 text-sm text-gray-500 text-center">{{ __('subscriptions.no_teachers_available') }}</div>
                                @endforelse
                            </div>
                        </div>
                    @endif
                    @error('teacher_id') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                </div>

                {{-- Group Circle --}}
                @if ($subscription_type === 'quran_group' && $teacher_id)
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('subscriptions.select_circle') }}</label>
                        <select wire:model="quran_circle_id" class="w-full rounded-lg border-gray-300">
                            <option value="">{{ __('subscriptions.select_circle') }}</option>
                            @foreach ($availableCircles as $circle)
                                <option value="{{ $circle['id'] }}">{{ $circle['name'] }} ({{ $circle['spots'] }} {{ __('subscriptions.spots_available') }})</option>
                            @endforeach
                        </select>
                        @error('quran_circle_id') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                    </div>
                @endif
            </div>
        @endif

        {{-- ═══ STEP 2 ═══ --}}
        @if ($currentStep === 2)
            <h2 class="text-lg font-semibold mb-4">{{ __('subscriptions.wizard_step2_title') }}</h2>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('subscriptions.select_package') }}</label>
                    <select wire:model.live="package_id" class="w-full rounded-lg border-gray-300">
                        <option value="">{{ __('subscriptions.select_package') }}</option>
                        @foreach ($availablePackages as $pkg)
                            <option value="{{ $pkg['id'] }}">{{ $pkg['name'] }} ({{ $pkg['sessions_per_month'] }} {{ __('subscriptions.sessions_per_month') }})</option>
                        @endforeach
                    </select>
                    @error('package_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('subscriptions.select_billing_cycle') }}</label>
                    <select wire:model.live="billing_cycle" class="w-full rounded-lg border-gray-300">
                        <option value="monthly">{{ __('enums.billing_cycle.monthly') }}</option>
                        <option value="quarterly">{{ __('enums.billing_cycle.quarterly') }}</option>
                        <option value="yearly">{{ __('enums.billing_cycle.yearly') }}</option>
                    </select>
                </div>
                <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">{{ __('subscriptions.package_price_label') }}</span>
                        <span class="text-lg font-bold text-gray-900">{{ number_format($amount, 2) }} {{ auth()->user()->academy?->currency?->value ?? 'SAR' }}</span>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">{{ __('subscriptions.discount_label') }}</label>
                        <input type="number" wire:model.live.debounce.300ms="discount" step="1" min="0" max="{{ $amount }}" class="w-full rounded-lg border-gray-300 text-sm" placeholder="0">
                    </div>
                    @if ($discount > 0)
                        <div class="flex items-center justify-between pt-2 border-t border-gray-200">
                            <span class="text-sm font-semibold text-gray-700">{{ __('subscriptions.final_price_label') }}</span>
                            <span class="text-lg font-bold text-green-600">{{ number_format($this->finalPrice, 2) }} {{ auth()->user()->academy?->currency?->value ?? 'SAR' }}</span>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        {{-- ═══ STEP 3: Payment ═══ --}}
        @if ($currentStep === 3)
            <h2 class="text-lg font-semibold mb-4">{{ __('subscriptions.wizard_step3_title') }}</h2>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('subscriptions.payment_source_label') }}</label>
                    <div class="flex gap-3">
                        <label class="flex-1 cursor-pointer">
                            <input type="radio" wire:model.live="payment_source" value="outside" class="peer sr-only">
                            <div class="text-center p-3 rounded-lg border-2 border-gray-200 peer-checked:border-green-600 peer-checked:bg-green-50 transition-all">
                                <i class="ri-hand-coin-line text-lg text-green-600 mb-1"></i>
                                <div class="text-sm font-medium">{{ __('subscriptions.paid_outside') }}</div>
                                <div class="text-xs text-gray-500 mt-0.5">{{ __('subscriptions.paid_outside_desc') }}</div>
                            </div>
                        </label>
                        <label class="flex-1 cursor-pointer">
                            <input type="radio" wire:model.live="payment_source" value="inside" class="peer sr-only">
                            <div class="text-center p-3 rounded-lg border-2 border-gray-200 peer-checked:border-blue-600 peer-checked:bg-blue-50 transition-all">
                                <i class="ri-bank-card-line text-lg text-blue-600 mb-1"></i>
                                <div class="text-sm font-medium">{{ __('subscriptions.paid_inside') }}</div>
                                <div class="text-xs text-gray-500 mt-0.5">{{ __('subscriptions.paid_inside_desc') }}</div>
                            </div>
                        </label>
                    </div>
                </div>
                @if ($payment_source === 'outside')
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('subscriptions.payment_reference_label') }}</label>
                        <input type="text" wire:model="payment_reference" class="w-full rounded-lg border-gray-300" placeholder="{{ __('subscriptions.payment_reference_placeholder') }}">
                    </div>
                @else
                    <div class="p-3 bg-blue-50 border border-blue-200 rounded-lg text-sm text-blue-700">
                        <i class="ri-information-line"></i> {{ __('subscriptions.pending_payment_notice') }}
                    </div>
                @endif
            </div>
        @endif

        {{-- ═══ STEP 4: Details ═══ --}}
        @if ($currentStep === 4)
            <h2 class="text-lg font-semibold mb-4">{{ __('subscriptions.wizard_step4_title') }}</h2>
            <div class="space-y-4">
                @if (in_array($subscription_type, ['quran_individual', 'quran_group']))
                    {{-- Learning Level (using QuranLearningLevel enum) --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('public.booking.quran.form.current_level_label') }}</label>
                        <select wire:model="memorization_level" class="w-full rounded-lg border-gray-300">
                            @foreach (\App\Enums\QuranLearningLevel::cases() as $level)
                                <option value="{{ $level->value }}">{{ $level->label() }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Specialization --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('subscriptions.specialization_label') }}</label>
                        <select wire:model="specialization" class="w-full rounded-lg border-gray-300">
                            <option value="memorization">{{ __('subscriptions.specialization_memorization') }}</option>
                            <option value="recitation">{{ __('subscriptions.specialization_recitation') }}</option>
                            <option value="tajweed">{{ __('subscriptions.specialization_tajweed') }}</option>
                            <option value="complete">{{ __('subscriptions.specialization_complete') }}</option>
                        </select>
                    </div>

                    {{-- Learning Goals (using LearningGoal enum — checkboxes) --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('public.booking.quran.form.learning_goals_label') }}</label>
                        <div class="space-y-2">
                            @foreach (\App\Enums\LearningGoal::cases() as $goal)
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" wire:model="learning_goals" value="{{ $goal->value }}"
                                           class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                    <span class="text-sm text-gray-700">{{ $goal->label() }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @endif

        {{-- Navigation --}}
        <div class="flex justify-between mt-6 pt-4 border-t">
            @if ($currentStep > 1)
                <button wire:click="previousStep" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200">{{ __('subscriptions.previous_step') }}</button>
            @else
                <div></div>
            @endif
            @if ($currentStep < $totalSteps)
                <button wire:click="nextStep" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700">{{ __('subscriptions.next_step') }}</button>
            @else
                <button wire:click="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">{{ __('subscriptions.create_full_subscription') }}</button>
            @endif
        </div>
    </div>
</div>
