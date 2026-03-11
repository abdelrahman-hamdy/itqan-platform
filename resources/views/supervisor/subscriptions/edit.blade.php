<x-layouts.supervisor>

@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';
    $studentName = $type === 'quran' ? ($subscription->student?->name ?? '-') : ($subscription->student?->name ?? '-');
    $teacherName = $type === 'quran' ? ($subscription->quranTeacherUser?->name ?? '-') : ($subscription->teacher?->user?->name ?? '-');
@endphp

<div class="max-w-3xl mx-auto">
    <x-ui.breadcrumb
        :items="[
            ['label' => __('supervisor.subscriptions.page_title'), 'url' => route('manage.subscriptions.index', ['subdomain' => $subdomain])],
            ['label' => $studentName, 'url' => route('manage.subscriptions.show', ['subdomain' => $subdomain, 'type' => $type, 'id' => $subscription->id])],
            ['label' => __('supervisor.subscriptions.edit_title')],
        ]"
        view-type="supervisor"
    />

    <div class="mb-6">
        <h1 class="text-xl sm:text-2xl font-bold text-gray-900">{{ __('supervisor.subscriptions.edit_title') }}</h1>
        <p class="mt-1 text-sm text-gray-600">
            {{ $studentName }} - {{ $teacherName }}
            <span class="inline-flex items-center px-2 py-0.5 text-xs rounded-full {{ $type === 'quran' ? 'bg-green-100 text-green-700' : 'bg-violet-100 text-violet-700' }} ms-1">
                {{ $type === 'quran' ? __('supervisor.subscriptions.type_quran') : __('supervisor.subscriptions.type_academic') }}
            </span>
        </p>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <form method="POST" action="{{ route('manage.subscriptions.update', ['subdomain' => $subdomain, 'type' => $type, 'id' => $subscription->id]) }}">
            @csrf
            @method('PUT')

            {{-- Total Sessions --}}
            <div class="mb-5">
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('supervisor.subscriptions.field_total_sessions') }}</label>
                <input type="number" name="total_sessions" value="{{ old('total_sessions', $subscription->total_sessions) }}" min="1" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                @error('total_sessions') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Dates --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('supervisor.subscriptions.field_start_date') }}</label>
                    <input type="date" name="starts_at" value="{{ old('starts_at', $subscription->starts_at?->format('Y-m-d')) }}" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('starts_at') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('supervisor.subscriptions.field_end_date') }}</label>
                    <input type="date" name="ends_at" value="{{ old('ends_at', $subscription->ends_at?->format('Y-m-d')) }}" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('ends_at') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- Notes --}}
            <div class="mb-5">
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('supervisor.subscriptions.field_notes') }}</label>
                <textarea name="notes" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">{{ old('notes', $subscription->notes) }}</textarea>
                @error('notes') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Actions --}}
            <div class="flex items-center gap-3 pt-4 border-t border-gray-200">
                <button type="submit"
                        class="px-6 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors">
                    {{ __('supervisor.subscriptions.btn_save') }}
                </button>
                <a href="{{ route('manage.subscriptions.show', ['subdomain' => $subdomain, 'type' => $type, 'id' => $subscription->id]) }}"
                   class="px-6 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200 transition-colors">
                    {{ __('supervisor.subscriptions.btn_cancel') }}
                </a>
            </div>
        </form>
    </div>
</div>

</x-layouts.supervisor>
