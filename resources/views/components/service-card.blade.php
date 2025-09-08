@props(['service'])

<div class="bg-white rounded-2xl p-6 border border-gray-300 hover:border-gray-400 transition-all duration-300 transform hover:-translate-y-1 group relative">
    <!-- Colored shadow on hover -->
    <div class="absolute inset-0 rounded-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-300" style="box-shadow: 0 20px 25px -5px {{ $service->color }}20, 0 10px 10px -5px {{ $service->color }}10;"></div>
    
    <!-- Content -->
    <div class="relative z-10">
        <!-- Icon positioned at top right -->
        <div class="w-10 h-10 rounded-lg flex items-center justify-center mb-4 ml-auto group-hover:scale-110 transition-transform duration-300" style="background-color: {{ $service->color }}15; width: 40px; height: 40px;">
            <i class="{{ $service->icon }} text-xl" style="color: {{ $service->color }};"></i>
        </div>
        
        <h3 class="text-lg font-semibold text-gray-900 mb-3 group-hover:text-gray-700 transition-colors">{{ $service->name }}</h3>
        <p class="text-sm text-gray-600 leading-relaxed">{{ $service->description }}</p>
    </div>
</div>
