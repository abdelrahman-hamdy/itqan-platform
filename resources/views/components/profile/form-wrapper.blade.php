@props(['title', 'description', 'action', 'method' => 'POST', 'enctype' => null, 'backRoute'])

<div class="max-w-4xl mx-auto">

    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">{{ $title }}</h1>
        <p class="text-gray-600">{{ $description }}</p>
    </div>

    <!-- Form Container -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="p-8">
            <form action="{{ $action }}" method="POST" {{ $enctype ? "enctype={$enctype}" : '' }}>
                @csrf
                @if($method !== 'POST')
                    @method($method)
                @endif

                {{ $slot }}

                <!-- Form Actions -->
                <div class="flex items-center justify-end gap-4 mt-8 pt-6 border-t border-gray-200">
                    @if(isset($backRoute))
                        <a href="{{ $backRoute }}"
                           class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-100 transition-colors">
                            {{ __('common.actions.cancel') }}
                        </a>
                    @endif
                    <button type="submit"
                            class="px-6 py-3 bg-primary text-white rounded-lg hover:bg-primary-600 transition-colors">
                        {{ __('common.actions.save_changes') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Note: Success messages are now handled by the unified toast notification system (x-ui.toast-container) --}}

</div>
