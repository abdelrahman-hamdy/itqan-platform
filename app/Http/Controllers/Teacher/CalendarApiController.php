<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\QuranCircle;
use App\Models\QuranIndividualCircle;
use App\Models\QuranSession;
use App\Models\QuranCircleSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class CalendarApiController extends Controller
{
    /**
     * Get all circles for the authenticated teacher
     */
    public function getCircles()
    {
        try {
            $teacherId = Auth::id();
            $user = Auth::user();
            
            Log::info('API getCircles called', ['teacher_id' => $teacherId]);
            
            // Validate user and academy
            if (!$user || !$user->academy_id) {
                Log::warning('Invalid user or missing academy', ['teacher_id' => $teacherId]);
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح لك بالوصول'
                ], 403);
            }
            
            // Get group circles with safe data mapping
            $groupCircles = $this->getGroupCirclesForTeacher($teacherId, $user->academy_id);
            
            // Get individual circles with safe data mapping
            $individualCircles = $this->getIndividualCirclesForTeacher($teacherId);
            
            return response()->json([
                'success' => true,
                'groupCircles' => $groupCircles,
                'individualCircles' => $individualCircles
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error loading circles: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'teacher_id' => $teacherId ?? null,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'خطأ في تحميل الحلقات. يرجى المحاولة مرة أخرى.'
            ], 500);
        }
    }
    
    /**
     * Get group circles for teacher with safe data handling
     */
    private function getGroupCirclesForTeacher($teacherId, $academyId)
    {
        try {
            $circles = QuranCircle::where('quran_teacher_id', $teacherId)
                ->where('academy_id', $academyId)
                ->with('schedule')
                ->get();
            
            return $circles->map(function ($circle) {
                try {
                    // Safe name retrieval with fallback
                    $name = $this->getCircleName($circle);
                    
                    // Safe schedule data
                    $schedule = $circle->schedule;
                    $isScheduled = $circle->schedule_configured && $schedule;
                    
                    return [
                        'id' => $circle->id,
                        'name' => $name,
                        'schedule_days' => $schedule?->days,
                        'schedule_time' => $schedule?->time,
                        'session_duration_minutes' => $circle->session_duration_minutes ?? 60,
                        'monthly_sessions_count' => $circle->monthly_sessions_count ?? 0,
                        'is_scheduled' => $isScheduled,
                        'status' => $circle->status ?? 'planning'
                    ];
                } catch (\Exception $e) {
                    Log::warning('Error mapping group circle', [
                        'circle_id' => $circle->id ?? 'unknown',
                        'error' => $e->getMessage()
                    ]);
                    
                    return [
                        'id' => $circle->id ?? 0,
                        'name' => 'حلقة غير محددة',
                        'schedule_days' => null,
                        'schedule_time' => null,
                        'session_duration_minutes' => 60,
                        'monthly_sessions_count' => 0,
                        'is_scheduled' => false,
                        'status' => 'planning'
                    ];
                }
            })->filter(); // Remove any null results
            
        } catch (\Exception $e) {
            Log::error('Error getting group circles', [
                'teacher_id' => $teacherId,
                'academy_id' => $academyId,
                'error' => $e->getMessage()
            ]);
            
            return collect([]); // Return empty collection on error
        }
    }
    
    /**
     * Get individual circles for teacher with safe data handling
     */
    private function getIndividualCirclesForTeacher($teacherId)
    {
        try {
            $circles = QuranIndividualCircle::where('quran_teacher_id', $teacherId)
                ->with(['student', 'subscription'])
                ->get();
            
            return $circles->map(function ($circle) {
                try {
                    // Safe student name retrieval
                    $studentName = $this->getStudentName($circle);
                    
                    // Safe subscription data
                    $subscription = $circle->subscription;
                    
                    return [
                        'id' => $circle->id,
                        'student_name' => $studentName,
                        'total_sessions' => $circle->total_sessions ?? 0,
                        'sessions_scheduled' => $circle->sessions_scheduled ?? 0,
                        'sessions_completed' => $circle->sessions_completed ?? 0,
                        'subscription_start' => $subscription?->starts_at?->format('Y-m-d'),
                        'subscription_end' => $subscription?->expires_at?->format('Y-m-d'),
                        'status' => $circle->status ?? 'pending'
                    ];
                } catch (\Exception $e) {
                    Log::warning('Error mapping individual circle', [
                        'circle_id' => $circle->id ?? 'unknown',
                        'error' => $e->getMessage()
                    ]);
                    
                    return [
                        'id' => $circle->id ?? 0,
                        'student_name' => 'طالب غير محدد',
                        'total_sessions' => 0,
                        'sessions_scheduled' => 0,
                        'sessions_completed' => 0,
                        'subscription_start' => null,
                        'subscription_end' => null,
                        'status' => 'pending'
                    ];
                }
            })->filter(); // Remove any null results
            
        } catch (\Exception $e) {
            Log::error('Error getting individual circles', [
                'teacher_id' => $teacherId,
                'error' => $e->getMessage()
            ]);
            
            return collect([]); // Return empty collection on error
        }
    }
    
    /**
     * Safely get circle name with fallbacks
     */
    private function getCircleName($circle)
    {
        try {
            // Try to use the accessor if available
            if (method_exists($circle, 'getNameAttribute')) {
                return $circle->name;
            }
            
            // Fallback to direct attributes
            $locale = app()->getLocale();
            if ($locale === 'ar') {
                return $circle->name_ar ?? $circle->name_en ?? 'حلقة غير محددة';
            }
            return $circle->name_en ?? $circle->name_ar ?? 'Unnamed Circle';
            
        } catch (\Exception $e) {
            Log::warning('Error getting circle name', [
                'circle_id' => $circle->id ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            
            return 'حلقة غير محددة';
        }
    }
    
    /**
     * Safely get student name with fallbacks
     */
    private function getStudentName($circle)
    {
        try {
            if (!$circle->student) {
                return 'طالب غير محدد';
            }
            
            $firstName = $circle->student->first_name ?? '';
            $lastName = $circle->student->last_name ?? '';
            $fullName = trim($firstName . ' ' . $lastName);
            
            return $fullName ?: 'طالب غير محدد';
            
        } catch (\Exception $e) {
            Log::warning('Error getting student name', [
                'circle_id' => $circle->id ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            
            return 'طالب غير محدد';
        }
    }

    /**
     * Handle bulk session scheduling
     */
    public function bulkSchedule(Request $request)
    {
        $request->validate([
            'circle_id' => 'required|integer',
            'circle_type' => 'required|in:group,individual',
            'sessions' => 'required|array',
            'sessions.*.date' => 'required|date',
            'sessions.*.time' => 'required|date_format:H:i',
            'sessions.*.duration' => 'required|integer|min:15|max:180'
        ]);

        try {
            $teacherId = Auth::id();
            $circleId = $request->circle_id;
            $circleType = $request->circle_type;
            $sessions = $request->sessions;

            DB::beginTransaction();

            // Verify circle ownership
            if ($circleType === 'group') {
                $circle = QuranCircle::where('id', $circleId)
                    ->where('quran_teacher_id', $teacherId)
                    ->where('academy_id', Auth::user()->academy_id)
                    ->first();
            } else {
                $circle = QuranIndividualCircle::where('id', $circleId)
                    ->where('quran_teacher_id', $teacherId)
                    ->first();
            }

            if (!$circle) {
                return response()->json([
                    'success' => false,
                    'message' => 'الحلقة غير موجودة أو غير مصرح لك بالوصول إليها'
                ], 404);
            }

            $createdSessions = [];
            foreach ($sessions as $sessionData) {
                $dateTime = Carbon::parse($sessionData['date'] . ' ' . $sessionData['time']);
                
                $session = QuranSession::create([
                    'quran_teacher_id' => $teacherId,
                    'academy_id' => Auth::user()->academy_id,
                    'circle_id' => $circleType === 'group' ? $circleId : null,
                    'individual_circle_id' => $circleType === 'individual' ? $circleId : null,
                    'scheduled_at' => $dateTime,
                    'duration_minutes' => $sessionData['duration'],
                    'status' => 'scheduled',
                    'session_type' => $circleType,
                ]);
                
                $createdSessions[] = $session;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم جدولة الجلسات بنجاح',
                'sessions_count' => count($createdSessions)
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in bulk scheduling: ' . $e->getMessage(), [
                'teacher_id' => $teacherId ?? null,
                'circle_id' => $circleId ?? null,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطأ في جدولة الجلسات: ' . $e->getMessage()
            ], 500);
        }
    }
}