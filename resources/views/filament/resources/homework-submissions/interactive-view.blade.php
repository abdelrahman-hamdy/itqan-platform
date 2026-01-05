<div class="space-y-4 p-4">
    <div class="grid grid-cols-2 gap-4">
        <div>
            <span class="text-sm text-gray-500 dark:text-gray-400">الطالب</span>
            <p class="font-medium">{{ $record->student?->name ?? '-' }}</p>
        </div>
        <div>
            <span class="text-sm text-gray-500 dark:text-gray-400">الواجب</span>
            <p class="font-medium">{{ $record->homework?->title ?? '-' }}</p>
        </div>
        <div>
            <span class="text-sm text-gray-500 dark:text-gray-400">الدورة</span>
            <p class="font-medium">{{ $record->homework?->session?->course?->title ?? '-' }}</p>
        </div>
        <div>
            <span class="text-sm text-gray-500 dark:text-gray-400">الجلسة</span>
            <p class="font-medium">{{ $record->homework?->session?->session_code ?? '-' }}</p>
        </div>
        <div>
            <span class="text-sm text-gray-500 dark:text-gray-400">تاريخ التسليم</span>
            <p class="font-medium">{{ $record->submitted_at?->format('d/m/Y H:i') ?? '-' }}</p>
        </div>
        <div>
            <span class="text-sm text-gray-500 dark:text-gray-400">الحالة</span>
            <p class="font-medium">
                @php
                    $statusEnum = $record->submission_status;
                    $statusColor = $statusEnum?->getColor() ?? 'gray';
                @endphp
                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                    bg-{{ $statusColor }}-100 text-{{ $statusColor }}-700 dark:bg-{{ $statusColor }}-900/20 dark:text-{{ $statusColor }}-400">
                    {{ $statusEnum?->getLabel() ?? '-' }}
                </span>
            </p>
        </div>
        <div>
            <span class="text-sm text-gray-500 dark:text-gray-400">الدرجة</span>
            <p class="font-medium">
                @if($record->score !== null)
                    {{ $record->score }}/{{ $record->max_score ?? 10 }}
                    @php
                        $maxScore = $record->max_score ?? 10;
                        $percentage = $maxScore > 0 ? round(($record->score / $maxScore) * 100, 1) : 0;
                    @endphp
                    ({{ $percentage }}%)
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
                <span>تسليم متأخر</span>
            </div>
        </div>
    @endif
</div>
