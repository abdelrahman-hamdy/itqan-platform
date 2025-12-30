@php
    use App\Enums\CalendarSessionType;
@endphp

<div class="space-y-4">
    @if(empty($sessions))
        <div class="text-center py-8">
            <x-heroicon-o-calendar-days class="w-12 h-12 mx-auto text-gray-400 mb-4" />
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-1">
                لا توجد جلسات
            </h3>
            <p class="text-gray-500 dark:text-gray-400">
                لا توجد جلسات مجدولة في هذا اليوم
            </p>
        </div>
    @else
        <div class="space-y-3">
            @foreach($sessions as $session)
                <div class="flex items-center gap-4 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-750 transition-colors">
                    {{-- Time --}}
                    <div class="flex-shrink-0 text-center">
                        <div class="text-lg font-bold text-gray-900 dark:text-white">
                            {{ $session['time'] }}
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">
                            {{ $session['duration'] }} دقيقة
                        </div>
                    </div>

                    {{-- Color Indicator --}}
                    <div
                        class="flex-shrink-0 w-1 h-12 rounded-full"
                        style="background-color: {{ $session['color'] }}"
                    ></div>

                    {{-- Session Info --}}
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-1">
                            <span
                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium text-white"
                                style="background-color: {{ $session['color'] }}"
                            >
                                @if(isset($session['icon']))
                                    <x-dynamic-component :component="$session['icon']" class="w-3 h-3" />
                                @endif
                                {{ $session['sessionType'] }}
                            </span>

                            @if($session['status'] && $session['statusColor'])
                                <span
                                    class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium text-white"
                                    style="background-color: {{ $session['statusColor'] }}"
                                >
                                    {{ $session['statusLabel'] }}
                                </span>
                            @endif

                            @if($session['isPassed'])
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-200 text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                    انتهت
                                </span>
                            @endif
                        </div>

                        <div class="font-medium text-gray-900 dark:text-white truncate">
                            {{ $session['studentName'] }}
                        </div>

                        @if($session['subject'])
                            <div class="text-sm text-gray-500 dark:text-gray-400 truncate">
                                {{ $session['subject'] }}
                            </div>
                        @endif
                    </div>

                    {{-- Actions --}}
                    <div class="flex-shrink-0 flex items-center gap-2">
                        @if($session['canEdit'] && !$session['isPassed'])
                            <button
                                type="button"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium rounded-lg text-white transition-colors"
                                style="background-color: #16a34a;"
                                onmouseover="this.style.backgroundColor='#22c55e'"
                                onmouseout="this.style.backgroundColor='#16a34a'"
                                title="تعديل سريع"
                                x-data
                                x-on:click="
                                    const wireComponent = Livewire.find('{{ $widgetId }}');
                                    $dispatch('close-modal', { id: '{{ $widgetId }}-action' });
                                    setTimeout(() => {
                                        wireComponent.call('openEditDialog', '{{ $session['eventId'] }}');
                                    }, 400);
                                "
                            >
                                <x-heroicon-m-pencil-square class="w-4 h-4" />
                                <span>تعديل</span>
                            </button>
                        @endif

                        @if($session['sessionUrl'])
                            <a
                                href="{{ $session['sessionUrl'] }}"
                                target="_blank"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium rounded-lg text-white transition-colors"
                                style="background-color: #2563eb;"
                                onmouseover="this.style.backgroundColor='#3b82f6'"
                                onmouseout="this.style.backgroundColor='#2563eb'"
                                title="فتح في صفحة جديدة"
                            >
                                <x-heroicon-m-arrow-top-right-on-square class="w-4 h-4" />
                                <span>فتح</span>
                            </a>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Summary --}}
        <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
            <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400">
                <span>إجمالي الجلسات</span>
                <span class="font-medium">{{ count($sessions) }} جلسة</span>
            </div>
        </div>
    @endif
</div>
