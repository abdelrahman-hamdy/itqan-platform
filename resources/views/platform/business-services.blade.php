@extends('components.platform-layout')

@section('title', 'خدمات الأعمال - منصة إتقان')

@section('content')
    <!-- Hero Section -->
    <div class="relative flex items-center justify-center overflow-hidden bg-gradient-to-br from-green-800 via-emerald-600 to-teal-700 py-20 pt-40" style="min-height: 100vh;">
        <!-- Background Pattern Layer -->
        <div class="absolute inset-0 opacity-50">
            <div class="absolute inset-0" style="background-image: url('/storage/app-design-assets/bg-pattern1.png'); background-size: 100px 100px; background-repeat: repeat;"></div>
        </div>
        
        <!-- Enhanced Background Elements -->
        <div class="absolute top-0 right-0 w-[40rem] h-[40rem] bg-gradient-to-br from-cyan-400/25 via-cyan-500/35 to-teal-500/30 rounded-full blur-3xl opacity-60 animate-pulse" style="animation-duration: 6s;"></div>
        <div class="absolute bottom-0 left-0 w-[45rem] h-[45rem] bg-gradient-to-tr from-turquoise-400/25 via-teal-500/30 to-cyan-500/25 rounded-full blur-3xl opacity-55 animate-bounce" style="animation-duration: 8s;"></div>
        <div class="absolute top-1/2 right-0 transform translate-x-1/2 -translate-y-1/2 w-[35rem] h-[35rem] bg-gradient-to-l from-teal-400/25 via-cyan-500/30 to-turquoise-500/25 rounded-full blur-3xl opacity-50 animate-ping" style="animation-duration: 10s;"></div>
        <div class="absolute top-0 left-0 w-[38rem] h-[38rem] bg-gradient-to-br from-cyan-400/25 via-teal-500/30 to-turquoise-500/25 rounded-full blur-3xl opacity-45 animate-pulse" style="animation-duration: 7s; animation-delay: 2s;"></div>
        <div class="absolute bottom-0 right-0 w-[42rem] h-[42rem] bg-gradient-to-tl from-turquoise-400/25 via-cyan-500/30 to-teal-500/25 rounded-full blur-3xl opacity-50 animate-bounce" style="animation-duration: 9s; animation-delay: 3s;"></div>
        
        <!-- Additional subtle elements -->
        <div class="absolute top-1/4 left-1/4 w-[20rem] h-[20rem] bg-gradient-to-r from-cyan-400/20 to-teal-500/25 rounded-full blur-2xl opacity-30 animate-pulse" style="animation-duration: 12s; animation-delay: 1s;"></div>
        <div class="absolute bottom-1/4 right-1/4 w-[25rem] h-[25rem] bg-gradient-to-r from-turquoise-400/20 to-cyan-500/25 rounded-full blur-2xl opacity-25 animate-bounce" style="animation-duration: 11s; animation-delay: 4s;"></div>
        
        <!-- Content -->
        <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <!-- Animated Label -->
            <div class="mb-8" data-aos="fade-up">
                <span class="inline-flex items-center gap-3 px-6 py-3 bg-white/10 text-cyan-100 rounded-full text-sm font-semibold border border-white/30 backdrop-blur-sm">
                    <i class="ri-briefcase-line text-lg"></i>
                    خدمات الأعمال المتطورة
                </span>
            </div>
            
            <!-- Main Heading -->
            <h1 class="text-4xl md:text-6xl font-bold mb-6 leading-relaxed text-white" data-aos="fade-up" data-aos-delay="200">
                خدمات الأعمال الاحترافية
            </h1>
            
            <!-- Subtitle -->
            <p class="text-xl md:text-2xl mb-8 text-cyan-100 max-w-3xl mx-auto" data-aos="fade-up" data-aos-delay="400">
                نقدم حلولاً متكاملة ومبتكرة لتنمية أعمالك وتطويرها باستخدام أحدث التقنيات
            </p>
            
            <!-- Action Buttons -->
            <div class="flex flex-col sm:flex-row gap-4 justify-center mb-16" data-aos="fade-up" data-aos-delay="600">
                <a href="#services" class="bg-teal-600 text-white px-8 py-4 rounded-xl font-semibold hover:bg-teal-700 transition-all transform hover:scale-105">
                    <i class="ri-eye-line ml-2"></i>
                    استكشف خدماتنا
                </a>
                <a href="#request" class="bg-sky-600 text-white px-8 py-4 rounded-xl font-semibold hover:bg-sky-700 transition-all transform hover:scale-105">
                    <i class="ri-customer-service-line ml-2"></i>
                    اطلب خدمتك الآن
                </a>
            </div>
            
            <!-- Feature Items -->
            <div class="flex flex-wrap justify-center gap-6 text-cyan-100" data-aos="fade-up" data-aos-delay="800">
                <!-- Feature 1 -->
                <div class="bg-white/10 backdrop-blur-md px-6 py-6 rounded-2xl border border-white/30 max-w-sm shadow-2xl" style="box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.08), 0 0 0 1px rgba(255, 255, 255, 0.1);">
                    <div class="flex items-center justify-center mb-4">
                        <i class="ri-shield-check-line text-4xl text-emerald-400"></i>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-3">جودة عالية</h3>
                    <p class="text-sm text-cyan-100 leading-relaxed">نضمن أعلى معايير الجودة في جميع مشاريعنا مع اختبارات شاملة</p>
                </div>

                <!-- Feature 2 -->
                <div class="bg-white/10 backdrop-blur-md px-6 py-6 rounded-2xl border border-white/30 max-w-sm shadow-2xl" style="animation-delay: 0.5s; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.08), 0 0 0 1px rgba(255, 255, 255, 0.1);">
                    <div class="flex items-center justify-center mb-4">
                        <i class="ri-rocket-line text-4xl text-emerald-400"></i>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-3">سرعة التنفيذ</h3>
                    <p class="text-sm text-cyan-100 leading-relaxed">تسليم المشاريع في الوقت المحدد مع الحفاظ على الجودة العالية</p>
                </div>

                <!-- Feature 3 -->
                <div class="bg-white/10 backdrop-blur-md px-6 py-6 rounded-2xl border border-white/30 max-w-sm shadow-2xl" style="animation-delay: 1s; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.08), 0 0 0 1px rgba(255, 255, 255, 0.1);">
                    <div class="flex items-center justify-center mb-4">
                        <i class="ri-customer-service-line text-4xl text-emerald-400"></i>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-3">دعم مستمر</h3>
                    <p class="text-sm text-cyan-100 leading-relaxed">دعم فني متواصل وخدمة عملاء متميزة على مدار الساعة</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Services Overview -->
    <section id="services" class="py-24 bg-white relative overflow-hidden">
        <!-- Section Top Border -->
        <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-green-100 via-teal-100 to-green-100"></div>
        
        <!-- Background Pattern -->
        <div class="absolute inset-0 opacity-5">
            <div class="absolute inset-0" style="background-image: url('/storage/app-design-assets/bg-pattern1.png'); background-size: 50px 50px; background-repeat: repeat;"></div>
        </div>
        
        <!-- Subtle Background Elements -->
        <div class="absolute top-10 right-10 w-32 h-32 bg-green-50 rounded-full blur-2xl opacity-30"></div>
        <div class="absolute bottom-10 left-10 w-40 h-40 bg-teal-50 rounded-full blur-2xl opacity-25"></div>
        
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="text-center mb-16" data-aos="fade-up">
                <h2 class="text-4xl md:text-5xl font-bold text-gray-900 mb-6 leading-relaxed">
                    <span class="flex items-center justify-center gap-4">
                        <i class="ri-briefcase-line text-5xl bg-gradient-to-r from-green-600 to-teal-600 bg-clip-text text-transparent"></i>
                        <span class="bg-gradient-to-r from-green-600 to-teal-600 bg-clip-text text-transparent">
                            خدماتنا
                        </span>
                    </span>
                </h2>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                    نقدم مجموعة شاملة من الخدمات الاحترافية المصممة خصيصاً لمساعدة شركتك على النمو والتطور
                </p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8" id="services-grid">
                @foreach($categories as $index => $category)
                    <div data-aos="fade-up" data-aos-delay="{{ ($index + 1) * 100 }}">
                        <x-service-card :service="$category" />
                    </div>
                @endforeach
            </div>
        </div>
        
        <!-- Section Bottom Border -->
        <div class="absolute bottom-0 left-0 right-0 h-1 bg-gradient-to-r from-green-100 via-teal-100 to-green-100"></div>
    </section>

    <!-- Request Form Section -->
    <section id="request" class="py-24 bg-gradient-to-br from-gray-50 via-green-50 to-teal-50 relative overflow-hidden">
        <!-- Background Pattern -->
        <div class="absolute inset-0 opacity-5">
            <div class="absolute inset-0" style="background-image: url('/storage/app-design-assets/bg-pattern1.png'); background-size: 50px 50px; background-repeat: repeat;"></div>
        </div>
        
        <!-- Subtle Background Elements -->
        <div class="absolute top-10 right-10 w-32 h-32 bg-green-100 rounded-full blur-2xl opacity-20"></div>
        <div class="absolute bottom-10 left-10 w-40 h-40 bg-teal-100 rounded-full blur-2xl opacity-15"></div>
        
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="text-center mb-16" data-aos="fade-up">
                <h2 class="text-4xl md:text-5xl font-bold text-gray-900 mb-6 leading-relaxed">
                    <span class="flex items-center justify-center gap-4">
                        <i class="ri-customer-service-line text-5xl text-green-600"></i>
                        <span class="bg-gradient-to-r from-green-600 to-teal-600 bg-clip-text text-transparent">
                            اطلب خدمتك الآن
                        </span>
                    </span>
                </h2>
                <p class="text-xl text-gray-600 max-w-2xl mx-auto">
                    أخبرنا عن مشروعك وسنقوم بالتواصل معك في أقرب وقت ممكن لتقديم أفضل الحلول
                </p>
            </div>
            
            <div class="bg-white rounded-2xl shadow-2xl p-8 border border-gray-200" data-aos="fade-up" data-aos-delay="200">
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
                                class="group relative inline-flex items-center gap-3 px-8 py-4 bg-gradient-to-r from-green-600 to-emerald-600 text-white rounded-2xl font-bold text-lg transition-all duration-300 transform hover:scale-105 hover:shadow-2xl overflow-hidden">
                            <div class="absolute inset-0 bg-gradient-to-r from-green-500 to-emerald-500 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                            <div class="absolute -inset-1 bg-gradient-to-r from-green-500 to-emerald-500 rounded-2xl blur opacity-30 group-hover:opacity-60 transition-opacity duration-300"></div>
                            <span class="relative z-10 flex items-center gap-3">
                                <i class="ri-send-plane-line text-xl"></i>
                                إرسال الطلب
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-24 bg-gradient-to-br from-slate-900 via-blue-900 to-indigo-900 relative overflow-hidden">
        <!-- Background Pattern -->
        <div class="absolute inset-0 opacity-5">
            <div class="absolute inset-0" style="background-image: url('/storage/app-design-assets/bg-pattern1.png'); background-size: 100px 100px; background-repeat: repeat;"></div>
        </div>
        
        <!-- Enhanced Background Elements -->
        <div class="absolute top-0 right-0 w-[40rem] h-[40rem] bg-gradient-to-br from-blue-500/20 via-blue-600/30 to-indigo-600/25 rounded-full blur-3xl opacity-60 animate-pulse" style="animation-duration: 6s;"></div>
        <div class="absolute bottom-0 left-0 w-[45rem] h-[45rem] bg-gradient-to-tr from-emerald-500/20 via-green-600/25 to-teal-600/20 rounded-full blur-3xl opacity-55 animate-bounce" style="animation-duration: 8s;"></div>
        <div class="absolute top-1/2 right-0 transform translate-x-1/2 -translate-y-1/2 w-[35rem] h-[35rem] bg-gradient-to-l from-cyan-500/20 via-teal-600/25 to-blue-600/20 rounded-full blur-3xl opacity-50 animate-ping" style="animation-duration: 10s;"></div>
        <div class="absolute top-0 left-0 w-[38rem] h-[38rem] bg-gradient-to-br from-purple-500/20 via-violet-600/25 to-indigo-600/20 rounded-full blur-3xl opacity-45 animate-pulse" style="animation-duration: 7s; animation-delay: 2s;"></div>
        <div class="absolute bottom-0 right-0 w-[42rem] h-[42rem] bg-gradient-to-tl from-indigo-500/20 via-blue-600/25 to-purple-600/20 rounded-full blur-3xl opacity-50 animate-bounce" style="animation-duration: 9s; animation-delay: 3s;"></div>
        
        <!-- Additional subtle elements -->
        <div class="absolute top-1/4 left-1/4 w-[20rem] h-[20rem] bg-gradient-to-r from-rose-500/15 to-pink-600/20 rounded-full blur-2xl opacity-30 animate-pulse" style="animation-duration: 12s; animation-delay: 1s;"></div>
        <div class="absolute bottom-1/4 right-1/4 w-[25rem] h-[25rem] bg-gradient-to-r from-amber-500/15 to-yellow-600/20 rounded-full blur-2xl opacity-25 animate-bounce" style="animation-duration: 11s; animation-delay: 4s;"></div>
        
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10">
            <h2 class="text-4xl md:text-5xl font-bold text-white mb-6 leading-relaxed" data-aos="fade-up">
                هل أنت مستعد لبدء مشروعك؟
            </h2>
            <p class="text-xl mb-12 text-green-100 max-w-2xl mx-auto" data-aos="fade-up" data-aos-delay="200">
                دعنا نعمل معاً لتحقيق رؤيتك ونقل أعمالك إلى المستوى التالي
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center" data-aos="fade-up" data-aos-delay="400">
                <a href="{{ route('platform.contact') }}" 
                   class="group relative inline-flex items-center gap-3 px-8 py-4 bg-gradient-to-r from-green-600 to-emerald-600 text-white rounded-2xl font-bold text-lg transition-all duration-300 transform hover:scale-105 hover:shadow-2xl overflow-hidden">
                    <div class="absolute inset-0 bg-gradient-to-r from-green-500 to-emerald-500 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                    <div class="absolute -inset-1 bg-gradient-to-r from-green-500 to-emerald-500 rounded-2xl blur opacity-30 group-hover:opacity-60 transition-opacity duration-300"></div>
                    <span class="relative z-10 flex items-center gap-3">
                        <i class="ri-phone-line text-xl"></i>
                        اتصل بنا الآن
                    </span>
                </a>
                <a href="{{ route('platform.portfolio') }}" 
                   class="bg-transparent border-2 border-white text-white px-8 py-4 rounded-2xl font-semibold hover:bg-white hover:text-green-600 transition-all transform hover:scale-105">
                    <i class="ri-eye-line ml-2"></i>
                    شاهد أعمالنا
                </a>
            </div>
        </div>
    </section>

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
            
            // Clear previous error messages
            clearErrorMessages();
            
            // Get form elements
            const form = this;
            const submitButton = form.querySelector('button[type="submit"]');
            const originalButtonText = submitButton.innerHTML;
            
            // Show loading state
            submitButton.disabled = true;
            submitButton.innerHTML = `
                <div class="flex items-center gap-3">
                    <div class="w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                    <span>جاري الإرسال...</span>
                </div>
            `;
            
            const formData = new FormData(form);
            
            // Get CSRF token
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || 
                             document.querySelector('input[name="_token"]')?.value;
            
            if (!csrfToken) {
                showErrorMessage('خطأ في الأمان. يرجى إعادة تحميل الصفحة والمحاولة مرة أخرى.');
                return;
            }
            
            // Ensure CSRF token is in form data as well
            formData.append('_token', csrfToken);
            
            fetch('{{ route("platform.business-services.request") }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(data => {
                        throw new Error(JSON.stringify(data));
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    showSuccessModal();
                    form.reset();
                } else {
                    showErrorMessage('حدث خطأ أثناء إرسال الطلب. يرجى المحاولة مرة أخرى.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                
                try {
                    const errorData = JSON.parse(error.message);
                    if (errorData.errors) {
                        showValidationErrors(errorData.errors);
                    } else {
                        showErrorMessage(errorData.message || 'حدث خطأ أثناء إرسال الطلب. يرجى المحاولة مرة أخرى.');
                    }
                } catch (parseError) {
                    showErrorMessage('حدث خطأ أثناء إرسال الطلب. يرجى المحاولة مرة أخرى.');
                }
            })
            .finally(() => {
                // Reset button state
                submitButton.disabled = false;
                submitButton.innerHTML = originalButtonText;
            });
        });

        function clearErrorMessages() {
            // Remove existing error messages
            document.querySelectorAll('.error-message').forEach(el => el.remove());
            
            // Remove error styling from inputs
            document.querySelectorAll('.border-red-500').forEach(el => {
                el.classList.remove('border-red-500');
                el.classList.add('border-gray-300');
            });
        }

        function showValidationErrors(errors) {
            Object.keys(errors).forEach(fieldName => {
                const field = document.querySelector(`[name="${fieldName}"]`);
                if (field) {
                    // Add error styling
                    field.classList.remove('border-gray-300');
                    field.classList.add('border-red-500');
                    
                    // Add error message
                    const errorDiv = document.createElement('div');
                    errorDiv.className = 'error-message text-red-500 text-sm mt-1';
                    errorDiv.textContent = errors[fieldName][0];
                    
                    field.parentNode.appendChild(errorDiv);
                }
            });
        }

        function showErrorMessage(message) {
            // Create error message element
            const errorDiv = document.createElement('div');
            errorDiv.className = 'error-message bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4';
            errorDiv.innerHTML = `
                <div class="flex items-center gap-2">
                    <i class="ri-error-warning-line"></i>
                    <span>${message}</span>
                </div>
            `;
            
            // Insert before the form
            const form = document.getElementById('service-request-form');
            form.parentNode.insertBefore(errorDiv, form);
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                if (errorDiv.parentNode) {
                    errorDiv.parentNode.removeChild(errorDiv);
                }
            }, 5000);
        }

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
