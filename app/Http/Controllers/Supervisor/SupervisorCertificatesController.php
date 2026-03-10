<?php

namespace App\Http\Controllers\Supervisor;

use App\Models\Certificate;
use App\Models\User;
use Illuminate\Http\Request;
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
}
