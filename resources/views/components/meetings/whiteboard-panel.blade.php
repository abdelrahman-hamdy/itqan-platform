{{--
    Whiteboard Overlay Panel — full-screen meeting overlay hosting the
    canvas + toolbar. Hidden by default; window.whiteboard.open() un-hides.
    State is fully ephemeral — closing the overlay wipes the canvas.

    The toolbar is hidden for students (read-only mode). The wait banner
    shows while a late-joiner is awaiting the teacher's snapshot reply.
--}}

@props([
    'canControl' => false,
])

<div id="whiteboardOverlay"
     class="fixed inset-0 z-[60] hidden bg-gray-900/95 backdrop-blur-sm flex flex-col"
     dir="ltr">
    {{-- Canvas host: centered, letterboxes to 1080×1440 internally. --}}
    <div class="flex-1 min-h-0 flex items-center justify-center p-4 relative">
        <canvas id="whiteboardCanvas"
                class="block w-full h-full max-w-[1080px] rounded-lg shadow-2xl"
                style="touch-action: none; background: #ffffff;"></canvas>

        {{-- Wait-for-teacher banner (students). --}}
        <div id="whiteboardWaitBanner"
             class="hidden absolute inset-0 flex items-center justify-center pointer-events-none">
            <div class="bg-gray-900/75 text-white text-sm px-4 py-3 rounded-lg shadow-lg backdrop-blur">
                <i class="ri-loader-4-line animate-spin mr-2"></i>
                <span>{{ __('whiteboard.waiting_for_teacher') }}</span>
            </div>
        </div>
    </div>

    @if($canControl)
    {{-- Teacher toolbar — order mirrors mobile whiteboard_toolbar_horizontal.dart:
         [pen] [eraser] | [7 colours] | [5 widths] | [undo][redo][fit][clear] | [close] --}}
    <div id="whiteboardToolbar"
         class="shrink-0 bg-gray-800 border-t border-gray-700 px-3 py-3 flex items-center justify-center gap-2 sm:gap-3 flex-wrap">
        {{-- Tools --}}
        <div class="flex items-center gap-1">
            <button id="wbTool-pen" aria-label="{{ __('whiteboard.pen') }}"
                    class="w-10 h-10 rounded-full bg-gray-700 hover:bg-gray-600 text-white flex items-center justify-center transition">
                <i class="ri-quill-pen-line text-lg"></i>
            </button>
            <button id="wbTool-eraser" aria-label="{{ __('whiteboard.eraser') }}"
                    class="w-10 h-10 rounded-full bg-gray-700 hover:bg-gray-600 text-white flex items-center justify-center transition">
                <i class="ri-eraser-line text-lg"></i>
            </button>
        </div>

        <span class="w-px h-8 bg-gray-600"></span>

        {{-- Colour swatches — append-only enum, indices must match
             WhiteboardColor in mobile whiteboard_stroke.dart. --}}
        <div class="flex items-center gap-1.5">
            @foreach([
                ['#111827', __('whiteboard.color_black')],
                ['#DC2626', __('whiteboard.color_red')],
                ['#2563EB', __('whiteboard.color_blue')],
                ['#059669', __('whiteboard.color_green')],
                ['#EAB308', __('whiteboard.color_yellow')],
                ['#EA580C', __('whiteboard.color_orange')],
                ['#9333EA', __('whiteboard.color_purple')],
            ] as $idx => $entry)
                <button data-wb-color="{{ $idx }}" aria-label="{{ $entry[1] }}"
                        class="w-7 h-7 rounded-full border-2 border-white/30 hover:border-white transition"
                        style="background-color: {{ $entry[0] }};"></button>
            @endforeach
        </div>

        <span class="w-px h-8 bg-gray-600"></span>

        {{-- Widths — indices must match WhiteboardWidth in mobile. --}}
        <div class="flex items-center gap-1">
            @foreach([2, 4, 8, 16, 28] as $idx => $px)
                <button data-wb-width="{{ $idx }}" aria-label="{{ __('whiteboard.width_label') }} {{ $px }}"
                        class="w-9 h-9 rounded-full bg-gray-700 hover:bg-gray-600 text-white flex items-center justify-center transition">
                    <span class="rounded-full bg-white"
                          style="width: {{ min(20, max(2, $px / 1.5)) }}px; height: {{ min(20, max(2, $px / 1.5)) }}px;"></span>
                </button>
            @endforeach
        </div>

        <span class="w-px h-8 bg-gray-600"></span>

        {{-- Actions --}}
        <div class="flex items-center gap-1">
            <button id="wbUndo" aria-label="{{ __('whiteboard.undo') }}"
                    class="w-10 h-10 rounded-full bg-gray-700 hover:bg-gray-600 text-white flex items-center justify-center transition">
                <i class="ri-arrow-go-back-line text-lg"></i>
            </button>
            <button id="wbRedo" aria-label="{{ __('whiteboard.redo') }}"
                    class="w-10 h-10 rounded-full bg-gray-700 hover:bg-gray-600 text-white flex items-center justify-center transition">
                <i class="ri-arrow-go-forward-line text-lg"></i>
            </button>
            <button id="wbFit" aria-label="{{ __('whiteboard.fit') }}"
                    class="w-10 h-10 rounded-full bg-gray-700 hover:bg-gray-600 text-white flex items-center justify-center transition">
                <i class="ri-focus-3-line text-lg"></i>
            </button>
            <button id="wbClear" aria-label="{{ __('whiteboard.clear') }}"
                    class="w-10 h-10 rounded-full bg-red-700 hover:bg-red-600 text-white flex items-center justify-center transition">
                <i class="ri-delete-bin-line text-lg"></i>
            </button>
        </div>

        <span class="w-px h-8 bg-gray-600"></span>

        <button id="wbClose" aria-label="{{ __('whiteboard.close') }}"
                class="w-10 h-10 rounded-full bg-gray-600 hover:bg-gray-500 text-white flex items-center justify-center transition">
            <i class="ri-close-line text-lg"></i>
        </button>
    </div>
    @else
    {{-- Students see a minimal close-only bar (so they can dismiss after the
         teacher closes the board — and to indicate the overlay is live). --}}
    <div class="shrink-0 bg-gray-800 border-t border-gray-700 px-3 py-2 flex items-center justify-end">
        <span class="text-xs text-gray-300 mr-3">{{ __('whiteboard.opened_by_teacher') }}</span>
    </div>
    @endif
</div>
