@props([
    'session',
    'viewType' => 'teacher'
])

<!-- Homework Management Section -->
<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-lg font-semibold text-gray-900">{{ __('components.sessions.homework.title') }}</h3>
        @if($session->sessionHomework)
        <button id="editHomeworkBtn"
                class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded-lg transition-colors shadow-sm">
            <i class="ri-edit-line ms-1"></i>
            {{ __('components.sessions.homework.edit_homework') }}
        </button>
        @else
        <button id="addHomeworkBtn"
                class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm rounded-lg transition-colors shadow-sm">
            <i class="ri-add-line ms-1"></i>
            {{ __('components.sessions.homework.add_homework') }}
        </button>
        @endif
    </div>
    
    @if($session->sessionHomework)
        <!-- Display Current Session Homework -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            <!-- Header -->
            <div class="bg-gray-50 border-b border-gray-200 p-6 rounded-t-xl">
                <div class="flex items-center gap-3">
                    <i class="ri-file-text-line text-indigo-600 text-xl"></i>
                    <h4 class="text-lg font-semibold text-gray-900">{{ __('components.sessions.homework.session_homework') }}</h4>
                </div>
            </div>
            
            <!-- Content -->
            <div class="p-6">
                
                <!-- Homework Types Summary - Always expand to fill space -->
                @php
                    $homeworkCount = 0;
                    if($session->sessionHomework->has_new_memorization) $homeworkCount++;
                    if($session->sessionHomework->has_review) $homeworkCount++;
                    if($session->sessionHomework->has_comprehensive_review && $session->sessionHomework->comprehensive_review_surahs) $homeworkCount++;
                @endphp
                
                <div class="grid gap-4 mb-6 @if($homeworkCount == 1) grid-cols-1 @elseif($homeworkCount == 2) grid-cols-1 md:grid-cols-2 @else grid-cols-1 md:grid-cols-2 lg:grid-cols-3 @endif">
                    @if($session->sessionHomework->has_new_memorization)
                    <div class="bg-gradient-to-br from-green-50 to-emerald-50 rounded-xl p-5 border border-green-200 shadow-sm hover:shadow-md transition-all duration-200 h-full">
                        <div class="flex items-center mb-3">
                            <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center ms-3">
                                <i class="ri-book-open-line text-green-600 text-lg"></i>
                            </div>
                            <span class="text-sm text-green-700 font-semibold">{{ __('components.sessions.homework.new_memorization') }}</span>
                        </div>
                        @if($session->sessionHomework->new_memorization_surah)
                        <p class="text-green-900 font-bold text-lg mb-1">{{ \App\Enums\QuranSurah::getArabicName($session->sessionHomework->new_memorization_surah) }}</p>
                        @endif
                        @if($session->sessionHomework->new_memorization_pages)
                        <p class="text-green-700 text-sm font-medium">{{ $session->sessionHomework->new_memorization_pages }} {{ __('components.sessions.homework.pages_count') }}</p>
                        @endif
                    </div>
                    @endif
                
                    @if($session->sessionHomework->has_review)
                    <div class="bg-gradient-to-br from-blue-50 to-cyan-50 rounded-xl p-5 border border-blue-200 shadow-sm hover:shadow-md transition-all duration-200 h-full">
                        <div class="flex items-center mb-3">
                            <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center ms-3">
                                <i class="ri-refresh-line text-blue-600 text-lg"></i>
                            </div>
                            <span class="text-sm text-blue-700 font-semibold">{{ __('components.sessions.homework.review') }}</span>
                        </div>
                        @if($session->sessionHomework->review_surah)
                        <p class="text-blue-900 font-bold text-lg mb-1">{{ \App\Enums\QuranSurah::getArabicName($session->sessionHomework->review_surah) }}</p>
                        @endif
                        @if($session->sessionHomework->review_pages)
                        <p class="text-blue-700 text-sm font-medium">{{ $session->sessionHomework->review_pages }} {{ __('components.sessions.homework.pages_count') }}</p>
                        @endif
                    </div>
                    @endif
                
                    @if($session->sessionHomework->has_comprehensive_review && $session->sessionHomework->comprehensive_review_surahs)
                    <div class="bg-gradient-to-br from-purple-50 to-violet-50 rounded-xl p-5 border border-purple-200 shadow-sm hover:shadow-md transition-all duration-200 h-full">
                        <div class="flex items-center mb-3">
                            <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center ms-3">
                                <i class="ri-list-check text-purple-600 text-lg"></i>
                            </div>
                            <span class="text-sm text-purple-700 font-semibold">{{ __('components.sessions.homework.comprehensive_review') }}</span>
                        </div>
                        <div class="text-purple-900 font-bold text-base leading-relaxed">
                            @php
                                $displaySurahs = [];
                                $savedSurahs = $session->sessionHomework->comprehensive_review_surahs;
                                
                                if (is_string($savedSurahs)) {
                                    $savedSurahs = json_decode($savedSurahs, true) ?: [];
                                }
                                
                                if (is_array($savedSurahs)) {
                                    $allSurahs = \App\Enums\QuranSurah::getAllSurahs();
                                    foreach ($savedSurahs as $surahKey) {
                                        if (isset($allSurahs[$surahKey])) {
                                            $displaySurahs[] = $allSurahs[$surahKey];
                                        } else {
                                            // Handle old data that might still be stored as Arabic names
                                            $displaySurahs[] = \App\Enums\QuranSurah::getArabicName($surahKey);
                                        }
                                    }
                                }
                            @endphp
                            @if(count($displaySurahs) > 0)
                                <div class="flex flex-wrap gap-2">
                                    @foreach($displaySurahs as $surah)
                                        <span class="inline-block bg-purple-100 text-purple-800 px-3 py-1 rounded-full text-sm font-medium">{{ $surah }}</span>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                    @endif
                </div>
            
                
                @if($session->sessionHomework->additional_instructions)
                <div class="border-t border-gray-200 pt-6 mt-6">
                    <div class="bg-gradient-to-br from-amber-50 to-yellow-50 rounded-xl p-5 border border-amber-200 shadow-sm">
                        <div class="flex items-center mb-3">
                            <div class="w-8 h-8 bg-amber-100 rounded-lg flex items-center justify-center ms-3">
                                <i class="ri-information-line text-amber-600 text-lg"></i>
                            </div>
                            <h5 class="font-semibold text-amber-900">{{ __('components.sessions.homework.additional_instructions') }}</h5>
                        </div>
                        <p class="text-amber-800 leading-relaxed">{{ $session->sessionHomework->additional_instructions }}</p>
                    </div>
                </div>
                @endif
            </div>
        </div>
    @else
        <!-- No Homework Assigned -->
        <div class="text-center py-12">
            <div class="w-20 h-20 mx-auto mb-4 bg-gray-100 rounded-full flex items-center justify-center">
                <i class="ri-file-text-line text-3xl text-gray-400"></i>
            </div>
            <h3 class="text-xl font-medium text-gray-900 mb-2">{{ __('components.sessions.homework.no_homework') }}</h3>
            <p class="text-gray-600 mb-4">{{ __('components.sessions.homework.no_homework_message') }}</p>
        </div>
    @endif
</div>

<!-- Homework Modal -->
<div id="homeworkModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h3 id="modalTitle" class="text-xl font-bold text-gray-900">{{ __('components.sessions.homework.add_homework') }}</h3>
                <button id="closeModalBtn" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <i class="ri-close-line text-2xl"></i>
                </button>
            </div>
        </div>
        
        <div class="p-6">
            <form id="homeworkForm" class="space-y-6">
            @csrf
            <input type="hidden" name="session_id" value="{{ $session->id }}">
            
            <!-- Homework Type Selection -->
            <div class="space-y-4">
                <h4 class="text-lg font-semibold text-gray-900">{{ __('components.sessions.homework.homework_type') }}</h4>
                
                <!-- New Memorization -->
                <div class="border border-gray-200 rounded-lg p-4">
                    <div class="flex items-center mb-3">
                        <input type="checkbox" id="has_new_memorization" name="has_new_memorization" 
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="has_new_memorization" class="me-2 text-sm font-medium text-gray-900">
                            {{ __('components.sessions.homework.new_memorization') }}
                        </label>
                    </div>
                    
                    <div id="newMemorizationFields" class="grid grid-cols-1 md:grid-cols-2 gap-4 hidden">
                        <div>
                            <label for="new_memorization_surah" class="block text-sm font-medium text-gray-700 mb-1">{{ __('components.sessions.homework.surah') }}</label>
                            <select id="new_memorization_surah" name="new_memorization_surah"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">{{ __('components.sessions.homework.select_surah') }}</option>
                                @php
                                    $surahs = \App\Enums\QuranSurah::getAllSurahs();
                                @endphp
                                @foreach($surahs as $key => $name)
                                    <option value="{{ $name }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div>
                            <label for="new_memorization_pages" class="block text-sm font-medium text-gray-700 mb-1">{{ __('components.sessions.homework.pages_number') }}</label>
                            <input type="number" id="new_memorization_pages" name="new_memorization_pages" step="0.5" min="0.5" max="10"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   placeholder="1.5">
                        </div>
                        

                    </div>
                </div>
                
                <!-- Review -->
                <div class="border border-gray-200 rounded-lg p-4">
                    <div class="flex items-center mb-3">
                        <input type="checkbox" id="has_review" name="has_review" 
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="has_review" class="me-2 text-sm font-medium text-gray-900">
                            {{ __('components.sessions.homework.review') }}
                        </label>
                    </div>
                    
                    <div id="reviewFields" class="grid grid-cols-1 md:grid-cols-2 gap-4 hidden">
                        <div>
                            <label for="review_surah" class="block text-sm font-medium text-gray-700 mb-1">{{ __('components.sessions.homework.surah') }}</label>
                            <select id="review_surah" name="review_surah"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">{{ __('components.sessions.homework.select_surah') }}</option>
                                @php
                                    $surahs = \App\Enums\QuranSurah::getAllSurahs();
                                @endphp
                                @foreach($surahs as $key => $name)
                                    <option value="{{ $name }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div>
                            <label for="review_pages" class="block text-sm font-medium text-gray-700 mb-1">{{ __('components.sessions.homework.pages_number') }}</label>
                            <input type="number" id="review_pages" name="review_pages" step="0.5" min="0.5" max="20"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   placeholder="2">
                        </div>
                        

                    </div>
                </div>
                
                <!-- Comprehensive Review -->
                <div class="border border-gray-200 rounded-lg p-4">
                    <div class="flex items-center mb-3">
                        <input type="checkbox" id="has_comprehensive_review" name="has_comprehensive_review" 
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="has_comprehensive_review" class="me-2 text-sm font-medium text-gray-900">
                            {{ __('components.sessions.homework.comprehensive_review') }}
                        </label>
                    </div>
                    
                    <div id="comprehensiveReviewFields" class="hidden">
                        <label for="comprehensive_review_surahs" class="block text-sm font-medium text-gray-700 mb-1">{{ __('components.sessions.homework.surahs') }}</label>
                        <div class="max-h-48 overflow-y-auto border border-gray-300 rounded-lg p-3 bg-white">
                            <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                                @php
                                    $surahs = \App\Enums\QuranSurah::getAllSurahs();
                                @endphp
                                @foreach($surahs as $key => $name)
                                    <label class="flex items-center gap-2">
                                        <input type="checkbox" name="comprehensive_review_surahs[]" value="{{ $key }}" 
                                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                        <span class="text-sm text-gray-700">{{ $name }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">{{ __('components.sessions.homework.surahs_help') }}</p>
                    </div>
                </div>
            </div>

            
            <div>
                <label for="additional_instructions" class="block text-sm font-medium text-gray-700 mb-1">{{ __('components.sessions.homework.additional_instructions') }}</label>
                <textarea id="additional_instructions" name="additional_instructions" rows="3"
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                          placeholder="{{ __('components.sessions.homework.additional_instructions_placeholder') }}"></textarea>
            </div>
            
                <button type="button" id="cancelHomeworkBtn"
                        class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                    {{ __('components.sessions.homework.cancel') }}
                </button>
                <button type="submit" id="saveHomeworkBtn"
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors">
                    {{ __('components.sessions.homework.save') }}
                </button>
            </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('homeworkModal');
    const modalTitle = document.getElementById('modalTitle');
    const form = document.getElementById('homeworkForm');
    const addBtn = document.getElementById('addHomeworkBtn');
    const editBtn = document.getElementById('editHomeworkBtn');
    const closeBtn = document.getElementById('closeModalBtn');
    const cancelBtn = document.getElementById('cancelHomeworkBtn');
    
    // Toggle field visibility based on checkbox states
    const toggleFields = (checkboxId, fieldsId) => {
        const checkbox = document.getElementById(checkboxId);
        const fields = document.getElementById(fieldsId);
        
        checkbox.addEventListener('change', function() {
            if (this.checked) {
                fields.classList.remove('hidden');
            } else {
                fields.classList.add('hidden');
                // Clear fields when hidden
                fields.querySelectorAll('input, textarea, select').forEach(input => {
                    if (input.type === 'checkbox') {
                        input.checked = false;
                    } else {
                        input.value = '';
                    }
                });
            }
        });
    };
    
    // Setup field toggles
    toggleFields('has_new_memorization', 'newMemorizationFields');
    toggleFields('has_review', 'reviewFields');
    toggleFields('has_comprehensive_review', 'comprehensiveReviewFields');
    
    // Open modal for adding homework
    addBtn?.addEventListener('click', function() {
        modalTitle.textContent = '{{ __('components.sessions.homework.modal_title_add') }}';
        form.reset();
        // Hide all conditional fields
        document.getElementById('newMemorizationFields').classList.add('hidden');
        document.getElementById('reviewFields').classList.add('hidden');
        document.getElementById('comprehensiveReviewFields').classList.add('hidden');
        modal.classList.remove('hidden');
    });
    
    // Open modal for editing homework
    editBtn?.addEventListener('click', async function() {
        modalTitle.textContent = '{{ __('components.sessions.homework.modal_title_edit') }}';
        
        try {
            const response = await fetch(`{{ url('/') }}/teacher/sessions/{{ $session->id }}/homework`);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            
            if (data.success && data.homework) {
                const homework = data.homework;
                
                // Reset form first
                form.reset();
                document.getElementById('newMemorizationFields').classList.add('hidden');
                document.getElementById('reviewFields').classList.add('hidden');
                document.getElementById('comprehensiveReviewFields').classList.add('hidden');
                
                // Clear all comprehensive review checkboxes
                const allCheckboxes = form.querySelectorAll('input[name="comprehensive_review_surahs[]"]');
                allCheckboxes.forEach(checkbox => checkbox.checked = false);
                
                // Fill form with existing data
                document.getElementById('has_new_memorization').checked = homework.has_new_memorization || false;
                document.getElementById('has_review').checked = homework.has_review || false;
                document.getElementById('has_comprehensive_review').checked = homework.has_comprehensive_review || false;
                
                if (homework.has_new_memorization) {
                    document.getElementById('newMemorizationFields').classList.remove('hidden');
                    document.getElementById('new_memorization_surah').value = homework.new_memorization_surah || '';
                    document.getElementById('new_memorization_pages').value = homework.new_memorization_pages || '';
                }
                
                if (homework.has_review) {
                    document.getElementById('reviewFields').classList.remove('hidden');
                    document.getElementById('review_surah').value = homework.review_surah || '';
                    document.getElementById('review_pages').value = homework.review_pages || '';
                }
                
                if (homework.has_comprehensive_review) {
                    document.getElementById('comprehensiveReviewFields').classList.remove('hidden');
                    // Handle comprehensive review surahs checkboxes
                    if (homework.comprehensive_review_surahs && Array.isArray(homework.comprehensive_review_surahs)) {
                        homework.comprehensive_review_surahs.forEach(surah => {
                            const checkbox = document.querySelector(`input[name="comprehensive_review_surahs[]"][value="${surah}"]`);
                            if (checkbox) {
                                checkbox.checked = true;
                            }
                        });
                    }
                }
                
                document.getElementById('additional_instructions').value = homework.additional_instructions || '';
                
                // Show modal after data is loaded
                modal.classList.remove('hidden');
                
            } else {
                showNotification(data.message || '{{ __('components.sessions.homework.loading_error') }}', 'error');
            }
        } catch (error) {
            showNotification('{{ __('components.sessions.homework.loading_error') }}: ' + error.message, 'error');
        }
    });
    
    // Close modal handlers
    closeBtn?.addEventListener('click', () => modal.classList.add('hidden'));
    cancelBtn?.addEventListener('click', () => modal.classList.add('hidden'));
    
    // Close on backdrop click
    modal?.addEventListener('click', function(e) {
        if (e.target === modal) {
            modal.classList.add('hidden');
        }
    });
    
    // Form submission
    form?.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());
        
        // Convert boolean strings to actual booleans first
        data.has_new_memorization = data.has_new_memorization === 'on' || data.has_new_memorization === true;
        data.has_review = data.has_review === 'on' || data.has_review === true;  
        data.has_comprehensive_review = data.has_comprehensive_review === 'on' || data.has_comprehensive_review === true;

        // Handle comprehensive review surahs array from checkboxes
        if (data.has_comprehensive_review) {
            const checkedSurahs = [];
            const checkboxes = form.querySelectorAll('input[name="comprehensive_review_surahs[]"]:checked');
            checkboxes.forEach(checkbox => {
                checkedSurahs.push(checkbox.value);
            });
            data.comprehensive_review_surahs = checkedSurahs;
        } else {
            // Ensure the field is cleared when comprehensive review is disabled
            data.comprehensive_review_surahs = [];
        }
        
        // Validate that at least one homework type is selected
        if (!data.has_new_memorization && !data.has_review && !data.has_comprehensive_review) {
            showNotification('{{ __('components.sessions.homework.at_least_one_type') }}', 'error');
            return;
        }
        
        try {
            
            const response = await fetch(`{{ url('/') }}/teacher/sessions/{{ $session->id }}/homework`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify(data)
            });
            
            
            // Get response text first
            const responseText = await response.text();
            
            let result;
            try {
                result = JSON.parse(responseText);
            } catch (parseError) {
                throw new Error('Server response was not valid JSON: ' + responseText.substring(0, 100));
            }
            

            if (response.ok && result.success) {
                showNotification('{{ __('components.sessions.homework.saved_successfully') }}', 'success');
                modal.classList.add('hidden');
                
                // Reload page to show updated homework
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                const errorMessage = result.message || `HTTP {{ __('components.common.error') }}: ${response.status}`;
                
                // Show detailed validation errors if available
                if (result.errors) {
                    const errorDetails = Object.values(result.errors).flat().join(', ');
                    showNotification(`${errorMessage}: ${errorDetails}`, 'error');
                } else {
                    showNotification(errorMessage, 'error');
                }
            }
        } catch (error) {
            showNotification('{{ __('components.sessions.homework.connection_error') }}: ' + error.message, 'error');
        }
    });
    
    // Use unified toast notification system
    function showNotification(message, type = 'info') {
        if (window.toast) {
            window.toast.show({ type: type, message: message });
        } else {
        }
    }
});
</script>
