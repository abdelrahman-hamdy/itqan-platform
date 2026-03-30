<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CertificateListController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of certificates for students taught by the authenticated teacher.
     */
    public function index(Request $request, $subdomain = null): View
    {
        $user = Auth::user();

        $query = Certificate::query()
            ->with(['student', 'certificateable']);

        if ($user->isQuranTeacher()) {
            // Certificates where teacher_id matches, or certificateable is a circle/individual the teacher owns
            $query->where(function ($q) use ($user) {
                $q->where('teacher_id', $user->id)
                    ->orWhereHasMorph('certificateable', [\App\Models\QuranCircle::class], function ($sub) use ($user) {
                        $sub->where('quran_teacher_id', $user->id);
                    })
                    ->orWhereHasMorph('certificateable', [\App\Models\QuranIndividualCircle::class], function ($sub) use ($user) {
                        $sub->where('quran_teacher_id', $user->id);
                    })
                    ->orWhereHasMorph('certificateable', [\App\Models\QuranSubscription::class], function ($sub) use ($user) {
                        $sub->where('quran_teacher_id', $user->id);
                    });
            });
        } elseif ($user->isAcademicTeacher()) {
            $profileId = $user->academicTeacherProfile?->id;

            if (! $profileId) {
                abort(403);
            }

            $query->where(function ($q) use ($user, $profileId) {
                $q->where('teacher_id', $user->id)
                    ->orWhereHasMorph('certificateable', [\App\Models\AcademicIndividualLesson::class], function ($sub) use ($profileId) {
                        $sub->where('academic_teacher_id', $profileId);
                    })
                    ->orWhereHasMorph('certificateable', [\App\Models\InteractiveCourse::class], function ($sub) use ($profileId) {
                        $sub->where('assigned_teacher_id', $profileId);
                    })
                    ->orWhereHasMorph('certificateable', [\App\Models\AcademicSubscription::class], function ($sub) use ($profileId) {
                        $sub->whereHas('lesson', fn ($l) => $l->where('academic_teacher_id', $profileId));
                    });
            });
        }

        // Get students list for filter dropdown (before applying filters)
        $students = (clone $query)->with('student')
            ->get()
            ->pluck('student.name', 'student_id')
            ->filter()
            ->unique()
            ->sort()
            ->toArray();

        // Apply filters
        if ($request->filled('certificate_type')) {
            $query->where('certificate_type', $request->certificate_type);
        }

        if ($request->filled('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('issued_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('issued_at', '<=', $request->date_to);
        }

        $certificates = $query->latest('issued_at')->paginate(15)->withQueryString();

        $totalCertificates = $certificates->total();

        return view('teacher.certificates.index', compact(
            'certificates',
            'totalCertificates',
            'students',
        ));
    }
}
