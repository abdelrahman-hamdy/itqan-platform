<?php

namespace App\Http\Controllers;

use App\Models\AcademicSubject;
use App\Models\AcademicGradeLevel;
use App\Models\AcademicTeacher;
use App\Models\Academy;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class AcademicSubjectController extends Controller
{
    /**
     * عرض قائمة المواد الأكاديمية
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = AcademicSubject::with([
                'academy:id,name',
                'creator:id,name',
                'teachers:id,user_id',
                'gradeLevels:id,name,name_en'
            ]);

            // فلترة حسب الأكاديمية
            if ($request->has('academy_id')) {
                $query->where('academy_id', $request->academy_id);
            }

            // فلترة حسب الفئة
            if ($request->has('category')) {
                $query->where('category', $request->category);
            }

            // فلترة حسب المجال
            if ($request->has('field')) {
                $query->where('field', $request->field);
            }

            // فلترة حسب النوع
            if ($request->has('is_core_subject')) {
                $query->where('is_core_subject', $request->boolean('is_core_subject'));
            }

            // فلترة حسب النشاط
            if ($request->has('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            // فلترة حسب مستوى الصعوبة
            if ($request->has('difficulty_level')) {
                $query->where('difficulty_level', $request->difficulty_level);
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
            $sortBy = $request->get('sort_by', 'display_order');
            $sortOrder = $request->get('sort_order', 'asc');
            $query->orderBy($sortBy, $sortOrder);

            $subjects = $query->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $subjects,
                'message' => 'تم جلب قائمة المواد الأكاديمية بنجاح'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب قائمة المواد',
                'error' => $e->getMessage()
            ], 500);
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
                'creator:id,name,email',
                'teachers.user:id,name,email,avatar',
                'gradeLevels:id,name,name_en,level',
                'privateSessions:id,subscription_id,scheduled_date,status',
                'interactiveCourses:id,title,status,start_date',
                'recordedCourses:id,title,status'
            ])->findOrFail($id);

            // إحصائيات إضافية
            $subject->loadCount([
                'teachers as total_teachers_count',
                'privateSessions as total_sessions_count',
                'interactiveCourses as total_courses_count',
                'recordedCourses as total_recorded_courses_count'
            ]);

            return response()->json([
                'success' => true,
                'data' => $subject,
                'message' => 'تم جلب تفاصيل المادة بنجاح'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب تفاصيل المادة',
                'error' => $e->getMessage()
            ], 500);
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
                'description' => 'required|string|max:1000',
                'category' => 'required|string|in:mathematics,sciences,languages,humanities,arts,technology,physical_education,other',
                'field' => 'required|string|in:primary,intermediate,secondary,university,professional,other',
                'level_scope' => 'nullable|array',
                'level_scope.*' => 'string|in:beginner,intermediate,advanced,expert',
                'prerequisites' => 'nullable|array',
                'prerequisites.*' => 'exists:academic_subjects,id',
                'color_code' => 'nullable|string|max:7|regex:/^#[0-9A-F]{6}$/i',
                'icon' => 'nullable|string|max:100',
                'is_core_subject' => 'boolean',
                'is_elective' => 'boolean',
                'credit_hours' => 'required|integer|min:1|max:20',
                'difficulty_level' => 'required|integer|min:1|max:5',
                'estimated_duration_weeks' => 'required|integer|min:1|max:52',
                'curriculum_framework' => 'nullable|string|max:2000',
                'learning_objectives' => 'required|array|min:1',
                'learning_objectives.*' => 'string|max:500',
                'assessment_methods' => 'required|array|min:1',
                'assessment_methods.*' => 'string|in:exam,quiz,assignment,project,presentation,participation,other',
                'required_materials' => 'nullable|array',
                'required_materials.*' => 'string|max:255',
                'display_order' => 'nullable|integer|min:0',
                'notes' => 'nullable|string|max:1000',
                'grade_levels' => 'required|array|min:1',
                'grade_levels.*.grade_level_id' => 'required|exists:academic_grade_levels,id',
                'grade_levels.*.hours_per_week' => 'required|integer|min:1|max:40',
                'grade_levels.*.semester' => 'required|string|in:first,second,summer',
                'grade_levels.*.is_mandatory' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'بيانات غير صحيحة',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            // إنشاء المادة
            $subject = AcademicSubject::create($request->except(['grade_levels']));

            // ربط المراحل الدراسية
            if ($request->has('grade_levels')) {
                foreach ($request->grade_levels as $gradeData) {
                    $subject->gradeLevels()->attach($gradeData['grade_level_id'], [
                        'hours_per_week' => $gradeData['hours_per_week'],
                        'semester' => $gradeData['semester'],
                        'is_mandatory' => $gradeData['is_mandatory'] ?? true,
                    ]);
                }
            }

            DB::commit();

            $subject->load(['academy:id,name', 'creator:id,name', 'gradeLevels:id,name']);

            return response()->json([
                'success' => true,
                'data' => $subject,
                'message' => 'تم إنشاء المادة الأكاديمية بنجاح'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إنشاء المادة',
                'error' => $e->getMessage()
            ], 500);
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
                'description' => 'sometimes|string|max:1000',
                'category' => 'sometimes|string|in:mathematics,sciences,languages,humanities,arts,technology,physical_education,other',
                'field' => 'sometimes|string|in:primary,intermediate,secondary,university,professional,other',
                'level_scope' => 'nullable|array',
                'level_scope.*' => 'string|in:beginner,intermediate,advanced,expert',
                'prerequisites' => 'nullable|array',
                'prerequisites.*' => 'exists:academic_subjects,id',
                'color_code' => 'nullable|string|max:7|regex:/^#[0-9A-F]{6}$/i',
                'icon' => 'nullable|string|max:100',
                'is_core_subject' => 'sometimes|boolean',
                'is_elective' => 'sometimes|boolean',
                'credit_hours' => 'sometimes|integer|min:1|max:20',
                'difficulty_level' => 'sometimes|integer|min:1|max:5',
                'estimated_duration_weeks' => 'sometimes|integer|min:1|max:52',
                'curriculum_framework' => 'nullable|string|max:2000',
                'learning_objectives' => 'sometimes|array|min:1',
                'learning_objectives.*' => 'string|max:500',
                'assessment_methods' => 'sometimes|array|min:1',
                'assessment_methods.*' => 'string|in:exam,quiz,assignment,project,presentation,participation,other',
                'required_materials' => 'nullable|array',
                'required_materials.*' => 'string|max:255',
                'is_active' => 'sometimes|boolean',
                'display_order' => 'nullable|integer|min:0',
                'notes' => 'nullable|string|max:1000',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'بيانات غير صحيحة',
                    'errors' => $validator->errors()
                ], 422);
            }

            $subject->update($request->except(['grade_levels']));

            // تحديث المراحل الدراسية إذا تم إرسالها
            if ($request->has('grade_levels')) {
                $subject->gradeLevels()->detach();
                foreach ($request->grade_levels as $gradeData) {
                    $subject->gradeLevels()->attach($gradeData['grade_level_id'], [
                        'hours_per_week' => $gradeData['hours_per_week'],
                        'semester' => $gradeData['semester'],
                        'is_mandatory' => $gradeData['is_mandatory'] ?? true,
                    ]);
                }
            }

            $subject->load(['academy:id,name', 'creator:id,name', 'gradeLevels:id,name']);

            return response()->json([
                'success' => true,
                'data' => $subject,
                'message' => 'تم تحديث بيانات المادة بنجاح'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث بيانات المادة',
                'error' => $e->getMessage()
            ], 500);
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
            $teachersCount = $subject->teachers()->count();
            if ($teachersCount > 0) {
                return response()->json([
                    'success' => false,
                    'message' => "لا يمكن حذف المادة لوجود {$teachersCount} معلم مرتبط بها"
                ], 422);
            }

            // التحقق من عدم وجود جلسات نشطة
            $activeSessions = $subject->privateSessions()
                ->where('status', 'scheduled')
                ->where('scheduled_date', '>=', now())
                ->count();

            if ($activeSessions > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن حذف المادة لوجود جلسات نشطة مبرمجة'
                ], 422);
            }

            // التحقق من عدم وجود دورات نشطة
            $activeCourses = $subject->interactiveCourses()
                ->where('status', 'active')
                ->count();

            if ($activeCourses > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن حذف المادة لوجود دورات تفاعلية نشطة'
                ], 422);
            }

            DB::beginTransaction();

            // حذف العلاقات
            $subject->gradeLevels()->detach();
            $subject->teachers()->detach();

            // حذف المادة
            $subject->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم حذف المادة بنجاح'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حذف المادة',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * تفعيل/إلغاء تفعيل مادة
     */
    public function toggleStatus(int $id): JsonResponse
    {
        try {
            $subject = AcademicSubject::findOrFail($id);

            $subject->update([
                'is_active' => !$subject->is_active
            ]);

            $status = $subject->is_active ? 'مفعلة' : 'معطلة';

            return response()->json([
                'success' => true,
                'data' => $subject,
                'message' => "تم {$status} المادة بنجاح"
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تغيير حالة المادة',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * إحصائيات المادة
     */
    public function statistics(int $id): JsonResponse
    {
        try {
            $subject = AcademicSubject::findOrFail($id);

            $stats = [
                'total_teachers' => $subject->teachers()->count(),
                'active_teachers' => $subject->teachers()->where('is_active', true)->count(),
                'total_sessions' => $subject->privateSessions()->count(),
                'completed_sessions' => $subject->privateSessions()->where('status', 'completed')->count(),
                'total_courses' => $subject->interactiveCourses()->count(),
                'published_courses' => $subject->interactiveCourses()->where('is_published', true)->count(),
                'total_recorded_courses' => $subject->recordedCourses()->count(),
                'active_recorded_courses' => $subject->recordedCourses()->where('is_active', true)->count(),
                'grade_levels_count' => $subject->gradeLevels()->count(),
                'average_difficulty' => $subject->difficulty_level,
                'estimated_duration' => $subject->estimated_duration_weeks . ' أسابيع',
                'credit_hours' => $subject->credit_hours,
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'تم جلب إحصائيات المادة بنجاح'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب إحصائيات المادة',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * البحث عن مواد متاحة
     */
    public function searchAvailable(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'academy_id' => 'required|exists:academies,id',
                'category' => 'nullable|string|in:mathematics,sciences,languages,humanities,arts,technology,physical_education,other',
                'field' => 'nullable|string|in:primary,intermediate,secondary,university,professional,other',
                'difficulty_level' => 'nullable|integer|min:1|max:5',
                'is_core_subject' => 'nullable|boolean',
                'grade_level_id' => 'nullable|exists:academic_grade_levels,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'بيانات غير صحيحة',
                    'errors' => $validator->errors()
                ], 422);
            }

            $query = AcademicSubject::with(['academy:id,name', 'gradeLevels:id,name'])
                ->where('academy_id', $request->academy_id)
                ->where('is_active', true);

            // فلترة حسب الفئة
            if ($request->has('category')) {
                $query->where('category', $request->category);
            }

            // فلترة حسب المجال
            if ($request->has('field')) {
                $query->where('field', $request->field);
            }

            // فلترة حسب مستوى الصعوبة
            if ($request->has('difficulty_level')) {
                $query->where('difficulty_level', $request->difficulty_level);
            }

            // فلترة حسب النوع
            if ($request->has('is_core_subject')) {
                $query->where('is_core_subject', $request->boolean('is_core_subject'));
            }

            // فلترة حسب المرحلة الدراسية
            if ($request->has('grade_level_id')) {
                $query->whereHas('gradeLevels', function ($q) use ($request) {
                    $q->where('academic_grade_levels.id', $request->grade_level_id);
                });
            }

            // ترتيب حسب الترتيب المعروض والصعوبة
            $query->orderBy('display_order', 'asc')
                  ->orderBy('difficulty_level', 'asc');

            $subjects = $query->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $subjects,
                'message' => 'تم البحث عن المواد المتاحة بنجاح'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء البحث عن المواد',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * تصدير منهج المادة
     */
    public function exportCurriculum(int $id): JsonResponse
    {
        try {
            $subject = AcademicSubject::with([
                'gradeLevels:id,name,name_en,level',
                'learning_objectives',
                'assessment_methods',
                'required_materials'
            ])->findOrFail($id);

            $curriculum = [
                'subject_info' => [
                    'name' => $subject->name,
                    'name_en' => $subject->name_en,
                    'description' => $subject->description,
                    'category' => $subject->category,
                    'field' => $subject->field,
                    'difficulty_level' => $subject->difficulty_level,
                    'credit_hours' => $subject->credit_hours,
                    'estimated_duration_weeks' => $subject->estimated_duration_weeks,
                ],
                'curriculum_framework' => $subject->curriculum_framework,
                'learning_objectives' => $subject->learning_objectives,
                'assessment_methods' => $subject->assessment_methods,
                'required_materials' => $subject->required_materials,
                'grade_levels' => $subject->gradeLevels->map(function ($grade) {
                    return [
                        'name' => $grade->name,
                        'name_en' => $grade->name_en,
                        'level' => $grade->level,
                        'pivot' => $grade->pivot
                    ];
                }),
                'exported_at' => now()->toISOString(),
            ];

            return response()->json([
                'success' => true,
                'data' => $curriculum,
                'message' => 'تم تصدير منهج المادة بنجاح'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تصدير منهج المادة',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * الحصول على فئات المواد
     */
    public function getCategories(): JsonResponse
    {
        try {
            $categories = [
                'mathematics' => 'الرياضيات',
                'sciences' => 'العلوم',
                'languages' => 'اللغات',
                'humanities' => 'العلوم الإنسانية',
                'arts' => 'الفنون',
                'technology' => 'التكنولوجيا',
                'physical_education' => 'التربية البدنية',
                'other' => 'أخرى'
            ];

            return response()->json([
                'success' => true,
                'data' => $categories,
                'message' => 'تم جلب فئات المواد بنجاح'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب فئات المواد',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * الحصول على مجالات المواد
     */
    public function getFields(): JsonResponse
    {
        try {
            $fields = [
                'primary' => 'الابتدائية',
                'intermediate' => 'المتوسطة',
                'secondary' => 'الثانوية',
                'university' => 'الجامعية',
                'professional' => 'المهنية',
                'other' => 'أخرى'
            ];

            return response()->json([
                'success' => true,
                'data' => $fields,
                'message' => 'تم جلب مجالات المواد بنجاح'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب مجالات المواد',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 