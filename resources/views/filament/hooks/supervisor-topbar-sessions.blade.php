@php
    $user = auth()->user();
@endphp

@if($user)
    <div class="flex items-center gap-2 ms-2">
        <a
            href="{{ url('/supervisor-panel/monitored-all-sessions') }}"
            class="inline-flex items-center justify-center gap-1 font-semibold rounded-lg px-3 py-1.5 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 transition-colors"
            style="background-color: #7c3aed; color: white;"
            onmouseover="this.style.backgroundColor='#6d28d9'"
            onmouseout="this.style.backgroundColor='#7c3aed'"
            title="مراقبة جميع الجلسات"
        >
            <x-heroicon-m-eye class="w-4 h-4 me-1" />
            <span>الجلسات</span>
        </a>
    </div>
@endif
