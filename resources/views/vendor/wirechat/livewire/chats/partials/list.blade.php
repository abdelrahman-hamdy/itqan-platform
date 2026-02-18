
@use('Wirechat\Wirechat\Facades\Wirechat')

<ul wire:loading.delay.long.remove wire:target="search" class="p-2 grid w-full spacey-y-2">
    @foreach ($conversations as $key=> $conversation)
    @php
    //$receiver =$conversation->getReceiver();
    $group = $conversation->isGroup() ? $conversation->group : null;
    $receiver = $conversation->isGroup() ? null : ($conversation->isPrivate() ? $conversation->peer_participant?->participantable : $this->auth);
    //$receiver = $conversation->isGroup() ? null : ($conversation->isPrivate() ? $conversation->peerParticipant()?->participantable : $this->auth);
    $lastMessage = $conversation->lastMessage;
    //mark isReadByAuth true if user has chat opened
    $isReadByAuth = $conversation?->readBy($conversation->auth_participant??$this->auth) || $selectedConversationId == $conversation->id;
    $belongsToAuth = $lastMessage?->belongsToAuth();

    // Get ChatGroup for entity-type colors if this is a group conversation
    $chatGroup = $conversation->isGroup() ? \App\Models\ChatGroup::where('conversation_id', $conversation->id)->first() : null;
    @endphp

    <li x-data="{
        conversationID: @js($conversation->id),
        showUnreadStatus: @js(!$isReadByAuth),
        handleChatOpened(event) {
            // Hide unread dot
            if (event.detail.conversation== this.conversationID) {
                this.showUnreadStatus= false;
            }
            //update this so that the the selected conversation highlighter can be updated
            $wire.selectedConversationId= event.detail.conversation;
        },
        handleChatClosed(event) {
                // Clear the globally selected conversation.
                $wire.selectedConversationId = null;
                selectedConversationId = null;
        },
        handleOpenChat(event) {
            // Clear the globally selected conversation.
            if (this.showUnreadStatus==  event.detail.conversation== this.conversationID) {
                this.showUnreadStatus= false;
            }
    }
    }"

    id="conversation-{{ $conversation->id }}"
        wire:key="conversation-em-{{ $conversation->id }}-{{ $conversation->updated_at->timestamp }}"
        x-on:chat-opened.window="handleChatOpened($event)"
        x-on:chat-closed.window="handleChatClosed($event)">
        <a @if ($widget) tabindex="0"
        role="button"
        dusk="openChatWidgetButton"
        @click="$dispatch('open-chat',{conversation:@js($conversation->id)})"
        @keydown.enter="$dispatch('open-chat',{conversation:@js($conversation->id)})"
        @else
        wire:navigate href="{{ route(Wirechat::viewRouteName(), $conversation->id) }}" @endif
            class="py-3 flex gap-4 hover:bg-gray-50 dark:hover:bg-gray-800 rounded-lg transition-all duration-200 relative w-full cursor-pointer px-2 group"
            :class="{
                'bg-gray-50 dark:bg-gray-800 border-e-4 border-primary-500 shadow-sm': $wire.selectedConversationId == conversationID,
                'bg-blue-50/60 dark:bg-blue-900/20 border-e-2 border-green-500': showUnreadStatus && $wire.selectedConversationId != conversationID,
            }">

            <div class="shrink-0 relative">
                @if($conversation->isGroup() && $chatGroup)
                    {{-- Entity-type-based group avatar --}}
                    @php $avatarStyle = $chatGroup->getGroupAvatarStyle(); @endphp
                    <div class="w-12 h-12 rounded-full flex items-center justify-center {{ $avatarStyle['bgClass'] }}">
                        <i class="{{ $avatarStyle['icon'] }} {{ $avatarStyle['textClass'] }} text-xl"></i>
                    </div>
                @elseif(!$conversation->isGroup() && $receiver instanceof \App\Models\User)
                    {{-- User avatar for private chats --}}
                    <x-avatar :user="$receiver" size="sm" />
                @else
                    {{-- Default WireChat avatar --}}
                    <x-wirechat::avatar disappearing="{{ $conversation->hasDisappearingTurnedOn() }}"
                        group="{{ $conversation->isGroup() }}"
                        :src="$group ? $group?->cover_url : $receiver?->cover_url ?? null" class="w-12 h-12" />
                @endif
                {{-- Unread indicator circle on avatar --}}
                <div x-show="showUnreadStatus" class="absolute -top-0.5 -end-0.5 w-3.5 h-3.5 bg-green-500 rounded-full border-2 border-white dark:border-gray-900"></div>
            </div>

            <aside class="flex justify-between w-full">
                <div class="relative overflow-hidden truncate leading-5 w-full flex-nowrap p-1">

                    {{-- name --}}
                    <div class="flex gap-1 mb-1 w-full items-center">
                        <h6 class="truncate text-gray-900 dark:text-white"
                        :class="showUnreadStatus ? 'font-bold' : 'font-semibold'">
                            {{ $group ? $group?->name : $receiver?->display_name }}
                        </h6>

                        @if ($conversation->isSelfConversation())
                            <span class="font-medium text-gray-500 dark:text-gray-400">({{__('wirechat::chats.labels.you')  }})</span>
                        @endif

                    </div>

                    {{-- Message body --}}
                    @if ($lastMessage != null)
                        @include('wirechat::livewire.chats.partials.message-body')
                    @endif

                </div>

                <div class="flex flex-col items-end p-1">
                    {{-- Time label --}}
                    @if ($lastMessage != null)
                        <span class="font-medium text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">
                            @if ($lastMessage->created_at->diffInMinutes(now()) < 1)
                                @lang('wirechat::chats.labels.now')
                            @else
                                {{ $lastMessage->created_at->shortAbsoluteDiffForHumans() }}
                            @endif
                        </span>
                    @endif

                    {{-- Read status --}}
                    {{-- Only show if AUTH is NOT onwer of message --}}
                    @if ($lastMessage != null && !$lastMessage?->ownedBy($this->auth) && !$isReadByAuth)
                        <div x-show="showUnreadStatus" dusk="unreadMessagesDot" class="flex flex-col text-center my-auto">
                            {{-- Dots icon --}}
                            <span dusk="unreadDotItem" class="sr-only">unread dot</span>
                            <div class="w-3 h-3 bg-primary-500 rounded-full mx-auto animate-pulse"></div>

                        </div>
                    @endif
                </div>


            </aside>
        </a>

    </li>
    @endforeach

</ul>
