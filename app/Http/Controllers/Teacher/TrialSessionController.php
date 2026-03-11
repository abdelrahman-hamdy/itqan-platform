<?php

namespace App\Http\Controllers\Teacher;

use App\Enums\TrialRequestStatus;
use App\Http\Controllers\Controller;
use App\Models\QuranTrialRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TrialSessionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:quran_teacher');
    }

    /**
     * Display trial sessions for the teacher
     */
    public function index(Request $request, $subdomain = null): View
    {
        $user = Auth::user();
        $teacherProfile = $user->quranTeacherProfile;

        if (! $teacherProfile) {
            abort(404, 'Teacher profile not found');
        }

        $baseQuery = QuranTrialRequest::where('teacher_id', $teacherProfile->id)
            ->where('academy_id', $user->academy_id);

        $stats = [
            'total' => (clone $baseQuery)->count(),
            'pending' => (clone $baseQuery)->where('status', 'pending')->count(),
            'scheduled' => (clone $baseQuery)->where('status', 'scheduled')->count(),
            'completed' => (clone $baseQuery)->where('status', 'completed')->count(),
        ];

        $query = clone $baseQuery;
        $query->with(['student', 'academy', 'trialSession.meeting']);

        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->whereHas('student', function ($sq) use ($request) {
                    $sq->where('name', 'like', '%' . $request->search . '%');
                })->orWhere('student_name', 'like', '%' . $request->search . '%');
            });
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $trialRequests = $query->latest()->paginate(15)->withQueryString();

        return view('teacher.trial-sessions.index', compact('trialRequests', 'stats'));
    }

    /**
     * Display trial request details for the teacher
     */
    public function show(Request $request, $subdomain, QuranTrialRequest $trialRequest): View
    {
        $user = Auth::user();
        $teacherProfile = $user->quranTeacherProfile;

        if (! $teacherProfile || $trialRequest->teacher_id !== $teacherProfile->id) {
            abort(403, __('common.unauthorized'));
        }

        $trialRequest->load([
            'student.studentProfile',
            'trialSession.meeting',
            'trialSession.attendances',
            'academy',
        ]);

        return view('teacher.trial-sessions.show', compact('trialRequest'));
    }

    /**
     * Save evaluation for a trial request
     */
    public function evaluate(Request $request, $subdomain, QuranTrialRequest $trialRequest): RedirectResponse
    {
        $user = Auth::user();
        $teacherProfile = $user->quranTeacherProfile;

        if (! $teacherProfile || $trialRequest->teacher_id !== $teacherProfile->id) {
            abort(403, __('common.unauthorized'));
        }

        $validated = $request->validate([
            'rating' => 'nullable|integer|min:1|max:10',
            'feedback' => 'nullable|string|max:2000',
        ]);

        // Check if teacher wants to complete the session
        $shouldComplete = $request->has('complete') && $trialRequest->status === TrialRequestStatus::SCHEDULED;

        if ($shouldComplete) {
            // Complete the trial request with rating and feedback
            $trialRequest->complete($validated['rating'] ?? null, $validated['feedback'] ?? null);
            $message = __('teacher.trial_sessions.session_completed');
        } else {
            // Just save the evaluation without changing status
            $trialRequest->update([
                'rating' => $validated['rating'] ?? $trialRequest->rating,
                'feedback' => $validated['feedback'] ?? $trialRequest->feedback,
            ]);
            $message = __('teacher.trial_sessions.evaluation_saved');
        }

        return redirect()->route('teacher.trial-sessions.show', [
            'subdomain' => $subdomain,
            'trialRequest' => $trialRequest->id,
        ])->with('success', $message);
    }
}
