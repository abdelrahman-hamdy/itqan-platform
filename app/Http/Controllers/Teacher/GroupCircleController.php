<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\QuranCircle;
use App\Services\AcademyContextService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class GroupCircleController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show form to create a new group circle
     */
    public function create($subdomain): View
    {
        $user = Auth::user();
        $academy = current_academy() ?? $user->academy;

        if (! $academy) {
            abort(404, __('errors.academy_not_found'));
        }

        $circle = null;
        $isEdit = false;

        return view('teacher.circles.group-circle-form', compact('circle', 'isEdit', 'academy'));
    }

    /**
     * Store a new group circle
     */
    public function store(Request $request, $subdomain): RedirectResponse
    {
        $user = Auth::user();
        $academy = current_academy() ?? $user->academy;

        if (! $academy) {
            abort(404, __('errors.academy_not_found'));
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'specialization' => 'nullable|string|in:memorization,recitation,interpretation,tajweed,complete',
            'memorization_level' => 'nullable|string|in:beginner,intermediate,advanced',
            'age_group' => 'nullable|string|in:children,youth,adults,all_ages',
            'gender_type' => 'nullable|string|in:male,female,mixed',
            'max_students' => 'required|integer|min:2|max:50',
            'monthly_fee' => 'nullable|numeric|min:0',
            'monthly_sessions_count' => 'nullable|integer|min:1|max:60',
            'learning_objectives' => 'nullable|array',
            'learning_objectives.*' => 'string|max:500',
            'status' => 'nullable|boolean',
        ]);

        // Generate circle code
        $circleCode = DB::transaction(function () use ($academy) {
            $last = QuranCircle::withoutGlobalScopes()
                ->where('academy_id', $academy->id)
                ->lockForUpdate()
                ->orderByRaw('CAST(SUBSTRING(circle_code, -4) AS UNSIGNED) DESC')
                ->first(['circle_code']);

            $seq = $last && preg_match('/(\d{4})$/', $last->circle_code, $m) ? (int) $m[1] + 1 : 1;

            return 'QC-' . str_pad($academy->id, 2, '0', STR_PAD_LEFT) . '-' . str_pad($seq, 4, '0', STR_PAD_LEFT);
        });

        $circle = QuranCircle::create([
            'academy_id' => $academy->id,
            'quran_teacher_id' => $user->id,
            'circle_code' => $circleCode,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'specialization' => $validated['specialization'] ?? null,
            'memorization_level' => $validated['memorization_level'] ?? null,
            'age_group' => $validated['age_group'] ?? null,
            'gender_type' => $validated['gender_type'] ?? null,
            'max_students' => $validated['max_students'],
            'monthly_fee' => $validated['monthly_fee'] ?? null,
            'monthly_sessions_count' => $validated['monthly_sessions_count'] ?? null,
            'learning_objectives' => $validated['learning_objectives'] ?? null,
            'status' => $validated['status'] ?? true,
            'enrolled_students' => 0,
        ]);

        return redirect()
            ->route('teacher.group-circles.show', ['subdomain' => $academy->subdomain, 'circle' => $circle->id])
            ->with('success', __('teacher.circle_form.created_success'));
    }

    /**
     * Show form to edit an existing group circle
     */
    public function edit($subdomain, $circleId): View
    {
        $user = Auth::user();
        $academy = current_academy() ?? $user->academy;

        if (! $academy) {
            abort(404, __('errors.academy_not_found'));
        }

        $circle = QuranCircle::where('id', $circleId)
            ->where('quran_teacher_id', $user->id)
            ->where('academy_id', $academy->id)
            ->firstOrFail();

        $isEdit = true;

        return view('teacher.circles.group-circle-form', compact('circle', 'isEdit', 'academy'));
    }

    /**
     * Update an existing group circle
     */
    public function update(Request $request, $subdomain, $circleId): RedirectResponse
    {
        $user = Auth::user();
        $academy = current_academy() ?? $user->academy;

        if (! $academy) {
            abort(404, __('errors.academy_not_found'));
        }

        $circle = QuranCircle::where('id', $circleId)
            ->where('quran_teacher_id', $user->id)
            ->where('academy_id', $academy->id)
            ->firstOrFail();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'specialization' => 'nullable|string|in:memorization,recitation,interpretation,tajweed,complete',
            'memorization_level' => 'nullable|string|in:beginner,intermediate,advanced',
            'age_group' => 'nullable|string|in:children,youth,adults,all_ages',
            'gender_type' => 'nullable|string|in:male,female,mixed',
            'max_students' => 'required|integer|min:2|max:50',
            'monthly_fee' => 'nullable|numeric|min:0',
            'monthly_sessions_count' => 'nullable|integer|min:1|max:60',
            'learning_objectives' => 'nullable|array',
            'learning_objectives.*' => 'string|max:500',
            'status' => 'nullable|boolean',
        ]);

        $circle->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'specialization' => $validated['specialization'] ?? null,
            'memorization_level' => $validated['memorization_level'] ?? null,
            'age_group' => $validated['age_group'] ?? null,
            'gender_type' => $validated['gender_type'] ?? null,
            'max_students' => $validated['max_students'],
            'monthly_fee' => $validated['monthly_fee'] ?? null,
            'monthly_sessions_count' => $validated['monthly_sessions_count'] ?? null,
            'learning_objectives' => $validated['learning_objectives'] ?? null,
            'status' => $validated['status'] ?? true,
        ]);

        return redirect()
            ->route('teacher.group-circles.show', ['subdomain' => $academy->subdomain, 'circle' => $circle->id])
            ->with('success', __('teacher.circle_form.updated_success'));
    }
}
