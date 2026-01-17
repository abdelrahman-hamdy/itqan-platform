<?php

namespace App\Http\Controllers;

use App\Enums\EducationalQualification;
use App\Enums\SessionStatus;
use App\Enums\SessionSubscriptionStatus;
use App\Http\Traits\Api\ApiResponses;
use App\Models\AcademicTeacherProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Enum;

class AcademicTeacherController extends Controller
{
    use ApiResponses;

    /**
     * عرض قائمة المعلمين الأكاديميين
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = AcademicTeacherProfile::with([
                'user:id,name,email,phone',
                'academy:id,name',
                'subjects:id,name,name_en',
                'gradeLevels:id,name,name_en',
            ]);

            // فلترة حسب الأكاديمية
            if ($request->has('academy_id')) {
                $query->where('academy_id', $request->academy_id);
            }

            // فلترة حسب الحالة
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // فلترة حسب الموافقة
            if ($request->has('is_approved')) {
                $query->where('is_approved', $request->boolean('is_approved'));
            }

            // فلترة حسب النشاط
            if ($request->has('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            // فلترة حسب المادة
            if ($request->has('subject_id')) {
                $query->whereHas('subjects', function ($q) use ($request) {
                    $q->where('academic_subjects.id', $request->subject_id);
                });
            }

            // فلترة حسب المرحلة الدراسية
            if ($request->has('grade_level_id')) {
                $query->whereHas('gradeLevels', function ($q) use ($request) {
                    $q->where('academic_grade_levels.id', $request->grade_level_id);
                });
            }

            // البحث
            if ($request->has('search')) {
                $search = $request->search;
                $query->whereHas('user', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                })->orWhere('teacher_code', 'like', "%{$search}%");
            }

            // ترتيب
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            $teachers = $query->paginate($request->get('per_page', 15));

            return $this->success($teachers, 'تم جلب قائمة المعلمين الأكاديميين بنجاح');

        } catch (\Exception $e) {
            return $this->serverError('حدث خطأ أثناء جلب قائمة المعلمين');
        }
    }

    /**
     * عرض تفاصيل معلم أكاديمي
     */
    public function show(int $id): JsonResponse
    {
        try {
            $teacher = AcademicTeacherProfile::with([
                'user:id,name,email,phone,avatar',
                'academy:id,name,logo',
                'subjects:id,name,name_en,category,field',
                'gradeLevels:id,name,name_en,level',
                'privateSessions:id,subscription_id,scheduled_date,status',
                'interactiveCourses:id,title,status,start_date',
                'recordedCourses:id,title,status',
                'students:id,user_id',
                'subscriptions:id,student_id,status,start_date',
            ])->findOrFail($id);

            // إحصائيات إضافية
            $teacher->loadCount([
                'privateSessions as total_sessions_count',
                'interactiveCourses as total_courses_count',
                'recordedCourses as total_recorded_courses_count',
                'students as total_students_count',
                'subscriptions as total_subscriptions_count',
            ]);

            return $this->success($teacher, 'تم جلب تفاصيل المعلم بنجاح');

        } catch (\Exception $e) {
            return $this->serverError('حدث خطأ أثناء جلب تفاصيل المعلم');
        }
    }

    /**
     * إنشاء معلم أكاديمي جديد
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|exists:users,id|unique:academic_teachers,user_id',
                'academy_id' => 'required|exists:academies,id',
                'teacher_code' => 'nullable|string|max:50|unique:academic_teachers,teacher_code',
                'education_level' => ['required', new Enum(EducationalQualification::class)],
                'university' => 'required|string|max:255',
                'graduation_year' => 'required|integer|min:1950|max:'.(date('Y') + 1),
                'teaching_experience_years' => 'required|integer|min:0|max:50',
                'certifications' => 'nullable|array',
                'certifications.*' => 'string|max:255',
                'languages' => 'required|array|min:1',
                'languages.*' => 'string|in:arabic,english,french,spanish,other',
                'available_days' => 'required|array|min:1',
                'available_days.*' => 'string|in:sunday,monday,tuesday,wednesday,thursday,friday,saturday',
                'available_time_start' => 'required|date_format:H:i',
                'available_time_end' => 'required|date_format:H:i|after:available_time_start',
                'session_price_individual' => 'required|numeric|min:0',
                'min_session_duration' => 'required|integer|min:15|max:120',
                'max_session_duration' => 'required|integer|min:15|max:180',
                'max_students_per_group' => 'required|integer|min:1|max:20',
                'bio_arabic' => 'required|string|max:1000',
                'bio_english' => 'nullable|string|max:1000',
                'subjects' => 'required|array|min:1',
                'subjects.*.subject_id' => 'required|exists:academic_subjects,id',
                'subjects.*.proficiency_level' => 'required|string|in:beginner,intermediate,advanced,expert',
                'subjects.*.years_experience' => 'required|integer|min:0|max:50',
                'subjects.*.is_primary' => 'boolean',
                'subjects.*.certification' => 'nullable|string|max:255',
                'grade_levels' => 'required|array|min:1',
                'grade_levels.*.grade_level_id' => 'required|exists:academic_grade_levels,id',
                'grade_levels.*.hours_per_week' => 'required|integer|min:1|max:40',
                'grade_levels.*.semester' => 'required|string|in:first,second,summer',
                'grade_levels.*.is_mandatory' => 'boolean',
                'notes' => 'nullable|string|max:1000',
            ]);

            if ($validator->fails()) {
                return $this->validationError($validator->errors()->toArray(), 'بيانات غير صحيحة');
            }

            DB::beginTransaction();

            // إنشاء المعلم
            $teacher = AcademicTeacherProfile::create($request->except(['subjects', 'grade_levels']));

            // ربط المواد
            if ($request->has('subjects')) {
                foreach ($request->subjects as $subjectData) {
                    $teacher->subjects()->attach($subjectData['subject_id'], [
                        'proficiency_level' => $subjectData['proficiency_level'],
                        'years_experience' => $subjectData['years_experience'],
                        'is_primary' => $subjectData['is_primary'] ?? false,
                        'certification' => $subjectData['certification'] ?? null,
                    ]);
                }
            }

            // ربط المراحل الدراسية
            if ($request->has('grade_levels')) {
                foreach ($request->grade_levels as $gradeData) {
                    $teacher->gradeLevels()->attach($gradeData['grade_level_id'], [
                        'hours_per_week' => $gradeData['hours_per_week'],
                        'semester' => $gradeData['semester'],
                        'is_mandatory' => $gradeData['is_mandatory'] ?? true,
                    ]);
                }
            }

            DB::commit();

            $teacher->load(['user:id,name,email', 'academy:id,name', 'subjects:id,name', 'gradeLevels:id,name']);

            return $this->created($teacher, 'تم إنشاء المعلم الأكاديمي بنجاح');

        } catch (\Exception $e) {
            DB::rollBack();

            return $this->serverError('حدث خطأ أثناء إنشاء المعلم');
        }
    }

    /**
     * تحديث بيانات معلم أكاديمي
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $teacher = AcademicTeacherProfile::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'teacher_code' => 'nullable|string|max:50|unique:academic_teachers,teacher_code,'.$id,
                'education_level' => ['sometimes', new Enum(EducationalQualification::class)],
                'university' => 'sometimes|string|max:255',
                'graduation_year' => 'sometimes|integer|min:1950|max:'.(date('Y') + 1),
                'teaching_experience_years' => 'sometimes|integer|min:0|max:50',
                'certifications' => 'nullable|array',
                'certifications.*' => 'string|max:255',
                'languages' => 'sometimes|array|min:1',
                'languages.*' => 'string|in:arabic,english,french,spanish,other',
                'available_days' => 'sometimes|array|min:1',
                'available_days.*' => 'string|in:sunday,monday,tuesday,wednesday,thursday,friday,saturday',
                'available_time_start' => 'sometimes|date_format:H:i',
                'available_time_end' => 'sometimes|date_format:H:i|after:available_time_start',
                'session_price_individual' => 'sometimes|numeric|min:0',
                'min_session_duration' => 'sometimes|integer|min:15|max:120',
                'max_session_duration' => 'sometimes|integer|min:15|max:180',
                'max_students_per_group' => 'sometimes|integer|min:1|max:20',
                'bio_arabic' => 'sometimes|string|max:1000',
                'bio_english' => 'nullable|string|max:1000',
                'status' => 'sometimes|string|in:pending,approved,rejected,suspended',
                'is_active' => 'sometimes|boolean',
                'can_create_courses' => 'sometimes|boolean',
                'notes' => 'nullable|string|max:1000',
            ]);

            if ($validator->fails()) {
                return $this->validationError($validator->errors()->toArray(), 'بيانات غير صحيحة');
            }

            $teacher->update($request->except(['subjects', 'grade_levels']));

            // تحديث المواد إذا تم إرسالها
            if ($request->has('subjects')) {
                $teacher->subjects()->detach();
                foreach ($request->subjects as $subjectData) {
                    $teacher->subjects()->attach($subjectData['subject_id'], [
                        'proficiency_level' => $subjectData['proficiency_level'],
                        'years_experience' => $subjectData['years_experience'],
                        'is_primary' => $subjectData['is_primary'] ?? false,
                        'certification' => $subjectData['certification'] ?? null,
                    ]);
                }
            }

            // تحديث المراحل الدراسية إذا تم إرسالها
            if ($request->has('grade_levels')) {
                $teacher->gradeLevels()->detach();
                foreach ($request->grade_levels as $gradeData) {
                    $teacher->gradeLevels()->attach($gradeData['grade_level_id'], [
                        'hours_per_week' => $gradeData['hours_per_week'],
                        'semester' => $gradeData['semester'],
                        'is_mandatory' => $gradeData['is_mandatory'] ?? true,
                    ]);
                }
            }

            $teacher->load(['user:id,name,email', 'academy:id,name', 'subjects:id,name', 'gradeLevels:id,name']);

            return $this->success($teacher, 'تم تحديث بيانات المعلم بنجاح');

        } catch (\Exception $e) {
            return $this->serverError('حدث خطأ أثناء تحديث بيانات المعلم');
        }
    }

    /**
     * حذف معلم أكاديمي
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $teacher = AcademicTeacherProfile::findOrFail($id);

            // التحقق من عدم وجود جلسات نشطة
            $activeSessions = $teacher->privateSessions()
                ->where('status', SessionStatus::SCHEDULED->value)
                ->where('scheduled_date', '>=', now())
                ->count();

            if ($activeSessions > 0) {
                return $this->validationError([], 'لا يمكن حذف المعلم لوجود جلسات نشطة مبرمجة');
            }

            // التحقق من عدم وجود اشتراكات نشطة
            $activeSubscriptions = $teacher->subscriptions()
                ->where('status', SessionSubscriptionStatus::ACTIVE->value)
                ->count();

            if ($activeSubscriptions > 0) {
                return $this->validationError([], 'لا يمكن حذف المعلم لوجود اشتراكات نشطة');
            }

            DB::beginTransaction();

            // حذف العلاقات
            $teacher->subjects()->detach();
            $teacher->gradeLevels()->detach();
            $teacher->students()->detach();

            // حذف المعلم
            $teacher->delete();

            DB::commit();

            return $this->success(null, 'تم حذف المعلم بنجاح');

        } catch (\Exception $e) {
            DB::rollBack();

            return $this->serverError('حدث خطأ أثناء حذف المعلم');
        }
    }

    /**
     * الموافقة على معلم
     */
    public function approve(int $id): JsonResponse
    {
        try {
            $teacher = AcademicTeacherProfile::findOrFail($id);

            if ($teacher->is_approved) {
                return $this->validationError([], 'المعلم مُوافق عليه بالفعل');
            }

            // Activate the teacher's user account
            $teacher->user?->update(['active_status' => true]);

            return $this->success($teacher, 'تم الموافقة على المعلم بنجاح');

        } catch (\Exception $e) {
            return $this->serverError('حدث خطأ أثناء الموافقة على المعلم');
        }
    }

    /**
     * رفض معلم
     */
    public function reject(Request $request, int $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'rejection_reason' => 'required|string|max:500',
            ]);

            if ($validator->fails()) {
                return $this->validationError($validator->errors()->toArray(), 'يجب تحديد سبب الرفض');
            }

            $teacher = AcademicTeacherProfile::findOrFail($id);

            $teacher->update([
                'is_approved' => false,
                'status' => 'rejected',
                'notes' => $teacher->notes."\n\nسبب الرفض: ".$request->rejection_reason,
            ]);

            return $this->success($teacher, 'تم رفض المعلم بنجاح');

        } catch (\Exception $e) {
            return $this->serverError('حدث خطأ أثناء رفض المعلم');
        }
    }

    /**
     * تعليق معلم
     */
    public function suspend(Request $request, int $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'suspension_reason' => 'required|string|max:500',
                'suspension_duration_days' => 'nullable|integer|min:1|max:365',
            ]);

            if ($validator->fails()) {
                return $this->validationError($validator->errors()->toArray(), 'يجب تحديد سبب التعليق');
            }

            $teacher = AcademicTeacherProfile::findOrFail($id);

            $teacher->update([
                'status' => 'suspended',
                'is_active' => false,
                'notes' => $teacher->notes."\n\nسبب التعليق: ".$request->suspension_reason,
            ]);

            return $this->success($teacher, 'تم تعليق المعلم بنجاح');

        } catch (\Exception $e) {
            return $this->serverError('حدث خطأ أثناء تعليق المعلم');
        }
    }

    /**
     * إعادة تفعيل معلم
     */
    public function reactivate(int $id): JsonResponse
    {
        try {
            $teacher = AcademicTeacherProfile::findOrFail($id);

            // Reactivate the teacher's user account
            $teacher->user?->update(['active_status' => true]);

            return $this->success($teacher, 'تم إعادة تفعيل المعلم بنجاح');

        } catch (\Exception $e) {
            return $this->serverError('حدث خطأ أثناء إعادة تفعيل المعلم');
        }
    }

    /**
     * إحصائيات المعلم
     */
    public function statistics(int $id): JsonResponse
    {
        try {
            $teacher = AcademicTeacherProfile::findOrFail($id);

            $stats = [
                'total_sessions' => $teacher->privateSessions()->count(),
                'completed_sessions' => $teacher->privateSessions()->where('status', SessionStatus::COMPLETED->value)->count(),
                'cancelled_sessions' => $teacher->privateSessions()->where('status', SessionStatus::CANCELLED->value)->count(),
                'total_students' => $teacher->students()->count(),
                'active_subscriptions' => $teacher->subscriptions()->where('status', SessionSubscriptionStatus::ACTIVE->value)->count(),
                'total_courses' => $teacher->interactiveCourses()->count(),
                'published_courses' => $teacher->interactiveCourses()->where('is_published', true)->count(),
                'average_rating' => $teacher->rating,
                'total_earnings' => $teacher->subscriptions()
                    ->where('payment_status', 'paid')
                    ->sum('final_monthly_amount'),
                'monthly_earnings' => $teacher->subscriptions()
                    ->where('payment_status', 'paid')
                    ->whereMonth('last_payment_date', now()->month)
                    ->sum('final_monthly_amount'),
            ];

            return $this->success($stats, 'تم جلب إحصائيات المعلم بنجاح');

        } catch (\Exception $e) {
            return $this->serverError('حدث خطأ أثناء جلب إحصائيات المعلم');
        }
    }

    /**
     * البحث عن معلمين متاحين
     */
    public function searchAvailable(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'subject_id' => 'required|exists:academic_subjects,id',
                'grade_level_id' => 'required|exists:academic_grade_levels,id',
                'preferred_days' => 'nullable|array',
                'preferred_days.*' => 'string|in:sunday,monday,tuesday,wednesday,thursday,friday,saturday',
                'preferred_time_start' => 'nullable|date_format:H:i',
                'preferred_time_end' => 'nullable|date_format:H:i',
                'max_price' => 'nullable|numeric|min:0',
            ]);

            if ($validator->fails()) {
                return $this->validationError($validator->errors()->toArray(), 'بيانات غير صحيحة');
            }

            $query = AcademicTeacherProfile::with(['user:id,name,email,avatar', 'subjects:id,name'])
                ->whereHas('user', fn ($q) => $q->where('active_status', true));

            // فلترة حسب المادة
            $query->whereHas('subjects', function ($q) use ($request) {
                $q->where('academic_subjects.id', $request->subject_id);
            });

            // فلترة حسب المرحلة الدراسية
            $query->whereHas('gradeLevels', function ($q) use ($request) {
                $q->where('academic_grade_levels.id', $request->grade_level_id);
            });

            // فلترة حسب الأيام المفضلة
            if ($request->has('preferred_days')) {
                $query->whereJsonContains('available_days', $request->preferred_days);
            }

            // فلترة حسب السعر
            if ($request->has('max_price')) {
                $query->where('session_price_individual', '<=', $request->max_price);
            }

            // ترتيب حسب التقييم والسعر
            $query->orderBy('rating', 'desc')
                ->orderBy('session_price_individual', 'asc');

            $teachers = $query->paginate($request->get('per_page', 10));

            return $this->success($teachers, 'تم البحث عن المعلمين المتاحين بنجاح');

        } catch (\Exception $e) {
            return $this->serverError('حدث خطأ أثناء البحث عن المعلمين');
        }
    }
}
