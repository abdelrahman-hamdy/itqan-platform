<x-layouts.authenticated role="student"
    title="{{ __('Payment Successful') }} - {{ config('app.name') }}">

    <div class="container mx-auto px-4 py-8">
        <div class="max-w-md mx-auto bg-white rounded-xl shadow-lg p-8 text-center">
            {{-- Success Icon --}}
            <div class="w-24 h-24 mx-auto mb-6 bg-green-50 rounded-full flex items-center justify-center">
                <svg class="w-12 h-12 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
            </div>

            {{-- Success Message --}}
            <h1 class="text-2xl font-bold text-gray-900 mb-3">
                {{ __('Payment Successful!') }}
            </h1>

            <p class="text-gray-600 mb-6">
                {{ __('Your subscription is now active. Returning to the mobile app...') }}
            </p>

            {{-- Auto-redirect Countdown --}}
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                <p class="text-sm text-blue-800 mb-2">
                    <span id="countdown-text">{{ __('Auto-redirecting in :seconds seconds', ['seconds' => $auto_redirect_seconds]) }}</span>
                </p>
                <div class="h-2 bg-blue-200 rounded-full overflow-hidden">
                    <div id="progress-bar" class="h-full bg-blue-500 transition-all duration-1000" style="width: 0%"></div>
                </div>
            </div>

            {{-- Manual Return Button --}}
            <a href="{{ $deeplink_url }}"
               class="inline-flex items-center justify-center gap-2 bg-primary-600 text-white px-6 py-3 rounded-lg hover:bg-primary-700 transition-colors mb-4 w-full">
                <i class="ri-smartphone-line text-xl"></i>
                <span>{{ __('Return to App Now') }}</span>
            </a>

            {{-- Fallback Instructions --}}
            <p class="text-sm text-gray-500">
                {{ __('If the app does not open automatically, tap the button above.') }}
            </p>

            {{-- Subscription Details (Optional) --}}
            @if(isset($subscription))
            <div class="mt-8 pt-6 border-t border-gray-200 text-start">
                <h3 class="text-sm font-semibold text-gray-700 mb-3">{{ __('Subscription Details') }}</h3>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-gray-600">{{ __('Status') }}</dt>
                        <dd class="font-medium text-green-600">{{ __('Active') }}</dd>
                    </div>
                    @if($subscription->starts_at)
                    <div class="flex justify-between">
                        <dt class="text-gray-600">{{ __('Start Date') }}</dt>
                        <dd class="font-medium text-gray-900">{{ $subscription->starts_at->format('Y-m-d') }}</dd>
                    </div>
                    @endif
                    @if($subscription->ends_at)
                    <div class="flex justify-between">
                        <dt class="text-gray-600">{{ __('End Date') }}</dt>
                        <dd class="font-medium text-gray-900">{{ $subscription->ends_at->format('Y-m-d') }}</dd>
                    </div>
                    @endif
                </dl>
            </div>
            @endif
        </div>
    </div>

    @push('scripts')
    <script>
        // Auto-redirect countdown
        let seconds = {{ $auto_redirect_seconds }};
        const progressBar = document.getElementById('progress-bar');
        const countdownText = document.getElementById('countdown-text');

        const interval = setInterval(() => {
            seconds--;
            const progress = (({{ $auto_redirect_seconds }} - seconds) / {{ $auto_redirect_seconds }}) * 100;
            progressBar.style.width = progress + '%';

            if (countdownText) {
                countdownText.textContent = `{{ __('Auto-redirecting in') }} ${seconds} {{ __('seconds') }}`;
            }

            if (seconds <= 0) {
                clearInterval(interval);
                window.location.href = {!! json_encode($deeplink_url ?? '') !!};
            }
        }, 1000);

        // Immediate redirect on page load (some browsers need this)
        setTimeout(() => {
            window.location.href = {!! json_encode($deeplink_url ?? '') !!};
        }, {{ $auto_redirect_seconds * 1000 }});
    </script>
    @endpush
</x-layouts.authenticated>
