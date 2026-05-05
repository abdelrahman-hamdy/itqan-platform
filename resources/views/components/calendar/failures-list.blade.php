@props(['textClass' => 'text-red-700'])

<template x-if="failures && failures.length">
    <ul class="mt-2 ps-5 list-disc text-xs {{ $textClass }} space-y-0.5">
        <template x-for="(f, index) in failures.slice(0, 5)" :key="index">
            <li><span x-text="f.date"></span> — <span x-text="f.reason"></span></li>
        </template>
        <template x-if="failures.length > 5">
            <li x-text="`+ ${failures.length - 5}…`"></li>
        </template>
    </ul>
</template>
