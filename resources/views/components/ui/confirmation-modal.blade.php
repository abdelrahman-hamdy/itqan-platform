{{-- Unified Confirmation Modal Component --}}
<div
    x-data="{
        show: false,
        title: '',
        message: '',
        confirmText: '{{ __('components.ui.confirmation_modal.confirm') }}',
        cancelText: '{{ __('components.ui.confirmation_modal.cancel') }}',
        confirmAction: null,
        isDangerous: false,
        icon: '',

        init() {
            this.$watch('show', value => {
                if (value) {
                    document.body.style.overflow = 'hidden';
                } else {
                    document.body.style.overflow = '';
                }
            });
        },

        open(data) {
            this.title = data.title || '{{ __('components.ui.confirmation_modal.default_title') }}';
            this.message = data.message || '{{ __('components.ui.confirmation_modal.default_message') }}';
            this.confirmText = data.confirmText || '{{ __('components.ui.confirmation_modal.confirm') }}';
            this.cancelText = data.cancelText || '{{ __('components.ui.confirmation_modal.cancel') }}';
            this.confirmAction = data.onConfirm || null;
            this.isDangerous = data.isDangerous || false;
            this.icon = data.icon || '';
            this.show = true;
        },

        confirm() {
            if (this.confirmAction && typeof this.confirmAction === 'function') {
                this.confirmAction();
            }
            this.close();
        },

        close() {
            this.show = false;
            this.confirmAction = null;
        }
    }"
    @open-confirmation.window="open($event.detail)"
    @keydown.escape.window="show && close()"
    x-show="show"
    x-cloak
    class="fixed inset-0 z-[9999] overflow-y-auto"
    role="dialog"
    aria-modal="true">

    {{-- Backdrop --}}
    <div x-show="show"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="close()"
         class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div>

    {{-- Modal Container - Bottom sheet on mobile, centered on desktop --}}
    <div class="fixed inset-0 flex items-end md:items-center justify-center p-0 md:p-4">
        {{-- Modal Content --}}
        <div
            @click.stop
            x-show="show"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-full md:translate-y-0 md:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 md:scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0 md:scale-100"
            x-transition:leave-end="opacity-0 translate-y-full md:translate-y-0 md:scale-95"
            class="relative bg-white w-full md:max-w-md rounded-t-2xl md:rounded-2xl shadow-2xl overflow-hidden">

            {{-- Mobile drag handle --}}
            <div class="md:hidden absolute top-2 left-1/2 -translate-x-1/2 w-10 h-1 rounded-full bg-gray-300 z-10"></div>

            {{-- Icon & Title Section --}}
            <div class="p-6 pb-4 pt-8 md:pt-6">
                {{-- Icon --}}
                <div class="mx-auto flex items-center justify-center w-16 h-16 md:w-14 md:h-14 rounded-full mb-4"
                    :class="isDangerous ? 'bg-red-100' : 'bg-blue-100'">
                    {{-- Custom Icon (if provided) --}}
                    <template x-if="icon">
                        <i :class="icon" class="text-3xl md:text-2xl" :class="isDangerous ? 'text-red-600' : 'text-blue-600'"></i>
                    </template>

                    {{-- Default Icons --}}
                    <template x-if="!icon">
                        <svg x-show="isDangerous" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-8 h-8 text-red-600">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                        </svg>
                        <svg x-show="!isDangerous" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-8 h-8 text-blue-600">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 5.25h.008v.008H12v-.008Z" />
                        </svg>
                    </template>
                </div>

                {{-- Title --}}
                <h3 class="text-lg md:text-xl font-bold text-center text-gray-900 mb-2" x-text="title"></h3>

                {{-- Message --}}
                <p class="text-center text-gray-600 text-sm md:text-base leading-relaxed" x-text="message"></p>
            </div>

            {{-- Actions - Stack on mobile, row on desktop --}}
            <div class="bg-gray-50 px-4 md:px-6 py-4 flex flex-col-reverse md:flex-row gap-3 md:justify-end">
                {{-- Cancel Button --}}
                <button
                    @click="close()"
                    type="button"
                    class="inline-flex items-center justify-center min-h-[48px] md:min-h-[44px] px-6 py-3 md:py-2.5 text-base md:text-sm font-semibold text-gray-700 bg-white hover:bg-gray-100 border border-gray-300 rounded-xl transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-gray-400">
                    <span x-text="cancelText"></span>
                </button>

                {{-- Confirm Button --}}
                <button
                    @click="confirm()"
                    type="button"
                    class="inline-flex items-center justify-center min-h-[48px] md:min-h-[44px] px-6 py-3 md:py-2.5 text-base md:text-sm font-semibold text-white rounded-xl transition-all duration-200 focus:outline-none focus:ring-2 shadow-md"
                    :class="isDangerous
                        ? 'bg-red-600 hover:bg-red-700 focus:ring-red-500'
                        : 'bg-blue-600 hover:bg-blue-700 focus:ring-blue-500'">
                    <span x-text="confirmText"></span>
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Global Helper Functions --}}
<script>
// Global confirmation functions - ensure they run after Alpine is loaded
(function() {
    function registerConfirmFunctions() {
        // Modern Alpine-based API
        window.confirmAction = function(options) {
            window.dispatchEvent(new CustomEvent('open-confirmation', {
                detail: options
            }));
        };

        // Legacy compatibility layer for showConfirmModal
        // Translates the old API to the new Alpine-based approach
        window.showConfirmModal = function(options) {
            window.dispatchEvent(new CustomEvent('open-confirmation', {
                detail: {
                    title: options.title || '{{ __('components.ui.confirmation_modal.default_title') }}',
                    message: options.message || '{{ __('components.ui.confirmation_modal.default_message') }}',
                    confirmText: options.confirmText || '{{ __('components.ui.confirmation_modal.confirm') }}',
                    cancelText: options.cancelText || '{{ __('components.ui.confirmation_modal.cancel') }}',
                    isDangerous: options.type === 'danger',
                    icon: options.type === 'danger' ? 'ri-error-warning-line' :
                          options.type === 'success' ? 'ri-check-line' : 'ri-question-line',
                    onConfirm: options.onConfirm || null
                }
            }));
        };

        // Also expose modal translations for legacy code
        window.modalTranslations = {
            defaultTitle: "{{ __('common.confirm.title') }}",
            defaultMessage: "{{ __('common.messages.confirm_action') }}",
            confirmText: "{{ __('common.actions.confirm') }}",
            cancelText: "{{ __('common.actions.cancel') }}"
        };
    }

    // Register immediately if Alpine is already loaded
    if (window.Alpine) {
        registerConfirmFunctions();
    } else {
        // Wait for Alpine to be initialized via Livewire or standalone
        document.addEventListener('livewire:init', registerConfirmFunctions);
        document.addEventListener('alpine:init', registerConfirmFunctions);
        // Fallback: DOMContentLoaded
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', registerConfirmFunctions);
        } else {
            registerConfirmFunctions();
        }
    }
})();
</script>
