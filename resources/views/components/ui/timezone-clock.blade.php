@php
    $timezone = \App\Services\AcademyContextService::getTimezone();
    $country = auth()->user()->academy?->country?->label() ?? __('enums.country.SA');
    $clockId = 'live-clock-' . Str::random(6);
@endphp

<div class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg bg-white text-gray-700 border border-gray-200 shadow-sm">
    <i class="ri-global-line text-blue-500"></i>
    <span>{{ __('student.calendar.all_times_in_country', ['country' => $country]) }}</span>
    <span class="text-gray-300">|</span>
    <i class="ri-time-line text-blue-500"></i>
    <span id="{{ $clockId }}" class="font-semibold text-gray-900 tabular-nums"></span>
</div>

<script>
(function() {
    const tz = @js($timezone);
    const el = document.getElementById(@js($clockId));
    function tick() {
        if (!el) return;
        el.textContent = new Date().toLocaleTimeString('ar-SA', {
            timeZone: tz, hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true
        });
    }
    tick();
    setInterval(tick, 1000);
})();
</script>
