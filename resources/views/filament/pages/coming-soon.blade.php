<x-filament-panels::page>
    <div class="flex items-center justify-center min-h-[60vh]">
        <div class="max-w-2xl w-full">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8 text-center border border-gray-200 dark:border-gray-700">
                <!-- Icon -->
                <div class="mx-auto w-20 h-20 bg-primary-100 dark:bg-primary-900/30 rounded-full flex items-center justify-center mb-6">
                    @svg($icon ?? 'heroicon-o-rocket-launch', 'w-10 h-10 text-primary-600 dark:text-primary-400')
                </div>

                <!-- Title -->
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                    {{ $title ?? 'قريباً' }}
                </h2>

                <!-- Coming Soon Badge -->
                <div class="inline-flex items-center gap-2 bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-200 px-4 py-2 rounded-full text-sm font-medium mb-6">
                    @svg('heroicon-o-clock', 'w-4 h-4')
                    <span>قيد التطوير</span>
                </div>

                <!-- Description -->
                <p class="text-gray-600 dark:text-gray-400 mb-6 text-lg">
                    {{ $description ?? 'نعمل على تطوير هذه الميزة وستكون متاحة قريباً.' }}
                </p>

                <!-- Features List -->
                @if(!empty($features))
                <div class="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-6 text-start">
                    <ul class="space-y-3">
                        @foreach($features as $feature)
                        <li class="flex items-center gap-3 text-gray-700 dark:text-gray-300">
                            @svg('heroicon-o-check-circle', 'w-5 h-5 text-green-500 flex-shrink-0')
                            <span>{{ $feature }}</span>
                        </li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <!-- Footer Note -->
                <p class="mt-6 text-sm text-gray-500 dark:text-gray-400">
                    سيتم إشعارك عند إطلاق هذه الميزة
                </p>
            </div>
        </div>
    </div>
</x-filament-panels::page>
