@extends('components.platform-layout')

@section('title', 'البورتفوليو - منصة إتقان')

@section('content')
    <!-- Hero Section -->
    <div class="relative flex items-center justify-center overflow-hidden bg-gradient-to-br from-slate-800 via-cyan-600 to-teal-700 py-20 pt-40" style="min-height: 100vh;">
        <!-- Background Pattern Layer -->
        <div class="absolute inset-0 opacity-5">
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
                    <i class="ri-folder-open-line text-lg"></i>
                    معرض الأعمال
                </span>
            </div>
            
            <!-- Main Heading -->
            <h1 class="text-4xl md:text-6xl font-bold mb-6 leading-relaxed text-white" data-aos="fade-up" data-aos-delay="200">
                أعمالنا المتميزة
            </h1>
            
            <!-- Subtitle -->
            <p class="text-xl md:text-2xl mb-8 text-cyan-100 max-w-3xl mx-auto" data-aos="fade-up" data-aos-delay="400">
                مجموعة من أفضل المشاريع التي قمنا بتنفيذها بعناية فائقة وإتقان عالي
            </p>
            
            <!-- Action Buttons -->
            <div class="flex flex-col sm:flex-row gap-4 justify-center mb-16" data-aos="fade-up" data-aos-delay="600">
                <a href="#portfolio" class="bg-green-600 text-white px-8 py-4 rounded-xl font-semibold hover:bg-green-700 transition-all transform hover:scale-105">
                    <i class="ri-eye-line ml-2"></i>
                    استكشف الأعمال
                </a>
                <a href="{{ route('platform.business-services') }}" class="bg-sky-600 text-white px-8 py-4 rounded-xl font-semibold hover:bg-sky-700 transition-all transform hover:scale-105">
                    <i class="ri-briefcase-line ml-2"></i>
                    اطلب مشروعك
                </a>
            </div>
            
            <!-- Feature Items -->
            <div class="flex flex-wrap justify-center gap-6 text-cyan-100" data-aos="fade-up" data-aos-delay="800">
                <!-- Feature 1 -->
                <div class="bg-white/10 backdrop-blur-md px-6 py-6 rounded-2xl border border-white/30 max-w-sm shadow-2xl" style="box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.08), 0 0 0 1px rgba(255, 255, 255, 0.1);">
                    <div class="flex items-center justify-center mb-4">
                        <i class="ri-palette-line text-3xl text-cyan-300"></i>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-3">تصميم إبداعي</h3>
                    <p class="text-sm text-cyan-100 leading-relaxed">تصاميم مبتكرة وجذابة تحقق أهدافك التجارية</p>
                </div>

                <!-- Feature 2 -->
                <div class="bg-white/10 backdrop-blur-md px-6 py-6 rounded-2xl border border-white/30 max-w-sm shadow-2xl" style="animation-delay: 0.5s; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.08), 0 0 0 1px rgba(255, 255, 255, 0.1);">
                    <div class="flex items-center justify-center mb-4">
                        <i class="ri-rocket-line text-3xl text-turquoise-300"></i>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-3">أداء عالي</h3>
                    <p class="text-sm text-cyan-100 leading-relaxed">حلول سريعة ومحسنة للأداء الأمثل</p>
                </div>

                <!-- Feature 3 -->
                <div class="bg-white/10 backdrop-blur-md px-6 py-6 rounded-2xl border border-white/30 max-w-sm shadow-2xl" style="animation-delay: 1s; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.08), 0 0 0 1px rgba(255, 255, 255, 0.1);">
                    <div class="flex items-center justify-center mb-4">
                        <i class="ri-shield-check-line text-3xl text-teal-300"></i>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-3">جودة مضمونة</h3>
                    <p class="text-sm text-cyan-100 leading-relaxed">معايير جودة عالية واختبارات شاملة</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Category Filter -->
    <section class="py-16 bg-white relative overflow-hidden">
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
            <div class="text-center mb-12" data-aos="fade-up">
                <h2 class="text-4xl md:text-5xl font-bold text-gray-900 mb-6 leading-relaxed">
                    <span class="flex items-center justify-center gap-4">
                        <i class="ri-filter-line text-5xl text-green-600"></i>
                        <span class="bg-gradient-to-r from-green-600 to-teal-600 bg-clip-text text-transparent">
                            تصفح أعمالنا
                        </span>
                    </span>
                </h2>
                <p class="text-xl text-gray-600 max-w-2xl mx-auto">
                    اختر التصنيف لعرض الأعمال المتعلقة به واستكشف تنوع مشاريعنا
                </p>
            </div>
            
            <div class="flex flex-wrap justify-center gap-4 mb-12" data-aos="fade-up" data-aos-delay="200">
                <button class="category-filter-btn active px-6 py-3 rounded-full bg-gradient-to-r from-green-600 to-emerald-600 text-white font-medium hover:from-green-700 hover:to-emerald-700 transition-all transform hover:scale-105 shadow-lg" data-category="all">
                    <i class="ri-grid-line ml-2"></i>
                    جميع الأعمال
                </button>
                @foreach($categories as $category)
                <button class="category-filter-btn px-6 py-3 rounded-full bg-gray-200 text-gray-700 font-medium hover:bg-gradient-to-r hover:from-green-600 hover:to-emerald-600 hover:text-white transition-all transform hover:scale-105" data-category="{{ $category->id }}">
                    {{ $category->name }}
                </button>
                @endforeach
            </div>
        </div>
        
        <!-- Section Bottom Border -->
        <div class="absolute bottom-0 left-0 right-0 h-1 bg-gradient-to-r from-green-100 via-teal-100 to-green-100"></div>
    </section>

    <!-- Portfolio Grid -->
    <section id="portfolio" class="py-24 bg-gradient-to-br from-gray-50 via-green-50 to-teal-50 relative overflow-hidden">
        <!-- Background Pattern -->
        <div class="absolute inset-0 opacity-5">
            <div class="absolute inset-0" style="background-image: url('/storage/app-design-assets/bg-pattern1.png'); background-size: 50px 50px; background-repeat: repeat;"></div>
        </div>
        
        <!-- Subtle Background Elements -->
        <div class="absolute top-10 right-10 w-32 h-32 bg-green-100 rounded-full blur-2xl opacity-20"></div>
        <div class="absolute bottom-10 left-10 w-40 h-40 bg-teal-100 rounded-full blur-2xl opacity-15"></div>
        
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8" id="portfolio-grid">
                @foreach($portfolioItems as $index => $item)
                <div class="portfolio-item bg-white rounded-2xl overflow-hidden shadow-xl hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2 border border-gray-200 flex flex-col" data-category="{{ $item->service_category_id }}" data-project-id="{{ $item->id }}" data-full-description="{{ $item->project_description }}" data-aos="fade-up" data-aos-delay="{{ ($index + 1) * 100 }}">
                    <div class="relative">
                        <img src="{{ $item->image_url }}" alt="{{ $item->project_name }}" class="w-full h-48 object-cover">
                        <div class="absolute top-4 right-4">
                            <span class="inline-block px-3 py-1 rounded-full text-xs font-semibold text-white shadow-lg" style="background-color: {{ $item->serviceCategory->color }}">
                                {{ $item->serviceCategory->name }}
                            </span>
                        </div>
                        <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent opacity-0 hover:opacity-100 transition-opacity duration-300"></div>
                    </div>
                    <div class="p-6 flex flex-col flex-grow">
                        <div class="flex-grow">
                            <h3 class="text-xl font-semibold text-gray-900 mb-3">{{ $item->project_name }}</h3>
                            <p class="text-gray-600 mb-4 leading-relaxed">{{ Str::limit($item->project_description, 100) }}</p>
                            
                            @if($item->project_features && count($item->project_features) > 0)
                            <div class="mb-4">
                                <h4 class="text-sm font-medium text-gray-700 mb-2">المميزات:</h4>
                                <div class="flex flex-wrap gap-2">
                                    @foreach(array_slice($item->project_features, 0, 3) as $feature)
                                    <span class="inline-block px-2 py-1 bg-gray-100 text-xs text-gray-600 rounded-full">
                                        {{ $feature['feature'] ?? $feature }}
                                    </span>
                                    @endforeach
                                    @if(count($item->project_features) > 3)
                                    <span class="inline-block px-2 py-1 bg-gray-100 text-xs text-gray-600 rounded-full">
                                        +{{ count($item->project_features) - 3 }}
                                    </span>
                                    @endif
                                </div>
                            </div>
                            @endif
                        </div>
                        
                        <button onclick="showProjectDetails({{ $item->id }})" 
                                class="w-full bg-gradient-to-r from-green-600 to-emerald-600 text-white py-3 px-4 rounded-xl font-semibold hover:from-green-700 hover:to-emerald-700 transition-all transform hover:scale-105 shadow-lg mt-auto">
                            <i class="ri-eye-line ml-2"></i>
                            عرض التفاصيل
                        </button>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- Project Details Modal -->
    <div id="project-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 p-3 md:p-4">
        <div class="bg-white rounded-xl md:rounded-2xl max-w-4xl w-full max-h-[90vh] overflow-y-auto shadow-2xl transform transition-all duration-300 scale-95 opacity-0" id="modal-content">
            <div class="p-4 md:p-6">
                <!-- Modal Header -->
                <div class="flex justify-between items-start gap-3 mb-4 md:mb-6">
                    <div class="min-w-0">
                        <h3 class="text-lg sm:text-xl md:text-2xl font-bold text-gray-900 mb-1.5 md:mb-2" id="modal-project-name">اسم المشروع</h3>
                        <span class="inline-block px-2.5 md:px-3 py-0.5 md:py-1 rounded-full text-xs md:text-sm font-semibold text-white shadow-lg" id="modal-project-category">
                            التصنيف
                        </span>
                    </div>
                    <button onclick="closeProjectModal()" class="min-h-[44px] min-w-[44px] text-gray-400 hover:text-gray-600 transition-colors p-2 hover:bg-gray-100 rounded-full flex-shrink-0 flex items-center justify-center">
                        <svg class="w-5 h-5 md:w-6 md:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <!-- Modal Content -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 md:gap-8">
                    <!-- Left Column - Project Image -->
                    <div class="relative">
                        <img id="modal-project-image" src="" alt="صورة المشروع" class="w-full h-auto object-contain rounded-lg md:rounded-xl border border-gray-200 shadow-lg">
                    </div>

                    <!-- Right Column - Text Content -->
                    <div class="space-y-4 md:space-y-6">
                        <!-- Description -->
                        <div>
                            <h4 class="text-base md:text-lg font-semibold text-gray-900 mb-2 md:mb-3">وصف المشروع</h4>
                            <p class="text-sm md:text-base text-gray-600 leading-relaxed" id="modal-project-description">وصف المشروع</p>
                        </div>

                        <!-- Features -->
                        <div id="modal-project-features">
                            <h4 class="text-base md:text-lg font-semibold text-gray-900 mb-2 md:mb-3">المميزات</h4>
                            <ul class="space-y-1.5 md:space-y-2">
                                <!-- Features will be populated by JavaScript -->
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Category filtering
        document.querySelectorAll('.category-filter-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                // Remove active class and gradient from all buttons
                document.querySelectorAll('.category-filter-btn').forEach(b => {
                    b.classList.remove('active', 'bg-green-600', 'text-white', 'bg-gradient-to-r', 'from-green-600', 'to-emerald-600', 'shadow-lg');
                    b.classList.add('bg-gray-200', 'text-gray-700');
                });
                
                // Add active class and gradient to clicked button
                this.classList.remove('bg-gray-200', 'text-gray-700');
                this.classList.add('active', 'bg-gradient-to-r', 'from-green-600', 'to-emerald-600', 'text-white', 'shadow-lg');
                
                const category = this.dataset.category;
                filterPortfolio(category);
            });
        });

        function filterPortfolio(category) {
            const items = document.querySelectorAll('.portfolio-item');
            
            items.forEach(item => {
                if (category === 'all' || item.dataset.category === category) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        }

        function showProjectDetails(projectId) {
            // Find the portfolio item by ID
            const portfolioItem = document.querySelector(`[data-project-id="${projectId}"]`);
            if (!portfolioItem) {
                console.error('Portfolio item not found');
                return;
            }
            
            // Extract data from the portfolio item
            const projectName = portfolioItem.querySelector('h3').textContent;
            const projectDescription = portfolioItem.dataset.fullDescription; // Get full description from data attribute
            const projectImage = portfolioItem.querySelector('img').src;
            const projectImageAlt = portfolioItem.querySelector('img').alt;
            const categorySpan = portfolioItem.querySelector('.absolute.top-4.right-4 span');
            const categoryName = categorySpan.textContent;
            const categoryColor = categorySpan.style.backgroundColor;
            
            // Get features from the portfolio item
            const featuresContainer = portfolioItem.querySelector('.flex.flex-wrap.gap-2');
            const features = [];
            if (featuresContainer) {
                const featureSpans = featuresContainer.querySelectorAll('span');
                featureSpans.forEach(span => {
                    if (!span.textContent.includes('+')) {
                        features.push(span.textContent);
                    }
                });
            }
            
            // Populate modal with actual data
            document.getElementById('modal-project-name').textContent = projectName;
            document.getElementById('modal-project-description').textContent = projectDescription;
            document.getElementById('modal-project-category').textContent = categoryName;
            document.getElementById('modal-project-category').style.backgroundColor = categoryColor;
            document.getElementById('modal-project-image').src = projectImage;
            document.getElementById('modal-project-image').alt = projectImageAlt;
            
            // Populate features with simple design
            const featuresList = document.querySelector('#modal-project-features ul');
            featuresList.innerHTML = '';
            features.forEach((feature, index) => {
                const featureLi = document.createElement('li');
                featureLi.className = 'flex items-center gap-3 text-gray-700 portfolio-feature-item';
                featureLi.style.animationDelay = `${index * 0.1}s`;
                featureLi.innerHTML = `
                    <div class="w-2 h-2 bg-green-500 rounded-full flex-shrink-0"></div>
                    <span>${feature}</span>
                `;
                featuresList.appendChild(featureLi);
            });
            
            // Show modal with animation
            const modal = document.getElementById('project-modal');
            const modalContent = document.getElementById('modal-content');
            
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            
            // Trigger animation
            setTimeout(() => {
                modalContent.classList.remove('scale-95', 'opacity-0');
                modalContent.classList.add('scale-100', 'opacity-100');
            }, 10);
        }

        function closeProjectModal() {
            const modal = document.getElementById('project-modal');
            const modalContent = document.getElementById('modal-content');
            
            // Animate out
            modalContent.classList.remove('scale-100', 'opacity-100');
            modalContent.classList.add('scale-95', 'opacity-0');
            
            // Hide modal after animation
            setTimeout(() => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }, 300);
        }

        // Close modal when clicking outside
        document.getElementById('project-modal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeProjectModal();
            }
        });
    </script>

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
                هل تريد مشروعاً مشابهاً؟
            </h2>
            <p class="text-xl mb-12 text-green-100 max-w-2xl mx-auto" data-aos="fade-up" data-aos-delay="200">
                دعنا نعمل معاً لتحقيق رؤيتك وإنشاء مشروع متميز يناسب احتياجاتك
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center" data-aos="fade-up" data-aos-delay="400">
                <a href="{{ route('platform.business-services') }}" 
                   class="group relative inline-flex items-center gap-3 px-8 py-4 bg-gradient-to-r from-green-600 to-emerald-600 text-white rounded-2xl font-bold text-lg transition-all duration-300 transform hover:scale-105 hover:shadow-2xl overflow-hidden">
                    <div class="absolute inset-0 bg-gradient-to-r from-green-500 to-emerald-500 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                    <div class="absolute -inset-1 bg-gradient-to-r from-green-500 to-emerald-500 rounded-2xl blur opacity-30 group-hover:opacity-60 transition-opacity duration-300"></div>
                    <span class="relative z-10 flex items-center gap-3">
                        <i class="ri-briefcase-line text-xl"></i>
                        اطلب مشروعك الآن
                    </span>
                </a>
                <a href="{{ route('platform.contact') }}" 
                   class="bg-transparent border-2 border-white text-white px-8 py-4 rounded-2xl font-semibold hover:bg-white hover:text-green-600 transition-all transform hover:scale-105">
                    <i class="ri-phone-line ml-2"></i>
                    اتصل بنا
                </a>
            </div>
        </div>
    </section>
@endsection
