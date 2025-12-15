@props([
    'user',
    'userType', // 'student', 'quran_teacher', 'academic_teacher', 'parent'
    'displayName',
    'roleLabel',
    'gender' => 'male',
    'phone' => null,
    'profileRoute' => null, // Route to navigate when clicking on profile
])

<!-- Profile Section -->
<a href="{{ $profileRoute ?? '#' }}"
   id="profile-section"
   class="block p-6 border-b border-gray-200 bg-gradient-to-br from-gray-50 to-gray-100/50 transition-all duration-300 {{ $profileRoute ? 'hover:bg-gray-100/80 cursor-pointer' : '' }}">
  <div id="profile-content" class="flex flex-col items-center text-center mb-4 transition-all duration-300">
    <x-avatar
      :user="$user"
      size="md"
      :userType="$userType"
      :gender="$gender"
      class="mb-3" />

    <div id="profile-info" class="transition-all duration-300">
      <h3 class="text-lg font-semibold text-gray-900">
        {{ $displayName }}
      </h3>
      <p class="text-xs text-gray-400">
        {{ $roleLabel }}
      </p>
    </div>
  </div>

  <!-- Contact Info -->
  <div id="student-info" class="teacher-info space-y-2 text-sm transition-all duration-300">
    @if($phone)
    <div class="flex items-center justify-center text-gray-600">
      <i class="ri-phone-line ml-2 text-gray-400"></i>
      <span>{{ $phone }}</span>
    </div>
    @endif
    <div class="flex items-center justify-center text-gray-600">
      <i class="ri-mail-line ml-2 text-gray-400"></i>
      <span class="truncate">{{ $user?->email ?? 'غير محدد' }}</span>
    </div>
  </div>
</a>
