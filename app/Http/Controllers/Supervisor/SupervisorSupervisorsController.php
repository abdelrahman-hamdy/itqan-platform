<?php

namespace App\Http\Controllers\Supervisor;

use App\Models\SupervisorProfile;
use App\Models\SupervisorResponsibility;
use App\Models\User;
use App\Services\AcademyContextService;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password as PasswordRules;
use Illuminate\View\View;

class SupervisorSupervisorsController extends BaseSupervisorWebController
{
    public function index(Request $request, $subdomain = null): View
    {
        if (! $this->isAdminUser()) {
            abort(403);
        }

        $academyId = $this->getAcademyId();

        $supervisors = User::where('user_type', 'supervisor')
            ->where('academy_id', $academyId)
            ->with('supervisorProfile.responsibilities')
            ->get()
            ->map(fn ($user) => [
                'user' => $user,
                'supervisor_code' => $user->supervisorProfile?->supervisor_code ?? '',
                'gender' => $user->supervisorProfile?->gender ?? null,
                'phone' => $user->supervisorProfile?->phone ?? $user->phone ?? '',
                'is_active' => (bool) ($user->active_status ?? false),
                'can_manage_teachers' => (bool) ($user->supervisorProfile?->can_manage_teachers ?? false),
                'performance_rating' => (float) ($user->supervisorProfile?->performance_rating ?? 0),
                'quran_teachers_count' => $user->supervisorProfile?->quranTeachers()->count() ?? 0,
                'academic_teachers_count' => $user->supervisorProfile?->academicTeachers()->count() ?? 0,
                'total_responsibilities' => $user->supervisorProfile?->getTotalResponsibilitiesCount() ?? 0,
                'created_at' => $user->created_at,
            ]);

        // Stats from unfiltered set
        $totalSupervisors = $supervisors->count();
        $activeCount = $supervisors->where('is_active', true)->count();
        $inactiveCount = $totalSupervisors - $activeCount;
        $maleCount = $supervisors->where('gender', 'male')->count();
        $femaleCount = $supervisors->where('gender', 'female')->count();

        // Apply filters
        $filtered = $supervisors;

        if ($search = $request->input('search')) {
            $search = mb_strtolower($search);
            $filtered = $filtered->filter(function ($s) use ($search) {
                return str_contains(mb_strtolower($s['user']->name), $search)
                    || str_contains(mb_strtolower($s['user']->email), $search)
                    || str_contains(mb_strtolower($s['supervisor_code']), $search);
            });
        }

        if ($gender = $request->input('gender')) {
            $filtered = $filtered->filter(fn ($s) => $s['gender'] === $gender);
        }

        if ($request->has('status') && $request->input('status') !== '') {
            $statusFilter = $request->input('status') === 'active';
            $filtered = $filtered->where('is_active', $statusFilter);
        }

        if ($request->has('has_responsibilities') && $request->input('has_responsibilities') !== '') {
            $hasResp = $request->input('has_responsibilities') === 'yes';
            $filtered = $filtered->filter(fn ($s) => $hasResp ? $s['total_responsibilities'] > 0 : $s['total_responsibilities'] === 0);
        }

        // Sort
        $sort = $request->input('sort', 'name_asc');
        $filtered = match ($sort) {
            'name_desc' => $filtered->sortByDesc(fn ($s) => $s['user']->name),
            'newest' => $filtered->sortByDesc('created_at'),
            'oldest' => $filtered->sortBy('created_at'),
            'responsibilities_count' => $filtered->sortByDesc('total_responsibilities'),
            default => $filtered->sortBy(fn ($s) => $s['user']->name),
        };

        // Paginate
        $perPage = 15;
        $page = $request->input('page', 1);
        $filteredValues = $filtered->values();
        $paginated = new LengthAwarePaginator(
            $filteredValues->forPage($page, $perPage)->values(),
            $filteredValues->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('supervisor.supervisors.index', [
            'supervisors' => $paginated,
            'totalSupervisors' => $totalSupervisors,
            'activeCount' => $activeCount,
            'inactiveCount' => $inactiveCount,
            'maleCount' => $maleCount,
            'femaleCount' => $femaleCount,
            'filteredCount' => $filteredValues->count(),
        ]);
    }

    public function toggleStatus(Request $request, $subdomain, User $supervisor): RedirectResponse
    {
        if (! $this->isAdminUser()) {
            abort(403);
        }

        $this->ensureSupervisorBelongsToAcademy($supervisor);

        $supervisor->active_status = ! $supervisor->active_status;
        $supervisor->save();

        return redirect()->back()->with('success', __('supervisor.supervisors.status_updated'));
    }

    public function resetPassword(Request $request, $subdomain, User $supervisor): RedirectResponse
    {
        if (! $this->isAdminUser()) {
            abort(403);
        }

        $this->ensureSupervisorBelongsToAcademy($supervisor);

        $newPassword = $request->input('new_password');
        $confirmation = $request->input('new_password_confirmation');

        if (! $newPassword || mb_strlen($newPassword) < 6) {
            return redirect()->back()->with('error', __('supervisor.supervisors.password_too_short'));
        }

        if ($newPassword !== $confirmation) {
            return redirect()->back()->with('error', __('supervisor.supervisors.passwords_dont_match'));
        }

        $supervisor->password = Hash::make($newPassword);
        $supervisor->save();

        return redirect()->back()->with('success', __('supervisor.supervisors.password_reset_success'));
    }

    public function destroy(Request $request, $subdomain, User $supervisor): RedirectResponse
    {
        if (! $this->isAdminUser()) {
            abort(403);
        }

        $this->ensureSupervisorBelongsToAcademy($supervisor);

        $supervisor->delete();

        return redirect()->route('manage.supervisors.index', ['subdomain' => $subdomain])
            ->with('success', __('supervisor.supervisors.supervisor_deleted'));
    }

    public function create(Request $request, $subdomain = null): View
    {
        if (! $this->isAdminUser()) {
            abort(403);
        }

        $academy = AcademyContextService::getCurrentAcademy();

        $quranTeachers = User::where('user_type', 'quran_teacher')
            ->where('academy_id', $academy->id)
            ->where('active_status', true)
            ->with('quranTeacherProfile')
            ->orderBy('first_name')
            ->get();

        $academicTeachers = User::where('user_type', 'academic_teacher')
            ->where('academy_id', $academy->id)
            ->where('active_status', true)
            ->with('academicTeacherProfile')
            ->orderBy('first_name')
            ->get();

        return view('supervisor.supervisors.create', compact('academy', 'quranTeachers', 'academicTeachers'));
    }

    public function store(Request $request, $subdomain = null): RedirectResponse
    {
        if (! $this->isAdminUser()) {
            abort(403);
        }

        $validator = Validator::make($request->all(), [
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'gender' => 'required|in:male,female',
            'can_manage_teachers' => 'nullable|boolean',
            'password' => ['required', PasswordRules::min(6)->letters()->numbers()],
            'password_confirmation' => 'required|same:password',
            'quran_teacher_ids' => 'nullable|array',
            'quran_teacher_ids.*' => 'exists:users,id',
            'academic_teacher_ids' => 'nullable|array',
            'academic_teacher_ids.*' => 'exists:users,id',
        ], [
            'first_name.required' => __('auth.register.teacher.step2.validation.first_name_required', [], 'ar'),
            'last_name.required' => __('auth.register.teacher.step2.validation.last_name_required', [], 'ar'),
            'email.required' => __('auth.register.teacher.step2.validation.email_required', [], 'ar'),
            'email.unique' => __('auth.register.teacher.step2.validation.email_unique', [], 'ar'),
            'gender.required' => __('auth.register.teacher.step2.validation.gender_required', [], 'ar'),
            'password.min' => __('supervisor.supervisors.password_too_short'),
            'password_confirmation.same' => __('supervisor.supervisors.passwords_dont_match'),
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $academy = AcademyContextService::getCurrentAcademy();

        $user = new User([
            'academy_id' => $academy->id,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
        ]);
        $user->user_type = 'supervisor';
        $user->active_status = true;
        $user->save();

        // Handle avatar upload
        $avatarPath = null;
        if ($request->hasFile('avatar')) {
            $avatarPath = $request->file('avatar')->store('avatars/supervisors', 'public');
            $user->avatar = $avatarPath;
            $user->save();
        }

        try {
            $profile = SupervisorProfile::create([
                'academy_id' => $academy->id,
                'user_id' => $user->id,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'phone' => $request->phone,
                'gender' => $request->gender,
                'can_manage_teachers' => $request->boolean('can_manage_teachers'),
                'avatar' => $avatarPath,
            ]);

            // Sync responsibilities
            $this->syncResponsibilities($profile, $request);
        } catch (QueryException $e) {
            Log::error('Supervisor creation failed: '.$e->getMessage(), [
                'user_id' => $user->id,
                'academy_id' => $academy->id,
            ]);

            if ($avatarPath) {
                Storage::disk('public')->delete($avatarPath);
            }
            $user->forceDelete();

            return back()->withErrors(['error' => __('supervisor.supervisors.create_error')])->withInput();
        }

        return redirect()->route('manage.supervisors.index', ['subdomain' => $subdomain])
            ->with('success', __('supervisor.supervisors.supervisor_created'));
    }

    public function edit(Request $request, $subdomain, User $supervisor): View
    {
        if (! $this->isAdminUser()) {
            abort(403);
        }

        $this->ensureSupervisorBelongsToAcademy($supervisor);

        $supervisor->load('supervisorProfile.responsibilities');

        $academy = AcademyContextService::getCurrentAcademy();

        $quranTeachers = User::where('user_type', 'quran_teacher')
            ->where('academy_id', $academy->id)
            ->where('active_status', true)
            ->with('quranTeacherProfile')
            ->orderBy('first_name')
            ->get();

        $academicTeachers = User::where('user_type', 'academic_teacher')
            ->where('academy_id', $academy->id)
            ->where('active_status', true)
            ->with('academicTeacherProfile')
            ->orderBy('first_name')
            ->get();

        $assignedQuranTeacherIds = $supervisor->supervisorProfile?->getAssignedQuranTeacherIds() ?? [];
        $assignedAcademicTeacherIds = $supervisor->supervisorProfile?->getAssignedAcademicTeacherIds() ?? [];

        return view('supervisor.supervisors.edit', compact(
            'supervisor',
            'academy',
            'quranTeachers',
            'academicTeachers',
            'assignedQuranTeacherIds',
            'assignedAcademicTeacherIds',
        ));
    }

    public function update(Request $request, $subdomain, User $supervisor): RedirectResponse
    {
        if (! $this->isAdminUser()) {
            abort(403);
        }

        $this->ensureSupervisorBelongsToAcademy($supervisor);

        $validator = Validator::make($request->all(), [
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.$supervisor->id,
            'phone' => 'nullable|string|max:20',
            'gender' => 'required|in:male,female',
            'can_manage_teachers' => 'nullable|boolean',
            'performance_rating' => 'nullable|numeric|min:0|max:10',
            'notes' => 'nullable|string|max:5000',
            'password' => ['nullable', PasswordRules::min(6)->letters()->numbers()],
            'password_confirmation' => 'nullable|same:password',
            'quran_teacher_ids' => 'nullable|array',
            'quran_teacher_ids.*' => 'exists:users,id',
            'academic_teacher_ids' => 'nullable|array',
            'academic_teacher_ids.*' => 'exists:users,id',
        ], [
            'first_name.required' => __('auth.register.teacher.step2.validation.first_name_required', [], 'ar'),
            'last_name.required' => __('auth.register.teacher.step2.validation.last_name_required', [], 'ar'),
            'email.required' => __('auth.register.teacher.step2.validation.email_required', [], 'ar'),
            'email.unique' => __('auth.register.teacher.step2.validation.email_unique', [], 'ar'),
            'gender.required' => __('auth.register.teacher.step2.validation.gender_required', [], 'ar'),
            'password.min' => __('supervisor.supervisors.password_too_short'),
            'password_confirmation.same' => __('supervisor.supervisors.passwords_dont_match'),
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            // Update User
            $supervisor->first_name = $request->first_name;
            $supervisor->last_name = $request->last_name;
            $supervisor->email = $request->email;
            $supervisor->phone = $request->phone;

            if (filled($request->password)) {
                $supervisor->password = Hash::make($request->password);
            }

            // Handle avatar
            if ($request->hasFile('avatar')) {
                if ($supervisor->avatar) {
                    Storage::disk('public')->delete($supervisor->avatar);
                }
                $avatarPath = $request->file('avatar')->store('avatars/supervisors', 'public');
                $supervisor->avatar = $avatarPath;
            }

            $supervisor->save();

            // Update or create SupervisorProfile
            $profile = $supervisor->supervisorProfile;
            if ($profile) {
                $profile->update([
                    'first_name' => $request->first_name,
                    'last_name' => $request->last_name,
                    'email' => $request->email,
                    'phone' => $request->phone,
                    'gender' => $request->gender,
                    'can_manage_teachers' => $request->boolean('can_manage_teachers'),
                    'performance_rating' => $request->input('performance_rating', $profile->performance_rating),
                    'notes' => $request->input('notes', $profile->notes),
                    'avatar' => $supervisor->avatar,
                ]);
            }

            // Sync responsibilities
            if ($profile) {
                $this->syncResponsibilities($profile, $request);
            }
        } catch (QueryException $e) {
            Log::error('Supervisor update failed: '.$e->getMessage(), [
                'user_id' => $supervisor->id,
            ]);

            return back()->withErrors(['error' => __('supervisor.supervisors.update_error')])->withInput();
        }

        return redirect()->route('manage.supervisors.index', ['subdomain' => $subdomain])
            ->with('success', __('supervisor.supervisors.supervisor_updated'));
    }

    private function ensureSupervisorBelongsToAcademy(User $supervisor): void
    {
        if ($supervisor->user_type !== 'supervisor' || $supervisor->academy_id !== $this->getAcademyId()) {
            abort(403);
        }
    }

    private function syncResponsibilities(SupervisorProfile $profile, Request $request): void
    {
        $quranTeacherIds = array_filter($request->input('quran_teacher_ids', []) ?? []);
        $academicTeacherIds = array_filter($request->input('academic_teacher_ids', []) ?? []);

        // Delete all existing User-type responsibilities
        SupervisorResponsibility::where('supervisor_profile_id', $profile->id)
            ->where('responsable_type', User::class)
            ->delete();

        // Re-create from submitted IDs
        foreach ($quranTeacherIds as $teacherId) {
            SupervisorResponsibility::create([
                'supervisor_profile_id' => $profile->id,
                'responsable_type' => User::class,
                'responsable_id' => $teacherId,
            ]);
        }

        foreach ($academicTeacherIds as $teacherId) {
            SupervisorResponsibility::create([
                'supervisor_profile_id' => $profile->id,
                'responsable_type' => User::class,
                'responsable_id' => $teacherId,
            ]);
        }
    }
}
