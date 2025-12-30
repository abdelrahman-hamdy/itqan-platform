<div class="p-6 text-center">
    <div class="mx-auto w-16 h-16 flex items-center justify-center rounded-full bg-gray-100 dark:bg-gray-800 mb-4">
        <x-heroicon-o-exclamation-circle class="w-8 h-8 text-gray-400 dark:text-gray-500" />
    </div>
    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
        {{ __('calendar.session.not_found') ?: 'لم يتم العثور على الجلسة' }}
    </h3>
    <p class="text-sm text-gray-500 dark:text-gray-400">
        {{ __('calendar.session.not_found_description') ?: 'قد تكون الجلسة محذوفة أو غير متاحة.' }}
    </p>
</div>
