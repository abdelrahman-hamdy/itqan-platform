@props([
    'name' => 'phone',
    'label' => null,
    'required' => true,
    'value' => '',
    'placeholder' => null,
    'helperText' => null,
    'error' => null,
    'countryCodeField' => 'phone_country_code',
    'countryField' => 'phone_country',
    'initialCountry' => 'sa',
])

@php
    $displayLabel = $label ?? __('components.forms.phone_input.label');
    $inputId = 'phone_' . Str::random(8);

    // Phone format hints by country
    $phoneFormats = [
        'sa' => '512345678',
        'eg' => '1001234567',
        'ae' => '501234567',
        'kw' => '50012345',
        'qa' => '33123456',
        'om' => '92123456',
        'bh' => '36001234',
        'jo' => '790123456',
    ];
    $defaultPlaceholder = $phoneFormats[$initialCountry] ?? '512345678';
@endphp

<div class="mb-4" id="{{ $inputId }}_wrapper">
    <label for="{{ $inputId }}" class="block text-sm font-medium text-gray-700 mb-2">
        {{ $displayLabel }} @if($required)<span class="text-red-500">*</span>@endif
    </label>

    <div class="phone-input-container">
        <input
            type="tel"
            id="{{ $inputId }}"
            name="{{ $name }}"
            @if($required) required @endif
            class="phone-tel-input input-field appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-smooth {{ $error ? 'border-red-500 ring-2 ring-red-200' : '' }}"
            placeholder="{{ $placeholder ?? $defaultPlaceholder }}"
            value="{{ old($name, $value) }}"
            autocomplete="tel"
        >
    </div>

    <input type="hidden" name="{{ $countryCodeField }}" id="{{ $inputId }}_country_code" value="+966">
    <input type="hidden" name="{{ $countryField }}" id="{{ $inputId }}_country" value="SA">

    @if($helperText)
        <p class="mt-1.5 text-xs text-gray-500">{{ $helperText }}</p>
    @endif

    @if($error)
        <p class="mt-1.5 text-sm text-red-600">{{ $error }}</p>
    @endif
</div>

<script>
(function() {
    const inputId = '{{ $inputId }}';
    const initialCountry = '{{ $initialCountry }}';
    const initialValue = '{{ old($name, $value) }}';

    // Phone formats by country code
    const phoneFormats = {
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
        'gb': '7400123456'
    };

    // Max digits by country
    const digitCounts = {
        'sa': 9, 'eg': 10, 'ae': 9, 'kw': 8, 'qa': 8, 'om': 8, 'bh': 8,
        'jo': 9, 'lb': 8, 'ps': 9, 'iq': 10, 'ye': 9, 'us': 10, 'gb': 10
    };

    function initPhoneInput() {
        const input = document.getElementById(inputId);
        if (!input) return;

        // Check if already initialized
        if (input.hasAttribute('data-iti-initialized')) return;
        input.setAttribute('data-iti-initialized', 'true');

        // Wait for intl-tel-input library
        if (typeof window.intlTelInput === 'undefined') {
            setTimeout(initPhoneInput, 100);
            return;
        }

        const iti = window.intlTelInput(input, {
            initialCountry: initialCountry,
            preferredCountries: ['sa', 'eg', 'ae', 'kw', 'qa', 'om', 'bh', 'jo'],
            separateDialCode: true,
            showSelectedDialCode: true,
            formatOnDisplay: false,
            autoPlaceholder: 'aggressive',
            utilsScript: 'https://cdn.jsdelivr.net/npm/intl-tel-input@19.5.6/build/js/utils.js'
        });

        // Force LTR direction on the container
        const itiContainer = input.closest('.iti');
        if (itiContainer) {
            itiContainer.style.direction = 'ltr';
            itiContainer.style.width = '100%';
        }

        // Update hidden fields and format hint
        function updateCountryFields() {
            const data = iti.getSelectedCountryData();
            const countryCode = '+' + (data.dialCode || '966');
            const countryISO = (data.iso2 || 'sa').toUpperCase();

            document.getElementById(inputId + '_country_code').value = countryCode;
            document.getElementById(inputId + '_country').value = countryISO;

            // Update placeholder with country-specific format
            const format = phoneFormats[data.iso2] || '512345678';
            input.placeholder = format;
        }

        // Get max digits for current country
        function getMaxDigits() {
            const data = iti.getSelectedCountryData();
            return digitCounts[data.iso2] || 15;
        }

        // Initial update
        updateCountryFields();

        // Country change handler
        input.addEventListener('countrychange', function() {
            updateCountryFields();
            input.value = '';
            input.focus();
        });

        // Input validation - only digits
        input.addEventListener('input', function(e) {
            const maxDigits = getMaxDigits();
            e.target.value = e.target.value.replace(/\D/g, '').substring(0, maxDigits);
        });

        input.addEventListener('keypress', function(e) {
            if (!/[0-9]/.test(e.key)) {
                e.preventDefault();
            }
        });

        // Set initial value
        if (initialValue) {
            input.value = initialValue.replace(/\D/g, '');
        }
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initPhoneInput);
    } else {
        initPhoneInput();
    }
})();
</script>
