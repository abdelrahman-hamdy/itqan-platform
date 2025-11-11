@extends('layouts.academy')

@section('title', 'التسجيل في ' . $circle->name . ' - ' . $academy->name)

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Back Button -->
        <div class="mb-6">
            <a href="{{ route('public.quran-circles.show', ['subdomain' => $academy->subdomain, 'circle' => $circle->id]) }}" 
               class="inline-flex items-center text-gray-600 hover:text-gray-900 transition-colors duration-200">
                <i class="ri-arrow-right-line ml-2"></i>
                العودة إلى تفاصيل الحلقة
            </a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Main Form -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h1 class="text-2xl font-bold text-gray-900 mb-6">التسجيل في حلقة {{ $circle->name }}</h1>

                    <form method="POST" action="{{ route('public.quran-circles.enroll.submit', ['subdomain' => $academy->subdomain, 'circle' => $circle->id]) }}">
                        @csrf

                        <!-- Current Level -->
                        <div class="mb-6">
                            <label for="current_level" class="block text-sm font-medium text-gray-700 mb-2">
                                المستوى الحالي في القرآن الكريم <span class="text-red-500">*</span>
                            </label>
                            <select name="current_level" id="current_level" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent @error('current_level') border-red-500 @enderror"
                                    required>
                                <option value="">اختر مستواك الحالي</option>
                                <option value="beginner" {{ old('current_level') === 'beginner' ? 'selected' : '' }}>مبتدئ</option>
                                <option value="elementary" {{ old('current_level') === 'elementary' ? 'selected' : '' }}>أساسي</option>
                                <option value="intermediate" {{ old('current_level') === 'intermediate' ? 'selected' : '' }}>متوسط</option>
                                <option value="advanced" {{ old('current_level') === 'advanced' ? 'selected' : '' }}>متقدم</option>
                                <option value="expert" {{ old('current_level') === 'expert' ? 'selected' : '' }}>متقن</option>
                                <option value="hafiz" {{ old('current_level') === 'hafiz' ? 'selected' : '' }}>حافظ</option>
                            </select>
                            @error('current_level')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Learning Goals -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                أهدافك التعليمية <span class="text-red-500">*</span>
                            </label>
                            <p class="text-sm text-gray-600 mb-3">يمكنك اختيار أكثر من هدف</p>
                            <div class="space-y-2">
                                <label class="flex items-center">
                                    <input type="checkbox" name="learning_goals[]" value="reading" 
                                           {{ in_array('reading', old('learning_goals', [])) ? 'checked' : '' }}
                                           class="ml-2 text-primary focus:ring-primary">
                                    <span class="text-gray-700">تعلم القراءة الصحيحة</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="learning_goals[]" value="tajweed" 
                                           {{ in_array('tajweed', old('learning_goals', [])) ? 'checked' : '' }}
                                           class="ml-2 text-primary focus:ring-primary">
                                    <span class="text-gray-700">إتقان التجويد</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="learning_goals[]" value="memorization" 
                                           {{ in_array('memorization', old('learning_goals', [])) ? 'checked' : '' }}
                                           class="ml-2 text-primary focus:ring-primary">
                                    <span class="text-gray-700">حفظ القرآن الكريم</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="learning_goals[]" value="improvement" 
                                           {{ in_array('improvement', old('learning_goals', [])) ? 'checked' : '' }}
                                           class="ml-2 text-primary focus:ring-primary">
                                    <span class="text-gray-700">تحسين الحفظ الحالي</span>
                                </label>
                            </div>
                            @error('learning_goals')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Notes -->
                        <div class="mb-6">
                            <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">
                                ملاحظات إضافية
                            </label>
                            <textarea name="notes" id="notes" rows="4" 
                                      placeholder="أخبرنا عن أي تفاصيل أخرى تريد أن يعرفها المعلم..."
                                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent @error('notes') border-red-500 @enderror">{{ old('notes') }}</textarea>
                            @error('notes')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Terms and Conditions -->
                        <div class="mb-6">
                            <label class="flex items-start">
                                <input type="checkbox" required class="ml-2 mt-1 text-primary focus:ring-primary">
                                <span class="text-sm text-gray-700">
                                    أوافق على 
                                    <a href="#" class="text-primary hover:text-primary-600 underline">الشروط والأحكام</a> 
                                    وسياسة الخصوصية الخاصة بالأكاديمية
                                </span>
                            </label>
                        </div>

                        <!-- Submit Button -->
                        <div class="flex gap-4">
                            <button type="submit" 
                                    class="flex-1 bg-primary text-white py-3 px-6 rounded-lg font-semibold hover:bg-primary-600 transition-colors duration-200">
                                إرسال طلب التسجيل
                            </button>
                            <a href="{{ route('public.quran-circles.show', ['subdomain' => $academy->subdomain, 'circle' => $circle->id]) }}" 
                               class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors duration-200">
                                إلغاء
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="lg:col-span-1">
                <!-- Circle Summary -->
                <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">ملخص الحلقة</h3>
                    
                    <div class="space-y-3">
                        <div>
                            <div class="text-sm text-gray-600">اسم الحلقة</div>
                            <div class="font-medium text-gray-900">{{ $circle->name }}</div>
                        </div>
                        
                        @if($circle->quranTeacher)
                            <div>
                                <div class="text-sm text-gray-600">المعلم</div>
                                <div class="font-medium text-gray-900">{{ $circle->quranTeacher->user->name }}</div>
                            </div>
                        @endif
                        
                        <div>
                            <div class="text-sm text-gray-600">التخصص</div>
                            <div class="font-medium text-gray-900">{{ $circle->specialization_text }}</div>
                        </div>
                        
                        <div>
                            <div class="text-sm text-gray-600">المواعيد</div>
                            <div class="font-medium text-gray-900">{{ $circle->schedule_text ?? 'سيتم تحديدها لاحقاً' }}</div>
                        </div>
                        
                        <div>
                            <div class="text-sm text-gray-600">مدة الجلسة</div>
                            <div class="font-medium text-gray-900">{{ $circle->session_duration_minutes ?? 60 }} دقيقة</div>
                        </div>
                        
                        <div>
                            <div class="text-sm text-gray-600">المقاعد المتاحة</div>
                            <div class="font-medium text-gray-900">{{ $circle->available_spots }} من {{ $circle->max_students }}</div>
                        </div>
                        
                        @if($circle->monthly_fee > 0)
                            <div>
                                <div class="text-sm text-gray-600">الرسوم الشهرية</div>
                                <div class="font-medium text-gray-900">{{ $circle->formatted_monthly_fee }}</div>
                            </div>
                        @else
                            <div>
                                <div class="text-sm text-green-600">مجاني</div>
                                <div class="font-medium text-green-700">لا توجد رسوم</div>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- What Happens Next -->
                <div class="bg-blue-50 rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">ما يحدث بعد التسجيل؟</h3>
                    <div class="space-y-3 text-sm text-gray-700">
                        <div class="flex items-start">
                            <span class="flex-shrink-0 w-6 h-6 bg-blue-500 text-white rounded-full flex items-center justify-center text-xs font-medium ml-3 mt-0.5">1</span>
                            <span>سيراجع المعلم طلبك ويتواصل معك خلال 24 ساعة</span>
                        </div>
                        <div class="flex items-start">
                            <span class="flex-shrink-0 w-6 h-6 bg-blue-500 text-white rounded-full flex items-center justify-center text-xs font-medium ml-3 mt-0.5">2</span>
                            <span>سيتم تحديد موعد أول جلسة حسب جدولك الزمني</span>
                        </div>
                        <div class="flex items-start">
                            <span class="flex-shrink-0 w-6 h-6 bg-blue-500 text-white rounded-full flex items-center justify-center text-xs font-medium ml-3 mt-0.5">3</span>
                            <span>ستحصل على رابط الجلسة عبر البريد الإلكتروني</span>
                        </div>
                        <div class="flex items-start">
                            <span class="flex-shrink-0 w-6 h-6 bg-blue-500 text-white rounded-full flex items-center justify-center text-xs font-medium ml-3 mt-0.5">4</span>
                            <span>ابدأ رحلتك في تعلم القرآن الكريم!</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection