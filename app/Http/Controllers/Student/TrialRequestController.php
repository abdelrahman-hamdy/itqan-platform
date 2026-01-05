<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\QuranTrialRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TrialRequestController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:student');
    }

    /**
     * Display trial request details for the student
     */
    public function show(Request $request, $subdomain, QuranTrialRequest $trialRequest): View
    {
        $user = Auth::user();

        // Authorization: student can only view their own trial requests
        if ($trialRequest->student_id !== $user->id) {
            abort(403, __('common.unauthorized'));
        }

        $trialRequest->load([
            'teacher.user',
            'trialSession.meeting',
            'academy',
        ]);

        return view('student.trial-request-detail', compact('trialRequest'));
    }
}
