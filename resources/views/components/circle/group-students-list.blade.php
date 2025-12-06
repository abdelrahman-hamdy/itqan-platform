@props([
    'circle',
    'viewType' => 'student' // 'student', 'teacher'
])

<div class="bg-white rounded-xl shadow-sm border border-gray-200">
    <div class="p-6 border-b border-gray-200">
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-bold text-gray-900">
                <i class="ri-group-line text-primary ml-2"></i>
                الطلاب المسجلون
            </h3>
            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                {{ $circle->students ? $circle->students->count() : 0 }} طالب
            </span>
        </div>
    </div>
    
    <div class="p-6">
        @if($circle->students && $circle->students->count() > 0)
            <div class="space-y-3">
                @foreach($circle->students as $student)
                    <div class="flex items-center p-4 bg-gradient-to-r from-gray-50 to-gray-100 rounded-xl hover:from-blue-50 hover:to-blue-100 transition-all duration-200 group">
                        <x-avatar
                            :user="$student"
                            size="md"
                            userType="student"
                            :gender="$student->gender ?? $student->studentProfile?->gender ?? 'male'" />
                        <div class="mr-4 flex-1">
                            <h4 class="font-semibold text-gray-900 group-hover:text-blue-700 transition-colors">{{ $student->name }}</h4>
                            <p class="text-sm text-gray-500">{{ $student->email ?? 'طالب' }}</p>
                            @if($student->parent)
                                <p class="text-xs text-gray-400">ولي الأمر: {{ $student->parent->name }}</p>
                            @endif
                        </div>
                        
                        @if($viewType === 'teacher')
                            <div class="flex items-center space-x-2 space-x-reverse">
                                <button class="p-2 text-green-600 hover:bg-green-100 rounded-lg transition-colors"
                                        onclick="contactStudent({{ $student->id }})"
                                        title="التواصل">
                                    <i class="ri-message-line"></i>
                                </button>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
            
            <!-- Available seats indicator -->
            @if($circle->max_students && $circle->students->count() < $circle->max_students)
                <div class="mt-4 p-3 bg-green-50 rounded-lg border border-green-200">
                    <div class="flex items-center">
                        <i class="ri-information-line text-green-600 ml-2"></i>
                        <span class="text-sm text-green-700">
                            يتوفر {{ $circle->max_students - $circle->students->count() }} مقعد إضافي في هذه الحلقة
                        </span>
                    </div>
                </div>
            @elseif($circle->max_students && $circle->students->count() >= $circle->max_students)
                <div class="mt-4 p-3 bg-yellow-50 rounded-lg border border-yellow-200">
                    <div class="flex items-center">
                        <i class="ri-alert-line text-yellow-600 ml-2"></i>
                        <span class="text-sm text-yellow-700">
                            الحلقة مكتملة - لا يمكن إضافة طلاب جدد
                        </span>
                    </div>
                </div>
            @endif
        @else
            <!-- Empty state -->
            <div class="text-center py-12">
                <div class="mx-auto w-20 h-20 bg-gradient-to-br from-gray-100 to-gray-200 rounded-full flex items-center justify-center mb-4">
                    <i class="ri-group-line text-3xl text-gray-400"></i>
                </div>
                <h4 class="text-lg font-semibold text-gray-900">لا يوجد طلاب مسجلون بعد</h4>
            </div>
        @endif
    </div>
</div>

