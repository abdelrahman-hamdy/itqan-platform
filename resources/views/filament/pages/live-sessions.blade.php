<x-filament-panels::page>
    {{-- Tab Navigation --}}
    <div class="flex flex-wrap gap-2 mb-4">
        <button
            wire:click="switchTab('quran')"
            @class([
                'inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg transition-colors',
                'bg-primary-600 text-white shadow-sm' => $activeTab === 'quran',
                'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700' => $activeTab !== 'quran',
            ])
        >
            <x-heroicon-o-book-open class="w-4 h-4" />
            جلسات القرآن
            <span @class([
                'inline-flex items-center justify-center px-2 py-0.5 text-xs font-bold rounded-full',
                'bg-white/20 text-white' => $activeTab === 'quran',
                'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100' => $activeTab !== 'quran',
            ])>
                {{ $this->getQuranCount() }}
            </span>
        </button>

        <button
            wire:click="switchTab('academic')"
            @class([
                'inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg transition-colors',
                'bg-primary-600 text-white shadow-sm' => $activeTab === 'academic',
                'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700' => $activeTab !== 'academic',
            ])
        >
            <x-heroicon-o-academic-cap class="w-4 h-4" />
            جلسات أكاديمية
            <span @class([
                'inline-flex items-center justify-center px-2 py-0.5 text-xs font-bold rounded-full',
                'bg-white/20 text-white' => $activeTab === 'academic',
                'bg-orange-100 text-orange-800 dark:bg-orange-800 dark:text-orange-100' => $activeTab !== 'academic',
            ])>
                {{ $this->getAcademicCount() }}
            </span>
        </button>

        <button
            wire:click="switchTab('interactive')"
            @class([
                'inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg transition-colors',
                'bg-primary-600 text-white shadow-sm' => $activeTab === 'interactive',
                'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700' => $activeTab !== 'interactive',
            ])
        >
            <x-heroicon-o-video-camera class="w-4 h-4" />
            جلسات الدورات
            <span @class([
                'inline-flex items-center justify-center px-2 py-0.5 text-xs font-bold rounded-full',
                'bg-white/20 text-white' => $activeTab === 'interactive',
                'bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100' => $activeTab !== 'interactive',
            ])>
                {{ $this->getInteractiveCount() }}
            </span>
        </button>
    </div>

    {{-- Table --}}
    {{ $this->table }}
</x-filament-panels::page>
