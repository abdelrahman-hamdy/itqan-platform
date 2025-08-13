@props([
    'circle',
    'viewType' => 'student' // 'student' or 'teacher'
])

@php
    $student = $circle->student;
    $studentName = $student->name ?? 'طالب';
@endphp

<!-- Circle Info Card -->
<x-circle.info-card :circle="$circle" :view-type="$viewType" context="individual" />





<!-- Learning Progress -->
<x-circle.learning-progress :circle="$circle" />
