<?php

namespace App\Http\Controllers;

use App\Enums\RelationshipType;
use App\Http\Requests\StoreChildRequest;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ParentChildrenController extends Controller
{
    /**
     * Display parent's children and add child form
     */
    public function index(): View
    {
        $parent = Auth::user()->parentProfile;

        if (! $parent) {
            abort(404, 'Parent profile not found');
        }

        // Get all attached children
        $children = $parent->students()->with('gradeLevel')->get();

        return view('parent.children.index', compact('children', 'parent'));
    }

    /**
     * Add a new child to parent account
     */
    public function store(StoreChildRequest $request): RedirectResponse
    {
        $parent = Auth::user()->parentProfile;

        if (! $parent) {
            return back()->with('error', __('حساب ولي الأمر غير موجود'));
        }

        // Find student by student code
        $student = StudentProfile::where('student_code', $request->student_code)->first();

        if (! $student) {
            return back()->with('error', __('لم يتم العثور على طالب بهذا الكود'));
        }

        // Check if student already attached to this parent
        if ($parent->students()->where('student_id', $student->id)->exists()) {
            return back()->with('error', __('هذا الطالب مرتبط بالفعل بحسابك'));
        }

        // Normalize phone numbers for comparison (remove spaces, dashes, etc.)
        $parentPhone = preg_replace('/[^0-9+]/', '', $parent->phone);
        $studentParentPhone = preg_replace('/[^0-9+]/', '', $student->parent_phone ?? '');

        // Check if parent phone matches student's parent phone
        if (empty($studentParentPhone)) {
            return back()->with('error', __('لا يوجد رقم هاتف ولي أمر مسجل لهذا الطالب'));
        }

        if ($parentPhone !== $studentParentPhone) {
            return back()->with('error', __('رقم هاتفك لا يطابق رقم هاتف ولي أمر هذا الطالب'));
        }

        // Attach student to parent
        $parent->students()->attach($student->id, [
            'relationship_type' => $parent->relationship_type ?? RelationshipType::FATHER,
        ]);

        // Update student's primary parent_id if not already set
        if (! $student->parent_id) {
            $student->update(['parent_id' => $parent->id]);
        }

        return back()->with('success', __('تم إضافة الطالب بنجاح'));
    }

    /**
     * Remove a child from parent account
     */
    public function destroy(StudentProfile $student): RedirectResponse
    {
        // Authorize that the parent can view/manage this child
        $this->authorize('viewChild', [User::class, $student]);

        $parent = Auth::user()->parentProfile;

        if (! $parent) {
            abort(404, 'Parent profile not found');
        }

        // Check if student is attached to this parent
        if (! $parent->students()->where('student_id', $student->id)->exists()) {
            return back()->with('error', __('هذا الطالب غير مرتبط بحسابك'));
        }

        // Detach student from parent
        $parent->students()->detach($student->id);

        // If this was the primary parent, clear parent_id
        if ($student->parent_id === $parent->id) {
            $student->update(['parent_id' => null]);
        }

        return back()->with('success', __('تم إلغاء ربط الطالب بنجاح'));
    }
}
