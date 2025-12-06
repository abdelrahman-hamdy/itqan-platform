<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
    @forelse ($items as $item)
        <div
            wire:click="selectItem({{ $item['id'] }}, '{{ $item['type'] }}')"
            class="cursor-pointer transition-all duration-200 item-card {{ $selectedItemId === $item['id'] && $selectedItemType === $item['type'] ? 'item-selected' : '' }}"
            data-item-id="{{ $item['id'] }}"
            data-item-type="{{ $item['type'] }}"
        >
            <x-filament::card
                class="border-2 border-gray-200 dark:border-gray-700 hover:ring-2 hover:ring-primary-300 dark:hover:ring-primary-600 hover:shadow-md transition-all duration-200"
            >
                <div class="space-y-3">
                    {{-- Item Header --}}
                    <div class="flex items-center justify-between">
                        <h4 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                            {{ $item['name'] ?? $item['title'] ?? 'عنصر' }}
                        </h4>
                        <x-filament::badge
                            :color="match($item['status']) {
                                'scheduled', 'active', 'fully_scheduled' => 'success',
                                'partially_scheduled', 'published' => 'info',
                                'not_scheduled', 'pending' => 'warning',
                                default => 'gray',
                            }"
                        >
                            {{ match($item['status']) {
                                'scheduled' => 'مجدولة',
                                'not_scheduled' => 'غير مجدولة',
                                'partially_scheduled' => 'مجدولة جزئياً',
                                'fully_scheduled' => 'مكتملة الجدولة',
                                'active' => 'نشط',
                                'published' => 'منشور',
                                'pending' => 'قيد الانتظار',
                                default => $item['status'],
                            } }}
                        </x-filament::badge>
                    </div>

                    {{-- Item Details --}}
                    <div class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                        {{-- Student Name (for individual items) --}}
                        @if(isset($item['student_name']))
                            <div class="flex items-center gap-2">
                                <x-heroicon-m-user class="w-4 h-4" />
                                <span>الطالب: {{ $item['student_name'] }}</span>
                            </div>
                        @endif

                        {{-- Subject Name --}}
                        @if(isset($item['subject_name']))
                            <div class="flex items-center gap-2">
                                <x-heroicon-m-book-open class="w-4 h-4" />
                                <span>المادة: {{ $item['subject_name'] }}</span>
                            </div>
                        @endif

                        {{-- Students Count (for group items) --}}
                        @if(isset($item['students_count']) && isset($item['max_students']))
                            <div class="flex items-center gap-2">
                                <x-heroicon-m-users class="w-4 h-4" />
                                <span>الطلاب: {{ $item['students_count'] }}/{{ $item['max_students'] }}</span>
                            </div>
                        @endif

                        {{-- Enrolled Students (for courses) --}}
                        @if(isset($item['enrolled_students']) && isset($item['max_students']) && !isset($item['students_count']))
                            <div class="flex items-center gap-2">
                                <x-heroicon-m-users class="w-4 h-4" />
                                <span>المشتركون: {{ $item['enrolled_students'] }}/{{ $item['max_students'] }}</span>
                            </div>
                        @endif

                        {{-- Total Sessions --}}
                        @if(isset($item['total_sessions']) || isset($item['sessions_count']))
                            <div class="flex items-center gap-2">
                                <x-heroicon-m-calendar-days class="w-4 h-4" />
                                <span>إجمالي الجلسات: {{ $item['total_sessions'] ?? $item['sessions_count'] }}</span>
                            </div>
                        @endif

                        {{-- Scheduled Sessions --}}
                        @if(isset($item['sessions_scheduled']))
                            <div class="flex items-center gap-2">
                                <x-heroicon-m-check-circle class="w-4 h-4 text-green-500" />
                                <span>المجدولة: {{ $item['sessions_scheduled'] }}</span>
                            </div>
                        @endif

                        {{-- Remaining Sessions --}}
                        @if(isset($item['sessions_remaining']))
                            <div class="flex items-center gap-2">
                                <x-heroicon-m-clock class="w-4 h-4 text-orange-500" />
                                <span>المتبقية: {{ $item['sessions_remaining'] }}</span>
                            </div>
                        @endif

                        {{-- Start Date (for courses) --}}
                        @if(isset($item['start_date']) && $item['start_date'])
                            <div class="flex items-center gap-2">
                                <x-heroicon-m-play class="w-4 h-4" />
                                <span>البداية: {{ $item['start_date'] }}</span>
                            </div>
                        @endif

                        {{-- Trial Request Details --}}
                        @if(isset($item['phone']))
                            <div class="flex items-center gap-2">
                                <x-heroicon-m-phone class="w-4 h-4" />
                                <span>{{ $item['phone'] }}</span>
                            </div>
                        @endif

                        @if(isset($item['level_label']))
                            <div class="flex items-center gap-2">
                                <x-heroicon-m-academic-cap class="w-4 h-4" />
                                <span>المستوى: {{ $item['level_label'] }}</span>
                            </div>
                        @endif

                        @if(isset($item['preferred_time_label']))
                            <div class="flex items-center gap-2">
                                <x-heroicon-m-clock class="w-4 h-4" />
                                <span>الوقت المفضل: {{ $item['preferred_time_label'] }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            </x-filament::card>
        </div>
    @empty
        <div class="col-span-full">
            <x-filament::section>
                <div class="text-center py-12">
                    <div class="w-16 h-16 mx-auto mb-4 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center">
                        <x-heroicon-o-inbox class="w-8 h-8 text-gray-400" />
                    </div>
                    <h3 class="mt-2 text-lg font-medium text-gray-900 dark:text-gray-100">لا توجد عناصر</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">سيتم عرض العناصر المتاحة هنا</p>
                </div>
            </x-filament::section>
        </div>
    @endforelse
</div>
