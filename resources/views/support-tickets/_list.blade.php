@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';
    $routePrefix = auth()->user()->isStudent() ? 'student.support' : 'teacher.support';
@endphp

<!-- Page Header -->
<div class="mb-6 md:mb-8">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-900">{{ __('support.my_tickets') }}</h1>
            <p class="mt-1 text-sm text-gray-600">{{ __('support.page_title') }}</p>
        </div>
        <a href="{{ route($routePrefix . '.create', ['subdomain' => $subdomain]) }}"
           class="inline-flex items-center gap-2 px-4 py-2.5 bg-emerald-600 text-white rounded-xl hover:bg-emerald-700 transition-colors text-sm font-medium">
            <i class="ri-add-line text-lg"></i>
            {{ __('support.new_ticket') }}
        </a>
    </div>
</div>

<!-- Tickets List -->
@if($tickets->count() > 0)
    <div class="space-y-3">
        @foreach($tickets as $ticket)
            <a href="{{ route($routePrefix . '.show', ['subdomain' => $subdomain, 'ticket' => $ticket]) }}"
               class="block bg-white rounded-xl shadow-sm border border-gray-100 p-4 md:p-5 hover:shadow-md transition-shadow">
                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                    <div class="flex-1 min-w-0">
                        <div class="flex flex-wrap items-center gap-2 mb-2">
                            <!-- Reason Badge -->
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium {{ $ticket->reason->color() }}">
                                <i class="{{ $ticket->reason->icon() }} text-sm"></i>
                                {{ $ticket->reason->label() }}
                            </span>
                            <!-- Status Badge -->
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium {{ $ticket->status->badgeClass() }}">
                                <i class="{{ $ticket->status->icon() }} text-sm"></i>
                                {{ $ticket->status->label() }}
                            </span>
                        </div>
                        <p class="text-sm text-gray-700 line-clamp-2">{{ $ticket->description }}</p>
                    </div>
                    <div class="flex flex-row sm:flex-col items-center sm:items-end gap-2 text-xs text-gray-500 shrink-0">
                        <span>{{ $ticket->created_at->diffForHumans() }}</span>
                        @if($ticket->replies_count > 0)
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-blue-50 text-blue-700 rounded-full">
                                <i class="ri-chat-3-line"></i>
                                {{ __('support.replies_count', ['count' => $ticket->replies_count]) }}
                            </span>
                        @endif
                    </div>
                </div>
            </a>
        @endforeach
    </div>

    <div class="mt-6">
        {{ $tickets->links() }}
    </div>
@else
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8 md:p-12 text-center">
        <div class="w-16 h-16 mx-auto mb-4 bg-gray-100 rounded-full flex items-center justify-center">
            <i class="ri-customer-service-2-line text-2xl text-gray-400"></i>
        </div>
        <h3 class="text-lg font-semibold text-gray-700 mb-2">{{ __('support.no_tickets') }}</h3>
        <p class="text-sm text-gray-500 mb-6">{{ __('support.no_tickets_description') }}</p>
        <a href="{{ route($routePrefix . '.create', ['subdomain' => $subdomain]) }}"
           class="inline-flex items-center gap-2 px-4 py-2.5 bg-emerald-600 text-white rounded-xl hover:bg-emerald-700 transition-colors text-sm font-medium">
            <i class="ri-add-line text-lg"></i>
            {{ __('support.new_ticket') }}
        </a>
    </div>
@endif
