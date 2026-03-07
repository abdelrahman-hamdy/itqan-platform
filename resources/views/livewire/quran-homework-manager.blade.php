<div>
    <!-- Homework Management Section -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-semibold text-gray-900">{{ __('components.sessions.homework.title') }}</h3>
            @if($homework)
            <button onclick="hwCall(this, 'openEditModal')"
                    class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded-lg transition-colors shadow-sm">
                <i class="ri-edit-line ms-1"></i>
                {{ __('components.sessions.homework.edit_homework') }}
            </button>
            @else
            <button onclick="hwCall(this, 'openAddModal')"
                    class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm rounded-lg transition-colors shadow-sm">
                <i class="ri-add-line ms-1"></i>
                {{ __('components.sessions.homework.add_homework') }}
            </button>
            @endif
        </div>

        @if($homework)
            <!-- Display Current Session Homework -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                <div class="bg-gray-50 border-b border-gray-200 p-6 rounded-t-xl">
                    <div class="flex items-center gap-3">
                        <i class="ri-file-text-line text-indigo-600 text-xl"></i>
                        <h4 class="text-lg font-semibold text-gray-900">{{ __('components.sessions.homework.session_homework') }}</h4>
                    </div>
                </div>

                <div class="p-6">
                    @php
                        $homeworkCount = 0;
                        if($homework->has_new_memorization) $homeworkCount++;
                        if($homework->has_review) $homeworkCount++;
                        if($homework->has_comprehensive_review && $homework->comprehensive_review_surahs) $homeworkCount++;
                    @endphp

                    <div class="grid gap-4 mb-6 @if($homeworkCount == 1) grid-cols-1 @elseif($homeworkCount == 2) grid-cols-1 md:grid-cols-2 @else grid-cols-1 md:grid-cols-2 lg:grid-cols-3 @endif">
                        @if($homework->has_new_memorization)
                        <div class="bg-gradient-to-br from-green-50 to-emerald-50 rounded-xl p-5 border border-green-200 shadow-sm hover:shadow-md transition-all duration-200 h-full">
                            <div class="flex items-center gap-3 mb-3">
                                <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center shrink-0">
                                    <i class="ri-book-open-line text-green-600 text-lg"></i>
                                </div>
                                <span class="text-sm text-green-700 font-semibold">{{ __('components.sessions.homework.new_memorization') }}</span>
                            </div>
                            @if($homework->new_memorization_surah)
                            <p class="text-green-900 font-bold text-lg mb-1">{{ \App\Enums\QuranSurah::getArabicName($homework->new_memorization_surah) }}</p>
                            @endif
                            @if($homework->new_memorization_pages)
                            <p class="text-green-700 text-sm font-medium">{{ (float) $homework->new_memorization_pages }} {{ __('components.sessions.homework.pages_count') }}</p>
                            @endif
                        </div>
                        @endif

                        @if($homework->has_review)
                        <div class="bg-gradient-to-br from-blue-50 to-cyan-50 rounded-xl p-5 border border-blue-200 shadow-sm hover:shadow-md transition-all duration-200 h-full">
                            <div class="flex items-center gap-3 mb-3">
                                <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center shrink-0">
                                    <i class="ri-refresh-line text-blue-600 text-lg"></i>
                                </div>
                                <span class="text-sm text-blue-700 font-semibold">{{ __('components.sessions.homework.review') }}</span>
                            </div>
                            @if($homework->review_surah)
                            <p class="text-blue-900 font-bold text-lg mb-1">{{ \App\Enums\QuranSurah::getArabicName($homework->review_surah) }}</p>
                            @endif
                            @if($homework->review_pages)
                            <p class="text-blue-700 text-sm font-medium">{{ (float) $homework->review_pages }} {{ __('components.sessions.homework.pages_count') }}</p>
                            @endif
                        </div>
                        @endif

                        @if($homework->has_comprehensive_review && $homework->comprehensive_review_surahs)
                        <div class="bg-gradient-to-br from-purple-50 to-violet-50 rounded-xl p-5 border border-purple-200 shadow-sm hover:shadow-md transition-all duration-200 h-full">
                            <div class="flex items-center gap-3 mb-3">
                                <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center shrink-0">
                                    <i class="ri-list-check text-purple-600 text-lg"></i>
                                </div>
                                <span class="text-sm text-purple-700 font-semibold">{{ __('components.sessions.homework.comprehensive_review') }}</span>
                            </div>
                            <div class="text-purple-900 font-bold text-base leading-relaxed">
                                @php
                                    $displaySurahs = [];
                                    $savedSurahs = $homework->comprehensive_review_surahs;
                                    if (is_string($savedSurahs)) {
                                        $savedSurahs = json_decode($savedSurahs, true) ?: [];
                                    }
                                    if (is_array($savedSurahs)) {
                                        $allSurahs = \App\Enums\QuranSurah::getAllSurahs();
                                        foreach ($savedSurahs as $surahKey) {
                                            if (isset($allSurahs[$surahKey])) {
                                                $displaySurahs[] = $allSurahs[$surahKey];
                                            } else {
                                                $displaySurahs[] = \App\Enums\QuranSurah::getArabicName($surahKey);
                                            }
                                        }
                                    }
                                @endphp
                                @if(count($displaySurahs) > 0)
                                    <div class="flex flex-wrap gap-2">
                                        @foreach($displaySurahs as $surah)
                                            <span class="inline-block bg-purple-100 text-purple-800 px-3 py-1 rounded-full text-sm font-medium">{{ $surah }}</span>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                        @endif
                    </div>

                    @if($homework->additional_instructions)
                    <div class="border-t border-gray-200 pt-6 mt-6">
                        <div class="bg-gradient-to-br from-amber-50 to-yellow-50 rounded-xl p-5 border border-amber-200 shadow-sm">
                            <div class="flex items-center gap-3 mb-3">
                                <div class="w-8 h-8 bg-amber-100 rounded-lg flex items-center justify-center shrink-0">
                                    <i class="ri-information-line text-amber-600 text-lg"></i>
                                </div>
                                <h5 class="font-semibold text-amber-900">{{ __('components.sessions.homework.additional_instructions') }}</h5>
                            </div>
                            <p class="text-amber-800 leading-relaxed">{{ $homework->additional_instructions }}</p>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        @else
            <!-- No Homework Assigned -->
            <div class="text-center py-12">
                <div class="w-20 h-20 mx-auto mb-4 bg-gray-100 rounded-full flex items-center justify-center">
                    <i class="ri-file-text-line text-3xl text-gray-400"></i>
                </div>
                <h3 class="text-xl font-medium text-gray-900 mb-2">{{ __('components.sessions.homework.no_homework') }}</h3>
                <p class="text-gray-600 mb-4">{{ __('components.sessions.homework.no_homework_message') }}</p>
            </div>
        @endif
    </div>

    <!-- Homework Modal -->
    @if($showModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
        {{-- Backdrop --}}
        <div class="fixed inset-0 bg-black/60 transition-opacity" onclick="hwCall(this, 'closeModal')"></div>

        {{-- Modal content --}}
        <div class="relative bg-white rounded-xl shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-y-auto z-10">
            <div class="p-6 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h3 class="text-xl font-bold text-gray-900">
                        {{ $homework ? __('components.sessions.homework.modal_title_edit') : __('components.sessions.homework.modal_title_add') }}
                    </h3>
                    <button onclick="hwCall(this, 'closeModal')" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <i class="ri-close-line text-2xl"></i>
                    </button>
                </div>
            </div>

            <div class="p-6">
                <div data-homework-form class="space-y-6">
                    <!-- Homework Type Selection -->
                    <div class="space-y-4">
                        <h4 class="text-lg font-semibold text-gray-900">{{ __('components.sessions.homework.homework_type') }}</h4>

                        <!-- New Memorization -->
                        <div class="border border-gray-200 rounded-lg p-4">
                            <div class="flex items-center mb-3">
                                <input type="checkbox" id="hw_has_new_memorization"
                                       {{ $has_new_memorization ? 'checked' : '' }}
                                       onchange="document.getElementById('hw-memorization-fields').classList.toggle('hidden', !this.checked)"
                                       class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                <label for="hw_has_new_memorization" class="ms-2 text-sm font-medium text-gray-900">
                                    {{ __('components.sessions.homework.new_memorization') }}
                                </label>
                            </div>

                            <div id="hw-memorization-fields" class="{{ $has_new_memorization ? '' : 'hidden' }}">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="hw_new_memorization_surah" class="block text-sm font-medium text-gray-700 mb-1">{{ __('components.sessions.homework.surah') }}</label>
                                        <select id="hw_new_memorization_surah"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            <option value="">{{ __('components.sessions.homework.select_surah') }}</option>
                                            @foreach($surahs as $key => $name)
                                                <option value="{{ $name }}" {{ $new_memorization_surah === $name ? 'selected' : '' }}>{{ $name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label for="hw_new_memorization_pages" class="block text-sm font-medium text-gray-700 mb-1">{{ __('components.sessions.homework.pages_number') }}</label>
                                        <input type="number" id="hw_new_memorization_pages" step="0.5" min="0.5" max="10"
                                               value="{{ $new_memorization_pages }}"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                               placeholder="مثال: 2 أو 1.5">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Review -->
                        <div class="border border-gray-200 rounded-lg p-4">
                            <div class="flex items-center mb-3">
                                <input type="checkbox" id="hw_has_review"
                                       {{ $has_review ? 'checked' : '' }}
                                       onchange="document.getElementById('hw-review-fields').classList.toggle('hidden', !this.checked)"
                                       class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                <label for="hw_has_review" class="ms-2 text-sm font-medium text-gray-900">
                                    {{ __('components.sessions.homework.review') }}
                                </label>
                            </div>

                            <div id="hw-review-fields" class="{{ $has_review ? '' : 'hidden' }}">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="hw_review_surah" class="block text-sm font-medium text-gray-700 mb-1">{{ __('components.sessions.homework.surah') }}</label>
                                        <select id="hw_review_surah"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            <option value="">{{ __('components.sessions.homework.select_surah') }}</option>
                                            @foreach($surahs as $key => $name)
                                                <option value="{{ $name }}" {{ $review_surah === $name ? 'selected' : '' }}>{{ $name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label for="hw_review_pages" class="block text-sm font-medium text-gray-700 mb-1">{{ __('components.sessions.homework.pages_number') }}</label>
                                        <input type="number" id="hw_review_pages" step="0.5" min="0.5" max="20"
                                               value="{{ $review_pages }}"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                               placeholder="مثال: 3 أو 2.5">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Comprehensive Review -->
                        <div class="border border-gray-200 rounded-lg p-4">
                            <div class="flex items-center mb-3">
                                <input type="checkbox" id="hw_has_comprehensive_review"
                                       {{ $has_comprehensive_review ? 'checked' : '' }}
                                       onchange="document.getElementById('hw-comprehensive-fields').classList.toggle('hidden', !this.checked)"
                                       class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                <label for="hw_has_comprehensive_review" class="ms-2 text-sm font-medium text-gray-900">
                                    {{ __('components.sessions.homework.comprehensive_review') }}
                                </label>
                            </div>

                            <div id="hw-comprehensive-fields" class="{{ $has_comprehensive_review ? '' : 'hidden' }}">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('components.sessions.homework.surahs') }}</label>
                                    <div class="max-h-48 overflow-y-auto border border-gray-300 rounded-lg p-3 bg-white">
                                        <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                                            @foreach($surahs as $key => $name)
                                                <label class="flex items-center gap-2">
                                                    <input type="checkbox" data-surah-cb value="{{ $key }}"
                                                           {{ is_array($comprehensive_review_surahs) && in_array($key, $comprehensive_review_surahs) ? 'checked' : '' }}
                                                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                                    <span class="text-sm text-gray-700">{{ $name }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-1">{{ __('components.sessions.homework.surahs_help') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label for="hw_additional_instructions" class="block text-sm font-medium text-gray-700 mb-1">{{ __('components.sessions.homework.additional_instructions') }}</label>
                        <textarea id="hw_additional_instructions" rows="3"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                  placeholder="{{ __('components.sessions.homework.additional_instructions_placeholder') }}">{{ $additional_instructions }}</textarea>
                    </div>

                    <div class="flex gap-3">
                        <button type="button" onclick="hwCall(this, 'closeModal')"
                                class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                            {{ __('components.sessions.homework.cancel') }}
                        </button>
                        <button type="button" onclick="hwSave(this)"
                                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors disabled:opacity-50">
                            <span data-save-text>{{ __('components.sessions.homework.save') }}</span>
                            <span data-save-loading style="display:none" class="inline-flex items-center">
                                <i class="ri-loader-line animate-spin ms-1"></i>
                                {{ __('components.sessions.homework.saving') }}
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <script>
    function hwGetComp(el) {
        var root = el.closest('[wire\\:id]');
        if (!root) return null;
        var id = root.getAttribute('wire:id');
        if (window.Livewire && window.Livewire.find) {
            return window.Livewire.find(id);
        }
        if (root.__livewire) {
            return root.__livewire;
        }
        return null;
    }

    function hwCall(el, method) {
        var comp = hwGetComp(el);
        if (comp) comp.call(method);
    }

    function hwSave(el) {
        var comp = hwGetComp(el);
        if (!comp) return;

        var root = el.closest('[wire\\:id]');
        var form = root.querySelector('[data-homework-form]');
        if (!form) return;

        var data = {
            has_new_memorization: !!form.querySelector('#hw_has_new_memorization')?.checked,
            has_review: !!form.querySelector('#hw_has_review')?.checked,
            has_comprehensive_review: !!form.querySelector('#hw_has_comprehensive_review')?.checked,
            new_memorization_surah: form.querySelector('#hw_new_memorization_surah')?.value || null,
            new_memorization_pages: form.querySelector('#hw_new_memorization_pages')?.value || null,
            review_surah: form.querySelector('#hw_review_surah')?.value || null,
            review_pages: form.querySelector('#hw_review_pages')?.value || null,
            comprehensive_review_surahs: Array.from(form.querySelectorAll('[data-surah-cb]:checked')).map(function(cb) { return cb.value; }),
            additional_instructions: form.querySelector('#hw_additional_instructions')?.value || null
        };

        // Show loading state
        el.disabled = true;
        var textEl = el.querySelector('[data-save-text]');
        var loadEl = el.querySelector('[data-save-loading]');
        if (textEl) textEl.style.display = 'none';
        if (loadEl) loadEl.style.display = 'inline-flex';

        comp.call('saveFormData', data);
    }
    </script>
</div>
