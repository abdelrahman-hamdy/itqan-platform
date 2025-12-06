<div x-data="{
        showSuccessToast: false,
        showErrorToast: false,
        toastMessage: '',
        handleSuccess(event) {
            this.toastMessage = event.detail.message || 'تم إصدار الشهادة بنجاح!';
            this.showSuccessToast = true;
            // Hide toast and reload after delay
            setTimeout(() => {
                this.showSuccessToast = false;
                setTimeout(() => window.location.reload(), 500);
            }, 1500);
        },
        handleError(event) {
            this.toastMessage = event.detail.message || 'حدث خطأ أثناء إصدار الشهادة';
            this.showErrorToast = true;
            // Auto-hide error toast after 5 seconds
            setTimeout(() => {
                this.showErrorToast = false;
            }, 5000);
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
            <div class="fixed inset-0 transition-opacity bg-gray-900 bg-opacity-75" wire:click="closeModal"></div>

            <!-- Modal Container -->
            <div class="inline-block w-full max-w-4xl overflow-hidden text-right align-middle transition-all transform bg-white rounded-2xl shadow-2xl sm:my-8">
                <!-- Modal Header -->
                <div class="bg-gradient-to-r from-amber-500 to-yellow-500 px-6 py-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-xl font-bold text-white flex items-center gap-2">
                            <i class="ri-award-line text-2xl"></i>
                            <span>{{ $isGroup ? 'إصدار شهادات للطلاب' : 'إصدار شهادة' }}</span>
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
                                <i class="ri-group-line ml-1 text-blue-500"></i>
                                اختر الطلاب لإصدار الشهادات لهم
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
                                                {{ count($selectedStudents) === count($students) ? 'تم تحديد الكل' : 'تحديد الكل' }}
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
                                            <div class="flex-1">
                                                <p class="font-medium text-gray-900">{{ $student['name'] }}</p>
                                                <p class="text-sm text-gray-500">{{ $student['email'] }}</p>
                                            </div>
                                            @if(isset($student['certificate_count']) && $student['certificate_count'] > 0)
                                                <span class="px-2 py-1 bg-amber-100 text-amber-700 rounded-full text-xs font-semibold flex items-center gap-1">
                                                    <i class="ri-award-fill text-amber-500"></i>
                                                    {{ $student['certificate_count'] }} شهادة
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
                                            <i class="ri-checkbox-circle-fill ml-1 text-green-500"></i>
                                            تم تحديد {{ count($selectedStudents) }} من {{ count($students) }} طالب
                                        </p>
                                        @if(count($selectedStudents) < count($students))
                                            <button type="button" wire:click="selectAllStudents" class="text-xs text-green-600 hover:text-green-800 underline">
                                                تحديد الباقي
                                            </button>
                                        @endif
                                    </div>
                                @else
                                    <p class="mt-2 text-sm text-gray-500">
                                        <i class="ri-information-line ml-1"></i>
                                        اختر الطلاب الذين تريد إصدار شهادات لهم
                                    </p>
                                @endif
                            @else
                                <div class="bg-gray-50 border border-gray-200 rounded-xl p-6 text-center">
                                    <i class="ri-user-line text-4xl text-gray-400 mb-3"></i>
                                    <p class="text-gray-600 font-medium">لا يوجد طلاب في هذه الحلقة</p>
                                </div>
                            @endif
                        </div>
                    @elseif($subscription)
                        <!-- Individual Mode: Student Info Card -->
                        <div class="bg-blue-50 rounded-xl p-4 mb-6 border-2 border-blue-100">
                            <div class="flex items-center gap-4">
                                <div class="w-16 h-16 bg-blue-200 rounded-full flex items-center justify-center">
                                    <i class="ri-user-line text-2xl text-blue-600"></i>
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm text-blue-600 mb-1">الطالب</p>
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
                                <i class="ri-palette-line ml-1 text-amber-500"></i>
                                اختر تصميم الشهادة
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
                                <i class="ri-file-text-line ml-1 text-amber-500"></i>
                                نص الإنجاز والتقدير
                            </label>
                            <p class="text-xs text-gray-600 mb-3">
                                <i class="ri-information-line ml-1"></i>
                                @if($isGroup)
                                    اكتب نص الإنجاز الذي سيظهر في جميع الشهادات المُصدرة
                                @else
                                    اكتب وصفاً للإنجازات التي حققها الطالب ليتم عرضها في الشهادة
                                @endif
                            </p>
                            <textarea
                                wire:model.live="achievementText"
                                id="achievementTextarea"
                                rows="6"
                                class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-amber-500 focus:ring-0 resize-none"
                                placeholder="مثال: لإتمامه حفظ جزء عم بإتقان، وتميزه في أحكام التلاوة والتجويد، مع حسن السلوك والالتزام..."></textarea>

                            <div class="flex items-center justify-between mt-2">
                                @error('achievementText')
                                    <p class="text-sm text-red-600">{{ $message }}</p>
                                @else
                                    <p class="text-xs text-gray-500">
                                        الحد الأدنى: 10 أحرف، الحد الأقصى: 1000 حرف
                                    </p>
                                @enderror
                                <p class="text-xs text-gray-600">{{ strlen($achievementText) }} / 1000</p>
                            </div>
                        </div>

                        <!-- Helper Examples -->
                        <div class="bg-gray-50 rounded-xl p-4 mb-6">
                            <p class="text-sm font-semibold text-gray-900 mb-3">
                                <i class="ri-lightbulb-line ml-1 text-yellow-500"></i>
                                أمثلة لنصوص الشهادات
                                <span class="text-xs font-normal text-gray-500 mr-2">(اضغط للنسخ)</span>
                            </p>
                            <div class="space-y-2">
                                @if($subscriptionType === 'quran' || $subscriptionType === 'group_quran')
                                    <button type="button"
                                            wire:click="setExampleText('لإتمامه حفظ القرآن الكريم كاملاً بإتقان، وتميزه في أحكام التلاوة والتجويد')"
                                            class="w-full text-right text-sm text-gray-700 hover:text-amber-600 hover:bg-amber-50 p-2 rounded-lg transition-all flex items-start gap-2">
                                        <i class="ri-file-copy-line text-gray-400 mt-0.5"></i>
                                        <span>لإتمامه حفظ القرآن الكريم كاملاً بإتقان، وتميزه في أحكام التلاوة والتجويد</span>
                                    </button>
                                    <button type="button"
                                            wire:click="setExampleText('لإنجازه حفظ جزء عم ومراجعته بإتقان، مع التزامه وحسن خلقه')"
                                            class="w-full text-right text-sm text-gray-700 hover:text-amber-600 hover:bg-amber-50 p-2 rounded-lg transition-all flex items-start gap-2">
                                        <i class="ri-file-copy-line text-gray-400 mt-0.5"></i>
                                        <span>لإنجازه حفظ جزء عم ومراجعته بإتقان، مع التزامه وحسن خلقه</span>
                                    </button>
                                    <button type="button"
                                            wire:click="setExampleText('لتميزه في تلاوة القرآن الكريم وإتقان أحكام التجويد، مع المواظبة والالتزام بالحضور')"
                                            class="w-full text-right text-sm text-gray-700 hover:text-amber-600 hover:bg-amber-50 p-2 rounded-lg transition-all flex items-start gap-2">
                                        <i class="ri-file-copy-line text-gray-400 mt-0.5"></i>
                                        <span>لتميزه في تلاوة القرآن الكريم وإتقان أحكام التجويد، مع المواظبة والالتزام بالحضور</span>
                                    </button>
                                @else
                                    <button type="button"
                                            wire:click="setExampleText('لتفوقه في دراسة المادة وإتمامه جميع الدروس بامتياز')"
                                            class="w-full text-right text-sm text-gray-700 hover:text-amber-600 hover:bg-amber-50 p-2 rounded-lg transition-all flex items-start gap-2">
                                        <i class="ri-file-copy-line text-gray-400 mt-0.5"></i>
                                        <span>لتفوقه في دراسة المادة وإتمامه جميع الدروس بامتياز</span>
                                    </button>
                                    <button type="button"
                                            wire:click="setExampleText('لإنجازه البرنامج الدراسي بتميز وحصوله على أعلى الدرجات')"
                                            class="w-full text-right text-sm text-gray-700 hover:text-amber-600 hover:bg-amber-50 p-2 rounded-lg transition-all flex items-start gap-2">
                                        <i class="ri-file-copy-line text-gray-400 mt-0.5"></i>
                                        <span>لإنجازه البرنامج الدراسي بتميز وحصوله على أعلى الدرجات</span>
                                    </button>
                                @endif
                            </div>
                        </div>
                    @elseif($previewMode)
                        <!-- Preview Mode - Template Image with Text Preview -->
                        <div class="bg-gray-50 rounded-xl p-4 mb-6">
                            <div class="flex items-center justify-between mb-4">
                                <h4 class="text-lg font-bold text-gray-900">
                                    <i class="ri-eye-line ml-2 text-blue-500"></i>
                                    معاينة الشهادة
                                </h4>
                                <span class="px-3 py-1 bg-amber-100 text-amber-700 rounded-full text-sm font-medium">
                                    {{ $templateStyles[$templateStyle]['label'] ?? 'القالب' }}
                                </span>
                            </div>

                            @if($isGroup)
                                <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-4">
                                    <p class="text-blue-800 text-sm">
                                        <i class="ri-information-line ml-1"></i>
                                        سيتم إصدار {{ count($selectedStudents) }} شهادة بنفس النص والتصميم
                                    </p>
                                </div>
                            @endif

                            <!-- Template Image Preview -->
                            <div class="bg-white rounded-lg shadow-lg border-2 border-amber-200 overflow-hidden">
                                <img src="{{ $templateStyles[$templateStyle]['previewImage'] ?? '' }}"
                                     alt="معاينة القالب"
                                     class="w-full h-auto">
                            </div>

                            <!-- Certificate Data Summary -->
                            <div class="mt-4 bg-white rounded-lg border border-gray-200 p-4">
                                <h5 class="font-bold text-gray-900 mb-3 text-sm">
                                    <i class="ri-file-list-3-line ml-1 text-amber-500"></i>
                                    بيانات الشهادة
                                </h5>
                                <div class="space-y-2 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">اسم الطالب:</span>
                                        <span class="font-medium text-gray-900">
                                            @if($isGroup)
                                                [سيتم تعبئته لكل طالب]
                                            @else
                                                {{ $studentName }}
                                            @endif
                                        </span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">الأكاديمية:</span>
                                        <span class="font-medium text-gray-900">{{ $academyName }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">المعلم:</span>
                                        <span class="font-medium text-gray-900">{{ $teacherName }}</span>
                                    </div>
                                    <div class="border-t pt-2 mt-2">
                                        <span class="text-gray-600 block mb-1">نص الإنجاز:</span>
                                        <p class="font-medium text-gray-900 text-right leading-relaxed">{{ $achievementText }}</p>
                                    </div>
                                </div>
                            </div>

                            <p class="text-xs text-green-600 text-center mt-3">
                                <i class="ri-checkbox-circle-line ml-1"></i>
                                سيتم إنشاء الشهادة بهذه البيانات على القالب المختار
                            </p>
                        </div>
                    @endif
                </div>

                <!-- Modal Footer -->
                <div class="bg-gray-50 px-6 py-4 flex gap-3 justify-end border-t border-gray-200">
                    <button type="button"
                            wire:click="closeModal"
                            class="px-6 py-2.5 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg font-semibold transition-colors">
                        <i class="ri-close-line ml-2"></i>
                        إلغاء
                    </button>

                    @if(($isGroup && count($students) > 0) || (!$isGroup && $subscription))
                        @if(!$previewMode)
                            <button type="button"
                                    wire:click="togglePreview"
                                    class="px-6 py-2.5 bg-blue-500 hover:bg-blue-600 text-white rounded-lg font-semibold transition-colors">
                                <i class="ri-eye-line ml-2"></i>
                                معاينة قبل الإصدار
                            </button>
                        @else
                            <button type="button"
                                    wire:click="togglePreview"
                                    class="px-6 py-2.5 bg-gray-500 hover:bg-gray-600 text-white rounded-lg font-semibold transition-colors">
                                <i class="ri-edit-line ml-2"></i>
                                تعديل
                            </button>

                            <button type="button"
                                    wire:click="issueCertificate"
                                    wire:loading.attr="disabled"
                                    class="px-6 py-2.5 bg-green-500 hover:bg-green-600 text-white rounded-lg font-bold transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                                <span wire:loading.remove wire:target="issueCertificate">
                                    <i class="ri-checkbox-circle-line ml-2"></i>
                                    @if($isGroup)
                                        إصدار {{ count($selectedStudents) }} شهادة
                                    @else
                                        إصدار الشهادة
                                    @endif
                                </span>
                                <span wire:loading wire:target="issueCertificate">
                                    <i class="ri-loader-4-line ml-2 animate-spin"></i>
                                    جاري الإصدار...
                                </span>
                            </button>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Success Toast Notification -->
    <div x-show="showSuccessToast"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform -translate-x-4"
         x-transition:enter-end="opacity-100 transform translate-x-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 transform translate-x-0"
         x-transition:leave-end="opacity-0 transform -translate-x-4"
         class="fixed top-4 right-4 z-[100] max-w-sm"
         style="display: none;">
        <div class="bg-green-500 text-white px-5 py-3 rounded-xl shadow-2xl flex items-center gap-3">
            <i class="ri-checkbox-circle-fill text-2xl"></i>
            <p class="font-bold" x-text="toastMessage"></p>
        </div>
    </div>

    <!-- Error Toast Notification -->
    <div x-show="showErrorToast"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform translate-y-2"
         x-transition:enter-end="opacity-100 transform translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 transform translate-y-0"
         x-transition:leave-end="opacity-0 transform translate-y-2"
         class="fixed top-4 left-1/2 transform -translate-x-1/2 z-[100] w-full max-w-md"
         style="display: none;">
        <div class="bg-red-500 text-white px-6 py-4 rounded-xl shadow-2xl flex items-center gap-3">
            <div class="flex-shrink-0">
                <i class="ri-error-warning-fill text-3xl"></i>
            </div>
            <div class="flex-1">
                <p class="font-bold text-base" x-text="toastMessage"></p>
            </div>
            <button @click="showErrorToast = false" class="flex-shrink-0 hover:bg-red-600 rounded-lg p-1 transition">
                <i class="ri-close-line text-xl"></i>
            </button>
        </div>
    </div>
</div>
