@extends('layouts.supervisor')

@section('title', 'مراجعة الحالات المتبقية')

@section('content')
<div class="container mx-auto px-4 py-6" dir="rtl">

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">مراجعة الحالات المتبقية</h1>
        <p class="text-sm text-gray-600 mt-2">
            هذه الجلسات صنّفها فحص الانحراف (<code>subscriptions:classify-residue-drift</code>)
            بأنها <code>NEEDS_REVIEW</code> أو <code>BACKUP_SHOWS_DIFFERENT</code> —
            أي أن المُصلِحات التلقائية (B.3 / B.4 / B.2) لا تستطيع إغلاقها دون قرار بشري.
            اختر إجراءً واحداً لكل جلسة:
        </p>
        <ul class="list-disc pr-6 mt-2 text-sm text-gray-600 space-y-1">
            <li><strong>اعتبارها مستهلكة</strong> — تكتب صف <code>session_consumption</code>
                (المصدر: <code>admin_manual</code>) وتعَلِّم الجلسة <code>subscription_counted=true</code>.
                تحتاج إلى وجود <code>quran_subscription_id</code> و <code>subscription_cycle_id</code> على الجلسة.</li>
            <li><strong>إلغاء العَلَم</strong> — تعكس أي <code>session_consumption</code> نشط وتُعيد
                <code>subscription_counted=false</code>. تُعاد مزامنة الاشتراك.</li>
            <li><strong>تأجيل</strong> — لا تعديل، فقط تسقط من قائمة المراجعة.</li>
        </ul>
        <p class="text-xs text-gray-500 mt-2">
            كل قرار يُسجَّل في <code>backfill_log</code> ويمكن التراجع عنه لاحقاً.
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
        إجمالي الحالات المعلقة: <strong>{{ count($entries) }}</strong>
    </div>

    @if (count($entries) === 0)
        <div class="bg-green-50 border border-green-200 rounded-lg p-8 text-center">
            <div class="text-3xl">✓</div>
            <div class="text-lg font-semibold text-green-800 mt-2">لا توجد حالات تحتاج مراجعتك</div>
            <div class="text-sm text-green-700 mt-1">
                إما لم يصدر CSV مصنّف بعد، أو أن جميع الحالات تم البت فيها.
            </div>
        </div>
    @else
        <div class="space-y-3">
            @foreach ($entries as $row)
                <div class="bg-white shadow rounded-lg p-4 border-r-4
                    {{ $row['verdict'] === 'BACKUP_SHOWS_DIFFERENT' ? 'border-amber-400' : 'border-indigo-400' }}">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div class="flex-1 min-w-[320px]">
                            <div class="flex items-center gap-2 mb-2 flex-wrap">
                                <span class="text-base font-semibold text-gray-900">
                                    جلسة #{{ $row['session_id'] }}
                                </span>
                                <span class="inline-block px-2 py-0.5 rounded text-xs font-mono
                                    {{ $row['verdict'] === 'BACKUP_SHOWS_DIFFERENT' ? 'bg-amber-100 text-amber-800' : 'bg-indigo-100 text-indigo-800' }}">
                                    {{ $row['verdict'] }}
                                </span>
                                <span class="inline-block px-2 py-0.5 rounded text-xs bg-gray-100 text-gray-700">
                                    حالة الجلسة: {{ $row['session_status'] ?? '—' }}
                                </span>
                                @if ($row['counted'])
                                    <span class="inline-block px-2 py-0.5 rounded text-xs bg-blue-100 text-blue-800">
                                        counted=1
                                    </span>
                                @endif
                            </div>
                            <div class="text-sm text-gray-700">
                                <strong>الطالب:</strong> {{ $row['student_name'] }}
                                @if ($row['teacher_name'])
                                    &nbsp;·&nbsp; <strong>المعلم:</strong> {{ $row['teacher_name'] }}
                                @endif
                            </div>
                            <div class="text-xs text-gray-500 mt-1 font-mono">
                                sub: <strong>{{ $row['sub_id'] ?? '∅' }}</strong>
                                @if ($row['sub_status'])
                                    ({{ $row['sub_status'] }})
                                @endif
                                &nbsp;·&nbsp; cycle: <strong>{{ $row['cycle_id'] ?? '∅' }}</strong>
                                @if ($row['cycle_used'] !== null)
                                    ({{ $row['cycle_used'] }}/{{ $row['cycle_total'] }})
                                @endif
                                @if ($row['cycle_state'])
                                    state={{ $row['cycle_state'] }}
                                @endif
                            </div>
                            <div class="text-xs text-gray-500 mt-1">
                                <strong>الموعد:</strong> {{ $row['scheduled_at'] ?? '—' }}
                            </div>
                            @if (! empty($row['evidence']))
                                <div class="mt-2 p-2 bg-gray-50 border border-gray-200 rounded text-xs">
                                    <div class="font-semibold text-gray-700 mb-1">شواهد:</div>
                                    <div class="flex flex-wrap gap-1">
                                        @foreach ($row['evidence'] as $tag)
                                            <span class="inline-block px-1.5 py-0.5 bg-white border border-gray-300 rounded text-[10px] font-mono text-gray-700">
                                                {{ $tag }}
                                            </span>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                            @if ($row['baseline_diff'])
                                <div class="mt-2 p-2 bg-amber-50 border border-amber-200 rounded text-xs">
                                    <strong class="text-amber-900">الفرق عن نسخة 15-مايو الاحتياطية:</strong>
                                    <span class="font-mono text-amber-800">{{ $row['baseline_diff'] }}</span>
                                </div>
                            @endif
                            @if ($row['sub_id'])
                                <div class="mt-2 flex flex-wrap gap-2">
                                    <a href="{{ route('manage.subscriptions.show', ['subdomain' => $subdomain, 'type' => 'quran', 'subscription' => $row['sub_id']]) }}"
                                       target="_blank"
                                       class="inline-flex items-center gap-1 text-xs text-blue-700 hover:underline">
                                        فتح صفحة الاشتراك ↗
                                    </a>
                                </div>
                            @endif
                        </div>

                        {{-- Decision form --}}
                        <form method="POST"
                              action="{{ route('manage.residue-review.record', ['subdomain' => $subdomain, 'session' => $row['session_id']]) }}"
                              class="flex flex-col gap-2 min-w-[260px]">
                            @csrf
                            <label class="text-xs text-gray-700 font-semibold">الإجراء:</label>
                            <select name="action" class="px-2 py-1 border border-gray-300 rounded text-sm">
                                @if ($row['can_force_count'])
                                    <option value="force_count">اعتبارها مستهلكة</option>
                                @else
                                    <option value="force_count" disabled>اعتبارها مستهلكة (يحتاج sub+cycle)</option>
                                @endif
                                <option value="force_uncount">إلغاء العَلَم</option>
                                <option value="defer" selected>تأجيل</option>
                            </select>

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
        <strong>كيف تُحدَّث هذه القائمة؟</strong>
        تُقرأ من <code>storage/app/audit/residue-classification-2026-05-17.csv</code>
        — أعد تشغيل <code>php artisan subscriptions:classify-residue-drift</code>
        لإعادة توليد التصنيف بعد كل إصلاح.
    </div>
</div>
@endsection
