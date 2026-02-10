<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>{{ __('payments.academic_payment.page_title') }} - {{ $academy->name ?? __('common.academy_default') }}</title>

  @vite(['resources/css/app.css', 'resources/js/app.js'])
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.min.css">

  <style>
    :root {
      --color-primary-500: {{ $academy->brand_color?->getHexValue(500) ?? '#4169E1' }};
      --color-secondary-500: {{ $academy->secondary_color?->getHexValue(500) ?? '#6495ED' }};
    }
    .card-hover { transition: all 0.3s ease; }
    .card-hover:hover { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(65, 105, 225, 0.1); }
  </style>
</head>

<body class="bg-gray-50 font-sans">

  <!-- Header -->
  <header class="bg-white shadow-sm">
    <div class="container mx-auto px-4 py-4">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
          @if($academy->logo)
            <img src="{{ asset('storage/' . $academy->logo) }}" alt="{{ $academy->name }}" class="h-10 w-10 rounded-lg">
          @endif
          <div>
            <h1 class="text-xl font-bold text-gray-900">{{ $academy->name ?? __('common.academy_default') }}</h1>
            <p class="text-sm text-gray-600">{{ __('payments.academic_payment.header_subtitle') }}</p>
          </div>
        </div>
        <div class="flex items-center gap-2 text-green-600">
          <i class="ri-shield-check-line text-lg"></i>
          <span class="text-sm font-medium">{{ __('payments.academic_payment.secure_payment') }}</span>
        </div>
      </div>
    </div>
  </header>

  @livewire('payment.payment-gateway-modal', ['academyId' => $academy->id])

  <!-- Main Content -->
  <section class="py-8">
    <div class="container mx-auto px-4 max-w-6xl">

      @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg mb-6">
          <div class="flex items-center gap-2">
            <i class="ri-check-circle-line text-lg"></i>
            {{ session('success') }}
          </div>
        </div>
      @endif

      @if(session('error'))
        <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg mb-6">
          <div class="flex items-center gap-2">
            <i class="ri-error-warning-line text-lg"></i>
            {{ session('error') }}
          </div>
        </div>
      @endif

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

        <!-- Payment Form -->
        <div class="lg:col-span-2">
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="mb-6">
              <h2 class="text-2xl font-bold text-gray-900 mb-2 flex items-center gap-2">
                <i class="ri-secure-payment-line text-primary"></i>
                {{ __('payments.academic_payment.complete_payment') }}
              </h2>
              <p class="text-gray-600">{{ __('payments.academic_payment.choose_method') }}</p>
            </div>

            <form id="payment-form" class="space-y-6">
              @csrf
              <input type="hidden" name="payment_gateway" id="academic_payment_gateway">

              <!-- Payment Methods -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-3">{{ __('payments.academic_payment.payment_method_label') }}</label>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <label class="relative">
                    <input type="radio" name="payment_method" value="credit_card" class="sr-only peer" checked>
                    <div class="p-4 border-2 border-gray-200 rounded-lg cursor-pointer peer-checked:border-primary peer-checked:bg-primary/5 card-hover">
                      <div class="flex items-center gap-3">
                        <i class="ri-bank-card-line text-2xl text-primary shrink-0"></i>
                        <div>
                          <div class="font-medium text-gray-900">{{ __('payments.quran_payment.credit_card_title') }}</div>
                          <div class="text-sm text-gray-600">{{ __('payments.quran_payment.credit_card_desc') }}</div>
                        </div>
                      </div>
                    </div>
                  </label>

                  <label class="relative">
                    <input type="radio" name="payment_method" value="mada" class="sr-only peer">
                    <div class="p-4 border-2 border-gray-200 rounded-lg cursor-pointer peer-checked:border-primary peer-checked:bg-primary/5 card-hover">
                      <div class="flex items-center gap-3">
                        <i class="ri-bank-card-2-line text-2xl text-primary shrink-0"></i>
                        <div>
                          <div class="font-medium text-gray-900">{{ __('payments.quran_payment.mada_title') }}</div>
                          <div class="text-sm text-gray-600">{{ __('payments.quran_payment.mada_desc') }}</div>
                        </div>
                      </div>
                    </div>
                  </label>

                  <label class="relative">
                    <input type="radio" name="payment_method" value="stc_pay" class="sr-only peer">
                    <div class="p-4 border-2 border-gray-200 rounded-lg cursor-pointer peer-checked:border-primary peer-checked:bg-primary/5 card-hover">
                      <div class="flex items-center gap-3">
                        <i class="ri-smartphone-line text-2xl text-primary shrink-0"></i>
                        <div>
                          <div class="font-medium text-gray-900">{{ __('payments.quran_payment.stc_pay_title') }}</div>
                          <div class="text-sm text-gray-600">{{ __('payments.quran_payment.stc_pay_desc') }}</div>
                        </div>
                      </div>
                    </div>
                  </label>

                  <label class="relative">
                    <input type="radio" name="payment_method" value="bank_transfer" class="sr-only peer">
                    <div class="p-4 border-2 border-gray-200 rounded-lg cursor-pointer peer-checked:border-primary peer-checked:bg-primary/5 card-hover">
                      <div class="flex items-center gap-3">
                        <i class="ri-bank-line text-2xl text-primary shrink-0"></i>
                        <div>
                          <div class="font-medium text-gray-900">{{ __('payments.quran_payment.bank_transfer_title') }}</div>
                          <div class="text-sm text-gray-600">{{ __('payments.quran_payment.bank_transfer_desc') }}</div>
                        </div>
                      </div>
                    </div>
                  </label>
                </div>
              </div>

              <!-- Card Details -->
              <div id="card-details" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label for="card_number" class="block text-sm font-medium text-gray-700 mb-1">{{ __('payments.quran_payment.card_number') }}</label>
                    <input type="text" id="card_number" name="card_number" placeholder="1234 5678 9012 3456" dir="ltr"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary text-start">
                  </div>
                  <div>
                    <label for="cardholder_name" class="block text-sm font-medium text-gray-700 mb-1">{{ __('payments.quran_payment.cardholder_name') }}</label>
                    <input type="text" id="cardholder_name" name="cardholder_name" placeholder="{{ __('payments.quran_payment.cardholder_placeholder') }}"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                  </div>
                </div>
                <div class="grid grid-cols-3 gap-4">
                  <div>
                    <label for="expiry_month" class="block text-sm font-medium text-gray-700 mb-1">{{ __('payments.quran_payment.expiry_month') }}</label>
                    <select id="expiry_month" name="expiry_month" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                      <option value="">{{ __('payments.quran_payment.month_placeholder') }}</option>
                      @for($i = 1; $i <= 12; $i++)
                        <option value="{{ $i }}">{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}</option>
                      @endfor
                    </select>
                  </div>
                  <div>
                    <label for="expiry_year" class="block text-sm font-medium text-gray-700 mb-1">{{ __('payments.quran_payment.expiry_year') }}</label>
                    <select id="expiry_year" name="expiry_year" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                      <option value="">{{ __('payments.quran_payment.year_placeholder') }}</option>
                      @for($i = date('Y'); $i <= date('Y') + 10; $i++)
                        <option value="{{ $i }}">{{ $i }}</option>
                      @endfor
                    </select>
                  </div>
                  <div>
                    <label for="cvv" class="block text-sm font-medium text-gray-700 mb-1">{{ __('payments.quran_payment.cvv') }}</label>
                    <input type="text" id="cvv" name="cvv" placeholder="123" maxlength="3" dir="ltr"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary text-center">
                  </div>
                </div>
              </div>

              <!-- Security Notice -->
              <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div class="flex items-start gap-3">
                  <i class="ri-shield-check-line text-blue-600 text-lg mt-0.5 shrink-0"></i>
                  <div class="text-sm text-blue-800">
                    <h4 class="font-semibold mb-1">{{ __('payments.quran_payment.security_title') }}</h4>
                    <p>{{ __('payments.quran_payment.security_message') }}</p>
                  </div>
                </div>
              </div>

              <!-- Submit Button -->
              <button type="submit" id="pay-button"
                      class="w-full bg-primary text-white py-3 px-6 rounded-lg font-medium hover:bg-secondary transition-colors flex items-center justify-center gap-2">
                <i class="ri-secure-payment-line"></i>
                <span>{{ __('payments.academic_payment.pay_button', ['amount' => number_format($totalAmount, 2), 'currency' => getCurrencySymbol(null, $subscription->academy)]) }}</span>
              </button>
            </form>
          </div>
        </div>

        <!-- Order Summary -->
        <div class="space-y-6">

          <!-- Subscription Details -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
              <i class="ri-file-text-line text-primary"></i>
              {{ __('payments.academic_payment.subscription_details') }}
            </h3>

            <div class="space-y-4">
              <!-- Teacher Info -->
              @if($subscription->teacher?->user)
              <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                <div class="w-12 h-12 rounded-full bg-gradient-to-br from-primary to-secondary flex items-center justify-center text-white text-sm font-bold shrink-0 overflow-hidden">
                  @if($subscription->teacher->avatar)
                    <img src="{{ asset('storage/' . $subscription->teacher->avatar) }}" alt="{{ $subscription->teacher->user->name }}" class="w-full h-full object-cover">
                  @else
                    {{ mb_substr($subscription->teacher->user->name ?? '', 0, 2) }}
                  @endif
                </div>
                <div class="min-w-0">
                  <div class="font-medium text-gray-900 truncate">{{ $subscription->teacher->user->name }}</div>
                  <div class="text-sm text-gray-600">{{ __('payments.academic_payment.academic_teacher') }}</div>
                </div>
              </div>
              @endif

              <!-- Subject & Grade -->
              <div class="space-y-2 text-sm">
                <div class="flex justify-between gap-2">
                  <span class="text-gray-600 shrink-0">{{ __('payments.academic_payment.subject_label') }}</span>
                  <span class="font-medium text-end">{{ $subscription->subject_name ?? $subscription->subject?->name ?? '-' }}</span>
                </div>
                <div class="flex justify-between gap-2">
                  <span class="text-gray-600 shrink-0">{{ __('payments.academic_payment.grade_label') }}</span>
                  <span class="font-medium">{{ $subscription->grade_level_name ?? $subscription->gradeLevel?->getDisplayName() ?? '-' }}</span>
                </div>
                <div class="flex justify-between gap-2">
                  <span class="text-gray-600 shrink-0">{{ __('payments.academic_payment.sessions_count') }}</span>
                  <span class="font-medium">{{ $subscription->sessions_per_month }} {{ __('payments.academic_payment.sessions_monthly') }}</span>
                </div>
                <div class="flex justify-between gap-2">
                  <span class="text-gray-600 shrink-0">{{ __('payments.academic_payment.session_duration') }}</span>
                  <span class="font-medium">{{ $subscription->session_duration_minutes ?? 60 }} {{ __('payments.academic_payment.minutes') }}</span>
                </div>
                <div class="flex justify-between gap-2">
                  <span class="text-gray-600 shrink-0">{{ __('payments.academic_payment.billing_cycle_label') }}</span>
                  <span class="font-medium">
                    @switch($subscription->billing_cycle->value ?? $subscription->billing_cycle)
                      @case('monthly') {{ __('payments.academic_payment.billing_monthly') }} @break
                      @case('quarterly') {{ __('payments.academic_payment.billing_quarterly') }} @break
                      @case('yearly') {{ __('payments.academic_payment.billing_yearly') }} @break
                      @default {{ $subscription->billing_cycle->value ?? $subscription->billing_cycle }}
                    @endswitch
                  </span>
                </div>
              </div>
            </div>
          </div>

          <!-- Payment Summary -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
              <i class="ri-calculator-line text-primary"></i>
              {{ __('payments.academic_payment.payment_summary') }}
            </h3>

            <div class="space-y-3 text-sm">
              <div class="flex justify-between gap-2">
                <span>{{ __('payments.academic_payment.subscription_price') }}</span>
                <span dir="ltr" class="font-medium">{{ number_format($originalPrice, 2) }} {{ getCurrencySymbol(null, $subscription->academy) }}</span>
              </div>

              @if($discountAmount > 0)
                <div class="flex justify-between gap-2 text-green-600">
                  <span>{{ __('payments.academic_payment.discount_label') }}</span>
                  <span dir="ltr">-{{ number_format($discountAmount, 2) }} {{ getCurrencySymbol(null, $subscription->academy) }}</span>
                </div>
              @endif

              <div class="flex justify-between gap-2">
                <span>{{ __('payments.academic_payment.vat_label') }}</span>
                <span dir="ltr" class="font-medium">{{ number_format($taxAmount, 2) }} {{ getCurrencySymbol(null, $subscription->academy) }}</span>
              </div>

              <div class="border-t border-gray-200 pt-3 flex justify-between gap-2 text-base font-bold">
                <span>{{ __('payments.academic_payment.total_amount') }}</span>
                <span class="text-primary" dir="ltr">{{ number_format($totalAmount, 2) }} {{ getCurrencySymbol(null, $subscription->academy) }}</span>
              </div>

              @if(getCurrencyCode(null, $subscription->academy) !== 'EGP')
              <div class="mt-3 p-3 bg-amber-50 border border-amber-200 rounded-lg text-sm text-amber-800">
                <div class="flex items-start gap-2">
                  <i class="ri-information-line text-amber-600 mt-0.5 shrink-0"></i>
                  <div>
                    <p class="font-medium mb-1">{{ __('payments.quran_payment.currency_notice_title') }}</p>
                    <p>{{ __('payments.quran_payment.currency_notice_message', ['currency' => getCurrencyCode(null, $subscription->academy)]) }}</p>
                  </div>
                </div>
              </div>
              @endif
            </div>
          </div>

          <!-- Contact Support -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
              <i class="ri-customer-service-line text-primary"></i>
              {{ __('payments.quran_payment.need_help') }}
            </h3>
            <p class="text-sm text-gray-600 mb-4">{{ __('payments.quran_payment.help_message') }}</p>
            @if($academy->contact_phone)
              <a href="tel:{{ $academy->contact_phone }}"
                 class="w-full bg-gray-100 text-gray-700 py-2 px-4 rounded-lg text-sm font-medium hover:bg-gray-200 transition-colors flex items-center justify-center gap-2">
                <i class="ri-phone-line"></i>
                <span dir="ltr">{{ $academy->contact_phone }}</span>
              </a>
            @endif
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Payment Processing Modal -->
  <div id="processing-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-xl p-8 max-w-md w-full text-center">
      <div class="animate-spin rounded-full h-16 w-16 border-b-2 border-primary mx-auto mb-4"></div>
      <h3 class="text-lg font-bold text-gray-900 mb-2">{{ __('payments.quran_payment.processing_payment') }}</h3>
      <p class="text-gray-600">{{ __('payments.quran_payment.processing_message') }}</p>
    </div>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const form = document.getElementById('payment-form');
      const payButton = document.getElementById('pay-button');
      const processingModal = document.getElementById('processing-modal');
      const cardDetails = document.getElementById('card-details');
      const paymentMethods = document.querySelectorAll('input[name="payment_method"]');

      paymentMethods.forEach(method => {
        method.addEventListener('change', function() {
          cardDetails.style.display = (this.value === 'credit_card' || this.value === 'mada') ? 'block' : 'none';
        });
      });

      let gatewayReady = false;

      if (typeof Livewire !== 'undefined') {
          Livewire.on('gatewaySelected', ({ gateway }) => {
              document.getElementById('academic_payment_gateway').value = gateway;
              gatewayReady = true;
              submitPayment();
          });
      }

      function submitPayment() {
        processingModal.classList.remove('hidden');
        payButton.disabled = true;

        const formData = new FormData(form);

        fetch('{{ route("academic.subscription.payment.submit", ["subdomain" => $academy->subdomain, "subscription" => $subscription->id]) }}', {
          method: 'POST',
          body: formData,
          headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}'
          }
        })
        .then(response => response.json())
        .then(response => {
          if (response.success) {
            const redirectUrl = response.data?.redirect_url || response.redirect_url;
            if (redirectUrl) {
              if (response.message) window.toast?.info(response.message);
              window.location.href = redirectUrl;
            } else {
              processingModal.classList.add('hidden');
              window.toast?.success(response.message || @json(__('payments.academic_payment.success')));
              setTimeout(() => {
                window.location.href = '{{ route("student.subscriptions", ["subdomain" => $academy->subdomain]) }}';
              }, 1500);
            }
          } else {
            processingModal.classList.add('hidden');
            const errorMessage = response.error || response.message || @json(__('payments.academic_payment.failed'));
            window.toast?.error(errorMessage);
            payButton.disabled = false;
          }
        })
        .catch(error => {
          processingModal.classList.add('hidden');
          window.toast?.error(@json(__('payments.quran_payment.connection_error')));
          payButton.disabled = false;
        });
      }

      form.addEventListener('submit', function(e) {
        e.preventDefault();
        Livewire.dispatch('openGatewaySelection');
      });
    });
  </script>

</body>
</html>
