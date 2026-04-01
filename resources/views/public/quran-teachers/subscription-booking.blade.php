<x-layouts.student>

@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain;
@endphp

<div class="max-w-2xl mx-auto px-4 py-8">
    <x-ui.breadcrumb
        :items="[
            ['label' => __('student.subscriptions.page_title'), 'url' => route('student.subscriptions', ['subdomain' => $subdomain])],
            ['label' => __('public.booking.quran.title')],
        ]"
        view-type="student"
    />

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden mt-6">
        {{-- Header --}}
        <div class="px-6 py-5 border-b border-gray-100 bg-gradient-to-l from-indigo-50 to-white">
            <h1 class="text-xl font-bold text-gray-900">
                {{ __('public.booking.quran.title') }}
            </h1>
            <p class="text-sm text-gray-600 mt-1">
                {{ __('public.booking.quran.form.subtitle') }}
            </p>
        </div>

        {{-- Info Sections (compact, collapsible) --}}
        <div class="px-6 py-4 bg-gray-50 border-b border-gray-100 space-y-4">
            <x-subscription.teacher-info-card :teacher="$teacher" teacherType="quran" :compact="true" />
            <div class="border-t border-gray-200"></div>
            <x-subscription.student-info :user="auth()->user()" :compact="true" />
            <div class="border-t border-gray-200"></div>
            <x-subscription.package-info-card :package="$package" packageType="quran" :selectedPeriod="$selectedPeriod ?? 'monthly'" :compact="true" :academy="$academy" />
        </div>

        {{-- Booking Form --}}
        <x-subscription.booking-form
            type="quran"
            :teacher="$teacher"
            :package="$package"
            :academy="$academy"
            :formAction="route('quran-teachers.subscribe.submit', ['subdomain' => $subdomain, 'teacherId' => $teacher->id, 'packageId' => $package->id])"
            :selectedPeriod="$selectedPeriod ?? 'monthly'" />
    </div>
</div>

</x-layouts.student>
