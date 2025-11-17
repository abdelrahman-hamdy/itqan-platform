{{-- Modern Chats Page - Clean Design --}}
<div class="h-full flex bg-white shadow-lg rounded-xl overflow-hidden border border-gray-200">
    {{-- Conversations Sidebar --}}
    <div class="w-full md:w-96 lg:w-[28rem] shrink-0 border-l border-gray-200 bg-white">
        <livewire:wirechat.chats />
    </div>

    {{-- Welcome/Empty State - Shows beside the sidebar on desktop --}}
    <main class="hidden md:flex flex-1 items-center justify-center bg-gradient-to-br from-gray-50 to-gray-100">
        <div class="text-center space-y-4 max-w-md px-4">
            {{-- Icon --}}
            <div class="w-24 h-24 mx-auto bg-primary/10 rounded-full flex items-center justify-center">
                <i class="ri-message-3-line text-5xl text-primary"></i>
            </div>

            {{-- Welcome Message --}}
            <h2 class="text-2xl font-bold text-gray-900">
                @lang('wirechat::pages.chat.messages.welcome')
            </h2>
            <p class="text-gray-600 text-lg">
                {{ __('wirechat::pages.chat.messages.select_conversation') }}
            </p>

            {{-- Features List --}}
            <div class="grid grid-cols-2 gap-4 pt-6 text-right">
                <div class="flex items-start space-x-3 space-x-reverse">
                    <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center shrink-0">
                        <i class="ri-check-double-line text-lg text-green-600"></i>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-900">{{ __('wirechat::pages.chat.features.instant_messaging.title') }}</p>
                        <p class="text-xs text-gray-500">{{ __('wirechat::pages.chat.features.instant_messaging.description') }}</p>
                    </div>
                </div>
                <div class="flex items-start space-x-3 space-x-reverse">
                    <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center shrink-0">
                        <i class="ri-file-line text-lg text-blue-600"></i>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-900">{{ __('wirechat::pages.chat.features.file_sharing.title') }}</p>
                        <p class="text-xs text-gray-500">{{ __('wirechat::pages.chat.features.file_sharing.description') }}</p>
                    </div>
                </div>
                <div class="flex items-start space-x-3 space-x-reverse">
                    <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center shrink-0">
                        <i class="ri-group-line text-lg text-purple-600"></i>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-900">{{ __('wirechat::pages.chat.features.group_chats.title') }}</p>
                        <p class="text-xs text-gray-500">{{ __('wirechat::pages.chat.features.group_chats.description') }}</p>
                    </div>
                </div>
                <div class="flex items-start space-x-3 space-x-reverse">
                    <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center shrink-0">
                        <i class="ri-lock-line text-lg text-orange-600"></i>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-900">{{ __('wirechat::pages.chat.features.secure.title') }}</p>
                        <p class="text-xs text-gray-500">{{ __('wirechat::pages.chat.features.secure.description') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>
