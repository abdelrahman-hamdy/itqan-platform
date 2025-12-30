<?php

namespace App\Http\Controllers;

use App\Models\AcademicGradeLevel;
use App\Models\AcademicSubject;
use App\Models\Academy;
use App\Models\CourseSubscription;
use App\Models\RecordedCourse;
use App\Models\StudentProgress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Enums\SessionStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\SubscriptionStatus;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use App\Http\Traits\Api\ApiResponses;
use App\Http\Requests\StoreRecordedCourseRequest;

class RecordedCourseController extends Controller
{
    use ApiResponses;
    /**
     * Display a listing of courses for the current academy
     */
    public function index(Request $request): View
    {
        $academy = $this->getCurrentAcademy();
        $user = Auth::user();

        $query = RecordedCourse::where('academy_id', $academy->id)
            ->published()
            ->with(['subject', 'gradeLevel', 'academy'])
            ->withCount(['lessons as total_lessons' => function ($query) {
                $query->where('is_published', true);
            }]);

        // If user is authenticated and is a student, load enrollments
        if ($user && $user->user_type === 'student') {
            $query->with(['enrollments' => function ($enrollmentQuery) use ($user) {
                $enrollmentQuery->where('student_id', $user->id);
            }]);
        }

        // Apply filters
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'LIKE', '%'.$request->search.'%')
                    ->orWhere('description', 'LIKE', '%'.$request->search.'%')
                    ->orWhere('tags', 'LIKE', '%'.$request->search.'%');
            });
        }

        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->subject_id);
        }

        if ($request->filled('grade_level_id')) {
            $query->where('grade_level_id', $request->grade_level_id);
        }

        if ($request->filled('level')) {
            $query->where('difficulty_level', $request->level);
        }

        // Sorting
        $sortBy = $request->get('sort', 'created_at');
        $sortOrder = $request->get('order', 'desc');

        switch ($sortBy) {
            case 'rating':
                $query->orderBy('avg_rating', $sortOrder);
                break;
            case 'enrollments':
                $query->orderBy('total_enrollments', $sortOrder);
                break;
            case 'price':
                $query->orderBy('price', $sortOrder);
                break;
            default:
                $query->orderBy($sortBy, $sortOrder);
        }

        $courses = $query->paginate(12);

        // Get filter options
        $subjects = AcademicSubject::where('academy_id', $academy->id)
            ->whereHas('recordedCourses', function ($query) {
                $query->where('is_published', true);
            })
            ->orderBy('name')
            ->get();

        $gradeLevels = AcademicGradeLevel::where('academy_id', $academy->id)
            ->whereHas('recordedCourses', function ($query) {
                $query->where('is_published', true);
            })
            ->orderBy('name')
            ->get();

        $levels = RecordedCourse::where('academy_id', $academy->id)
            ->where('is_published', true)
            ->distinct()
            ->pluck('difficulty_level')
            ->filter()
            ->sort();

        // Use public view for unified public/authenticated experience
        return view('public.recorded-courses.index', compact('courses', 'subjects', 'gradeLevels', 'levels', 'academy'));
    }

    /**
     * Show the form for creating a new course
     */
    public function create(): View
    {
        $this->authorize('create', RecordedCourse::class);

        $academy = $this->getCurrentAcademy();
        $subjects = AcademicSubject::where('academy_id', $academy->id)->get();
        $gradeLevels = AcademicGradeLevel::where('academy_id', $academy->id)->get();

        return view('courses.create', compact('subjects', 'gradeLevels', 'academy'));
    }

    /**
     * Store a newly created course
     */
    public function store(StoreRecordedCourseRequest $request): RedirectResponse
    {
        $this->authorize('create', RecordedCourse::class);

        $validated = $request->validated();

        $academy = $this->getCurrentAcademy();

        $course = RecordedCourse::create(array_merge($validated, [
            'academy_id' => $academy->id,
            'course_code' => $this->generateCourseCode($academy),
            'currency' => $academy->default_currency ?? 'SAR',
            'status' => 'draft',
            'created_by' => Auth::id(),
        ]));

        return redirect()
            ->route('courses.show', $course)
            ->with('success', 'تم إنشاء الدورة بنجاح');
    }

    /**
     * Display the specified course (Public Access - ID based)
     */
    public function show($subdomain, $courseId): View
    {
        $academy = $this->getCurrentAcademy();

        // Find course by ID and ensure it belongs to current academy
        $course = RecordedCourse::where('id', $courseId)
            ->where('academy_id', $academy->id)
            ->where('is_published', true)
            ->firstOrFail();

        $course->load([
            'subject',
            'gradeLevel',
            'academy',
            'sections' => function ($query) {
                $query->where('is_published', true)->orderBy('order');
            },
            'sections.lessons' => function ($query) {
                $query->where('is_published', true)->orderBy('id');
            },
            'reviews' => function ($query) {
                $query->with('user:id,name')->latest()->take(5);
            },
        ])
            ->loadCount([
                'lessons as total_lessons' => function ($query) {
                    $query->where('is_published', true);
                },
                'enrollments as active_enrollments' => function ($query) {
                    $query->where('status', EnrollmentStatus::ACTIVE->value);
                },
            ]);

        $user = Auth::user();
        $isEnrolled = false;
        $enrollment = null;
        $canEnroll = $course->canEnroll();
        $isAuthenticated = (bool) $user;

        if ($user) {
            $enrollment = CourseSubscription::where('student_id', $user->id)
                ->where('recorded_course_id', $course->id)
                ->where('status', EnrollmentStatus::ACTIVE->value)
                ->first();

            $isEnrolled = (bool) $enrollment;
        }

        // Get related courses with eager loading
        $relatedCourses = RecordedCourse::where('academy_id', $course->academy_id)
            ->where('id', '!=', $course->id)
            ->where(function ($query) use ($course) {
                $query->where('subject_id', $course->subject_id)
                    ->orWhere('category', $course->category)
                    ->orWhere('difficulty_level', $course->difficulty_level);
            })
            ->published()
            ->with(['subject', 'gradeLevel'])
            ->withCount(['lessons as total_lessons' => function ($query) {
                $query->where('is_published', true);
            }])
            ->take(4)
            ->get();

        return view('courses.show', compact(
            'course',
            'academy',
            'isEnrolled',
            'enrollment',
            'canEnroll',
            'relatedCourses',
            'isAuthenticated'
        ));
    }

    /**
     * Enroll user in a course
     */
    public function enroll(Request $request, $subdomain, $courseId): RedirectResponse
    {
        if (! Auth::check()) {
            return redirect()->route('login', ['subdomain' => $subdomain])
                ->with('message', 'يجب تسجيل الدخول أولاً للتسجيل في الدورة');
        }

        $academy = $this->getCurrentAcademy();
        $course = RecordedCourse::where('id', $courseId)
            ->where('academy_id', $academy->id)
            ->where('is_published', true)
            ->firstOrFail();

        $user = Auth::user();

        // Check if user is already enrolled
        $existingEnrollment = CourseSubscription::where('student_id', $user->id)
            ->where('recorded_course_id', $course->id)
            ->where('status', EnrollmentStatus::ACTIVE->value)
            ->first();

        if ($existingEnrollment) {
            return redirect()->back()
                ->with('error', 'أنت مسجل بالفعل في هذه الدورة');
        }

        if (! $course->canEnroll()) {
            return redirect()->back()
                ->with('error', 'لا يمكن التسجيل في هذه الدورة حالياً');
        }

        DB::transaction(function () use ($course, $user) {
            $enrollmentData = [
                'academy_id' => $course->academy_id,
                'student_id' => $user->id,
                'recorded_course_id' => $course->id,
                'subscription_code' => $this->generateSubscriptionCode($course),
                'enrollment_type' => 'free', // Bypass payment for now
                'payment_type' => 'one_time',
                'price_paid' => 0, // Free enrollment
                'original_price' => $course->price,
                'discount_amount' => $course->price, // Full discount
                'currency' => $course->academy?->currency ?? 'SAR',
                'payment_status' => 'paid', // Mark as paid to bypass payment
                'access_type' => 'lifetime',
                'lifetime_access' => true,
                'certificate_eligible' => true, // Default to true
                'status' => EnrollmentStatus::ACTIVE->value,
                'enrolled_at' => now(),
                'created_by' => $user->id,
            ];

            CourseSubscription::create($enrollmentData);
        });

        // Always redirect to learning page since payment is bypassed
        return redirect()->route('courses.learn', ['subdomain' => $subdomain, 'id' => $course->id])
            ->with('success', 'تم تسجيلك في الدورة بنجاح!');
    }

    /**
     * API endpoint for course enrollment (returns JSON)
     */
    public function enrollApi(Request $request, $subdomain, $courseId): JsonResponse
    {
        $this->authorize('enroll', \App\Models\CourseSubscription::class);

        $academy = $this->getCurrentAcademy();
        $course = RecordedCourse::where('id', $courseId)
            ->where('academy_id', $academy->id)
            ->where('is_published', true)
            ->first();

        if (! $course) {
            return $this->notFound('الدورة غير موجودة');
        }

        $user = Auth::user();

        // Check if user is already enrolled
        $existingEnrollment = CourseSubscription::where('student_id', $user->id)
            ->where('recorded_course_id', $course->id)
            ->where('status', EnrollmentStatus::ACTIVE->value)
            ->first();

        if ($existingEnrollment) {
            return $this->error('أنت مسجل بالفعل في هذه الدورة', 400);
        }

        if (! $course->canEnroll()) {
            return $this->error('لا يمكن التسجيل في هذه الدورة حالياً', 400);
        }

        try {
            DB::transaction(function () use ($course, $user) {
                $enrollmentData = [
                    'academy_id' => $course->academy_id,
                    'student_id' => $user->id,
                    'recorded_course_id' => $course->id,
                    'subscription_code' => $this->generateSubscriptionCode($course),
                    'enrollment_type' => 'free', // Bypass payment for now
                    'payment_type' => 'one_time',
                    'price_paid' => 0, // Free enrollment
                    'original_price' => $course->price,
                    'discount_amount' => $course->price, // Full discount
                    'currency' => $course->academy?->currency ?? 'SAR',
                    'payment_status' => 'paid', // Mark as paid to bypass payment
                    'access_type' => 'lifetime',
                    'lifetime_access' => true,
                    'certificate_eligible' => true, // Default to true
                    'status' => EnrollmentStatus::ACTIVE->value,
                    'enrolled_at' => now(),
                    'created_by' => $user->id,
                ];

                CourseSubscription::create($enrollmentData);
            });

            return $this->success([
                'success' => true,
                'message' => 'تم تسجيلك في الدورة بنجاح!',
                'data' => null,
                'redirect_url' => route('courses.learn', ['subdomain' => $subdomain, 'id' => $course->id]),
            ], true, 200);

        } catch (\Exception $e) {
            \Log::error('Course enrollment error: '.$e->getMessage(), [
                'course_id' => $course->id,
                'user_id' => $user->id,
                'error' => $e->getTraceAsString(),
            ]);

            $errorData = [
                'success' => false,
                'message' => 'حدث خطأ أثناء التسجيل. يرجى المحاولة مرة أخرى',
                'data' => null,
            ];

            if (app()->environment('local')) {
                $errorData['debug'] = $e->getMessage();
            }

            return $this->success($errorData, false, 500);
        }
    }

    /**
     * Get course progress
     */
    public function getProgress($courseId): JsonResponse
    {
        $course = RecordedCourse::withCount(['lessons as total_lessons' => function ($query) {
            $query->where('is_published', true);
        }])->findOrFail($courseId);

        $user = Auth::user();

        if (! $user) {
            return $this->unauthorized('Unauthorized');
        }

        $totalLessons = $course->total_lessons;
        $completedLessons = StudentProgress::where('user_id', $user->id)
            ->where('recorded_course_id', $courseId)
            ->where('is_completed', true)
            ->count();

        $progressPercentage = $totalLessons > 0 ? ($completedLessons / $totalLessons) * 100 : 0;

        return $this->success([
            'progress_percentage' => $progressPercentage,
            'completed_lessons' => $completedLessons,
            'total_lessons' => $totalLessons,
        ]);
    }

    /**
     * Show course learning interface
     */
    public function learn($subdomain, $courseId): View|RedirectResponse
    {
        if (! Auth::check()) {
            return redirect()->route('login', ['subdomain' => $subdomain]);
        }

        $academy = $this->getCurrentAcademy();
        $course = RecordedCourse::where('id', $courseId)
            ->where('academy_id', $academy->id)
            ->where('is_published', true)
            ->firstOrFail();

        $user = Auth::user();
        $enrollment = CourseSubscription::where('student_id', $user->id)
            ->where('recorded_course_id', $course->id)
            ->where('status', EnrollmentStatus::ACTIVE->value)
            ->first();

        if (! $enrollment) {
            return redirect()->route('courses.show', ['subdomain' => $subdomain, 'id' => $course->id])
                ->with('error', 'يجب التسجيل في الدورة أولاً');
        }

        $course->load([
            'sections' => function ($query) {
                $query->where('is_published', true)->orderBy('order');
            },
            'sections.lessons' => function ($query) {
                $query->where('is_published', true)->orderBy('id');
            },
        ])
            ->loadCount([
                'lessons as total_lessons' => function ($query) {
                    $query->where('is_published', true);
                },
            ]);

        // Get user's progress from StudentProgress table
        $totalLessons = $course->lessons()->where('is_published', true)->count();
        $completedLessons = StudentProgress::where('user_id', $user->id)
            ->where('recorded_course_id', $course->id)
            ->where('is_completed', true)
            ->count();

        return view('courses.learn', compact('course', 'academy', 'enrollment', 'totalLessons', 'completedLessons'));
    }

    /**
     * Show course checkout page
     */
    public function checkout($subdomain, $courseId): View|RedirectResponse
    {
        if (! Auth::check()) {
            return redirect()->route('login', ['subdomain' => $subdomain]);
        }

        $academy = $this->getCurrentAcademy();
        $course = RecordedCourse::where('id', $courseId)
            ->where('academy_id', $academy->id)
            ->where('is_published', true)
            ->firstOrFail();

        if ($course->is_free) {
            return redirect()->route('courses.enroll', ['subdomain' => $subdomain, 'id' => $course->id]);
        }

        $user = Auth::user();
        $enrollment = CourseSubscription::where('student_id', $user->id)
            ->where('recorded_course_id', $course->id)
            ->whereIn('status', [SubscriptionStatus::ACTIVE->value, SubscriptionStatus::PENDING->value])
            ->first();

        if (! $enrollment) {
            return redirect()->route('courses.show', ['subdomain' => $subdomain, 'id' => $course->id])
                ->with('error', 'يرجى التسجيل في الدورة أولاً');
        }

        return view('courses.checkout', compact('course', 'academy', 'enrollment'));
    }

    /**
     * Get current academy from subdomain
     */
    private function getCurrentAcademy()
    {
        $subdomain = request()->route('subdomain') ?? 'itqan-academy';

        return Academy::where('subdomain', $subdomain)->firstOrFail();
    }

    /**
     * Generate unique course code
     */
    private function generateCourseCode(Academy $academy)
    {
        $prefix = strtoupper(substr($academy->subdomain, 0, 3));
        $number = RecordedCourse::where('academy_id', $academy->id)->count() + 1;

        return $prefix.'-CRS-'.str_pad($number, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Generate unique subscription code
     */
    private function generateSubscriptionCode(RecordedCourse $course)
    {
        return 'SUB-'.$course->academy_id.'-'.$course->id.'-'.time();
    }
}
