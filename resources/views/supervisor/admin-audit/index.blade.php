<x-layouts.supervisor>

@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';
    $categoryLabels = [
        'inv_d2_drift_payment_mismatch' => 'INV-D2 — السعر/الدفع غير متطابق',
        'inv_d2_drift_ambiguous' => 'INV-D2 — غير محدد (لا توجد باقة فريدة مطابقة)',
        'inv_d2_free_not_override' => 'INV-D2 — اشتراك مجاني بدون تجاوز رسمي',
        'inv_d2_orphan_package' => 'INV-D2 — اشتراك بدون باقة مرتبطة',
        'paused_no_audit_corrupt' => 'اشتراكات موقوفة بحالة غير سليمة',
    ];
@endphp

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6" dir="rtl">

    <div class="mb-6 flex items-start justify-between gap-4 flex-wrap">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">مراجعة قرارات الاشتراكات</h1>
            <p class="text-sm text-gray-600 mt-1">
                صفحة مؤقتة لجمع قرارات الإدارة على الحالات التي تحتاج تدخل بشري.
                القرارات تُحفظ في قاعدة البيانات للمعالجة لاحقاً.
            </p>
        </div>
        <div class="flex items-center gap-2 shrink-0">
            @if(($appliedCount ?? 0) > 0)
                @if($includeApplied ?? false)
                    <a href="{{ url()->current() }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm rounded-lg bg-emerald-100 text-emerald-800 border border-emerald-300 hover:bg-emerald-200">
                        <i class="ri-eye-off-line"></i>
                        إخفاء الحالات المُطبَّقة ({{ $appliedCount }})
                    </a>
                @else
                    <a href="{{ url()->current() }}?show_applied=1" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm rounded-lg bg-white text-gray-700 border border-gray-300 hover:bg-gray-50">
                        <i class="ri-history-line"></i>
                        إظهار الحالات المُطبَّقة ({{ $appliedCount }})
                    </a>
                @endif
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-4 py-2 rounded">
            {{ session('success') }}
        </div>
    @endif

    {{-- Top summary --}}
    <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-6">
        @foreach($categoryLabels as $type => $label)
            @php
                $tot = $totals[$type] ?? 0;
                $dec = $decided[$type] ?? 0;
                $pct = $tot > 0 ? round(($dec / $tot) * 100) : 0;
            @endphp
            <a href="#section-{{ $type }}" class="bg-white border border-gray-200 rounded-lg p-3 hover:bg-gray-50 block">
                <div class="text-xs text-gray-500 mb-1">{{ $label }}</div>
                <div class="text-2xl font-bold text-gray-900">{{ $dec }} / {{ $tot }}</div>
                <div class="text-xs {{ $pct === 100 ? 'text-green-600' : ($pct > 0 ? 'text-blue-600' : 'text-gray-400') }}">
                    {{ $pct }}% مكتمل
                </div>
            </a>
        @endforeach
    </div>

    @foreach($categoryLabels as $type => $label)
        @php $bucket = $cases[$type] ?? []; @endphp
        @if(count($bucket) === 0) @continue @endif

        <section id="section-{{ $type }}" class="mb-10 bg-white shadow-sm border border-gray-200 rounded-lg">
            <header class="px-5 py-3 border-b border-gray-200 bg-gray-50 rounded-t-lg flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-800">{{ $label }}</h2>
                <span class="text-sm text-gray-500">{{ count($bucket) }} حالة</span>
            </header>

            <div class="divide-y divide-gray-100">
                @foreach($bucket as $case)
                    <div id="{{ $case['case_key'] }}" class="px-5 py-4 {{ $case['decision'] ? 'bg-green-50/30' : '' }}">

                        {{-- Case header --}}
                        <div class="flex items-start justify-between gap-3 mb-3">
                            <div class="flex-1">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="text-sm font-semibold text-gray-900">
                                        @if(isset($case['cycle_id']))
                                            Cycle #{{ $case['cycle_id'] }}
                                        @else
                                            Sub #{{ $case['sub_id'] }}
                                        @endif
                                    </span>
                                    <span class="text-xs text-gray-400">•</span>
                                    <span class="text-sm text-gray-700">{{ $case['student'] ?? '?' }}</span>
                                    @if(!empty($case['teacher']) && $case['teacher'] !== '?')
                                        <span class="text-xs text-gray-400">•</span>
                                        <span class="text-sm text-gray-600">{{ $case['teacher'] }}</span>
                                    @endif
                                    @if($case['decision'])
                                        <span class="inline-flex items-center gap-1 text-xs bg-green-100 text-green-800 px-2 py-0.5 rounded">
                                            ✓ تم القرار
                                        </span>
                                    @endif
                                </div>
                            </div>
                            @if(!empty($case['sub_url']) && $case['sub_url'] !== '#')
                                <a href="{{ $case['sub_url'] }}" target="_blank" rel="noopener"
                                   class="text-xs text-blue-600 hover:text-blue-800 underline whitespace-nowrap">
                                    فتح صفحة الاشتراك ↗
                                </a>
                            @endif
                        </div>

                        {{-- Case data — varies per type --}}
                        <div class="bg-gray-50 border border-gray-200 rounded p-3 mb-3 text-sm space-y-1 text-gray-800">
                            @if($type === 'inv_d2_drift_payment_mismatch' || $type === 'inv_d2_drift_ambiguous')
                                <div>
                                    <strong>السعر المسجل على الدورة:</strong>
                                    {{ number_format($case['final_price'], 2) }} SAR
                                    (خصم {{ number_format($case['discount'], 2) }})
                                </div>
                                <div>
                                    <strong>عدد الحصص في الدورة:</strong> {{ $case['total_sessions'] }}
                                    • <strong>نوع الفوترة:</strong> {{ $case['billing_cycle'] }}
                                    • <strong>حالة الدورة:</strong> {{ $case['cycle_state'] }}
                                </div>
                                <div>
                                    <strong>الباقة الحالية على الاشتراك:</strong>
                                    @if($case['current_pkg'])
                                        #{{ $case['current_pkg']['id'] }} {{ $case['current_pkg']['name'] }}
                                        ({{ $case['current_pkg']['sessions'] }}×{{ $case['current_pkg']['duration'] }}min)
                                        سعر: {{ $case['current_pkg']['price'] }}, تخفيض: {{ $case['current_pkg']['sale_price'] ?? '—' }}
                                    @else
                                        لا توجد باقة
                                    @endif
                                </div>
                                @if(!empty($case['matching_pkgs']))
                                    <div>
                                        <strong>الباقات المطابقة بالسعر+الحصص:</strong>
                                        @foreach($case['matching_pkgs'] as $p)
                                            <span class="inline-block bg-white border border-gray-300 px-2 py-0.5 rounded text-xs ml-1">
                                                #{{ $p['id'] }} {{ $p['sessions'] }}×{{ $p['duration'] }}min
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                                <div>
                                    <strong>المدفوعات المرتبطة:</strong>
                                    @if(count($case['payments']) === 0)
                                        <span class="text-gray-500">لا توجد مدفوعات</span>
                                    @else
                                        <ul class="list-disc list-inside text-xs mt-1">
                                            @foreach($case['payments'] as $p)
                                                <li>
                                                    #{{ $p['id'] }} — {{ number_format($p['amount'], 2) }} SAR — {{ $p['status'] }} — {{ $p['gateway'] }} — {{ $p['created_at'] }}
                                                </li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </div>

                            @elseif($type === 'inv_d2_free_not_override')
                                <div>
                                    <strong>السعر على الدورة:</strong> 0 SAR
                                    • <strong>سعر الباقة الحي:</strong> {{ number_format($case['live_pkg_price'], 2) }} SAR
                                </div>
                                <div>
                                    <strong>اشتراك برعاية:</strong> {{ $case['is_sponsored'] ? 'نعم' : 'لا' }}
                                    @if(!empty($case['sponsorship_reason']))
                                        — السبب: {{ $case['sponsorship_reason'] }}
                                    @endif
                                    • <strong>تجريبي:</strong> {{ $case['is_trial'] ? 'نعم' : 'لا' }}
                                </div>
                                <div>
                                    <strong>المدفوعات:</strong>
                                    @if(count($case['payments']) === 0)
                                        <span class="text-gray-500">لا توجد</span>
                                    @else
                                        @foreach($case['payments'] as $p)
                                            <span class="inline-block bg-white border border-gray-300 px-2 py-0.5 rounded text-xs ml-1">
                                                {{ number_format($p['amount'], 2) }} ({{ $p['status'] }})
                                            </span>
                                        @endforeach
                                    @endif
                                </div>

                            @elseif($type === 'inv_d2_orphan_package')
                                <div>
                                    <strong>السعر على الدورة:</strong> {{ number_format($case['final_price'], 2) }} SAR
                                    • <strong>عدد الحصص:</strong> {{ $case['total_sessions'] }}
                                    • <strong>الفوترة:</strong> {{ $case['billing_cycle'] }}
                                </div>
                                <div>
                                    <strong>package_id على الاشتراك:</strong> {{ $case['sub_package_id_field'] ?? 'NULL' }}
                                    (الباقة المرتبطة غير موجودة)
                                </div>
                                @if(!empty($case['matching_pkgs']))
                                    <div>
                                        <strong>باقات مرشحة (بنفس السعر):</strong>
                                        @foreach($case['matching_pkgs'] as $p)
                                            <span class="inline-block bg-white border border-gray-300 px-2 py-0.5 rounded text-xs ml-1">
                                                #{{ $p['id'] }} {{ $p['sessions'] }}×{{ $p['duration'] }}min
                                            </span>
                                        @endforeach
                                    </div>
                                @endif

                            @elseif($type === 'paused_no_audit_corrupt')
                                <div>
                                    <strong>تاريخ الإيقاف:</strong> {{ $case['paused_at'] }}
                                    • <strong>السبب المسجل:</strong> {{ $case['pause_reason'] }}
                                </div>
                                <div>
                                    <strong>الباقة:</strong> {{ $case['pkg'] }}
                                </div>
                                <div>
                                    <strong>المدفوعات المكتملة:</strong> {{ number_format($case['completed_payments_sum'], 2) }} SAR
                                </div>
                                <div class="text-xs text-orange-700">
                                    صنف المشكلة: {{ $case['pause_bucket'] }}
                                </div>
                            @endif
                        </div>

                        {{-- Decision form --}}
                        <form method="POST" action="{{ route('manage.admin-audit.decide', ['subdomain' => $subdomain]) }}" class="space-y-3">
                            @csrf
                            <input type="hidden" name="case_key" value="{{ $case['case_key'] }}">
                            <input type="hidden" name="case_type" value="{{ $type }}">
                            <input type="hidden" name="subject_type" value="{{ $case['subject_type'] ?? '' }}">
                            <input type="hidden" name="subject_id" value="{{ $case['subject_id'] ?? '' }}">

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                @foreach($case['options'] as $optKey => $optLabel)
                                    <label class="flex items-start gap-2 p-2 border border-gray-200 rounded cursor-pointer hover:bg-gray-50 has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50">
                                        <input type="radio" name="selected_option" value="{{ $optKey }}"
                                               @if($case['decision'] && $case['decision']->selected_option === $optKey) checked @endif
                                               class="mt-1">
                                        <span class="text-sm text-gray-800">{{ $optLabel }}</span>
                                    </label>
                                @endforeach
                            </div>

                            <div>
                                <label class="block text-xs text-gray-600 mb-1">ملاحظة (اختياري)</label>
                                <textarea name="free_text" rows="2" placeholder="أضف أي تفاصيل تساعد في التنفيذ..."
                                          class="w-full border border-gray-300 rounded px-2 py-1 text-sm">{{ $case['decision']->free_text ?? '' }}</textarea>
                            </div>

                            <div class="flex items-center gap-3">
                                <button type="submit"
                                        class="bg-blue-600 hover:bg-blue-700 text-white text-sm px-4 py-1.5 rounded">
                                    حفظ القرار
                                </button>
                                @if($case['decision'])
                                    <span class="text-xs text-gray-500">
                                        آخر تحديث: {{ $case['decision']->decided_at?->diffForHumans() }}
                                        بواسطة {{ $case['decision']->decidedBy?->name ?? '?' }}
                                    </span>
                                @endif
                            </div>
                        </form>
                    </div>
                @endforeach
            </div>
        </section>
    @endforeach

    @if(collect($totals)->sum() === 0)
        <div class="bg-green-50 border border-green-200 rounded p-6 text-center">
            <div class="text-2xl mb-2">✓</div>
            <div class="text-green-800 font-semibold">لا توجد حالات تحتاج قراراً حالياً</div>
        </div>
    @endif

</div>

</x-layouts.supervisor>
