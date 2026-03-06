
@use('Wirechat\Wirechat\Facades\Wirechat')

<header class="px-3 z-10 sticky top-0 w-full py-2 bg-white dark:bg-gray-900" dusk="header">


    {{-- Title/name and Icon --}}
    <section class=" justify-between flex items-center   pb-2">

        @if (isset($heading))
            <div class="flex items-center gap-2 truncate  " wire:ignore>
                <h2 class=" text-2xl font-bold dark:text-white"  dusk="heading">{{$heading}}</h2>
            </div>
        @endif



        <div class="flex gap-x-2 items-center">
            @if ($redirectToHomeAction)
                @php
                    $userType = auth()->user()?->user_type;
                    $profileRoute = match($userType) {
                        'student' => route('student.profile'),
                        'teacher' => route('teacher.profile'),
                        'parent' => route('parent.dashboard'),
                        default => config('wirechat.home_route', '/'),
                    };
                @endphp
                @if(in_array($userType, ['student', 'teacher']))
                    <a href="{{ $profileRoute }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                        <i class="ri-user-line text-base"></i>
                        {{ __('wirechat::chats.profile_button') }}
                    </a>
                @else
                    <a href="{{ $profileRoute }}" class="flex items-center transition-colors duration-200">
                        <i class="ri-home-4-line text-2xl text-gray-500 hover:text-gray-900 transition-colors"></i>
                    </a>
                @endif
            @endif
        </div>



    </section>

    {{-- Search input --}}
    @if ($chatsSearch)
        <section class="mt-4">
            <div class="px-2 rounded-lg bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 grid grid-cols-12 items-center transition-colors duration-200">

                <label for="chats-search-field" class="col-span-1">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="size-5 w-5 h-5 text-gray-400 dark:text-gray-500">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                    </svg>
                </label>

                <input id="chats-search-field" name="chats_search" maxlength="100" type="search" wire:model.live.debounce='search'
                    placeholder="{{ __('wirechat::chats.inputs.search.placeholder')  }}" autocomplete="off"
                    class=" col-span-11 border-0  bg-inherit text-gray-900 dark:text-white outline-hidden w-full focus:outline-hidden  focus:ring-0 hover:ring-0 placeholder:text-gray-400 dark:placeholder:text-gray-500">

                </div>

        </section>
    @endif

</header>
