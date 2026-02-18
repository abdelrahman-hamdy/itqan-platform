@php
    $user = auth()->user();
    $isSuperAdmin = $user?->isSuperAdmin();
    $isAdmin = $user?->isAdmin();
    $currentAcademy = \App\Services\AcademyContextService::getCurrentAcademy();

    // Dynamic URLs
    $baseDomain = 'itqanway.com';
    $academyUrl = $currentAcademy ? 'https://' . $currentAcademy->subdomain . '.' . $baseDomain : '#';
@endphp

@if($user && ($isSuperAdmin || $isAdmin))
    <div class="flex items-center gap-2 ms-2">
        {{-- Academy Button - Green (dynamic based on loaded academy) --}}
        @if($currentAcademy)
            <a
                href="{{ $academyUrl }}"
                target="_blank"
                class="inline-flex items-center justify-center gap-1 font-semibold rounded-lg px-3 py-1.5 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 transition-colors"
                style="background-color: #059669; color: white;"
                onmouseover="this.style.backgroundColor='#047857'"
                onmouseout="this.style.backgroundColor='#059669'"
            >
                <x-heroicon-m-building-library class="w-4 h-4 me-1" />
                <span>{{ Str::limit($currentAcademy->localized_name, 15) }}</span>
            </a>
        @endif
    </div>
@endif
