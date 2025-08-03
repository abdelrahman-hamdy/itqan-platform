<?php

namespace App\Http\Controllers;

use App\Models\RecordedCourse;
use App\Models\CourseSubscription;
use App\Models\Academy;
use App\Models\AcademicSubject;
use App\Models\AcademicGradeLevel;
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
        
        $query = RecordedCourse::where('academy_id', $academy->id)
            ->published()
            ->with(['instructor', 'subject', 'gradeLevel']);

        // Apply filters
        if ($request->filled('subject')) {
            $query->where('subject_id', $request->subject);
        }

        if ($request->filled('level')) {
            $query->where('level', $request->level);
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('free_only')) {
            $query->free();
        }

        if ($request->filled('featured')) {
            $query->featured();
        }

        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('title', 'LIKE', '%' . $request->search . '%')
                  ->orWhere('description', 'LIKE', '%' . $request->search . '%')
                  ->orWhere('tags', 'LIKE', '%' . $request->search . '%');
            });
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
        $subjects = AcademicSubject::where('academy_id', $academy->id)->get();
        $gradeLevels = AcademicGradeLevel::where('academy_id', $academy->id)->get();
        
        return view('courses.index', compact('courses', 'subjects', 'gradeLevels', 'academy'));
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
        $instructors = $academy->academicTeachers()->where('status', 'active')->get();
        
        return view('courses.create', compact('subjects', 'gradeLevels', 'instructors', 'academy'));
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
            'instructor_id' => 'required|exists:academic_teachers,id',
            'subject_id' => 'nullable|exists:academic_subjects,id',
            'grade_level_id' => 'nullable|exists:academic_grade_levels,id',
            'level' => 'required|in:beginner,intermediate,advanced',
            'price' => 'required|numeric|min:0',
            'discount_price' => 'nullable|numeric|min:0|lt:price',
            'is_free' => 'boolean',
            'completion_certificate' => 'boolean',
            'prerequisites' => 'nullable|array',
            'learning_outcomes' => 'nullable|array',
            'category' => 'nullable|string|max:100',
            'tags' => 'nullable|array',
            'difficulty_level' => 'required|in:very_easy,easy,medium,hard,very_hard',
            'thumbnail_url' => 'nullable|url',
            'trailer_video_url' => 'nullable|url'
        ]);

        $academy = $this->getCurrentAcademy();
        
        $course = RecordedCourse::create(array_merge($validated, [
            'academy_id' => $academy->id,
            'course_code' => $this->generateCourseCode($academy),
            'currency' => $academy->default_currency ?? 'SAR',
            'status' => 'draft',
            'created_by' => Auth::id()
        ]));

        return redirect()
            ->route('courses.show', $course)
            ->with('success', 'تم إنشاء الدورة بنجاح');
    }

    /**
     * Display the specified course
     */
    public function show(RecordedCourse $course)
    {
        $course->load([
            'instructor',
            'subject', 
            'gradeLevel',
            'sections.lessons',
            'reviews' => function($query) {
                $query->latest()->take(5);
            }
        ]);

        $user = Auth::user();
        $isEnrolled = false;
        $enrollment = null;
        $canEnroll = $course->canEnroll();

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
            ->where(function($query) use ($course) {
                $query->where('subject_id', $course->subject_id)
                      ->orWhere('category', $course->category)
                      ->orWhere('level', $course->level);
            })
            ->published()
            ->take(4)
            ->get();

        return view('courses.show', compact('course', 'isEnrolled', 'enrollment', 'canEnroll', 'relatedCourses'));
    }

    /**
     * Enroll user in a course
     */
    public function enroll(Request $request, RecordedCourse $course)
    {
        if (!Auth::check()) {
            $subdomain = request()->route('subdomain') ?? 'itqan-academy';
            return redirect()->route('login', ['subdomain' => $subdomain])
                ->with('message', 'يجب تسجيل الدخول أولاً للتسجيل في الدورة');
        }

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

        if (!$course->canEnroll()) {
            return redirect()->back()
                ->with('error', 'لا يمكن التسجيل في هذه الدورة حالياً');
        }

        DB::transaction(function() use ($course, $user) {
            $enrollmentData = [
                'academy_id' => $course->academy_id,
                'student_id' => $user->id,
                'recorded_course_id' => $course->id,
                'subscription_code' => $this->generateSubscriptionCode($course),
                'enrollment_type' => $course->is_free ? 'free' : 'paid',
                'payment_type' => $course->is_free ? 'free' : 'pending',
                'price_paid' => $course->discount_price ?? $course->price,
                'original_price' => $course->price,
                'discount_amount' => $course->discount_price ? ($course->price - $course->discount_price) : 0,
                'currency' => $course->currency,
                'payment_status' => $course->is_free ? 'free' : 'pending',
                'access_type' => 'full',
                'lifetime_access' => true,
                'certificate_eligible' => $course->completion_certificate,
                'status' => 'active',
                'enrolled_at' => now(),
                'created_by' => $user->id
            ];

            if ($course->is_free) {
                $enrollmentData['expires_at'] = null;
                $enrollmentData['payment_status'] = 'free';
            }

            CourseSubscription::createEnrollment($enrollmentData);
        });

        if ($course->is_free) {
            return redirect()->route('courses.learn', $course)
                ->with('success', 'تم تسجيلك في الدورة بنجاح!');
        } else {
            return redirect()->route('courses.checkout', $course)
                ->with('message', 'يرجى إكمال عملية الدفع لتأكيد التسجيل');
        }
    }

    /**
     * Show course learning interface
     */
    public function learn(RecordedCourse $course)
    {
        if (!Auth::check()) {
            $subdomain = request()->route('subdomain') ?? 'itqan-academy';
            return redirect()->route('login', ['subdomain' => $subdomain]);
        }

        $user = Auth::user();
        $enrollment = CourseSubscription::where('student_id', $user->id)
            ->where('recorded_course_id', $course->id)
            ->where('status', 'active')
            ->first();

        if (!$enrollment) {
            return redirect()->route('courses.show', $course)
                ->with('error', 'يجب التسجيل في الدورة أولاً');
        }

        $course->load([
            'sections.lessons' => function($query) {
                $query->published()->ordered();
            },
            'instructor'
        ]);

        // Get user's progress
        $enrollment->updateProgress();
        
        // Get next lesson to continue
        $nextLesson = $enrollment->getNextLesson();
        
        return view('courses.learn', compact('course', 'enrollment', 'nextLesson'));
    }

    /**
     * Show course checkout page
     */
    public function checkout(RecordedCourse $course)
    {
        if (!Auth::check()) {
            $subdomain = request()->route('subdomain') ?? 'itqan-academy';
            return redirect()->route('login', ['subdomain' => $subdomain]);
        }

        if ($course->is_free) {
            return redirect()->route('courses.enroll', $course);
        }

        $user = Auth::user();
        $enrollment = CourseSubscription::where('student_id', $user->id)
            ->where('recorded_course_id', $course->id)
            ->whereIn('status', ['active', 'pending'])
            ->first();

        if (!$enrollment) {
            return redirect()->route('courses.show', $course)
                ->with('error', 'يرجى التسجيل في الدورة أولاً');
        }

        return view('courses.checkout', compact('course', 'enrollment'));
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
        return $prefix . '-CRS-' . str_pad($number, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Generate unique subscription code
     */
    private function generateSubscriptionCode(RecordedCourse $course)
    {
        return 'SUB-' . $course->academy_id . '-' . $course->id . '-' . time();
    }
} 