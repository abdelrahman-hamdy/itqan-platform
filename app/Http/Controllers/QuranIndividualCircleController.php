<?php

namespace App\Http\Controllers;

use App\Models\QuranIndividualCircle;
use App\Models\QuranSession;
use App\Services\QuranSessionSchedulingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class QuranIndividualCircleController extends Controller
{
    private QuranSessionSchedulingService $schedulingService;

    public function __construct(QuranSessionSchedulingService $schedulingService)
    {
        $this->schedulingService = $schedulingService;
        $this->middleware('auth');
    }

    /**
     * Display individual circles for the teacher
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        if (!$user->isQuranTeacher()) {
            abort(403, 'غير مسموح لك بالوصول لهذه الصفحة');
        }

        $circles = QuranIndividualCircle::where('quran_teacher_id', $user->id)
            ->with(['student', 'subscription.package'])
            ->when($request->status, function ($query, $status) {
                return $query->where('status', $status);
            })
            ->latest()
            ->paginate(15);

        return view('teacher.individual-circles.index', compact('circles'));
    }

    /**
     * Show individual circle details
     */
    public function show($subdomain, $circle)
    {
        $user = Auth::user();
        
        // Find the circle
        $circleModel = QuranIndividualCircle::findOrFail($circle);
        
        // Determine user role and permissions
        $userRole = 'guest';
        $isTeacher = false;
        $isStudent = false;
        
        if ($user->user_type === 'quran_teacher' && $circleModel->quran_teacher_id === $user->id) {
            $userRole = 'teacher';
            $isTeacher = true;
        } elseif ($user->user_type === 'student' && $circleModel->student_id === $user->id) {
            $userRole = 'student';
            $isStudent = true;
        } else {
            abort(403, 'غير مسموح لك بالوصول لهذه الحلقة');
        }

        $circleModel->load([
            'student', 
            'subscription.package',
            'sessions' => function ($query) {
                $query->orderBy('scheduled_at');
            },
            'scheduledSessions' => function ($query) {
                $query->whereIn('status', ['scheduled', 'in_progress'])->orderBy('scheduled_at');
            },
            'completedSessions' => function ($query) {
                $query->where('status', 'completed')->orderBy('ended_at', 'desc');
            },
            'templateSessions' => function ($query) {
                $query->orderBy('session_sequence');
            }
        ]);

        // Rename for view consistency  
        $circle = $circleModel;
        
        // Determine which view to use based on user role
        $viewName = $userRole === 'teacher' ? 'teacher.individual-circles.show' : 'student.individual-circles.show';
        
        return view($viewName, compact('circle', 'userRole', 'isTeacher', 'isStudent'));
    }

    /**
     * Get unscheduled sessions for a circle (AJAX)
     */
    public function getTemplateSessions($subdomain, $circle): JsonResponse
    {
        $user = Auth::user();
        
        // Find the circle
        $circleModel = QuranIndividualCircle::findOrFail($circle);
        
        // Check ownership - user should be the teacher of this circle
        if ($user->user_type !== 'quran_teacher' || $circleModel->quran_teacher_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'غير مسموح'], 403);
        }

        // Get unscheduled sessions instead of templates
        $unscheduledSessions = $circleModel->sessions()
            ->where('status', 'pending')
            ->orderBy('monthly_session_number')
            ->get(['id', 'title', 'monthly_session_number', 'duration_minutes']);

        return response()->json([
            'success' => true,
            'sessions' => $unscheduledSessions->map(function ($session) {
                return [
                    'id' => $session->id,
                    'title' => $session->title,
                    'sequence' => $session->monthly_session_number ?? 0,
                    'duration' => $session->duration_minutes,
                ];
            })
        ]);
    }

    /**
     * Schedule a template session
     */
    public function scheduleSession(Request $request, $subdomain, $circle): JsonResponse
    {
        $user = Auth::user();
        
        // Find the circle
        $circleModel = QuranIndividualCircle::findOrFail($circle);
        
        // Check ownership - user should be the teacher of this circle
        if ($user->user_type !== 'quran_teacher' || $circleModel->quran_teacher_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'غير مسموح'], 403);
        }

        $request->validate([
            'template_session_id' => 'required|exists:quran_sessions,id',
            'scheduled_at' => 'required|date|after:now',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'lesson_objectives' => 'nullable|array',
        ]);

        try {
            $unscheduledSession = QuranSession::findOrFail($request->template_session_id);
            
            // Verify the session belongs to this circle and is unscheduled
            if ($unscheduledSession->individual_circle_id !== $circleModel->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'الجلسة المحددة لا تنتمي لهذه الحلقة'
                ], 400);
            }

            if ($unscheduledSession->status !== 'unscheduled') {
                return response()->json([
                    'success' => false,
                    'message' => 'هذه الجلسة مجدولة بالفعل'
                ], 400);
            }

            // Update the unscheduled session to scheduled
            $unscheduledSession->update([
                'status' => 'scheduled',
                'is_scheduled' => true,
                'scheduled_at' => Carbon::parse($request->scheduled_at),
                'title' => $request->title ?: $unscheduledSession->title,
                'description' => $request->description ?: $unscheduledSession->description,
                'lesson_objectives' => $request->lesson_objectives ?: $unscheduledSession->lesson_objectives,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم جدولة الجلسة بنجاح',
                'session' => [
                    'id' => $unscheduledSession->id,
                    'title' => $unscheduledSession->title,
                    'scheduled_at' => $unscheduledSession->scheduled_at->format('Y-m-d H:i'),
                    'sequence' => $unscheduledSession->monthly_session_number ?? 0,
                    'status' => $unscheduledSession->status,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في جدولة الجلسة: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk schedule multiple sessions
     */
    public function bulkSchedule(Request $request, $subdomain, $circle): JsonResponse
    {
        $user = Auth::user();
        
        // Find the circle
        $circleModel = QuranIndividualCircle::findOrFail($circle);
        
        // Check ownership - user should be the teacher of this circle
        if ($user->user_type !== 'quran_teacher' || $circleModel->quran_teacher_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'غير مسموح'], 403);
        }

        $request->validate([
            'sessions' => 'required|array|min:1',
            'sessions.*.template_session_id' => 'required|exists:quran_sessions,id',
            'sessions.*.scheduled_at' => 'required|date|after:now',
            'sessions.*.title' => 'nullable|string|max:255',
            'sessions.*.description' => 'nullable|string',
        ]);

        try {
            $scheduledSessions = $this->schedulingService->bulkScheduleIndividualSessions(
                $circleModel,
                $request->sessions
            );

            return response()->json([
                'success' => true,
                'message' => "تم جدولة {$scheduledSessions->count()} جلسة بنجاح",
                'scheduled_count' => $scheduledSessions->count(),
                'sessions' => $scheduledSessions->map(function ($session) {
                    return [
                        'id' => $session->id,
                        'title' => $session->title,
                        'scheduled_at' => $session->scheduled_at->format('Y-m-d H:i'),
                        'sequence' => $session->monthly_session_number ?? 0,
                    ];
                })
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في جدولة الجلسات: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available time slots for scheduling
     */
    public function getAvailableTimeSlots(Request $request, $subdomain, $circle): JsonResponse
    {
        $user = Auth::user();
        
        // Find the circle
        $circleModel = QuranIndividualCircle::findOrFail($circle);
        
        // Check ownership - user should be the teacher of this circle
        if ($user->user_type !== 'quran_teacher' || $circleModel->quran_teacher_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'غير مسموح'], 403);
        }

        $request->validate([
            'date' => 'required|date|after_or_equal:today',
            'duration' => 'integer|min:15|max:240',
        ]);

        $date = Carbon::parse($request->date);
        $duration = $request->duration ?? $circleModel->default_duration_minutes;

        $availableSlots = $this->schedulingService->getAvailableTimeSlots(
            $user->id,
            $date,
            $duration
        );

        return response()->json([
            'success' => true,
            'date' => $date->format('Y-m-d'),
            'available_slots' => $availableSlots
        ]);
    }

    /**
     * Update circle settings
     */
    public function updateSettings(Request $request, $subdomain, $circle): JsonResponse
    {
        $user = Auth::user();
        
        // Find the circle
        $circleModel = QuranIndividualCircle::findOrFail($circle);
        
        // Check ownership - user should be the teacher of this circle
        if ($user->user_type !== 'quran_teacher' || $circleModel->quran_teacher_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'غير مسموح'], 403);
        }

        $request->validate([
            'default_duration_minutes' => 'integer|min:15|max:240',
            'preferred_times' => 'array',
            'meeting_link' => 'nullable|url',
            'meeting_id' => 'nullable|string|max:100',
            'meeting_password' => 'nullable|string|max:50',
            'recording_enabled' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        try {
            $circleModel->update($request->only([
                'default_duration_minutes',
                'preferred_times',
                'meeting_link',
                'meeting_id',
                'meeting_password',
                'recording_enabled',
                'notes',
            ]));

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث إعدادات الحلقة بنجاح'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في تحديث الإعدادات: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate comprehensive progress report for the circle
     */
    public function progressReport($subdomain, $circle)
    {
        $user = Auth::user();
        
        // Find the circle
        $circleModel = QuranIndividualCircle::findOrFail($circle);
        
        // Check ownership - user should be the teacher of this circle
        if ($user->user_type !== 'quran_teacher' || $circleModel->quran_teacher_id !== $user->id) {
            abort(403, 'غير مسموح لك بالوصول لهذا التقرير');
        }

        // Load comprehensive data for enhanced progress tracking
        $circleModel->load([
            'student.studentProfile',
            'subscription.package',
            'sessions' => function ($query) {
                $query->orderBy('scheduled_at', 'desc');
            },
            'homework',
            'progress'
        ]);

        // Get detailed session statistics for attendance analysis
        $totalSessions = $circleModel->sessions->count();
        $completedSessions = $circleModel->sessions->where('status', 'completed');
        $scheduledSessions = $circleModel->sessions->where('status', 'scheduled');
        
        // Enhanced attendance analysis with different statuses
        $attendedSessions = $completedSessions->where('attendance_status', 'attended')->count();
        $lateSessions = $completedSessions->where('attendance_status', 'late')->count();
        $absentSessions = $completedSessions->where('attendance_status', 'absent')->count();
        $leftEarlySessions = $completedSessions->where('attendance_status', 'left_early')->count();
        
        // For sessions without explicit attendance_status, assume attended if completed
        $completedWithoutStatus = $completedSessions->whereNull('attendance_status')->count();
        $totalAttended = $attendedSessions + $lateSessions + $leftEarlySessions + $completedWithoutStatus;
        
        // Performance metrics calculation
        $avgRecitation = $completedSessions->avg('recitation_quality') ?? 0;
        $avgTajweed = $completedSessions->avg('tajweed_accuracy') ?? 0;
        $avgDuration = $completedSessions->avg('actual_duration_minutes') ?? 0;
        
        // Calculate total papers memorized (using new paper-based system)
        $totalPapers = $circleModel->papers_memorized_precise ?? 
            ($circleModel->verses_memorized ? $this->convertVersesToPapers($circleModel->verses_memorized) : 0);

        // Comprehensive statistics for the enhanced view
        $stats = [
            // Basic session counts
            'total_sessions' => $totalSessions,
            'completed_sessions' => $completedSessions->count(),
            'scheduled_sessions' => $scheduledSessions->count(),
            'remaining_sessions' => max(0, ($circleModel->total_sessions ?? 0) - $completedSessions->count()),
            'progress_percentage' => $circleModel->progress_percentage ?? 0,
            
            // Enhanced attendance metrics
            'attendance_rate' => $completedSessions->count() > 0 
                ? ($totalAttended / $completedSessions->count()) * 100 
                : 0,
            'attended_sessions' => $attendedSessions,
            'late_sessions' => $lateSessions,
            'absent_sessions' => $absentSessions,
            'left_early_sessions' => $leftEarlySessions,
            
            // Performance and progress metrics
            'avg_recitation_quality' => $avgRecitation,
            'avg_tajweed_accuracy' => $avgTajweed,
            'avg_session_duration' => $avgDuration,
            'total_papers_memorized' => $totalPapers,
            
            // Learning analytics
            'papers_per_session' => $completedSessions->count() > 0 && $totalPapers > 0 
                ? $totalPapers / $completedSessions->count() 
                : 0,
            'consistency_score' => $this->calculateConsistencyScore($circleModel),
        ];

        // Rename for view consistency
        $circle = $circleModel;
        return view('teacher.individual-circles.progress', compact('circle', 'stats'));
    }

    /**
     * Convert verses to approximate paper count (وجه)
     * Based on standard Quran structure
     */
    private function convertVersesToPapers(int $verses): float
    {
        // Average verses per paper (وجه) in standard Mushaf
        // This varies by Surah, but 17.5 is a reasonable average
        $averageVersesPerPaper = 17.5;
        return round($verses / $averageVersesPerPaper, 2);
    }

    /**
     * Calculate consistency score based on attendance pattern
     */
    private function calculateConsistencyScore($circle): float
    {
        $sessions = $circle->sessions()->where('status', 'completed')->orderBy('scheduled_at')->get();
        
        if ($sessions->count() < 2) {
            return 0;
        }

        $attendancePattern = $sessions->map(function ($session) {
            // Score: attended = 1, late = 0.7, left_early = 0.5, absent = 0
            return match($session->attendance_status) {
                'attended' => 1.0,
                'late' => 0.7,
                'left_early' => 0.5,
                'absent' => 0.0,
                default => 1.0 // Default to attended if status not set
            };
        });

        // Calculate consistency based on variance in attendance
        $mean = $attendancePattern->avg();
        $variance = $attendancePattern->map(fn($score) => pow($score - $mean, 2))->avg();
        
        // Convert to consistency score (0-10, where 10 is most consistent)
        $consistencyScore = max(0, 10 - ($variance * 20));
        
        return round($consistencyScore, 1);
    }
}