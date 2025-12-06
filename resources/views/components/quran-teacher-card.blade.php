@props(['teacher', 'academy', 'showEnrollmentStatus' => false])

<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 card-hover">
  <!-- Header with Avatar beside Name and Status -->
  <div class="flex items-start gap-4 mb-4">
    <!-- Teacher Avatar -->
    <x-avatar
      :user="$teacher"
      size="md"
      userType="quran_teacher"
      :gender="$teacher->gender ?? $teacher->user?->gender ?? 'male'"
      class="flex-shrink-0" />
    
    <!-- Name and Description -->
    <div class="flex-1 min-w-0">
      <h3 class="font-semibold text-gray-900 mb-1 truncate">
        {{ $teacher->full_name ?? $teacher->user->name ?? '' }}
      </h3>
      @if($teacher->bio_arabic || $teacher->bio_english)
        <p class="text-sm text-gray-600 line-clamp-2">{{ $teacher->bio_arabic ?? $teacher->bio_english }}</p>
      @endif
    </div>
  </div>
  
  <div class="space-y-2">
    @if(!empty($teacher->certifications) && is_array($teacher->certifications) && count($teacher->certifications) > 0)
      <div class="flex items-center text-sm text-gray-600">
        <i class="ri-award-line ml-2"></i>
        <span>{{ implode('، ', $teacher->certifications) }}</span>
      </div>
    @elseif($teacher->educational_qualification)
      <div class="flex items-center text-sm text-gray-600">
        <i class="ri-book-line ml-2"></i>
        <span>{{ $teacher->educational_qualification }}</span>
      </div>
    @endif
    
    @if($teacher->teaching_experience_years)
      <div class="flex items-center text-sm text-gray-600">
        <i class="ri-time-line ml-2"></i>
        <span>{{ $teacher->teaching_experience_years }} سنوات خبرة</span>
      </div>
    @endif
    
    @if($teacher->total_students ?? 0)
      <div class="flex items-center text-sm text-gray-600">
        <i class="ri-group-line ml-2"></i>
        <span>{{ $teacher->total_students }} طالب</span>
      </div>
    @endif
    
    @if(($teacher->avg_rating ?? $teacher->rating ?? 0) > 0)
      <x-reviews.star-rating
        :rating="$teacher->avg_rating ?? $teacher->rating ?? 0"
        :total-reviews="$teacher->total_reviews ?? null"
        size="sm"
      />
    @endif
  </div>

  <!-- Action Button -->
  <div class="mt-6">
    <a href="{{ route('quran-teachers.show', ['subdomain' => $academy->subdomain, 'teacherId' => $teacher->id]) }}"
       class="w-full bg-primary text-white px-4 py-3 rounded-lg text-sm font-medium hover:bg-primary-700 transition-colors text-center block">
      عرض التفاصيل
    </a>
  </div>
</div>
