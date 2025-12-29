
<main x-data="{
    height: 0,
    previousHeight: 0,
    lastScrollTop: 0,
    showDateLabel: false,
    scrollTimeout: null,
    scrollToBottom() {
        $el.scrollTo({
            top: $el.scrollHeight,
            behavior: 'smooth'
        });
    },
    updateScrollPosition: function() {
        // Calculate the difference in height

        newHeight = $el.scrollHeight;

        heightDifference = newHeight - height;


        $el.scrollTop += heightDifference;
        // Update the previous height to the new height
        height = newHeight;

    }

    }"
        x-init="

        setTimeout(() => {

                requestAnimationFrame(() => {

                    this.height = $el.scrollHeight;
                    $el.scrollTop = this.height;
                });

            }, 300); //! Add delay so height can be update at right time


        "
    @scroll ="
        scrollTop = $el.scrollTop;
        scrollHeight = $el.scrollHeight;
        clientHeight = $el.clientHeight;

        // Detect scroll direction and show/hide date label
        if (scrollTop < lastScrollTop) {
            // Scrolling up
            showDateLabel = true;
            // Clear existing timeout
            if (scrollTimeout) clearTimeout(scrollTimeout);
            // Hide after 2 seconds of no scrolling
            scrollTimeout = setTimeout(() => { showDateLabel = false; }, 2000);
        } else if (scrollTop > lastScrollTop) {
            // Scrolling down
            showDateLabel = false;
            if (scrollTimeout) clearTimeout(scrollTimeout);
        }

        lastScrollTop = scrollTop;

        if((scrollTop<=0) && $wire.canLoadMore){
            $wire.loadMore();
        }
     "
    @update-height.window="
        requestAnimationFrame(() => {
            updateScrollPosition();
          });
        "

        @scroll-bottom.window="
        requestAnimationFrame(() => {
            {{-- overflow-y: hidden; is used to hide the vertical scrollbar initially. --}}
            $el.style.overflowY='hidden';



            {{-- scroll the element down --}}
            $el.scrollTop = $el.scrollHeight;

            {{-- After updating the chat height, overflowY is set back to 'auto',
                which allows the browser to determine whether to display the scrollbar
                based on the content height.  --}}
               $el.style.overflowY='auto';
        });
    "


    x-cloak
     class='flex flex-col h-full  relative gap-2 gap-y-4 p-4 md:p-5 lg:p-8  grow  overscroll-contain overflow-x-hidden w-full my-auto bg-gradient-to-br from-indigo-50 to-blue-50 dark:from-indigo-950 dark:to-blue-950'
    style="contain: content" >



    <div x-cloak wire:loading.delay.class.remove="invisible" wire:target="loadMore" class="invisible transition-all duration-300 ">
        <x-wirechat::loading-spin />
    </div>

    {{-- Define previous message outside the loop --}}
    @php
        $previousMessage = null;
    @endphp

    <!--Message-->
    @if ($loadedMessages)
        {{-- @dd($loadedMessages) --}}
        @foreach ($loadedMessages as $date => $messageGroup)

            {{-- Date with scroll animation --}}
            <div x-show="showDateLabel"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 -translate-y-4"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0"
                x-transition:leave-end="opacity-0 -translate-y-4"
                class="sticky top-0 uppercase p-2 shadow-md px-3 z-50 rounded-xl border border-gray-300 dark:border-gray-600 text-sm flex text-center justify-center bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 w-32 mx-auto font-medium">
                {{ $date }}
            </div>

            @foreach ($messageGroup as $key => $message)
                {{-- @dd($message) --}}
                @php
                    $belongsToAuth = $message->belongsToAuth();
                    $parent = $message->parent ?? null;
                    $attachment = $message->attachment ?? null;
                    $isEmoji = $message->isEmoji();


                    // keep track of previous message
                    // The ($key -1 ) will get the previous message from loaded
                    // messages since $key is directly linked to $message
                    if ($key > 0) {
                        $previousMessage = $messageGroup->get($key - 1);
                    }

                    // Get the next message
                    $nextMessage = $key < $messageGroup->count() - 1 ? $messageGroup->get($key + 1) : null;
                @endphp


                <div class="flex gap-2" wire:key="message-{{ $key }}"  >

                    {{-- Message user Avatar --}}
                    {{-- Hide avatar if message belongs to auth --}}
                    @if (!$belongsToAuth && !$isPrivate)
                        <div @class([
                            'shrink-0 mb-auto  -mb-2',
                            // Hide avatar if the next message is from the same user
                            'invisible' =>
                                $previousMessage &&
                                $message?->sendable?->is($previousMessage?->sendable),
                        ])>
                            <x-wirechat::avatar src="{{ $message->sendable?->cover_url ?? null }}" class="h-8 w-8" />
                        </div>
                    @endif

                    {{-- Full width for messages thread --}}
                    <div class="w-full">
                        <div @class([
                            'flex flex-col gap-y-2',
                            'ml-auto' => $belongsToAuth])>



                            {{-- Show parent/reply message --}}
                            @if ($parent != null)
                                <div @class([
                                    'max-w-fit   flex flex-col gap-y-2',
                                    'ml-auto' => $belongsToAuth,
                                    // 'ml-9 sm:ml-10' => !$belongsToAuth,
                                ])>


                                    @php
                                    $sender = $message?->ownedBy($this->auth)
                                        ? __('wirechat::chat.labels.you')
                                        : ($message->sendable?->display_name ?? __('wirechat::chat.labels.user'));

                                    $receiver = $parent?->ownedBy($this->auth)
                                        ? __('wirechat::chat.labels.you')
                                        : ($parent->sendable?->display_name ?? __('wirechat::chat.labels.user'));
                                    @endphp

                                    <h6 class="text-xs text-gray-500 dark:text-gray-300 px-2">
                                        @if ($parent?->ownedBy($this->auth) && $message?->ownedBy($this->auth))
                                            {{ __('wirechat::chat.labels.you_replied_to_yourself') }}
                                        @elseif ($parent?->ownedBy($this->auth))
                                            {{ __('wirechat::chat.labels.participant_replied_to_you', ['sender' => $sender]) }}
                                        @elseif ($message?->ownedBy($parent->sendable))
                                            {{ __('wirechat::chat.labels.participant_replied_to_themself', ['sender' => $sender]) }}
                                        @else
                                            {{ __('wirechat::chat.labels.participant_replied_other_participant', ['sender' => $sender, 'receiver' => $receiver]) }}
                                        @endif
                                    </h6>



                                    <div @class([
                                        'px-1 border-gray-300 dark:border-gray-600 overflow-hidden ',
                                        ' border-r-4 ml-auto' => $belongsToAuth,
                                        ' border-l-4 mr-auto ' => !$belongsToAuth,
                                    ])>
                                        <p
                                            class=" bg-gray-100 dark:text-white break-all  dark:bg-gray-700 text-gray-900 line-clamp-1 text-sm  rounded-full max-w-fit   px-3 py-1 ">
                                            {{ $parent?->body != '' ? $parent?->body : ($parent->hasAttachment() ?  __('wirechat::chat.labels.attachment') : '') }}
                                        </p>
                                    </div>


                                </div>
                            @endif



                            {{-- Body section --}}
                            <div @class([
                                'flex gap-1 md:gap-4 group transition-transform ',
                                'justify-end' => $belongsToAuth,
                            ])>

                                {{-- Message Actions --}}
                                @if (($isGroup && $conversation->group?->allowsMembersToSendMessages()) || $authParticipant->isAdmin())
                                <div dusk="message_actions" @class([ 'my-auto flex  w-auto  items-center gap-2', 'order-1' => !$belongsToAuth, ])>
                                    {{-- reply button --}}
                                    <button wire:click="setReply('{{ encrypt($message->id) }}')"
                                        class=" invisible  group-hover:visible hover:scale-110 transition-transform">

                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                            fill="currentColor" class="bi bi-reply-fill w-4 h-4 dark:text-white"
                                            viewBox="0 0 16 16">
                                            <path
                                                d="M5.921 11.9 1.353 8.62a.72.72 0 0 1 0-1.238L5.921 4.1A.716.716 0 0 1 7 4.719V6c1.5 0 6 0 7 8-2.5-4.5-7-4-7-4v1.281c0 .56-.606.898-1.079.62z" />
                                        </svg>
                                    </button>
                                    {{-- Dropdown actions button --}}
                                    <x-wirechat::dropdown class="w-40" align="{{ $belongsToAuth ? 'right' : 'left' }}"
                                        width="48">
                                        <x-slot name="trigger">
                                            {{-- Dots --}}
                                            <button class="invisible  group-hover:visible hover:scale-110 transition-transform">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                    fill="currentColor"
                                                    class="bi bi-three-dots h-3 w-3 text-gray-700 dark:text-white"
                                                    viewBox="0 0 16 16">
                                                    <path
                                                        d="M3 9.5a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3m5 0a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3m5 0a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3" />
                                                </svg>
                                            </button>
                                        </x-slot>
                                        <x-slot name="content">

                                            @if ($message->ownedBy($this->auth)|| ($authParticipant->isAdmin() && $isGroup))
                                                <button dusk="delete_message_for_everyone"
                                                    @click="$dispatch('open-confirmation', {
                                                        title: 'حذف للجميع',
                                                        message: '{{ __('wirechat::chat.actions.delete_for_everyone.confirmation_message') }}',
                                                        confirmText: 'حذف',
                                                        cancelText: 'إلغاء',
                                                        isDangerous: true,
                                                        onConfirm: () => $wire.call('deleteForEveryone', '{{ encrypt($message->id) }}')
                                                    })"
                                                    class="w-full text-start">
                                                    <x-wirechat::dropdown-link class="text-red-500">
                                                        @lang('wirechat::chat.actions.delete_for_everyone.label')
                                                    </x-wirechat::dropdown-link>
                                                </button>
                                            @endif


                                            {{-- Dont show delete for me if is group --}}
                                            @if (!$isGroup)
                                            <button dusk="delete_message_for_me"
                                                @click="$dispatch('open-confirmation', {
                                                    title: 'حذف من عندي',
                                                    message: '{{ __('wirechat::chat.actions.delete_for_me.confirmation_message') }}',
                                                    confirmText: 'حذف',
                                                    cancelText: 'إلغاء',
                                                    isDangerous: true,
                                                    onConfirm: () => $wire.call('deleteForMe', '{{ encrypt($message->id) }}')
                                                })"
                                                class="w-full text-start">
                                                <x-wirechat::dropdown-link>
                                                    @lang('wirechat::chat.actions.delete_for_me.label')
                                                </x-wirechat::dropdown-link>
                                            </button>
                                            @endif


                                            <button dusk="reply_to_message_button" wire:click="setReply('{{ encrypt($message->id) }}')"class="w-full text-start">
                                                <x-wirechat::dropdown-link>
                                                    @lang('wirechat::chat.actions.reply.label')
                                                </x-wirechat::dropdown-link>
                                            </button>


                                        </x-slot>
                                    </x-wirechat::dropdown>

                                </div>
                                @endif


                                {{-- Message body --}}
                                <div class="flex flex-col gap-2 max-w-[95%]  relative">
                                    {{-- Show sender name is message does not belong to auth and conversation is group --}}


                                    {{-- -------------------- --}}
                                    {{-- Attachment section --}}
                                    {{-- -------------------- --}}
                                    @if ($attachment)
                                        @if (!$belongsToAuth && $isGroup)
                                            <div style="color:  var(--wc-brand-primary);" @class([
                                                'shrink-0 font-medium text-sm sm:text-base',
                                                // Hide avatar if the next message is from the same user
                                                'hidden' => $message?->sendable?->is($previousMessage?->sendable),
                                            ])>
                                                {{ $message->sendable?->display_name }}
                                            </div>
                                        @endif
                                        {{-- Attachemnt is Application/ --}}
                                        @if (str()->startsWith($attachment->mime_type, 'application/'))
                                            @include('wirechat::livewire.chat.partials.file', [ 'attachment' => $attachment ])
                                        @endif

                                        {{-- Attachemnt is Video/ --}}
                                        @if (str()->startsWith($attachment->mime_type, 'video/'))
                                            <x-wirechat::video height="max-h-[400px]" :cover="false" source="{{ $attachment?->url }}" />
                                        @endif

                                        {{-- Attachemnt is image/ --}}
                                        @if (str()->startsWith($attachment->mime_type, 'image/'))
                                            @include('wirechat::livewire.chat.partials.image', [ 'previousMessage' => $previousMessage, 'message' => $message, 'nextMessage' => $nextMessage, 'belongsToAuth' => $belongsToAuth, 'attachment' => $attachment ])
                                        @endif
                                    @endif

                                    {{-- if message is emoji then don't show the styled messagebody layout --}}
                                    @if ($isEmoji)
                                        <p class="text-5xl dark:text-white ">
                                            {{ $message->body }}
                                        </p>
                                    @endif

                                    {{-- -------------------- --}}
                                    {{-- Message body section --}}
                                    {{-- If message is not emoji then show the message body styles --}}
                                    {{-- -------------------- --}}

                                    @if ($message->body && !$isEmoji)
                                    @include('wirechat::livewire.chat.partials.message', [ 'previousMessage' => $previousMessage, 'message' => $message, 'nextMessage' => $nextMessage, 'belongsToAuth' => $belongsToAuth, 'isGroup' => $isGroup, 'attachment' => $attachment])
                                    @endif

                                </div>

                            </div>
                        </div>
                    </div>

                </div>
            @endforeach
        @endforeach


    @endif

</main>
