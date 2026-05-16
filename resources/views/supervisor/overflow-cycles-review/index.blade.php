@extends('layouts.supervisor')

@section('title', 'مراجعة الدورات المتجاوزة سعتها')

@section('content')
<div class="container mx-auto px-4 py-6" dir="rtl">

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">مراجعة الدورات المتجاوزة سعتها</h1>
        <p class="text-sm text-gray-600 mt-2">
            هذه الدورات لديها حصص قديمة معلَّمة كـ <code>subscription_counted=true</code> لكن
            إضافتها كصفوف <code>session_consumption</code> ستتجاوز عدد الحصص الإجمالي للدورة.
            هذا يعني أن أحد الاحتمالين قد حدث تاريخياً، والرجاء اختيار الإجراء المناسب لكل دورة:
        </p>
        <ul class="list-disc pr-6 mt-2 text-sm text-gray-600 space-y-1">
            <li><strong>زيادة الإجمالي</strong> — كانت الإدارة قد مدّدت الباقة دون تحديث <code>total_sessions</code>.</li>
            <li><strong>إعفاء N من العَلَم</strong> — الكتابة القديمة عدّت بعض الحصص مرتين؛ نلغي عَلَم العدّ على N منها.</li>
            <li><strong>تأجيل</strong> — لا تعديل الآن.</li>
        </ul>
        <p class="text-xs text-gray-500 mt-2">
            بعد تطبيق القرار، أعد تشغيل <code>subscriptions:fix-legacy-counting-drift --apply</code>
            لإكمال إنشاء صفوف <code>session_consumption</code> المتبقية.
        </p>
    </div>

    @if (session('success'))
        <div class="mb-4 p-3 bg-green-100 border-r-4 border-green-500 text-green-800 rounded text-sm">
            {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="mb-4 p-3 bg-red-100 border-r-4 border-red-500 text-red-800 rounded text-sm">
            {{ session('error') }}
        </div>
    @endif
    @if ($errors->any())
        <div class="mb-4 p-3 bg-red-100 border-r-4 border-red-500 text-red-800 rounded text-sm">
            <ul class="list-disc pr-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="mb-4 text-sm text-gray-700">
        إجمالي الدورات المعلقة: <strong>{{ count($cycles) }}</strong>
    </div>

    @if (count($cycles) === 0)
        <div class="bg-green-50 border border-green-200 rounded-lg p-8 text-center">
            <div class="text-3xl">✓</div>
            <div class="text-lg font-semibold text-green-800 mt-2">جميع الدورات تمت مراجعتها</div>
            <div class="text-sm text-green-700 mt-1">لا توجد دورات تتجاوز سعتها بعد الآن.</div>
        </div>
    @else
        <div class="space-y-3">
            @foreach ($cycles as $row)
                <div class="bg-white shadow rounded-lg p-4 border-r-4 border-amber-400">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div class="flex-1 min-w-[320px]">
                            <div class="flex items-center gap-2 mb-2 flex-wrap">
                                <span class="text-base font-semibold text-gray-900">
                                    دورة #{{ $row['cycle_id'] }}
                                </span>
                                <span class="inline-block px-2 py-0.5 rounded text-xs bg-gray-100 text-gray-700">
                                    اشتراك #{{ $row['sub_id'] ?? '—' }}
                                </span>
                                <span class="inline-block px-2 py-0.5 rounded text-xs
                                    {{ $row['sub_status'] === 'active' ? 'bg-green-100 text-green-800' : '' }}
                                    {{ $row['sub_status'] === 'paused' ? 'bg-blue-100 text-blue-800' : '' }}
                                    {{ $row['sub_status'] === 'expired' ? 'bg-gray-100 text-gray-800' : '' }}
                                    {{ $row['sub_status'] === 'cancelled' ? 'bg-red-100 text-red-800' : '' }}
                                    {{ $row['sub_status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}">
                                    {{ $row['sub_status'] ?? '—' }}
                                </span>
                                <span class="inline-block px-2 py-0.5 rounded text-xs bg-gray-100 text-gray-700">
                                    دفع: {{ $row['cycle_payment_status'] }}
                                </span>
                                <span class="inline-block px-2 py-0.5 rounded text-xs bg-amber-100 text-amber-800">
                                    تجاوز: +{{ $row['overflow_by'] }}
                                </span>
                            </div>
                            <div class="text-sm text-gray-700">
                                <strong>الطالب:</strong> {{ $row['student_name'] }}
                                @if ($row['teacher_name'])
                                    &nbsp;·&nbsp; <strong>المعلم:</strong> {{ $row['teacher_name'] }}
                                @endif
                            </div>
                            <div class="text-xs text-gray-500 mt-1 font-mono">
                                used: <strong>{{ $row['used'] }}</strong>
                                &nbsp;·&nbsp; drift: <strong class="text-amber-700">{{ $row['drift'] }}</strong>
                                &nbsp;·&nbsp; would_be_used: <strong>{{ $row['would_be_used'] }}</strong>
                                &nbsp;·&nbsp; total: <strong>{{ $row['total'] }}</strong>
                            </div>
                            <div class="text-xs text-gray-500 mt-1">
                                <strong>يبدأ:</strong> {{ $row['starts_at'] ?? '—' }}
                                &nbsp;·&nbsp; <strong>ينتهي:</strong> {{ $row['ends_at'] ?? '—' }}
                            </div>
                            <div class="mt-2 flex flex-wrap gap-2">
                                @if ($row['sub_id'])
                                    <a href="{{ route('manage.subscriptions.show', ['subdomain' => $subdomain, 'type' => 'quran', 'subscription' => $row['sub_id']]) }}"
                                       target="_blank"
                                       class="inline-flex items-center gap-1 text-xs text-blue-700 hover:underline">
                                        فتح صفحة الاشتراك ↗
                                    </a>
                                @endif
                                <details class="text-xs">
                                    <summary class="cursor-pointer text-gray-600 hover:underline">
                                        عرض IDs الحصص ({{ count($row['drift_session_ids']) }})
                                    </summary>
                                    <div class="font-mono text-gray-500 mt-1 break-all">
                                        {{ implode(', ', $row['drift_session_ids']) }}
                                    </div>
                                </details>
                            </div>
                        </div>

                        {{-- Decision form --}}
                        <form method="POST"
                              action="{{ route('manage.overflow-cycles-review.record', ['subdomain' => $subdomain, 'cycle' => $row['cycle_id']]) }}"
                              class="flex flex-col gap-2 min-w-[280px]"
                              x-data="{ action: 'bump_total' }">
                            @csrf
                            <label class="text-xs text-gray-700 font-semibold">الإجراء:</label>
                            <select name="action" x-model="action"
                                    class="px-2 py-1 border border-gray-300 rounded text-sm">
                                <option value="bump_total">زيادة الإجمالي بـ {{ $row['drift'] }}</option>
                                <option value="forgive_n">إعفاء N من العَلَم</option>
                                <option value="defer">تأجيل</option>
                            </select>

                            <div x-show="action === 'forgive_n'" x-cloak>
                                <label class="text-xs text-gray-700 font-semibold">عدد الحصص للإعفاء:</label>
                                <input type="number"
                                       name="forgive_count"
                                       value="{{ $row['overflow_by'] }}"
                                       min="1"
                                       max="{{ $row['drift'] }}"
                                       class="w-full px-2 py-1 border border-gray-300 rounded text-sm text-center font-mono"/>
                                <p class="text-xs text-gray-500 mt-1">
                                    سيُعفى أحدث {{ $row['overflow_by'] }} حصة من العَلَم القديم.
                                </p>
                            </div>

                            <input type="text"
                                   name="note"
                                   placeholder="ملاحظة (اختياري)"
                                   maxlength="500"
                                   class="w-full px-2 py-1 border border-gray-300 rounded text-xs"/>
                            <button type="submit"
                                    class="px-3 py-1.5 bg-indigo-600 text-white text-sm rounded hover:bg-indigo-700">
                                حفظ
                            </button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <div class="mt-6 p-3 bg-gray-50 border rounded text-xs text-gray-600">
        <strong>كيف يعمل هذا؟</strong>
        بعد اختيار <em>زيادة الإجمالي</em> أو <em>إعفاء N</em>، تُسجَّل العملية في
        <code>backfill_log</code> ويمكن التراجع عنها لاحقاً. بعد ذلك يمكن إعادة تشغيل
        <code>subscriptions:fix-legacy-counting-drift --apply</code> لتحويل ما تبقّى من
        الحصص إلى صفوف <code>session_consumption</code> رسمياً.
    </div>
</div>
@endsection
