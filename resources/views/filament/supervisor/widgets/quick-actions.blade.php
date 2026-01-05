<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-heroicon-o-bolt class="w-5 h-5 text-primary-500" />
                <span>الإجراءات السريعة</span>
            </div>
        </x-slot>

        @if($hasResponsibilities)
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                @foreach($actions as $action)
                    @php
                        $color = $action['color'] ?? 'gray';
                        $bgClasses = match($color) {
                            'success' => 'bg-success-50 dark:bg-success-900/20 border-success-200 dark:border-success-800 hover:border-success-400 dark:hover:border-success-600',
                            'info' => 'bg-info-50 dark:bg-info-900/20 border-info-200 dark:border-info-800 hover:border-info-400 dark:hover:border-info-600',
                            'warning' => 'bg-warning-50 dark:bg-warning-900/20 border-warning-200 dark:border-warning-800 hover:border-warning-400 dark:hover:border-warning-600',
                            'primary' => 'bg-primary-50 dark:bg-primary-900/20 border-primary-200 dark:border-primary-800 hover:border-primary-400 dark:hover:border-primary-600',
                            'danger' => 'bg-danger-50 dark:bg-danger-900/20 border-danger-200 dark:border-danger-800 hover:border-danger-400 dark:hover:border-danger-600',
                            default => 'bg-gray-50 dark:bg-gray-900/20 border-gray-200 dark:border-gray-700 hover:border-gray-400 dark:hover:border-gray-500',
                        };
                        $iconBgClass = match($color) {
                            'success' => 'bg-success-500',
                            'info' => 'bg-info-500',
                            'warning' => 'bg-warning-500',
                            'primary' => 'bg-primary-500',
                            'danger' => 'bg-danger-500',
                            default => 'bg-gray-500',
                        };
                        $titleTextClass = match($color) {
                            'success' => 'text-success-900 dark:text-success-100',
                            'info' => 'text-info-900 dark:text-info-100',
                            'warning' => 'text-warning-900 dark:text-warning-100',
                            'primary' => 'text-primary-900 dark:text-primary-100',
                            'danger' => 'text-danger-900 dark:text-danger-100',
                            default => 'text-gray-900 dark:text-gray-100',
                        };
                        $descTextClass = match($color) {
                            'success' => 'text-success-600 dark:text-success-400',
                            'info' => 'text-info-600 dark:text-info-400',
                            'warning' => 'text-warning-600 dark:text-warning-400',
                            'primary' => 'text-primary-600 dark:text-primary-400',
                            'danger' => 'text-danger-600 dark:text-danger-400',
                            default => 'text-gray-600 dark:text-gray-400',
                        };
                        $chevronClass = match($color) {
                            'success' => 'text-success-500',
                            'info' => 'text-info-500',
                            'warning' => 'text-warning-500',
                            'primary' => 'text-primary-500',
                            'danger' => 'text-danger-500',
                            default => 'text-gray-500',
                        };
                    @endphp
                    <a href="{{ $action['url'] }}"
                       class="group flex items-center gap-3 p-4 rounded-xl border-2 transition-all duration-200 hover:shadow-lg {{ $bgClasses }}">
                        <div class="flex-shrink-0 w-12 h-12 rounded-xl flex items-center justify-center transition-transform group-hover:scale-110 {{ $iconBgClass }}">
                            <x-dynamic-component :component="$action['icon']" class="w-6 h-6 text-white" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold {{ $titleTextClass }}">{{ $action['title'] }}</p>
                            <p class="text-xs mt-0.5 {{ $descTextClass }}">
                                @if(isset($action['count']) && $action['count'] !== '')
                                    <span class="font-bold">{{ $action['count'] }}</span>
                                @endif
                                {{ $action['description'] }}
                            </p>
                        </div>
                        <x-heroicon-o-chevron-left class="w-5 h-5 opacity-50 group-hover:opacity-100 transition-opacity {{ $chevronClass }}" />
                    </a>
                @endforeach
            </div>
        @else
            <div class="text-center py-12">
                <div class="w-16 h-16 mx-auto bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center mb-4">
                    <x-heroicon-o-clipboard-document-list class="w-8 h-8 text-gray-400" />
                </div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">لم يتم تعيين أي مسؤوليات بعد</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">تواصل مع المسؤول لتعيين المسؤوليات</p>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
