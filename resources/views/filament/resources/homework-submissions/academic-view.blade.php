<div class="space-y-4 p-4">
    <div class="grid grid-cols-2 gap-4">
        <div>
            <span class="text-sm text-gray-500 dark:text-gray-400">الطالب</span>
            <p class="font-medium">{{ $record->student?->name ?? '-' }}</p>
        </div>
        <div>
            <span class="text-sm text-gray-500 dark:text-gray-400">الجلسة</span>
            <p class="font-medium">{{ $record->session?->session_code ?? '-' }}</p>
        </div>
        <div>
            <span class="text-sm text-gray-500 dark:text-gray-400">الواجب</span>
            <p class="font-medium">{{ $record->homework?->title ?? '-' }}</p>
        </div>
        <div>
            <span class="text-sm text-gray-500 dark:text-gray-400">تاريخ التسليم</span>
            <p class="font-medium">{{ $record->submitted_at?->format('d/m/Y H:i') ?? '-' }}</p>
        </div>
        <div>
            <span class="text-sm text-gray-500 dark:text-gray-400">الحالة</span>
            <p class="font-medium">
                @php
                    $status = $record->submission_status?->value ?? $record->submission_status;
                    $statusLabels = [
                        'pending' => 'بانتظار التسليم',
                        'submitted' => 'تم التسليم',
                        'late' => 'متأخر',
                        'graded' => 'تم التصحيح',
                    ];
                @endphp
                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                    @if($status === 'pending') bg-gray-100 text-gray-700
                    @elseif($status === 'submitted') bg-info-100 text-info-700
                    @elseif($status === 'late') bg-danger-100 text-danger-700
                    @elseif($status === 'graded') bg-success-100 text-success-700
                    @else bg-gray-100 text-gray-700
                    @endif">
                    {{ $statusLabels[$status] ?? $status }}
                </span>
            </p>
        </div>
        <div>
            <span class="text-sm text-gray-500 dark:text-gray-400">الدرجة</span>
            <p class="font-medium">
                @if($record->score !== null)
                    {{ $record->score }}/{{ $record->max_score }}
                    @if($record->score_percentage)
                        ({{ round($record->score_percentage, 1) }}%)
                    @endif
                @else
                    -
                @endif
            </p>
        </div>
    </div>

    @if($record->submission_text)
        <div class="border-t pt-4">
            <span class="text-sm text-gray-500 dark:text-gray-400">نص الإجابة</span>
            <div class="mt-2 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                <p class="whitespace-pre-wrap">{{ $record->submission_text }}</p>
            </div>
        </div>
    @endif

    @if($record->submission_files && count($record->submission_files) > 0)
        <div class="border-t pt-4">
            <span class="text-sm text-gray-500 dark:text-gray-400">الملفات المرفقة</span>
            <div class="mt-2 space-y-2">
                @foreach($record->submission_files as $file)
                    <div class="flex items-center gap-2 p-2 bg-gray-50 dark:bg-gray-800 rounded">
                        <x-heroicon-o-document class="w-5 h-5 text-gray-400" />
                        <span>{{ basename($file) }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @if($record->teacher_feedback)
        <div class="border-t pt-4">
            <span class="text-sm text-gray-500 dark:text-gray-400">ملاحظات المعلم</span>
            <div class="mt-2 p-3 bg-success-50 dark:bg-success-900/20 rounded-lg border border-success-200 dark:border-success-800">
                <p class="whitespace-pre-wrap text-success-800 dark:text-success-200">{{ $record->teacher_feedback }}</p>
            </div>
        </div>
    @endif

    @if($record->is_late)
        <div class="border-t pt-4">
            <div class="flex items-center gap-2 text-danger-600">
                <x-heroicon-o-clock class="w-5 h-5" />
                <span>تسليم متأخر ({{ $record->days_late }} يوم)</span>
            </div>
        </div>
    @endif
</div>
