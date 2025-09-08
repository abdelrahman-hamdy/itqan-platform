<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\AcademicIndividualLesson;

// Check lesson 36
$lesson = AcademicIndividualLesson::find(36);

if (! $lesson) {
    echo "Lesson 36 not found\n";
    exit;
}

echo "=== LESSON 36 DATA ===\n";
echo "ID: {$lesson->id}\n";
echo "Name: {$lesson->name}\n";
echo "Status: {$lesson->status}\n";
echo "Academy ID: {$lesson->academy_id}\n";
echo "Academic Teacher ID: {$lesson->academic_teacher_id}\n";
echo "Student ID: {$lesson->student_id}\n";
echo "Subject ID: {$lesson->academic_subject_id}\n";
echo "Total Sessions: {$lesson->total_sessions}\n";
echo "\n";

// Check what teacher this lesson belongs to
if ($lesson->academicTeacher) {
    echo "=== ASSIGNED TEACHER ===\n";
    echo "Teacher ID: {$lesson->academicTeacher->id}\n";
    echo "User ID: {$lesson->academicTeacher->user_id}\n";
    $teacherName = $lesson->academicTeacher->user ? $lesson->academicTeacher->user->name : 'No user';
    echo "Name: {$teacherName}\n";
    echo "\n";
}

// Check student info
if ($lesson->student) {
    echo "=== STUDENT INFO ===\n";
    echo "Student Name: {$lesson->student->name}\n";
    echo "Student Academy ID: {$lesson->student->academy_id}\n";
    echo "\n";
}

// Check what the query would return for different scenarios
echo "=== QUERY TESTING ===\n";

// Test 1: Check if any lessons exist for this teacher
$teacherProfile = $lesson->academicTeacher;
if ($teacherProfile) {
    $lessonsForTeacher = AcademicIndividualLesson::where('academic_teacher_id', $teacherProfile->id)->count();
    echo "Total lessons for teacher {$teacherProfile->id}: {$lessonsForTeacher}\n";

    // Test 2: Check academy filter
    $lessonsForTeacherAndAcademy = AcademicIndividualLesson::where('academic_teacher_id', $teacherProfile->id)
        ->where('academy_id', $lesson->academy_id)
        ->count();
    echo "Lessons for teacher + academy filter: {$lessonsForTeacherAndAcademy}\n";

    // Test 3: Check status filter
    $lessonsWithStatusFilter = AcademicIndividualLesson::where('academic_teacher_id', $teacherProfile->id)
        ->where('academy_id', $lesson->academy_id)
        ->whereIn('status', ['active', 'approved'])
        ->count();
    echo "Lessons with status filter (active, approved): {$lessonsWithStatusFilter}\n";

    // Test 4: Check all statuses available
    $allStatuses = AcademicIndividualLesson::where('academic_teacher_id', $teacherProfile->id)
        ->where('academy_id', $lesson->academy_id)
        ->pluck('status')
        ->unique()
        ->toArray();
    echo 'All available statuses for this teacher: '.implode(', ', $allStatuses)."\n";
}
