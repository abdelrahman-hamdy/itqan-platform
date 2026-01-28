{{-- Single source of truth for phone input country name overrides.
     Generated from App\Helpers\CountryList — the unified country list
     shared with nationality dropdowns and all other country selectors.
     Included by app-head.blade.php and phone-input.blade.php.
     Uses server-side locale detection — no async JS issues. --}}
<script>
if (!window.phoneCountryNames) {
    window.phoneCountryNames = @json(\App\Helpers\CountryList::toPhoneCountryNames());
}
</script>
