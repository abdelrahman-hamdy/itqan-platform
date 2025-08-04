<?php

namespace App\Http\Controllers;

use App\Models\QuranTrialRequest;
use App\Models\QuranSubscription;
use App\Models\QuranSession;
use App\Models\QuranTeacherProfile;
use App\Models\Academy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TeacherScheduleController extends Controller
{
    /**
     * Show teacher schedule management dashboard
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $academy = $user->academy;

        if (!$user->isQuranTeacher()) {
            abort(403, 'Access denied');
        }

        $teacherProfile = $user->quranTeacherProfile;
        
        if (!$teacherProfile) {
            abort(404, 'Teacher profile not found');
        }

        // Get pending trial requests
        $pendingTrialRequests = QuranTrialRequest::where('teacher_id', $teacherProfile->id)
            ->where('academy_id', $academy->id)
            ->whereIn('status', ['pending', 'approved'])
            ->with(['student', 'academy'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Get active subscriptions requiring schedule setup
        $activeSubscriptions = QuranSubscription::where('quran_teacher_id', $teacherProfile->id)
            ->where('academy_id', $academy->id)
            ->where('subscription_status', 'active')
            ->where('payment_status', 'current')
            ->with(['student', 'package'])
            ->get();

        // Get upcoming sessions for the next 7 days
        $upcomingSessions = QuranSession::where('quran_teacher_id', $teacherProfile->id)
            ->where('academy_id', $academy->id)
            ->where('scheduled_at', '>=', now())
            ->where('scheduled_at', '<=', now()->addDays(7))
            ->whereIn('status', ['scheduled', 'in_progress'])
            ->with(['student', 'subscription'])
            ->orderBy('scheduled_at')
            ->get();

        // Get today's sessions
        $todaySessions = QuranSession::where('quran_teacher_id', $teacherProfile->id)
            ->where('academy_id', $academy->id)
            ->whereDate('scheduled_at', today())
            ->with(['student', 'subscription'])
            ->orderBy('scheduled_at')
            ->get();

        return view('teacher.schedule.dashboard', compact(
            'academy',
            'teacherProfile',
            'pendingTrialRequests',
            'activeSubscriptions',
            'upcomingSessions',
            'todaySessions'
        ));
    }

    /**
     * Show trial session scheduling form
     */
    public function showTrialScheduling(Request $request, $trialRequestId)
    {
        $user = Auth::user();
        $academy = $user->academy;
        $teacherProfile = $user->quranTeacherProfile;

        $trialRequest = QuranTrialRequest::where('id', $trialRequestId)
            ->where('teacher_id', $teacherProfile->id)
            ->where('academy_id', $academy->id)
            ->whereIn('status', ['pending', 'approved'])
            ->with(['student', 'academy'])
            ->first();

        if (!$trialRequest) {
            return redirect()->route('teacher.schedule.index')
                ->with('error', 'لم يتم العثور على طلب الجلسة التجريبية');
        }

        return view('teacher.schedule.trial-scheduling', compact(
            'academy',
            'teacherProfile',
            'trialRequest'
        ));
    }

    /**
     * Schedule trial session
     */
    public function scheduleTrialSession(Request $request, $trialRequestId)
    {
        $user = Auth::user();
        $academy = $user->academy;
        $teacherProfile = $user->quranTeacherProfile;

        $validator = Validator::make($request->all(), [
            'scheduled_date' => 'required|date|after_or_equal:today',
            'start_time' => 'required|date_format:H:i',
            'duration' => 'required|integer|min:15|max:120',
            'meeting_link' => 'nullable|url',
            'meeting_password' => 'nullable|string|max:50',
            'notes' => 'nullable|string|max:500',
        ], [
            'scheduled_date.required' => 'تاريخ الجلسة مطلوب',
            'scheduled_date.after_or_equal' => 'يجب أن يكون تاريخ الجلسة اليوم أو لاحقاً',
            'start_time.required' => 'وقت بداية الجلسة مطلوب',
            'duration.required' => 'مدة الجلسة مطلوبة',
            'duration.min' => 'مدة الجلسة يجب أن تكون 15 دقيقة على الأقل',
            'duration.max' => 'مدة الجلسة يجب أن تكون 120 دقيقة كحد أقصى',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::transaction(function() use ($request, $trialRequestId, $teacherProfile, $academy, $user) {
                // Get trial request
                $trialRequest = QuranTrialRequest::where('id', $trialRequestId)
                    ->where('teacher_id', $teacherProfile->id)
                    ->where('academy_id', $academy->id)
                    ->lockForUpdate()
                    ->first();

                if (!$trialRequest || !$trialRequest->canBeScheduled()) {
                    throw new \Exception('لا يمكن جدولة هذا الطلب');
                }

                // Calculate end time
                $scheduledDateTime = Carbon::parse($request->scheduled_date . ' ' . $request->start_time);
                $endDateTime = $scheduledDateTime->copy()->addMinutes($request->duration);

                // Check for scheduling conflicts
                $hasConflict = QuranSession::where('quran_teacher_id', $teacherProfile->id)
                    ->where('academy_id', $academy->id)
                    ->where('scheduled_at', '>=', $scheduledDateTime->subMinutes(15))
                    ->where('scheduled_at', '<=', $endDateTime->addMinutes(15))
                    ->whereIn('status', ['scheduled', 'in_progress'])
                    ->exists();

                if ($hasConflict) {
                    throw new \Exception('لديك جلسة أخرى في نفس الوقت. يرجى اختيار وقت آخر.');
                }

                // Create the trial session
                $session = QuranSession::create([
                    'academy_id' => $academy->id,
                    'quran_teacher_id' => $teacherProfile->id,
                    'student_id' => $trialRequest->student_id,
                    'session_code' => $this->generateSessionCode($academy->id),
                    'session_type' => 'trial',
                    'status' => 'scheduled',
                    'title' => 'جلسة تجريبية - ' . $trialRequest->student_name,
                    'description' => 'جلسة تجريبية لتقييم مستوى الطالب وتحديد خطة التعلم المناسبة',
                    'scheduled_at' => $scheduledDateTime,
                    'duration_minutes' => $request->duration,
                    'meeting_link' => $request->meeting_link,
                    'meeting_password' => $request->meeting_password,
                    'session_notes' => $request->notes,
                    'current_level' => $trialRequest->current_level,
                    'created_by' => $user->id,
                ]);

                // Update trial request
                $trialRequest->schedule(
                    $scheduledDateTime,
                    $request->meeting_link,
                    $request->meeting_password
                );

                // Link session to trial request
                $trialRequest->update(['trial_session_id' => $session->id]);
            });

            return redirect()->route('teacher.schedule.index')
                ->with('success', 'تم جدولة الجلسة التجريبية بنجاح! سيتم إرسال تنبيه للطالب');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Show subscription sessions setup
     */
    public function showSubscriptionScheduling(Request $request, $subscriptionId)
    {
        $user = Auth::user();
        $academy = $user->academy;
        $teacherProfile = $user->quranTeacherProfile;

        $subscription = QuranSubscription::where('id', $subscriptionId)
            ->where('quran_teacher_id', $teacherProfile->id)
            ->where('academy_id', $academy->id)
            ->where('subscription_status', 'active')
            ->where('payment_status', 'current')
            ->with(['student', 'package'])
            ->first();

        if (!$subscription) {
            return redirect()->route('teacher.schedule.index')
                ->with('error', 'لم يتم العثور على الاشتراك');
        }

        // Get existing sessions for this subscription
        $existingSessions = QuranSession::where('quran_subscription_id', $subscription->id)
            ->where('academy_id', $academy->id)
            ->where('scheduled_at', '>=', now())
            ->orderBy('scheduled_at')
            ->get();

        return view('teacher.schedule.subscription-scheduling', compact(
            'academy',
            'teacherProfile',
            'subscription',
            'existingSessions'
        ));
    }

    /**
     * Setup recurring sessions for subscription
     */
    public function setupSubscriptionSessions(Request $request, $subscriptionId)
    {
        $user = Auth::user();
        $academy = $user->academy;
        $teacherProfile = $user->quranTeacherProfile;

        $validator = Validator::make($request->all(), [
            'sessions' => 'required|array|min:1',
            'sessions.*.day' => 'required|in:sunday,monday,tuesday,wednesday,thursday,friday,saturday',
            'sessions.*.time' => 'required|date_format:H:i',
            'sessions.*.duration' => 'required|integer|min:30|max:120',
            'start_date' => 'required|date|after_or_equal:today',
            'meeting_link' => 'nullable|url',
            'meeting_password' => 'nullable|string|max:50',
        ], [
            'sessions.required' => 'يجب إضافة جلسة واحدة على الأقل',
            'start_date.required' => 'تاريخ بداية الجلسات مطلوب',
            'start_date.after_or_equal' => 'يجب أن يكون تاريخ البداية اليوم أو لاحقاً',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::transaction(function() use ($request, $subscriptionId, $teacherProfile, $academy, $user) {
                $subscription = QuranSubscription::where('id', $subscriptionId)
                    ->where('quran_teacher_id', $teacherProfile->id)
                    ->where('academy_id', $academy->id)
                    ->lockForUpdate()
                    ->first();

                if (!$subscription) {
                    throw new \Exception('لم يتم العثور على الاشتراك');
                }

                $startDate = Carbon::parse($request->start_date);
                $endDate = $subscription->expires_at;
                $sessionsToCreate = [];

                // Generate sessions based on the recurring schedule
                foreach ($request->sessions as $sessionTemplate) {
                    $currentDate = $startDate->copy();
                    
                    // Find the first occurrence of the specified day
                    $dayOfWeek = $this->getDayOfWeekNumber($sessionTemplate['day']);
                    while ($currentDate->dayOfWeek !== $dayOfWeek) {
                        $currentDate->addDay();
                    }

                    // Generate sessions until subscription expires
                    while ($currentDate->lte($endDate) && count($sessionsToCreate) < $subscription->sessions_remaining) {
                        $sessionDateTime = $currentDate->copy()->setTimeFromTimeString($sessionTemplate['time']);
                        
                        // Check for conflicts
                        $hasConflict = QuranSession::where('quran_teacher_id', $teacherProfile->id)
                            ->where('academy_id', $academy->id)
                            ->where('scheduled_at', '>=', $sessionDateTime->copy()->subMinutes(15))
                            ->where('scheduled_at', '<=', $sessionDateTime->copy()->addMinutes($sessionTemplate['duration'] + 15))
                            ->whereIn('status', ['scheduled', 'in_progress'])
                            ->exists();

                        if (!$hasConflict) {
                            $sessionsToCreate[] = [
                                'academy_id' => $academy->id,
                                'quran_teacher_id' => $teacherProfile->id,
                                'quran_subscription_id' => $subscription->id,
                                'student_id' => $subscription->student_id,
                                'session_code' => $this->generateSessionCode($academy->id),
                                'session_type' => 'regular',
                                'status' => 'scheduled',
                                'title' => 'جلسة قرآن - ' . $subscription->student->name,
                                'description' => 'جلسة منتظمة لتعلم وحفظ القرآن الكريم',
                                'scheduled_at' => $sessionDateTime,
                                'duration_minutes' => $sessionTemplate['duration'],
                                'meeting_link' => $request->meeting_link,
                                'meeting_password' => $request->meeting_password,
                                'created_by' => $user->id,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];
                        }

                        $currentDate->addWeek();
                    }
                }

                // Create all sessions
                if (!empty($sessionsToCreate)) {
                    QuranSession::insert($sessionsToCreate);
                }
            });

            return redirect()->route('teacher.schedule.index')
                ->with('success', 'تم إعداد جدول الجلسات بنجاح! تم إنشاء ' . count($request->sessions) . ' جلسة منتظمة');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Update teacher availability
     */
    public function updateAvailability(Request $request)
    {
        $user = Auth::user();
        $teacherProfile = $user->quranTeacherProfile;

        $validator = Validator::make($request->all(), [
            'available_days' => 'required|array|min:1',
            'available_days.*' => 'in:sunday,monday,tuesday,wednesday,thursday,friday,saturday',
            'available_time_start' => 'required|date_format:H:i',
            'available_time_end' => 'required|date_format:H:i|after:available_time_start',
        ], [
            'available_days.required' => 'يجب اختيار يوم واحد على الأقل',
            'available_time_start.required' => 'وقت البداية مطلوب',
            'available_time_end.required' => 'وقت النهاية مطلوب',
            'available_time_end.after' => 'وقت النهاية يجب أن يكون بعد وقت البداية',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $teacherProfile->update([
                'available_days' => $request->available_days,
                'available_time_start' => $request->available_time_start,
                'available_time_end' => $request->available_time_end,
            ]);

            return redirect()->back()
                ->with('success', 'تم تحديث أوقات التوفر بنجاح');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'حدث خطأ أثناء تحديث أوقات التوفر')
                ->withInput();
        }
    }

    /**
     * Generate unique session code
     */
    private function generateSessionCode($academyId): string
    {
        $academyId = $academyId ?: 1;
        $prefix = 'QS-' . str_pad($academyId, 2, '0', STR_PAD_LEFT) . '-';
        $timestamp = now()->format('ymd');
        $random = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        return $prefix . $timestamp . '-' . $random;
    }

    /**
     * Get day of week number for Carbon
     */
    private function getDayOfWeekNumber($day): int
    {
        $days = [
            'sunday' => 0,
            'monday' => 1,
            'tuesday' => 2,
            'wednesday' => 3,
            'thursday' => 4,
            'friday' => 5,
            'saturday' => 6,
        ];

        return $days[$day] ?? 0;
    }
}