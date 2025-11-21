@props([
    'icon' => 'heroicon-o-inbox',
    'title' => 'لا توجد عناصر',
    'description' => 'سيتم عرض العناصر هنا',
])

<div class="col-span-full">
    <x-filament::section>
        <div class="text-center py-12">
            <div class="w-16 h-16 mx-auto mb-4 bg-gray-100 rounded-full flex items-center justify-center">
                @svg($icon, 'w-8 h-8 text-gray-400')
            </div>
            <h3 class="mt-2 text-lg font-medium text-gray-900">{{ $title }}</h3>
            <p class="mt-1 text-sm text-gray-500">{{ $description }}</p>
        </div>
    </x-filament::section>
</div>
