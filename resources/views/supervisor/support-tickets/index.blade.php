<x-layouts.supervisor>

@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';

    $activeFilters = array_filter(
        array_merge($filters, ['page' => request('page')]),
        fn ($v) => $v !== null && $v !== '',
    );

    $hasUserFilter = ! empty($filters['user_id']) && ($filterUser ?? null) !== null;
    $clearUserFilterUrl = $hasUserFilter
        ? request()->fullUrlWithQuery(['user_id' => null, 'page' => null])
        : null;
@endphp

<div>
    <x-ui.breadcrumb
        :items="[['label' => __('support.supervisor.page_title')]]"
        view-type="supervisor"
    />

    <!-- Page Header -->
    <div class="mb-6 md:mb-8">
        <h1 class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-900">{{ __('support.supervisor.page_title') }}</h1>
        <p class="mt-1 text-sm text-gray-600">{{ __('support.supervisor.page_description') }}</p>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 md:gap-4 mb-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                    <i class="ri-customer-service-2-line text-blue-600"></i>
                </div>
                <div>
                    <p class="text-xl font-bold text-gray-900">{{ $stats['total'] }}</p>
                    <p class="text-xs text-gray-600">{{ __('support.supervisor.all_tickets') }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                    <i class="ri-chat-new-line text-green-600"></i>
                </div>
                <div>
                    <p class="text-xl font-bold text-gray-900">{{ $stats['open'] }}</p>
                    <p class="text-xs text-gray-600">{{ __('support.supervisor.open_tickets') }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center">
                    <i class="ri-check-double-line text-gray-600"></i>
                </div>
                <div>
                    <p class="text-xl font-bold text-gray-900">{{ $stats['closed'] }}</p>
                    <p class="text-xs text-gray-600">{{ __('support.supervisor.closed_tickets') }}</p>
                </div>
            </div>
        </div>
    </div>

    @if($hasUserFilter)
        <div class="mb-4 inline-flex items-center gap-2 px-3 py-2 bg-emerald-50 border border-emerald-200 rounded-lg text-sm text-emerald-800">
            <i class="ri-filter-3-line"></i>
            <span>{{ __('support.supervisor.filtered_by_user', ['name' => $filterUser->name]) }}</span>
            <a href="{{ $clearUserFilterUrl }}"
               class="ms-1 inline-flex items-center justify-center w-5 h-5 rounded-full bg-emerald-100 hover:bg-emerald-200 text-emerald-700 transition-colors"
               title="{{ __('support.supervisor.clear_filter') }}"
               aria-label="{{ __('support.supervisor.clear_filter') }}">
                <i class="ri-close-line text-xs"></i>
            </a>
        </div>
    @endif

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-6">
        <form method="GET" action="{{ route('manage.support-tickets.index', ['subdomain' => $subdomain]) }}" class="flex flex-wrap gap-3">
            @if(! empty($filters['user_id']))
                <input type="hidden" name="user_id" value="{{ $filters['user_id'] }}">
            @endif
            <div class="flex-1 min-w-[200px]">
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}"
                       placeholder="{{ __('support.supervisor.search_placeholder') }}"
                       class="w-full rounded-lg border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 text-sm">
            </div>
            <select name="status" class="rounded-lg border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 text-sm">
                <option value="">{{ __('support.supervisor.filter_status') }}</option>
                @foreach($statuses as $value => $label)
                    <option value="{{ $value }}" {{ ($filters['status'] ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            <select name="reason" class="rounded-lg border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 text-sm">
                <option value="">{{ __('support.supervisor.filter_reason') }}</option>
                @foreach($reasons as $value => $label)
                    <option value="{{ $value }}" {{ ($filters['reason'] ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            <button type="submit" class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors text-sm font-medium">
                <i class="ri-search-line"></i>
            </button>
        </form>
    </div>

    <!-- Tickets Table -->
    @if($tickets->count() > 0)
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-100">
                        <tr>
                            <th class="px-4 py-3 text-right font-medium text-gray-600">{{ __('support.supervisor.reporter') }}</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-600">{{ __('support.supervisor.role') }}</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-600">{{ __('support.supervisor.reason') }}</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-600">{{ __('support.supervisor.status') }}</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-600">{{ __('support.supervisor.replies') }}</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-600">{{ __('support.supervisor.date') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($tickets as $ticket)
                            <tr class="hover:bg-gray-50 cursor-pointer" onclick="window.location='{{ route('manage.support-tickets.show', array_merge(['subdomain' => $subdomain, 'ticket' => $ticket], $activeFilters)) }}'">
                                <td class="px-4 py-3 font-medium text-gray-900">{{ $ticket->user->name }}</td>
                                <td class="px-4 py-3">
                                    <span class="text-xs text-gray-500">{{ $ticket->user->getUserTypeLabel() }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium {{ $ticket->reason->color() }}">
                                        <i class="{{ $ticket->reason->icon() }}"></i>
                                        {{ $ticket->reason->label() }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium {{ $ticket->status->badgeClass() }}">
                                        {{ $ticket->status->label() }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-gray-500">{{ $ticket->replies_count }}</td>
                                <td class="px-4 py-3 text-xs text-gray-500">{{ $ticket->created_at->diffForHumans() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-6">
            {{ $tickets->withQueryString()->links() }}
        </div>
    @else
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8 md:p-12 text-center">
            <div class="w-16 h-16 mx-auto mb-4 bg-gray-100 rounded-full flex items-center justify-center">
                <i class="ri-customer-service-2-line text-2xl text-gray-400"></i>
            </div>
            <h3 class="text-lg font-semibold text-gray-700 mb-2">{{ __('support.supervisor.no_tickets') }}</h3>
            <p class="text-sm text-gray-500">{{ __('support.supervisor.no_tickets_description') }}</p>
        </div>
    @endif

    <!-- Settings Section (Admin Only) -->
    @if($isAdmin && $contactFormSettings !== null)
        <div class="mt-8 bg-white rounded-xl shadow-sm border border-gray-100 p-4 md:p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-1">{{ __('support.supervisor.settings_title') }}</h3>
            <p class="text-sm text-gray-500 mb-4">{{ __('support.supervisor.settings_description') }}</p>

            <form action="{{ route('manage.support-tickets.settings.update', ['subdomain' => $subdomain]) }}" method="POST">
                @csrf

                <!-- Toggle -->
                <div class="mb-4">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" name="support_contact_form_enabled" value="1"
                               {{ $contactFormSettings['enabled'] ? 'checked' : '' }}
                               class="rounded border-gray-300 text-emerald-600 shadow-sm focus:ring-emerald-500">
                        <span class="text-sm font-medium text-gray-700">{{ __('support.supervisor.form_enabled') }}</span>
                    </label>
                </div>

                <!-- Arabic Message -->
                <div class="mb-4">
                    <label for="message_ar" class="block text-sm font-medium text-gray-700 mb-1">{{ __('support.supervisor.message_ar_label') }}</label>
                    <textarea name="support_contact_form_message_ar" id="message_ar" rows="3" dir="rtl"
                              class="w-full rounded-lg border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 text-sm">{{ $contactFormSettings['message_ar'] }}</textarea>
                </div>

                <!-- English Message -->
                <div class="mb-4">
                    <label for="message_en" class="block text-sm font-medium text-gray-700 mb-1">{{ __('support.supervisor.message_en_label') }}</label>
                    <textarea name="support_contact_form_message_en" id="message_en" rows="3" dir="ltr"
                              class="w-full rounded-lg border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 text-sm">{{ $contactFormSettings['message_en'] }}</textarea>
                </div>

                <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors text-sm font-medium">
                    <i class="ri-save-line"></i>
                    {{ __('support.supervisor.save_settings') }}
                </button>
            </form>
        </div>
    @endif
</div>

</x-layouts.supervisor>
