@props([
    'students', // Collection of students
    'session',  // Session model
    'title' => null, // Optional custom title
])

@php
use App\Enums\AttendanceStatus;
    $studentCount = is_countable($students) ? count($students) : $students->count();
@endphp

@if($studentCount > 0)
<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
    <h3 class="text-lg font-bold text-gray-900 mb-4">
        <i class="ri-group-line text-primary ml-2"></i>
        {{ $title ?? "قائمة الطلاب ({$studentCount} طالب)" }}
    </h3>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الطالب</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الحضور</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">المدة</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($students as $studentData)
                    @php
                        // Handle different data structures
                        // For InteractiveCourse: $studentData is an enrollment
                        // For QuranCircle: $studentData might be a student directly
                        if (isset($studentData->studentProfile)) {
                            $student = $studentData->studentProfile;
                        } elseif (isset($studentData->student)) {
                            $student = $studentData->student;
                        } else {
                            $student = $studentData;
                        }

                        $attendance = $session->attendances->where('student_id', $student->id)->first();
                    @endphp
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10 bg-primary rounded-full flex items-center justify-center">
                                    <i class="ri-user-line text-white"></i>
                                </div>
                                <div class="mr-4">
                                    <div class="text-sm font-medium text-gray-900">{{ $student->user->name }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($attendance)
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                    {{ $attendance->status === AttendanceStatus::ATTENDED->value ? 'bg-green-100 text-green-800' :
                                       ($attendance->status === AttendanceStatus::LATE->value ? 'bg-yellow-100 text-yellow-800' :
                                       ($attendance->status === AttendanceStatus::LEFT->value ? 'bg-orange-100 text-orange-800' :
                                       'bg-red-100 text-red-800')) }}">
                                    {{ match($attendance->status) { AttendanceStatus::ATTENDED->value => 'حاضر', AttendanceStatus::LATE->value => 'متأخر', AttendanceStatus::LEFT->value => 'غادر مبكراً', default => 'غائب' } }}
                                </span>
                            @else
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                    لم يتم التسجيل
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            @if($attendance && $attendance->duration_minutes)
                                {{ $attendance->duration_minutes }} دقيقة
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
