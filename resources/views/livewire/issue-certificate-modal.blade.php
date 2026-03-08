<div x-data="{
        handleSuccess(event) {
            // Use unified toast system (handled by toast container)
            // Reload page after a short delay to show the updated data
            setTimeout(() => window.location.reload(), 2000);
        },
        handleError(event) {
            // Error is handled by unified toast system
            // No additional action needed here
        }
     }"
     x-on:achievement-text-updated.window="document.getElementById('achievementTextarea').value = $event.detail.text"
     x-on:certificate-issued-success.window="handleSuccess($event)"
     x-on:certificate-issued-error.window="handleError($event)">
    @if($showModal)
    <!-- Modal Overlay -->
    <div class="fixed inset-0 z-50 overflow-y-auto" wire:key="certificate-modal-{{ $subscriptionId ?? $circleId }}">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <!-- Background Overlay -->
            <div class="fixed inset-0 transition-opacity z-40" style="background-color: rgba(0, 0, 0, 0.5)" wire:click="closeModal"></div>

            <!-- Modal Container -->
            <div class="inline-block w-full max-w-4xl overflow-hidden text-start align-middle transition-all transform bg-white rounded-2xl shadow-2xl sm:my-8 relative z-50">
                <!-- Modal Header -->
                <div class="bg-gradient-to-r from-amber-500 to-yellow-500 px-6 py-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-xl font-bold text-white flex items-center gap-2">
                            <i class="ri-award-line text-2xl"></i>
                            <span>{{ $isGroup ? __('components.certificate.modal.title_group') : __('components.certificate.modal.title_single') }}</span>
                        </h3>
                        <button wire:click="closeModal" class="text-white hover:text-gray-200 transition">
                            <i class="ri-close-line text-2xl"></i>
                        </button>
                    </div>
                </div>

                <!-- Modal Body -->
                <div class="px-6 py-6 max-h-[70vh] overflow-y-auto">
                    @if($isGroup && ($circle || $course))
                        <!-- Group Mode: Student Selection -->
                        <div class="mb-6">
                            <label class="block text-sm font-bold text-gray-900 mb-3">
                                <i class="ri-group-line ms-1 text-blue-500"></i>
                                {{ __('components.certificate.modal.select_students') }}
                            </label>

                            @if(count($students) > 0)
                                <!-- Select All Checkbox -->
                                <div class="rounded-lg p-3 mb-3 border transition-all {{ count($selectedStudents) === count($students) ? 'bg-green-50 border-green-300' : 'bg-blue-50 border-blue-100' }}">
                                    <label class="flex items-center justify-between cursor-pointer">
                                        <div class="flex items-center gap-3">
                                            <input type="checkbox"
                                                   wire:model.live="selectAll"
                                                   wire:change="toggleSelectAll"
                                                   class="w-5 h-5 text-green-600 border-gray-300 rounded focus:ring-green-500">
                                            <span class="font-medium {{ count($selectedStudents) === count($students) ? 'text-green-900' : 'text-blue-900' }}">
                                                {{ count($selectedStudents) === count($students) ? __('components.certificate.modal.all_selected') : __('components.certificate.modal.select_all') }}
                                            </span>
                                        </div>
                                        <span class="text-sm font-bold px-3 py-1 rounded-full {{ count($selectedStudents) > 0 ? 'bg-green-500 text-white' : 'bg-gray-200 text-gray-600' }}">
                                            {{ count($selectedStudents) }} / {{ count($students) }}
                                        </span>
                                    </label>
                                </div>

                                <!-- Students List -->
                                <div class="border-2 border-gray-200 rounded-xl max-h-60 overflow-y-auto">
                                    @foreach($students as $student)
                                        <label class="flex items-center gap-4 p-4 border-b border-gray-100 last:border-b-0 hover:bg-gray-50 cursor-pointer transition
                                            {{ in_array($student['subscription_id'], $selectedStudents) ? 'bg-green-50' : '' }}">
                                            <input type="checkbox"
                                                   value="{{ $student['subscription_id'] }}"
                                                   wire:model.live="selectedStudents"
                                                   class="w-5 h-5 text-green-500 border-gray-300 rounded focus:ring-green-500">
                                            <x-avatar :user="(object)['name' => $student['name'], 'avatar' => $student['avatar'] ?? null, 'gender' => $student['gender'] ?? 'male']"
                                                       userType="student" size="sm" />
                                            <div class="flex-1">
                                                <p class="font-medium text-gray-900">{{ $student['name'] }}</p>
                                                <p class="text-sm text-gray-500">{{ $student['email'] }}</p>
                                            </div>
                                            @if(isset($student['certificate_count']) && $student['certificate_count'] > 0)
                                                <span class="px-2 py-1 bg-amber-100 text-amber-700 rounded-full text-xs font-semibold flex items-center gap-1">
                                                    <i class="ri-award-fill text-amber-500"></i>
                                                    {{ $student['certificate_count'] }} {{ __('components.certificate.modal.certificate_count') }}
                                                </span>
                                            @endif
                                        </label>
                                    @endforeach
                                </div>

                                @error('selectedStudents')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror

                                <!-- Selected Count Badge -->
                                @if(count($selectedStudents) > 0)
                                    <div class="mt-3 bg-green-50 border border-green-200 rounded-lg p-3 flex items-center justify-between">
                                        <p class="text-sm text-green-800 font-medium">
                                            <i class="ri-checkbox-circle-fill ms-1 text-green-500"></i>
                                            {{ __('components.certificate.modal.students_selected', ['selected' => count($selectedStudents), 'total' => count($students)]) }}
                                        </p>
                                        @if(count($selectedStudents) < count($students))
                                            <button type="button" wire:click="selectAllStudents" class="text-xs text-green-600 hover:text-green-800 underline">
                                                {{ __('components.certificate.modal.select_remaining') }}
                                            </button>
                                        @endif
                                    </div>
                                @else
                                    <p class="mt-2 text-sm text-gray-500">
                                        <i class="ri-information-line ms-1"></i>
                                        {{ __('components.certificate.modal.select_students_hint') }}
                                    </p>
                                @endif
                            @else
                                <div class="bg-gray-50 border border-gray-200 rounded-xl p-6 text-center">
                                    <i class="ri-user-line text-4xl text-gray-400 mb-3"></i>
                                    <p class="text-gray-600 font-medium">{{ __('components.certificate.modal.no_students') }}</p>
                                </div>
                            @endif
                        </div>
                    @elseif($subscription)
                        <!-- Individual Mode: Student Info Card -->
                        <div class="bg-blue-50 rounded-xl p-4 mb-6 border-2 border-blue-100">
                            <div class="flex items-center gap-4">
                                @php
                                    $studentUser = $subscriptionType === 'interactive'
                                        ? ($subscription->student->user ?? $subscription->student)
                                        : $subscription->student;
                                @endphp
                                <x-avatar :user="$studentUser" userType="student" size="md" />
                                <div class="flex-1">
                                    <p class="text-sm text-blue-600 mb-1">{{ __('components.certificate.modal.student_label') }}</p>
                                    <p class="text-lg font-bold text-gray-900">{{ $studentName }}</p>
                                    <p class="text-sm text-gray-600">{{ $academyName }}</p>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if(!$previewMode && (($isGroup && count($students) > 0) || (!$isGroup && $subscription)))
                        <!-- Template Style Selection with Images -->
                        <div class="mb-6">
                            <label class="block text-sm font-bold text-gray-900 mb-3">
                                <i class="ri-palette-line ms-1 text-amber-500"></i>
                                {{ __('components.certificate.modal.select_design') }}
                            </label>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                                @foreach($templateStyles as $style => $details)
                                    <label class="relative cursor-pointer block group">
                                        <input type="radio"
                                               name="templateStyle"
                                               value="{{ $style }}"
                                               wire:model.live="templateStyle"
                                               class="peer sr-only">
                                        <div class="border-2 rounded-xl overflow-hidden transition-all
                                            {{ $templateStyle === $style ? 'border-amber-500 ring-2 ring-amber-200' : 'border-gray-200 hover:border-gray-300' }}
                                        ">
                                            <!-- Template Preview Image -->
                                            <div class="aspect-[297/210] bg-gray-100 relative">
                                                <img src="{{ $details['previewImage'] }}"
                                                     alt="{{ $details['label'] }}"
                                                     class="w-full h-full object-cover">
                                                @if($templateStyle === $style)
                                                    <div class="absolute inset-0 bg-amber-500/20"></div>
                                                    <div class="absolute top-2 left-2">
                                                        <i class="ri-checkbox-circle-fill text-xl text-amber-500 drop-shadow-lg"></i>
                                                    </div>
                                                @endif
                                            </div>
                                            <!-- Template Label -->
                                            <div class="p-2 text-center bg-white">
                                                <p class="font-bold text-gray-900 text-xs">{{ $details['label'] }}</p>
                                            </div>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                            @error('templateStyle')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Achievement Text -->
                        <div class="mb-6">
                            <label class="block text-sm font-bold text-gray-900 mb-2">
                                <i class="ri-file-text-line ms-1 text-amber-500"></i>
                                {{ __('components.certificate.modal.achievement_text') }}
                            </label>
                            <p class="text-xs text-gray-600 mb-3">
                                <i class="ri-information-line ms-1"></i>
                                @if($isGroup)
                                    {{ __('components.certificate.modal.achievement_hint_group') }}
                                @else
                                    {{ __('components.certificate.modal.achievement_hint_single') }}
                                @endif
                            </p>
                            <textarea
                                wire:model.live="achievementText"
                                id="achievementTextarea"
                                rows="6"
                                class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-amber-500 focus:ring-0 resize-none"
                                placeholder="{{ __('components.certificate.modal.achievement_placeholder') }}"></textarea>

                            <div class="flex items-center justify-between mt-2">
                                @error('achievementText')
                                    <p class="text-sm text-red-600">{{ $message }}</p>
                                @else
                                    <p class="text-xs text-gray-500">
                                        {{ __('components.certificate.modal.char_limits') }}
                                    </p>
                                @enderror
                                <p class="text-xs text-gray-600">{{ strlen($achievementText) }} / 1000</p>
                            </div>
                        </div>

                        <!-- Helper Examples -->
                        <div class="bg-gray-50 rounded-xl p-4 mb-6">
                            <p class="text-sm font-semibold text-gray-900 mb-3">
                                <i class="ri-lightbulb-line ms-1 text-yellow-500"></i>
                                {{ __('components.certificate.modal.example_texts') }}
                                <span class="text-xs font-normal text-gray-500 me-2">{{ __('components.certificate.modal.click_to_copy') }}</span>
                            </p>
                            <div class="space-y-2">
                                @if($subscriptionType === 'quran' || $subscriptionType === 'group_quran')
                                    <button type="button"
                                            wire:click="setExampleText('لإتمامه حفظ القرآن الكريم كاملاً بإتقان، وتميزه في أحكام التلاوة والتجويد')"
                                            class="w-full text-start text-sm text-gray-700 hover:text-amber-600 hover:bg-amber-50 p-2 rounded-lg transition-all flex items-start gap-2">
                                        <i class="ri-file-copy-line text-gray-400 mt-0.5"></i>
                                        <span>لإتمامه حفظ القرآن الكريم كاملاً بإتقان، وتميزه في أحكام التلاوة والتجويد</span>
                                    </button>
                                    <button type="button"
                                            wire:click="setExampleText('لإنجازه حفظ جزء عم ومراجعته بإتقان، مع التزامه وحسن خلقه')"
                                            class="w-full text-start text-sm text-gray-700 hover:text-amber-600 hover:bg-amber-50 p-2 rounded-lg transition-all flex items-start gap-2">
                                        <i class="ri-file-copy-line text-gray-400 mt-0.5"></i>
                                        <span>لإنجازه حفظ جزء عم ومراجعته بإتقان، مع التزامه وحسن خلقه</span>
                                    </button>
                                    <button type="button"
                                            wire:click="setExampleText('لتميزه في تلاوة القرآن الكريم وإتقان أحكام التجويد، مع المواظبة والالتزام بالحضور')"
                                            class="w-full text-start text-sm text-gray-700 hover:text-amber-600 hover:bg-amber-50 p-2 rounded-lg transition-all flex items-start gap-2">
                                        <i class="ri-file-copy-line text-gray-400 mt-0.5"></i>
                                        <span>لتميزه في تلاوة القرآن الكريم وإتقان أحكام التجويد، مع المواظبة والالتزام بالحضور</span>
                                    </button>
                                @else
                                    <button type="button"
                                            wire:click="setExampleText('لتفوقه في دراسة المادة وإتمامه جميع الدروس بامتياز')"
                                            class="w-full text-start text-sm text-gray-700 hover:text-amber-600 hover:bg-amber-50 p-2 rounded-lg transition-all flex items-start gap-2">
                                        <i class="ri-file-copy-line text-gray-400 mt-0.5"></i>
                                        <span>لتفوقه في دراسة المادة وإتمامه جميع الدروس بامتياز</span>
                                    </button>
                                    <button type="button"
                                            wire:click="setExampleText('لإنجازه البرنامج الدراسي بتميز وحصوله على أعلى الدرجات')"
                                            class="w-full text-start text-sm text-gray-700 hover:text-amber-600 hover:bg-amber-50 p-2 rounded-lg transition-all flex items-start gap-2">
                                        <i class="ri-file-copy-line text-gray-400 mt-0.5"></i>
                                        <span>لإنجازه البرنامج الدراسي بتميز وحصوله على أعلى الدرجات</span>
                                    </button>
                                @endif
                            </div>
                        </div>
                    @elseif($previewMode)
                        <!-- Preview Mode - Template Image with CSS Overlay -->
                        <div class="bg-gray-50 rounded-xl p-4 mb-6">
                            <div class="flex items-center justify-between mb-4">
                                <h4 class="text-lg font-bold text-gray-900">
                                    <i class="ri-eye-line ms-2 text-blue-500"></i>
                                    {{ __('components.certificate.modal.preview_title') }}
                                </h4>
                                <span class="px-3 py-1 bg-amber-100 text-amber-700 rounded-full text-sm font-medium">
                                    {{ $templateStyles[$templateStyle]['label'] ?? __('components.certificate.modal.template') }}
                                </span>
                            </div>

                            @if($isGroup)
                                <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-4">
                                    <p class="text-blue-800 text-sm">
                                        <i class="ri-information-line ms-1"></i>
                                        {{ __('components.certificate.modal.will_issue_count', ['count' => count($selectedStudents)]) }}
                                    </p>
                                </div>
                            @endif

                            <!-- Certificate Preview with Text Overlay -->
                            <div class="bg-white rounded-lg shadow-lg border-2 border-amber-200 overflow-hidden"
                                 dir="ltr"
                                 style="position: relative; aspect-ratio: 297 / 210;">
                                {{-- Template background image --}}
                                <img src="{{ $templateStyles[$templateStyle]['previewImage'] ?? '' }}"
                                     alt="{{ __('components.certificate.modal.preview_alt') }}"
                                     class="w-full h-full object-cover"
                                     style="position: absolute; inset: 0;">

                                {{-- Title: شهادة تقدير --}}
                                <div style="position: absolute; top: 16.67%; width: 100%; text-align: center; direction: rtl;">
                                    <span style="font-size: clamp(0.9rem, 2.5vw, 1.6rem); font-weight: 800; color: {{ $primaryColor }}; font-family: 'Cairo', 'Tajawal', sans-serif;">
                                        شهادة تقدير
                                    </span>
                                </div>

                                {{-- Subtitle --}}
                                <div style="position: absolute; top: 26.19%; width: 100%; text-align: center; direction: rtl;">
                                    <span style="font-size: clamp(0.5rem, 1.4vw, 0.85rem); color: #374151; font-family: 'Tajawal', sans-serif;">
                                        تشهد {{ $academyName }} بأن
                                    </span>
                                </div>

                                {{-- Student Name --}}
                                <div style="position: absolute; top: 33.33%; width: 100%; text-align: center; direction: rtl;">
                                    <span style="font-size: clamp(0.75rem, 2vw, 1.3rem); font-weight: 700; color: {{ $primaryColor }}; font-family: 'Cairo', 'Tajawal', sans-serif;">
                                        @if($isGroup)
                                            {{ __('components.certificate.modal.student_name_placeholder') }}
                                        @else
                                            {{ $studentName }}
                                        @endif
                                    </span>
                                </div>

                                {{-- Certificate / Achievement Text --}}
                                <div style="position: absolute; top: 45.24%; left: 13.47%; width: 73.06%; text-align: center; direction: rtl;">
                                    <p style="font-size: clamp(0.4rem, 1.1vw, 0.7rem); color: #4b5563; line-height: 1.7; font-family: 'Tajawal', sans-serif; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 4; -webkit-box-orient: vertical;">
                                        {{ $achievementText }}
                                    </p>
                                </div>

                                {{-- Bottom row: Teacher | Date | Academy (RTL order in LTR container) --}}
                                {{-- Teacher (right side in certificate = left in LTR) --}}
                                <div style="position: absolute; top: 74.76%; left: 10.77%; width: 23.57%; text-align: center; direction: rtl;">
                                    <span style="font-size: clamp(0.35rem, 0.8vw, 0.55rem); color: #6b7280; font-family: 'Tajawal', sans-serif;">
                                        المعلم
                                    </span>
                                </div>
                                <div style="position: absolute; top: 78.57%; left: 10.77%; width: 23.57%; text-align: center; direction: rtl;">
                                    <span style="font-size: clamp(0.35rem, 0.9vw, 0.6rem); font-weight: 600; color: #1f2937; font-family: 'Tajawal', sans-serif;">
                                        {{ $teacherName }}
                                    </span>
                                </div>

                                {{-- Date (center) --}}
                                <div style="position: absolute; top: 74.76%; left: 36.70%; width: 26.94%; text-align: center; direction: rtl;">
                                    <span style="font-size: clamp(0.35rem, 0.8vw, 0.55rem); color: #6b7280; font-family: 'Tajawal', sans-serif;">
                                        التاريخ
                                    </span>
                                </div>
                                <div style="position: absolute; top: 78.57%; left: 36.70%; width: 26.94%; text-align: center; direction: rtl;">
                                    <span style="font-size: clamp(0.35rem, 0.9vw, 0.6rem); font-weight: 600; color: #1f2937; font-family: 'Tajawal', sans-serif;">
                                        {{ $previewDate }}
                                    </span>
                                </div>

                                {{-- Academy (left side in certificate = right in LTR) --}}
                                <div style="position: absolute; top: 74.76%; left: 65.66%; width: 23.57%; text-align: center; direction: rtl;">
                                    <span style="font-size: clamp(0.35rem, 0.8vw, 0.55rem); color: #6b7280; font-family: 'Tajawal', sans-serif;">
                                        الأكاديمية
                                    </span>
                                </div>
                                <div style="position: absolute; top: 78.57%; left: 65.66%; width: 23.57%; text-align: center; direction: rtl;">
                                    <span style="font-size: clamp(0.35rem, 0.9vw, 0.6rem); font-weight: 600; color: #1f2937; font-family: 'Tajawal', sans-serif;">
                                        {{ $academyName }}
                                    </span>
                                </div>

                                {{-- Certificate Number --}}
                                <div style="position: absolute; top: 90.48%; width: 100%; text-align: center; direction: rtl;">
                                    <span style="font-size: clamp(0.3rem, 0.7vw, 0.5rem); color: #9ca3af; font-family: 'Tajawal', sans-serif;">
                                        CERT-XXXX-XXXXXX
                                    </span>
                                </div>
                            </div>

                            <p class="text-xs text-green-600 text-center mt-3">
                                <i class="ri-checkbox-circle-line ms-1"></i>
                                {{ __('components.certificate.modal.will_create_message') }}
                            </p>
                        </div>
                    @endif
                </div>

                <!-- Modal Footer -->
                <div class="bg-gray-50 px-6 py-4 flex gap-3 justify-end border-t border-gray-200">
                    <button type="button"
                            wire:click="closeModal"
                            class="px-6 py-2.5 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg font-semibold transition-colors">
                        <i class="ri-close-line ms-2"></i>
                        {{ __('components.certificate.modal.cancel') }}
                    </button>

                    @if(($isGroup && count($students) > 0) || (!$isGroup && $subscription))
                        @if(!$previewMode)
                            <button type="button"
                                    wire:click="togglePreview"
                                    class="px-6 py-2.5 bg-blue-500 hover:bg-blue-600 text-white rounded-lg font-semibold transition-colors">
                                <i class="ri-eye-line ms-2"></i>
                                {{ __('components.certificate.modal.preview_before') }}
                            </button>
                        @else
                            <button type="button"
                                    wire:click="togglePreview"
                                    class="px-6 py-2.5 bg-gray-500 hover:bg-gray-600 text-white rounded-lg font-semibold transition-colors">
                                <i class="ri-edit-line ms-2"></i>
                                {{ __('components.certificate.modal.edit') }}
                            </button>

                            <button type="button"
                                    wire:click="issueCertificate"
                                    wire:loading.attr="disabled"
                                    class="px-6 py-2.5 bg-green-500 hover:bg-green-600 text-white rounded-lg font-bold transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                                <span wire:loading.remove wire:target="issueCertificate">
                                    <i class="ri-checkbox-circle-line ms-2"></i>
                                    @if($isGroup)
                                        {{ __('components.certificate.modal.issue_count', ['count' => count($selectedStudents)]) }}
                                    @else
                                        {{ __('components.certificate.modal.issue_single') }}
                                    @endif
                                </span>
                                <span wire:loading wire:target="issueCertificate">
                                    <i class="ri-loader-4-line ms-2 animate-spin"></i>
                                    {{ __('components.certificate.modal.issuing') }}
                                </span>
                            </button>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif
    {{-- Toast notifications are now handled by the unified toast container --}}
</div>
