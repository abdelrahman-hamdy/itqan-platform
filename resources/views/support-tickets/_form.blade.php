@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';
    $routePrefix = auth()->user()->isStudent() ? 'student.support' : 'teacher.support';
    $reasons = \App\Enums\SupportTicketReason::options();
@endphp

<!-- Page Header -->
<div class="mb-6 md:mb-8">
    <h1 class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-900">{{ __('support.new_ticket') }}</h1>
    <p class="mt-1 text-sm text-gray-600">{{ __('support.page_title') }}</p>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 md:p-6">
    <form action="{{ route($routePrefix . '.store', ['subdomain' => $subdomain]) }}" method="POST" enctype="multipart/form-data">
        @csrf

        <!-- Reason -->
        <div class="mb-5">
            <label for="reason" class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('support.reason_label') }} <span class="text-red-500">*</span></label>
            <select name="reason" id="reason" required
                    class="w-full rounded-lg border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 text-sm">
                <option value="">{{ __('support.reason_placeholder') }}</option>
                @foreach($reasons as $value => $label)
                    <option value="{{ $value }}" {{ old('reason') === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            @error('reason')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Description -->
        <div class="mb-5">
            <label for="description" class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('support.description_label') }} <span class="text-red-500">*</span></label>
            <textarea name="description" id="description" rows="5" required minlength="10" maxlength="2000"
                      class="w-full rounded-lg border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 text-sm"
                      placeholder="{{ __('support.description_placeholder') }}">{{ old('description') }}</textarea>
            @error('description')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Image -->
        <div class="mb-6">
            <label for="image" class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('support.image_label') }}</label>
            <input type="file" name="image" id="image" accept="image/jpeg,image/png,image/webp"
                   class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100">
            <p class="mt-1 text-xs text-gray-500">{{ __('support.image_hint') }}</p>
            @error('image')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Submit -->
        <div class="flex items-center gap-3">
            <button type="submit"
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-emerald-600 text-white rounded-xl hover:bg-emerald-700 transition-colors text-sm font-medium">
                <i class="ri-send-plane-line"></i>
                {{ __('support.submit') }}
            </button>
            <a href="{{ route($routePrefix . '.index', ['subdomain' => $subdomain]) }}"
               class="inline-flex items-center gap-2 px-5 py-2.5 bg-gray-100 text-gray-700 rounded-xl hover:bg-gray-200 transition-colors text-sm font-medium">
                {{ __('support.my_tickets') }}
            </a>
        </div>
    </form>
</div>
