<div id="info-modal" class="bg-white dark:bg-gray-900 min-h-screen">

    {{-- Header --}}
    <section class="flex gap-4 z-10 items-center p-5 sticky top-0 bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800">
        <button wire:click="$dispatch('closeChatDrawer')" class="focus:outline-hidden cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-800 p-2 rounded-lg transition">
            <svg class="w-6 h-6 text-gray-700 dark:text-gray-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('wirechat::chat.info.heading.label') }}</h3>
    </section>

    {{-- Profile Section --}}
    <header class="py-8">
        <div class="flex flex-col items-center gap-4">
            <a href="{{ $receiver?->profile_url }}">
                @if($receiver instanceof \App\Models\User)
                    <div class="ring-4 ring-gray-100 dark:ring-gray-800 rounded-full">
                        <x-avatar :user="$receiver" size="xl" />
                    </div>
                @else
                    <x-wirechat::avatar :src="$cover_url" class="h-24 w-24 ring-4 ring-gray-100 dark:ring-gray-800" />
                @endif
            </a>

            <a class="text-center" @dusk="receiver_name" href="{{ $receiver?->profile_url }}">
                <h5 class="text-xl font-bold text-gray-900 dark:text-white">{{ $receiver?->display_name }}</h5>
            </a>
        </div>
    </header>

    {{-- Media & Files Section --}}
    <section x-data="{ expanded: false, activeTab: 'media' }" class="mx-4 mb-4 bg-gray-50 dark:bg-gray-800/50 rounded-xl overflow-hidden border border-gray-200 dark:border-gray-700">
        <button @click="expanded = !expanded"
            class="w-full cursor-pointer py-4 px-5 hover:bg-gray-100 dark:hover:bg-gray-800 transition flex justify-between items-center">
            <div class="flex gap-3 items-center">
                <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-blue-600 dark:text-blue-400">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                    </svg>
                </div>
                <span class="font-semibold text-gray-900 dark:text-white">{{ __('wirechat::chat.info.labels.media_and_files') }}</span>
            </div>
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                class="w-5 h-5 text-gray-500 dark:text-gray-400 transition-transform duration-200"
                :class="expanded ? 'rotate-180' : ''">
                <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
            </svg>
        </button>

        <div x-show="expanded"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 -translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 -translate-y-2"
            class="border-t border-gray-200 dark:border-gray-700">

            {{-- Tabs Navigation --}}
            <div class="flex bg-white dark:bg-gray-900 px-5 pt-3">
                <button
                    @click="activeTab = 'media'"
                    class="px-4 pb-3 font-medium text-sm transition-colors relative flex items-center gap-2"
                    :class="activeTab === 'media' ? 'text-blue-600 dark:text-blue-400' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200'">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                    </svg>
                    <span>{{ __('wirechat::chat.info.labels.media') }}</span>
                    @if(isset($mediaAttachments) && $mediaAttachments->count() > 0)
                        <span class="text-xs px-2 py-0.5 rounded-full bg-blue-100 dark:bg-blue-900/50 text-blue-600 dark:text-blue-400 font-semibold">{{ $mediaAttachments->count() }}</span>
                    @endif
                    <div x-show="activeTab === 'media'" class="absolute bottom-0 left-0 right-0 h-0.5 bg-blue-600 dark:bg-blue-400 rounded-t"></div>
                </button>

                <button
                    @click="activeTab = 'files'"
                    class="px-4 pb-3 font-medium text-sm transition-colors relative flex items-center gap-2"
                    :class="activeTab === 'files' ? 'text-blue-600 dark:text-blue-400' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200'">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                    </svg>
                    <span>{{ __('wirechat::chat.info.labels.files') }}</span>
                    @if(isset($fileAttachments) && $fileAttachments->count() > 0)
                        <span class="text-xs px-2 py-0.5 rounded-full bg-blue-100 dark:bg-blue-900/50 text-blue-600 dark:text-blue-400 font-semibold">{{ $fileAttachments->count() }}</span>
                    @endif
                    <div x-show="activeTab === 'files'" class="absolute bottom-0 left-0 right-0 h-0.5 bg-blue-600 dark:bg-blue-400 rounded-t"></div>
                </button>
            </div>

            {{-- Media Tab Content --}}
            <div x-show="activeTab === 'media'" class="p-5 bg-white dark:bg-gray-900">
                @if(isset($mediaAttachments) && $mediaAttachments->count() > 0)
                    <div class="grid grid-cols-3 gap-3">
                        @foreach($mediaAttachments as $media)
                            <button @click="$dispatch('open-lightbox', { url: '{{ $media->url }}', type: '{{ $media->mime_type }}' })" class="group relative aspect-square bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-800 dark:to-gray-700 rounded-xl overflow-hidden shadow-sm hover:shadow-lg transition-all duration-200 hover:scale-105 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                @if(str_starts_with($media->mime_type, 'image/'))
                                    <img src="{{ $media->url }}" alt="{{ $media->original_name }}" class="w-full h-full object-cover" loading="lazy">
                                    <div class="absolute inset-0 bg-black opacity-0 group-hover:opacity-10 transition-opacity duration-200"></div>
                                @elseif(str_starts_with($media->mime_type, 'video/'))
                                    <video class="w-full h-full object-cover" preload="metadata">
                                        <source src="{{ $media->url }}" type="{{ $media->mime_type }}">
                                    </video>
                                    <div class="absolute inset-0 flex items-center justify-center bg-black bg-opacity-30 group-hover:bg-opacity-40 transition">
                                        <div class="bg-white bg-opacity-90 rounded-full p-3 group-hover:scale-110 transition">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24" class="w-8 h-8 text-gray-900">
                                                <path d="M8 5v14l11-7z"/>
                                            </svg>
                                        </div>
                                    </div>
                                @endif
                            </button>
                        @endforeach
                    </div>
                @else
                    <div class="flex flex-col items-center justify-center py-16 text-center">
                        <div class="p-4 bg-gray-100 dark:bg-gray-800 rounded-full mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-12 h-12 text-gray-400 dark:text-gray-600">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                            </svg>
                        </div>
                        <p class="text-sm font-medium text-gray-900 dark:text-white mb-1">{{ __('wirechat::chat.labels.no_media') }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('wirechat::chat.labels.no_media_description') }}</p>
                    </div>
                @endif
            </div>

            {{-- Files Tab Content --}}
            <div x-show="activeTab === 'files'" class="p-5 bg-white dark:bg-gray-900">
                @if(isset($fileAttachments) && $fileAttachments->count() > 0)
                    <div class="space-y-2">
                        @foreach($fileAttachments as $file)
                            <a href="{{ $file->url }}" download="{{ $file->original_name }}"
                                class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition-all duration-200 group border border-transparent hover:border-gray-200 dark:hover:border-gray-600">
                                <div class="w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0 {{ match(strtolower(pathinfo($file->original_name, PATHINFO_EXTENSION))) {
                                    'pdf' => 'bg-red-100 dark:bg-red-900/30',
                                    'doc', 'docx' => 'bg-blue-100 dark:bg-blue-900/30',
                                    'xls', 'xlsx' => 'bg-green-100 dark:bg-green-900/30',
                                    'zip', 'rar' => 'bg-yellow-100 dark:bg-yellow-900/30',
                                    default => 'bg-gray-100 dark:bg-gray-700',
                                } }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 {{ match(strtolower(pathinfo($file->original_name, PATHINFO_EXTENSION))) {
                                        'pdf' => 'text-red-600 dark:text-red-400',
                                        'doc', 'docx' => 'text-blue-600 dark:text-blue-400',
                                        'xls', 'xlsx' => 'text-green-600 dark:text-green-400',
                                        'zip', 'rar' => 'text-yellow-600 dark:text-yellow-400',
                                        default => 'text-gray-600 dark:text-gray-400',
                                    } }}">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white truncate group-hover:text-blue-600 dark:group-hover:text-blue-400 transition">
                                        {{ $file->original_name }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                        {{ strtoupper($file->clean_mime_type) }}
                                    </p>
                                </div>
                                <div class="flex-shrink-0 opacity-0 group-hover:opacity-100 transition">
                                    <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4 text-blue-600 dark:text-blue-400">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                        </svg>
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="flex flex-col items-center justify-center py-16 text-center">
                        <div class="p-4 bg-gray-100 dark:bg-gray-800 rounded-full mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-12 h-12 text-gray-400 dark:text-gray-600">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m6.75 12-3-3m0 0-3 3m3-3v6m-1.06-15H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                            </svg>
                        </div>
                        <p class="text-sm font-medium text-gray-900 dark:text-white mb-1">{{ __('wirechat::chat.labels.no_files') }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('wirechat::chat.labels.no_files_description') }}</p>
                    </div>
                @endif
            </div>
        </div>
    </section>

    {{-- Divider --}}
    <div class="h-px bg-gray-200 dark:bg-gray-800 mx-4 my-2"></div>

    {{-- Actions Section --}}
    @php
        // Check if this is a supervised individual chat
        $chatGroup = \App\Models\ChatGroup::where('conversation_id', $conversation->id)->first();
        $isSupervisedChat = false;
        $isArchived = false;

        if ($chatGroup) {
            $isSupervisedChat = $chatGroup->isSupervisedChat();
            $isArchived = $chatGroup->isArchived();
        } elseif (!$conversation->isGroup()) {
            // For individual chats without ChatGroup, check if it's student-supervisor
            $participants = $conversation->participants()->with('participantable')->get();
            $userTypes = $participants->map(fn($p) => $p->participantable?->user_type)->filter()->toArray();
            $isSupervisedChat = in_array('student', $userTypes) && in_array('supervisor', $userTypes);
        }
    @endphp

    <section class="px-4 pb-6 space-y-4">
        @if ($isSupervisedChat)
            {{-- Archive/Unarchive button for supervised chats --}}
            @if ($isArchived && $chatGroup)
                <button
                    wire:click="$dispatch('unarchiveChat', { chatGroupId: {{ $chatGroup->id }} })"
                    class="w-full py-3 px-4 rounded-lg bg-emerald-500 hover:bg-emerald-600 transition flex gap-3 items-center justify-center text-white font-medium cursor-pointer">
                    <i class="ri-inbox-unarchive-line text-lg"></i>
                    <span>{{ __('chat.unarchive_chat') }}</span>
                </button>
            @elseif ($chatGroup)
                <button
                    @click="$dispatch('open-confirmation', {
                        title: '{{ __('chat.archive_chat') }}',
                        message: '{{ __('chat.archive_chat_confirmation') }}',
                        confirmText: '{{ __('chat.archive') }}',
                        cancelText: 'إلغاء',
                        isDangerous: false,
                        confirmColor: 'orange',
                        onConfirm: () => $wire.dispatch('archiveChat', { chatGroupId: {{ $chatGroup->id }} })
                    })"
                    class="w-full py-3 px-4 rounded-lg bg-orange-500 hover:bg-orange-600 transition flex gap-3 items-center justify-center text-white font-medium cursor-pointer">
                    <i class="ri-archive-line text-lg"></i>
                    <span>{{ __('chat.archive_chat') }}</span>
                </button>
            @else
                {{-- No ChatGroup yet - archive by conversation ID (will create ChatGroup) --}}
                <button
                    @click="$dispatch('open-confirmation', {
                        title: '{{ __('chat.archive_chat') }}',
                        message: '{{ __('chat.archive_chat_confirmation') }}',
                        confirmText: '{{ __('chat.archive') }}',
                        cancelText: 'إلغاء',
                        isDangerous: false,
                        confirmColor: 'orange',
                        onConfirm: () => $wire.dispatch('archiveChatByConversation', { conversationId: {{ $conversation->id }} })
                    })"
                    class="w-full py-3 px-4 rounded-lg bg-orange-500 hover:bg-orange-600 transition flex gap-3 items-center justify-center text-white font-medium cursor-pointer">
                    <i class="ri-archive-line text-lg"></i>
                    <span>{{ __('chat.archive_chat') }}</span>
                </button>
            @endif

            {{-- Helper text --}}
            <p class="text-gray-500 dark:text-gray-400 text-sm text-center">{{ __('chat.archive_chat_helper') }}</p>

            {{-- Supervised chat notice --}}
            <div class="p-3 rounded-lg bg-blue-50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-800">
                <div class="flex items-center gap-2 text-blue-600 dark:text-blue-400 text-sm">
                    <i class="ri-shield-user-line"></i>
                    <span>{{ __('chat.supervised_chat_notice') }}</span>
                </div>
            </div>
        @else
            {{-- Original delete button for non-supervised chats --}}
            <div class="flex justify-center">
                <button
                    @click="$dispatch('open-confirmation', {
                        title: 'حذف المحادثة',
                        message: '{{ __('wirechat::chat.info.actions.delete_chat.confirmation_message') }}',
                        confirmText: 'حذف',
                        cancelText: 'إلغاء',
                        isDangerous: true,
                        onConfirm: () => $wire.call('deleteChat')
                    })"
                    class="inline-flex items-center gap-2 px-6 py-3 bg-red-50 dark:bg-red-900/20 hover:bg-red-100 dark:hover:bg-red-900/30 text-red-600 dark:text-red-400 rounded-xl transition-all duration-200 font-semibold border border-red-200 dark:border-red-800 hover:border-red-300 dark:hover:border-red-700 cursor-pointer">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                    </svg>
                    <span>{{ __('wirechat::chat.info.actions.delete_chat.label') }}</span>
                </button>
            </div>
        @endif
    </section>

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
        x-transition:leave-end="opacity-0">

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
