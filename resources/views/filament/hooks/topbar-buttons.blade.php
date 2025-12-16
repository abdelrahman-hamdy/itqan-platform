@php
    $user = auth()->user();
    $isSuperAdmin = $user?->isSuperAdmin();
    $isAdmin = $user?->isAdmin();
    $currentAcademy = \App\Services\AcademyContextService::getCurrentAcademy();
@endphp

@if($user && ($isSuperAdmin || $isAdmin))
    <div class="flex items-center gap-2 ms-2">
        @if($isSuperAdmin)
            <a
                href="{{ url('/') }}"
                target="_blank"
                class="inline-flex items-center justify-center gap-1 font-semibold rounded-lg px-3 py-1.5 text-sm bg-amber-600 text-white shadow-sm hover:bg-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:bg-amber-500 dark:hover:bg-amber-400 transition-colors"
            >
                <x-heroicon-m-globe-alt class="w-4 h-4 rtl:ml-1 ltr:mr-1" />
                <span>المنصة</span>
            </a>
        @endif

        @if($currentAcademy)
            <a
                href="{{ $currentAcademy->full_url }}"
                target="_blank"
                class="inline-flex items-center justify-center gap-1 font-semibold rounded-lg px-3 py-1.5 text-sm bg-emerald-600 text-white shadow-sm hover:bg-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 dark:bg-emerald-500 dark:hover:bg-emerald-400 transition-colors"
            >
                <x-heroicon-m-building-library class="w-4 h-4 rtl:ml-1 ltr:mr-1" />
                <span>{{ Str::limit($currentAcademy->name, 15) }}</span>
            </a>
        @endif
    </div>
@endif
