{{--
    Mushaf Overlay Panel — full-screen meeting overlay hosting the page
    viewer. Hidden by default; window.mushaf.open() / m_open packet from
    the teacher un-hides. Mirrors the mobile MushafReaderScreen layout.

    Per-page KFGQPC QPC v4 fonts are loaded lazily via FontFace API from
    /mushaf/fonts/{page}.woff2 (see MushafFontController).
--}}

@props([
    'canShare' => false,
])

<div id="mushafOverlay"
     class="fixed inset-0 z-[60] hidden bg-stone-100 flex flex-col"
     dir="rtl">
    {{-- Header --}}
    <div class="shrink-0 bg-white border-b border-gray-200 px-4 py-3 flex items-center justify-between gap-3">
        <div class="flex items-center gap-2 min-w-0">
            <i class="ri-book-open-line text-xl text-gray-700"></i>
            <h2 class="text-base font-semibold text-gray-800 truncate">{{ __('mushaf.mushaf') }}</h2>
            <span class="text-sm text-gray-500 ms-2">
                {{ __('mushaf.page_label') }}
                <span id="mushafPageLabel" class="font-mono">1</span>
            </span>
        </div>
        <div class="flex items-center gap-2">
            @if($canShare)
            <button id="mushafShareToggle"
                    aria-label="{{ __('mushaf.share') }}"
                    class="px-3 py-2 rounded-lg bg-gray-700 hover:bg-gray-600 text-white text-sm flex items-center gap-2 transition">
                <i class="ri-share-line"></i>
                <span>{{ __('mushaf.share') }}</span>
            </button>
            @endif
            <button id="mushafClose"
                    aria-label="{{ __('mushaf.close') }}"
                    class="w-10 h-10 rounded-full bg-gray-200 hover:bg-gray-300 text-gray-800 flex items-center justify-center transition">
                <i class="ri-close-line text-lg"></i>
            </button>
        </div>
    </div>

    {{-- Page viewer: page card centered with prev/next chevrons. The
         glyph string is set in JS using the per-page QPC font. --}}
    <div class="flex-1 min-h-0 overflow-auto flex items-stretch justify-center px-2 py-4 gap-2">
        {{-- Previous (RTL: visually right) = next page in Mushaf order --}}
        <button id="mushafPrev"
                aria-label="{{ __('mushaf.prev_page') }}"
                class="shrink-0 self-center w-12 h-12 rounded-full bg-white hover:bg-gray-100 text-gray-700 shadow flex items-center justify-center transition">
            <i class="ri-arrow-right-s-line text-2xl"></i>
        </button>

        <div class="relative flex-1 max-w-[820px] bg-white rounded-lg shadow-lg border border-gray-200 overflow-hidden">
            {{-- Page glyph host: text-rendered with per-page QPC font.
                 The wait-for-teacher banner is omitted here — students
                 see the placeholder page-N text until snapshot arrives. --}}
            <div id="mushafPage"
                 class="px-4 py-6 leading-loose text-[32px] sm:text-[40px] text-gray-900 text-center"
                 style="font-family: 'Amiri', serif;"></div>

            {{-- Absolute-positioned overlay layer for ayah highlight --}}
            <div id="mushafHighlightLayer"
                 class="absolute inset-0 pointer-events-none"></div>
        </div>

        {{-- Next (RTL: visually left) = previous page in Mushaf order --}}
        <button id="mushafNext"
                aria-label="{{ __('mushaf.next_page') }}"
                class="shrink-0 self-center w-12 h-12 rounded-full bg-white hover:bg-gray-100 text-gray-700 shadow flex items-center justify-center transition">
            <i class="ri-arrow-left-s-line text-2xl"></i>
        </button>
    </div>
</div>
