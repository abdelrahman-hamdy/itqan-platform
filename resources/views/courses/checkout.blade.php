@extends('components.layouts.student')

@section('title', 'الدفع: ' . $course->title . ' - ' . $academy->name)

@section('content')
<div class="min-h-screen bg-gray-50">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <!-- Breadcrumb -->
            <nav class="mb-8">
                <ol class="flex items-center space-x-2 space-x-reverse text-sm text-gray-600">
                    <li><a href="{{ route('courses.index', ['subdomain' => $academy->subdomain]) }}" class="hover:text-primary">الدورات المسجلة</a></li>
                    <li>/</li>
                    <li><a href="{{ route('courses.show', ['subdomain' => $academy->subdomain, 'id' => $course->id]) }}" class="hover:text-primary">{{ $course->title }}</a></li>
                    <li>/</li>
                    <li class="text-gray-900">الدفع</li>
                </ol>
            </nav>

            <!-- Page Header -->
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-gray-900 mb-2">إكمال التسجيل</h1>
                <p class="text-gray-600">أكمل عملية الدفع للوصول الكامل للدورة</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Checkout Form -->
                <div class="lg:col-span-2">
                    <!-- Course Summary -->
                    <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
                        <h2 class="text-xl font-bold text-gray-900 mb-4">ملخص الطلب</h2>
                        <div class="flex items-start gap-4">
                            @if($course->thumbnail_url)
                            <div class="w-20 h-16 bg-gray-200 rounded-lg overflow-hidden flex-shrink-0">
                                <img src="{{ $course->thumbnail_url }}" 
                                     alt="{{ $course->title }}" 
                                     class="w-full h-full object-cover">
                            </div>
                            @endif
                            <div class="flex-1">
                                <h3 class="font-semibold text-gray-900 mb-2">{{ $course->title }}</h3>
                                <div class="flex items-center gap-4 text-sm text-gray-600">
                                    <span><i class="ri-time-line ml-1"></i>{{ $course->duration_hours ?? '0' }} ساعة</span>
                                    <span><i class="ri-play-circle-line ml-1"></i>{{ $course->total_lessons }} درس</span>
                                    @if($course->completion_certificate)
                                    <span><i class="ri-award-line ml-1"></i>شهادة إتمام</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Method -->
                    <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
                        <h2 class="text-xl font-bold text-gray-900 mb-4">طريقة الدفع</h2>
                        
                        <form id="payment-form" class="space-y-4">
                            @csrf
                            
                            <!-- Payment Methods -->
                            <div class="space-y-3">
                                <label class="flex items-center p-4 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50">
                                    <input type="radio" name="payment_method" value="credit_card" checked 
                                           class="text-primary focus:ring-primary">
                                    <div class="mr-3 flex-1">
                                        <div class="flex items-center gap-2">
                                            <i class="ri-bank-card-line text-xl text-gray-600"></i>
                                            <span class="font-medium text-gray-900">بطاقة ائتمان</span>
                                        </div>
                                        <p class="text-sm text-gray-600 mt-1">Visa, Mastercard, American Express</p>
                                    </div>
                                </label>

                                <label class="flex items-center p-4 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50">
                                    <input type="radio" name="payment_method" value="bank_transfer" 
                                           class="text-primary focus:ring-primary">
                                    <div class="mr-3 flex-1">
                                        <div class="flex items-center gap-2">
                                            <i class="ri-bank-line text-xl text-gray-600"></i>
                                            <span class="font-medium text-gray-900">تحويل بنكي</span>
                                        </div>
                                        <p class="text-sm text-gray-600 mt-1">تحويل مباشر من حسابك البنكي</p>
                                    </div>
                                </label>

                                <label class="flex items-center p-4 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50">
                                    <input type="radio" name="payment_method" value="apple_pay" 
                                           class="text-primary focus:ring-primary">
                                    <div class="mr-3 flex-1">
                                        <div class="flex items-center gap-2">
                                            <i class="ri-smartphone-line text-xl text-gray-600"></i>
                                            <span class="font-medium text-gray-900">Apple Pay</span>
                                        </div>
                                        <p class="text-sm text-gray-600 mt-1">دفع سريع وآمن</p>
                                    </div>
                                </label>
                            </div>

                            <!-- Credit Card Details (shown when credit card is selected) -->
                            <div id="credit-card-details" class="space-y-4 p-4 bg-gray-50 rounded-lg">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">رقم البطاقة</label>
                                    <input type="text" placeholder="1234 5678 9012 3456" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">تاريخ الانتهاء</label>
                                        <input type="text" placeholder="MM/YY" 
                                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">CVV</label>
                                        <input type="text" placeholder="123" 
                                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">اسم حامل البطاقة</label>
                                    <input type="text" placeholder="الاسم كما هو مكتوب على البطاقة" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Terms & Conditions -->
                    <div class="bg-white rounded-xl shadow-sm p-6">
                        <h2 class="text-xl font-bold text-gray-900 mb-4">الشروط والأحكام</h2>
                        <div class="space-y-3">
                            <label class="flex items-start">
                                <input type="checkbox" required 
                                       class="mt-1 rounded border-gray-300 text-primary focus:ring-primary">
                                <span class="mr-2 text-sm text-gray-700">
                                    أوافق على <a href="#" class="text-primary hover:underline">شروط الخدمة</a> و 
                                    <a href="#" class="text-primary hover:underline">سياسة الخصوصية</a>
                                </span>
                            </label>
                            <label class="flex items-start">
                                <input type="checkbox" 
                                       class="mt-1 rounded border-gray-300 text-primary focus:ring-primary">
                                <span class="mr-2 text-sm text-gray-700">
                                    أرغب في تلقي العروض والتحديثات عبر البريد الإلكتروني
                                </span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Order Summary -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-xl shadow-sm p-6 sticky top-8">
                        <h2 class="text-xl font-bold text-gray-900 mb-4">ملخص الفاتورة</h2>
                        
                        <div class="space-y-3 mb-6">
                            <div class="flex justify-between">
                                <span class="text-gray-600">سعر الدورة</span>
                                <span class="font-medium">{{ number_format($course->price) }} ريال</span>
                            </div>
                            
                            @if($course->discount_price && $course->discount_price < $course->price)
                            <div class="flex justify-between text-green-600">
                                <span>الخصم</span>
                                <span>-{{ number_format($course->price - $course->discount_price) }} ريال</span>
                            </div>
                            @endif
                            
                            <div class="flex justify-between">
                                <span class="text-gray-600">ضريبة القيمة المضافة (15%)</span>
                                <span class="font-medium">{{ number_format(($course->discount_price ?? $course->price) * 0.15) }} ريال</span>
                            </div>
                            
                            <div class="border-t border-gray-200 pt-3">
                                <div class="flex justify-between text-lg font-bold">
                                    <span>المجموع</span>
                                    <span class="text-primary">
                                        {{ number_format(($course->discount_price ?? $course->price) * 1.15) }} ريال
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Payment Button -->
                        <button onclick="processPayment()" 
                                class="w-full bg-green-600 text-white py-3 px-4 rounded-lg font-medium hover:bg-green-700 transition-colors mb-4">
                            <i class="ri-secure-payment-line ml-2"></i>
                            إتمام الدفع
                        </button>

                        <!-- Security Features -->
                        <div class="text-center">
                            <div class="flex items-center justify-center gap-2 text-sm text-gray-600 mb-2">
                                <i class="ri-shield-check-line text-green-500"></i>
                                <span>دفع آمن ومشفر</span>
                            </div>
                            <div class="flex items-center justify-center gap-4 text-xs text-gray-500">
                                <span>SSL محمي</span>
                                <span>•</span>
                                <span>256-bit تشفير</span>
                            </div>
                        </div>

                        <!-- Money Back Guarantee -->
                        <div class="mt-6 p-4 bg-blue-50 rounded-lg">
                            <div class="text-center">
                                <i class="ri-award-line text-blue-600 text-2xl mb-2"></i>
                                <h3 class="font-semibold text-blue-900 mb-1">ضمان استرداد الأموال</h3>
                                <p class="text-xs text-blue-700">
                                    استرد أموالك كاملة خلال 30 يوم إذا لم تكن راضياً عن الدورة
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
    const button = event.target;
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="ri-loader-4-line animate-spin ml-2"></i>جاري المعالجة...';
    button.disabled = true;
    
    // Simulate payment processing
    setTimeout(() => {
        // Show success message
        alert('تم الدفع بنجاح! مرحباً بك في الدورة');
        
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
