@props([
    'name' => 'phone',
    'label' => 'رقم الهاتف',
    'required' => true,
    'value' => '',
    'placeholder' => 'أدخل رقم الهاتف',
    'helperText' => null,
    'error' => null,
    'countryCodeField' => 'phone_country_code',
    'countryField' => 'phone_country', // NEW: ISO country code field (e.g., "EG", "SA")
    'initialCountry' => 'sa',
])

<div class="phone-input-wrapper mb-4"
     x-data="phoneInputData(
         '{{ $name }}',
         '{{ $countryCodeField }}',
         '{{ $countryField }}',
         '{{ $initialCountry }}',
         {{ json_encode($value ?? '') }}
     )">
    <label for="{{ $name }}" class="block text-sm font-medium text-gray-700 mb-2">
        {{ $label }} @if($required)<span class="text-red-500">*</span>@endif
    </label>

    <input
        type="tel"
        id="{{ $name }}"
        name="{{ $name }}"
        @if($required) required @endif
        x-ref="input"
        class="input-field appearance-none relative block w-full px-4 border border-gray-300 placeholder-gray-400 text-gray-900 rounded-button focus:outline-none transition-smooth text-xl {{ $error ? 'border-red-500' : '' }}"
        style="padding-top: 16px; padding-bottom: 8px;"
        placeholder="{{ $placeholder }}"
        value="{{ old($name, $value) }}"
    >

    <!-- Hidden field for country code (e.g., "+20") -->
    <input type="hidden" id="{{ $countryCodeField }}" name="{{ $countryCodeField }}" x-model="countryCode">

    <!-- Hidden field for ISO country code (e.g., "EG") -->
    <input type="hidden" id="{{ $countryField }}" name="{{ $countryField }}" x-model="countryISO">

    @if($helperText)
        <p class="mt-1 text-xs text-gray-500">
            <i class="fas fa-lightbulb ml-1"></i>
            {{ $helperText }}
        </p>
    @endif

    @if($error)
        <p class="mt-1 text-sm text-red-600">{{ $error }}</p>
    @endif
</div>

@once
@push('styles')
<style>
    /* Full width container */
    .phone-input-wrapper {
        width: 100% !important;
    }

    /* Force LTR layout and full width - STRONG OVERRIDES */
    .phone-input-wrapper .iti {
        width: 100% !important;
        direction: ltr !important;
    }

    /* Position flag dropdown on LEFT side (force override) */
    .phone-input-wrapper .iti__flag-container {
        left: 0 !important;
        right: auto !important;
    }

    /* Style the flag button */
    .phone-input-wrapper .iti__selected-flag {
        padding: 0 8px 0 12px !important;
        border-right: 1px solid #d1d5db !important;
        border-left: none !important;
    }

    /* Arrow positioning */
    .phone-input-wrapper .iti__arrow {
        margin-left: 6px !important;
        margin-right: 0 !important;
    }

    /* Make country code BOLD and VISIBLE */
    .phone-input-wrapper .iti__selected-dial-code {
        font-weight: 700 !important;
        color: #1f2937 !important;
        font-size: 1rem !important;
        margin-left: 8px !important;
        margin-top: 4px !important;
        display: inline-block !important;
    }

    /* Input field - LTR with proper padding for flag+code */
    .phone-input-wrapper .iti input[type="tel"] {
        padding-left: 110px !important;
        padding-right: 16px !important;
        text-align: left !important;
        direction: ltr !important;
        width: 100% !important;
    }

    /* Dropdown list styling */
    .iti__country-list {
        text-align: right !important;
        direction: rtl !important;
    }

    .iti__country-name {
        margin-right: 6px !important;
        margin-left: 0 !important;
    }

    .iti__dial-code {
        direction: ltr !important;
        margin-left: 0 !important;
        margin-right: 6px !important;
    }

    /* Validation states with strong colors */
    .phone-input-wrapper .phone-valid {
        border-color: #10b981 !important;
        box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.2) !important;
    }

    .phone-input-wrapper .phone-invalid {
        border-color: #ef4444 !important;
        box-shadow: 0 0 0 2px rgba(239, 68, 68, 0.2) !important;
    }

    /* Focus states */
    .phone-input-wrapper .iti input[type="tel"]:focus {
        outline: none !important;
    }

    .phone-input-wrapper .phone-valid:focus {
        border-color: #10b981 !important;
        box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.3) !important;
    }

    .phone-input-wrapper .phone-invalid:focus {
        border-color: #ef4444 !important;
        box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.3) !important;
    }
</style>
@endpush

@push('scripts')
<script>
// Arabic country names mapping
window.arabicCountryNames = {
    'sa': 'السعودية',
    'eg': 'مصر',
    'ae': 'الإمارات',
    'kw': 'الكويت',
    'qa': 'قطر',
    'om': 'عمان',
    'bh': 'البحرين',
    'jo': 'الأردن',
    'lb': 'لبنان',
    'ps': 'فلسطين',
    'iq': 'العراق',
    'ye': 'اليمن',
    'sd': 'السودان',
    'sy': 'سوريا',
    'ma': 'المغرب',
    'dz': 'الجزائر',
    'tn': 'تونس',
    'ly': 'ليبيا',
    'mr': 'موريتانيا',
    'so': 'الصومال',
    'dj': 'جيبوتي',
    'km': 'جزر القمر',
    'us': 'الولايات المتحدة',
    'gb': 'المملكة المتحدة',
    'ca': 'كندا',
    'au': 'أستراليا',
    'de': 'ألمانيا',
    'fr': 'فرنسا',
    'it': 'إيطاليا',
    'es': 'إسبانيا',
    'tr': 'تركيا',
    'pk': 'باكستان',
    'in': 'الهند',
    'bd': 'بنغلاديش',
    'my': 'ماليزيا',
    'id': 'إندونيسيا',
};

// Phone format examples per country (pure number format)
window.phoneFormats = {
    'sa': '512345678',
    'eg': '1001234567',
    'ae': '501234567',
    'kw': '50012345',
    'qa': '33123456',
    'om': '92123456',
    'bh': '36001234',
    'jo': '790123456',
    'lb': '71123456',
    'ps': '599123456',
    'iq': '7901234567',
    'ye': '712345678',
    'us': '2025551234',
    'gb': '7400123456',
};

// Exact digit counts per country for strict validation
window.phoneDigitCounts = {
    'sa': 9,
    'eg': 10,
    'ae': 9,
    'kw': 8,
    'qa': 8,
    'om': 8,
    'bh': 8,
    'jo': 9,
    'lb': 8,
    'ps': 9,
    'iq': 10,
    'ye': 9,
    'sd': 9,
    'sy': 10,
    'ma': 9,
    'dz': 9,
    'tn': 8,
    'ly': 10,
    'mr': 8,
    'so': 9,
    'dj': 8,
    'km': 7,
    'us': 10,
    'gb': 10,
    'ca': 10,
    'au': 9,
    'de': 10,
    'fr': 9,
    'it': 10,
    'es': 9,
    'tr': 10,
    'pk': 10,
    'in': 10,
    'bd': 10,
    'my': 9,
    'id': 10,
};

function phoneInputData(fieldName, countryCodeField, countryField, initialCountry, initialValue) {
    return {
        iti: null,
        countryCode: '+966',
        countryISO: 'SA', // NEW: Track ISO country code
        maxDigits: 9,

        init() {
            // Wait for intl-tel-input library to load
            const initPhone = () => {
                if (!window.intlTelInput) {
                    console.warn('Waiting for intl-tel-input library...');
                    // Poll every 100ms for up to 5 seconds
                    const maxAttempts = 50;
                    let attempts = 0;
                    const checkInterval = setInterval(() => {
                        attempts++;
                        if (window.intlTelInput) {
                            clearInterval(checkInterval);
                            this.initializeIntlTelInput();
                        } else if (attempts >= maxAttempts) {
                            clearInterval(checkInterval);
                            console.error('intl-tel-input library failed to load after 5 seconds');
                        }
                    }, 100);
                    return;
                }

                this.initializeIntlTelInput();
            };

            // Use nextTick to ensure DOM is ready
            this.$nextTick(initPhone);
        },

        initializeIntlTelInput() {
            // Initialize intl-tel-input with strict validation and LTR layout
            this.iti = window.intlTelInput(this.$refs.input, {
                initialCountry: initialCountry,
                preferredCountries: ['sa', 'eg', 'ae', 'kw', 'qa', 'om', 'bh', 'jo', 'lb', 'ps', 'iq', 'ye', 'sd'],
                separateDialCode: true, // Show country code separately beside flag
                showSelectedDialCode: true, // Ensure dial code is visible
                autoPlaceholder: "aggressive",
                formatOnDisplay: false, // We'll handle formatting ourselves
                strictMode: true,
                utilsScript: "https://cdn.jsdelivr.net/npm/intl-tel-input@19.5.6/build/js/utils.js",
                customPlaceholder: function(selectedCountryPlaceholder, selectedCountryData) {
                    return window.phoneFormats[selectedCountryData.iso2] || selectedCountryPlaceholder;
                },
                // Force container to respect our LTR layout
                containerClass: 'iti-ltr-forced'
            });

            // Force LTR direction on the container
            const itiContainer = this.$refs.input.closest('.iti');
            if (itiContainer) {
                itiContainer.style.direction = 'ltr';
                itiContainer.classList.add('iti-ltr-forced');
            }

            // Set initial value
            if (initialValue) {
                this.$refs.input.value = initialValue;
            }

            // Update country code and max length on load
            this.updateCountryCode();
            this.updateMaxLength();

            // Listen for country change
            this.$refs.input.addEventListener('countrychange', () => {
                this.updateCountryCode();
                this.updatePlaceholder();
                this.updateMaxLength();
                // Clear input when country changes to prevent invalid numbers
                this.$refs.input.value = '';
                this.validateInput();
            });

            // Strict input control - only allow digits
            this.$refs.input.addEventListener('keypress', (e) => {
                const char = String.fromCharCode(e.which);
                // Only allow digits
                if (!/[0-9]/.test(char)) {
                    e.preventDefault();
                    return false;
                }

                // Check if adding this digit would exceed max length
                const currentDigits = this.$refs.input.value.replace(/\D/g, '');
                if (currentDigits.length >= this.maxDigits) {
                    e.preventDefault();
                    return false;
                }
            });

            // Prevent pasting non-numeric content
            this.$refs.input.addEventListener('paste', (e) => {
                e.preventDefault();
                const pastedText = (e.clipboardData || window.clipboardData).getData('text');
                const digitsOnly = pastedText.replace(/\D/g, '');
                const truncated = digitsOnly.substring(0, this.maxDigits);
                this.$refs.input.value = truncated;
                this.validateInput();
            });

            // Validate on input
            this.$refs.input.addEventListener('input', (e) => {
                // Strip non-digits
                let value = this.$refs.input.value.replace(/\D/g, '');

                // Enforce max length
                if (value.length > this.maxDigits) {
                    value = value.substring(0, this.maxDigits);
                }

                this.$refs.input.value = value;
                this.validateInput();
            });

            // Custom rendering for Arabic names in dropdown
            this.customizeDropdown();

            // Update placeholder initially
            this.updatePlaceholder();
        },

        updatePlaceholder() {
            if (this.iti) {
                const countryData = this.iti.getSelectedCountryData();
                const placeholder = window.phoneFormats[countryData.iso2];
                if (placeholder) {
                    this.$refs.input.placeholder = placeholder;
                }
            }
        },

        validateInput() {
            if (!this.iti) return;

            const inputElement = this.$refs.input;
            const currentDigits = inputElement.value.replace(/\D/g, '');
            const countryData = this.iti.getSelectedCountryData();
            const requiredDigits = window.phoneDigitCounts[countryData.iso2] || this.maxDigits;

            // Check if the number has the exact required digit count
            const isValidLength = currentDigits.length === requiredDigits;
            const hasValue = currentDigits.length > 0;

            // Remove all validation classes first
            inputElement.classList.remove('phone-valid', 'phone-invalid', 'border-red-500', 'border-gray-300', 'border-blue-500');

            if (hasValue) {
                if (isValidLength) {
                    // Valid: green border with glow
                    inputElement.classList.add('phone-valid');
                } else {
                    // Invalid: red border with glow
                    inputElement.classList.add('phone-invalid');
                }
            } else {
                // Empty: default gray border
                inputElement.classList.add('border-gray-300');
            }
        },

        updateMaxLength() {
            if (this.iti) {
                const countryData = this.iti.getSelectedCountryData();
                this.maxDigits = window.phoneDigitCounts[countryData.iso2] || 15;
                // Update input maxlength attribute
                this.$refs.input.setAttribute('maxlength', this.maxDigits);
            }
        },

        updateFormatHint() {
            // Simplified - no longer displaying hint text
            // Keep function for backward compatibility
        },

        updateCountryCode() {
            if (this.iti) {
                const countryData = this.iti.getSelectedCountryData();
                this.countryCode = '+' + countryData.dialCode;
                this.countryISO = countryData.iso2.toUpperCase(); // NEW: Update ISO code (e.g., "EG", "SA")
            }
        },

        customizeDropdown() {
            // Add Arabic names to dropdown after a short delay
            setTimeout(() => {
                const countries = document.querySelectorAll('.iti__country');
                countries.forEach(country => {
                    const countryCode = country.getAttribute('data-country-code');
                    if (countryCode && window.arabicCountryNames[countryCode]) {
                        const nameSpan = country.querySelector('.iti__country-name');
                        if (nameSpan) {
                            nameSpan.textContent = window.arabicCountryNames[countryCode];
                        }
                    }
                });
            }, 100);
        }
    }
}
</script>
@endpush
@endonce
