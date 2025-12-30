@props([
    'session',
    'homework' => null,
    'viewType' => 'student',
    'sessionType' => 'quran'
])

@php
    // Handle different session types
    if ($sessionType === 'academic') {
        // Academic session homework - simpler structure
        $hasHomework = !empty($session->homework_description) || !empty($session->homework_file);
        $sessionHomework = null;
    } else {
        // Quran session homework - evaluated orally in session reports
        $sessionHomework = $session->sessionHomework;
        $hasHomework = $sessionHomework && $sessionHomework->has_any_homework;
    }
@endphp

<!-- Homework Section -->
<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ __('components.homework.display.title') }}</h3>
    
    @if($sessionType === 'academic' && $hasHomework)
        <!-- Academic Homework Section -->
        <div class="space-y-6">
            <!-- Academic Homework Overview -->
            <div class="bg-gradient-to-r from-purple-50 to-indigo-50 rounded-lg p-4 border border-purple-200">
                <div class="flex items-center justify-between mb-3">
                    <h4 class="font-semibold text-purple-900">{{ __('components.homework.display.academic_homework') }}</h4>
                    @if($session->status === \App\Enums\SessionStatus::COMPLETED && $session->session_grade)
                        <span class="text-xs px-2 py-1 rounded-full bg-purple-100 text-purple-800">
                            {{ __('components.homework.display.grading_score') }} {{ $session->session_grade }}/10
                        </span>
                    @endif
                </div>
                
                @if($session->homework_description)
                <div class="space-y-3">
                    <div class="bg-white rounded-lg p-4 border border-purple-100">
                        <h5 class="font-medium text-purple-900 mb-2">
                            <i class="ri-clipboard-line text-purple-600 ms-2"></i>
                            {{ __('components.homework.display.homework_description_label') }}
                        </h5>
                        <div class="text-gray-700 leading-relaxed">
                            {{ $session->homework_description }}
                        </div>
                    </div>
                    
                    @if($session->homework_file)
                    <div class="bg-white rounded-lg p-4 border border-purple-100">
                        <h5 class="font-medium text-purple-900 mb-2">
                            <i class="ri-attachment-line text-purple-600 ms-2"></i>
                            {{ __('components.homework.display.attached_file_label') }}
                        </h5>
                        <a href="{{ Storage::url($session->homework_file) }}" 
                           target="_blank"
                           class="inline-flex items-center text-purple-600 hover:text-purple-800 transition-colors">
                            <i class="ri-download-line ms-2"></i>
                            {{ __('components.homework.display.download_file') }}
                        </a>
                    </div>
                    @endif
                </div>
                @endif
            </div>

            <!-- Student Homework Submission (for academic sessions) -->
            @if($viewType === 'student')
            <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                <h5 class="font-medium text-gray-900 mb-3">
                    <i class="ri-send-plane-line text-gray-600 ms-2"></i>
                    تسليم الواجب
                </h5>
                
                <!-- Check if homework already submitted -->
                @php
                    $submittedHomework = $session->sessionReports()
                        ->where('student_id', auth()->id())
                        ->whereNotNull('homework_description')
                        ->first();
                @endphp
                
                @if($submittedHomework && $submittedHomework->homework_description)
                    <!-- Already submitted -->
                    <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                        <div class="flex items-center mb-2">
                            <i class="ri-check-circle-line text-green-600 ms-2"></i>
                            <span class="font-medium text-green-800">{{ __('components.homework.display.submission_received') }}</span>
                        </div>
                        <div class="text-sm text-green-700 mb-3">
                            {{ $submittedHomework->homework_description }}
                        </div>
                        @if($submittedHomework->homework_file)
                            <a href="{{ Storage::url($submittedHomework->homework_file) }}" 
                               target="_blank"
                               class="inline-flex items-center text-green-600 hover:text-green-800 text-sm">
                                <i class="ri-attachment-line ms-1"></i>
                                الملف المرفق
                            </a>
                        @endif
                        
                        @if($submittedHomework->homework_degree !== null)
                            <div class="mt-3 pt-3 border-t border-green-200">
                                <div class="text-sm text-green-800">
                                    <span class="font-medium">{{ __('components.homework.display.grade_label') }}</span>
                                    <span class="text-lg font-bold">{{ $submittedHomework->homework_degree }}/10</span>
                                </div>
                            </div>
                        @endif
                    </div>
                @else
                    <!-- Homework submission form -->
                    <form id="homeworkSubmissionForm" class="space-y-4" enctype="multipart/form-data">
                        @csrf
                        <div>
                            <label for="homework_submission" class="block text-sm font-medium text-gray-700 mb-2">
                                {{ __('components.homework.display.submission_form_title') }}
                            </label>
                            <textarea 
                                id="homework_submission" 
                                name="homework_submission" 
                                rows="4" 
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-purple-500 focus:border-purple-500"
                                placeholder="{{ __('components.homework.submission.solution_placeholder') }}" 
                                required></textarea>
                        </div>
                        
                        <div>
                            <label for="homework_file" class="block text-sm font-medium text-gray-700 mb-2">
                                {{ __('components.homework.display.file_upload_label') }}
                            </label>
                            <input 
                                type="file" 
                                id="homework_file" 
                                name="homework_file" 
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-purple-500 focus:border-purple-500"
                                accept=".pdf,.doc,.docx,.txt,.jpg,.jpeg,.png">
                            <p class="text-xs text-gray-500 mt-1">
                                الملفات المدعومة: PDF, Word, صور, نصوص (حد أقصى 10MB)
                            </p>
                        </div>
                        
                        <button 
                            type="submit" 
                            class="w-full bg-purple-600 text-white py-2 px-4 rounded-lg hover:bg-purple-700 transition-colors">
                            <i class="ri-send-plane-line ms-2"></i>
                            {{ __('components.homework.display.submit_button') }}
                        </button>
                    </form>
                @endif
            </div>
            @endif
        </div>
    @elseif($sessionType === 'quran' && $sessionHomework && $sessionHomework->has_any_homework)
        <div class="space-y-6">
            <!-- Homework Overview -->
            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-lg p-4 border border-blue-200">
                <div class="flex items-center mb-3">
                    <h4 class="font-semibold text-blue-900">واجب الجلسة</h4>
                </div>
                
                @if($sessionHomework->due_date)
                <div class="flex items-center gap-2 text-sm text-blue-700">
                    <i class="ri-calendar-line"></i>
                    <span>{{ __('components.homework.display.due_date_label') }} {{ $sessionHomework->due_date->format('Y/m/d') }}</span>
                    @if($sessionHomework->is_overdue)
                        <span class="text-red-600 font-medium">{{ __('components.homework.display.overdue') }}</span>
                    @endif
                </div>
                @endif
            </div>

            <!-- New Memorization -->
            @if($sessionHomework->has_new_memorization)
            <div class="border border-green-200 rounded-lg p-4 bg-green-50">
                <div class="flex items-center gap-2 mb-3">
                    <div class="w-8 h-8 bg-green-600 rounded-full flex items-center justify-center">
                        <i class="ri-book-open-line text-white text-sm"></i>
                    </div>
                    <h5 class="font-semibold text-green-900">{{ __('components.homework.display.memorization_section') }}</h5>
                </div>
                
                <div class="space-y-2 text-sm">
                    @if($sessionHomework->new_memorization_surah)
                    <div class="flex items-center gap-2">
                        <i class="ri-bookmark-line text-green-600"></i>
                        <span class="text-green-800">{{ __('components.homework.display.surah_label') }} {{ $sessionHomework->new_memorization_surah }}</span>
                    </div>
                    @endif
                    
                    @if($sessionHomework->new_memorization_pages)
                    <div class="flex items-center gap-2">
                        <i class="ri-file-list-line text-green-600"></i>
                        <span class="text-green-800">{{ __('components.homework.display.pages_label') }} {{ $sessionHomework->new_memorization_pages }} {{ __('components.homework.display.pages_unit') }}</span>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            <!-- Review -->
            @if($sessionHomework->has_review)
            <div class="border border-blue-200 rounded-lg p-4 bg-blue-50">
                <div class="flex items-center gap-2 mb-3">
                    <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center">
                        <i class="ri-refresh-line text-white text-sm"></i>
                    </div>
                    <h5 class="font-semibold text-blue-900">{{ __('components.homework.display.review_section') }}</h5>
                </div>
                
                <div class="space-y-2 text-sm">
                    @if($sessionHomework->review_surah)
                    <div class="flex items-center gap-2">
                        <i class="ri-bookmark-line text-blue-600"></i>
                        <span class="text-blue-800">{{ __('components.homework.display.surah_label') }} {{ $sessionHomework->review_surah }}</span>
                    </div>
                    @endif
                    
                    @if($sessionHomework->review_pages)
                    <div class="flex items-center gap-2">
                        <i class="ri-file-list-line text-blue-600"></i>
                        <span class="text-blue-800">{{ __('components.homework.display.pages_label') }} {{ $sessionHomework->review_pages }} {{ __('components.homework.display.pages_unit') }}</span>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            <!-- Comprehensive Review -->
            @if($sessionHomework->has_comprehensive_review && $sessionHomework->comprehensive_review_surahs)
            <div class="border border-purple-200 rounded-lg p-4 bg-purple-50">
                <div class="flex items-center gap-2 mb-3">
                    <div class="w-8 h-8 bg-purple-600 rounded-full flex items-center justify-center">
                        <i class="ri-stack-line text-white text-sm"></i>
                    </div>
                    <h5 class="font-semibold text-purple-900">{{ __('components.homework.display.comprehensive_review_section') }}</h5>
                </div>
                
                <div class="space-y-2 text-sm">
                    <div class="flex items-start gap-2">
                        <i class="ri-list-check text-purple-600 mt-1"></i>
                        <div>
                            <span class="text-purple-800 font-medium">{{ __('components.homework.display.required_surahs') }}</span>
                            <div class="flex flex-wrap gap-1 mt-1">
                                @foreach($sessionHomework->comprehensive_review_surahs as $surah)
                                <span class="inline-block bg-white text-purple-800 text-xs px-2 py-1 rounded border border-purple-200">
                                    {{ $surah }}
                                </span>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Additional Instructions -->
            @if($sessionHomework->additional_instructions)
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <div class="flex items-center gap-2 mb-2">
                    <i class="ri-lightbulb-line text-yellow-600"></i>
                    <h5 class="font-semibold text-yellow-900">{{ __('components.homework.display.additional_instructions_label') }}</h5>
                </div>
                <p class="text-yellow-800 text-sm">{{ $sessionHomework->additional_instructions }}</p>
            </div>
            @endif
        </div>
    @else
        <!-- No Homework -->
        <div class="text-center py-8">
            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="ri-file-list-line text-gray-400 text-2xl"></i>
            </div>
            <h4 class="text-lg font-medium text-gray-900 mb-2">{{ __('components.homework.display.no_homework_title') }}</h4>
            <p class="text-gray-600 text-sm">
                @if($sessionType === 'academic')
                    {{ __('components.homework.display.no_homework_academic') }}
                @else
                    {{ __('components.homework.display.no_homework_quran') }}
                @endif
            </p>
        </div>
    @endif
</div>