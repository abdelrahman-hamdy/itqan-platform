@props(['history'])

@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';
@endphp

<div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-start py-3 px-4 text-sm font-medium text-gray-500">الاختبار</th>
                    <th class="text-start py-3 px-4 text-sm font-medium text-gray-500">المصدر</th>
                    <th class="text-start py-3 px-4 text-sm font-medium text-gray-500">الدرجة</th>
                    <th class="text-start py-3 px-4 text-sm font-medium text-gray-500">الحالة</th>
                    <th class="text-start py-3 px-4 text-sm font-medium text-gray-500">تاريخ التقديم</th>
                    <th class="text-start py-3 px-4 text-sm font-medium text-gray-500">الإجراءات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($history as $record)
                    <tr class="hover:bg-gray-50">
                        <td class="py-3 px-4">
                            <span class="font-medium text-gray-900">{{ $record->quiz->title ?? 'اختبار محذوف' }}</span>
                        </td>
                        <td class="py-3 px-4 text-gray-600 text-sm">
                            {{ $record->assignable_name }}
                        </td>
                        <td class="py-3 px-4">
                            <span class="font-bold text-lg {{ $record->passed ? 'text-green-600' : 'text-red-600' }}">
                                {{ $record->score }}%
                            </span>
                        </td>
                        <td class="py-3 px-4">
                            @if($record->passed)
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    <i class="ri-check-line ms-1"></i>
                                    ناجح
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                    <i class="ri-close-line ms-1"></i>
                                    لم ينجح
                                </span>
                            @endif
                        </td>
                        <td class="py-3 px-4 text-gray-600 text-sm">
                            {{ $record->submitted_at->format('Y-m-d') }}
                            <br>
                            <span class="text-xs text-gray-400">{{ $record->submitted_at->format('H:i') }}</span>
                        </td>
                        <td class="py-3 px-4">
                            <a href="{{ route('student.quiz.result', ['subdomain' => $subdomain, 'quiz_id' => $record->attempt->quiz_assignment_id]) }}"
                               class="inline-flex items-center text-blue-600 hover:text-blue-800 text-sm">
                                <i class="ri-eye-line ms-1"></i>
                                عرض التفاصيل
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
