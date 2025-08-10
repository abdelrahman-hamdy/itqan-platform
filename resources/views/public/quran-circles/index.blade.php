@extends('layouts.academy')

@section('title', 'حلقات القرآن الكريم - ' . $academy->name)

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="bg-white rounded-lg shadow-sm p-6 mb-8">
            <div class="text-center">
                <h1 class="text-3xl font-bold text-gray-900 mb-4">حلقات القرآن الكريم</h1>
                <p class="text-lg text-gray-600 mb-6">انضم إلى حلقات تحفيظ القرآن الكريم مع معلمين متخصصين</p>
                
                <!-- Quick Stats -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-primary-50 rounded-lg p-4">
                        <div class="text-2xl font-bold text-primary-600">{{ $circles->total() }}</div>
                        <div class="text-sm text-gray-600">حلقة متاحة</div>
                    </div>
                    <div class="bg-green-50 rounded-lg p-4">
                        <div class="text-2xl font-bold text-green-600">{{ $circles->where('enrollment_status', 'open')->count() }}</div>
                        <div class="text-sm text-gray-600">مفتوحة للتسجيل</div>
                    </div>
                    <div class="bg-blue-50 rounded-lg p-4">
                        <div class="text-2xl font-bold text-blue-600">مجاني</div>
                        <div class="text-sm text-gray-600">تجربة أسبوع</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Circles Grid -->
        @if($circles->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                @foreach($circles as $circle)
                    <div class="bg-white rounded-xl shadow-sm hover:shadow-md transition-shadow duration-200 overflow-hidden">
                        <!-- Circle Header -->
                        <div class="p-6">
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <h3 class="text-lg font-bold text-gray-900 mb-1">{{ $circle->name }}</h3>
                                    <p class="text-sm text-gray-600">
                                        مع {{ $circle->quranTeacher->user->name ?? 'المعلم' }}
                                    </p>
                                </div>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                    {{ $circle->enrollment_status === 'open' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                    {{ $circle->enrollment_status_text }}
                                </span>
                            </div>
                            
                            <!-- Circle Details -->
                            <div class="space-y-3 mb-4">
                                <div class="flex items-center text-sm text-gray-600">
                                    <i class="ri-book-open-line w-4 h-4 ml-2"></i>
                                    <span>{{ $circle->specialization_text }}</span>
                                </div>
                                <div class="flex items-center text-sm text-gray-600">
                                    <i class="ri-time-line w-4 h-4 ml-2"></i>
                                    <span>{{ $circle->schedule_text ?? 'سيتم تحديد المواعيد لاحقاً' }}</span>
                                </div>
                                <div class="flex items-center text-sm text-gray-600">
                                    <i class="ri-group-line w-4 h-4 ml-2"></i>
                                    <span>{{ $circle->enrolled_students ?? 0 }} من {{ $circle->max_students }} طالب</span>
                                </div>
                                <div class="flex items-center text-sm text-gray-600">
                                    <i class="ri-star-line w-4 h-4 ml-2"></i>
                                    <span>{{ $circle->avg_rating ?? 'جديد' }} 
                                        @if($circle->avg_rating)
                                            ({{ $circle->total_reviews }} تقييم)
                                        @endif
                                    </span>
                                </div>
                                @if($circle->monthly_fee > 0)
                                    <div class="flex items-center text-sm text-gray-600">
                                        <i class="ri-money-dollar-circle-line w-4 h-4 ml-2"></i>
                                        <span>{{ $circle->formatted_monthly_fee }} شهرياً</span>
                                    </div>
                                @else
                                    <div class="flex items-center text-sm text-green-600">
                                        <i class="ri-gift-line w-4 h-4 ml-2"></i>
                                        <span>مجاني</span>
                                    </div>
                                @endif
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex gap-2">
                                <a href="{{ route('public.quran-circles.show', ['subdomain' => $academy->subdomain, 'circle' => $circle->id]) }}" 
                                   class="flex-1 bg-primary text-white py-2 px-4 rounded-lg font-semibold hover:bg-primary-600 transition-colors duration-200 text-center">
                                    عرض التفاصيل
                                </a>
                                @if($circle->enrollment_status === 'open' && !$circle->is_full)
                                    @auth
                                        @if(auth()->user()->user_type === 'student')
                                            <a href="{{ route('public.quran-circles.enroll', ['subdomain' => $academy->subdomain, 'circle' => $circle->id]) }}" 
                                               class="bg-green-500 text-white py-2 px-4 rounded-lg font-semibold hover:bg-green-600 transition-colors duration-200">
                                                انضم
                                            </a>
                                        @endif
                                    @else
                                        <a href="{{ route('login', ['subdomain' => $academy->subdomain]) }}" 
                                           class="bg-green-500 text-white py-2 px-4 rounded-lg font-semibold hover:bg-green-600 transition-colors duration-200">
                                            انضم
                                        </a>
                                    @endauth
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Pagination -->
            <div class="flex justify-center">
                {{ $circles->links() }}
            </div>
        @else
            <!-- Empty State -->
            <div class="bg-white rounded-lg shadow-sm p-12 text-center">
                <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="ri-book-open-line text-3xl text-gray-400"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">لا توجد حلقات متاحة حالياً</h3>
                <p class="text-gray-600 mb-6">نعمل على إضافة حلقات جديدة قريباً</p>
                <a href="{{ route('academy.homepage', ['subdomain' => $academy->subdomain]) }}" 
                   class="inline-flex items-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-600 transition-colors duration-200">
                    <i class="ri-arrow-right-line ml-2"></i>
                    العودة للصفحة الرئيسية
                </a>
            </div>
        @endif
    </div>
</div>
@endsection