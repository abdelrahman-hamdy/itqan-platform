@php
    $timezone = \App\Services\AcademyContextService::getTimezone();
@endphp
<div class="space-y-4">
    <div class="grid grid-cols-1 gap-4">
        @if($record->scheduled_at)
            <div>
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">موعد الجلسة</div>
                <div class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                    {{ $record->scheduled_at->timezone($timezone)->format('Y-m-d h:i A') }}
                </div>
            </div>
        @endif

        @if($record->duration_minutes)
            <div>
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">مدة الجلسة</div>
                <div class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                    {{ $record->duration_minutes }} دقيقة
                </div>
            </div>
        @endif

        @if($record->status)
            <div>
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">حالة الجلسة</div>
                <div class="mt-1">
                    <x-filament::badge
                        :color="match($record->status instanceof \App\Enums\SessionStatus ? $record->status->value : $record->status) {
                            'scheduled' => 'warning',
                            'ready' => 'info',
                            'ongoing' => 'primary',
                            'completed' => 'success',
                            'cancelled' => 'danger',
                            default => 'gray',
                        }"
                    >
                        {{ match($record->status instanceof \App\Enums\SessionStatus ? $record->status->value : $record->status) {
                            'scheduled' => 'مجدولة',
                            'ready' => 'جاهزة',
                            'ongoing' => 'جارية',
                            'completed' => 'مكتملة',
                            'cancelled' => 'ملغية',
                            default => $record->status,
                        } }}
                    </x-filament::badge>
                </div>
            </div>
        @endif

        @if($record->description)
            <div>
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">ملاحظات الجلسة</div>
                <div class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                    {{ $record->description }}
                </div>
            </div>
        @endif
    </div>
</div>
