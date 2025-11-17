{{-- Alias component for student layout --}}
@props(['title' => '', 'description' => ''])

<x-layouts.student :title="$title" :description="$description">
    @isset($head)
        <x-slot name="head">
            {{ $head }}
        </x-slot>
    @endisset

    {{ $slot }}
</x-layouts.student>
