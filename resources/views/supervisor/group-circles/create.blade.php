<x-layouts.supervisor>

@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';
@endphp

<div>
    <x-ui.breadcrumb
        :items="[
            ['label' => __('supervisor.group_circles.breadcrumb'), 'route' => route('manage.group-circles.index', ['subdomain' => $subdomain])],
            ['label' => __('supervisor.group_circles.breadcrumb_create')],
        ]"
        view-type="supervisor"
    />

    <div class="mb-6 md:mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-900">{{ __('supervisor.group_circles.create_circle') }}</h1>
            <p class="mt-1 md:mt-2 text-sm md:text-base text-gray-600">{{ __('supervisor.group_circles.page_subtitle_create') }}</p>
        </div>
        <a href="{{ route('manage.group-circles.index', ['subdomain' => $subdomain]) }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-lg transition-colors">
            <i class="ri-arrow-go-back-line"></i>
            {{ __('supervisor.group_circles.cancel') }}
        </a>
    </div>

    <form method="POST" action="{{ route('manage.group-circles.store', ['subdomain' => $subdomain]) }}" class="max-w-4xl">
        @csrf

        @include('supervisor.group-circles._form', ['circle' => null])

        <div class="mt-6 flex flex-col-reverse sm:flex-row sm:items-center sm:justify-end gap-3">
            <a href="{{ route('manage.group-circles.index', ['subdomain' => $subdomain]) }}"
               class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 text-sm font-medium rounded-lg transition-colors">
                {{ __('supervisor.group_circles.cancel') }}
            </a>
            <button type="submit"
                    class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-green-600 hover:bg-green-700 text-white text-sm font-semibold rounded-lg transition-colors shadow-sm">
                <i class="ri-add-line"></i>
                {{ __('supervisor.group_circles.submit_create') }}
            </button>
        </div>
    </form>
</div>

</x-layouts.supervisor>
