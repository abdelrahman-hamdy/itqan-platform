<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-inbox class="h-5 w-5 text-primary-500" />
                    <span>{{ __('chat.inbox') }}</span>
                </div>
                @if($this->getUnreadCount() > 0)
                    <span class="inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white bg-danger-500 rounded-full">
                        {{ $this->getUnreadCount() }}
                    </span>
                @endif
            </div>
        </x-slot>

        <div class="space-y-3">
            @forelse($this->getRecentConversations() as $conversation)
                <div class="flex items-center gap-3 p-3 rounded-lg {{ $conversation['unread'] ? 'bg-primary-50 dark:bg-primary-900/20 border border-primary-200 dark:border-primary-800' : 'bg-gray-50 dark:bg-gray-800' }}">
                    <div class="flex-shrink-0 w-10 h-10 bg-primary-100 dark:bg-primary-800 rounded-full flex items-center justify-center">
                        <x-heroicon-s-user class="w-5 h-5 text-primary-600 dark:text-primary-400" />
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                            {{ $conversation['name'] ?? __('chat.unknown') }}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                            {{ \Illuminate\Support\Str::limit($conversation['lastMessage'] ?? __('chat.no_messages'), 40) }}
                        </p>
                        @if($conversation['time'])
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">
                                {{ $conversation['time'] }}
                            </p>
                        @endif
                    </div>
                    @if($conversation['unread'])
                        <span class="flex-shrink-0 w-3 h-3 bg-primary-500 rounded-full animate-pulse"></span>
                    @endif
                </div>
            @empty
                <div class="text-center py-6 text-gray-500 dark:text-gray-400">
                    <x-heroicon-o-inbox class="w-10 h-10 mx-auto mb-3 opacity-50" />
                    <p class="text-sm">{{ __('chat.no_conversations') }}</p>
                </div>
            @endforelse
        </div>

        <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
            <a href="{{ $this->getChatUrl() }}"
               target="_blank"
               class="inline-flex items-center justify-center w-full gap-2 px-4 py-2.5 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2">
                <x-heroicon-o-arrow-top-right-on-square class="w-4 h-4" />
                {{ __('chat.open_inbox') }}
            </a>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
