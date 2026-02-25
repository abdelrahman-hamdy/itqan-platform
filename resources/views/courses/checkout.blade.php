@extends('components.layouts.student')

@section('title', __('courses.checkout.breadcrumb.payment') . ': ' . $course->title . ' - ' . $academy->name)

@section('content')
@livewire('payment.payment-gateway-modal', ['academyId' => $academy->id])

<div class="min-h-screen bg-gray-50">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <!-- Breadcrumb -->
            <nav class="mb-8">
                <ol class="flex items-center gap-2 text-sm text-gray-600">
                    <li><a href="{{ route('courses.index', ['subdomain' => $academy->subdomain]) }}" class="hover:text-primary">{{ __('courses.checkout.breadcrumb.courses') }}</a></li>
                    <li>/</li>
                    <li><a href="{{ route('courses.show', ['subdomain' => $academy->subdomain, 'id' => $course->id]) }}" class="hover:text-primary">{{ $course->title }}</a></li>
                    <li>/</li>
                    <li class="text-gray-900">{{ __('courses.checkout.breadcrumb.payment') }}</li>
                </ol>
            </nav>

            <!-- Page Header -->
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-gray-900 mb-2">{{ __('courses.checkout.title') }}</h1>
                <p class="text-gray-600">{{ __('courses.checkout.subtitle') }}</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Checkout Form -->
                <div class="lg:col-span-2">
                    <!-- Course Summary -->
                    <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
                        <h2 class="text-xl font-bold text-gray-900 mb-4">{{ __('courses.checkout.order_summary.title') }}</h2>
                        <div class="flex items-start gap-4">
                            @if($course->thumbnail_url)
                            <div class="w-20 h-16 bg-gray-200 rounded-lg overflow-hidden shrink-0">
                                <img src="{{ $course->thumbnail_url }}"
                                     alt="{{ $course->title }}"
                                     class="w-full h-full object-cover">
                            </div>
                            @endif
                            <div class="flex-1">
                                <h3 class="font-semibold text-gray-900 mb-2">{{ $course->title }}</h3>
                                <div class="flex items-center gap-4 text-sm text-gray-600">
                                    <span class="flex items-center gap-1"><i class="ri-time-line shrink-0"></i>{{ $course->duration_hours ?? '0' }} {{ __('courses.checkout.order_summary.hours') }}</span>
                                    <span class="flex items-center gap-1"><i class="ri-play-circle-line shrink-0"></i>{{ $course->total_lessons }} {{ __('courses.checkout.order_summary.lessons') }}</span>
                                    @if($course->completion_certificate)
                                    <span class="flex items-center gap-1"><i class="ri-award-line shrink-0"></i>{{ __('courses.checkout.order_summary.certificate') }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Method -->
                    <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
                        <h2 class="text-xl font-bold text-gray-900 mb-4">{{ __('courses.checkout.payment_method.title') }}</h2>

                        <form id="payment-form" class="space-y-4">
                            @csrf
                            <input type="hidden" name="payment_gateway" id="checkout_payment_gateway">

                            <!-- Payment Method Selector with Saved Cards -->
                            @auth
                                <livewire:payment.payment-method-selector />
                            @else
                                <!-- Guest Payment Methods -->
                                <div class="space-y-3">
                                    <label class="flex items-center p-4 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50">
                                        <input type="radio" name="payment_method" value="credit_card" checked
                                               class="text-primary focus:ring-primary">
                                        <div class="ms-3 flex-1">
                                            <div class="flex items-center gap-2">
                                                <i class="ri-bank-card-line text-xl text-gray-600 shrink-0"></i>
                                                <span class="font-medium text-gray-900">{{ __('courses.checkout.payment_method.credit_card') }}</span>
                                            </div>
                                            <p class="text-sm text-gray-600 mt-1">{{ __('courses.checkout.payment_method.credit_card_desc') }}</p>
                                        </div>
                                    </label>

                                    <label class="flex items-center p-4 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50">
                                        <input type="radio" name="payment_method" value="wallet"
                                               class="text-primary focus:ring-primary">
                                        <div class="ms-3 flex-1">
                                            <div class="flex items-center gap-2">
                                                <i class="ri-wallet-3-line text-xl text-gray-600 shrink-0"></i>
                                                <span class="font-medium text-gray-900">المحفظة الإلكترونية</span>
                                            </div>
                                            <p class="text-sm text-gray-600 mt-1">فودافون كاش، أورنج كاش، اتصالات كاش</p>
                                        </div>
                                    </label>
                                </div>
                            @endauth

                            <!-- Paymob Payment Frame Container (for new cards) -->
                            <div id="paymob-card-frame" class="hidden p-4 bg-gray-50 rounded-lg">
                                <div id="paymob-iframe-container" class="min-h-[300px]">
                                    <!-- Paymob iframe will be loaded here -->
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Terms & Conditions -->
                    <div class="bg-white rounded-xl shadow-sm p-6">
                        <h2 class="text-xl font-bold text-gray-900 mb-4">{{ __('courses.checkout.terms.title') }}</h2>
                        <div class="space-y-3">
                            <label class="flex items-start gap-2">
                                <input type="checkbox" required
                                       class="mt-1 rounded border-gray-300 text-primary focus:ring-primary shrink-0">
                                <span class="text-sm text-gray-700">
                                    {{ __('courses.checkout.terms.agree') }} <a href="#" class="text-primary hover:underline">{{ __('courses.checkout.terms.terms_of_service') }}</a> {{ __('courses.checkout.terms.and') }}
                                    <a href="#" class="text-primary hover:underline">{{ __('courses.checkout.terms.privacy_policy') }}</a>
                                </span>
                            </label>
                            <label class="flex items-start gap-2">
                                <input type="checkbox"
                                       class="mt-1 rounded border-gray-300 text-primary focus:ring-primary shrink-0">
                                <span class="text-sm text-gray-700">
                                    {{ __('courses.checkout.terms.newsletter') }}
                                </span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Order Summary -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-xl shadow-sm p-6 sticky top-8">
                        <h2 class="text-xl font-bold text-gray-900 mb-4">{{ __('courses.checkout.invoice.title') }}</h2>

                        <div class="space-y-3 mb-6">
                            <div class="flex justify-between">
                                <span class="text-gray-600">{{ __('courses.checkout.invoice.course_price') }}</span>
                                <span class="font-medium">{{ number_format($course->price) }} {{ getCurrencySymbol() }}</span>
                            </div>

                            <div class="flex justify-between">
                                <span class="text-gray-600">{{ __('courses.checkout.invoice.vat') }}</span>
                                <span class="font-medium">{{ number_format($course->price * 0.15) }} {{ getCurrencySymbol() }}</span>
                            </div>

                            <div class="border-t border-gray-200 pt-3">
                                <div class="flex justify-between text-lg font-bold">
                                    <span>{{ __('courses.checkout.invoice.total') }}</span>
                                    <span class="text-primary">
                                        {{ number_format($course->price * 1.15) }} {{ getCurrencySymbol() }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Payment Button -->
                        <button onclick="processPayment()"
                                class="w-full bg-green-600 text-white py-3 px-4 rounded-lg font-medium hover:bg-green-700 transition-colors mb-4">
                            <span class="flex items-center justify-center gap-2">
                                <i class="ri-secure-payment-line shrink-0"></i>
                                <span>{{ __('courses.checkout.payment.complete_button') }}</span>
                            </span>
                        </button>

                        <!-- Security Features -->
                        <div class="text-center">
                            <div class="flex items-center justify-center gap-2 text-sm text-gray-600 mb-2">
                                <i class="ri-shield-check-line text-green-500 shrink-0"></i>
                                <span>{{ __('courses.checkout.payment.secure') }}</span>
                            </div>
                            <div class="flex items-center justify-center gap-4 text-xs text-gray-500">
                                <span>{{ __('courses.checkout.payment.ssl_protected') }}</span>
                                <span>•</span>
                                <span>{{ __('courses.checkout.payment.encryption') }}</span>
                            </div>
                        </div>

                        <!-- Money Back Guarantee -->
                        <div class="mt-6 p-4 bg-blue-50 rounded-lg">
                            <div class="text-center">
                                <i class="ri-award-line text-blue-600 text-2xl mb-2"></i>
                                <h3 class="font-semibold text-blue-900 mb-1">{{ __('courses.checkout.guarantee.title') }}</h3>
                                <p class="text-xs text-blue-700">
                                    {{ __('courses.checkout.guarantee.message') }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Payment state
let selectedPaymentData = {
    type: 'new',
    saved_payment_method_id: null,
    save_card: true
};

// Listen for Livewire payment method selection events
document.addEventListener('livewire:initialized', () => {
    Livewire.on('payment-method-selected', (data) => {
        selectedPaymentData = data[0] || data;
        const paymobFrame = document.getElementById('paymob-card-frame');

        // Show/hide Paymob iframe based on selection
        if (selectedPaymentData.type === 'new') {
            paymobFrame?.classList.remove('hidden');
            initPaymobFrame();
        } else {
            paymobFrame?.classList.add('hidden');
        }
    });
});

function initPaymobFrame() {
    // Initialize Paymob payment frame for new card entry
    const container = document.getElementById('paymob-iframe-container');
    if (!container) return;

    // Paymob iframe URL will be fetched from the server
    fetch('{{ route("api.v1.payments.create-intent", ["subdomain" => $academy->subdomain]) }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            amount: {{ $course->price * 115 }},
            currency: '{{ getCurrencyCode() }}',
            payment_type: 'course',
            course_id: {{ $course->id }},
            save_card: selectedPaymentData.save_card
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.iframe_url) {
            const iframe = document.createElement('iframe');
            iframe.src = data.iframe_url;
            iframe.className = 'w-full min-h-[400px] border-0 rounded-lg';
            container.innerHTML = '';
            container.appendChild(iframe);
        }
    })
    .catch(error => {
        console.error('Failed to initialize payment frame:', error);
        container.innerHTML = '<p class="text-red-600 text-center py-4">فشل تحميل نموذج الدفع. يرجى المحاولة مرة أخرى.</p>';
    });
}

let selectedGateway = null;

// Listen for gateway selection
document.addEventListener('livewire:initialized', () => {
    Livewire.on('gatewaySelected', ({ gateway }) => {
        selectedGateway = gateway;
        document.getElementById('checkout_payment_gateway').value = gateway;
        executePayment();
    });
});

function processPayment() {
    // Show gateway selection modal first
    Livewire.dispatch('openGatewaySelection');
}

function executePayment() {
    // Show loading state
    const button = document.querySelector('[onclick="processPayment()"]');
    const originalText = button.innerHTML;
    button.innerHTML = '<span class="flex items-center justify-center gap-2"><i class="ri-loader-4-line animate-spin shrink-0"></i><span>' + @json(__('courses.checkout.payment.processing')) + '</span></span>';
    button.disabled = true;

    // Check payment type
    if (selectedPaymentData.type === 'saved' && selectedPaymentData.saved_payment_method_id) {
        // Process payment with saved card
        processSavedCardPayment(button, originalText);
    } else if (selectedPaymentData.type === 'new') {
        // For new cards, the payment is handled by Paymob iframe callback
        // Show message to complete payment in iframe
        window.toast?.info('يرجى إكمال عملية الدفع في النموذج أعلاه');
        button.innerHTML = originalText;
        button.disabled = false;
    } else if (selectedPaymentData.type === 'wallet') {
        // Process wallet payment
        processWalletPayment(button, originalText);
    }
}

function processSavedCardPayment(button, originalText) {
    fetch('{{ route("api.v1.payments.charge-saved", ["subdomain" => $academy->subdomain]) }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            saved_payment_method_id: selectedPaymentData.saved_payment_method_id,
            amount: {{ $course->price * 115 }},
            currency: '{{ getCurrencyCode() }}',
            payment_type: 'course',
            course_id: {{ $course->id }},
            payment_gateway: selectedGateway
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.toast?.success(@json(__('courses.checkout.success.payment_success')));
            window.location.href = `{{ route('courses.learn', ['subdomain' => $academy->subdomain, 'id' => $course->id]) }}`;
        } else {
            window.toast?.error(data.message || 'فشلت عملية الدفع. يرجى المحاولة مرة أخرى.');
            button.innerHTML = originalText;
            button.disabled = false;
        }
    })
    .catch(error => {
        console.error('Payment failed:', error);
        window.toast?.error('فشلت عملية الدفع. يرجى المحاولة مرة أخرى.');
        button.innerHTML = originalText;
        button.disabled = false;
    });
}

function processWalletPayment(button, originalText) {
    // Create wallet payment intent and redirect to wallet payment page
    fetch('{{ route("api.v1.payments.create-intent", ["subdomain" => $academy->subdomain]) }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            amount: {{ $course->price * 115 }},
            currency: '{{ getCurrencyCode() }}',
            payment_type: 'course',
            payment_method: 'wallet',
            course_id: {{ $course->id }},
            payment_gateway: selectedGateway
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.redirect_url) {
            window.location.href = data.redirect_url;
        } else {
            window.toast?.error('فشل إنشاء عملية الدفع. يرجى المحاولة مرة أخرى.');
            button.innerHTML = originalText;
            button.disabled = false;
        }
    })
    .catch(error => {
        console.error('Wallet payment failed:', error);
        window.toast?.error('فشلت عملية الدفع. يرجى المحاولة مرة أخرى.');
        button.innerHTML = originalText;
        button.disabled = false;
    });
}

// Listen for Paymob payment callback (postMessage from iframe)
window.addEventListener('message', function(event) {
    // Verify origin in production
    if (event.data && event.data.type === 'PAYMOB_CALLBACK') {
        if (event.data.success) {
            window.toast?.success(@json(__('courses.checkout.success.payment_success')));
            window.location.href = `{{ route('courses.learn', ['subdomain' => $academy->subdomain, 'id' => $course->id]) }}`;
        } else {
            window.toast?.error(event.data.message || 'فشلت عملية الدفع');
        }
    }
});

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // For guest users, handle payment method switching
    const paymentMethods = document.querySelectorAll('input[name="payment_method"]');
    const paymobFrame = document.getElementById('paymob-card-frame');

    paymentMethods.forEach(method => {
        method.addEventListener('change', function() {
            if (this.value === 'credit_card') {
                selectedPaymentData.type = 'new';
                paymobFrame?.classList.remove('hidden');
                initPaymobFrame();
            } else if (this.value === 'wallet') {
                selectedPaymentData.type = 'wallet';
                paymobFrame?.classList.add('hidden');
            }
        });
    });
});
</script>
@endsection
