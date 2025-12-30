{{--
    Unified Toast Notification Container

    This component provides a centralized toast notification system for the entire application.
    It handles toasts from:
    - JavaScript: window.toast.success('message')
    - Livewire: $this->dispatch('toast', type: 'success', message: 'Message')
    - Alpine.js: $dispatch('toast', { type: 'success', message: 'Message' })
    - Session flash: session()->flash('toast', ['type' => 'success', 'message' => 'Message'])

    Usage:
    Include this component once in your layout file:
    <x-ui.toast-container />

    Then trigger toasts from anywhere:
    - JavaScript: window.toast.success('تم الحفظ بنجاح')
    - JavaScript: window.toast.error('حدث خطأ')
    - JavaScript: window.toast.warning('تنبيه')
    - JavaScript: window.toast.info('معلومة')
    - Livewire: $this->dispatch('toast', type: 'success', message: 'Message', duration: 5000)
--}}

<div id="toast-container"
     x-data="toastManager()"
     x-on:toast.window="addToast($event.detail)"
     class="fixed top-4 left-4 right-4 z-[9999] pointer-events-none flex flex-col items-end gap-3 sm:left-auto sm:right-4 sm:max-w-sm"
     x-cloak>

    {{-- Toast items rendered via Alpine.js --}}
    <template x-for="toast in toasts" :key="toast.id">
        <div x-show="toast.visible"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 transform translate-x-8 rtl:-translate-x-8"
             x-transition:enter-end="opacity-100 transform translate-x-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 transform translate-x-0"
             x-transition:leave-end="opacity-0 transform translate-x-8 rtl:-translate-x-8"
             :class="getToastClasses(toast.type)"
             class="pointer-events-auto w-full rounded-xl shadow-lg backdrop-blur-sm border"
             @mouseenter="pauseToast(toast)"
             @mouseleave="resumeToast(toast)">

            <div class="flex items-start gap-3 p-4">
                {{-- Icon --}}
                <div class="flex-shrink-0">
                    <template x-if="toast.type === 'success'">
                        <svg class="w-5 h-5 text-green-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                            <path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12Zm13.36-1.814a.75.75 0 1 0-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 0 0-1.06 1.06l2.25 2.25a.75.75 0 0 0 1.14-.094l3.75-5.25Z" clip-rule="evenodd" />
                        </svg>
                    </template>
                    <template x-if="toast.type === 'error'">
                        <svg class="w-5 h-5 text-red-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                            <path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25Zm-1.72 6.97a.75.75 0 1 0-1.06 1.06L10.94 12l-1.72 1.72a.75.75 0 1 0 1.06 1.06L12 13.06l1.72 1.72a.75.75 0 1 0 1.06-1.06L13.06 12l1.72-1.72a.75.75 0 1 0-1.06-1.06L12 10.94l-1.72-1.72Z" clip-rule="evenodd" />
                        </svg>
                    </template>
                    <template x-if="toast.type === 'warning'">
                        <svg class="w-5 h-5 text-amber-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                            <path fill-rule="evenodd" d="M9.401 3.003c1.155-2 4.043-2 5.197 0l7.355 12.748c1.154 2-.29 4.5-2.599 4.5H4.645c-2.309 0-3.752-2.5-2.598-4.5L9.4 3.003ZM12 8.25a.75.75 0 0 1 .75.75v3.75a.75.75 0 0 1-1.5 0V9a.75.75 0 0 1 .75-.75Zm0 8.25a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Z" clip-rule="evenodd" />
                        </svg>
                    </template>
                    <template x-if="toast.type === 'info'">
                        <svg class="w-5 h-5 text-blue-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                            <path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12Zm8.706-1.442c1.146-.573 2.437.463 2.126 1.706l-.709 2.836.042-.02a.75.75 0 0 1 .67 1.34l-.04.022c-1.147.573-2.438-.463-2.127-1.706l.71-2.836-.042.02a.75.75 0 1 1-.671-1.34l.041-.022ZM12 9a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Z" clip-rule="evenodd" />
                        </svg>
                    </template>
                </div>

                {{-- Content --}}
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900" x-text="toast.message"></p>
                    <p x-show="toast.description" class="mt-1 text-xs text-gray-600" x-text="toast.description"></p>
                </div>

                {{-- Close button --}}
                <button @click="removeToast(toast.id)"
                        class="flex-shrink-0 p-1 rounded-lg hover:bg-black/5 transition-colors"
                        type="button">
                    <svg class="w-4 h-4 text-gray-400 hover:text-gray-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            {{-- Progress bar --}}
            <div x-show="toast.showProgress && !toast.paused" class="h-1 bg-gray-100 rounded-b-xl overflow-hidden">
                <div :class="getProgressBarClass(toast.type)"
                     class="h-full transition-all ease-linear"
                     :style="`width: ${toast.progress}%`"></div>
            </div>
        </div>
    </template>
</div>

{{-- Handle session flash toasts --}}
@if(session('toast'))
    @php $flashToast = session('toast'); @endphp
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (window.toast) {
                window.toast.show({
                    type: '{{ $flashToast['type'] ?? 'info' }}',
                    message: '{{ $flashToast['message'] ?? '' }}',
                    duration: {{ $flashToast['duration'] ?? 5000 }}
                });
            }
        });
    </script>
@endif

{{-- Handle session success/error flash messages --}}
@if(session('success'))
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (window.toast) {
                window.toast.success('{{ session('success') }}');
            }
        });
    </script>
@endif

@if(session('error'))
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (window.toast) {
                window.toast.error('{{ session('error') }}');
            }
        });
    </script>
@endif

<script>
/**
 * Unified Toast Manager - Alpine.js Component
 * Provides a centralized toast notification system
 */
function toastManager() {
    return {
        toasts: [],
        maxToasts: 5,

        init() {
            // Expose global toast API
            window.toast = {
                show: (options) => this.addToast(options),
                success: (message, options = {}) => this.addToast({ type: 'success', message, ...options }),
                error: (message, options = {}) => this.addToast({ type: 'error', message, ...options }),
                warning: (message, options = {}) => this.addToast({ type: 'warning', message, ...options }),
                info: (message, options = {}) => this.addToast({ type: 'info', message, ...options }),
                clear: () => this.clearAll()
            };

            // Listen for Livewire events
            if (typeof Livewire !== 'undefined') {
                Livewire.on('toast', (data) => {
                    this.addToast(Array.isArray(data) ? data[0] : data);
                });
            }

            // Listen for WireChat toast events
            window.addEventListener('wirechat-toast', (e) => {
                this.addToast(e.detail);
            });

            // Listen for certificate issuance events from IssueCertificateModal component
            window.addEventListener('certificate-issued-success', (e) => {
                this.addToast({ type: 'success', message: e.detail.message || '{{ __('components.ui.toast.certificate_issued_success') }}' });
            });
            window.addEventListener('certificate-issued-error', (e) => {
                this.addToast({ type: 'error', message: e.detail.message || '{{ __('components.ui.toast.certificate_issued_error') }}' });
            });
        },

        addToast(options) {
            // Normalize options
            const toast = {
                id: Date.now() + Math.random(),
                type: options.type || 'info',
                message: options.message || '',
                description: options.description || null,
                duration: options.duration || this.getDefaultDuration(options.type),
                showProgress: options.showProgress !== false,
                visible: true,
                paused: false,
                progress: 100,
                timer: null,
                startTime: null,
                remainingTime: null
            };

            // Remove oldest toast if we have too many
            if (this.toasts.length >= this.maxToasts) {
                this.removeToast(this.toasts[0].id);
            }

            this.toasts.push(toast);
            this.startTimer(toast);

            return toast.id;
        },

        getDefaultDuration(type) {
            const durations = {
                success: 4000,
                error: 6000,
                warning: 5000,
                info: 4000
            };
            return durations[type] || 4000;
        },

        startTimer(toast) {
            toast.startTime = Date.now();
            toast.remainingTime = toast.duration;

            const updateProgress = () => {
                if (toast.paused) return;

                const elapsed = Date.now() - toast.startTime;
                toast.progress = Math.max(0, 100 - (elapsed / toast.duration) * 100);

                if (elapsed >= toast.duration) {
                    this.removeToast(toast.id);
                } else {
                    toast.timer = requestAnimationFrame(updateProgress);
                }
            };

            toast.timer = requestAnimationFrame(updateProgress);
        },

        pauseToast(toast) {
            toast.paused = true;
            toast.remainingTime = toast.duration - (Date.now() - toast.startTime);
            if (toast.timer) {
                cancelAnimationFrame(toast.timer);
            }
        },

        resumeToast(toast) {
            toast.paused = false;
            toast.duration = toast.remainingTime;
            toast.startTime = Date.now();
            this.startTimer(toast);
        },

        removeToast(id) {
            const index = this.toasts.findIndex(t => t.id === id);
            if (index !== -1) {
                const toast = this.toasts[index];
                toast.visible = false;
                if (toast.timer) {
                    cancelAnimationFrame(toast.timer);
                }
                setTimeout(() => {
                    this.toasts = this.toasts.filter(t => t.id !== id);
                }, 300);
            }
        },

        clearAll() {
            this.toasts.forEach(toast => {
                if (toast.timer) cancelAnimationFrame(toast.timer);
            });
            this.toasts = [];
        },

        getToastClasses(type) {
            const classes = {
                success: 'bg-white/95 border-green-200',
                error: 'bg-white/95 border-red-200',
                warning: 'bg-white/95 border-amber-200',
                info: 'bg-white/95 border-blue-200'
            };
            return classes[type] || classes.info;
        },

        getProgressBarClass(type) {
            const classes = {
                success: 'bg-green-500',
                error: 'bg-red-500',
                warning: 'bg-amber-500',
                info: 'bg-blue-500'
            };
            return classes[type] || classes.info;
        }
    };
}
</script>
