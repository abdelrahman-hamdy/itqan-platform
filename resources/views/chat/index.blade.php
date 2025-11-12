@php
  $user = auth()->user();
  $userType = $user->user_type;

  // Determine which view to include based on user type
  $viewName = match($userType) {
    'student' => 'chat.student',
    'quran_teacher', 'academic_teacher' => 'chat.teacher',
    'parent' => 'chat.parent',
    'academy_admin' => 'chat.academy-admin',
    'super_admin' => 'chat.admin',
    default => 'chat.default'
  };
@endphp

{{-- Include the appropriate view based on user type --}}
@include($viewName)