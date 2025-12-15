@if($profileUrl)
<a href="{{ $profileUrl }}"
   target="_blank"
   rel="noopener noreferrer"
   class="fi-btn inline-flex items-center justify-center gap-1.5 rounded-lg px-3 py-1.5 text-sm font-semibold shadow-sm transition-all duration-200 bg-primary-600 text-white hover:bg-primary-500 dark:bg-primary-500 dark:hover:bg-primary-400 focus:ring-2 focus:ring-primary-500/50 me-3">
    <x-heroicon-o-arrow-top-right-on-square class="h-4 w-4 rtl:rotate-[-90deg]"/>
    <span>{{ $label }}</span>
</a>
@endif
