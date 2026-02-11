<div>
    @if($showAlert)
        <div class="mb-6 rounded-lg border-2 border-amber-500 bg-amber-50 p-4 shadow-sm dark:border-amber-600 dark:bg-amber-900/20"
             role="alert"
             x-data="{ show: true }"
             x-show="show"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 transform scale-95"
             x-transition:enter-end="opacity-100 transform scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 transform scale-100"
             x-transition:leave-end="opacity-0 transform scale-95">

            <div class="flex items-start gap-3">
                <!-- Warning Icon -->
                <div class="flex-shrink-0">
                    <svg class="h-6 w-6 text-amber-600 dark:text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>

                <!-- Alert Content -->
                <div class="flex-1">
                    <h3 class="mb-1 text-base font-semibold text-amber-800 dark:text-amber-400">
                        ⚠️ تنبيه: التجديد التلقائي مفعّل بدون بطاقة محفوظة
                    </h3>

                    <p class="mb-3 text-sm text-amber-700 dark:text-amber-300">
                        لديك
                        <span class="font-bold">{{ $subscriptionsAtRisk }}</span>
                        {{ $subscriptionsAtRisk === 1 ? 'اشتراك' : 'اشتراكات' }}
                        مع التجديد التلقائي المفعّل، لكن لا توجد بطاقة دفع محفوظة.
                        يرجى إضافة بطاقة الآن لتجنب انقطاع الخدمة عند موعد التجديد.
                    </p>

                    <!-- Action Buttons -->
                    <div class="flex flex-wrap items-center gap-3">
                        <button wire:click="addCard"
                                class="inline-flex items-center gap-2 rounded-md bg-amber-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition-all hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:bg-amber-700 dark:hover:bg-amber-800">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            إضافة بطاقة الآن
                        </button>

                        <a href="{{ route('student.subscriptions') }}"
                           class="inline-flex items-center gap-2 rounded-md border border-amber-600 bg-white px-4 py-2 text-sm font-medium text-amber-700 shadow-sm transition-all hover:bg-amber-50 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:border-amber-600 dark:bg-amber-900/10 dark:text-amber-400 dark:hover:bg-amber-900/20">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            عرض الاشتراكات
                        </a>
                    </div>
                </div>

                <!-- Dismiss Button -->
                <button wire:click="dismiss"
                        @click="show = false"
                        type="button"
                        class="flex-shrink-0 rounded-md p-1 text-amber-600 transition-colors hover:bg-amber-100 focus:outline-none focus:ring-2 focus:ring-amber-500 dark:text-amber-500 dark:hover:bg-amber-900/30"
                        aria-label="إغلاق">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>
    @endif
</div>
