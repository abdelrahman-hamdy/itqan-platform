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
<div id="{{ $id }}" class="fixed inset-0 z-50 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <!-- Background overlay -->
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="closeModal('{{ $id }}')"></div>

        <!-- Modal centering -->
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

        <!-- Modal panel -->
        <div class="inline-block align-bottom bg-white rounded-xl text-right overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white px-6 pt-6 pb-4">
                <div class="sm:flex sm:items-start">
                    <!-- Icon -->
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full 
                         bg-{{ $confirmColor }}-100 sm:mx-0 sm:h-10 sm:w-10 mb-4 sm:mb-0 sm:ml-4">
                        <i class="{{ $icon }} text-{{ $confirmColor }}-600 text-xl"></i>
                    </div>
                    
                    <!-- Content -->
                    <div class="mt-3 text-center sm:mt-0 sm:text-right flex-1">
                        <h3 class="text-lg font-bold text-gray-900 mb-2" id="modal-title">
                            {{ $title }}
                        </h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-600 leading-relaxed">
                                {{ $message }}
                            </p>
                        </div>
                        
                        @if($hasInput)
                            <div class="mt-4">
                                <label for="{{ $id }}-input" class="block text-sm font-medium text-gray-700 mb-2 text-right">
                                    {{ $inputLabel }}
                                </label>
                                <textarea 
                                    id="{{ $id }}-input" 
                                    name="reason"
                                    rows="3"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-{{ $confirmColor }}-500 focus:border-{{ $confirmColor }}-500 text-right"
                                    placeholder="{{ $inputPlaceholder }}"></textarea>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            
            <!-- Action buttons -->
            <div class="bg-gray-50 px-6 py-4 flex flex-row-reverse space-x-2 space-x-reverse">
                <button type="button" 
                        id="{{ $id }}-confirm"
                        class="inline-flex justify-center px-4 py-2 text-sm font-medium text-white 
                               bg-{{ $confirmColor }}-600 border border-transparent rounded-md 
                               hover:bg-{{ $confirmColor }}-700 focus:outline-none focus:ring-2 
                               focus:ring-offset-2 focus:ring-{{ $confirmColor }}-500 transition-colors duration-200">
                    <span class="button-text">{{ $confirmText }}</span>
                    <div class="loading-spinner hidden">
                        <svg class="animate-spin -ml-1 mr-3 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span>جاري التنفيذ...</span>
                    </div>
                </button>
                
                <button type="button" 
                        id="{{ $id }}-cancel"
                        onclick="closeModal('{{ $id }}')"
                        class="inline-flex justify-center px-4 py-2 text-sm font-medium text-gray-700 
                               bg-white border border-gray-300 rounded-md hover:bg-gray-50 
                               focus:outline-none focus:ring-2 focus:ring-offset-2 
                               focus:ring-{{ $confirmColor }}-500 transition-colors duration-200">
                    {{ $cancelText }}
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Modal control functions
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
    
    // Show modal
    modal.classList.remove('hidden');
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
    
    modal.classList.add('hidden');
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

// Close modal on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const visibleModal = document.querySelector('[id$="-modal"]:not(.hidden)');
        if (visibleModal) {
            closeModal(visibleModal.id);
        }
    }
});
</script>
