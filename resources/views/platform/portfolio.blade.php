@extends('components.platform-layout')

@section('title', 'البورتفوليو - منصة إتقان')

@section('content')
    <!-- Hero Section -->
    <div class="hero-gradient text-white py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h1 class="text-4xl md:text-6xl font-bold mb-6">أعمالنا</h1>
            <p class="text-xl md:text-2xl mb-8 text-green-100">مجموعة من أفضل المشاريع التي قمنا بتنفيذها</p>
        </div>
    </div>

    <!-- Category Filter -->
    <div class="py-16 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold text-gray-900 mb-4">تصفح أعمالنا</h2>
                <p class="text-xl text-gray-600">اختر التصنيف لعرض الأعمال المتعلقة به</p>
            </div>
            
            <div class="flex flex-wrap justify-center gap-4 mb-12">
                <button class="category-filter-btn active px-6 py-3 rounded-full bg-green-600 text-white font-medium hover:bg-green-700 transition-colors" data-category="all">
                    جميع الأعمال
                </button>
                @foreach($categories as $category)
                <button class="category-filter-btn px-6 py-3 rounded-full bg-gray-200 text-gray-700 font-medium hover:bg-green-600 hover:text-white transition-colors" data-category="{{ $category->id }}">
                    {{ $category->name }}
                </button>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Portfolio Grid -->
    <div class="py-16 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8" id="portfolio-grid">
                @foreach($portfolioItems as $item)
                <div class="portfolio-item bg-white rounded-xl overflow-hidden shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-2" data-category="{{ $item->service_category_id }}">
                    <div class="relative">
                        <img src="{{ $item->image_url }}" alt="{{ $item->project_name }}" class="w-full h-48 object-cover">
                        <div class="absolute top-4 right-4">
                            <span class="inline-block px-3 py-1 rounded-full text-xs font-semibold text-white" style="background-color: {{ $item->serviceCategory->color }}">
                                {{ $item->serviceCategory->name }}
                            </span>
                        </div>
                    </div>
                    <div class="p-6">
                        <h3 class="text-xl font-semibold text-gray-900 mb-3">{{ $item->project_name }}</h3>
                        <p class="text-gray-600 mb-4">{{ Str::limit($item->project_description, 100) }}</p>
                        
                        @if($item->project_features && count($item->project_features) > 0)
                        <div class="mb-4">
                            <h4 class="text-sm font-medium text-gray-700 mb-2">المميزات:</h4>
                            <div class="flex flex-wrap gap-2">
                                @foreach(array_slice($item->project_features, 0, 3) as $feature)
                                <span class="inline-block px-2 py-1 bg-gray-100 text-xs text-gray-600 rounded">
                                    {{ $feature['feature'] ?? $feature }}
                                </span>
                                @endforeach
                                @if(count($item->project_features) > 3)
                                <span class="inline-block px-2 py-1 bg-gray-100 text-xs text-gray-600 rounded">
                                    +{{ count($item->project_features) - 3 }}
                                </span>
                                @endif
                            </div>
                        </div>
                        @endif
                        
                        <button onclick="showProjectDetails({{ $item->id }})" 
                                class="w-full bg-green-600 text-white py-2 px-4 rounded-lg hover:bg-green-700 transition-colors">
                            عرض التفاصيل
                        </button>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Project Details Modal -->
    <div id="project-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-2xl max-w-4xl mx-4 max-h-[90vh] overflow-y-auto">
            <div class="p-8">
                <div class="flex justify-between items-start mb-6">
                    <h3 class="text-2xl font-bold text-gray-900" id="modal-project-name">اسم المشروع</h3>
                    <button onclick="closeProjectModal()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <div>
                        <img id="modal-project-image" src="" alt="" class="w-full h-64 object-cover rounded-lg">
                    </div>
                    <div>
                        <div class="mb-4">
                            <span class="inline-block px-3 py-1 rounded-full text-sm font-semibold text-white" id="modal-project-category">
                                التصنيف
                            </span>
                        </div>
                        <p class="text-gray-600 mb-6" id="modal-project-description">وصف المشروع</p>
                        
                        <div id="modal-project-features">
                            <h4 class="font-semibold text-gray-900 mb-2">المميزات:</h4>
                            <ul class="space-y-2">
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
                // Remove active class from all buttons
                document.querySelectorAll('.category-filter-btn').forEach(b => b.classList.remove('active', 'bg-green-600', 'text-white'));
                document.querySelectorAll('.category-filter-btn').forEach(b => b.classList.add('bg-gray-200', 'text-gray-700'));
                
                // Add active class to clicked button
                this.classList.remove('bg-gray-200', 'text-gray-700');
                this.classList.add('active', 'bg-green-600', 'text-white');
                
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
            // This would typically fetch project details from an API
            // For now, we'll show a placeholder modal
            document.getElementById('modal-project-name').textContent = 'تفاصيل المشروع';
            document.getElementById('modal-project-description').textContent = 'سيتم إضافة تفاصيل المشروع هنا قريباً...';
            document.getElementById('modal-project-category').textContent = 'التصنيف';
            
            document.getElementById('project-modal').classList.remove('hidden');
            document.getElementById('project-modal').classList.add('flex');
        }

        function closeProjectModal() {
            document.getElementById('project-modal').classList.add('hidden');
            document.getElementById('project-modal').classList.remove('flex');
        }

        // Close modal when clicking outside
        document.getElementById('project-modal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeProjectModal();
            }
        });
    </script>
@endsection
