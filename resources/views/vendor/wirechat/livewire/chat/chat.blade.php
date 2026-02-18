{{-- Import helper function to use in chatbox --}}
@use('Wirechat\Wirechat\Helpers\Helper')
@use('Wirechat\Wirechat\Facades\Wirechat')

@php
    $primaryColor = Wirechat::getColor();
@endphp



@assets
    <style>

        emoji-picker {
            width: 100% !important;
            height: 100%;
        }

        /* Emoji picker configuration */
        emoji-picker {
            --background: none !important;
            --border-radius: 12px;
            --input-border-color: rgb(229 229 229);
            --input-padding: 0.45rem;
            --outline-color: none;
            --outline-size: 1px;
            --num-columns: 8;
            /* Mobile-first default */
            --emoji-padding: 0.7rem;
            --emoji-size: 1.5rem;
            /* Smaller size for mobile */
            --border-color: none;
            --indicator-color: #9ca3af;
        }


        @media screen and (min-width: 600px) {
            emoji-picker {
                --num-columns: 10;
                /* Increase columns for larger screens */
                --emoji-size: 1.8rem;
                /* Larger size for desktop */
            }
        }

        @media screen and (min-width: 900px) {
            emoji-picker {
                --num-columns: 16;
                /* Increase columns for larger screens */
                --emoji-size: 1.9rem;
                /* Larger size for desktop */
            }
        }
        /* Dark mode using prefers-color-scheme */
        @media (prefers-color-scheme: dark) {
            emoji-picker {
                --background: none !important;
                --input-border-color: rgb(55 65 81);
                --outline-color: none;
                --outline-size: 1px;
                --border-color: none;
                --input-font-color: white;
                --indicator-color: rgb(75 85 99);
                --button-hover-background: rgb(75 85 99);
            }
        }


        /* Ensure dark mode takes precedence */
        .dark emoji-picker {
            --background: none !important;
            --input-border-color: rgb(55 65 81);
            --outline-color: none;
            --outline-size: 1px;
            --border-color: none;
            --input-font-color: white;
            --indicator-color: rgb(75 85 99);
            --button-hover-background: rgb(75 85 99);
        }
    </style>

@endassets

<div x-data="{
    initializing: true,
    conversationId: @js($conversation->id),
    conversationElement: document.getElementById('conversation'),
    loadEmojiPicker() {
        if (!document.head.querySelector('script[src=\'https://cdn.jsdelivr.net/npm/emoji-picker-element@^1/index.js\']')) {
            let script = document.createElement('script');
            script.type = 'module';
            script.async = true; // Load asynchronously
            script.src = 'https://cdn.jsdelivr.net/npm/emoji-picker-element@^1/index.js';
            document.head.appendChild(script);
        }
    },
    get isWidget() {

        return $wire.widget == true;
    }
}"

 x-init="setTimeout(() => {

    requestAnimationFrame(() => {
        initializing = false;
        $wire.dispatch('focus-input-field');
        loadEmojiPicker();
        {{-- if (isWidget) { --}}
            //NotifyListeners about chat opened
            $wire.dispatch('chat-opened',{conversation:conversationId});
        {{-- } --}}
    });
}, 120);"
    class="w-full transition bg-white dark:bg-gray-900 overflow-hidden h-full relative" style="contain:content">

    <div class=" flex flex-col  grow h-full   relative ">
        {{-- ---------- --}}
        {{-- --Header-- --}}
        {{-- ---------- --}}
        @include('wirechat::livewire.chat.partials.header', [ 'conversation' => $conversation, 'receiver' => $receiver])
        {{-- ---------- --}}
        {{-- -Body----- --}}
        {{-- ---------- --}}
        @include('wirechat::livewire.chat.partials.body', [ 'conversation' => $conversation, 'authParticipant' => $authParticipant, 'loadedMessages' => $loadedMessages, 'isPrivate' => $conversation->isPrivate(), 'isGroup' => $conversation->isGroup(), 'receiver' => $receiver])
        {{-- ---------- --}}
        {{-- -Footer--- --}}
        {{-- ---------- --}}
        @include('wirechat::livewire.chat.partials.footer', [ 'conversation' => $conversation, 'authParticipant' => $authParticipant, 'media' => $media, 'files' => $files, 'replyMessage' => $replyMessage])

    </div>

    <livewire:wirechat.chat.drawer />

    {{-- Lightbox Modal --}}
    <div x-data="{
        show: false,
        url: '',
        type: '',
        init() {
            this.$watch('show', value => {
                if (value) {
                    document.body.style.overflow = 'hidden';
                } else {
                    document.body.style.overflow = '';
                }
            });
        }
    }"
        @open-lightbox.window="show = true; url = $event.detail.url; type = $event.detail.type"
        @keydown.escape.window="show = false"
        x-show="show"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black bg-opacity-90"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="show = false">

        {{-- Close Button --}}
        <button @click="show = false" class="absolute top-4 right-4 z-10 p-2 bg-white dark:bg-gray-800 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 transition">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6 text-gray-700 dark:text-gray-300">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>

        {{-- Content --}}
        <div class="relative max-w-7xl max-h-full" @click.stop>
            <img x-show="type && type.startsWith('image/')" :src="url" class="max-w-full max-h-[90vh] object-contain rounded-lg shadow-2xl" alt="عرض الصورة">
            <video x-show="type && type.startsWith('video/')" :src="url" controls class="max-w-full max-h-[90vh] rounded-lg shadow-2xl"></video>
        </div>
    </div>
</div>
