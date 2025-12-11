@props([
    'id' => 'session-action-modal',
    'title' => 'تأكيد الإجراء',
    'message' => 'هل أنت متأكد من تنفيذ هذا الإجراء؟',
    'confirmText' => 'تأكيد',
    'cancelText' => 'إلغاء',
    'confirmColor' => 'blue',
    'icon' => 'ri-question-line',
    'hasInput' => false,
    'inputLabel' => 'السبب (اختياري)',
    'inputPlaceholder' => 'اكتب السبب...',
])

<!-- Modal Backdrop -->
<div id="{{ $id }}"
     x-data="{ open: false }"
     x-show="open"
     x-cloak
     @open-modal-{{ $id }}.window="open = true"
     @close-modal-{{ $id }}.window="open = false"
     @keydown.escape.window="if(open) { open = false; closeModal('{{ $id }}'); }"
     class="fixed inset-0 z-50 overflow-y-auto"
     aria-labelledby="modal-title"
     role="dialog"
     aria-modal="true">

    <!-- Background overlay -->
    <div x-show="open"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="open = false; closeModal('{{ $id }}')"
         class="fixed inset-0 bg-black/50 backdrop-blur-sm"
         aria-hidden="true"></div>

    <!-- Modal Container - Bottom sheet on mobile, centered on desktop -->
    <div class="fixed inset-0 flex items-end md:items-center justify-center p-0 md:p-4">
        <!-- Modal panel -->
        <div x-show="open"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-full md:translate-y-0 md:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 md:scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 md:scale-100"
             x-transition:leave-end="opacity-0 translate-y-full md:translate-y-0 md:scale-95"
             @click.stop
             class="relative bg-white w-full md:max-w-lg rounded-t-2xl md:rounded-2xl shadow-xl overflow-hidden">

            <!-- Mobile drag handle -->
            <div class="md:hidden absolute top-2 left-1/2 -translate-x-1/2 w-10 h-1 rounded-full bg-gray-300"></div>

            <div class="bg-white px-4 md:px-6 pt-6 md:pt-6 pb-4">
                <div class="flex flex-col md:flex-row md:items-start gap-4">
                    <!-- Icon -->
                    <div class="mx-auto md:mx-0 flex-shrink-0 flex items-center justify-center h-14 w-14 md:h-12 md:w-12 rounded-full bg-{{ $confirmColor }}-100">
                        <i class="{{ $icon }} text-{{ $confirmColor }}-600 text-2xl md:text-xl"></i>
                    </div>

                    <!-- Content -->
                    <div class="text-center md:text-right flex-1">
                        <h3 class="text-lg md:text-xl font-bold text-gray-900 mb-2" id="modal-title">
                            {{ $title }}
                        </h3>
                        <p class="text-sm md:text-base text-gray-600 leading-relaxed">
                            {{ $message }}
                        </p>

                        @if($hasInput)
                            <div class="mt-4">
                                <label for="{{ $id }}-input" class="block text-sm font-medium text-gray-700 mb-2 text-right">
                                    {{ $inputLabel }}
                                </label>
                                <textarea
                                    id="{{ $id }}-input"
                                    name="reason"
                                    rows="3"
                                    class="w-full px-4 py-3 min-h-[100px] border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-{{ $confirmColor }}-500 focus:border-{{ $confirmColor }}-500 text-right text-base"
                                    placeholder="{{ $inputPlaceholder }}"></textarea>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Action buttons - Stack on mobile, row on desktop -->
            <div class="bg-gray-50 px-4 md:px-6 py-4 flex flex-col-reverse md:flex-row-reverse gap-3 md:gap-2">
                <button type="button"
                        id="{{ $id }}-confirm"
                        class="inline-flex items-center justify-center min-h-[48px] md:min-h-[44px] px-6 py-3 md:py-2 text-base md:text-sm font-medium text-white
                               bg-{{ $confirmColor }}-600 border border-transparent rounded-xl md:rounded-lg
                               hover:bg-{{ $confirmColor }}-700 focus:outline-none focus:ring-2
                               focus:ring-offset-2 focus:ring-{{ $confirmColor }}-500 transition-colors duration-200">
                    <span class="button-text">{{ $confirmText }}</span>
                    <div class="loading-spinner hidden flex items-center gap-2">
                        <svg class="animate-spin h-5 w-5 md:h-4 md:w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span>جاري التنفيذ...</span>
                    </div>
                </button>

                <button type="button"
                        id="{{ $id }}-cancel"
                        @click="open = false; closeModal('{{ $id }}')"
                        class="inline-flex items-center justify-center min-h-[48px] md:min-h-[44px] px-6 py-3 md:py-2 text-base md:text-sm font-medium text-gray-700
                               bg-white border border-gray-300 rounded-xl md:rounded-lg hover:bg-gray-50
                               focus:outline-none focus:ring-2 focus:ring-offset-2
                               focus:ring-{{ $confirmColor }}-500 transition-colors duration-200">
                    {{ $cancelText }}
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    [x-cloak] { display: none !important; }
</style>

<script>
// Modal control functions - Compatible with Alpine.js
function openModal(modalId, options = {}) {
    const modal = document.getElementById(modalId);
    if (!modal) return;

    // Update modal content if options provided
    if (options.title) {
        const titleEl = modal.querySelector('#modal-title');
        if (titleEl) titleEl.textContent = options.title;
    }

    if (options.message) {
        const messageEl = modal.querySelector('.text-gray-600');
        if (messageEl) messageEl.textContent = options.message;
    }

    // Dispatch Alpine.js event to open modal
    window.dispatchEvent(new CustomEvent('open-modal-' + modalId));
    document.body.style.overflow = 'hidden';

    // Focus on confirm button
    const confirmBtn = modal.querySelector(`#${modalId}-confirm`);
    if (confirmBtn) {
        setTimeout(() => confirmBtn.focus(), 100);
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (!modal) return;

    // Dispatch Alpine.js event to close modal
    window.dispatchEvent(new CustomEvent('close-modal-' + modalId));
    document.body.style.overflow = '';

    // Reset loading state
    const confirmBtn = modal.querySelector(`#${modalId}-confirm`);
    const cancelBtn = modal.querySelector(`#${modalId}-cancel`);
    const buttonText = confirmBtn?.querySelector('.button-text');
    const loadingSpinner = confirmBtn?.querySelector('.loading-spinner');

    if (confirmBtn) confirmBtn.disabled = false;
    if (cancelBtn) cancelBtn.disabled = false;
    if (buttonText) buttonText.classList.remove('hidden');
    if (loadingSpinner) loadingSpinner.classList.add('hidden');

    // Clear input if exists
    const input = modal.querySelector('textarea[name="reason"]');
    if (input) input.value = '';
}

function setModalLoading(modalId, loading = true) {
    const modal = document.getElementById(modalId);
    if (!modal) return;

    const confirmBtn = modal.querySelector(`#${modalId}-confirm`);
    const cancelBtn = modal.querySelector(`#${modalId}-cancel`);
    const buttonText = confirmBtn?.querySelector('.button-text');
    const loadingSpinner = confirmBtn?.querySelector('.loading-spinner');

    if (loading) {
        if (confirmBtn) confirmBtn.disabled = true;
        if (cancelBtn) cancelBtn.disabled = true;
        if (buttonText) buttonText.classList.add('hidden');
        if (loadingSpinner) loadingSpinner.classList.remove('hidden');
    } else {
        if (confirmBtn) confirmBtn.disabled = false;
        if (cancelBtn) cancelBtn.disabled = false;
        if (buttonText) buttonText.classList.remove('hidden');
        if (loadingSpinner) loadingSpinner.classList.add('hidden');
    }
}
</script>
