@php
       $authIsAdminInGroup=  $participant?->isAdmin();
       $authIsOwner=  $participant?->isOwner();
       $isGroup=  $conversation?->isGroup();

       // Check if this is a supervised chat group
       $chatGroup = \App\Models\ChatGroup::where('conversation_id', $conversation->id)->first();
       $isSupervisedChat = $chatGroup?->isSupervisedChat() ?? false;
       $chatPermissionService = app(\App\Services\ChatPermissionService::class);

       // Check if auth user is a student (for disabling interactivity)
       $authIsStudent = auth()->user()?->user_type === 'student';

       // Get supervisor user ID if this is a supervised chat
       $supervisorUserId = $chatGroup?->supervisor_id;
    @endphp


<div x-ref="members"
    class="h-[calc(100vh_-_6rem)]  sm:h-[450px] bg-[var(--wc-light-primary)] dark:bg-[var(--wc-dark-primary)] dark:text-white border border-[var(--wc-light-secondary)] dark:border-[var(--wc-dark-secondary)]  overflow-y-auto overflow-x-hidden  ">

    <header class=" sticky top-0 bg-[var(--wc-light-primary)] dark:bg-[var(--wc-dark-primary)] z-10 p-2">
        <div class="flex items-center justify-center pb-2">

            <x-wirechat::actions.close-modal>
            <button  dusk="close_modal_button"
                class="p-2 ml-0 text-gray-600 hover:bg-[var(--wc-light-secondary)] dark:hover:bg-[var(--wc-dark-secondary)] dark:hover:text-white rounded-full hover:text-gray-800 ">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class=" w-5 w-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                </svg>

            </button>
            </x-wirechat::actions.close-modal>

            <h3 class=" mx-auto font-semibold ">{{__('wirechat::chat.group.members.heading.label')}} </h3>



        </div>

        {{-- Member limit error --}}
        <section class="flex flex-wrap items-center px-0 border-b dark:border-[var(--wc-dark-secondary)]">
            <input type="search" id="users-search-field" wire:model.live.debounce='search' autocomplete="off"
                placeholder="{{__('wirechat::chat.group.members.inputs.search.placeholder')}}"
                class=" w-full border-0 w-auto dark:bg-[var(--wc-dark-primary)] outline-hidden focus:outline-hidden bg-[var(--wc-dark-parimary)] rounded-lg focus:ring-0 hover:ring-0">
        </section>

    </header>


    <div class="relative w-full p-2 ">
        {{-- <h5 class="text font-semibold text-gray-800 dark:text-gray-100">Recent Chats</h5> --}}
        <section class="my-4 grid">
            @if (count($participants)!=0)

                <ul class="overflow-auto flex flex-col">

                    @foreach ($participants as $key => $participant)
                        @php
                            $loopParticipantIsAuth =
                                $participant->participantable_id == auth()->id() &&
                                $participant->participantable_type == auth()->user()->getMorphClass();

                            // Check if this participant is the supervisor
                            $isThisSupervisor = $supervisorUserId && $participant->participantable_id == $supervisorUserId;
                        @endphp
                        <li wire:key="users-{{ $key }}"
                            class="flex gap-2 items-center overflow-x-hidden p-2 py-3">

                            <div class="flex gap-3 items-center w-full">
                                @if($participant->participantable instanceof \App\Models\User)
                                    <x-avatar :user="$participant->participantable" size="md" />
                                @else
                                    <x-wirechat::avatar src="{{ $participant->participantable->cover_url }}"
                                        class="w-10 h-10" />
                                @endif

                                <div class="flex items-center w-full min-w-0 gap-2">
                                    <h6 class="truncate flex-1">
                                        {{ $loopParticipantIsAuth ? __('chat.you') : $participant->participantable->display_name }}</h6>

                                    {{-- Supervisor badge (highest priority) --}}
                                    @if ($isThisSupervisor)
                                        <span class="flex items-center bg-purple-100 text-purple-700 dark:bg-purple-900 dark:text-purple-300 text-xs font-medium px-2 py-0.5 rounded-full shrink-0">
                                            {{ __('chat.role_supervisor') }}
                                        </span>
                                    {{-- Owner badge --}}
                                    @elseif ($participant->isOwner())
                                        <span class="flex items-center bg-emerald-100 text-emerald-700 dark:bg-emerald-900 dark:text-emerald-300 text-xs font-medium px-2 py-0.5 rounded-full shrink-0">
                                            {{ __('chat.role_owner') }}
                                        </span>
                                    {{-- Admin badge --}}
                                    @elseif ($participant->isAdmin())
                                        <span class="flex items-center bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300 text-xs font-medium px-2 py-0.5 rounded-full shrink-0">
                                            {{ __('chat.role_admin') }}
                                        </span>
                                    @endif

                                </div>
                            </div>

                        </li>
                    @endforeach



                </ul>


                {{-- Load more button --}}
                @if ($canLoadMore)
                    <section class="w-full justify-center flex my-3">
                        <button dusk="loadMoreButton" @click="$wire.loadMore()"
                            class=" text-sm dark:text-white hover:text-gray-700 transition-colors dark:hover:text-gray-500 dark:gray-200">
                            {{__('wirechat::chat.group.members.actions.load_more.label')}}
                        </button>
                    </section>
                @endif

            @else

            <span class="m-auto">{{__('wirechat::chat.group.members.labels.no_members_found')}}</span>
            @endif

        </section>
    </div>

</div>
