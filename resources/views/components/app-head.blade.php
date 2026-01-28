@props(['title' => null, 'description' => null])

@php
    $appName = config('app.name', 'منصة إتقان');
    $pageTitle = $title ?? $appName;
    $pageDescription = $description ?? 'منصة التعلم الإلكتروني';

    // Font configuration - Tajawal for both Arabic and English
    // Using range syntax (200..900) instead of semicolon-separated for better compatibility
    $primaryFont = 'Tajawal';
    $fontWeights = '200..900';

    // Get current academy and colors
    $academy = auth()->user()?->academy ?? \App\Models\Academy::first();
    $primaryColor = $academy?->brand_color ?? \App\Enums\TailwindColor::SKY;
    $gradientPalette = $academy?->gradient_palette ?? \App\Enums\GradientPalette::OCEAN_BREEZE;
    $favicon = $academy?->favicon ?? null;

    // Generate CSS variables for all shades
    $primaryVars = $primaryColor->generateCssVariables('primary');

    // Get gradient colors for CSS variables
    $gradientColors = $gradientPalette->getColors();
    $gradientFrom = $gradientColors['from'];
    $gradientTo = $gradientColors['to'];
@endphp

<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">

<!-- Page Title -->
<title>{{ $pageTitle }}</title>
<meta name="description" content="{{ $pageDescription }}">

<!-- Favicon -->
@if($favicon)
<link rel="icon" type="image/png" href="{{ Storage::url($favicon) }}">
@else
<link rel="icon" type="image/svg+xml" href="{{ asset('images/itqan-logo.svg') }}">
<link rel="icon" type="image/png" href="{{ asset('favicon.ico') }}">
@endif

<!-- Fonts - Primary: Tajawal (Arabic & English) -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family={{ $primaryFont }}:wght@{{ $fontWeights }}&display=swap" rel="stylesheet">

<!-- Icons (RemixIcon & Flag-icons) are bundled via Vite in app.css -->

<!-- Phone Input Library is bundled via Vite (resources/js/phone-input.js) -->
<!-- CSS is imported in phone-input.js, JS exposes window.intlTelInput -->

<!-- Phone Input Data Function - Define before phone input is used -->
<!-- Arabic country names are loaded from resources/js/phone-country-data.js via Vite bundle -->
<script>
window.phoneFormats = {
    'sa': '512345678', 'eg': '1001234567', 'ae': '501234567', 'kw': '50012345',
    'qa': '33123456', 'om': '92123456', 'bh': '36001234', 'jo': '790123456',
    'lb': '71123456', 'ps': '599123456', 'iq': '7901234567', 'ye': '712345678',
    'us': '2025551234', 'gb': '7400123456',
};
window.phoneDigitCounts = {
    'sa': 9, 'eg': 10, 'ae': 9, 'kw': 8, 'qa': 8, 'om': 8, 'bh': 8,
    'jo': 9, 'lb': 8, 'ps': 9, 'iq': 10, 'ye': 9, 'sd': 9, 'sy': 10,
    'ma': 9, 'dz': 9, 'tn': 8, 'ly': 10, 'mr': 8, 'so': 9, 'dj': 8,
    'km': 7, 'us': 10, 'gb': 10, 'ca': 10, 'au': 9, 'de': 10, 'fr': 9,
    'it': 10, 'es': 9, 'tr': 10, 'pk': 10, 'in': 10, 'bd': 10, 'my': 9, 'id': 10,
};
window.phoneInputData = function(fieldName, countryCodeField, countryField, initialCountry, initialValue) {
    return {
        iti: null, countryCode: '+966', countryISO: 'SA', maxDigits: 9,
        fieldName, countryCodeField, countryField, initialCountry, initialValue,
        initialized: false,
        init() {
            const self = this;
            // Prevent multiple initializations
            if (this.initialized) return;
            this.initialized = true;

            const initPhone = () => {
                if (!window.intlTelInput) {
                    let attempts = 0;
                    const checkInterval = setInterval(() => {
                        attempts++;
                        if (window.intlTelInput) { clearInterval(checkInterval); self.initializeIntlTelInput(); }
                        else if (attempts >= 50) { clearInterval(checkInterval); }
                    }, 100);
                    return;
                }
                self.initializeIntlTelInput();
            };
            this.$nextTick(initPhone);
        },
        initializeIntlTelInput() {
            const inputEl = this.$refs.input;
            if (!inputEl) return;

            // Check if already initialized - destroy old instance first
            if (inputEl.closest('.iti')) {
                if (this.iti && typeof this.iti.destroy === 'function') {
                    this.iti.destroy();
                }
                const existingWrapper = inputEl.closest('.iti');
                if (existingWrapper && existingWrapper.parentNode) {
                    existingWrapper.parentNode.insertBefore(inputEl, existingWrapper);
                    existingWrapper.remove();
                }
            }

            this.iti = window.intlTelInput(inputEl, {
                initialCountry: this.initialCountry,
                excludeCountries: ['il'],
                preferredCountries: ['sa', 'eg', 'ae', 'kw', 'qa', 'om', 'bh', 'jo'],
                localizedCountries: window.getLocalizedCountryNames ? window.getLocalizedCountryNames() : undefined,
                separateDialCode: true, showSelectedDialCode: true, autoPlaceholder: "aggressive",
                formatOnDisplay: false, strictMode: true,
                utilsScript: "https://cdn.jsdelivr.net/npm/intl-tel-input@19.5.6/build/js/utils.js",
                customPlaceholder: (p, d) => window.phoneFormats[d.iso2] || p,
                containerClass: 'iti-ltr-forced'
            });
            const itiContainer = inputEl.closest('.iti');
            if (itiContainer) { itiContainer.style.direction = 'ltr'; itiContainer.classList.add('iti-ltr-forced'); }
            if (this.initialValue) inputEl.value = this.initialValue;
            this.updateCountryCode(); this.updateMaxLength();
            inputEl.addEventListener('countrychange', () => {
                this.updateCountryCode(); this.updatePlaceholder(); this.updateMaxLength();
                inputEl.value = ''; this.validateInput();
            });
            inputEl.addEventListener('keypress', (e) => {
                if (!/[0-9]/.test(String.fromCharCode(e.which))) { e.preventDefault(); return false; }
                if (inputEl.value.replace(/\D/g, '').length >= this.maxDigits) { e.preventDefault(); return false; }
            });
            inputEl.addEventListener('paste', (e) => {
                e.preventDefault();
                inputEl.value = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '').substring(0, this.maxDigits);
                this.validateInput();
            });
            inputEl.addEventListener('input', () => {
                let v = inputEl.value.replace(/\D/g, '');
                if (v.length > this.maxDigits) v = v.substring(0, this.maxDigits);
                inputEl.value = v; this.validateInput();
            });
            this.updatePlaceholder();
        },
        updatePlaceholder() {
            if (this.iti && this.$refs.input) {
                const p = window.phoneFormats[this.iti.getSelectedCountryData().iso2];
                if (p) this.$refs.input.placeholder = p;
            }
        },
        validateInput() {
            if (!this.iti || !this.$refs.input) return;
            const el = this.$refs.input, d = el.value.replace(/\D/g, ''), cd = this.iti.getSelectedCountryData();
            const req = window.phoneDigitCounts[cd.iso2] || this.maxDigits;
            el.classList.remove('phone-valid', 'phone-invalid', 'border-red-500', 'border-gray-300');
            if (d.length > 0) el.classList.add(d.length === req ? 'phone-valid' : 'phone-invalid');
            else el.classList.add('border-gray-300');
        },
        updateMaxLength() {
            if (this.iti && this.$refs.input) {
                this.maxDigits = window.phoneDigitCounts[this.iti.getSelectedCountryData().iso2] || 15;
                this.$refs.input.setAttribute('maxlength', this.maxDigits);
            }
        },
        updateCountryCode() {
            if (this.iti) {
                const cd = this.iti.getSelectedCountryData();
                this.countryCode = '+' + cd.dialCode; this.countryISO = cd.iso2.toUpperCase();
            }
        }
    };
};
var phoneInputData = window.phoneInputData;
</script>

<!-- NOTE: Alpine.js is bundled with Livewire 3 (inject_assets: true in config/livewire.php) -->
<!-- Do NOT load Alpine.js from CDN - it causes conflicts and "Uncaught (in promise)" errors -->

<!-- Toast Queue Bootstrap - Must load before any other JS to capture early notifications -->
<script src="{{ asset('js/toast-queue.js') }}"></script>

<!-- Phone input component scripts (additional from stack) -->
@stack('head-scripts')

<!-- Styles -->
@vite(['resources/css/app.css', 'resources/js/app.js'])

<!-- Academy Colors CSS Variables -->
<style>
    :root {
        @foreach($primaryVars as $varName => $varValue)
        {{ $varName }}: {{ $varValue }};
        @endforeach

        /* Gradient palette variables */
        --gradient-from: {{ $gradientFrom }};
        --gradient-to: {{ $gradientTo }};
    }

    /* Phone Input Styles - Now bundled via resources/css/components/phone-input.css */
</style>

<!-- Additional Component Styles -->
@stack('styles')

<!-- Additional Head Content -->
{{ $slot }}
