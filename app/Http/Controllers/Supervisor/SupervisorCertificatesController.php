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

        if ($request->teacher_id) {
            $query->where('teacher_id', $request->teacher_id);
        }

        if ($request->certificate_type) {
            $query->where('certificate_type', $request->certificate_type);
        }

        if ($request->date_from) {
            $query->whereDate('issued_at', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $query->whereDate('issued_at', '<=', $request->date_to);
        }

        $certificates = $query->latest('issued_at')->paginate(15)->withQueryString();

        $totalCertificates = Certificate::whereIn('teacher_id', $allTeacherIds)->count();

        $teachers = User::whereIn('id', $allTeacherIds)->get()->map(fn ($u) => [
            'id' => $u->id,
            'name' => $u->name,
        ])->toArray();

        return view('supervisor.certificates.index', compact('certificates', 'teachers', 'totalCertificates'));
    }
}
