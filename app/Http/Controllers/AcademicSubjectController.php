<?php

namespace App\Http\Controllers;

use App\Http\Traits\Api\ApiResponses;
use App\Models\AcademicGradeLevel;
use App\Models\AcademicSubject;
use App\Models\Academy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AcademicSubjectController extends Controller
{
    use ApiResponses;

    /**
     * عرض قائمة المواد الأكاديمية
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = AcademicSubject::with([
                'academy:id,name',
                'creator:id,first_name,last_name',
                'teachers:id,user_id',
                'gradeLevels:id,name,name_en',
            ]);

            // فلترة حسب الأكاديمية
            if ($request->has('academy_id')) {
                $query->where('academy_id', $request->academy_id);
            }

            // فلترة حسب النشاط
            if ($request->has('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            // البحث
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('name_en', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            }

            // ترتيب
            $sortBy = $request->get('sort_by', 'name');
            $sortOrder = $request->get('sort_order', 'asc');
            $query->orderBy($sortBy, $sortOrder);

            $subjects = $query->paginate($request->get('per_page', 15));

            return $this->success($subjects, 'تم جلب قائمة المواد الأكاديمية بنجاح');

        } catch (\Exception $e) {
            return $this->serverError('حدث خطأ أثناء جلب قائمة المواد');
        }
    }

    /**
     * عرض تفاصيل مادة أكاديمية
     */
    public function show(int $id): JsonResponse
    {
        try {
            $subject = AcademicSubject::with([
                'academy:id,name,logo',
                'creator:id,first_name,last_name,email',
                'teachers.user:id,first_name,last_name,email,avatar',
                'gradeLevels:id,name,name_en,level',
                'academicIndividualLessons:id,student_id,teacher_id,status',
                'interactiveCourses:id,title,status,start_date',
                'recordedCourses:id,title,status',
            ])->findOrFail($id);

            // إحصائيات إضافية
            $subject->loadCount([
                'teachers as total_teachers_count',
                'academicIndividualLessons as total_lessons_count',
                'interactiveCourses as total_courses_count',
                'recordedCourses as total_recorded_courses_count',
            ]);

            return $this->success($subject, 'تم جلب تفاصيل المادة بنجاح');

        } catch (\Exception $e) {
            return $this->serverError('حدث خطأ أثناء جلب تفاصيل المادة');
        }
    }

    /**
     * إنشاء مادة أكاديمية جديدة
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'academy_id' => 'required|exists:academies,id',
                'name' => 'required|string|max:255',
                'name_en' => 'nullable|string|max:255',
                'description' => 'nullable|string|max:1000',
                'admin_notes' => 'nullable|string|max:1000',
                'is_active' => 'boolean',
                'grade_levels' => 'nullable|array',
                'grade_levels.*.grade_level_id' => 'required|exists:academic_grade_levels,id',
                'grade_levels.*.hours_per_week' => 'nullable|integer|min:1|max:40',
                'grade_levels.*.semester' => 'nullable|string|in:first,second,both,summer',
                'grade_levels.*.is_mandatory' => 'boolean',
            ]);

            if ($validator->fails()) {
                return $this->validationError($validator->errors()->toArray(), 'بيانات غير صحيحة');
            }

            DB::beginTransaction();

            // إنشاء المادة
            $subject = AcademicSubject::create([
                'academy_id' => $request->academy_id,
                'name' => $request->name,
                'name_en' => $request->name_en,
                'description' => $request->description,
                'admin_notes' => $request->admin_notes,
                'is_active' => $request->is_active ?? true,
                'created_by' => auth()->id(),
            ]);

            // ربط المراحل الدراسية
            if ($request->has('grade_levels')) {
                foreach ($request->grade_levels as $gradeData) {
                    $subject->gradeLevels()->attach($gradeData['grade_level_id'], [
                        'hours_per_week' => $gradeData['hours_per_week'] ?? 3,
                        'semester' => $gradeData['semester'] ?? 'both',
                        'is_mandatory' => $gradeData['is_mandatory'] ?? true,
                    ]);
                }
            }

            DB::commit();

            $subject->load(['academy:id,name', 'creator:id,first_name,last_name', 'gradeLevels:id,name']);

            return $this->created($subject, 'تم إنشاء المادة الأكاديمية بنجاح');

        } catch (\Exception $e) {
            DB::rollBack();

            return $this->serverError('حدث خطأ أثناء إنشاء المادة');
        }
    }

    /**
     * تحديث بيانات مادة أكاديمية
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $subject = AcademicSubject::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|string|max:255',
                'name_en' => 'nullable|string|max:255',
                'description' => 'nullable|string|max:1000',
                'admin_notes' => 'nullable|string|max:1000',
                'is_active' => 'sometimes|boolean',
                'grade_levels' => 'nullable|array',
                'grade_levels.*.grade_level_id' => 'required|exists:academic_grade_levels,id',
                'grade_levels.*.hours_per_week' => 'nullable|integer|min:1|max:40',
                'grade_levels.*.semester' => 'nullable|string|in:first,second,both,summer',
                'grade_levels.*.is_mandatory' => 'boolean',
            ]);

            if ($validator->fails()) {
                return $this->validationError($validator->errors()->toArray(), 'بيانات غير صحيحة');
            }

            $subject->update($request->only([
                'name',
                'name_en',
                'description',
                'admin_notes',
                'is_active',
            ]));

            // تحديث المراحل الدراسية إذا تم إرسالها
            if ($request->has('grade_levels')) {
                $subject->gradeLevels()->detach();
                foreach ($request->grade_levels as $gradeData) {
                    $subject->gradeLevels()->attach($gradeData['grade_level_id'], [
                        'hours_per_week' => $gradeData['hours_per_week'] ?? 3,
                        'semester' => $gradeData['semester'] ?? 'both',
                        'is_mandatory' => $gradeData['is_mandatory'] ?? true,
                    ]);
                }
            }

            $subject->load(['academy:id,name', 'creator:id,first_name,last_name', 'gradeLevels:id,name']);

            return $this->success($subject, 'تم تحديث بيانات المادة بنجاح');

        } catch (\Exception $e) {
            return $this->serverError('حدث خطأ أثناء تحديث بيانات المادة');
        }
    }

    /**
     * حذف مادة أكاديمية
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $subject = AcademicSubject::findOrFail($id);

            // التحقق من عدم وجود معلمين مرتبطين
            if ($subject->teachers()->count() > 0) {
                return $this->error('لا يمكن حذف المادة لوجود معلمين مرتبطين بها', 400);
            }

            // التحقق من عدم وجود دورات مرتبطة
            if ($subject->interactiveCourses()->count() > 0 || $subject->recordedCourses()->count() > 0) {
                return $this->error('لا يمكن حذف المادة لوجود دورات مرتبطة بها', 400);
            }

            // حذف ربط المراحل الدراسية
            $subject->gradeLevels()->detach();

            // حذف المادة
            $subject->delete();

            return $this->success(null, 'تم حذف المادة بنجاح');

        } catch (\Exception $e) {
            return $this->serverError('حدث خطأ أثناء حذف المادة');
        }
    }

    /**
     * الحصول على المواد حسب المرحلة الدراسية
     */
    public function getByGradeLevel(int $gradeLevelId): JsonResponse
    {
        try {
            $gradeLevel = AcademicGradeLevel::findOrFail($gradeLevelId);

            $subjects = $gradeLevel->subjects()
                ->where('is_active', true)
                ->with(['academy:id,name'])
                ->get();

            return $this->success($subjects, 'تم جلب قائمة المواد بنجاح');

        } catch (\Exception $e) {
            return $this->serverError('حدث خطأ أثناء جلب المواد');
        }
    }

    /**
     * الحصول على المواد النشطة لأكاديمية معينة
     */
    public function getActiveByAcademy(int $academyId): JsonResponse
    {
        try {
            $academy = Academy::findOrFail($academyId);

            $subjects = AcademicSubject::where('academy_id', $academyId)
                ->where('is_active', true)
                ->orderBy('name')
                ->get();

            return $this->success($subjects, 'تم جلب قائمة المواد بنجاح');

        } catch (\Exception $e) {
            return $this->serverError('حدث خطأ أثناء جلب المواد');
        }
    }
}
