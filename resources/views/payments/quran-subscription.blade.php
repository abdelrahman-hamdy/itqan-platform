<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>دفع اشتراك القرآن الكريم - {{ $academy->name ?? 'أكاديمية إتقان' }}</title>
  <script src="https://cdn.tailwindcss.com/3.4.16"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.min.css">
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            primary: @json(preg_match('/^#[a-fA-F0-9]{6}$/', $academy->primary_color ?? '') ? $academy->primary_color : '#4169E1'),
            secondary: @json(preg_match('/^#[a-fA-F0-9]{6}$/', $academy->secondary_color ?? '') ? $academy->secondary_color : '#6495ED'),
          }
        }
      }
    };
  </script>
  <style>
    .card-hover {
      transition: all 0.3s ease;
    }
    .card-hover:hover {
      transform: translateY(-2px);
      box-shadow: 0 10px 25px rgba(65, 105, 225, 0.1);
    }
  </style>
</head>

<body class="bg-gray-50 font-sans">

  <!-- Header -->
  <header class="bg-white shadow-sm">
    <div class="container mx-auto px-4 py-4">
      <div class="flex items-center justify-between">
        <!-- Logo and Academy Name -->
        <div class="flex items-center space-x-3 space-x-reverse">
          @if($academy->logo)
            <img src="{{ asset('storage/' . $academy->logo) }}" alt="{{ $academy->name }}" class="h-10 w-10 rounded-lg">
          @endif
          <div>
            <h1 class="text-xl font-bold text-gray-900">{{ $academy->name ?? 'أكاديمية إتقان' }}</h1>
            <p class="text-sm text-gray-600">دفع اشتراك القرآن الكريم</p>
          </div>
        </div>

        <!-- Security Badge -->
        <div class="flex items-center space-x-2 space-x-reverse text-green-600">
          <i class="ri-shield-check-line text-lg"></i>
          <span class="text-sm font-medium">دفع آمن</span>
        </div>
      </div>
    </div>
  </header>

  <!-- Main Content -->
  <section class="py-8">
    <div class="container mx-auto px-4 max-w-6xl">
      
      <!-- Success/Error Messages -->
      @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg mb-6">
          <div class="flex items-center">
            <i class="ri-check-circle-line text-lg ml-2"></i>
            {{ session('success') }}
          </div>
        </div>
      @endif

      @if(session('error'))
        <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg mb-6">
          <div class="flex items-center">
            <i class="ri-error-warning-line text-lg ml-2"></i>
            {{ session('error') }}
          </div>
        </div>
      @endif

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Payment Form -->
        <div class="lg:col-span-2">
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="mb-6">
              <h2 class="text-2xl font-bold text-gray-900 mb-2">
                <i class="ri-secure-payment-line text-primary ml-2"></i>
                إكمال عملية الدفع
              </h2>
              <p class="text-gray-600">اختر طريقة الدفع المناسبة لإكمال اشتراكك</p>
            </div>

            <form id="payment-form" class="space-y-6">
              @csrf
              
              <!-- Payment Methods -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-3">طريقة الدفع *</label>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  
                  <!-- Credit Card -->
                  <label class="relative">
                    <input type="radio" name="payment_method" value="credit_card" class="sr-only peer" checked>
                    <div class="p-4 border-2 border-gray-200 rounded-lg cursor-pointer peer-checked:border-primary peer-checked:bg-primary/5 card-hover">
                      <div class="flex items-center">
                        <i class="ri-bank-card-line text-2xl text-primary ml-3"></i>
                        <div>
                          <div class="font-medium text-gray-900">بطاقة ائتمانية</div>
                          <div class="text-sm text-gray-600">Visa, MasterCard</div>
                        </div>
                      </div>
                    </div>
                  </label>

                  <!-- Mada -->
                  <label class="relative">
                    <input type="radio" name="payment_method" value="mada" class="sr-only peer">
                    <div class="p-4 border-2 border-gray-200 rounded-lg cursor-pointer peer-checked:border-primary peer-checked:bg-primary/5 card-hover">
                      <div class="flex items-center">
                        <i class="ri-bank-card-2-line text-2xl text-primary ml-3"></i>
                        <div>
                          <div class="font-medium text-gray-900">مدى</div>
                          <div class="text-sm text-gray-600">بطاقات الدفع السعودية</div>
                        </div>
                      </div>
                    </div>
                  </label>

                  <!-- STC Pay -->
                  <label class="relative">
                    <input type="radio" name="payment_method" value="stc_pay" class="sr-only peer">
                    <div class="p-4 border-2 border-gray-200 rounded-lg cursor-pointer peer-checked:border-primary peer-checked:bg-primary/5 card-hover">
                      <div class="flex items-center">
                        <i class="ri-smartphone-line text-2xl text-primary ml-3"></i>
                        <div>
                          <div class="font-medium text-gray-900">STC Pay</div>
                          <div class="text-sm text-gray-600">الدفع عبر الجوال</div>
                        </div>
                      </div>
                    </div>
                  </label>

                  <!-- Bank Transfer -->
                  <label class="relative">
                    <input type="radio" name="payment_method" value="bank_transfer" class="sr-only peer">
                    <div class="p-4 border-2 border-gray-200 rounded-lg cursor-pointer peer-checked:border-primary peer-checked:bg-primary/5 card-hover">
                      <div class="flex items-center">
                        <i class="ri-bank-line text-2xl text-primary ml-3"></i>
                        <div>
                          <div class="font-medium text-gray-900">تحويل بنكي</div>
                          <div class="text-sm text-gray-600">تحويل مباشر</div>
                        </div>
                      </div>
                    </div>
                  </label>
                </div>
              </div>

              <!-- Card Details (shown when credit card or mada selected) -->
              <div id="card-details" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label for="card_number" class="block text-sm font-medium text-gray-700 mb-1">رقم البطاقة *</label>
                    <input type="text" id="card_number" name="card_number" 
                           placeholder="1234 5678 9012 3456"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                  </div>
                  
                  <div>
                    <label for="cardholder_name" class="block text-sm font-medium text-gray-700 mb-1">اسم حامل البطاقة *</label>
                    <input type="text" id="cardholder_name" name="cardholder_name" 
                           placeholder="كما هو مكتوب على البطاقة"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                  </div>
                </div>

                <div class="grid grid-cols-3 gap-4">
                  <div>
                    <label for="expiry_month" class="block text-sm font-medium text-gray-700 mb-1">الشهر *</label>
                    <select id="expiry_month" name="expiry_month" 
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                      <option value="">الشهر</option>
                      @for($i = 1; $i <= 12; $i++)
                        <option value="{{ $i }}">{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}</option>
                      @endfor
                    </select>
                  </div>
                  
                  <div>
                    <label for="expiry_year" class="block text-sm font-medium text-gray-700 mb-1">السنة *</label>
                    <select id="expiry_year" name="expiry_year" 
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                      <option value="">السنة</option>
                      @for($i = date('Y'); $i <= date('Y') + 10; $i++)
                        <option value="{{ $i }}">{{ $i }}</option>
                      @endfor
                    </select>
                  </div>
                  
                  <div>
                    <label for="cvv" class="block text-sm font-medium text-gray-700 mb-1">CVV *</label>
                    <input type="text" id="cvv" name="cvv" 
                           placeholder="123" maxlength="3"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                  </div>
                </div>
              </div>

              <!-- Security Notice -->
              <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div class="flex items-start">
                  <i class="ri-shield-check-line text-blue-600 text-lg ml-2 mt-0.5"></i>
                  <div class="text-sm text-blue-800">
                    <h4 class="font-semibold mb-1">أمان المعاملات</h4>
                    <p>جميع المعاملات مشفرة ومحمية بأعلى معايير الأمان. لا نحتفظ ببيانات بطاقتك الائتمانية.</p>
                  </div>
                </div>
              </div>

              <!-- Submit Button -->
              <button type="submit" id="pay-button"
                      class="w-full bg-primary text-white py-3 px-6 rounded-lg font-medium hover:bg-secondary transition-colors flex items-center justify-center">
                <i class="ri-secure-payment-line ml-2"></i>
                <span>دفع {{ number_format($totalAmount, 2) }} {{ $subscription->currency }}</span>
              </button>
            </form>
          </div>
        </div>

        <!-- Order Summary -->
        <div class="space-y-6">
          
          <!-- Subscription Details -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">
              <i class="ri-file-text-line text-primary ml-2"></i>
              تفاصيل الاشتراك
            </h3>
            
            <div class="space-y-4">
              <!-- Teacher Info -->
              <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                <div class="w-12 h-12 rounded-full bg-gradient-to-br from-primary to-secondary flex items-center justify-center text-white text-sm font-bold ml-3">
                  @if($subscription->quranTeacher->avatar)
                    <img src="{{ asset('storage/' . $subscription->quranTeacher->avatar) }}" alt="{{ $subscription->quranTeacher->full_name }}" class="w-full h-full rounded-full object-cover">
                  @else
                    {{ substr($subscription->quranTeacher->first_name, 0, 1) }}{{ substr($subscription->quranTeacher->last_name, 0, 1) }}
                  @endif
                </div>
                <div>
                  <div class="font-medium text-gray-900">{{ $subscription->quranTeacher->full_name }}</div>
                  <div class="text-sm text-gray-600">معلم القرآن الكريم</div>
                </div>
              </div>

              <!-- Package Info -->
              <div class="space-y-2">
                <div class="flex justify-between">
                  <span class="text-gray-600">الباقة:</span>
                  <span class="font-medium">{{ $subscription->package->getDisplayName() }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-gray-600">نوع الاشتراك:</span>
                  <span class="font-medium">{{ $subscription->subscription_type === 'private' ? 'جلسات خاصة' : 'جلسات جماعية' }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-gray-600">عدد الجلسات:</span>
                  <span class="font-medium">{{ $subscription->total_sessions }} جلسة</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-gray-600">مدة الاشتراك:</span>
                  <span class="font-medium">
                    @switch($subscription->billing_cycle)
                      @case('monthly') شهر واحد @break
                      @case('quarterly') ثلاثة أشهر @break  
                      @case('yearly') سنة واحدة @break
                      @default {{ $subscription->billing_cycle }}
                    @endswitch
                  </span>
                </div>
              </div>
            </div>
          </div>

          <!-- Payment Summary -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">
              <i class="ri-calculator-line text-primary ml-2"></i>
              ملخص الدفع
            </h3>
            
            <div class="space-y-3">
              <div class="flex justify-between">
                <span>سعر الاشتراك:</span>
                <span>{{ number_format($originalPrice, 2) }} {{ $subscription->currency }}</span>
              </div>

              @if($discountAmount > 0)
                <div class="flex justify-between text-green-600">
                  <span>الخصم:</span>
                  <span>-{{ number_format($discountAmount, 2) }} {{ $subscription->currency }}</span>
                </div>
                <div class="flex justify-between">
                  <span>السعر بعد الخصم:</span>
                  <span>{{ number_format($finalPrice, 2) }} {{ $subscription->currency }}</span>
                </div>
              @endif

              <div class="flex justify-between">
                <span>ضريبة القيمة المضافة (15%):</span>
                <span>{{ number_format($taxAmount, 2) }} {{ $subscription->currency }}</span>
              </div>

              <div class="border-t border-gray-200 pt-3 flex justify-between text-lg font-bold">
                <span>المجموع الكلي:</span>
                <span class="text-primary">{{ number_format($totalAmount, 2) }} {{ $subscription->currency }}</span>
              </div>
            </div>
          </div>

          <!-- Contact Support -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">
              <i class="ri-customer-service-line text-primary ml-2"></i>
              هل تحتاج مساعدة؟
            </h3>
            <p class="text-sm text-gray-600 mb-4">
              إذا واجهت أي مشكلة في عملية الدفع، تواصل معنا
            </p>
            @if($academy->contact_phone)
              <a href="tel:{{ $academy->contact_phone }}" 
                 class="w-full bg-gray-100 text-gray-700 py-2 px-4 rounded-lg text-center text-sm font-medium hover:bg-gray-200 transition-colors block mb-2">
                <i class="ri-phone-line ml-2"></i>
                {{ $academy->contact_phone }}
              </a>
            @endif
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Payment Processing Modal -->
  <div id="processing-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
    <div class="bg-white rounded-xl p-8 max-w-md mx-4 text-center">
      <div class="animate-spin rounded-full h-16 w-16 border-b-2 border-primary mx-auto mb-4"></div>
      <h3 class="text-lg font-bold text-gray-900 mb-2">جارٍ معالجة الدفع...</h3>
      <p class="text-gray-600">يرجى عدم إغلاق هذه الصفحة أو الضغط على زر الرجوع</p>
    </div>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const form = document.getElementById('payment-form');
      const payButton = document.getElementById('pay-button');
      const processingModal = document.getElementById('processing-modal');
      const cardDetails = document.getElementById('card-details');
      const paymentMethods = document.querySelectorAll('input[name="payment_method"]');

      // Show/hide card details based on payment method
      paymentMethods.forEach(method => {
        method.addEventListener('change', function() {
          if (this.value === 'credit_card' || this.value === 'mada') {
            cardDetails.style.display = 'block';
          } else {
            cardDetails.style.display = 'none';
          }
        });
      });

      form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Show processing modal
        processingModal.classList.remove('hidden');
        payButton.disabled = true;

        // Collect form data
        const formData = new FormData(form);
        
        // Submit payment
        fetch('{{ route("quran.subscription.payment.submit", ["subdomain" => $academy->subdomain, "subscription" => $subscription->id]) }}', {
          method: 'POST',
          body: formData,
          headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}'
          }
        })
        .then(response => response.json())
        .then(data => {
          processingModal.classList.add('hidden');
          
          if (data.success) {
            // Show success message and redirect
            window.toast?.info(data.message);
            window.location.href = data.redirect_url;
          } else {
            // Show error message
            window.toast?.error(data.error || 'حدث خطأ أثناء عملية الدفع');
            payButton.disabled = false;
          }
        })
        .catch(error => {
          processingModal.classList.add('hidden');
          window.toast?.error('حدث خطأ في الاتصال. يرجى المحاولة مرة أخرى');
          payButton.disabled = false;
        });
      });
    });
  </script>

</body>
</html>