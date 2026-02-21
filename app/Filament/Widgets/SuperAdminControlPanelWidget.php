<?php

namespace App\Filament\Widgets;

use App\Enums\BusinessRequestStatus;
use App\Enums\HomeworkSubmissionStatus;
use App\Enums\PaymentStatus;
use App\Enums\SessionRequestStatus;
use App\Enums\SessionStatus;
use App\Enums\SessionSubscriptionStatus;
use App\Enums\TrialRequestStatus;
use App\Enums\UserType;
use App\Filament\Resources\AcademicPackageResource;
use App\Filament\Resources\AcademicSessionResource;
use App\Filament\Resources\AcademicSubscriptionResource;
use App\Filament\Resources\AcademicTeacherProfileResource;
use App\Filament\Resources\BusinessServiceRequestResource;
use App\Filament\Resources\InteractiveCourseResource;
use App\Filament\Resources\InteractiveCourseSessionResource;
use App\Filament\Resources\ParentProfileResource;
use App\Filament\Resources\PaymentResource;
use App\Filament\Resources\QuranCircleResource;
use App\Filament\Resources\QuranIndividualCircleResource;
use App\Filament\Resources\QuranPackageResource;
use App\Filament\Resources\QuranSessionResource;
use App\Filament\Resources\QuranSubscriptionResource;
use App\Filament\Resources\QuranTeacherProfileResource;
use App\Filament\Resources\QuranTrialRequestResource;
use App\Filament\Resources\RecordedCourseResource;
use App\Filament\Resources\StudentProfileResource;
use App\Filament\Resources\TeacherReviewResource;
use App\Models\AcademicHomeworkSubmission;
use App\Models\AcademicSession;
use App\Models\AcademicSubscription;
use App\Models\Academy;
use App\Models\BusinessServiceRequest;
use App\Models\CourseReview;
use App\Models\InteractiveCourseHomeworkSubmission;
use App\Models\InteractiveCourseSession;
use App\Models\Payment;
use App\Models\QuranSession;
use App\Models\QuranSubscription;
use App\Models\QuranTrialRequest;
use App\Models\SessionRequest;
use App\Models\TeacherReview;
use App\Models\User;
use App\Services\AcademyContextService;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class SuperAdminControlPanelWidget extends Widget
{
    protected static bool $isDiscoverable = false;

    protected string $view = 'filament.widgets.super-admin-control-panel';

    protected ?string $pollingInterval = '60s';

    protected static ?int $sort = -2;

    protected int|string|array $columnSpan = 'full';

    public string $activeTab = 'quran';

    public string $quranPeriod = 'today';

    public string $academicPeriod = 'today';

    public function getViewData(): array
    {
        $academy = AcademyContextService::getCurrentAcademy();

        if (! $academy) {
            return [
                'quran' => ['pending' => [], 'sessions' => $this->emptySessionData()],
                'academic' => ['pending' => [], 'sessions' => []],
                'general' => ['pending' => [], 'inactiveUsers' => []],
                'totalPending' => 0,
                'actions' => ['quran' => [], 'academic' => [], 'general' => []],
                'academyName' => '',
                'periodLabels' => $this->getPeriodLabels(),
            ];
        }

        $cacheKey = 'sa_cp_' . $academy->id . '_q' . $this->quranPeriod . '_a' . $this->academicPeriod;

        $data = Cache::remember($cacheKey, 30, function () use ($academy) {
            $quran = $this->getQuranSectionData($academy);
            $academic = $this->getAcademicSectionData($academy);
            $general = $this->getGeneralSectionData($academy);

            $totalPending = collect($quran['pending'])->sum('count')
                + collect($academic['pending'])->sum('count')
                + collect($general['pending'])->sum('count');

            return [
                'quran' => $quran,
                'academic' => $academic,
                'general' => $general,
                'totalPending' => $totalPending,
                'academyName' => $academy->name,
            ];
        });

        $data['actions'] = [
            'quran' => $this->getQuranActions(),
            'academic' => $this->getAcademicActions(),
            'general' => $this->getGeneralActions(),
        ];
        $data['periodLabels'] = $this->getPeriodLabels();

        return $data;
    }

    private function getPeriodLabels(): array
    {
        return [
            'today' => 'اليوم',
            'week' => 'الأسبوع',
            'month' => 'الشهر',
            'all' => 'الكل',
        ];
    }

    private function getPeriodRange(string $period): ?array
    {
        return match ($period) {
            'today' => [now()->startOfDay(), now()->endOfDay()],
            'week' => [now()->startOfWeek(), now()->endOfDay()],
            'month' => [now()->startOfMonth(), now()->endOfDay()],
            default => null,
        };
    }

    private function emptySessionData(): array
    {
        return [
            'total' => 0, 'scheduled' => 0, 'ongoing' => 0,
            'completed' => 0, 'cancelled' => 0,
            'label' => '', 'icon' => '', 'color' => '', 'url' => '',
        ];
    }

    private function getQuranSectionData(Academy $academy): array
    {
        $academyId = $academy->id;
        $period = $this->getPeriodRange($this->quranPeriod);

        return [
            'pending' => [
                [
                    'count' => QuranSubscription::where('academy_id', $academyId)
                        ->where('status', SessionSubscriptionStatus::PENDING->value)->count(),
                    'label' => 'اشتراكات معلقة',
                    'icon' => 'heroicon-o-credit-card',
                    'color' => 'warning',
                    'url' => route('filament.admin.resources.quran-subscriptions.index', [
                        'filters[status][value]' => 'pending',
                    ]),
                ],
                [
                    'count' => QuranTrialRequest::where('academy_id', $academyId)
                        ->where('status', TrialRequestStatus::PENDING->value)->count(),
                    'label' => 'طلبات تجربة',
                    'icon' => 'heroicon-o-beaker',
                    'color' => 'warning',
                    'url' => route('filament.admin.resources.quran-trial-requests.index', [
                        'filters[status][value]' => 'pending',
                    ]),
                ],
                [
                    'count' => Schema::hasTable('session_requests')
                        ? SessionRequest::where('academy_id', $academyId)
                            ->whereIn('status', [
                                SessionRequestStatus::PENDING->value,
                                SessionRequestStatus::AGREED->value,
                            ])->count()
                        : 0,
                    'label' => 'طلبات جلسات',
                    'icon' => 'heroicon-o-calendar',
                    'color' => 'warning',
                    'url' => null,
                ],
                [
                    'count' => QuranSubscription::where('academy_id', $academyId)
                        ->where('status', SessionSubscriptionStatus::ACTIVE->value)
                        ->where('ends_at', '<=', now()->addDays(7))
                        ->where('ends_at', '>', now())->count(),
                    'label' => 'اشتراكات تنتهي قريباً',
                    'icon' => 'heroicon-o-clock',
                    'color' => 'danger',
                    'url' => route('filament.admin.resources.quran-subscriptions.index', [
                        'filters[status][value]' => 'active',
                    ]),
                ],
            ],
            'sessions' => $this->getSessionTypeData(
                QuranSession::where('academy_id', $academyId),
                $period,
                'جلسات القرآن',
                'heroicon-o-book-open',
                'success',
                QuranSessionResource::getUrl('index'),
            ),
        ];
    }

    private function getAcademicSectionData(Academy $academy): array
    {
        $academyId = $academy->id;
        $period = $this->getPeriodRange($this->academicPeriod);

        return [
            'pending' => [
                [
                    'count' => AcademicSubscription::where('academy_id', $academyId)
                        ->where('status', SessionSubscriptionStatus::PENDING->value)->count(),
                    'label' => 'اشتراكات معلقة',
                    'icon' => 'heroicon-o-credit-card',
                    'color' => 'warning',
                    'url' => route('filament.admin.resources.academic-subscriptions.index', [
                        'filters[status][value]' => 'pending',
                    ]),
                ],
                [
                    'count' => AcademicSubscription::where('academy_id', $academyId)
                        ->where('status', SessionSubscriptionStatus::ACTIVE->value)
                        ->where('ends_at', '<=', now()->addDays(7))
                        ->where('ends_at', '>', now())->count(),
                    'label' => 'اشتراكات تنتهي قريباً',
                    'icon' => 'heroicon-o-clock',
                    'color' => 'danger',
                    'url' => route('filament.admin.resources.academic-subscriptions.index', [
                        'filters[status][value]' => 'active',
                    ]),
                ],
                [
                    'count' => AcademicHomeworkSubmission::where('academy_id', $academyId)
                            ->whereIn('submission_status', [
                                HomeworkSubmissionStatus::SUBMITTED->value,
                                HomeworkSubmissionStatus::LATE->value,
                                HomeworkSubmissionStatus::RESUBMITTED->value,
                            ])->count()
                        + InteractiveCourseHomeworkSubmission::where('academy_id', $academyId)
                            ->whereIn('submission_status', [
                                HomeworkSubmissionStatus::SUBMITTED->value,
                                HomeworkSubmissionStatus::LATE->value,
                                HomeworkSubmissionStatus::RESUBMITTED->value,
                            ])->count(),
                    'label' => 'واجبات بانتظار التصحيح',
                    'icon' => 'heroicon-o-document-check',
                    'color' => 'info',
                    'url' => route('filament.admin.resources.homework-submissions.index', [
                        'filters[submission_status][value]' => 'submitted',
                    ]),
                ],
                [
                    'count' => CourseReview::where('academy_id', $academyId)
                            ->where('is_approved', false)->count()
                        + TeacherReview::where('academy_id', $academyId)
                            ->where('is_approved', false)->count(),
                    'label' => 'مراجعات بانتظار الموافقة',
                    'icon' => 'heroicon-o-star',
                    'color' => 'info',
                    'url' => route('filament.admin.resources.teacher-reviews.index', [
                        'filters[is_approved][value]' => '0',
                    ]),
                ],
            ],
            'sessions' => [
                'academic' => $this->getSessionTypeData(
                    AcademicSession::where('academy_id', $academyId),
                    $period,
                    'جلسات أكاديمية',
                    'heroicon-o-academic-cap',
                    'info',
                    AcademicSessionResource::getUrl('index'),
                ),
                'interactive' => $this->getSessionTypeData(
                    InteractiveCourseSession::where('academy_id', $academyId),
                    $period,
                    'جلسات تفاعلية',
                    'heroicon-o-video-camera',
                    'primary',
                    InteractiveCourseSessionResource::getUrl('index'),
                ),
            ],
        ];
    }

    private function getGeneralSectionData(Academy $academy): array
    {
        $academyId = $academy->id;

        return [
            'pending' => [
                [
                    'count' => Payment::where('academy_id', $academyId)
                        ->where('status', PaymentStatus::PENDING->value)->count(),
                    'label' => 'مدفوعات معلقة',
                    'icon' => 'heroicon-o-banknotes',
                    'color' => 'danger',
                    'url' => route('filament.admin.resources.payments.index', [
                        'filters[status][values][0]' => 'pending',
                    ]),
                ],
                [
                    'count' => Payment::where('academy_id', $academyId)
                        ->where('status', PaymentStatus::FAILED->value)
                        ->whereDate('created_at', today())->count(),
                    'label' => 'مدفوعات فاشلة اليوم',
                    'icon' => 'heroicon-o-x-circle',
                    'color' => 'danger',
                    'url' => route('filament.admin.resources.payments.index', [
                        'filters[status][values][0]' => 'failed',
                    ]),
                ],
                [
                    'count' => BusinessServiceRequest::where('status', BusinessRequestStatus::PENDING->value)->count(),
                    'label' => 'طلبات خدمات',
                    'icon' => 'heroicon-o-briefcase',
                    'color' => 'warning',
                    'url' => route('filament.admin.resources.business-service-requests.index', [
                        'filters[status][value]' => 'pending',
                    ]),
                ],
            ],
            'inactiveUsers' => [
                [
                    'count' => User::where('academy_id', $academyId)
                        ->where('active_status', false)
                        ->where('user_type', UserType::STUDENT->value)->count(),
                    'label' => 'طلاب',
                    'icon' => 'heroicon-o-user',
                    'color' => 'gray',
                    'url' => StudentProfileResource::getUrl('index'),
                ],
                [
                    'count' => User::where('academy_id', $academyId)
                        ->where('active_status', false)
                        ->where('user_type', UserType::QURAN_TEACHER->value)->count(),
                    'label' => 'معلمو قرآن',
                    'icon' => 'heroicon-o-book-open',
                    'color' => 'gray',
                    'url' => QuranTeacherProfileResource::getUrl('index') . '?' . http_build_query([
                        'filters' => ['active_status' => ['value' => '0']],
                    ]),
                ],
                [
                    'count' => User::where('academy_id', $academyId)
                        ->where('active_status', false)
                        ->where('user_type', UserType::ACADEMIC_TEACHER->value)->count(),
                    'label' => 'معلمون أكاديميون',
                    'icon' => 'heroicon-o-academic-cap',
                    'color' => 'gray',
                    'url' => AcademicTeacherProfileResource::getUrl('index') . '?' . http_build_query([
                        'filters' => ['active_status' => ['value' => '0']],
                    ]),
                ],
                [
                    'count' => User::where('academy_id', $academyId)
                        ->where('active_status', false)
                        ->where('user_type', UserType::PARENT->value)->count(),
                    'label' => 'أولياء أمور',
                    'icon' => 'heroicon-o-users',
                    'color' => 'gray',
                    'url' => ParentProfileResource::getUrl('index'),
                ],
            ],
        ];
    }

    private function getSessionTypeData($query, ?array $period, string $label, string $icon, string $color, string $url): array
    {
        $baseQuery = clone $query;

        if ($period !== null) {
            $baseQuery = $baseQuery->whereBetween('scheduled_at', $period);
        }

        return [
            'total' => (clone $baseQuery)->count(),
            'completed' => (clone $baseQuery)->where('status', SessionStatus::COMPLETED->value)->count(),
            'ongoing' => (clone $baseQuery)->where('status', SessionStatus::ONGOING->value)->count(),
            'scheduled' => (clone $baseQuery)->whereIn('status', [
                SessionStatus::SCHEDULED->value,
                SessionStatus::READY->value,
            ])->count(),
            'cancelled' => (clone $baseQuery)->where('status', SessionStatus::CANCELLED->value)->count(),
            'label' => $label,
            'icon' => $icon,
            'color' => $color,
            'url' => $url,
        ];
    }

    private function getQuranActions(): array
    {
        return [
            ['label' => 'اشتراك قرآن جديد', 'icon' => 'heroicon-o-credit-card', 'url' => QuranSubscriptionResource::getUrl('create'), 'color' => 'success'],
            ['label' => 'حلقة قرآن جديدة', 'icon' => 'heroicon-o-user-group', 'url' => QuranCircleResource::getUrl('create'), 'color' => 'success'],
            ['label' => 'حلقة فردية جديدة', 'icon' => 'heroicon-o-user', 'url' => QuranIndividualCircleResource::getUrl('create'), 'color' => 'success'],
            ['label' => 'باقة قرآن جديدة', 'icon' => 'heroicon-o-cube', 'url' => QuranPackageResource::getUrl('create'), 'color' => 'success'],
        ];
    }

    private function getAcademicActions(): array
    {
        return [
            ['label' => 'اشتراك أكاديمي جديد', 'icon' => 'heroicon-o-credit-card', 'url' => AcademicSubscriptionResource::getUrl('create'), 'color' => 'info'],
            ['label' => 'باقة أكاديمية جديدة', 'icon' => 'heroicon-o-cube-transparent', 'url' => AcademicPackageResource::getUrl('create'), 'color' => 'info'],
            ['label' => 'دورة تفاعلية جديدة', 'icon' => 'heroicon-o-video-camera', 'url' => InteractiveCourseResource::getUrl('create'), 'color' => 'info'],
            ['label' => 'دورة مسجلة جديدة', 'icon' => 'heroicon-o-play-circle', 'url' => RecordedCourseResource::getUrl('create'), 'color' => 'info'],
        ];
    }

    private function getGeneralActions(): array
    {
        return [
            ['label' => 'إضافة طالب جديد', 'icon' => 'heroicon-o-user-plus', 'url' => StudentProfileResource::getUrl('create'), 'color' => 'primary'],
            ['label' => 'تسجيل مدفوعة', 'icon' => 'heroicon-o-banknotes', 'url' => PaymentResource::getUrl('create'), 'color' => 'warning'],
        ];
    }

    public static function canView(): bool
    {
        return AcademyContextService::isSuperAdmin()
            && AcademyContextService::getCurrentAcademy() !== null;
    }
}
