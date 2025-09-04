# 📋 Comprehensive Quran Homework & Attendance System Implementation Plan

## 🎯 **SYSTEM OVERVIEW**

This plan implements a sophisticated Quran homework and attendance tracking system for both individual and group circles with the following key principles:

- **Quran-specific homework**: Pages-based system with "حفظ جديد" (new memorization) and "مراجعة" (review)
- **Automatic attendance tracking**: Based on LiveKit meeting join/leave events
- **Manual override capability**: Teachers can modify attendance records
- **Non-breaking implementation**: Preserve all existing functionality
- **Consistent UX**: Unified experience across individual and group sessions

---

## 🏗️ **DATABASE ARCHITECTURE**

### **1. QuranSessionHomework Model** 
*Session-level homework configuration*

```php
// Migration: create_quran_session_homeworks_table.php
Schema::create('quran_session_homeworks', function (Blueprint $table) {
    $table->id();
    $table->foreignId('session_id')->constrained('quran_sessions')->onDelete('cascade');
    $table->foreignId('created_by')->constrained('users')->onDelete('cascade'); // Teacher who created
    
    // New Memorization (حفظ جديد)
    $table->decimal('new_memorization_pages', 4, 2)->default(0); // e.g., 1.5 pages
    $table->string('new_memorization_surah')->nullable();
    $table->integer('new_memorization_from_verse')->nullable();
    $table->integer('new_memorization_to_verse')->nullable();
    $table->text('new_memorization_notes')->nullable();
    
    // Review (مراجعة) 
    $table->decimal('review_pages', 4, 2)->default(0); // e.g., 2.0 pages
    $table->string('review_surah')->nullable();
    $table->integer('review_from_verse')->nullable();
    $table->integer('review_to_verse')->nullable();
    $table->text('review_notes')->nullable();
    
    // General homework settings
    $table->text('additional_instructions')->nullable();
    $table->date('due_date')->nullable();
    $table->enum('difficulty_level', ['easy', 'medium', 'hard'])->default('medium');
    $table->boolean('is_active')->default(true);
    
    $table->timestamps();
    
    // Indexes
    $table->index(['session_id', 'is_active']);
});
```

### **2. QuranHomeworkAssignment Model**
*Student-specific homework completion tracking*

```php
// Migration: create_quran_homework_assignments_table.php
Schema::create('quran_homework_assignments', function (Blueprint $table) {
    $table->id();
    $table->foreignId('session_homework_id')->constrained('quran_session_homeworks')->onDelete('cascade');
    $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
    $table->foreignId('session_id')->constrained('quran_sessions')->onDelete('cascade');
    
    // New Memorization Progress
    $table->decimal('new_memorization_completed_pages', 4, 2)->default(0);
    $table->enum('new_memorization_quality', ['excellent', 'good', 'needs_improvement', 'not_completed'])->nullable();
    $table->text('new_memorization_teacher_notes')->nullable();
    
    // Review Progress  
    $table->decimal('review_completed_pages', 4, 2)->default(0);
    $table->enum('review_quality', ['excellent', 'good', 'needs_improvement', 'not_completed'])->nullable();
    $table->text('review_teacher_notes')->nullable();
    
    // Overall Assessment
    $table->decimal('overall_score', 3, 1)->nullable(); // 0.0 to 10.0
    $table->enum('completion_status', ['not_started', 'in_progress', 'completed', 'partially_completed'])->default('not_started');
    $table->boolean('submitted_by_student')->default(false);
    $table->timestamp('submitted_at')->nullable();
    $table->timestamp('evaluated_by_teacher_at')->nullable();
    $table->foreignId('evaluated_by')->nullable()->constrained('users')->onDelete('set null');
    
    $table->timestamps();
    
    // Unique constraint - one assignment per student per session homework
    $table->unique(['session_homework_id', 'student_id']);
    $table->index(['student_id', 'completion_status']);
    $table->index(['session_id', 'student_id']);
});
```

### **3. Enhanced QuranSessionAttendance Model**
*Automatic + manual attendance tracking*

```php
// Migration: enhance_quran_session_attendances_table.php
Schema::table('quran_session_attendances', function (Blueprint $table) {
    // Auto-tracking fields
    $table->timestamp('auto_join_time')->nullable()->after('join_time');
    $table->timestamp('auto_leave_time')->nullable()->after('leave_time');
    $table->integer('auto_duration_minutes')->nullable()->after('leave_time');
    $table->boolean('auto_tracked')->default(false)->after('attendance_status');
    
    // Manual override fields
    $table->boolean('manually_overridden')->default(false)->after('auto_tracked');
    $table->foreignId('overridden_by')->nullable()->constrained('users')->onDelete('set null')->after('manually_overridden');
    $table->timestamp('overridden_at')->nullable()->after('overridden_by');
    $table->text('override_reason')->nullable()->after('overridden_at');
    
    // Enhanced tracking
    $table->json('meeting_events')->nullable()->after('notes'); // JSON log of join/leave events
    $table->integer('connection_quality_score')->nullable()->after('meeting_events'); // 1-10
    
    // Homework completion link
    $table->decimal('pages_memorized_today', 4, 2)->nullable()->after('verses_memorized_today');
    $table->decimal('pages_reviewed_today', 4, 2)->nullable()->after('pages_memorized_today');
    
    // Update indexes
    $table->index(['auto_tracked', 'manually_overridden']);
    $table->index(['session_id', 'auto_tracked']);
});
```

---

## 🔧 **BACKEND IMPLEMENTATION**

### **1. Models & Relationships**

```php
// app/Models/QuranSessionHomework.php
class QuranSessionHomework extends Model
{
    protected $fillable = [
        'session_id', 'created_by',
        'new_memorization_pages', 'new_memorization_surah', 'new_memorization_from_verse', 'new_memorization_to_verse', 'new_memorization_notes',
        'review_pages', 'review_surah', 'review_from_verse', 'review_to_verse', 'review_notes',
        'additional_instructions', 'due_date', 'difficulty_level', 'is_active'
    ];

    protected $casts = [
        'new_memorization_pages' => 'decimal:2',
        'review_pages' => 'decimal:2',
        'due_date' => 'date',
        'is_active' => 'boolean'
    ];

    public function session(): BelongsTo {
        return $this->belongsTo(QuranSession::class, 'session_id');
    }

    public function creator(): BelongsTo {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignments(): HasMany {
        return $this->hasMany(QuranHomeworkAssignment::class, 'session_homework_id');
    }

    // Helper methods
    public function getTotalPagesAttribute(): float {
        return $this->new_memorization_pages + $this->review_pages;
    }

    public function getCompletionStatsAttribute(): array {
        $assignments = $this->assignments()->get();
        return [
            'total_students' => $assignments->count(),
            'completed' => $assignments->where('completion_status', 'completed')->count(),
            'in_progress' => $assignments->where('completion_status', 'in_progress')->count(),
            'not_started' => $assignments->where('completion_status', 'not_started')->count(),
        ];
    }
}

// app/Models/QuranHomeworkAssignment.php  
class QuranHomeworkAssignment extends Model
{
    protected $fillable = [
        'session_homework_id', 'student_id', 'session_id',
        'new_memorization_completed_pages', 'new_memorization_quality', 'new_memorization_teacher_notes',
        'review_completed_pages', 'review_quality', 'review_teacher_notes',
        'overall_score', 'completion_status', 'submitted_by_student', 'submitted_at', 'evaluated_by_teacher_at', 'evaluated_by'
    ];

    protected $casts = [
        'new_memorization_completed_pages' => 'decimal:2',
        'review_completed_pages' => 'decimal:2',
        'overall_score' => 'decimal:1',
        'submitted_by_student' => 'boolean',
        'submitted_at' => 'datetime',
        'evaluated_by_teacher_at' => 'datetime'
    ];

    public function sessionHomework(): BelongsTo {
        return $this->belongsTo(QuranSessionHomework::class, 'session_homework_id');
    }

    public function student(): BelongsTo {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function session(): BelongsTo {
        return $this->belongsTo(QuranSession::class, 'session_id');
    }

    public function evaluator(): BelongsTo {
        return $this->belongsTo(User::class, 'evaluated_by');
    }

    // Helper methods
    public function getTotalCompletedPagesAttribute(): float {
        return $this->new_memorization_completed_pages + $this->review_completed_pages;
    }

    public function getCompletionPercentageAttribute(): float {
        $homework = $this->sessionHomework;
        $totalRequired = $homework->total_pages;
        return $totalRequired > 0 ? min(100, ($this->total_completed_pages / $totalRequired) * 100) : 0;
    }
}

// Enhanced QuranSessionAttendance.php
class QuranSessionAttendance extends Model 
{
    protected $fillable = [
        'session_id', 'student_id', 'attendance_status',
        'join_time', 'leave_time', 'auto_join_time', 'auto_leave_time', 'auto_duration_minutes',
        'auto_tracked', 'manually_overridden', 'overridden_by', 'overridden_at', 'override_reason',
        'participation_score', 'recitation_quality', 'tajweed_accuracy', 'verses_reviewed',
        'homework_completion', 'pages_memorized_today', 'pages_reviewed_today', 'notes',
        'meeting_events', 'connection_quality_score'
    ];

    protected $casts = [
        'join_time' => 'datetime',
        'leave_time' => 'datetime', 
        'auto_join_time' => 'datetime',
        'auto_leave_time' => 'datetime',
        'overridden_at' => 'datetime',
        'auto_tracked' => 'boolean',
        'manually_overridden' => 'boolean',
        'homework_completion' => 'boolean',
        'participation_score' => 'decimal:1',
        'recitation_quality' => 'decimal:1',
        'tajweed_accuracy' => 'decimal:1',
        'pages_memorized_today' => 'decimal:2',
        'pages_reviewed_today' => 'decimal:2',
        'meeting_events' => 'array'
    ];

    // Automatic attendance calculation
    public function calculateAttendanceFromMeetingEvents(): string {
        if (!$this->meeting_events || empty($this->meeting_events)) {
            return 'absent';
        }

        $joinTime = $this->auto_join_time;
        $leaveTime = $this->auto_leave_time;
        $sessionStart = $this->session->scheduled_at;
        
        if (!$joinTime) {
            return 'absent';
        }

        // Late if joined more than 10 minutes after session start
        $minutesLate = $joinTime->diffInMinutes($sessionStart, false);
        if ($minutesLate > 10) {
            return 'late';
        }

        // Left early if left more than 10 minutes before expected end
        $expectedEnd = $sessionStart->addMinutes($this->session->duration_minutes);
        if ($leaveTime && $leaveTime->isBefore($expectedEnd->subMinutes(10))) {
            return 'left_early';
        }

        return 'present';
    }
}

// Update QuranSession.php relationships
class QuranSession extends Model 
{
    // Add new relationships
    public function sessionHomework(): HasOne {
        return $this->hasOne(QuranSessionHomework::class, 'session_id');
    }

    public function homeworkAssignments(): HasMany {
        return $this->hasMany(QuranHomeworkAssignment::class, 'session_id');
    }

    // Enhanced attendances relationship with auto-tracking
    public function attendances(): HasMany {
        return $this->hasMany(QuranSessionAttendance::class, 'session_id');
    }

    public function autoTrackedAttendances(): HasMany {
        return $this->hasMany(QuranSessionAttendance::class, 'session_id')->where('auto_tracked', true);
    }

    // Helper methods
    public function createHomeworkAssignmentsForStudents(): void {
        if (!$this->sessionHomework) return;

        $students = $this->getStudentsForSession();
        foreach ($students as $student) {
            QuranHomeworkAssignment::firstOrCreate([
                'session_homework_id' => $this->sessionHomework->id,
                'student_id' => $student->id,
                'session_id' => $this->id,
            ]);
        }
    }

    private function getStudentsForSession(): Collection {
        if ($this->session_type === 'group' && $this->circle) {
            return $this->circle->students;
        } elseif ($this->session_type === 'individual' && $this->student_id) {
            return collect([User::find($this->student_id)]);
        }
        return collect();
    }
}
```

### **2. Services**

```php
// app/Services/QuranHomeworkService.php
class QuranHomeworkService
{
    public function createSessionHomework(QuranSession $session, array $homeworkData): QuranSessionHomework
    {
        $homework = QuranSessionHomework::create([
            'session_id' => $session->id,
            'created_by' => auth()->id(),
            ...$homeworkData
        ]);

        // Auto-create assignments for all students
        $session->createHomeworkAssignmentsForStudents();

        return $homework;
    }

    public function updateHomeworkAssignment(QuranHomeworkAssignment $assignment, array $data): QuranHomeworkAssignment
    {
        $assignment->update($data);

        // Auto-calculate completion status
        $homework = $assignment->sessionHomework;
        $totalRequired = $homework->total_pages;
        $totalCompleted = $assignment->total_completed_pages;

        if ($totalCompleted >= $totalRequired) {
            $assignment->completion_status = 'completed';
        } elseif ($totalCompleted > 0) {
            $assignment->completion_status = 'in_progress';
        } else {
            $assignment->completion_status = 'not_started';
        }

        $assignment->evaluated_by_teacher_at = now();
        $assignment->evaluated_by = auth()->id();
        $assignment->save();

        return $assignment;
    }

    public function getSessionHomeworkStats(QuranSession $session): array
    {
        $homework = $session->sessionHomework;
        if (!$homework) {
            return ['has_homework' => false];
        }

        $assignments = $homework->assignments()->with('student')->get();
        
        return [
            'has_homework' => true,
            'total_pages' => $homework->total_pages,
            'new_memorization_pages' => $homework->new_memorization_pages,
            'review_pages' => $homework->review_pages,
            'total_students' => $assignments->count(),
            'completed_count' => $assignments->where('completion_status', 'completed')->count(),
            'in_progress_count' => $assignments->where('completion_status', 'in_progress')->count(),
            'not_started_count' => $assignments->where('completion_status', 'not_started')->count(),
            'average_completion' => $assignments->avg('completion_percentage'),
            'assignments' => $assignments
        ];
    }
}

// app/Services/QuranAttendanceService.php
class QuranAttendanceService
{
    public function trackMeetingEvent(string $sessionId, string $studentId, string $eventType, array $eventData): void
    {
        $attendance = QuranSessionAttendance::firstOrCreate([
            'session_id' => $sessionId,
            'student_id' => $studentId,
        ]);

        $events = $attendance->meeting_events ?? [];
        $events[] = [
            'type' => $eventType, // 'joined', 'left', 'reconnected'
            'timestamp' => now(),
            'data' => $eventData
        ];

        $attendance->meeting_events = $events;
        $attendance->auto_tracked = true;

        // Update auto join/leave times
        if ($eventType === 'joined' && !$attendance->auto_join_time) {
            $attendance->auto_join_time = now();
        }

        if ($eventType === 'left') {
            $attendance->auto_leave_time = now();
            $attendance->auto_duration_minutes = $attendance->auto_join_time 
                ? $attendance->auto_join_time->diffInMinutes(now()) 
                : 0;
        }

        // Auto-calculate attendance status if not manually overridden
        if (!$attendance->manually_overridden) {
            $attendance->attendance_status = $attendance->calculateAttendanceFromMeetingEvents();
        }

        $attendance->save();
    }

    public function manuallyOverrideAttendance(QuranSessionAttendance $attendance, array $overrideData): QuranSessionAttendance
    {
        $attendance->update($overrideData);
        $attendance->manually_overridden = true;
        $attendance->overridden_by = auth()->id();
        $attendance->overridden_at = now();
        $attendance->save();

        return $attendance;
    }

    public function getSessionAttendanceStats(QuranSession $session): array
    {
        $attendances = $session->attendances()->with('student')->get();
        
        return [
            'total_students' => $attendances->count(),
            'present_count' => $attendances->where('attendance_status', 'present')->count(),
            'late_count' => $attendances->where('attendance_status', 'late')->count(),
            'absent_count' => $attendances->where('attendance_status', 'absent')->count(),
            'left_early_count' => $attendances->where('attendance_status', 'left_early')->count(),
            'auto_tracked_count' => $attendances->where('auto_tracked', true)->count(),
            'manually_overridden_count' => $attendances->where('manually_overridden', true)->count(),
            'attendances' => $attendances
        ];
    }
}
```

---

## 🎨 **FRONTEND IMPLEMENTATION**

### **1. Session Settings Homework Section**

```php
// Add to session settings page view
<!-- resources/views/teacher/sessions/settings.blade.php -->
<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">إعدادات الواجبات</h3>
    
    <form id="homeworkForm" class="space-y-6">
        @csrf
        <input type="hidden" name="session_id" value="{{ $session->id }}">
        
        <!-- New Memorization Section -->
        <div class="border border-green-200 rounded-lg p-4 bg-green-50">
            <h4 class="font-semibold text-green-900 mb-3 flex items-center">
                <i class="ri-book-add-line ml-2"></i>
                حفظ جديد
            </h4>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">عدد الصفحات</label>
                    <input type="number" name="new_memorization_pages" step="0.5" min="0" max="10"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                           placeholder="1.5" value="{{ $homework->new_memorization_pages ?? '' }}">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">السورة</label>
                    <input type="text" name="new_memorization_surah"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                           placeholder="البقرة" value="{{ $homework->new_memorization_surah ?? '' }}">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">من الآية</label>
                    <input type="number" name="new_memorization_from_verse" min="1"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                           value="{{ $homework->new_memorization_from_verse ?? '' }}">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">إلى الآية</label>
                    <input type="number" name="new_memorization_to_verse" min="1"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                           value="{{ $homework->new_memorization_to_verse ?? '' }}">
                </div>
            </div>
            
            <div class="mt-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">ملاحظات إضافية للحفظ</label>
                <textarea name="new_memorization_notes" rows="2"
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                          placeholder="ركز على التجويد وصحة النطق">{{ $homework->new_memorization_notes ?? '' }}</textarea>
            </div>
        </div>
        
        <!-- Review Section -->
        <div class="border border-blue-200 rounded-lg p-4 bg-blue-50">
            <h4 class="font-semibold text-blue-900 mb-3 flex items-center">
                <i class="ri-refresh-line ml-2"></i>
                مراجعة
            </h4>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">عدد الصفحات</label>
                    <input type="number" name="review_pages" step="0.5" min="0" max="20"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                           placeholder="2.0" value="{{ $homework->review_pages ?? '' }}">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">السورة</label>
                    <input type="text" name="review_surah"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                           placeholder="آل عمران" value="{{ $homework->review_surah ?? '' }}">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">من الآية</label>
                    <input type="number" name="review_from_verse" min="1"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                           value="{{ $homework->review_from_verse ?? '' }}">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">إلى الآية</label>
                    <input type="number" name="review_to_verse" min="1"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                           value="{{ $homework->review_to_verse ?? '' }}">
                </div>
            </div>
            
            <div class="mt-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">ملاحظات إضافية للمراجعة</label>
                <textarea name="review_notes" rows="2"
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                          placeholder="راجع السور السابقة وركز على الأخطاء الشائعة">{{ $homework->review_notes ?? '' }}</textarea>
            </div>
        </div>
        
        <!-- General Settings -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">موعد التسليم</label>
                <input type="date" name="due_date"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                       value="{{ $homework->due_date ? $homework->due_date->format('Y-m-d') : '' }}">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">مستوى الصعوبة</label>
                <select name="difficulty_level"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="easy" {{ ($homework->difficulty_level ?? '') === 'easy' ? 'selected' : '' }}>سهل</option>
                    <option value="medium" {{ ($homework->difficulty_level ?? 'medium') === 'medium' ? 'selected' : '' }}>متوسط</option>
                    <option value="hard" {{ ($homework->difficulty_level ?? '') === 'hard' ? 'selected' : '' }}>صعب</option>
                </select>
            </div>
        </div>
        
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">تعليمات إضافية</label>
            <textarea name="additional_instructions" rows="3"
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                      placeholder="أي تعليمات أو ملاحظات إضافية للطلاب">{{ $homework->additional_instructions ?? '' }}</textarea>
        </div>
        
        <div class="flex justify-end space-x-3 space-x-reverse">
            <button type="button" class="px-4 py-2 text-gray-700 bg-gray-200 hover:bg-gray-300 rounded-lg">
                إلغاء
            </button>
            <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg">
                حفظ الواجبات
            </button>
        </div>
    </form>
</div>
```

### **2. Teacher Results Interface**

```php
// Component: resources/views/components/sessions/homework-results-management.blade.php
@props(['session'])

<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">نتائج الواجبات</h3>
    
    @if($session->sessionHomework && $session->homeworkAssignments->count() > 0)
        <div class="space-y-4">
            @foreach($session->homeworkAssignments as $assignment)
            <div class="border border-gray-200 rounded-lg p-4">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-3">
                        <x-student-avatar :student="$assignment->student" size="sm" />
                        <div>
                            <h4 class="font-semibold text-gray-900">{{ $assignment->student->name }}</h4>
                            <span class="text-sm px-2 py-1 rounded-full
                                {{ $assignment->completion_status === 'completed' ? 'bg-green-100 text-green-800' :
                                   ($assignment->completion_status === 'in_progress' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-600') }}">
                                {{ $assignment->completion_status === 'completed' ? 'مكتمل' :
                                   ($assignment->completion_status === 'in_progress' ? 'جاري' : 'لم يبدأ') }}
                            </span>
                        </div>
                    </div>
                    
                    <div class="text-right">
                        <div class="text-lg font-bold text-blue-600">{{ number_format($assignment->completion_percentage, 1) }}%</div>
                        <div class="text-sm text-gray-600">{{ $assignment->total_completed_pages }}/{{ $assignment->sessionHomework->total_pages }} صفحة</div>
                    </div>
                </div>
                
                <form class="homework-result-form" data-assignment-id="{{ $assignment->id }}">
                    @csrf
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <!-- New Memorization -->
                        <div class="border-r border-gray-200 pr-4">
                            <h5 class="font-medium text-green-900 mb-2 flex items-center">
                                <i class="ri-book-add-line ml-1"></i>
                                حفظ جديد ({{ $assignment->sessionHomework->new_memorization_pages }} صفحة)
                            </h5>
                            
                            <div class="space-y-2">
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">الصفحات المكتملة</label>
                                    <input type="number" name="new_memorization_completed_pages" 
                                           step="0.5" min="0" max="{{ $assignment->sessionHomework->new_memorization_pages }}"
                                           class="w-full px-2 py-1 border border-gray-300 rounded text-sm"
                                           value="{{ $assignment->new_memorization_completed_pages }}">
                                </div>
                                
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">جودة الحفظ</label>
                                    <select name="new_memorization_quality" class="w-full px-2 py-1 border border-gray-300 rounded text-sm">
                                        <option value="">غير محدد</option>
                                        <option value="excellent" {{ $assignment->new_memorization_quality === 'excellent' ? 'selected' : '' }}>ممتاز</option>
                                        <option value="good" {{ $assignment->new_memorization_quality === 'good' ? 'selected' : '' }}>جيد</option>
                                        <option value="needs_improvement" {{ $assignment->new_memorization_quality === 'needs_improvement' ? 'selected' : '' }}>يحتاج تحسين</option>
                                        <option value="not_completed" {{ $assignment->new_memorization_quality === 'not_completed' ? 'selected' : '' }}>لم يكمل</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Review -->
                        <div class="pl-4">
                            <h5 class="font-medium text-blue-900 mb-2 flex items-center">
                                <i class="ri-refresh-line ml-1"></i>
                                مراجعة ({{ $assignment->sessionHomework->review_pages }} صفحة)
                            </h5>
                            
                            <div class="space-y-2">
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">الصفحات المكتملة</label>
                                    <input type="number" name="review_completed_pages" 
                                           step="0.5" min="0" max="{{ $assignment->sessionHomework->review_pages }}"
                                           class="w-full px-2 py-1 border border-gray-300 rounded text-sm"
                                           value="{{ $assignment->review_completed_pages }}">
                                </div>
                                
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">جودة المراجعة</label>
                                    <select name="review_quality" class="w-full px-2 py-1 border border-gray-300 rounded text-sm">
                                        <option value="">غير محدد</option>
                                        <option value="excellent" {{ $assignment->review_quality === 'excellent' ? 'selected' : '' }}>ممتاز</option>
                                        <option value="good" {{ $assignment->review_quality === 'good' ? 'selected' : '' }}>جيد</option>
                                        <option value="needs_improvement" {{ $assignment->review_quality === 'needs_improvement' ? 'selected' : '' }}>يحتاج تحسين</option>
                                        <option value="not_completed" {{ $assignment->review_quality === 'not_completed' ? 'selected' : '' }}>لم يكمل</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">الدرجة الإجمالية (0-10)</label>
                            <input type="number" name="overall_score" min="0" max="10" step="0.1"
                                   class="w-full px-2 py-1 border border-gray-300 rounded text-sm"
                                   value="{{ $assignment->overall_score }}">
                        </div>
                        
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">حالة الإنجاز</label>
                            <select name="completion_status" class="w-full px-2 py-1 border border-gray-300 rounded text-sm">
                                <option value="not_started" {{ $assignment->completion_status === 'not_started' ? 'selected' : '' }}>لم يبدأ</option>
                                <option value="in_progress" {{ $assignment->completion_status === 'in_progress' ? 'selected' : '' }}>جاري</option>
                                <option value="partially_completed" {{ $assignment->completion_status === 'partially_completed' ? 'selected' : '' }}>مكتمل جزئياً</option>
                                <option value="completed" {{ $assignment->completion_status === 'completed' ? 'selected' : '' }}>مكتمل</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-xs font-medium text-gray-700 mb-1">ملاحظات المعلم</label>
                        <textarea name="teacher_notes" rows="2"
                                  class="w-full px-2 py-1 border border-gray-300 rounded text-sm"
                                  placeholder="ملاحظات حول أداء الطالب...">{{ $assignment->new_memorization_teacher_notes }}</textarea>
                    </div>
                    
                    <div class="flex justify-end">
                        <button type="submit" class="px-3 py-1 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded">
                            حفظ النتائج
                        </button>
                    </div>
                </form>
            </div>
            @endforeach
        </div>
    @else
        <div class="text-center py-8">
            <div class="w-16 h-16 mx-auto mb-4 bg-gray-100 rounded-full flex items-center justify-center">
                <i class="ri-file-text-line text-2xl text-gray-400"></i>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">لا توجد واجبات</h3>
            <p class="text-gray-600">لم يتم إضافة واجبات لهذه الجلسة بعد.</p>
        </div>
    @endif
</div>

<script>
document.querySelectorAll('.homework-result-form').forEach(form => {
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const assignmentId = form.dataset.assignmentId;
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());
        
        try {
            const response = await fetch(`/teacher/homework-assignments/${assignmentId}/update-results`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': data._token
                },
                body: JSON.stringify(data)
            });
            
            if (response.ok) {
                showNotification('تم حفظ نتائج الواجب بنجاح', 'success');
            } else {
                showNotification('فشل في حفظ النتائج', 'error');
            }
        } catch (error) {
            showNotification('حدث خطأ أثناء الحفظ', 'error');
        }
    });
});
</script>
```

---

## 🚀 **IMPLEMENTATION PHASES**

### **Phase 1: Database & Models** (Priority: HIGH)
1. ✅ Create migrations for new tables
2. ✅ Build model classes with relationships  
3. ✅ Add relationships to existing QuranSession model
4. ✅ Create database seeders for testing

### **Phase 2: Backend Services** (Priority: HIGH)
1. ✅ Implement QuranHomeworkService
2. ✅ Implement QuranAttendanceService
3. ✅ Create API endpoints for homework/attendance management
4. ✅ Add automatic attendance tracking via LiveKit webhooks

### **Phase 3: Teacher Interfaces** (Priority: HIGH)
1. ✅ Session settings homework section
2. ✅ Homework results management interface
3. ✅ Enhanced attendance management with manual override
4. ✅ Session statistics dashboard

### **Phase 4: Student Interfaces** (Priority: MEDIUM)
1. ✅ Student homework view with progress tracking
2. ✅ Attendance history for students
3. ✅ Homework submission interface (if needed)

### **Phase 5: Integration & Testing** (Priority: HIGH)
1. ✅ LiveKit meeting event integration
2. ✅ Comprehensive testing of all features
3. ✅ Performance optimization
4. ✅ Documentation and training materials

---

## 🎯 **SUCCESS METRICS**

- **Teacher Efficiency**: 50% reduction in time spent on homework/attendance management
- **Student Engagement**: Clear homework progress tracking increases completion rates
- **Accuracy**: 95% accuracy in automatic attendance tracking
- **System Reliability**: Zero data loss, all operations reversible
- **User Satisfaction**: Intuitive interfaces that teachers can use without training

---

This comprehensive system will provide a robust, scalable foundation for Quran homework and attendance management while maintaining the existing system's integrity and providing excellent user experience for both teachers and students. 🚀
