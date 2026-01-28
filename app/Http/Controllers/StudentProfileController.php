<?php

namespace App\Http\Controllers;

use App\Helpers\CountryList;
use App\Http\Requests\UpdateStudentProfileRequest;
use App\Services\Student\StudentAcademicService;
use App\Services\StudentDashboardService;
use App\Services\StudentProfileService;
use App\Services\StudentStatisticsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class StudentProfileController extends Controller
{
    public function __construct(
        protected StudentDashboardService $dashboardService,
        protected StudentStatisticsService $statisticsService,
        protected StudentProfileService $profileService,
        protected StudentAcademicService $academicService
    ) {}

    public function index(): View
    {
        $user = Auth::user();

        // Load dashboard data using service
        $dashboardData = $this->dashboardService->loadDashboardData($user);

        // Get academic private sessions with recent sessions using service
        $academicPrivateSessions = $this->academicService->getSubscriptionsWithRecentSessions($user, 5);

        // Calculate statistics using service
        $stats = $this->statisticsService->calculate($user);

        return view('student.profile', [
            'quranCircles' => $dashboardData['circles'],
            'quranPrivateSessions' => $dashboardData['privateSessions'],
            'quranTrialRequests' => $dashboardData['trialRequests'],
            'interactiveCourses' => $dashboardData['interactiveCourses'],
            'recordedCourses' => $dashboardData['recordedCourses'],
            'academicPrivateSessions' => $academicPrivateSessions,
            'stats' => $stats,
        ]);
    }

    public function edit(): View|RedirectResponse
    {
        $user = Auth::user();
        $studentProfile = $this->profileService->getOrCreateProfile($user);

        if (! $studentProfile) {
            return redirect()->route('student.profile')
                ->with('error', 'لم يتم العثور على الملف الشخصي للطالب. يرجى التواصل مع الدعم الفني.');
        }

        $this->authorize('view', $studentProfile);

        $gradeLevels = $this->profileService->getGradeLevels($user);
        $countries = CountryList::toSelectArray();

        return view('student.edit-profile', compact('studentProfile', 'gradeLevels', 'countries'));
    }

    public function update(UpdateStudentProfileRequest $request): RedirectResponse
    {
        $user = Auth::user();
        $studentProfile = $this->profileService->getOrCreateProfile($user);

        if (! $studentProfile) {
            return redirect()->back()
                ->with('error', 'لم يتم العثور على الملف الشخصي للطالب. يرجى التواصل مع الدعم الفني.');
        }

        $this->authorize('update', $studentProfile);

        $validated = $request->validated();

        $this->profileService->updateProfile(
            $studentProfile,
            $validated,
            $request->file('avatar')
        );

        return redirect()->route('student.profile', ['subdomain' => $user->academy->subdomain ?? 'itqan-academy'])
            ->with('success', 'تم تحديث الملف الشخصي بنجاح');
    }
}
