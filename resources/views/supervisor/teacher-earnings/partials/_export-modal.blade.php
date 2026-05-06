@php
    /**
     * Shared export modal for both teacher-earnings tabs.
     *
     * Required: $exportType ('summary' | 'details'), $exportColumns (array of column keys),
     *          $subdomain, plus the filter context vars already in scope on the parent view.
     */
    $summaryLabelKeys = [
        'teacher' => 'supervisor.teacher_earnings.summary_teacher_name',
        'quran_individual' => 'supervisor.teacher_earnings.summary_quran_individual',
        'quran_group' => 'supervisor.teacher_earnings.summary_quran_group',
        'academic' => 'supervisor.teacher_earnings.summary_academic',
        'interactive' => 'supervisor.teacher_earnings.summary_interactive',
        'sessions' => 'supervisor.teacher_earnings.summary_sessions_count',
        'hours' => 'supervisor.teacher_earnings.summary_total_hours',
        'total' => 'supervisor.teacher_earnings.summary_total',
    ];
    $detailsLabelKeys = [
        'teacher' => 'supervisor.teacher_earnings.details_col_teacher',
        'source_type' => 'supervisor.teacher_earnings.details_col_source_type',
        'source_name' => 'supervisor.teacher_earnings.details_col_source_name',
        'session_date' => 'supervisor.teacher_earnings.details_col_session_date',
        'earning_month' => 'supervisor.teacher_earnings.details_col_earning_month',
        'duration' => 'supervisor.teacher_earnings.details_col_duration',
        'calculation_method' => 'supervisor.teacher_earnings.details_col_calculation_method',
        'amount' => 'supervisor.teacher_earnings.details_col_amount',
        'status' => 'supervisor.teacher_earnings.details_col_status',
        'dispute_notes' => 'supervisor.teacher_earnings.details_col_dispute_notes',
    ];
    $labelKeys = $exportType === 'details' ? $detailsLabelKeys : $summaryLabelKeys;
@endphp

<div id="export-modal" class="hidden fixed inset-0 z-[9999] overflow-y-auto">
    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" onclick="document.getElementById('export-modal').classList.add('hidden')"></div>
    <div class="fixed inset-0 flex items-end md:items-center justify-center p-0 md:p-4">
        <div class="relative bg-white w-full md:max-w-lg rounded-t-2xl md:rounded-2xl shadow-2xl overflow-hidden max-h-[92vh] flex flex-col" onclick="event.stopPropagation()">
            <div class="md:hidden absolute top-2 left-1/2 -translate-x-1/2 w-10 h-1 rounded-full bg-gray-300 z-10"></div>
            <div class="p-6 pb-4 pt-8 md:pt-6 overflow-y-auto" x-data="{ allChecked: true }">
                <div class="mx-auto flex items-center justify-center w-14 h-14 rounded-full bg-green-100 mb-4">
                    <i class="ri-file-download-line text-2xl text-green-600"></i>
                </div>
                <h3 class="text-lg font-bold text-center text-gray-900 mb-1">{{ __('supervisor.teacher_earnings.export_title') }}</h3>
                <p class="text-center text-xs text-gray-500 mb-4">
                    @if($exportType === 'details')
                        {{ __('supervisor.teacher_earnings.export_type_details') }}
                    @else
                        {{ __('supervisor.teacher_earnings.export_type_summary') }}
                    @endif
                </p>

                <form method="POST"
                    action="{{ route('manage.teacher-earnings.export', ['subdomain' => $subdomain]) }}"
                    @submit.prevent="
                        const checked = $el.querySelectorAll('input[name=&quot;columns[]&quot;]:checked').length;
                        if (checked === 0) { alert('{{ __('supervisor.teacher_earnings.export_columns_required') }}'); return; }
                        $el.submit();
                    ">
                    @csrf

                    <input type="hidden" name="export_type" value="{{ $exportType }}">

                    {{-- Pass current filter state --}}
                    <input type="hidden" name="month" value="{{ $currentMonth ?? '' }}">
                    @foreach($currentTeacherIds ?? [] as $tid)
                        <input type="hidden" name="teacher_ids[]" value="{{ $tid }}">
                    @endforeach
                    <input type="hidden" name="start_date" value="{{ $startDate ?? '' }}">
                    <input type="hidden" name="end_date" value="{{ $endDate ?? '' }}">
                    @if($exportType === 'summary')
                        <input type="hidden" name="teacher_type" value="{{ $currentTeacherType ?? '' }}">
                        <input type="hidden" name="gender" value="{{ $currentGender ?? '' }}">
                    @else
                        <input type="hidden" name="status" value="{{ $currentStatus ?? 'all' }}">
                    @endif

                    {{-- Export format selection --}}
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('supervisor.teacher_earnings.export_format_label') }}</label>
                        <div class="flex items-center gap-4">
                            <label class="flex items-center gap-2 cursor-pointer px-3 py-2 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                                <input type="radio" name="format" value="pdf" checked class="text-green-600 focus:ring-green-500">
                                <span class="text-sm text-gray-700 flex items-center gap-1.5">
                                    <i class="ri-file-pdf-2-line text-red-500"></i>
                                    {{ __('supervisor.teacher_earnings.export_format_pdf') }}
                                </span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer px-3 py-2 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                                <input type="radio" name="format" value="excel" class="text-green-600 focus:ring-green-500">
                                <span class="text-sm text-gray-700 flex items-center gap-1.5">
                                    <i class="ri-file-excel-2-line text-green-600"></i>
                                    {{ __('supervisor.teacher_earnings.export_format_excel') }}
                                </span>
                            </label>
                        </div>
                    </div>

                    {{-- Column selection --}}
                    <div class="mb-4">
                        <div class="flex items-center justify-between mb-2">
                            <label class="block text-sm font-medium text-gray-700">{{ __('supervisor.teacher_earnings.export_columns_label') }}</label>
                            <div class="flex items-center gap-2 text-xs">
                                <button type="button"
                                    @click="$root.querySelectorAll('input[name=&quot;columns[]&quot;]').forEach(c => c.checked = true); allChecked = true;"
                                    class="cursor-pointer text-indigo-600 hover:text-indigo-700 font-medium">
                                    {{ __('supervisor.teacher_earnings.export_columns_select_all') }}
                                </button>
                                <span class="text-gray-300">|</span>
                                <button type="button"
                                    @click="$root.querySelectorAll('input[name=&quot;columns[]&quot;]').forEach(c => c.checked = false); allChecked = false;"
                                    class="cursor-pointer text-gray-600 hover:text-gray-700 font-medium">
                                    {{ __('supervisor.teacher_earnings.export_columns_clear_all') }}
                                </button>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 p-3 bg-gray-50 rounded-lg border border-gray-200">
                            @foreach($exportColumns as $col)
                                <label class="flex items-center gap-2 cursor-pointer px-2 py-1.5 rounded hover:bg-white transition-colors">
                                    <input type="checkbox" name="columns[]" value="{{ $col }}" checked
                                        class="rounded border-gray-300 text-green-600 focus:ring-green-500 cursor-pointer">
                                    <span class="text-sm text-gray-700">{{ __($labelKeys[$col]) }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- Active filters summary --}}
                    <div class="mb-4 p-3 bg-gray-50 rounded-lg text-sm text-gray-600">
                        <p class="font-medium text-gray-700 mb-1">{{ __('supervisor.teacher_earnings.export_current_filters') }}:</p>
                        <ul class="space-y-1 text-xs">
                            <li>
                                <span class="text-gray-500">{{ __('supervisor.teacher_earnings.filter_teacher') }}:</span>
                                @if(!empty($currentTeacherIds))
                                    @php $selectedNames = collect($teachers)->whereIn('id', $currentTeacherIds)->pluck('name')->implode('، '); @endphp
                                    <span class="font-medium">{{ $selectedNames }}</span>
                                @else
                                    <span>{{ __('supervisor.teacher_earnings.export_all_teachers') }}</span>
                                @endif
                            </li>
                            <li>
                                <span class="text-gray-500">{{ __('supervisor.teacher_earnings.export_period_label') }}:</span>
                                @if(($startDate ?? null) || ($endDate ?? null))
                                    <span class="font-medium">{{ $startDate ?? '...' }} - {{ $endDate ?? '...' }}</span>
                                @elseif($currentMonth ?? null)
                                    @php
                                        $selectedMonth = collect($availableMonths)->firstWhere('value', $currentMonth);
                                    @endphp
                                    <span class="font-medium">{{ $selectedMonth['label'] ?? $currentMonth }}</span>
                                @else
                                    <span>{{ __('supervisor.teacher_earnings.export_all_periods') }}</span>
                                @endif
                            </li>
                        </ul>
                    </div>

                    <div class="flex flex-col-reverse md:flex-row gap-3 md:justify-end">
                        <button type="button" onclick="document.getElementById('export-modal').classList.add('hidden')"
                            class="cursor-pointer min-h-[44px] px-6 py-2.5 text-sm font-semibold text-gray-700 bg-white hover:bg-gray-100 border border-gray-300 rounded-xl transition-colors">
                            {{ __('common.actions.cancel') }}
                        </button>
                        <button type="submit"
                            class="cursor-pointer min-h-[44px] px-6 py-2.5 text-sm font-semibold text-white bg-green-600 hover:bg-green-700 rounded-xl transition-colors inline-flex items-center justify-center gap-2">
                            <i class="ri-download-line"></i>
                            {{ __('supervisor.teacher_earnings.export_download') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
