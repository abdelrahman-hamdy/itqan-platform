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
            ->with(['student:id,first_name,last_name,name', 'certificateable']);

        if ($user->isQuranTeacher()) {
            // Certificates where teacher_id matches, or certificateable is a circle/individual the teacher owns
            $query->where(function ($q) use ($user) {
                $q->where('teacher_id', $user->id)
                    ->orWhere(function ($q2) use ($user) {
                        $q2->where('certificateable_type', \App\Models\QuranCircle::class)
                            ->whereHas('certificateable', fn ($sub) => $sub->where('quran_teacher_id', $user->id));
                    })
                    ->orWhere(function ($q2) use ($user) {
                        $q2->where('certificateable_type', \App\Models\QuranIndividualCircle::class)
                            ->whereHas('certificateable', fn ($sub) => $sub->where('quran_teacher_id', $user->id));
                    });
            });
        } elseif ($user->isAcademicTeacher()) {
            $profileId = $user->academicTeacherProfile?->id;

            if (! $profileId) {
                abort(403);
            }

            $query->where(function ($q) use ($user, $profileId) {
                $q->where('teacher_id', $user->id)
                    ->orWhere(function ($q2) use ($profileId) {
                        $q2->where('certificateable_type', \App\Models\AcademicIndividualLesson::class)
                            ->whereHas('certificateable', fn ($sub) => $sub->where('academic_teacher_id', $profileId));
                    })
                    ->orWhere(function ($q2) use ($profileId) {
                        $q2->where('certificateable_type', \App\Models\InteractiveCourse::class)
                            ->whereHas('certificateable', fn ($sub) => $sub->where('assigned_teacher_id', $profileId));
                    });
            });
        }

        // Filter by certificate type
        if ($request->filled('certificate_type')) {
            $query->where('certificate_type', $request->certificate_type);
        }

        $certificates = $query->latest('issued_at')->paginate(15)->withQueryString();

        $totalCertificates = $certificates->total();

        return view('teacher.certificates.index', compact(
            'certificates',
            'totalCertificates',
        ));
    }
}
