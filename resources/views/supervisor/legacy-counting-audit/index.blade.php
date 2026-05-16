@extends('layouts.supervisor')

@section('title', 'مراجعة عدّ الحصص — أعمدة قديمة')

@section('content')
<div class="container mx-auto px-4 py-6" dir="rtl">

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">مراجعة عدّ الحصص (Phase 4 — قبل حذف الأعمدة القديمة)</h1>
        <p class="text-sm text-gray-600 mt-2">
            هذه الصفحة قراءة فقط. تَعرض جميع الحصص والدورات التي ستتأثر بإسقاط الأعمدة القديمة
            (<code>subscription_counted</code>، <code>subscription_counted_at</code>، <code>v2_consumption_complete</code>)،
            مع تصنيف المخاطر والقرار الإداري اللازم لكل حالة.
        </p>
    </div>

    {{-- ============= SUMMARY ============= --}}
    <div class="bg-white shadow rounded-lg p-6 mb-6">
        <h2 class="text-lg font-semibold mb-4">ملخص</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
            <div class="border rounded p-3">
                <div class="text-gray-500">إجمالي الدورات</div>
                <div class="text-xl font-bold">{{ number_format($summary['cycles_total']) }}</div>
            </div>
            <div class="border rounded p-3 bg-green-50">
                <div class="text-gray-500">دورات v2_complete=true</div>
                <div class="text-xl font-bold text-green-700">{{ number_format($summary['cycles_v2_complete']) }}</div>
            </div>
            <div class="border rounded p-3 bg-yellow-50">
                <div class="text-gray-500">دورات لم تُهاجَر بعد</div>
                <div class="text-xl font-bold text-yellow-700">{{ number_format($summary['cycles_v2_pending']) }}</div>
            </div>
            <div class="border rounded p-3">
                <div class="text-gray-500">صفوف session_consumption نشطة</div>
                <div class="text-xl font-bold">{{ number_format($summary['session_consumption_active']) }}</div>
            </div>
            <div class="border rounded p-3">
                <div class="text-gray-500">حصص Quran (subscription_counted=true)</div>
                <div class="font-bold">{{ number_format($summary['quran_sessions_legacy_counted']) }}</div>
            </div>
            <div class="border rounded p-3">
                <div class="text-gray-500">حصص أكاديمية (subscription_counted=true)</div>
                <div class="font-bold">{{ number_format($summary['academic_sessions_legacy_counted']) }}</div>
            </div>
            <div class="border rounded p-3">
                <div class="text-gray-500">meeting_attendances (counted_at NOT NULL)</div>
                <div class="font-bold">{{ number_format($summary['meeting_attendances_legacy_counted']) }}</div>
            </div>
        </div>

        <div class="mt-4 p-3 bg-blue-50 border-r-4 border-blue-500 text-sm text-blue-900">
            <strong>قاعدة الإسقاط:</strong> يمكن إسقاط الأعمدة بأمان فقط بعد:
            (1) صفر صفوف drift في كل الجداول أدناه،
            (2) جميع الدورات
            <code>v2_consumption_complete = true</code>،
            (3) صفر كتاب يكتب الأعمدة في الـ 7 أيام الماضية.
        </div>
    </div>

    {{-- ============= STUCK SESSIONS WITH EARNINGS — HIGHEST URGENCY ============= --}}
    <div class="bg-white shadow rounded-lg p-6 mb-6 border-r-4 border-red-500">
        <h2 class="text-lg font-semibold mb-2 text-red-700">
            🔴 حصص عالقة scheduled لها أرباح معلَّمة للمعلم ({{ count($stuckWithEarnings) }})
        </h2>
        <p class="text-sm text-gray-600 mb-4">
            هذه الحصص كانت متبقية في حالة <code>scheduled</code> بتاريخ ماضٍ، لكن سجل
            <code>teacher_earnings</code> أُنشئ لها بالفعل. أحدها الاحتمالين: إما أن الحصة جرت فعلاً
            (يجب تحديث الحالة إلى completed وإنشاء صف consumption) أو أن الأرباح أُنشئت خطأ (يجب
            عكسها). تطلَّب القرار الإداري لكل حالة.
        </p>

        @if (count($stuckWithEarnings) === 0)
            <p class="text-green-700 text-sm">لا توجد حصص في هذه الفئة.</p>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-xs border">
                    <thead class="bg-gray-100 text-gray-700">
                        <tr>
                            <th class="px-2 py-2 text-right">earning</th>
                            <th class="px-2 py-2 text-right">حصة</th>
                            <th class="px-2 py-2 text-right">المعلم</th>
                            <th class="px-2 py-2 text-right">الاشتراك</th>
                            <th class="px-2 py-2 text-right">المبلغ</th>
                            <th class="px-2 py-2 text-right">تاريخ الحصة</th>
                            <th class="px-2 py-2 text-right">تاريخ إنشاء الربح</th>
                            <th class="px-2 py-2 text-right">القرار المقترح</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($stuckWithEarnings as $r)
                            <tr class="border-t hover:bg-red-50">
                                <td class="px-2 py-2">#{{ $r['earning_id'] }}</td>
                                <td class="px-2 py-2">#{{ $r['session_id'] }}</td>
                                <td class="px-2 py-2">#{{ $r['teacher_id'] }}</td>
                                <td class="px-2 py-2">#{{ $r['subscription_id'] ?? '—' }}</td>
                                <td class="px-2 py-2 font-mono">{{ $r['amount'] }} {{ $r['currency'] }}</td>
                                <td class="px-2 py-2 font-mono text-gray-600">{{ $r['scheduled_at'] }}</td>
                                <td class="px-2 py-2 font-mono text-gray-600">{{ $r['earning_created'] }}</td>
                                <td class="px-2 py-2 text-gray-700">{{ $r['recommended'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- ============= LEGACY COUNTED, NO CONSUMPTION ROW — HIGH RISK ============= --}}
    <div class="bg-white shadow rounded-lg p-6 mb-6 border-r-4 border-red-500">
        <h2 class="text-lg font-semibold mb-2 text-red-700">
            🔴 حصص subscription_counted=true بدون صف consumption — إجمالي: {{ number_format($totals['drift_legacy_not_consumption_total']) }} (عرض أول {{ count($driftLegacyNotConsumption) }})
        </h2>
        <p class="text-sm text-gray-600 mb-4">
            النظام القديم يقول "هذه الحصة عُدّت" لكن الجدول الجديد (المرجع الموثّق) لا يحتوي على صف
            مطابق. إسقاط العمود الآن سيمحو الإثبات الوحيد أن الحصة عُدّت. الإجراء: إنشاء صف consumption مكافئ
            قبل الإسقاط (أو تأكيد إداري أن العَلَم القديم خطأ).
        </p>

        @if (count($driftLegacyNotConsumption) === 0)
            <p class="text-green-700 text-sm">لا توجد حصص في هذه الفئة. آمن لإسقاط العمود.</p>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-xs border">
                    <thead class="bg-gray-100 text-gray-700">
                        <tr>
                            <th class="px-2 py-2 text-right">النوع</th>
                            <th class="px-2 py-2 text-right">الحصة</th>
                            <th class="px-2 py-2 text-right">الحالة</th>
                            <th class="px-2 py-2 text-right">تاريخ الحصة</th>
                            <th class="px-2 py-2 text-right">عُدّت في</th>
                            <th class="px-2 py-2 text-right">الدورة</th>
                            <th class="px-2 py-2 text-right">القرار المقترح</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($driftLegacyNotConsumption as $r)
                            <tr class="border-t hover:bg-red-50">
                                <td class="px-2 py-2">{{ $r['kind'] }}</td>
                                <td class="px-2 py-2">#{{ $r['session_id'] }}</td>
                                <td class="px-2 py-2">{{ $r['status'] }}</td>
                                <td class="px-2 py-2 font-mono text-gray-600">{{ $r['scheduled_at'] }}</td>
                                <td class="px-2 py-2 font-mono text-gray-600">{{ $r['subscription_counted_at'] ?? '—' }}</td>
                                <td class="px-2 py-2">#{{ $r['cycle_id'] ?? '—' }}</td>
                                <td class="px-2 py-2 text-gray-700">{{ $r['recommended'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- ============= ATTENDANCE DRIFT — MEDIUM RISK ============= --}}
    <div class="bg-white shadow rounded-lg p-6 mb-6 border-r-4 border-orange-500">
        <h2 class="text-lg font-semibold mb-2 text-orange-700">
            ⚠️ meeting_attendances counted_at مع غياب صف consumption — إجمالي: {{ number_format($totals['attendance_drift_total']) }} (عرض أول {{ count($attendanceDrift) }})
        </h2>
        <p class="text-sm text-gray-600 mb-4">
            صف الحضور علَّم نفسه على أنه "مُحتسَب" لكن لا يوجد صف consumption مطابق.
            عادةً نتيجة كاتب قديم لم يحدّث الكتابة المزدوجة بعد. القرار: تأكيد ما إذا كانت الحصة جرت
            (إنشاء صف consumption) أو مسح العَلَم.
        </p>

        @if (count($attendanceDrift) === 0)
            <p class="text-green-700 text-sm">لا توجد حالات.</p>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-xs border">
                    <thead class="bg-gray-100 text-gray-700">
                        <tr>
                            <th class="px-2 py-2 text-right">attendance</th>
                            <th class="px-2 py-2 text-right">الحصة</th>
                            <th class="px-2 py-2 text-right">نوع</th>
                            <th class="px-2 py-2 text-right">المستخدم</th>
                            <th class="px-2 py-2 text-right">counted_at</th>
                            <th class="px-2 py-2 text-right">القرار المقترح</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($attendanceDrift as $r)
                            <tr class="border-t hover:bg-orange-50">
                                <td class="px-2 py-2">#{{ $r['attendance_id'] }}</td>
                                <td class="px-2 py-2">#{{ $r['session_id'] }}</td>
                                <td class="px-2 py-2">{{ $r['session_type'] }}</td>
                                <td class="px-2 py-2">#{{ $r['user_id'] }}</td>
                                <td class="px-2 py-2 font-mono text-gray-600">{{ $r['counted_at'] }}</td>
                                <td class="px-2 py-2 text-gray-700">{{ $r['recommended'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- ============= CYCLES PENDING V2 MIGRATION ============= --}}
    <div class="bg-white shadow rounded-lg p-6 mb-6 border-r-4 border-yellow-500">
        <h2 class="text-lg font-semibold mb-2 text-yellow-700">
            🟡 دورات لم تُهاجَر بعد (v2_consumption_complete=false/null) — أول 200
        </h2>
        <p class="text-sm text-gray-600 mb-4">
            هذه الدورات لم تُحدَّث إلى المسار الجديد. إسقاط <code>v2_consumption_complete</code> الآن
            سيكسر منطق <code>SubscriptionReconciler</code> الذي يتفرع حسب قيمة هذا العمود. يجب أولاً
            إكمال الهجرة أو تعديل كود الـ reconciler ليتوقف عن الاعتماد على العمود.
        </p>

        @if (count($cyclesPendingV2Migration) === 0)
            <p class="text-green-700 text-sm">جميع الدورات v2_complete. آمن لإسقاط العمود (بعد تعديل reconciler).</p>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-xs border">
                    <thead class="bg-gray-100 text-gray-700">
                        <tr>
                            <th class="px-2 py-2 text-right">الدورة</th>
                            <th class="px-2 py-2 text-right">الاشتراك</th>
                            <th class="px-2 py-2 text-right">الحالة</th>
                            <th class="px-2 py-2 text-right">الدفع</th>
                            <th class="px-2 py-2 text-right">حصص</th>
                            <th class="px-2 py-2 text-right">تاريخ الإنشاء</th>
                            <th class="px-2 py-2 text-right">المخاطرة</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($cyclesPendingV2Migration as $r)
                            <tr class="border-t hover:bg-yellow-50">
                                <td class="px-2 py-2">#{{ $r['cycle_id'] }}</td>
                                <td class="px-2 py-2">{{ $r['subscribable'] }}</td>
                                <td class="px-2 py-2">{{ $r['state'] }}</td>
                                <td class="px-2 py-2">{{ $r['payment'] }}</td>
                                <td class="px-2 py-2 font-mono">{{ $r['used_of_total'] }}</td>
                                <td class="px-2 py-2 font-mono text-gray-600">{{ $r['created'] }}</td>
                                <td class="px-2 py-2 text-gray-700">{{ $r['risk_reason'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="text-xs text-gray-500 mt-2">عرض أول 200 صف من إجمالي {{ number_format($summary['cycles_v2_pending']) }}.</p>
        @endif
    </div>

    {{-- ============= LOW-RISK DRIFT ============= --}}
    <div class="bg-white shadow rounded-lg p-6 mb-6 border-r-4 border-green-500">
        <h2 class="text-lg font-semibold mb-2 text-green-700">
            🟢 صفوف consumption موجودة لكن العَلَم القديم لم يُكتب ({{ count($driftConsumptionNotLegacy) }})
        </h2>
        <p class="text-sm text-gray-600 mb-4">
            هذه إشارة معلوماتية فقط — الجدول الجديد هو المرجع، والعَلَم القديم متخلِّف عن الكتابة (كاتب
            تخطّى الكتابة المزدوجة). آمن لإسقاط العمود.
        </p>

        @if (count($driftConsumptionNotLegacy) === 0)
            <p class="text-green-700 text-sm">لا توجد حالات. الكتابة المزدوجة متّسقة.</p>
        @else
            <details class="text-xs">
                <summary class="cursor-pointer text-gray-700">عرض أول {{ count($driftConsumptionNotLegacy) }} صف</summary>
                <table class="min-w-full text-xs border mt-2">
                    <thead class="bg-gray-100 text-gray-700">
                        <tr>
                            <th class="px-2 py-2 text-right">النوع</th>
                            <th class="px-2 py-2 text-right">الحصة</th>
                            <th class="px-2 py-2 text-right">الحالة</th>
                            <th class="px-2 py-2 text-right">consumed_at</th>
                            <th class="px-2 py-2 text-right">الدورة</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($driftConsumptionNotLegacy as $r)
                            <tr class="border-t">
                                <td class="px-2 py-2">{{ $r['kind'] }}</td>
                                <td class="px-2 py-2">#{{ $r['session_id'] }}</td>
                                <td class="px-2 py-2">{{ $r['status'] }}</td>
                                <td class="px-2 py-2 font-mono text-gray-600">{{ $r['consumed_at'] }}</td>
                                <td class="px-2 py-2">#{{ $r['cycle_id'] ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </details>
        @endif
    </div>

    <div class="mt-6 p-4 bg-gray-50 border rounded text-xs text-gray-600">
        <strong>هذه الصفحة مؤقّتة.</strong>
        تُستخدم فقط أثناء التحضير لإسقاط الأعمدة القديمة. بعد إكمال Phase 4،
        تُحذَف هذه الصفحة والتحكم بها من المسارات. لا تُعدِّل أي بيانات من هنا — جميع
        الإصلاحات يدوية عبر صفحات الاشتراكات/الحصص الموجودة.
    </div>
</div>
@endsection
