@php
    use Filament\Facades\Filament;
    use Illuminate\Support\Facades\Storage;

    // Get current tenant (academy) - may be null for non-tenant panels
    $tenant = Filament::getTenant();

    // Panel color mapping to Tailwind colors
    $colorMap = [
        'green' => ['bg' => 'bg-green-100 dark:bg-green-900/30', 'text' => 'text-green-600 dark:text-green-400'],
        'blue' => ['bg' => 'bg-blue-100 dark:bg-blue-900/30', 'text' => 'text-blue-600 dark:text-blue-400'],
        'purple' => ['bg' => 'bg-purple-100 dark:bg-purple-900/30', 'text' => 'text-purple-600 dark:text-purple-400'],
        'amber' => ['bg' => 'bg-amber-100 dark:bg-amber-900/30', 'text' => 'text-amber-600 dark:text-amber-400'],
        'sky' => ['bg' => 'bg-sky-100 dark:bg-sky-900/30', 'text' => 'text-sky-600 dark:text-sky-400'],
    ];

    $panelColor = $panelColor ?? 'sky';
    $colors = $colorMap[$panelColor] ?? $colorMap['sky'];

    // Determine brand name based on panel type
    $panelType = $panelType ?? 'default';
    $brandName = match($panelType) {
        'admin' => 'منصة إتقان - لوحة التحكم',
        'supervisor' => 'لوحة المشرف',
        default => $tenant?->name ?? 'أكاديمية إتقان',
    };

    // Determine logo URL - only for tenant-based panels with actual logos
    $logoUrl = null;
    if ($tenant) {
        if ($tenant->logo_url) {
            $logoUrl = $tenant->logo_url;
        } elseif ($tenant->logo) {
            $logoUrl = Storage::url($tenant->logo);
        }
    }
@endphp

<div class="flex items-center gap-3">
    @if($logoUrl)
        <img src="{{ $logoUrl }}"
             alt="{{ $brandName }}"
             class="h-10 w-auto max-w-[180px] object-contain">
    @else
        <div class="w-10 h-10 flex items-center justify-center rounded-lg {{ $colors['bg'] }}">
            <x-heroicon-s-academic-cap class="w-6 h-6 {{ $colors['text'] }}" />
        </div>
        <span class="text-lg font-bold text-gray-900 dark:text-white hidden sm:block">{{ $brandName }}</span>
    @endif
</div>
