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
    // Define Arabic country names if not already set by app-head
    if (!window.arabicCountryNames) {
        window.arabicCountryNames = {
            // Arab countries
            'sa': 'السعودية', 'eg': 'مصر', 'ae': 'الإمارات', 'kw': 'الكويت',
            'qa': 'قطر', 'om': 'عُمان', 'bh': 'البحرين', 'jo': 'الأردن',
            'lb': 'لبنان', 'ps': 'فلسطين', 'iq': 'العراق', 'ye': 'اليمن',
            'sd': 'السودان', 'ss': 'جنوب السودان', 'sy': 'سوريا', 'ma': 'المغرب',
            'dz': 'الجزائر', 'tn': 'تونس', 'ly': 'ليبيا', 'mr': 'موريتانيا',
            'so': 'الصومال', 'dj': 'جيبوتي', 'km': 'جزر القمر',
            // Europe
            'gb': 'المملكة المتحدة', 'de': 'ألمانيا', 'fr': 'فرنسا', 'it': 'إيطاليا',
            'es': 'إسبانيا', 'pt': 'البرتغال', 'nl': 'هولندا', 'be': 'بلجيكا',
            'at': 'النمسا', 'ch': 'سويسرا', 'se': 'السويد', 'no': 'النرويج',
            'dk': 'الدنمارك', 'fi': 'فنلندا', 'ie': 'أيرلندا', 'pl': 'بولندا',
            'cz': 'التشيك', 'ro': 'رومانيا', 'hu': 'المجر', 'gr': 'اليونان',
            'bg': 'بلغاريا', 'hr': 'كرواتيا', 'sk': 'سلوفاكيا', 'si': 'سلوفينيا',
            'rs': 'صربيا', 'ba': 'البوسنة والهرسك', 'me': 'الجبل الأسود',
            'mk': 'مقدونيا الشمالية', 'al': 'ألبانيا', 'xk': 'كوسوفو',
            'ee': 'إستونيا', 'lv': 'لاتفيا', 'lt': 'ليتوانيا', 'lu': 'لوكسمبورغ',
            'mt': 'مالطا', 'cy': 'قبرص', 'is': 'آيسلندا', 'li': 'ليختنشتاين',
            'mc': 'موناكو', 'ad': 'أندورا', 'sm': 'سان مارينو', 'va': 'الفاتيكان',
            'md': 'مولدوفا', 'ua': 'أوكرانيا', 'by': 'بيلاروسيا', 'ru': 'روسيا',
            'ge': 'جورجيا', 'am': 'أرمينيا', 'az': 'أذربيجان',
            'gg': 'غيرنزي', 'je': 'جيرزي', 'im': 'جزيرة مان',
            'gi': 'جبل طارق', 'fo': 'جزر فارو', 'ax': 'جزر آلاند',
            'sj': 'سفالبارد ويان ماين',
            // Americas
            'us': 'الولايات المتحدة', 'ca': 'كندا', 'mx': 'المكسيك',
            'br': 'البرازيل', 'ar': 'الأرجنتين', 'co': 'كولومبيا', 'cl': 'تشيلي',
            'pe': 'بيرو', 've': 'فنزويلا', 'ec': 'الإكوادور', 'bo': 'بوليفيا',
            'py': 'باراغواي', 'uy': 'الأوروغواي', 'gy': 'غيانا', 'sr': 'سورينام',
            'gf': 'غويانا الفرنسية', 'pa': 'بنما', 'cr': 'كوستاريكا',
            'ni': 'نيكاراغوا', 'hn': 'هندوراس', 'sv': 'السلفادور',
            'gt': 'غواتيمالا', 'bz': 'بليز', 'cu': 'كوبا', 'jm': 'جامايكا',
            'ht': 'هايتي', 'do': 'جمهورية الدومينيكان', 'pr': 'بورتوريكو',
            'tt': 'ترينيداد وتوباغو', 'bb': 'باربادوس', 'bs': 'الباهاما',
            'ag': 'أنتيغوا وباربودا', 'dm': 'دومينيكا', 'gd': 'غرينادا',
            'kn': 'سانت كيتس ونيفيس', 'lc': 'سانت لوسيا',
            'vc': 'سانت فنسنت والغرينادين', 'gp': 'غوادلوب', 'mq': 'مارتينيك',
            'ky': 'جزر كايمان', 'bm': 'برمودا', 'vg': 'جزر العذراء البريطانية',
            'vi': 'جزر العذراء الأمريكية', 'ai': 'أنغويلا', 'ms': 'مونتسرات',
            'tc': 'جزر توركس وكايكوس', 'cw': 'كوراساو', 'sx': 'سينت مارتن',
            'bl': 'سان بارتيلمي', 'mf': 'سان مارتن', 'pm': 'سان بيير وميكلون',
            'fk': 'جزر فوكلاند', 'bq': 'بونير',
            // Asia
            'tr': 'تركيا', 'ir': 'إيران', 'pk': 'باكستان', 'af': 'أفغانستان',
            'in': 'الهند', 'bd': 'بنغلاديش', 'lk': 'سريلانكا', 'np': 'نيبال',
            'bt': 'بوتان', 'mv': 'المالديف', 'cn': 'الصين', 'jp': 'اليابان',
            'kr': 'كوريا الجنوبية', 'kp': 'كوريا الشمالية', 'tw': 'تايوان',
            'hk': 'هونغ كونغ', 'mo': 'ماكاو', 'mn': 'منغوليا',
            'kz': 'كازاخستان', 'uz': 'أوزبكستان', 'tm': 'تركمانستان',
            'kg': 'قيرغيزستان', 'tj': 'طاجيكستان',
            'my': 'ماليزيا', 'id': 'إندونيسيا', 'sg': 'سنغافورة', 'ph': 'الفلبين',
            'th': 'تايلاند', 'vn': 'فيتنام', 'mm': 'ميانمار', 'kh': 'كمبوديا',
            'la': 'لاوس', 'bn': 'بروناي', 'tl': 'تيمور الشرقية',
            // Africa
            'ng': 'نيجيريا', 'gh': 'غانا', 'ke': 'كينيا', 'tz': 'تنزانيا',
            'ug': 'أوغندا', 'et': 'إثيوبيا', 'er': 'إريتريا', 'rw': 'رواندا',
            'bi': 'بوروندي', 'za': 'جنوب أفريقيا', 'cm': 'الكاميرون',
            'sn': 'السنغال', 'ci': 'ساحل العاج', 'ml': 'مالي', 'bf': 'بوركينا فاسو',
            'ne': 'النيجر', 'td': 'تشاد', 'cf': 'جمهورية أفريقيا الوسطى',
            'cg': 'الكونغو', 'cd': 'الكونغو الديمقراطية', 'ga': 'الغابون',
            'gq': 'غينيا الاستوائية', 'ao': 'أنغولا', 'mz': 'موزمبيق',
            'mg': 'مدغشقر', 'mw': 'ملاوي', 'zm': 'زامبيا', 'zw': 'زيمبابوي',
            'bw': 'بوتسوانا', 'na': 'ناميبيا', 'sz': 'إسواتيني', 'ls': 'ليسوتو',
            'gm': 'غامبيا', 'gn': 'غينيا', 'gw': 'غينيا بيساو', 'sl': 'سيراليون',
            'lr': 'ليبيريا', 'cv': 'الرأس الأخضر', 'st': 'ساو تومي وبرينسيبي',
            'tg': 'توغو', 'bj': 'بنين', 'mu': 'موريشيوس', 'sc': 'سيشل',
            'sh': 'سانت هيلينا', 'yt': 'مايوت', 're': 'ريونيون',
            'eh': 'الصحراء الغربية',
            // Oceania
            'au': 'أستراليا', 'nz': 'نيوزيلندا', 'fj': 'فيجي', 'pg': 'بابوا غينيا الجديدة',
            'ws': 'ساموا', 'to': 'تونغا', 'vu': 'فانواتو', 'sb': 'جزر سليمان',
            'ki': 'كيريباتي', 'mh': 'جزر مارشال', 'fm': 'ميكرونيزيا',
            'pw': 'بالاو', 'nr': 'ناورو', 'tv': 'توفالو', 'gu': 'غوام',
            'as': 'ساموا الأمريكية', 'mp': 'جزر ماريانا الشمالية',
            'pf': 'بولينيزيا الفرنسية', 'nc': 'كاليدونيا الجديدة',
            'wf': 'واليس وفوتونا', 'ck': 'جزر كوك', 'nu': 'نيوي',
            'tk': 'توكيلاو', 'nf': 'جزيرة نورفولك',
            'gl': 'غرينلاند', 'io': 'إقليم المحيط الهندي البريطاني',
            'cc': 'جزر كوكوس', 'cx': 'جزيرة الكريسماس',
        };
    }

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
            excludeCountries: ['il'],
            preferredCountries: ['sa', 'eg', 'ae', 'kw', 'qa', 'om', 'bh', 'jo'],
            localizedCountries: window.arabicCountryNames,
            separateDialCode: true,
            showSelectedDialCode: true,
            formatOnDisplay: false,
            autoPlaceholder: 'aggressive',
            strictMode: true,
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

        // Customize dropdown to show Arabic names
        function customizeDropdown() {
            if (!window.arabicCountryNames) return;
            setTimeout(() => {
                const wrapper = input.closest('.iti');
                if (!wrapper) return;
                wrapper.querySelectorAll('.iti__country').forEach(c => {
                    const cc = c.getAttribute('data-country-code');
                    if (cc && window.arabicCountryNames[cc]) {
                        const ns = c.querySelector('.iti__country-name');
                        if (ns) ns.textContent = window.arabicCountryNames[cc];
                    }
                });
            }, 100);
        }

        // Initial update
        updateCountryFields();
        customizeDropdown();

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
