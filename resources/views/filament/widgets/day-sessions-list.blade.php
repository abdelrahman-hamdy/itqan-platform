<div class="space-y-3">
    @forelse($sessions as $session)
        @php
            $isQuran = $session['type'] === 'quran';
            $isPassed = $session['isPassed'];
            $eventId = $session['eventId'];
        @endphp

        <div class="border rounded-lg p-4 {{ $isPassed ? 'opacity-70' : '' }}"
             style="border-color: {{ $session['color'] }}; {{ $isPassed ? 'text-decoration: line-through;' : '' }}">
            <div class="flex justify-between items-start gap-4">
                <div class="flex-1">
                    <div class="flex items-center gap-2 mb-2">
                        <div class="font-bold text-lg" style="color: {{ $session['color'] }};">
                            {{ $session['time'] }}
                        </div>
                        <span class="px-2 py-1 rounded-full text-xs font-medium"
                              style="background-color: {{ $session['color'] }}20; color: {{ $session['color'] }};">
                            {{ $session['sessionType'] }}
                        </span>
                    </div>

                    <div class="text-base font-medium text-gray-900 dark:text-gray-100 mb-1">
                        {{ $session['studentName'] }}
                    </div>

                    @if(!empty($session['subject']))
                        <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">
                            {{ $session['subject'] }}
                        </div>
                    @endif

                    <div class="text-xs text-gray-500 dark:text-gray-500">
                        {{ $session['duration'] }} دقيقة
                    </div>

                    @if(!empty($session['status']))
                        <div class="mt-2">
                            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium
                                {{ match($session['status']) {
                                    'scheduled' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                                    'ongoing' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                                    'completed' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                    'cancelled' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                    default => 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200',
                                } }}">
                                {{ match($session['status']) {
                                    'scheduled' => 'مجدولة',
                                    'ongoing' => 'جارية',
                                    'completed' => 'مكتملة',
                                    'cancelled' => 'ملغية',
                                    default => $session['status'],
                                } }}
                            </span>
                        </div>
                    @endif
                </div>

                @if(!$isPassed && $session['canEdit'])
                    <div class="flex-shrink-0">
                        <button
                            type="button"
                            wire:click="editSessionFromDayModal('{{ $eventId }}')"
                            class="inline-flex items-center gap-1 px-3 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded-lg transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                            تعديل
                        </button>
                    </div>
                @endif
            </div>
        </div>
    @empty
        <div class="text-center py-8 text-gray-500">
            <svg class="mx-auto h-8 w-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
            </svg>
            <p class="mt-2">لا توجد جلسات مجدولة في هذا اليوم</p>
        </div>
    @endforelse
</div>
