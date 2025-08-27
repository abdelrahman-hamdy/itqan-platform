<?php

namespace App\Http\Controllers;

use App\Models\AcademicGradeLevel;
use App\Models\AcademicSubject;
use App\Models\Academy;
use App\Models\CourseSubscription;
use App\Models\RecordedCourse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RecordedCourseController extends Controller
{
    /**
     * Display a listing of courses for the current academy
     */
    public function index(Request $request)
    {
        $academy = $this->getCurrentAcademy();
        $user = Auth::user();

        $query = RecordedCourse::where('academy_id', $academy->id)
            ->published()
            ->with(['subject', 'gradeLevel']);

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

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('level')) {
            $query->where('difficulty_level', $request->level);
        }

        if ($request->filled('price')) {
            if ($request->price === 'free') {
                $query->where('price', 0);
            } elseif ($request->price === 'paid') {
                $query->where('price', '>', 0);
            }
        }

        // Student-specific filters
        if ($user && $user->user_type === 'student' && $request->filled('status')) {
            if ($request->status === 'enrolled') {
                $query->whereHas('enrollments', function ($enrollmentQuery) use ($user) {
                    $enrollmentQuery->where('student_id', $user->id);
                });
            } elseif ($request->status === 'not_enrolled') {
                $query->whereDoesntHave('enrollments', function ($enrollmentQuery) use ($user) {
                    $enrollmentQuery->where('student_id', $user->id);
                });
            }
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
        $categories = RecordedCourse::where('academy_id', $academy->id)
            ->where('is_published', true)
            ->distinct()
            ->pluck('category')
            ->filter()
            ->sort();

        $levels = RecordedCourse::where('academy_id', $academy->id)
            ->where('is_published', true)
            ->distinct()
            ->pluck('difficulty_level')
            ->filter()
            ->sort();

        return view('courses.index', compact('courses', 'categories', 'levels', 'academy'));
    }

    /**
     * Show the form for creating a new course
     */
    public function create()
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
    public function store(Request $request)
    {
        $this->authorize('create', RecordedCourse::class);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'title_en' => 'nullable|string|max:255',
            'description' => 'required|string',
            'description_en' => 'nullable|string',

            'subject_id' => 'nullable|exists:academic_subjects,id',
            'grade_level_id' => 'nullable|exists:academic_grade_levels,id',

            'price' => 'required|numeric|min:0',
            'discount_price' => 'nullable|numeric|min:0|lt:price',

            'prerequisites' => 'nullable|array',
            'learning_outcomes' => 'nullable|array',
            'category' => 'nullable|string|max:100',
            'tags' => 'nullable|array',
            'difficulty_level' => 'required|in:easy,medium,hard',
            'thumbnail_url' => 'nullable|url',
        ]);

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
    public function show($subdomain, $courseId)
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
            'sections.lessons' => function ($query) {
                $query->where('is_published', true)->orderBy('id');
            },
            'reviews' => function ($query) {
                $query->latest()->take(5);
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
                ->where('status', 'active')
                ->first();

            $isEnrolled = (bool) $enrollment;
        }

        // Get related courses
        $relatedCourses = RecordedCourse::where('academy_id', $course->academy_id)
            ->where('id', '!=', $course->id)
            ->where(function ($query) use ($course) {
                $query->where('subject_id', $course->subject_id)
                    ->orWhere('category', $course->category)
                    ->orWhere('difficulty_level', $course->difficulty_level);
            })
            ->published()
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
    public function enroll(Request $request, $subdomain, $courseId)
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
            ->where('status', 'active')
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
                'enrollment_type' => $course->is_free ? 'free' : 'paid',
                'payment_type' => 'one_time', // Fixed: use valid enum value
                'price_paid' => $course->discount_price ?? $course->price,
                'original_price' => $course->price,
                'discount_amount' => $course->discount_price ? ($course->price - $course->discount_price) : 0,
                'currency' => $course->currency,
                'payment_status' => $course->is_free ? 'paid' : 'pending', // Fixed: use valid enum value
                'access_type' => 'lifetime', // Fixed: use valid enum value
                'lifetime_access' => true,
                'certificate_eligible' => $course->completion_certificate,
                'status' => 'active',
                'enrolled_at' => now(),
                'created_by' => $user->id,
            ];

            CourseSubscription::create($enrollmentData);
        });

        if ($course->is_free) {
            return redirect()->route('courses.learn', ['subdomain' => $subdomain, 'id' => $course->id])
                ->with('success', 'تم تسجيلك في الدورة بنجاح!');
        } else {
            return redirect()->route('courses.checkout', ['subdomain' => $subdomain, 'id' => $course->id])
                ->with('message', 'يرجى إكمال عملية الدفع لتأكيد التسجيل');
        }
    }

    /**
     * API endpoint for course enrollment (returns JSON)
     */
    public function enrollApi(Request $request, $subdomain, $courseId)
    {
        if (! Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'يجب تسجيل الدخول أولاً للتسجيل في الدورة',
            ], 401);
        }

        $academy = $this->getCurrentAcademy();
        $course = RecordedCourse::where('id', $courseId)
            ->where('academy_id', $academy->id)
            ->where('is_published', true)
            ->first();

        if (! $course) {
            return response()->json([
                'success' => false,
                'message' => 'الدورة غير موجودة',
            ], 404);
        }

        $user = Auth::user();

        // Check if user is already enrolled
        $existingEnrollment = CourseSubscription::where('student_id', $user->id)
            ->where('recorded_course_id', $course->id)
            ->where('status', 'active')
            ->first();

        if ($existingEnrollment) {
            return response()->json([
                'success' => false,
                'message' => 'أنت مسجل بالفعل في هذه الدورة',
            ], 400);
        }

        if (! $course->canEnroll()) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن التسجيل في هذه الدورة حالياً',
            ], 400);
        }

        try {
            DB::transaction(function () use ($course, $user) {
                $enrollmentData = [
                    'academy_id' => $course->academy_id,
                    'student_id' => $user->id,
                    'recorded_course_id' => $course->id,
                    'subscription_code' => $this->generateSubscriptionCode($course),
                    'enrollment_type' => $course->is_free ? 'free' : 'paid',
                    'payment_type' => 'one_time', // Fixed: use valid enum value
                    'price_paid' => $course->discount_price ?? $course->price,
                    'original_price' => $course->price,
                    'discount_amount' => $course->discount_price ? ($course->price - $course->discount_price) : 0,
                    'currency' => $course->currency,
                    'payment_status' => $course->is_free ? 'paid' : 'pending', // Fixed: use valid enum value
                    'access_type' => 'lifetime', // Fixed: use valid enum value
                    'lifetime_access' => true,
                    'certificate_eligible' => $course->completion_certificate,
                    'status' => 'active',
                    'enrolled_at' => now(),
                    'created_by' => $user->id,
                ];

                CourseSubscription::create($enrollmentData);
            });

            return response()->json([
                'success' => true,
                'message' => 'تم تسجيلك في الدورة بنجاح!',
                'redirect_url' => $course->is_free
                    ? route('courses.learn', ['subdomain' => $subdomain, 'id' => $course->id])
                    : route('courses.checkout', ['subdomain' => $subdomain, 'id' => $course->id]),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء التسجيل. يرجى المحاولة مرة أخرى',
            ], 500);
        }
    }

    /**
     * Show course learning interface
     */
    public function learn($subdomain, $courseId)
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
            ->where('status', 'active')
            ->first();

        if (! $enrollment) {
            return redirect()->route('courses.show', ['subdomain' => $subdomain, 'id' => $course->id])
                ->with('error', 'يجب التسجيل في الدورة أولاً');
        }

        $course->load([
            'sections.lessons' => function ($query) {
                $query->where('is_published', true)->orderBy('id');
            },
        ]);

        // Get user's progress (simplified for now)
        $totalLessons = $course->lessons()->where('is_published', true)->count();
        $completedLessons = $enrollment->completed_lessons ?? 0;

        return view('courses.learn', compact('course', 'academy', 'enrollment', 'totalLessons', 'completedLessons'));
    }

    /**
     * Show course checkout page
     */
    public function checkout($subdomain, $courseId)
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
            ->whereIn('status', ['active', 'pending'])
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
