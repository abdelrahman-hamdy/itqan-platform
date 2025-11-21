<div>
    @if($showModal)
    <!-- Modal Overlay -->
    <div class="fixed inset-0 z-50 overflow-y-auto" wire:key="certificate-modal-{{ $subscriptionId }}">
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
                            <span>إصدار شهادة</span>
                        </h3>
                        <button wire:click="closeModal" class="text-white hover:text-gray-200 transition">
                            <i class="ri-close-line text-2xl"></i>
                        </button>
                    </div>
                </div>

                <!-- Modal Body -->
                <div class="px-6 py-6">
                    @if($subscription)
                        <!-- Student Info Card -->
                        <div class="bg-blue-50 rounded-xl p-4 mb-6 border-2 border-blue-100">
                            <div class="flex items-center gap-4">
                                <x-avatar
                                    :user="$subscription->student"
                                    size="lg"
                                />
                                <div class="flex-1">
                                    <p class="text-sm text-blue-600 mb-1">الطالب</p>
                                    <p class="text-lg font-bold text-gray-900">{{ $subscription->student->name }}</p>
                                    <p class="text-sm text-gray-600">{{ $subscription->academy->name }}</p>
                                </div>
                            </div>
                        </div>

                        @if(!$previewMode)
                            <!-- Template Style Selection -->
                            <div class="mb-6">
                                <label class="block text-sm font-bold text-gray-900 mb-3">
                                    <i class="ri-palette-line ml-1 text-amber-500"></i>
                                    اختر تصميم الشهادة
                                </label>
                                <div class="grid grid-cols-3 gap-4">
                                    @foreach($templateStyles as $style => $details)
                                        <label class="relative cursor-pointer">
                                            <input type="radio"
                                                   name="templateStyle"
                                                   value="{{ $style }}"
                                                   wire:model="templateStyle"
                                                   class="peer sr-only">
                                            <div class="border-2 rounded-xl p-4 transition-all peer-checked:border-{{ $details['color'] }}-500 peer-checked:bg-{{ $details['color'] }}-50 hover:border-{{ $details['color'] }}-300">
                                                <div class="flex flex-col items-center text-center">
                                                    <i class="{{ $details['icon'] }} text-4xl mb-2 text-{{ $details['color'] }}-500"></i>
                                                    <p class="font-bold text-gray-900 mb-1">{{ $details['label'] }}</p>
                                                    <p class="text-xs text-gray-600">{{ $details['description'] }}</p>
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
                                    اكتب وصفاً للإنجازات التي حققها الطالب ليتم عرضها في الشهادة
                                </p>
                                <textarea
                                    wire:model="achievementText"
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
                                <p class="text-sm font-semibold text-gray-900 mb-2">
                                    <i class="ri-lightbulb-line ml-1 text-yellow-500"></i>
                                    أمثلة لنصوص الشهادات
                                </p>
                                <div class="space-y-2 text-sm text-gray-700">
                                    @if($subscriptionType === 'quran')
                                        <p class="cursor-pointer hover:text-amber-600" wire:click="$set('achievementText', 'لإتمامه حفظ القرآن الكريم كاملاً بإتقان، وتميزه في أحكام التلاوة والتجويد')">
                                            • لإتمامه حفظ القرآن الكريم كاملاً بإتقان...
                                        </p>
                                        <p class="cursor-pointer hover:text-amber-600" wire:click="$set('achievementText', 'لإنجازه حفظ جزء عم ومراجعته بإتقان، مع التزامه وحسن خلقه')">
                                            • لإنجازه حفظ جزء عم ومراجعته بإتقان...
                                        </p>
                                    @else
                                        <p class="cursor-pointer hover:text-amber-600" wire:click="$set('achievementText', 'لتفوقه في دراسة مادة الرياضيات وإتمامه جميع الدروس بامتياز')">
                                            • لتفوقه في دراسة مادة الرياضيات...
                                        </p>
                                        <p class="cursor-pointer hover:text-amber-600" wire:click="$set('achievementText', 'لإنجازه برنامج اللغة الإنجليزية بتميز وحصوله على أعلى الدرجات')">
                                            • لإنجازه برنامج اللغة الإنجليزية بتميز...
                                        </p>
                                    @endif
                                </div>
                            </div>
                        @else
                            <!-- Preview Mode -->
                            <div class="bg-gray-50 rounded-xl p-6 mb-6">
                                <div class="flex items-center justify-between mb-4">
                                    <h4 class="text-lg font-bold text-gray-900">
                                        <i class="ri-eye-line ml-2 text-blue-500"></i>
                                        معاينة الشهادة
                                    </h4>
                                    <span class="px-3 py-1 bg-{{ $templateStyles[$templateStyle]['color'] }}-100 text-{{ $templateStyles[$templateStyle]['color'] }}-700 rounded-full text-sm font-medium">
                                        {{ $templateStyles[$templateStyle]['label'] }}
                                    </span>
                                </div>

                                <!-- Mock Certificate Preview -->
                                <div class="bg-white rounded-lg p-8 shadow-lg border-4 border-{{ $templateStyles[$templateStyle]['color'] }}-200">
                                    <div class="text-center mb-6">
                                        <h3 class="text-3xl font-bold text-{{ $templateStyles[$templateStyle]['color'] }}-600 mb-2">شهادة تقدير</h3>
                                        <div class="w-32 h-1 bg-{{ $templateStyles[$templateStyle]['color'] }}-400 mx-auto mb-4"></div>
                                    </div>

                                    <div class="mb-6">
                                        <p class="text-center text-gray-700 mb-2">تُمنح هذه الشهادة إلى</p>
                                        <p class="text-center text-2xl font-bold text-gray-900 mb-4 border-b-2 border-{{ $templateStyles[$templateStyle]['color'] }}-300 pb-2">
                                            {{ $subscription->student->name }}
                                        </p>
                                    </div>

                                    <div class="mb-6">
                                        <p class="text-center text-gray-700 leading-relaxed whitespace-pre-line">{{ $achievementText }}</p>
                                    </div>

                                    <div class="flex justify-between items-end mt-8">
                                        <div class="text-center">
                                            <div class="border-t-2 border-gray-300 w-32 mb-2"></div>
                                            <p class="text-sm text-gray-600">{{ $subscription->academy->name }}</p>
                                        </div>
                                        <div class="text-center">
                                            <div class="border-t-2 border-gray-300 w-32 mb-2"></div>
                                            <p class="text-sm text-gray-600">{{ $subscription->teacher->name }}</p>
                                        </div>
                                    </div>
                                </div>

                                <p class="text-xs text-gray-500 text-center mt-4">
                                    <i class="ri-information-line ml-1"></i>
                                    هذه معاينة تقريبية، الشهادة النهائية ستكون بجودة أعلى مع الشعارات والختم الرسمي
                                </p>
                            </div>
                        @endif
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
                                إصدار الشهادة
                            </span>
                            <span wire:loading wire:target="issueCertificate">
                                <i class="ri-loader-4-line ml-2 animate-spin"></i>
                                جاري الإصدار...
                            </span>
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
