<div>
    {{-- Trigger Button --}}
    <button wire:click="open" type="button"
        class="inline-flex items-center gap-2 px-4 py-2.5 bg-amber-600 text-white rounded-lg hover:bg-amber-700 transition-colors text-sm font-medium shadow-sm">
        <i class="ri-add-line text-lg"></i>
        {{ __('supervisor.trial_sessions.create_new_request') }}
    </button>

    {{-- Modal Backdrop --}}
    @if($showModal)
    <div class="fixed inset-0 z-50 overflow-y-auto" x-data x-transition>
        <div class="flex min-h-full items-center justify-center p-4">
            {{-- Backdrop --}}
            <div class="fixed inset-0 bg-gray-900/50 transition-opacity" wire:click="$set('showModal', false)"></div>

            {{-- Modal Content --}}
            <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-2xl transform transition-all">
                {{-- Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                    <div>
                        <h2 class="text-lg font-bold text-gray-900">{{ __('supervisor.trial_sessions.create_modal_title') }}</h2>
                        <p class="text-sm text-gray-500 mt-0.5">{{ __('supervisor.trial_sessions.create_modal_subtitle') }}</p>
                    </div>
                    <button wire:click="$set('showModal', false)" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <i class="ri-close-line text-xl"></i>
                    </button>
                </div>

                {{-- Body --}}
                <form wire:submit="create" class="px-6 py-5 space-y-6 max-h-[70vh] overflow-y-auto">

                    {{-- Section: Student & Teacher --}}
                    <div>
                        <h3 class="text-sm font-semibold text-gray-700 mb-3 flex items-center gap-2">
                            <i class="ri-user-line text-amber-600"></i>
                            {{ __('supervisor.trial_sessions.student_teacher_section') }}
                        </h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            {{-- Student Select --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('supervisor.trial_sessions.student') }} <span class="text-red-500">*</span></label>
                                <select wire:model.live="student_id"
                                    class="w-full rounded-lg border-gray-300 text-sm focus:border-amber-500 focus:ring-amber-500">
                                    <option value="">{{ __('supervisor.trial_sessions.select_student') }}</option>
                                    @foreach($students as $student)
                                        <option value="{{ $student['id'] }}">{{ $student['name'] }}</option>
                                    @endforeach
                                </select>
                                @error('student_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>

                            {{-- Teacher Select --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('supervisor.trial_sessions.teacher') }} <span class="text-red-500">*</span></label>
                                <select wire:model="teacher_id"
                                    class="w-full rounded-lg border-gray-300 text-sm focus:border-amber-500 focus:ring-amber-500">
                                    <option value="">{{ __('supervisor.trial_sessions.select_teacher') }}</option>
                                    @foreach($teachers as $teacher)
                                        <option value="{{ $teacher['id'] }}">{{ $teacher['name'] }}</option>
                                    @endforeach
                                </select>
                                @error('teacher_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        {{-- Auto-filled Student Info --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('supervisor.trial_sessions.student_name') }} <span class="text-red-500">*</span></label>
                                <input type="text" wire:model="student_name"
                                    class="w-full rounded-lg border-gray-300 text-sm focus:border-amber-500 focus:ring-amber-500"
                                    placeholder="{{ __('supervisor.trial_sessions.student_name_placeholder') }}">
                                @error('student_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('supervisor.trial_sessions.student_age') }}</label>
                                <input type="number" wire:model="student_age" min="3" max="100"
                                    class="w-full rounded-lg border-gray-300 text-sm focus:border-amber-500 focus:ring-amber-500">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('supervisor.trial_sessions.phone') }}</label>
                                <input type="tel" wire:model="phone" dir="ltr"
                                    class="w-full rounded-lg border-gray-300 text-sm focus:border-amber-500 focus:ring-amber-500 text-left">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('supervisor.trial_sessions.email') }}</label>
                                <input type="email" wire:model="email" dir="ltr"
                                    class="w-full rounded-lg border-gray-300 text-sm focus:border-amber-500 focus:ring-amber-500 text-left">
                            </div>
                        </div>
                    </div>

                    {{-- Section: Learning Details --}}
                    <div>
                        <h3 class="text-sm font-semibold text-gray-700 mb-3 flex items-center gap-2">
                            <i class="ri-book-open-line text-amber-600"></i>
                            {{ __('supervisor.trial_sessions.learning_details_section') }}
                        </h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('supervisor.trial_sessions.current_level') }} <span class="text-red-500">*</span></label>
                                <select wire:model="current_level"
                                    class="w-full rounded-lg border-gray-300 text-sm focus:border-amber-500 focus:ring-amber-500">
                                    <option value="">{{ __('supervisor.trial_sessions.select_level') }}</option>
                                    @foreach($this->levelOptions as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('current_level') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('supervisor.trial_sessions.preferred_time') }}</label>
                                <select wire:model="preferred_time"
                                    class="w-full rounded-lg border-gray-300 text-sm focus:border-amber-500 focus:ring-amber-500">
                                    <option value="">{{ __('supervisor.trial_sessions.select_time') }}</option>
                                    @foreach($this->timeOptions as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        {{-- Learning Goals --}}
                        <div class="mt-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('supervisor.trial_sessions.learning_goals') }}</label>
                            <div class="grid grid-cols-2 gap-2">
                                @foreach($this->goalOptions as $value => $label)
                                    <label class="flex items-center gap-2 p-2 rounded-lg border border-gray-200 hover:bg-gray-50 cursor-pointer transition-colors">
                                        <input type="checkbox" wire:model="learning_goals" value="{{ $value }}"
                                            class="rounded border-gray-300 text-amber-600 focus:ring-amber-500">
                                        <span class="text-sm text-gray-700">{{ $label }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        {{-- Notes --}}
                        <div class="mt-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('supervisor.trial_sessions.notes') }}</label>
                            <textarea wire:model="notes" rows="3"
                                class="w-full rounded-lg border-gray-300 text-sm focus:border-amber-500 focus:ring-amber-500"
                                placeholder="{{ __('supervisor.trial_sessions.notes_placeholder') }}"></textarea>
                        </div>
                    </div>
                </form>

                {{-- Footer --}}
                <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-200 bg-gray-50 rounded-b-2xl">
                    <button wire:click="$set('showModal', false)" type="button"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                        {{ __('supervisor.trial_sessions.cancel') }}
                    </button>
                    <button wire:click="create" type="button"
                        class="px-5 py-2 text-sm font-medium text-white bg-amber-600 rounded-lg hover:bg-amber-700 transition-colors"
                        wire:loading.attr="disabled"
                        wire:loading.class="opacity-50 cursor-not-allowed">
                        <span wire:loading.remove wire:target="create">{{ __('supervisor.trial_sessions.create_request') }}</span>
                        <span wire:loading wire:target="create" class="flex items-center gap-2">
                            <i class="ri-loader-4-line animate-spin"></i>
                            {{ __('supervisor.trial_sessions.creating') }}
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
