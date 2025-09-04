<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            التقويم الأكاديمي
        </x-slot>

        <div class="space-y-4">
            @if(!empty($this->getViewData()['events']))
                <div class="grid gap-3">
                    <h4 class="text-sm font-medium text-gray-900">جلسات اليوم - {{ $this->getViewData()['dayName'] }}</h4>
                    @foreach($this->getViewData()['events'] as $event)
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg border">
                            <div class="flex items-center space-x-3 space-x-reverse">
                                @if($event['type'] === 'individual')
                                    <div class="w-3 h-3 bg-blue-500 rounded-full"></div>
                                @else
                                    <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                                @endif
                                <div>
                                    <p class="text-sm font-medium text-gray-900">{{ $event['title'] }}</p>
                                    @if($event['type'] === 'individual')
                                        <p class="text-xs text-gray-500">الطالب: {{ $event['student'] }}</p>
                                    @else
                                        <p class="text-xs text-gray-500">الدورة: {{ $event['course'] }}</p>
                                    @endif
                                    <p class="text-xs text-gray-500">المادة: {{ $event['subject'] }}</p>
                                </div>
                            </div>
                            <div class="text-left">
                                <p class="text-sm font-medium text-gray-900">{{ $event['time'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-8">
                    <div class="text-gray-400 mb-2">
                        <i class="ri-calendar-line text-3xl"></i>
                    </div>
                    <p class="text-sm text-gray-500">لا توجد جلسات مجدولة لهذا اليوم</p>
                </div>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
