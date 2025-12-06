@props([
    'teacher',
    'color' => 'yellow' // yellow or violet
])

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
  @if($teacher->educational_qualification || $teacher->qualification)
    <div class="flex items-start gap-3">
      <i class="ri-graduation-cap-line text-{{ $color }}-600 text-xl mt-0.5"></i>
      <div>
        <div class="text-sm text-gray-500 mb-1">المؤهل التعليمي</div>
        <div class="text-gray-900 font-medium">
          @if($teacher->educational_qualification)
            {{ \App\Enums\EducationalQualification::getLabel($teacher->educational_qualification) }}
          @else
            {{ $teacher->qualification }}
          @endif
        </div>
      </div>
    </div>
  @endif

  @if($teacher->teaching_experience_years || $teacher->experience_years)
    <div class="flex items-start gap-3">
      <i class="ri-time-line text-{{ $color }}-600 text-xl mt-0.5"></i>
      <div>
        <div class="text-sm text-gray-500 mb-1">الخبرة التدريسية</div>
        <div class="text-gray-900 font-medium">
          {{ $teacher->teaching_experience_years ?? $teacher->experience_years }} سنوات
          {{ $color === 'yellow' ? 'في تعليم القرآن الكريم' : 'في التدريس الأكاديمي' }}
        </div>
      </div>
    </div>
  @endif

  @if($teacher->certifications && is_array($teacher->certifications) && count($teacher->certifications) > 0)
    <div class="flex items-start gap-3">
      <i class="ri-medal-line text-{{ $color }}-600 text-xl mt-0.5"></i>
      <div class="flex-1">
        <div class="text-sm text-gray-500 mb-2">{{ $color === 'yellow' ? 'الشهادات والإجازات' : 'الشهادات والتخصصات' }}</div>
        <ul class="space-y-2">
          @foreach($teacher->certifications as $certification)
            <li class="flex items-start gap-2 text-gray-900">
              <i class="ri-check-line text-green-600 mt-0.5 text-sm"></i>
              <span>{{ $certification }}</span>
            </li>
          @endforeach
        </ul>
      </div>
    </div>
  @endif

  @if($teacher->languages && is_array($teacher->languages) && count($teacher->languages) > 0)
    <div class="flex items-start gap-3">
      <i class="ri-global-line text-{{ $color }}-600 text-xl mt-0.5"></i>
      <div>
        <div class="text-sm text-gray-500 mb-2">اللغات</div>
        <div class="flex flex-wrap gap-2">
          @foreach($teacher->languages as $language)
            @php
              $languageNames = [
                'arabic' => 'العربية',
                'english' => 'الإنجليزية',
                'french' => 'الفرنسية',
                'german' => 'الألمانية',
                'turkish' => 'التركية',
                'spanish' => 'الإسبانية',
                'urdu' => 'الأردية',
                'persian' => 'الفارسية'
              ];
            @endphp
            <span class="px-3 py-1 bg-gray-100 text-gray-700 rounded-lg text-sm">{{ $languageNames[$language] ?? $language }}</span>
          @endforeach
        </div>
      </div>
    </div>
  @endif
</div>
