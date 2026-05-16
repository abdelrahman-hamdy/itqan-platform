@extends('layouts.supervisor')

@section('title', 'مراجعة الحصص المُستهلكة خارج المنصة')

@section('content')
<div class="container mx-auto px-4 py-6" dir="rtl">

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">مراجعة الحصص المُستهلكة خارج المنصة</h1>
        <p class="text-sm text-gray-600 mt-2">
            هذه الاشتراكات أُنشِئت يدوياً بواسطة الإدارة ولم نتمكن من الحفاظ على عدد الحصص
            المُستهلكة قبل دخول المنصة. الرجاء إدخال العدد الصحيح لكل اشتراك (إن لم تكن
            هناك حصص سابقة، اترك القيمة <strong>0</strong> واضغط حفظ).
        </p>
        <p class="text-sm text-gray-500 mt-1">
            بعد الحفظ، يُحذف الاشتراك تلقائياً من هذه القائمة وتُحدَّث حالته الفعلية.
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
        إجمالي الاشتراكات المعلقة: <strong>{{ count($subs) }}</strong>
    </div>

    @if (count($subs) === 0)
        <div class="bg-green-50 border border-green-200 rounded-lg p-8 text-center">
            <div class="text-3xl">✓</div>
            <div class="text-lg font-semibold text-green-800 mt-2">جميع الاشتراكات تمت مراجعتها</div>
            <div class="text-sm text-green-700 mt-1">لا توجد اشتراكات تحتاج إدخال يدوي.</div>
        </div>
    @else
        <div class="space-y-3">
            @foreach ($subs as $row)
                <div class="bg-white shadow rounded-lg p-4 border-r-4 border-blue-400">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        {{-- Left: sub info --}}
                        <div class="flex-1 min-w-[280px]">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="text-base font-semibold text-gray-900">
                                    اشتراك #{{ $row['sub_id'] }}
                                </span>
                                <span class="inline-block px-2 py-0.5 rounded text-xs
                                    {{ $row['status'] === 'active' ? 'bg-green-100 text-green-800' : '' }}
                                    {{ $row['status'] === 'paused' ? 'bg-blue-100 text-blue-800' : '' }}
                                    {{ $row['status'] === 'expired' ? 'bg-gray-100 text-gray-800' : '' }}
                                    {{ $row['status'] === 'cancelled' ? 'bg-red-100 text-red-800' : '' }}
                                    {{ $row['status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}">
                                    {{ $row['status'] }}
                                </span>
                                <span class="inline-block px-2 py-0.5 rounded text-xs bg-gray-100 text-gray-700">
                                    دفع: {{ $row['cycle_payment_status'] }}
                                </span>
                            </div>
                            <div class="text-sm text-gray-700">
                                <strong>الطالب:</strong> {{ $row['student_name'] }} (#{{ $row['student_id'] }})
                                @if ($row['teacher_name'])
                                    &nbsp;·&nbsp; <strong>المعلم:</strong> {{ $row['teacher_name'] }}
                                @endif
                            </div>
                            <div class="text-xs text-gray-500 mt-1 font-mono">
                                إجمالي: {{ $row['total_sessions'] }} حصة
                                &nbsp;·&nbsp; مُستهلَك حالياً (داخل المنصة): {{ $row['active_consumption'] }}
                                &nbsp;·&nbsp; sub.used: {{ $row['sub_sessions_used'] }}
                                &nbsp;·&nbsp; cycle.used: {{ $row['cycle_sessions_used'] }}
                            </div>
                            <div class="text-xs text-gray-500 mt-1">
                                <strong>تاريخ الإنشاء:</strong> {{ $row['sub_created_at'] }}
                                &nbsp;·&nbsp; <strong>يبدأ:</strong> {{ $row['starts_at'] ?: '—' }}
                                &nbsp;·&nbsp; <strong>ينتهي:</strong> {{ $row['ends_at'] ?: '—' }}
                            </div>
                            <div class="mt-2">
                                <a href="{{ route('manage.subscriptions.show', ['subdomain' => $subdomain, 'type' => 'quran', 'subscription' => $row['sub_id']]) }}"
                                   target="_blank"
                                   class="inline-flex items-center gap-1 text-xs text-blue-700 hover:underline">
                                    فتح صفحة الاشتراك ↗
                                </a>
                            </div>
                        </div>

                        {{-- Right: input form --}}
                        <form method="POST"
                              action="{{ route('manage.preset-sessions-review.record', ['subdomain' => $subdomain, 'sub' => $row['sub_id']]) }}"
                              class="flex flex-col gap-2 min-w-[260px]">
                            @csrf
                            <label class="text-xs text-gray-700 font-semibold">
                                الحصص المُستهلكة قبل المنصة:
                            </label>
                            <input type="number"
                                   name="preserved_value"
                                   value="0"
                                   min="0"
                                   max="{{ max(0, $row['total_sessions'] - 1) }}"
                                   required
                                   class="w-32 px-2 py-1 border border-gray-300 rounded text-sm text-center font-mono"/>
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
        كل اشتراك أُدخل قيمته (مثلاً 0 أو 5 أو 14)، نسجل القيمة في بيانات الاشتراك بحيث لا
        تُحذف مرة أخرى. بعد ذلك يحتسب النظام: المُستهلَك الكلي = (الحصص داخل المنصة) +
        (القيمة التي أدخلتها). الباقي = الإجمالي − المُستهلَك الكلي.
    </div>
</div>
@endsection
