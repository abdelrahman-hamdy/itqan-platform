@extends('layouts.app')

@section('title', $course->title)

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-7xl mx-auto">
        <!-- Breadcrumb -->
        <nav class="mb-8">
            <ol class="flex items-center space-x-2 space-x-reverse text-sm text-gray-600">
                <li><a href="{{ route('courses.index') }}" class="hover:text-primary">الدورات</a></li>
                <li>/</li>
                <li class="text-gray-900">{{ $course->title }}</li>
            </ol>
        </nav>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Course Content -->
            <div class="lg:col-span-2">
                <!-- Course Header -->
                <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-8">
                    @if($course->thumbnail)
                    <div class="aspect-video bg-gray-200">
                        <img src="{{ asset('storage/' . $course->thumbnail) }}" 
                             alt="{{ $course->title }}" 
                             class="w-full h-full object-cover">
                    </div>
                    @endif
                    
                    <div class="p-6">
                        <div class="flex items-center gap-4 mb-4">
                            @if($course->category)
                            <span class="bg-primary/10 text-primary px-3 py-1 rounded-full text-sm font-medium">
                                {{ $course->category->name }}
                            </span>
                            @endif
                            @if($course->difficulty_level)
                            <span class="bg-gray-100 text-gray-700 px-3 py-1 rounded-full text-sm">
                                {{ $course->difficulty_level }}
                            </span>
                            @endif
                        </div>
                        
                        <h1 class="text-3xl font-bold text-gray-900 mb-4">{{ $course->title }}</h1>
                        
                        @if($course->instructor_name)
                        <div class="flex items-center gap-2 mb-6">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                            <span class="text-gray-700 font-medium">{{ $course->instructor_name }}</span>
                        </div>
                        @endif
                        
                        <!-- Course Stats -->
                        <div class="grid grid-cols-3 gap-4 mb-6">
                            <div class="text-center">
                                <div class="text-lg font-bold text-gray-900">{{ $course->sections->count() }}</div>
                                <div class="text-sm text-gray-600">فصول</div>
                            </div>
                            <div class="text-center">
                                <div class="text-lg font-bold text-gray-900">
                                    {{ $course->sections->sum(function($section) { return $section->lessons->count(); }) }}
                                </div>
                                <div class="text-sm text-gray-600">دروس</div>
                            </div>
                            <div class="text-center">
                                <div class="text-lg font-bold text-gray-900">
                                    {{ $course->duration_hours ?? 'N/A' }}{{ $course->duration_hours ? ' ساعة' : '' }}
                                </div>
                                <div class="text-sm text-gray-600">المدة</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Course Description -->
                @if($course->description)
                <div class="bg-white rounded-xl shadow-sm p-6 mb-8">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">وصف الدورة</h2>
                    <div class="prose prose-gray max-w-none">
                        {!! nl2br(e($course->description)) !!}
                    </div>
                </div>
                @endif

                <!-- Learning Objectives -->
                @if($course->learning_objectives)
                <div class="bg-white rounded-xl shadow-sm p-6 mb-8">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">أهداف التعلم</h2>
                    <div class="prose prose-gray max-w-none">
                        @if(is_array($course->learning_objectives))
                        <ul class="space-y-2">
                            @foreach($course->learning_objectives as $objective)
                            <li class="flex items-start gap-2">
                                <svg class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                                <span>{{ $objective }}</span>
                            </li>
                            @endforeach
                        </ul>
                        @else
                        {!! nl2br(e($course->learning_objectives)) !!}
                        @endif
                    </div>
                </div>
                @endif

                <!-- Course Curriculum -->
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-6">المنهج الدراسي</h2>
                    
                    @if($course->sections->count() > 0)
                    <div class="space-y-4">
                        @foreach($course->sections as $section)
                        <div class="border border-gray-200 rounded-lg">
                            <div class="p-4 bg-gray-50 border-b border-gray-200">
                                <h3 class="font-semibold text-gray-900">{{ $section->title }}</h3>
                                @if($section->description)
                                <p class="text-sm text-gray-600 mt-1">{{ $section->description }}</p>
                                @endif
                                <div class="text-sm text-gray-500 mt-2">
                                    {{ $section->lessons->count() }} {{ $section->lessons->count() === 1 ? 'درس' : 'دروس' }}
                                </div>
                            </div>
                            
                            @if($section->lessons->count() > 0)
                            <div class="divide-y divide-gray-200">
                                @foreach($section->lessons as $lesson)
                                <div class="p-4 flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center">
                                            @if($enrollment)
                                            <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                            </svg>
                                            @else
                                            <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"></path>
                                                <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"></path>
                                            </svg>
                                            @endif
                                        </div>
                                        <div>
                                            <h4 class="font-medium text-gray-900">{{ $lesson->title }}</h4>
                                            @if($lesson->description)
                                            <p class="text-sm text-gray-600">{{ Str::limit($lesson->description, 100) }}</p>
                                            @endif
                                        </div>
                                    </div>
                                    
                                    <div class="flex items-center gap-4">
                                        @if($lesson->duration_minutes)
                                        <span class="text-sm text-gray-500">{{ $lesson->duration_minutes }} دقيقة</span>
                                        @endif
                                        
                                        @if($enrollment)
                                        <a href="{{ route('lessons.show', [$course->id, $lesson->id]) }}" 
                                           class="text-primary hover:text-primary-dark text-sm font-medium">
                                            مشاهدة
                                        </a>
                                        @else
                                        <svg class="w-5 h-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"></path>
                                        </svg>
                                        @endif
                                    </div>
                                </div>
                                @endforeach
                            </div>
                            @endif
                        </div>
                        @endforeach
                    </div>
                    @else
                    <div class="text-center py-8">
                        <p class="text-gray-600">لا توجد دروس متاحة حالياً</p>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Sidebar -->
            <div class="lg:col-span-1">
                <!-- Enrollment Card -->
                <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
                    @if($enrollment)
                    <!-- Already Enrolled -->
                    <div class="text-center mb-6">
                        <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-green-600 mb-2">مسجل في الدورة</h3>
                        <p class="text-sm text-gray-600">يمكنك الآن الوصول لجميع دروس هذه الدورة</p>
                    </div>
                    
                    <!-- Progress -->
                    @if($enrollment->progress_percentage)
                    <div class="mb-6">
                        <div class="flex justify-between text-sm mb-2">
                            <span class="text-gray-600">التقدم</span>
                            <span class="font-medium">{{ $enrollment->progress_percentage }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-green-500 h-2 rounded-full" style="width: {{ $enrollment->progress_percentage }}%"></div>
                        </div>
                    </div>
                    @endif
                    
                    <a href="{{ route('courses.learn', $course->id) }}" 
                       class="w-full bg-primary text-white py-3 px-4 rounded-lg font-medium hover:bg-primary-dark transition-colors text-center block">
                        متابعة التعلم
                    </a>
                    
                    @else
                    <!-- Not Enrolled -->
                    <div class="text-center mb-6">
                        @if($course->price && $course->price > 0)
                        <div class="text-3xl font-bold text-gray-900 mb-2">
                            {{ number_format($course->price) }} {{ $course->currency ?? 'ريال' }}
                        </div>
                        @if($course->original_price && $course->original_price > $course->price)
                        <div class="text-lg text-gray-500 line-through mb-2">
                            {{ number_format($course->original_price) }} {{ $course->currency ?? 'ريال' }}
                        </div>
                        @endif
                        @else
                        <div class="text-3xl font-bold text-green-600 mb-2">مجاني</div>
                        @endif
                    </div>
                    
                    <a href="{{ route('courses.checkout', $course->id) }}" 
                       class="w-full bg-primary text-white py-3 px-4 rounded-lg font-medium hover:bg-primary-dark transition-colors text-center block mb-4">
                        {{ $course->price && $course->price > 0 ? 'اشترك الآن' : 'تسجيل مجاني' }}
                    </a>
                    
                    <div class="text-center">
                        <p class="text-sm text-gray-600">ضمان الجودة لمدة 30 يوماً</p>
                    </div>
                    @endif
                </div>

                <!-- Course Features -->
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <h3 class="font-bold text-gray-900 mb-4">مميزات الدورة</h3>
                    <div class="space-y-3">
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="text-sm text-gray-700">دروس مسجلة عالية الجودة</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="text-sm text-gray-700">وصول مدى الحياة</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="text-sm text-gray-700">شهادة إتمام</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="text-sm text-gray-700">دعم فني متميز</span>
                        </div>
                        @if($course->has_assignments)
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="text-sm text-gray-700">واجبات وتطبيقات عملية</span>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection