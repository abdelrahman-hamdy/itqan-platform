@props([
    'session',
    'homework' => null,
    'viewType' => 'student',
])

@php
    $sessionHomework = $homework ?? $session->sessionHomework;
    $hasHomework = $sessionHomework && $sessionHomework->has_any_homework;
@endphp

<!-- Homework Section -->
<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
    <h3 class="text-lg font-bold text-gray-900 mb-4">
        <i class="ri-book-open-line text-primary ms-2"></i>
        {{ __('components.sessions.homework.title') }}
    </h3>

    @if($hasHomework)
        @php
            $homeworkCount = 0;
            if($sessionHomework->has_new_memorization) $homeworkCount++;
            if($sessionHomework->has_review) $homeworkCount++;
            if($sessionHomework->has_comprehensive_review && $sessionHomework->comprehensive_review_surahs) $homeworkCount++;
        @endphp

        <div class="grid gap-4 mb-6 @if($homeworkCount == 1) grid-cols-1 @elseif($homeworkCount == 2) grid-cols-1 md:grid-cols-2 @else grid-cols-1 md:grid-cols-2 lg:grid-cols-3 @endif">
            @if($sessionHomework->has_new_memorization)
            <div class="bg-gradient-to-br from-green-50 to-emerald-50 rounded-xl p-5 border border-green-200 h-full">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-8 h-8 bg-green-600 rounded-full flex items-center justify-center shrink-0">
                        <i class="ri-book-open-line text-white text-sm"></i>
                    </div>
                    <span class="text-sm text-green-700 font-semibold">{{ __('components.sessions.homework.new_memorization') }}</span>
                </div>
                @if($sessionHomework->new_memorization_surah)
                <p class="text-green-900 font-bold text-lg mb-1">{{ \App\Enums\QuranSurah::getArabicName($sessionHomework->new_memorization_surah) }}</p>
                @endif
                @if($sessionHomework->new_memorization_pages)
                <p class="text-green-700 text-sm font-medium">{{ (float) $sessionHomework->new_memorization_pages }} {{ __('components.sessions.homework.pages_count') }}</p>
                @endif
            </div>
            @endif

            @if($sessionHomework->has_review)
            <div class="bg-gradient-to-br from-blue-50 to-cyan-50 rounded-xl p-5 border border-blue-200 h-full">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center shrink-0">
                        <i class="ri-refresh-line text-white text-sm"></i>
                    </div>
                    <span class="text-sm text-blue-700 font-semibold">{{ __('components.sessions.homework.review') }}</span>
                </div>
                @if($sessionHomework->review_surah)
                <p class="text-blue-900 font-bold text-lg mb-1">{{ \App\Enums\QuranSurah::getArabicName($sessionHomework->review_surah) }}</p>
                @endif
                @if($sessionHomework->review_pages)
                <p class="text-blue-700 text-sm font-medium">{{ (float) $sessionHomework->review_pages }} {{ __('components.sessions.homework.pages_count') }}</p>
                @endif
            </div>
            @endif

            @if($sessionHomework->has_comprehensive_review && $sessionHomework->comprehensive_review_surahs)
            <div class="bg-gradient-to-br from-purple-50 to-violet-50 rounded-xl p-5 border border-purple-200 h-full">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-8 h-8 bg-purple-600 rounded-full flex items-center justify-center shrink-0">
                        <i class="ri-list-check text-white text-sm"></i>
                    </div>
                    <span class="text-sm text-purple-700 font-semibold">{{ __('components.sessions.homework.comprehensive_review') }}</span>
                </div>
                <div class="text-purple-900 font-bold text-base leading-relaxed">
                    @php
                        $displaySurahs = [];
                        $savedSurahs = $sessionHomework->comprehensive_review_surahs;
                        if (is_string($savedSurahs)) {
                            $savedSurahs = json_decode($savedSurahs, true) ?: [];
                        }
                        if (is_array($savedSurahs)) {
                            $allSurahs = \App\Enums\QuranSurah::getAllSurahs();
                            foreach ($savedSurahs as $surahKey) {
                                if (isset($allSurahs[$surahKey])) {
                                    $displaySurahs[] = $allSurahs[$surahKey];
                                } else {
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

        @if($sessionHomework->additional_instructions)
        <div class="border-t border-gray-200 pt-6 mt-6">
            <div class="bg-gradient-to-br from-amber-50 to-yellow-50 rounded-xl p-5 border border-amber-200">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-8 h-8 bg-amber-600 rounded-full flex items-center justify-center shrink-0">
                        <i class="ri-information-line text-white text-sm"></i>
                    </div>
                    <h5 class="font-semibold text-amber-900">{{ __('components.sessions.homework.additional_instructions') }}</h5>
                </div>
                <p class="text-amber-800 leading-relaxed">{{ $sessionHomework->additional_instructions }}</p>
            </div>
        </div>
        @endif
    @else
        <!-- No Homework Assigned -->
        <div class="text-center py-12">
            <div class="w-20 h-20 mx-auto mb-4 bg-gray-100 rounded-full flex items-center justify-center">
                <i class="ri-file-text-line text-3xl text-gray-400"></i>
            </div>
            <h4 class="text-xl font-medium text-gray-900 mb-2">{{ __('components.sessions.homework.no_homework') }}</h4>
            <p class="text-gray-600">{{ __('components.sessions.homework.no_homework_message') }}</p>
        </div>
    @endif
</div>
