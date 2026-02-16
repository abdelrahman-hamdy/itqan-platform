@props(['timezone' => null])

@php
    $tz = $timezone ?? AcademyContextService::getTimezone();
    $label = match($tz) {
        'Asia/Riyadh' => 'توقيت السعودية (GMT+3)',
        'Africa/Cairo' => 'توقيت مصر (GMT+2)',
        'Asia/Dubai' => 'توقيت الإمارات (GMT+4)',
        default => $tz,
    };
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center px-2 py-1 text-xs font-medium rounded-md bg-blue-50 text-blue-700 border border-blue-200']) }}>
    <svg class="w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
    </svg>
    {{ $label }}
</span>
