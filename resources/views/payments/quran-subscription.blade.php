{{-- Minimal gateway selection page for quran subscription payment retry --}}
{{-- This page only shows when multiple gateways are available. --}}
{{-- If only one gateway exists, the controller auto-redirects without showing this page. --}}

<x-layouts.authenticated role="student"
    title="{{ __('payments.quran_payment.page_title') }} - {{ $academy->name ?? '' }}">

    @livewire('payment.payment-gateway-modal', ['academyId' => $academy->id])

    <div class="max-w-lg mx-auto py-12">
        {{-- Flash Messages --}}
        @if(session('error'))
            <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl flex items-center gap-3">
                <i class="ri-error-warning-fill text-red-600 text-xl"></i>
                <span class="text-red-800">{{ session('error') }}</span>
            </div>
        @endif

        {{-- Loading state while gateway modal opens --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8 text-center" id="gateway-loading">
            <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-blue-50 flex items-center justify-center">
                <i class="ri-secure-payment-line text-blue-600 text-2xl"></i>
            </div>
            <h2 class="text-lg font-bold text-gray-900 mb-2">{{ __('payments.gateway_selection.title') }}</h2>
            <p class="text-gray-600 text-sm mb-6">{{ __('payments.gateway_selection.subtitle') }}</p>

            <div class="animate-pulse flex justify-center">
                <div class="w-8 h-8 border-2 border-blue-200 border-t-blue-600 rounded-full animate-spin"></div>
            </div>
        </div>

        {{-- Hidden form that submits after gateway selection --}}
        <form id="gateway-form" method="POST"
              action="{{ route('quran.subscription.payment.submit', ['subdomain' => $academy->subdomain, 'subscription' => $subscription->id]) }}"
              style="display: none;">
            @csrf
            <input type="hidden" name="payment_gateway" id="selected_gateway">
        </form>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-open gateway selection modal
            setTimeout(() => {
                if (typeof Livewire !== 'undefined') {
                    Livewire.dispatch('openGatewaySelection');
                }
            }, 300);

            // When gateway is selected, submit the form
            if (typeof Livewire !== 'undefined') {
                Livewire.on('gatewaySelected', ({ gateway }) => {
                    document.getElementById('selected_gateway').value = gateway;
                    document.getElementById('gateway-loading').innerHTML = `
                        <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-blue-50 flex items-center justify-center">
                            <div class="w-8 h-8 border-2 border-blue-200 border-t-blue-600 rounded-full animate-spin"></div>
                        </div>
                        <h2 class="text-lg font-bold text-gray-900 mb-2">{{ __('payments.academic_payment.redirecting') }}</h2>
                    `;
                    document.getElementById('gateway-form').submit();
                });
            }
        });
    </script>
    @endpush

</x-layouts.authenticated>
