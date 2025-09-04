@extends('components.platform-layout')

@section('title', 'خدمات الأعمال - منصة إتقان')

@section('content')
    <!-- Hero Section -->
    <div class="hero-gradient text-white py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h1 class="text-4xl md:text-6xl font-bold mb-6">خدمات الأعمال الاحترافية</h1>
            <p class="text-xl md:text-2xl mb-8 text-green-100">نقدم حلولاً متكاملة لتنمية أعمالك وتطويرها</p>
        </div>
    </div>

    <!-- Services Overview -->
    <div class="py-24 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">خدماتنا</h2>
                <p class="text-xl text-gray-600 max-w-2xl mx-auto">
                    نقدم مجموعة شاملة من الخدمات الاحترافية لمساعدة شركتك على النمو والتطور
                </p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8" id="services-grid">
                @foreach($categories as $category)
                <div class="bg-gray-50 rounded-xl p-8 hover:shadow-lg transition-all duration-300 transform hover:-translate-y-2">
                    <div class="w-16 h-16 rounded-lg flex items-center justify-center mb-6" style="background-color: {{ $category->color }}20;">
                        @if($category->icon)
                            <i class="{{ $category->icon }} text-3xl" style="color: {{ $category->color }};"></i>
                        @else
                            <svg class="w-8 h-8" style="color: {{ $category->color }};" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        @endif
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-4">{{ $category->name }}</h3>
                    <p class="text-gray-600 mb-6">{{ $category->description }}</p>
                    <div class="text-sm text-gray-500">
                        <span class="inline-block bg-gray-200 rounded-full px-3 py-1 text-xs font-semibold text-gray-700">
                            {{ $category->portfolioItems_count ?? 0 }} مشروع
                        </span>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Request Form Section -->
    <div class="py-24 bg-gray-50">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">اطلب خدمتك الآن</h2>
                <p class="text-xl text-gray-600">
                    أخبرنا عن مشروعك وسنقوم بالتواصل معك في أقرب وقت ممكن
                </p>
            </div>
            
            <div class="bg-white rounded-2xl shadow-xl p-8">
                <form id="service-request-form" class="space-y-6">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="client_name" class="block text-sm font-medium text-gray-700 mb-2">الاسم الكامل *</label>
                            <input type="text" id="client_name" name="client_name" required 
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                        </div>
                        <div>
                            <label for="client_phone" class="block text-sm font-medium text-gray-700 mb-2">رقم الهاتف *</label>
                            <input type="tel" id="client_phone" name="client_phone" required 
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                        </div>
                    </div>
                    
                    <div>
                        <label for="client_email" class="block text-sm font-medium text-gray-700 mb-2">البريد الإلكتروني *</label>
                        <input type="email" id="client_email" name="client_email" required 
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                    </div>
                    
                    <div>
                        <label for="service_category_id" class="block text-sm font-medium text-gray-700 mb-2">نوع الخدمة *</label>
                        <select id="service_category_id" name="service_category_id" required 
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                            <option value="">اختر نوع الخدمة</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="project_budget" class="block text-sm font-medium text-gray-700 mb-2">الميزانية المتوقعة</label>
                            <select id="project_budget" name="project_budget" 
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                                <option value="">اختر الميزانية</option>
                                <option value="500-1000">500 - 1000 ر.س</option>
                                <option value="1000-3000">1000 - 3000 ر.س</option>
                                <option value="3000-5000">3000 - 5000 ر.س</option>
                                <option value="5000-10000">5000 - 10000 ر.س</option>
                                <option value="10000+">أكثر من 10000 ر.س</option>
                            </select>
                        </div>
                        <div>
                            <label for="project_deadline" class="block text-sm font-medium text-gray-700 mb-2">الموعد المطلوب</label>
                            <select id="project_deadline" name="project_deadline" 
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                                <option value="">اختر الموعد</option>
                                <option value="urgent">عاجل (أسبوع)</option>
                                <option value="normal">عادي (أسبوعين)</option>
                                <option value="flexible">مرن (شهر أو أكثر)</option>
                            </select>
                        </div>
                    </div>
                    
                    <div>
                        <label for="project_description" class="block text-sm font-medium text-gray-700 mb-2">وصف المشروع *</label>
                        <textarea id="project_description" name="project_description" rows="6" required 
                                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                  placeholder="اكتب تفاصيل مشروعك والمتطلبات المحددة..."></textarea>
                    </div>
                    
                    <div class="text-center">
                        <button type="submit" 
                                class="bg-green-600 text-white px-8 py-4 rounded-xl font-semibold hover:bg-green-700 transition-colors transform hover:scale-105">
                            إرسال الطلب
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <div id="success-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-2xl p-8 max-w-md mx-4 text-center">
            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            <h3 class="text-xl font-semibold text-gray-900 mb-2">تم إرسال طلبك بنجاح!</h3>
            <p class="text-gray-600 mb-6">سنقوم بالتواصل معك في أقرب وقت ممكن</p>
            <button onclick="closeSuccessModal()" 
                    class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 transition-colors">
                إغلاق
            </button>
        </div>
    </div>

    <script>
        document.getElementById('service-request-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('{{ route("platform.business-services.request") }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSuccessModal();
                    this.reset();
                } else {
                    alert('حدث خطأ أثناء إرسال الطلب. يرجى المحاولة مرة أخرى.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('حدث خطأ أثناء إرسال الطلب. يرجى المحاولة مرة أخرى.');
            });
        });

        function showSuccessModal() {
            document.getElementById('success-modal').classList.remove('hidden');
            document.getElementById('success-modal').classList.add('flex');
        }

        function closeSuccessModal() {
            document.getElementById('success-modal').classList.add('hidden');
            document.getElementById('success-modal').classList.remove('flex');
        }
    </script>
@endsection
