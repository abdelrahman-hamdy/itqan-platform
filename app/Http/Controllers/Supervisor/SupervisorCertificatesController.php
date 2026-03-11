<?php

namespace App\Http\Controllers\Supervisor;

use App\Enums\CertificateType;
use App\Models\Certificate;
use App\Models\User;
use App\Services\AcademyContextService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SupervisorCertificatesController extends BaseSupervisorWebController
{
    public function index(Request $request, $subdomain = null): View
    {
        $allTeacherIds = $this->getAllAssignedTeacherIds();

        $query = Certificate::whereIn('teacher_id', $allTeacherIds)
            ->with(['student', 'teacher', 'certificateable']);

        if ($request->filled('teacher_id')) {
            $query->where('teacher_id', $request->teacher_id);
        }

        if ($request->filled('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        if ($request->filled('certificate_type')) {
            $query->where('certificate_type', $request->certificate_type);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('issued_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('issued_at', '<=', $request->date_to);
        }

        $certificates = $query->latest('issued_at')->paginate(15)->withQueryString();

        $totalCertificates = Certificate::whereIn('teacher_id', $allTeacherIds)->count();

        $teachers = User::whereIn('id', $allTeacherIds)->get()->map(fn ($u) => [
            'id' => $u->id,
            'name' => $u->name,
        ])->toArray();

        $students = Certificate::whereIn('teacher_id', $allTeacherIds)
            ->whereNotNull('student_id')
            ->with('student:id,name')
            ->get()
            ->pluck('student.name', 'student_id')
            ->filter()
            ->unique()
            ->sort()
            ->toArray();

        return view('supervisor.certificates.index', compact('certificates', 'teachers', 'totalCertificates', 'students'));
    }

    public function show(Request $request, $subdomain = null, $certificate = null): View
    {
        $allTeacherIds = $this->getAllAssignedTeacherIds();

        $certificate = Certificate::whereIn('teacher_id', $allTeacherIds)
            ->with(['student', 'teacher', 'certificateable', 'issuedBy'])
            ->findOrFail($certificate);

        return view('supervisor.certificates.show', compact('certificate'));
    }

    public function issue(Request $request, $subdomain = null): View
    {
        if (! $this->isAdminUser()) {
            abort(403);
        }

        $allTeacherIds = $this->getAllAssignedTeacherIds();

        $teachers = User::whereIn('id', $allTeacherIds)->get()->map(fn ($u) => [
            'id' => $u->id,
            'name' => $u->name,
        ])->toArray();

        $students = User::where('user_type', 'student')
            ->select('id', 'name')
            ->orderBy('name')
            ->get()
            ->map(fn ($u) => ['id' => $u->id, 'name' => $u->name])
            ->toArray();

        $certificateTypes = CertificateType::options();

        return view('supervisor.certificates.issue', compact('teachers', 'students', 'certificateTypes'));
    }

    public function store(Request $request, $subdomain = null)
    {
        if (! $this->isAdminUser()) {
            abort(403);
        }

        $validated = $request->validate([
            'student_id' => 'required|exists:users,id',
            'teacher_id' => 'required|exists:users,id',
            'certificate_type' => 'required|in:' . implode(',', CertificateType::values()),
            'certificate_text' => 'nullable|string|max:1000',
        ]);

        $academy = AcademyContextService::getCurrentAcademy();

        Certificate::create([
            'academy_id' => $academy->id,
            'student_id' => $validated['student_id'],
            'teacher_id' => $validated['teacher_id'],
            'certificate_type' => $validated['certificate_type'],
            'certificate_text' => $validated['certificate_text'] ?? null,
            'issued_at' => now(),
            'issued_by' => Auth::id(),
            'is_manual' => true,
            'certificate_number' => 'CERT-' . strtoupper(substr(md5(uniqid()), 0, 8)),
        ]);

        return redirect()
            ->route('manage.certificates.index', ['subdomain' => $subdomain ?? request()->route('subdomain')])
            ->with('success', __('supervisor.certificates.issue_success'));
    }
}
