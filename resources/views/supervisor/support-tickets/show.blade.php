<x-layouts.supervisor>

@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';
    $backUrl = route('manage.support-tickets.index', array_merge(['subdomain' => $subdomain], $backFilters));
    $isStudentReporter = $ticket->user->isStudent();
    $isTeacherReporter = $ticket->user->isTeacher();
@endphp

<div>
    <x-ui.breadcrumb
        :items="[
            ['label' => __('support.supervisor.page_title'), 'route' => $backUrl],
            ['label' => __('support.ticket_detail')],
        ]"
        view-type="supervisor"
    />

    <!-- Back to all issues -->
    <a href="{{ $backUrl }}" class="inline-flex items-center gap-2 px-3 py-1.5 mb-4 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors text-sm font-medium">
        <i class="ri-arrow-right-line rtl:hidden"></i>
        <i class="ri-arrow-left-line ltr:hidden"></i>
        {{ __('support.supervisor.back_to_all') }}
    </a>

    <!-- Related actions for the reporting user -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-3 md:p-4 mb-6">
        <div class="flex flex-wrap items-center gap-2">
            <span class="text-xs text-gray-500 me-1">{{ __('support.supervisor.related_actions') }}:</span>

            @if($isStudentReporter)
                <a href="{{ route('manage.students.show', ['subdomain' => $subdomain, 'student' => $ticket->user_id]) }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-50 text-blue-700 rounded-lg hover:bg-blue-100 transition-colors text-xs font-medium">
                    <i class="ri-user-line"></i>
                    {{ __('support.supervisor.view_student_profile') }}
                </a>
                <a href="{{ route('manage.subscriptions.index', ['subdomain' => $subdomain, 'student_id' => $ticket->user_id]) }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-purple-50 text-purple-700 rounded-lg hover:bg-purple-100 transition-colors text-xs font-medium">
                    <i class="ri-bookmark-line"></i>
                    {{ __('support.supervisor.view_student_subscriptions') }}
                </a>
                @if($isAdmin)
                    <a href="{{ route('manage.payments.index', ['subdomain' => $subdomain, 'user_id' => $ticket->user_id]) }}"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-50 text-emerald-700 rounded-lg hover:bg-emerald-100 transition-colors text-xs font-medium">
                        <i class="ri-bank-card-line"></i>
                        {{ __('support.supervisor.view_student_payments') }}
                    </a>
                @endif
            @elseif($isTeacherReporter)
                <a href="{{ route('manage.teachers.show', ['subdomain' => $subdomain, 'teacher' => $ticket->user_id]) }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-50 text-blue-700 rounded-lg hover:bg-blue-100 transition-colors text-xs font-medium">
                    <i class="ri-graduation-cap-line"></i>
                    {{ __('support.supervisor.view_teacher_profile') }}
                </a>
                <a href="{{ route('manage.subscriptions.index', ['subdomain' => $subdomain, 'teacher_user_id' => $ticket->user_id]) }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-purple-50 text-purple-700 rounded-lg hover:bg-purple-100 transition-colors text-xs font-medium">
                    <i class="ri-bookmark-line"></i>
                    {{ __('support.supervisor.view_teacher_subscriptions') }}
                </a>
                <a href="{{ route('manage.sessions.index', ['subdomain' => $subdomain, 'teacher_id' => $ticket->user_id]) }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-50 text-indigo-700 rounded-lg hover:bg-indigo-100 transition-colors text-xs font-medium">
                    <i class="ri-calendar-event-line"></i>
                    {{ __('support.supervisor.view_teacher_sessions') }}
                </a>
                <a href="{{ route('manage.calendar.index', ['subdomain' => $subdomain, 'teacher_id' => $ticket->user_id]) }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-sky-50 text-sky-700 rounded-lg hover:bg-sky-100 transition-colors text-xs font-medium">
                    <i class="ri-calendar-line"></i>
                    {{ __('support.supervisor.view_teacher_calendar') }}
                </a>
                <a href="{{ route('manage.teacher-earnings.index', ['subdomain' => $subdomain, 'teacher_ids' => [$ticket->user_id]]) }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-50 text-emerald-700 rounded-lg hover:bg-emerald-100 transition-colors text-xs font-medium">
                    <i class="ri-money-dollar-circle-line"></i>
                    {{ __('support.supervisor.view_teacher_earnings') }}
                </a>
            @endif

            <a href="{{ route('manage.support-tickets.index', ['subdomain' => $subdomain, 'user_id' => $ticket->user_id]) }}"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-amber-50 text-amber-700 rounded-lg hover:bg-amber-100 transition-colors text-xs font-medium">
                <i class="ri-history-line"></i>
                {{ __('support.supervisor.view_user_tickets', ['name' => $ticket->user->name]) }}
            </a>
        </div>
    </div>

    <!-- Ticket Info Card -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 md:p-6 mb-6">
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-4">
            <div>
                <div class="flex flex-wrap items-center gap-2 mb-2">
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium {{ $ticket->reason->color() }}">
                        <i class="{{ $ticket->reason->icon() }} text-sm"></i>
                        {{ $ticket->reason->label() }}
                    </span>
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium {{ $ticket->status->badgeClass() }}">
                        <i class="{{ $ticket->status->icon() }} text-sm"></i>
                        {{ $ticket->status->label() }}
                    </span>
                </div>
                <div class="flex items-center gap-2 text-sm text-gray-600">
                    <i class="ri-user-line"></i>
                    <span class="font-medium">{{ $ticket->user->name }}</span>
                    <span class="text-xs px-1.5 py-0.5 bg-gray-100 rounded">{{ $ticket->user->getUserTypeLabel() }}</span>
                    <span class="text-xs text-gray-400">{{ $ticket->created_at->diffForHumans() }}</span>
                </div>
            </div>

            @if($ticket->status === \App\Enums\SupportTicketStatus::OPEN)
                <form action="{{ route('manage.support-tickets.close', ['subdomain' => $subdomain, 'ticket' => $ticket]) }}" method="POST"
                      x-data="{ submitting: false }"
                      @submit="if (! confirm('{{ __('support.supervisor.close_confirm') }}')) { $event.preventDefault(); return; } submitting = true">
                    @csrf
                    @foreach($backFilters as $k => $v)
                        <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                    @endforeach
                    <button type="submit"
                            :disabled="submitting"
                            :class="submitting ? 'opacity-60 cursor-not-allowed' : ''"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors text-sm font-medium">
                        <i class="ri-check-double-line" x-show="!submitting"></i>
                        <i class="ri-loader-4-line animate-spin" x-show="submitting" x-cloak></i>
                        {{ __('support.supervisor.close_ticket') }}
                    </button>
                </form>
            @endif
        </div>

        <p class="text-sm text-gray-700 whitespace-pre-line">{{ $ticket->description }}</p>

        @if($ticket->image_path)
            <div class="mt-4">
                <img src="{{ asset('storage/' . $ticket->image_path) }}" alt="{{ __('support.image_label') }}" class="max-w-sm rounded-lg border border-gray-200">
            </div>
        @endif

        @if($ticket->status === \App\Enums\SupportTicketStatus::CLOSED && $ticket->closedByUser)
            <div class="mt-4 p-3 bg-gray-50 rounded-lg border border-gray-200">
                <p class="text-sm text-gray-600">
                    <i class="ri-check-double-line text-gray-500"></i>
                    {{ __('support.closed_by', ['name' => $ticket->closedByUser->name]) }}
                    — {{ $ticket->closed_at->diffForHumans() }}
                </p>
            </div>
        @endif
    </div>

    <!-- Conversation -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 md:p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ __('support.conversation') }}</h3>

        @if($ticket->replies->count() > 0)
            <div class="space-y-4 mb-6">
                @foreach($ticket->replies as $reply)
                    @php
                        $isReporter = $reply->user_id === $ticket->user_id;
                    @endphp
                    <div class="flex gap-3">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center shrink-0 {{ $isReporter ? 'bg-blue-100' : 'bg-emerald-100' }}">
                            <i class="{{ $isReporter ? 'ri-user-line text-blue-600' : 'ri-shield-user-line text-emerald-600' }} text-sm"></i>
                        </div>
                        <div class="flex-1">
                            <div class="rounded-xl p-3 {{ $isReporter ? 'bg-blue-50 border border-blue-100' : 'bg-emerald-50 border border-emerald-100' }}">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="text-xs font-medium {{ $isReporter ? 'text-blue-700' : 'text-emerald-700' }}">
                                        {{ $reply->user->name }}
                                    </span>
                                    @if(!$isReporter)
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-emerald-100 text-emerald-700">
                                            {{ $reply->user->getUserTypeLabel() }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-blue-100 text-blue-700">
                                            {{ $ticket->user->getUserTypeLabel() }}
                                        </span>
                                    @endif
                                </div>
                                <p class="text-sm text-gray-700 whitespace-pre-line">{{ $reply->body }}</p>
                            </div>
                            <span class="text-[11px] text-gray-400 mt-1 block">{{ $reply->created_at->diffForHumans() }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-6 mb-6">
                <i class="ri-chat-3-line text-3xl text-gray-300 mb-2"></i>
                <p class="text-sm text-gray-500">{{ __('support.no_replies') }}</p>
            </div>
        @endif

        <!-- Admin Reply Form -->
        @if($ticket->status === \App\Enums\SupportTicketStatus::OPEN)
            <form action="{{ route('manage.support-tickets.reply', ['subdomain' => $subdomain, 'ticket' => $ticket]) }}" method="POST"
                  x-data="{ submitting: false }"
                  @submit="submitting = true"
                  class="border-t border-gray-100 pt-4">
                @csrf
                @foreach($backFilters as $k => $v)
                    <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                @endforeach
                <div class="mb-3">
                    <textarea name="body" rows="3" required minlength="2" maxlength="2000"
                              :disabled="submitting"
                              class="w-full rounded-lg border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 text-sm disabled:bg-gray-50"
                              placeholder="{{ __('support.reply_placeholder') }}">{{ old('body') }}</textarea>
                    @error('body')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <button type="submit"
                        :disabled="submitting"
                        :class="submitting ? 'opacity-60 cursor-not-allowed' : ''"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors text-sm font-medium">
                    <i class="ri-send-plane-line" x-show="!submitting"></i>
                    <i class="ri-loader-4-line animate-spin" x-show="submitting" x-cloak></i>
                    <span x-text="submitting ? '{{ __('support.sending') }}' : '{{ __('support.send_reply') }}'"></span>
                </button>
            </form>
        @endif
    </div>
</div>

</x-layouts.supervisor>
