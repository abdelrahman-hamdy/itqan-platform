@if($profileUrl)
<a href="{{ $profileUrl }}"
   target="_blank"
   rel="noopener noreferrer"
   class="fi-btn inline-flex items-center justify-center gap-1.5 rounded-lg px-3 py-1.5 text-sm font-semibold shadow-sm transition-all duration-200 me-3"
   style="background-color: #2563eb; color: white;"
   onmouseover="this.style.backgroundColor='#1d4ed8'"
   onmouseout="this.style.backgroundColor='#2563eb'">
    <x-heroicon-o-arrow-top-right-on-square class="h-4 w-4 rtl:rotate-[-90deg]"/>
    <span>{{ $label }}</span>
</a>
@endif
