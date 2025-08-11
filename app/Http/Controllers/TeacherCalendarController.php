<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use App\Services\CalendarService;
use App\Services\SessionSchedulingService;
use App\Services\QuranSessionSchedulingService;
use App\Services\AcademyContextService;
use App\Models\QuranSubscription;
use App\Models\QuranCircle;
use App\Models\QuranCircleSchedule;
use App\Models\QuranIndividualCircle;
use App\Models\QuranSession;
use App\Models\Academy;
use Carbon\Carbon;

class TeacherCalendarController extends Controller
{
    private CalendarService $calendarService;
    private SessionSchedulingService $schedulingService;
    private QuranSessionSchedulingService $quranSchedulingService;

    public function __construct(
        CalendarService $calendarService,
        SessionSchedulingService $schedulingService,
        QuranSessionSchedulingService $quranSchedulingService
    ) {
        $this->calendarService = $calendarService;
        $this->schedulingService = $schedulingService;
        $this->quranSchedulingService = $quranSchedulingService;
        $this->middleware('auth');
    }

    /**
     * Show the teacher calendar page
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Check if user is a teacher
        if (!$user->isQuranTeacher() && !$user->isAcademicTeacher()) {
            abort(403, 'غير مسموح لك بالوصول لهذه الصفحة');
        }

        $view = $request->get('view', 'month');
        $date = $request->get('date') ? Carbon::parse($request->get('date')) : now();

        // Get calendar data
        $startDate = $this->getStartDate($date, $view);
        $endDate = $this->getEndDate($date, $view);
        
        $events = $this->calendarService->getUserCalendar($user, $startDate, $endDate);
        $stats = $this->calendarService->getCalendarStats($user, $date);

        // Get teacher's individual and group circles for session creation
        $individualCircles = $this->getTeacherIndividualCircles();
        $groupCircles = $this->getTeacherGroupCircles();

        return view('teacher.calendar.index', compact(
            'events', 'stats', 'view', 'date', 'user',
            'individualCircles', 'groupCircles'
        ));
    }

    /**
     * Get calendar events via AJAX
     */
    public function getEvents(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        $request->validate([
            'start' => 'required|date',
            'end' => 'required|date',
            'filter' => 'in:all,individual,group,templates',
            'status' => 'in:all,upcoming,past,scheduled,pending',
            'type' => 'in:all,individual,group',
            'circle' => 'nullable|string',
            'circle_id' => 'nullable|integer',
        ]);

        $startDate = Carbon::parse($request->start);
        $endDate = Carbon::parse($request->end);
        $filter = $request->get('filter', 'all');
        $status = $request->get('status', 'all');
        $type = $request->get('type', 'all');
        $circle = $request->get('circle');
        $circleId = $request->get('circle_id');
        
        // Get events based on filters
        $events = $this->getFilteredEvents($user, $startDate, $endDate, $filter, $circle, $status, $type, $circleId);

        return response()->json([
            'success' => true,
            'events' => $events
        ]);
    }

    /**
     * Get filtered events for the calendar
     */
    private function getFilteredEvents($user, $startDate, $endDate, $filter, $circle = null, $status = 'all', $type = 'all', $circleId = null): array
    {
        $events = [];

        // Get individual circle sessions
        if ($filter === 'all' || $filter === 'individual' || $filter === 'templates') {
            $individualQuery = QuranSession::where('quran_teacher_id', $user->id)
                ->whereHas('individualCircle')
                ->whereBetween('scheduled_at', [$startDate, $endDate]);

            if ($circle && str_starts_with($circle, 'individual-')) {
                $specificCircleId = str_replace('individual-', '', $circle);
                $individualQuery->where('individual_circle_id', $specificCircleId);
            }
            
            // Filter by specific circle_id for status checking
            if ($circleId && $type === 'individual') {
                $individualQuery->where('individual_circle_id', $circleId);
            }

            if ($filter === 'templates') {
                $individualQuery->where('is_template', true)->where('is_scheduled', false);
            } elseif ($filter === 'individual') {
                $individualQuery->where('is_scheduled', true);
            }

            $individualSessions = $individualQuery->with(['individualCircle.student'])->get();

            foreach ($individualSessions as $session) {
                $events[] = [
                    'id' => 'individual-' . $session->id,
                    'title' => $session->title ?: ('جلسة فردية - ' . $session->individualCircle->student->name),
                    'start' => $session->scheduled_at ? $session->scheduled_at->toISOString() : null,
                    'end' => $session->scheduled_at ? $session->scheduled_at->addMinutes($session->duration_minutes)->toISOString() : null,
                    'backgroundColor' => $session->is_template ? '#f59e0b' : '#3b82f6',
                    'borderColor' => $session->is_template ? '#d97706' : '#2563eb',
                    'textColor' => '#ffffff',
                    'extendedProps' => [
                        'session_type' => 'individual',
                        'is_template' => $session->is_template ?? false,
                        'is_scheduled' => $session->is_scheduled ?? false,
                        'individual_circle_id' => $session->individual_circle_id,
                        'template_session_id' => $session->is_template ? $session->id : null,
                        'student_name' => $session->individualCircle->student->name ?? null,
                        'status' => $session->status,
                    ]
                ];
            }
        }

        // Get group circle sessions
        if ($filter === 'all' || $filter === 'group') {
            $groupQuery = QuranSession::where('quran_teacher_id', $user->id)
                ->where('session_type', 'group')
                ->whereBetween('scheduled_at', [$startDate, $endDate]);

            if ($circle && str_starts_with($circle, 'group-')) {
                $specificCircleId = str_replace('group-', '', $circle);
                $groupQuery->where('circle_id', $specificCircleId);
            }
            
            // Filter by specific circle_id for status checking
            if ($circleId && $type === 'group') {
                $groupQuery->where('circle_id', $circleId);
            }

            $groupSessions = $groupQuery->with(['circle'])->get();

            foreach ($groupSessions as $session) {
                $events[] = [
                    'id' => 'group-' . $session->id,
                    'title' => $session->title ?: ('حلقة جماعية - ' . $session->circle->name_ar),
                    'start' => $session->scheduled_at->toISOString(),
                    'end' => $session->scheduled_at->addMinutes($session->duration_minutes)->toISOString(),
                    'backgroundColor' => '#10b981',
                    'borderColor' => '#059669',
                    'textColor' => '#ffffff',
                    'extendedProps' => [
                        'session_type' => 'group',
                        'is_template' => false,
                        'is_scheduled' => true,
                        'circle_id' => $session->circle_id,
                        'circle_name' => $session->circle->name_ar ?? null,
                        'enrolled_students' => $session->circle->enrolled_students ?? 0,
                        'status' => $session->status,
                    ]
                ];
            }
        }

        return $events;
    }

    /**
     * Create a new session
     */
    public function createSession(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        $request->validate([
            'type' => 'required|in:individual,group',
            'individual_circle_id' => 'required_if:type,individual|exists:quran_individual_circles,id',
            'group_circle_id' => 'required_if:type,group|exists:quran_circles,id',
            'template_session_id' => 'nullable|exists:quran_sessions,id',
            'title' => 'nullable|string|max:255',
            'start_time' => 'required|date|after:now',
            'duration_minutes' => 'required|integer|min:15|max:240',
            'description' => 'nullable|string',
            'lesson_objectives' => 'nullable|string',
        ]);

        try {
            if ($request->type === 'individual') {
                $circle = QuranIndividualCircle::findOrFail($request->individual_circle_id);
                
                // Validate that the circle belongs to the teacher
                if ($circle->quran_teacher_id !== $user->id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'غير مسموح لك بجدولة هذه الجلسة'
                    ], 403);
                }

                // If template_session_id is provided, schedule that specific session
                if ($request->template_session_id) {
                    $templateSession = QuranSession::findOrFail($request->template_session_id);
                    
                    // Validate that the template session belongs to this circle
                    if ($templateSession->individual_circle_id !== $circle->id) {
                        return response()->json([
                            'success' => false,
                            'message' => 'هذه الجلسة لا تنتمي للحلقة المحددة'
                        ], 403);
                    }

                    // Schedule the template session
                    $templateSession->update([
                        'scheduled_at' => Carbon::parse($request->start_time),
                        'is_scheduled' => true,
                        'teacher_scheduled_at' => now(),
                        'scheduled_by' => $user->id,
                        'status' => 'scheduled',
                        'title' => $request->title ?: $templateSession->title,
                        'description' => $request->description ?: $templateSession->description,
                        'lesson_objectives' => $request->lesson_objectives,
                        'duration_minutes' => $request->duration_minutes ?: $templateSession->duration_minutes,
                    ]);

                    $session = $templateSession;
                } else {
                    // Create a new individual session (not from template)
                    $session = QuranSession::create([
                        'academy_id' => $circle->academy_id,
                        'quran_teacher_id' => $user->id,
                        'individual_circle_id' => $circle->id,
                        'student_id' => $circle->student_id,
                        'session_code' => 'QIS-' . uniqid(),
                        'session_type' => 'individual',
                        'status' => 'scheduled',
                        'is_scheduled' => true,
                        'is_template' => false,
                        'scheduled_at' => Carbon::parse($request->start_time),
                        'teacher_scheduled_at' => now(),
                        'scheduled_by' => $user->id,
                        'title' => $request->title ?: 'جلسة فردية - ' . $circle->name,
                        'description' => $request->description,
                        'lesson_objectives' => $request->lesson_objectives,
                        'duration_minutes' => $request->duration_minutes ?: $circle->default_duration_minutes,
                        'created_by' => $user->id,
                    ]);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'تم إنشاء الجلسة بنجاح',
                    'session' => $session
                ]);

            } else { // group circle
                $circle = QuranCircle::findOrFail($request->group_circle_id);
                
                // Validate that the circle belongs to the teacher
                if ($circle->quran_teacher_id !== $user->id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'غير مسموح لك بإنشاء جلسة لهذه الحلقة'
                    ], 403);
                }
                
                $session = QuranSession::create([
                    'academy_id' => $circle->academy_id,
                    'quran_teacher_id' => $user->id,
                    'circle_id' => $circle->id,
                    'session_code' => 'QGS-' . uniqid(),
                    'session_type' => 'group',
                    'status' => 'scheduled',
                    'is_scheduled' => true,
                    'teacher_scheduled_at' => now(),
                    'scheduled_by' => $user->id,
                    'title' => $request->title,
                    'description' => $request->description,
                    'lesson_objectives' => $request->lesson_objectives,
                    'scheduled_at' => $request->start_time,
                    'duration_minutes' => $request->duration_minutes,
                    'created_by' => $user->id,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'تم إنشاء جلسة الحلقة بنجاح',
                    'session' => $session
                ]);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في إنشاء الجلسة: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update session
     */
    public function updateSession(Request $request, QuranSession $session): JsonResponse
    {
        $user = Auth::user();
        
        // Check if user owns this session
        if ($session->quran_teacher_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'غير مسموح'], 403);
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'start_time' => 'required|date',
            'duration_minutes' => 'required|integer|min:15|max:240',
            'description' => 'nullable|string',
            'lesson_objectives' => 'nullable|string',
        ]);

        try {
            $session->update([
                'title' => $request->title,
                'scheduled_at' => $request->start_time,
                'duration_minutes' => $request->duration_minutes,
                'description' => $request->description,
                'lesson_objectives' => $request->lesson_objectives,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث الجلسة بنجاح',
                'session' => $session->fresh()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في تحديث الجلسة: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete session
     */
    public function deleteSession(QuranSession $session): JsonResponse
    {
        $user = Auth::user();
        
        // Check if user owns this session
        if ($session->quran_teacher_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'غير مسموح'], 403);
        }

        try {
            // If it's an individual session, restore the session count
            if ($session->session_type === 'individual' && $session->quran_subscription_id) {
                $session->subscription->increment('sessions_remaining');
            }

            $session->delete();

            return response()->json([
                'success' => true,
                'message' => 'تم حذف الجلسة بنجاح'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في حذف الجلسة: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get teacher's individual circles with template sessions
     */
    private function getTeacherIndividualCircles()
    {
        $user = Auth::user();
        
        // Check if user is a Quran teacher
        if (!$user->isQuranTeacher()) {
            return collect();
        }
        
        return QuranIndividualCircle::where('quran_teacher_id', $user->id)
            ->where('status', 'active')
            ->with([
                'student', 
                'scheduledSessions' => function($query) {
                    $query->whereIn('status', ['scheduled', 'in_progress'])->orderBy('scheduled_at');
                },
                'subscription.package' => function($query) {
                    $query->select('id', 'sessions_per_month', 'session_duration_minutes');
                }
            ])
            ->get();
    }

        /**
     * Get teacher's group circles
     */
    private function getTeacherGroupCircles()
    {
        $user = Auth::user();
        
        // Check if user is a Quran teacher
        if (!$user->isQuranTeacher()) {
            return collect();
        }
        
        return QuranCircle::select([
                'id', 'name_ar', 'circle_code', 'enrolled_students', 
                'session_duration_minutes', 'monthly_sessions_count', 'status'
            ])
            ->where('quran_teacher_id', $user->id)
            ->whereIn('status', ['active', 'inactive'])
            ->get();
    }

    /**
     * Get event color based on status
     */
    private function getEventColor(string $status): string
    {
        return match ($status) {
            'scheduled' => '#3B82F6', // Blue
            'ongoing' => '#F59E0B',   // Yellow
            'completed' => '#10B981', // Green
            'cancelled' => '#EF4444', // Red
            default => '#6B7280',     // Gray
        };
    }

    /**
     * Get current academy
     */
    private function getCurrentAcademy(): Academy
    {
        return AcademyContextService::getCurrentAcademy();
    }

    /**
     * Helper methods for date ranges
     */
    private function getStartDate(Carbon $date, string $view): Carbon
    {
        return match ($view) {
            'day' => $date->copy()->startOfDay(),
            'week' => $date->copy()->startOfWeek(),
            'month' => $date->copy()->startOfMonth()->startOfWeek(),
            default => $date->copy()->startOfMonth(),
        };
    }

    private function getEndDate(Carbon $date, string $view): Carbon
    {
        return match ($view) {
            'day' => $date->copy()->endOfDay(),
            'week' => $date->copy()->endOfWeek(),
            'month' => $date->copy()->endOfMonth()->endOfWeek(),
            default => $date->copy()->endOfMonth(),
        };
    }

    /**
     * Bulk update sessions (for batch operations)
     */
    public function bulkUpdate(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        $request->validate([
            'changes' => 'required|array',
            'changes.*.type' => 'required|in:schedule,move,resize,update,delete',
            'changes.*.sessionId' => 'required',
        ]);

        try {
            $processedChanges = [];
            
            foreach ($request->changes as $change) {
                $result = $this->processChange($user, $change);
                if ($result['success']) {
                    $processedChanges[] = $result;
                } else {
                    // If any change fails, return error
                    return response()->json([
                        'success' => false,
                        'message' => $result['message']
                    ], 400);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'تم حفظ جميع التغييرات بنجاح',
                'processed' => count($processedChanges)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في معالجة التغييرات: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process individual change
     */
    private function processChange($user, array $change): array
    {
        try {
            switch ($change['type']) {
                case 'schedule':
                    return $this->scheduleTemplateSession($user, $change);
                    
                case 'move':
                    return $this->moveSession($user, $change);
                    
                case 'resize':
                    return $this->resizeSession($user, $change);
                    
                case 'update':
                    return $this->updateSessionDetails($user, $change);
                    
                case 'delete':
                    return $this->deleteSessionBulk($user, $change);
                    
                default:
                    return ['success' => false, 'message' => 'نوع تغيير غير مدعوم'];
            }
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'خطأ في معالجة التغيير: ' . $e->getMessage()];
        }
    }

    /**
     * Schedule a template session
     */
    private function scheduleTemplateSession($user, array $change): array
    {
        $sessionId = str_replace('temp-', '', $change['sessionId']);
        $session = QuranSession::find($sessionId);
        
        if (!$session || $session->quran_teacher_id !== $user->id) {
            return ['success' => false, 'message' => 'الجلسة غير موجودة أو غير مخولة'];
        }

        $session->update([
            'scheduled_at' => Carbon::parse($change['date']),
            'is_scheduled' => true,
            'teacher_scheduled_at' => now(),
            'scheduled_by' => $user->id,
            'status' => 'scheduled',
            'duration_minutes' => $change['duration'] ?? $session->duration_minutes
        ]);

        return ['success' => true, 'message' => 'تم جدولة الجلسة بنجاح'];
    }

    /**
     * Move a session to different date/time
     */
    private function moveSession($user, array $change): array
    {
        $sessionId = str_replace('temp-', '', $change['sessionId']);
        $session = QuranSession::find($sessionId);
        if (!$session || $session->quran_teacher_id !== $user->id) {
            return ['success' => false, 'message' => 'الجلسة غير موجودة أو غير مخولة'];
        }

        $session->update([
            'scheduled_at' => Carbon::parse($change['newDate'])
        ]);

        return ['success' => true, 'message' => 'تم نقل الجلسة بنجاح'];
    }

    /**
     * Resize a session (change duration)
     */
    private function resizeSession($user, array $change): array
    {
        $sessionId = str_replace('temp-', '', $change['sessionId']);
        $session = QuranSession::find($sessionId);
        if (!$session || $session->quran_teacher_id !== $user->id) {
            return ['success' => false, 'message' => 'الجلسة غير موجودة أو غير مخولة'];
        }

        $newDuration = Carbon::parse($change['newEnd'])->diffInMinutes(Carbon::parse($session->scheduled_at));
        
        $session->update([
            'duration_minutes' => $newDuration
        ]);

        return ['success' => true, 'message' => 'تم تغيير مدة الجلسة بنجاح'];
    }

    /**
     * Update session details
     */
    private function updateSessionDetails($user, array $change): array
    {
        $sessionId = str_replace('temp-', '', $change['sessionId']);
        $session = QuranSession::find($sessionId);
        if (!$session || $session->quran_teacher_id !== $user->id) {
            return ['success' => false, 'message' => 'الجلسة غير موجودة أو غير مخولة'];
        }

        $updates = [];
        
        if (isset($change['updates']['title'])) {
            $updates['title'] = $change['updates']['title'];
        }
        
        if (isset($change['updates']['description'])) {
            $updates['description'] = $change['updates']['description'];
        }
        
        if (isset($change['updates']['date'])) {
            $updates['scheduled_at'] = Carbon::parse($change['updates']['date']);
        }
        
        if (isset($change['updates']['duration'])) {
            $updates['duration_minutes'] = intval($change['updates']['duration']);
        }

        $session->update($updates);

        return ['success' => true, 'message' => 'تم تحديث تفاصيل الجلسة بنجاح'];
    }

    /**
     * Delete session (bulk operation)
     */
    private function deleteSessionBulk($user, array $change): array
    {
        $sessionId = str_replace('temp-', '', $change['sessionId']);
        $session = QuranSession::find($sessionId);
        if (!$session || $session->quran_teacher_id !== $user->id) {
            return ['success' => false, 'message' => 'الجلسة غير موجودة أو غير مخولة'];
        }

        // If it's an individual session, restore the session count
        if ($session->session_type === 'individual' && $session->individualCircle) {
            $session->individualCircle->increment('sessions_remaining');
        }

        $session->delete();

        return ['success' => true, 'message' => 'تم حذف الجلسة بنجاح'];
    }
}