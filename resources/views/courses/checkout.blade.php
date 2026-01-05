@extends('components.layouts.student')

@section('title', __('courses.checkout.breadcrumb.payment') . ': ' . $course->title . ' - ' . $academy->name)

@section('content')
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

                            <!-- Payment Methods -->
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
                                    <input type="radio" name="payment_method" value="bank_transfer"
                                           class="text-primary focus:ring-primary">
                                    <div class="ms-3 flex-1">
                                        <div class="flex items-center gap-2">
                                            <i class="ri-bank-line text-xl text-gray-600 shrink-0"></i>
                                            <span class="font-medium text-gray-900">{{ __('courses.checkout.payment_method.bank_transfer') }}</span>
                                        </div>
                                        <p class="text-sm text-gray-600 mt-1">{{ __('courses.checkout.payment_method.bank_transfer_desc') }}</p>
                                    </div>
                                </label>

                                <label class="flex items-center p-4 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50">
                                    <input type="radio" name="payment_method" value="apple_pay"
                                           class="text-primary focus:ring-primary">
                                    <div class="ms-3 flex-1">
                                        <div class="flex items-center gap-2">
                                            <i class="ri-smartphone-line text-xl text-gray-600 shrink-0"></i>
                                            <span class="font-medium text-gray-900">{{ __('courses.checkout.payment_method.apple_pay') }}</span>
                                        </div>
                                        <p class="text-sm text-gray-600 mt-1">{{ __('courses.checkout.payment_method.apple_pay_desc') }}</p>
                                    </div>
                                </label>
                            </div>

                            <!-- Credit Card Details (shown when credit card is selected) -->
                            <div id="credit-card-details" class="space-y-4 p-4 bg-gray-50 rounded-lg">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('courses.checkout.card_details.card_number') }}</label>
                                    <input type="text"
                                           placeholder="{{ __('courses.checkout.card_details.card_number_placeholder') }}"
                                           dir="ltr"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary text-start">
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('courses.checkout.card_details.expiry_date') }}</label>
                                        <input type="text"
                                               placeholder="{{ __('courses.checkout.card_details.expiry_placeholder') }}"
                                               dir="ltr"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary text-center">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('courses.checkout.card_details.cvv') }}</label>
                                        <input type="text"
                                               placeholder="{{ __('courses.checkout.card_details.cvv_placeholder') }}"
                                               dir="ltr"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary text-center">
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('courses.checkout.card_details.cardholder_name') }}</label>
                                    <input type="text"
                                           placeholder="{{ __('courses.checkout.card_details.cardholder_placeholder') }}"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
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
                                <span class="font-medium">{{ number_format($course->price) }} {{ __('courses.checkout.invoice.currency') }}</span>
                            </div>

                            <div class="flex justify-between">
                                <span class="text-gray-600">{{ __('courses.checkout.invoice.vat') }}</span>
                                <span class="font-medium">{{ number_format($course->price * 0.15) }} {{ __('courses.checkout.invoice.currency') }}</span>
                            </div>

                            <div class="border-t border-gray-200 pt-3">
                                <div class="flex justify-between text-lg font-bold">
                                    <span>{{ __('courses.checkout.invoice.total') }}</span>
                                    <span class="text-primary">
                                        {{ number_format($course->price * 1.15) }} {{ __('courses.checkout.invoice.currency') }}
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
function processPayment() {
    // Show loading state
    const button = event.target.closest('button');
    const originalText = button.innerHTML;
    button.innerHTML = '<span class="flex items-center justify-center gap-2"><i class="ri-loader-4-line animate-spin shrink-0"></i><span>' + @json(__('courses.checkout.payment.processing')) + '</span></span>';
    button.disabled = true;

    // Simulate payment processing
    setTimeout(() => {
        // Show success message
        window.toast?.success(@json(__('courses.checkout.success.payment_success')));

        // Redirect to course learning page
        window.location.href = `{{ route('courses.learn', ['subdomain' => $academy->subdomain, 'id' => $course->id]) }}`;
    }, 2000);
}

// Show/hide payment method details
document.addEventListener('DOMContentLoaded', function() {
    const paymentMethods = document.querySelectorAll('input[name="payment_method"]');
    const creditCardDetails = document.getElementById('credit-card-details');

    paymentMethods.forEach(method => {
        method.addEventListener('change', function() {
            if (this.value === 'credit_card') {
                creditCardDetails.style.display = 'block';
            } else {
                creditCardDetails.style.display = 'none';
            }
        });
    });
});
</script>
@endsection
