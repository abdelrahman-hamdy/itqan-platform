@props([
    'title' => '',
    'description' => '',
    'layoutType' => 'student', // student, teacher, parent, supervisor
])

@php
// Determine which layout component to use based on user role
$layoutComponent = match($layoutType) {
    'teacher' => 'layouts.teacher',
    'supervisor' => 'layouts.supervisor',
    'parent' => 'layouts.parent-layout',
    default => 'layouts.student',
};
@endphp

<x-dynamic-component :component="$layoutComponent" :title="$title" :description="$description">
    {{ $slot }}
</x-dynamic-component>
