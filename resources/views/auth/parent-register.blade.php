<x-auth.layout title="{{ __('auth.register.parent.title') }}" subtitle="{{ __('auth.register.parent.subtitle') }}" maxWidth="lg" :academy="$academy">
@php
    // PRIMARY COLOR: Use brand_color from academy settings - HEX VALUES for inline styles
    $accentColorHex = '#0ea5e9'; // sky-500
    $accentColorDarkHex = '#0c4a6e'; // sky-900
    $accentColorMediumHex = '#0369a1'; // sky-700
    $accentColorLightHex = '#f0f9ff'; // sky-50

    if ($academy && $academy->brand_color) {
        try {
            $accentColorHex = $academy->brand_color->getHexValue(500);
            $accentColorDarkHex = $academy->brand_color->getHexValue(900);
            $accentColorMediumHex = $academy->brand_color->getHexValue(700);
            $accentColorLightHex = $academy->brand_color->getHexValue(50);
        } catch (\Exception $e) {
            // Fallback to defaults
        }
    }

    // GRADIENT COLORS: Use gradient_palette for gradient buttons only
    $gradientFromFull = 'cyan-500';
    $gradientToFull = 'blue-600';

    if ($academy && $academy->gradient_palette) {
        $colors = $academy->gradient_palette->getColors();
        $gradientFromFull = $colors['from'];
        $gradientToFull = $colors['to'];
    }
@endphp
    <form id="parentRegisterForm"
          method="POST"
          action="{{ route('parent.register.post', ['subdomain' => $academy->subdomain ?? request()->route('subdomain')]) }}"
          x-data="parentRegistrationForm()"
          @submit="handleSubmit">
        @csrf

        <!-- Validation Errors Display -->
        @if ($errors->any())
            <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                <div class="flex items-start gap-3">
                    <i class="ri-error-warning-line text-red-500 text-xl flex-shrink-0 mt-0.5"></i>
                    <div class="flex-1">
                        <h4 class="text-sm font-semibold text-red-800 mb-2">{{ __('auth.register.parent.errors.title') }}</h4>
                        <ul class="space-y-1">
                            @foreach ($errors->all() as $error)
                                <li class="text-sm text-red-700 flex items-start gap-2">
                                    <i class="ri-close-circle-line text-red-500 mt-0.5 flex-shrink-0"></i>
                                    <span>{{ $error }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        <!-- Step 1: Phone & Student Verification -->
        <div class="mb-8">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 bg-primary/10 rounded-lg flex items-center justify-center">
                    <i class="ri-shield-check-line text-primary text-xl"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-900">{{ __('auth.register.parent.step1.title') }}</h3>
            </div>

            <!-- Info Message -->
            <div class="mb-6 p-4 border-r-4 rounded-lg" style="background-color: {{ $accentColorLightHex }}; border-color: {{ $accentColorHex }};">
                <div class="flex items-start gap-3">
                    <div class="flex-shrink-0 w-8 h-8 rounded-lg flex items-center justify-center" style="background-color: {{ $accentColorHex }};">
                        <i class="ri-information-line text-white text-lg"></i>
                    </div>
                    <div class="flex-1">
                        <h4 class="text-sm font-semibold mb-1" style="color: {{ $accentColorDarkHex }};">{{ __('auth.register.parent.step1.info_title') }}</h4>
                        <p class="text-sm" style="color: {{ $accentColorMediumHex }};">
                            {{ __('auth.register.parent.step1.info_text') }}
                        </p>
                    </div>
                </div>
            </div>

            <x-forms.phone-input
                name="parent_phone"
                :label="__('auth.register.parent.step1.phone_label')"
                :required="true"
                countryCodeField="parent_phone_country_code"
                countryField="parent_phone_country"
                initialCountry="{{ old('parent_phone_country') ? strtolower(old('parent_phone_country')) : 'sa' }}"
                :placeholder="__('auth.register.parent.step1.phone_placeholder')"
                :value="old('parent_phone')"
                :error="$errors->first('parent_phone')"
            />

            <!-- Student Codes Fields -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    {{ __('auth.register.parent.step1.student_codes_label') }}
                    <span class="text-red-500">*</span>
                </label>

                <div class="space-y-3">
                    <template x-for="(code, index) in studentCodes" :key="index">
                        <div class="flex items-center gap-2">
                            <div class="flex-1 relative">
                                <input
                                    type="text"
                                    x-model="studentCodes[index]"
                                    :placeholder="'{{ __('auth.register.parent.step1.student_code_placeholder') }} ' + (index + 1)"
                                    @input="studentCodes[index] = $event.target.value.toUpperCase()"
                                    class="input-field appearance-none block w-full px-4 py-3 pr-11 border border-gray-300 rounded-button text-gray-900 placeholder-gray-400 focus:outline-none transition-smooth"
                                    maxlength="20"
                                />
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                    <i class="ri-key-line text-gray-400 text-lg"></i>
                                </div>
                            </div>
                            <button
                                type="button"
                                x-show="studentCodes.length > 1"
                                @click="removeStudentField(index)"
                                class="flex-shrink-0 w-10 h-10 flex items-center justify-center rounded-button border border-red-300 text-red-600 hover:bg-red-50 transition-smooth"
                            >
                                <i class="ri-delete-bin-line text-lg"></i>
                            </button>
                        </div>
                    </template>
                </div>

                <button
                    type="button"
                    x-show="studentCodes.length < 10"
                    @click="addStudentField"
                    class="mt-3 w-full flex items-center justify-center gap-2 px-4 py-2.5 border-2 border-dashed border-gray-300 rounded-button text-gray-600 hover:border-primary hover:text-primary hover:bg-primary/5 transition-smooth"
                >
                    <i class="ri-add-line text-lg"></i>
                    <span class="text-sm font-medium">{{ __('auth.register.parent.step1.add_student') }}</span>
                </button>

                <p class="mt-2 text-xs text-gray-500 flex items-center">
                    <i class="ri-information-line ms-1"></i>
                    {{ __('auth.register.parent.step1.max_students_info') }}
                </p>
            </div>

            <!-- Hidden field for form submission -->
            <input type="hidden" name="student_codes" id="student_codes" x-model="studentCodes.filter(c => c.trim()).join(',')" />

            <!-- Verify Button -->
            <button
                type="button"
                @click="verifyStudents"
                :disabled="verifying"
                :class="{ 'opacity-75 cursor-wait': verifying }"
                class="w-full flex items-center justify-center gap-2 px-6 py-3.5 bg-gradient-to-r from-{{ $gradientFromFull }} to-{{ $gradientToFull }} text-white font-medium rounded-button hover:shadow-lg hover:-translate-y-0.5 transition-smooth disabled:cursor-not-allowed"
            >
                <div x-show="verifying" class="w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                <i x-show="!verifying" class="ri-check-line text-lg"></i>
                <span x-text="verifying ? '{{ __('auth.register.parent.step1.verifying') }}' : '{{ __('auth.register.parent.step1.verify_button') }}'"></span>
            </button>

            <!-- Verification Results -->
            <div x-show="verifiedStudents.length > 0 || unverifiedCodes.length > 0 || studentsWithParent.length > 0" class="mt-5 space-y-4">
                <!-- Verified Students -->
                <div x-show="verifiedStudents.length > 0" class="overflow-hidden rounded-xl border-2 border-green-200 bg-gradient-to-br from-green-50 to-emerald-50">
                    <div class="bg-green-600 px-4 py-3 flex items-center gap-2">
                        <i class="ri-checkbox-circle-fill text-white text-xl"></i>
                        <h4 class="text-sm font-semibold text-white">{{ __('auth.register.parent.verification.verified_title') }}</h4>
                    </div>
                    <ul class="p-4 space-y-2.5">
                        <template x-for="student in verifiedStudents" :key="student.code">
                            <li class="flex items-center justify-between bg-white/80 backdrop-blur-sm p-3.5 rounded-lg border border-green-200 shadow-sm animate-slideIn">
                                <div class="flex items-center gap-3">
                                    <div class="w-11 h-11 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl flex items-center justify-center shadow-sm">
                                        <i class="ri-user-smile-line text-white text-lg"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-gray-900" x-text="student.name"></p>
                                        <div class="flex items-center gap-2 text-xs text-gray-600 mt-0.5">
                                            <span class="px-2 py-0.5 bg-green-100 text-green-700 rounded font-medium" x-text="student.code"></span>
                                            <span>•</span>
                                            <span x-text="student.grade"></span>
                                        </div>
                                    </div>
                                </div>
                                <i class="ri-checkbox-circle-fill text-2xl" style="color: {{ $accentColorHex }};"></i>
                            </li>
                        </template>
                    </ul>
                </div>

                <!-- Students Already Have Parent -->
                <div x-show="studentsWithParent.length > 0" class="overflow-hidden rounded-xl border-2 border-red-200 bg-gradient-to-br from-red-50 to-rose-50">
                    <div class="bg-red-600 px-4 py-3 flex items-center gap-2">
                        <i class="ri-user-forbid-line text-white text-xl"></i>
                        <h4 class="text-sm font-semibold text-white">{{ __('auth.register.parent.verification.already_has_parent_title') }}</h4>
                    </div>
                    <div class="p-4">
                        <ul class="space-y-2">
                            <template x-for="student in studentsWithParent" :key="student.code">
                                <li class="flex items-center gap-2 text-sm bg-white/80 backdrop-blur-sm px-3 py-2.5 rounded-lg border border-red-200 animate-slideIn">
                                    <i class="ri-error-warning-line text-red-600 text-lg"></i>
                                    <div class="flex-1">
                                        <p class="font-medium text-gray-900" x-text="student.name"></p>
                                        <div class="flex items-center gap-2 text-xs text-gray-600 mt-0.5">
                                            <span class="px-2 py-0.5 bg-red-100 text-red-700 rounded font-medium" x-text="student.code"></span>
                                            <span>•</span>
                                            <span x-text="student.grade"></span>
                                        </div>
                                    </div>
                                    <span class="text-xs text-red-600 font-medium">{{ __('auth.register.parent.verification.account_exists') }}</span>
                                </li>
                            </template>
                        </ul>
                        <div class="mt-3 p-3 bg-red-100 rounded-lg">
                            <p class="text-xs text-red-700 flex items-start gap-2">
                                <i class="ri-information-line text-red-600 text-sm mt-0.5 flex-shrink-0"></i>
                                <span>{{ __('auth.register.parent.verification.already_has_parent_info') }}</span>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Unverified Codes -->
                <div x-show="unverifiedCodes.length > 0" class="overflow-hidden rounded-xl border-2 border-amber-200 bg-gradient-to-br from-amber-50 to-orange-50">
                    <div class="bg-amber-500 px-4 py-3 flex items-center gap-2">
                        <i class="ri-error-warning-fill text-white text-xl"></i>
                        <h4 class="text-sm font-semibold text-white">{{ __('auth.register.parent.verification.unverified_title') }}</h4>
                    </div>
                    <div class="p-4">
                        <ul class="space-y-2">
                            <template x-for="code in unverifiedCodes" :key="code">
                                <li class="flex items-center gap-2 text-sm bg-white/80 backdrop-blur-sm px-3 py-2 rounded-lg border border-amber-200">
                                    <i class="ri-close-circle-line text-amber-600 text-lg"></i>
                                    <span class="font-medium text-gray-700" x-text="code"></span>
                                    <span class="text-xs text-gray-500">- {{ __('auth.register.parent.verification.unverified_info') }}</span>
                                </li>
                            </template>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Step 2: Personal Information (Shows after verification) -->
        <div x-show="verified" x-cloak id="personalInfoSection" class="mb-8">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 bg-primary/10 rounded-lg flex items-center justify-center">
                    <i class="ri-user-line text-primary text-xl"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-900">{{ __('auth.register.parent.step2.title') }}</h3>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-auth.input
                    label="{{ __('auth.register.parent.step2.first_name') }}"
                    name="first_name"
                    type="text"
                    icon="ri-user-line"
                    placeholder="{{ __('auth.register.parent.step2.first_name_placeholder') }}"
                    :value="old('first_name')"
                    :required="true"
                />

                <x-auth.input
                    label="{{ __('auth.register.parent.step2.last_name') }}"
                    name="last_name"
                    type="text"
                    icon="ri-user-line"
                    placeholder="{{ __('auth.register.parent.step2.last_name_placeholder') }}"
                    :value="old('last_name')"
                    :required="true"
                />
            </div>

            <x-auth.input
                label="{{ __('auth.register.parent.step2.email') }}"
                name="email"
                type="email"
                icon="ri-mail-line"
                placeholder="example@domain.com"
                :value="old('email')"
                :required="true"
                autocomplete="email"
            />

            <div>
                <x-auth.input
                    label="{{ __('auth.register.parent.step2.password') }}"
                    name="password"
                    type="password"
                    icon="ri-lock-line"
                    placeholder="{{ __('auth.register.parent.step2.password_placeholder') }}"
                    :required="true"
                    autocomplete="new-password"
                    helperText="{{ __('auth.register.parent.step2.password_helper') }}"
                />
            </div>

            <div>
                <x-auth.input
                    label="{{ __('auth.register.parent.step2.password_confirmation') }}"
                    name="password_confirmation"
                    type="password"
                    icon="ri-lock-check-line"
                    placeholder="{{ __('auth.register.parent.step2.password_confirmation_placeholder') }}"
                    :required="true"
                    autocomplete="new-password"
                />
                <div x-show="!passwordMatch" x-cloak>
                    <p class="mt-1.5 text-sm text-red-600 flex items-center animate-shake">
                        <i class="ri-error-warning-line ms-1"></i>
                        <span x-text="passwordError"></span>
                    </p>
                </div>
            </div>

            <!-- Optional Fields -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-auth.input
                    label="{{ __('auth.register.parent.step2.occupation') }}"
                    name="occupation"
                    type="text"
                    icon="ri-briefcase-line"
                    placeholder="{{ __('auth.register.parent.step2.occupation_placeholder') }}"
                    :value="old('occupation')"
                    :required="false"
                />

                <x-auth.input
                    label="{{ __('auth.register.parent.step2.address') }}"
                    name="address"
                    type="text"
                    icon="ri-map-pin-line"
                    placeholder="{{ __('auth.register.parent.step2.address_placeholder') }}"
                    :value="old('address')"
                    :required="false"
                />
            </div>
        </div>

        <!-- Global Errors -->
        @if ($errors->has('error') || session('error'))
            <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                <div class="flex items-start">
                    <i class="ri-error-warning-line text-red-500 text-lg ms-2 flex-shrink-0 mt-0.5"></i>
                    <p class="text-sm text-red-700">
                        {{ $errors->first('error') ?? session('error') }}
                    </p>
                </div>
            </div>
        @endif

        <!-- Submit Button -->
        <div x-show="verified" x-cloak>
            <button
                type="submit"
                :disabled="loading"
                :class="{ 'btn-loading opacity-75': loading }"
                class="w-full flex items-center justify-center gap-2 px-6 py-3.5 bg-gradient-to-r from-primary to-secondary text-white font-medium rounded-button hover:shadow-lg hover:-translate-y-0.5 transition-smooth disabled:cursor-not-allowed"
            >
                <i class="ri-user-add-line text-lg"></i>
                <span>{{ __('auth.register.parent.submit') }}</span>
            </button>
        </div>

        <!-- Login Link -->
        <div class="mt-6 text-center">
            <p class="text-sm text-gray-600">
                {{ __('auth.register.parent.already_have_account') }}
                <a href="{{ route('login', ['subdomain' => $academy->subdomain ?? request()->route('subdomain')]) }}"
                   class="font-medium text-primary hover:underline transition-smooth">
                    {{ __('auth.register.parent.login_link') }}
                </a>
            </p>
        </div>
    </form>

    <style>
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-slideIn {
            animation: slideIn 0.3s ease-out;
        }
    </style>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('parentRegistrationForm', () => ({
                // State
                loading: false,
                verifying: false,
                // Only restore state if there are validation errors (not on page refresh)
                verified: {{ ($errors->any() && (old('first_name') || old('email'))) ? 'true' : 'false' }},
                verifiedStudents: @json($errors->any() ? session('verified_students', []) : []),
                unverifiedCodes: [],
                studentsWithParent: [],
                studentCodes: @json($errors->any() && old('student_codes') ? explode(',', old('student_codes')) : ['']),
                passwordMatch: true,
                passwordError: '',

                // Initialization
                init() {
                    this.setupPasswordValidation();
                },

                // Methods
                addStudentField() {
                    if (this.studentCodes.length < 10) {
                        this.studentCodes.push('');
                    }
                },

                removeStudentField(index) {
                    if (this.studentCodes.length > 1) {
                        this.studentCodes.splice(index, 1);
                    }
                },

                validatePasswords() {
                    const password = document.getElementById('password')?.value || '';
                    const passwordConfirmation = document.getElementById('password_confirmation')?.value || '';

                    if (passwordConfirmation.length > 0) {
                        if (password !== passwordConfirmation) {
                            this.passwordMatch = false;
                            this.passwordError = '{{ __('auth.register.parent.step2.password_mismatch') }}';
                        } else {
                            this.passwordMatch = true;
                            this.passwordError = '';
                        }
                    } else {
                        this.passwordMatch = true;
                        this.passwordError = '';
                    }
                },

                setupPasswordValidation() {
                    this.$nextTick(() => {
                        const passwordField = document.getElementById('password');
                        const confirmationField = document.getElementById('password_confirmation');

                        if (passwordField && confirmationField) {
                            confirmationField.addEventListener('blur', () => {
                                this.validatePasswords();
                            });

                            confirmationField.addEventListener('input', () => {
                                this.validatePasswords();
                            });

                            passwordField.addEventListener('input', () => {
                                this.validatePasswords();
                            });
                        }
                    });
                },

                async verifyStudents() {
                    const phone = document.getElementById('parent_phone').value;
                    const countryCode = document.getElementById('parent_phone_country_code').value;
                    const codes = this.studentCodes.filter(code => code.trim() !== '').map(code => code.trim().toUpperCase());

                    if (!phone || codes.length === 0) {
                        window.toast?.warning('يرجى إدخال رقم الهاتف ورمز طالب واحد على الأقل');
                        return;
                    }

                    this.verifying = true;

                    try {
                        const response = await fetch('{{ route("parent.verify.students", ["subdomain" => $academy->subdomain ?? request()->route("subdomain")]) }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({
                                parent_phone: phone,
                                parent_phone_country_code: countryCode,
                                student_codes: codes.join(',')
                            })
                        });

                        const data = await response.json();

                        // Always update all arrays from API response
                        this.verifiedStudents = data.verified || [];
                        this.unverifiedCodes = data.unverified || [];
                        this.studentsWithParent = data.already_has_parent || [];

                        // Only set verified to true if we have students we can actually register
                        this.verified = this.verifiedStudents.length > 0;

                        if (data.success && this.verified) {
                            setTimeout(() => {
                                document.getElementById('personalInfoSection')?.scrollIntoView({
                                    behavior: 'smooth',
                                    block: 'start'
                                });
                            }, 300);
                        } else if (!data.success || this.verifiedStudents.length === 0) {
                            // Show appropriate message based on the results
                            if (this.studentsWithParent.length > 0 && this.verifiedStudents.length === 0) {
                                window.toast?.error('جميع الطلاب المدخلين لديهم حساب ولي أمر بالفعل. لا يمكن إنشاء حساب جديد.');
                            } else if (data.message) {
                                window.toast?.error(data.message);
                            } else {
                                window.toast?.error('حدث خطأ أثناء التحقق. يرجى المحاولة مرة أخرى.');
                            }
                        }
                    } catch (error) {
                        window.toast?.error('حدث خطأ أثناء التحقق. يرجى التحقق من الاتصال بالإنترنت والمحاولة مرة أخرى.');
                    } finally {
                        this.verifying = false;
                    }
                },

                handleSubmit(event) {
                    const hasValidationErrors = {{ $errors->any() ? 'true' : 'false' }};
                    const hasOldInput = {{ (old('first_name') || old('email')) ? 'true' : 'false' }};

                    if (!this.verified || (this.verifiedStudents.length === 0 && !(hasValidationErrors && hasOldInput))) {
                        event.preventDefault();
                        window.toast?.warning('يرجى التحقق من رموز الطلاب أولاً');
                    } else if (!this.passwordMatch) {
                        event.preventDefault();
                        window.toast?.warning('كلمتا المرور غير متطابقتين. يرجى التأكد من تطابق كلمتي المرور');
                    } else {
                        this.loading = true;
                    }
                }
            }));
        });
    </script>
</x-auth.layout>
