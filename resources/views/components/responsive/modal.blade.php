@props([
    'id' => 'modal',
    'title' => '',
    'size' => 'md',
    'closeOnBackdrop' => true,
    'closeOnEscape' => true,
])

@php
    $sizeClasses = [
        'sm' => 'max-w-sm',
        'md' => 'max-w-lg',
        'lg' => 'max-w-2xl',
        'xl' => 'max-w-4xl',
        'full' => 'max-w-full mx-4',
    ];
@endphp

<div id="{{ $id }}"
     x-data="{ open: false }"
     x-show="open"
     x-cloak
     @open-modal-{{ $id }}.window="open = true"
     @close-modal-{{ $id }}.window="open = false"
     @if($closeOnEscape)
     @keydown.escape.window="open = false"
     @endif
     class="fixed inset-0 z-50 overflow-y-auto"
     aria-labelledby="{{ $id }}-title"
     role="dialog"
     aria-modal="true">

    <!-- Backdrop -->
    <div x-show="open"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @if($closeOnBackdrop)
         @click="open = false"
         @endif
         class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div>

    <!-- Modal Container -->
    <div class="fixed inset-0 flex items-end md:items-center justify-center p-0 md:p-4">
        <!-- Modal Panel -->
        <div x-show="open"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-full md:translate-y-0 md:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 md:scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 md:scale-100"
             x-transition:leave-end="opacity-0 translate-y-full md:translate-y-0 md:scale-95"
             @click.stop
             class="relative bg-white w-full {{ $sizeClasses[$size] ?? $sizeClasses['md'] }}
                    rounded-t-2xl md:rounded-2xl shadow-xl
                    max-h-[90vh] md:max-h-[85vh] overflow-hidden
                    flex flex-col">

            <!-- Header -->
            <div class="flex items-center justify-between p-4 md:p-5 border-b border-gray-100 flex-shrink-0">
                <!-- Mobile drag handle -->
                <div class="md:hidden absolute top-2 left-1/2 -translate-x-1/2 w-10 h-1 rounded-full bg-gray-300"></div>

                <h3 id="{{ $id }}-title" class="text-lg font-bold text-gray-900 mt-2 md:mt-0">{{ $title }}</h3>

                <button @click="open = false"
                        class="p-2 min-h-[44px] min-w-[44px] flex items-center justify-center rounded-lg hover:bg-gray-100 text-gray-500 transition-colors"
                        aria-label="إغلاق">
                    <i class="ri-close-line text-xl"></i>
                </button>
            </div>

            <!-- Body -->
            <div class="p-4 md:p-6 overflow-y-auto flex-1">
                {{ $slot }}
            </div>

            <!-- Footer (optional) -->
            @isset($footer)
                <div class="p-4 md:p-5 border-t border-gray-100 bg-gray-50 flex-shrink-0">
                    {{ $footer }}
                </div>
            @endisset
        </div>
    </div>
</div>

<style>
    [x-cloak] {
        display: none !important;
    }
</style>
