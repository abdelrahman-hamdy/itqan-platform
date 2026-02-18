@use('Wirechat\Wirechat\Facades\Wirechat')

@php
    $group = $conversation->group;
    // Get our ChatGroup if linked
    $chatGroup = \App\Models\ChatGroup::where('conversation_id', $conversation->id)->first();
    $participantCount = $conversation->participants()->count();

    // Detect if this is a supervised chat (with or without ChatGroup)
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

<header
    class="w-full  sticky inset-x-0 flex pb-[5px] pt-[7px] top-0 z-10 bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700   border-b shadow-sm">

    <div class="  flex  w-full items-center   px-2 py-2   lg:px-4 gap-2 md:gap-5 ">

        {{-- Return --}}
        <a @if ($this->isWidget()) @click="$dispatch('close-chat',{conversation: @js($conversation->id) })"
            dusk="return_to_home_button_dispatch"
        @else
            href="{{ route(Wirechat::indexRouteName(), $conversation->id) }}"
            dusk="return_to_home_button_link" @endif
            @class([
                'shrink-0  cursor-pointer dark:text-white',
                'lg:hidden' => !$this->isWidget(),
            ]) id="chatReturn">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6"
                stroke="currentColor" class="w-6 h-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
            </svg>
        </a>

        {{-- Receiver wirechat::Avatar --}}
        <section class="grid grid-cols-12 w-full">
            <div class="shrink-0 col-span-11 w-full truncate overflow-h-hidden relative">

                {{-- Group --}}
                @if ($conversation->isGroup())
                    <x-wirechat::actions.show-group-info conversation="{{ $conversation->id }}"
                        widget="{{ $this->isWidget() }}">
                        <div class="flex items-center gap-2 cursor-pointer ">
                            @if($chatGroup)
                                @php $avatarStyle = $chatGroup->getGroupAvatarStyle(); @endphp
                                <div class="h-8 w-8 lg:w-10 lg:h-10 rounded-full flex items-center justify-center shrink-0 {{ $avatarStyle['bgClass'] }}">
                                    <i class="{{ $avatarStyle['icon'] }} {{ $avatarStyle['textClass'] }} text-lg lg:text-xl"></i>
                                </div>
                            @else
                                <x-wirechat::avatar disappearing="{{ $conversation->hasDisappearingTurnedOn() }}"
                                    :group="true" :src="$group?->cover_url ?? null "
                                    class="h-8 w-8 lg:w-10 lg:h-10 " />
                            @endif
                            <div class="flex items-center gap-2 min-w-0">
                                <h6 class="font-bold text-base text-gray-800 dark:text-white truncate">
                                    {{ $group?->name }}
                                </h6>
                                <span class="text-xs text-gray-500 bg-gray-100 dark:bg-gray-700 dark:text-gray-400 px-2 py-0.5 rounded-full shrink-0">
                                    {{ $participantCount }} {{ __('chat.members') }}
                                </span>
                            </div>
                        </div>
                    </x-wirechat::actions.show-group-info>
                @else
                    {{-- Not Group --}}
                    <x-wirechat::actions.show-chat-info conversation="{{ $conversation->id }}"
                        widget="{{ $this->isWidget() }}">
                        <div class="flex items-center gap-2 cursor-pointer ">
                            @if($receiver instanceof \App\Models\User)
                                <x-avatar :user="$receiver" size="sm" />
                            @else
                                <x-wirechat::avatar disappearing="{{ $conversation->hasDisappearingTurnedOn() }}"
                                    :group="false" :src="$receiver?->cover_url ?? null"
                                    class="h-8 w-8 lg:w-10 lg:h-10 " />
                            @endif
                            <h6 class="font-bold text-base text-gray-800 dark:text-white w-full truncate">
                                {{ $receiver?->display_name }} @if ($conversation->isSelfConversation())
                                    ({{ __('wirechat::chat.labels.you') }})
                                @endif
                            </h6>
                        </div>
                    </x-wirechat::actions.show-chat-info>
                @endif


            </div>

            {{-- Header Actions --}}
            <div class="flex gap-2 items-center ms-auto col-span-1">
                <x-wirechat::dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="cursor-pointer inline-flex px-0 me-2 text-gray-700 dark:text-gray-400">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.9" stroke="currentColor" class="size-6 w-7 h-7">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 6.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5ZM12 12.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5ZM12 18.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5Z" />
                            </svg>

                        </button>
                    </x-slot>
                    <x-slot name="content">


                        @if ($conversation->isGroup())
                            {{-- Open group info button --}}
                            <x-wirechat::actions.show-group-info conversation="{{ $conversation->id }}"
                                widget="{{ $this->isWidget() }}">
                                <button class="w-full text-start">
                                    <x-wirechat::dropdown-link>
                                        {{ __('wirechat::chat.actions.open_group_info.label') }}
                                    </x-wirechat::dropdown-link>
                                </button>
                            </x-wirechat::actions.show-group-info>
                        @else
                            {{-- Open chat info button --}}
                            <x-wirechat::actions.show-chat-info conversation="{{ $conversation->id }}"
                                widget="{{ $this->isWidget() }}">
                                <button class="w-full text-start">
                                    <x-wirechat::dropdown-link>
                                        {{ __('wirechat::chat.actions.open_chat_info.label') }}
                                    </x-wirechat::dropdown-link>
                                </button>
                            </x-wirechat::actions.show-chat-info>
                        @endif


                        @if ($this->isWidget())
                            <x-wirechat::dropdown-link @click="$dispatch('close-chat',{conversation: @js($conversation->id) })">
                                @lang('wirechat::chat.actions.close_chat.label')
                            </x-wirechat::dropdown-link>
                        @else
                            <x-wirechat::dropdown-link href="{{ route(Wirechat::indexRouteName()) }}" class="shrink-0">
                                @lang('wirechat::chat.actions.close_chat.label')
                            </x-wirechat::dropdown-link>
                        @endif


                        {{-- Clear conversation content - for all chats --}}
                        <button class="w-full"
                            @click="$dispatch('open-confirmation', {
                                title: 'مسح سجل المحادثة',
                                message: '{{ __('wirechat::chat.actions.clear_chat.confirmation_message') }}',
                                confirmText: 'مسح',
                                cancelText: 'إلغاء',
                                isDangerous: true,
                                onConfirm: () => $wire.call('clearConversation')
                            })">

                            <x-wirechat::dropdown-link class="text-red-500 dark:text-red-400">
                                <span class="flex items-center gap-2">
                                    <i class="ri-delete-bin-line"></i>
                                    @lang('wirechat::chat.actions.clear_chat.label')
                                </span>
                            </x-wirechat::dropdown-link>
                        </button>

                        {{-- Archive chat for supervised chats (both group and individual) --}}
                        @if ($isSupervisedChat)
                            @if ($isArchived && $chatGroup)
                                <button
                                    wire:click="$dispatch('unarchiveChat', { chatGroupId: {{ $chatGroup->id }} })"
                                    class="w-full text-start">
                                    <x-wirechat::dropdown-link>
                                        <span class="flex items-center gap-2">
                                            <i class="ri-inbox-unarchive-line"></i>
                                            {{ __('chat.unarchive_chat') }}
                                        </span>
                                    </x-wirechat::dropdown-link>
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
                                    class="w-full text-start">
                                    <x-wirechat::dropdown-link class="text-orange-500 dark:text-orange-400">
                                        <span class="flex items-center gap-2">
                                            <i class="ri-archive-line"></i>
                                            {{ __('chat.archive_chat') }}
                                        </span>
                                    </x-wirechat::dropdown-link>
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
                                    class="w-full text-start">
                                    <x-wirechat::dropdown-link class="text-orange-500 dark:text-orange-400">
                                        <span class="flex items-center gap-2">
                                            <i class="ri-archive-line"></i>
                                            {{ __('chat.archive_chat') }}
                                        </span>
                                    </x-wirechat::dropdown-link>
                                </button>
                            @endif
                        @endif

                    </x-slot>
                </x-wirechat::dropdown>

            </div>
        </section>


    </div>

</header>
