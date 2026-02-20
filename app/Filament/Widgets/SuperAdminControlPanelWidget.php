<?php

namespace App\Filament\Widgets;

use App\Enums\BusinessRequestStatus;
use App\Enums\HomeworkSubmissionStatus;
use App\Enums\PaymentStatus;
use App\Enums\SessionRequestStatus;
use App\Enums\SessionStatus;
use App\Enums\SessionSubscriptionStatus;
use App\Enums\TrialRequestStatus;
use App\Filament\Resources\AcademicPackageResource;
use App\Filament\Resources\AcademicSessionResource;
use App\Filament\Resources\AcademicSubscriptionResource;
use App\Filament\Resources\BusinessServiceRequestResource;
use App\Filament\Resources\HomeworkSubmissionsResource;
use App\Filament\Resources\InteractiveCourseResource;
use App\Filament\Resources\InteractiveCourseSessionResource;
use App\Filament\Resources\PaymentResource;
use App\Filament\Resources\QuranCircleResource;
use App\Filament\Resources\QuranIndividualCircleResource;
use App\Filament\Resources\QuranPackageResource;
use App\Filament\Resources\QuranSessionResource;
use App\Filament\Resources\QuranSubscriptionResource;
use App\Filament\Resources\QuranTrialRequestResource;
use App\Filament\Resources\RecordedCourseResource;
use App\Filament\Resources\StudentProfileResource;
use App\Filament\Resources\TeacherReviewResource;
use App\Filament\Resources\UserResource;
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

    public string $activeTab = 'pending';

    public function getViewData(): array
    {
        $academy = AcademyContextService::getCurrentAcademy();

        if (! $academy) {
            return [
                'pending' => [],
                'today' => [],
                'quickStats' => [],
                'actions' => [],
                'academyName' => '',
            ];
        }

        $cacheKey = 'sa_cp_' . $academy->id;

        $data = Cache::remember($cacheKey, 30, function () use ($academy) {
            $pending = $this->getPendingWorkflowData($academy);
            $today = $this->getTodaySessionsData($academy);

            return [
                'pending' => $pending,
                'today' => $today,
                'quickStats' => $this->getQuickStatsData($academy, $pending, $today),
                'academyName' => $academy->name,
            ];
        });

        $data['actions'] = $this->getQuickActionsData();

        return $data;
    }

    private function getPendingWorkflowData(Academy $academy): array
    {
        $academyId = $academy->id;

        return [
            'subscriptions' => [
                'count' => QuranSubscription::where('academy_id', $academyId)
                        ->where('status', SessionSubscriptionStatus::PENDING->value)->count()
                    + AcademicSubscription::where('academy_id', $academyId)
                        ->where('status', SessionSubscriptionStatus::PENDING->value)->count(),
                'label' => 'اشتراكات معلقة',
                'icon' => 'heroicon-o-credit-card',
                'color' => 'warning',
                'url' => route('filament.admin.resources.quran-subscriptions.index', [
                    'tableFilters[status][value]' => 'pending',
                ]),
                'urgent' => true,
            ],
            'trial_requests' => [
                'count' => QuranTrialRequest::where('academy_id', $academyId)
                    ->where('status', TrialRequestStatus::PENDING->value)->count(),
                'label' => 'طلبات تجربة',
                'icon' => 'heroicon-o-beaker',
                'color' => 'warning',
                'url' => route('filament.admin.resources.quran-trial-requests.index', [
                    'tableFilters[status][value]' => 'pending',
                ]),
                'urgent' => true,
            ],
            'session_requests' => [
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
                'urgent' => true,
            ],
            'pending_payments' => [
                'count' => Payment::where('academy_id', $academyId)
                    ->where('status', PaymentStatus::PENDING->value)->count(),
                'label' => 'مدفوعات معلقة',
                'icon' => 'heroicon-o-banknotes',
                'color' => 'danger',
                'url' => route('filament.admin.resources.payments.index', [
                    'tableFilters[status][values][0]' => 'pending',
                ]),
                'urgent' => true,
            ],
            'failed_payments' => [
                'count' => Payment::where('academy_id', $academyId)
                    ->where('status', PaymentStatus::FAILED->value)
                    ->whereDate('created_at', today())->count(),
                'label' => 'مدفوعات فاشلة اليوم',
                'icon' => 'heroicon-o-exclamation-circle',
                'color' => 'danger',
                'url' => route('filament.admin.resources.payments.index', [
                    'tableFilters[status][values][0]' => 'failed',
                ]),
                'urgent' => true,
            ],
            'expiring_subs' => [
                'count' => QuranSubscription::where('academy_id', $academyId)
                        ->where('status', SessionSubscriptionStatus::ACTIVE->value)
                        ->where('ends_at', '<=', now()->addDays(7))
                        ->where('ends_at', '>', now())->count()
                    + AcademicSubscription::where('academy_id', $academyId)
                        ->where('status', SessionSubscriptionStatus::ACTIVE->value)
                        ->where('ends_at', '<=', now()->addDays(7))
                        ->where('ends_at', '>', now())->count(),
                'label' => 'اشتراكات تنتهي قريباً',
                'icon' => 'heroicon-o-exclamation-triangle',
                'color' => 'danger',
                'url' => route('filament.admin.resources.quran-subscriptions.index', [
                    'tableFilters[status][value]' => 'active',
                ]),
                'urgent' => true,
            ],
            'business_requests' => [
                'count' => BusinessServiceRequest::where('status', BusinessRequestStatus::PENDING->value)->count(),
                'label' => 'طلبات خدمات',
                'icon' => 'heroicon-o-briefcase',
                'color' => 'warning',
                'url' => BusinessServiceRequestResource::getUrl('index'),
                'urgent' => false,
            ],
            'reviews' => [
                'count' => CourseReview::where('academy_id', $academyId)
                        ->where('is_approved', false)->count()
                    + TeacherReview::where('academy_id', $academyId)
                        ->where('is_approved', false)->count(),
                'label' => 'مراجعات بانتظار الموافقة',
                'icon' => 'heroicon-o-star',
                'color' => 'info',
                'url' => route('filament.admin.resources.teacher-reviews.index', [
                    'tableFilters[is_approved][value]' => '0',
                ]),
                'urgent' => false,
            ],
            'homework' => [
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
                    'tableFilters[submission_status][value]' => 'submitted',
                ]),
                'urgent' => false,
            ],
            'inactive_users' => [
                'count' => User::where('academy_id', $academyId)
                    ->where('active_status', false)->count(),
                'label' => 'مستخدمين غير نشطين',
                'icon' => 'heroicon-o-user-minus',
                'color' => 'gray',
                'url' => UserResource::getUrl('index'),
                'urgent' => false,
            ],
        ];
    }

    private function getTodaySessionsData(Academy $academy): array
    {
        $academyId = $academy->id;
        $todayStart = now()->startOfDay();
        $todayEnd = now()->endOfDay();

        return [
            'quran' => $this->getSessionTypeData(
                QuranSession::where('academy_id', $academyId),
                $todayStart,
                $todayEnd,
                'جلسات القرآن',
                'heroicon-o-book-open',
                'success',
                QuranSessionResource::getUrl('index'),
            ),
            'academic' => $this->getSessionTypeData(
                AcademicSession::where('academy_id', $academyId),
                $todayStart,
                $todayEnd,
                'الجلسات الأكاديمية',
                'heroicon-o-academic-cap',
                'info',
                AcademicSessionResource::getUrl('index'),
            ),
            'interactive' => $this->getSessionTypeData(
                InteractiveCourseSession::where('academy_id', $academyId),
                $todayStart,
                $todayEnd,
                'جلسات تفاعلية',
                'heroicon-o-video-camera',
                'primary',
                InteractiveCourseSessionResource::getUrl('index'),
            ),
        ];
    }

    private function getSessionTypeData($query, $todayStart, $todayEnd, string $label, string $icon, string $color, string $url): array
    {
        $baseQuery = (clone $query)->whereBetween('scheduled_at', [$todayStart, $todayEnd]);

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

    private function getQuickStatsData(Academy $academy, array $pending, array $today): array
    {
        $academyId = $academy->id;
        $totalPending = collect($pending)->sum('count');
        $totalTodaySessions = collect($today)->sum('total');
        $ongoingSessions = collect($today)->sum('ongoing');
        $completedToday = collect($today)->sum('completed');

        $activeSubscriptions = QuranSubscription::where('academy_id', $academyId)
                ->where('status', SessionSubscriptionStatus::ACTIVE->value)->count()
            + AcademicSubscription::where('academy_id', $academyId)
                ->where('status', SessionSubscriptionStatus::ACTIVE->value)->count();

        return [
            'total_pending' => $totalPending,
            'today_sessions' => $totalTodaySessions,
            'ongoing_sessions' => $ongoingSessions,
            'completed_today' => $completedToday,
            'active_subscriptions' => $activeSubscriptions,
        ];
    }

    private function getQuickActionsData(): array
    {
        return [
            ['label' => 'اشتراك قرآن جديد', 'icon' => 'heroicon-o-book-open', 'url' => QuranSubscriptionResource::getUrl('create'), 'color' => 'success'],
            ['label' => 'اشتراك أكاديمي جديد', 'icon' => 'heroicon-o-academic-cap', 'url' => AcademicSubscriptionResource::getUrl('create'), 'color' => 'info'],
            ['label' => 'إضافة مستخدم', 'icon' => 'heroicon-o-user-plus', 'url' => UserResource::getUrl('create'), 'color' => 'primary'],
            ['label' => 'تسجيل مدفوعة', 'icon' => 'heroicon-o-banknotes', 'url' => PaymentResource::getUrl('create'), 'color' => 'warning'],
            ['label' => 'حلقة قرآن جديدة', 'icon' => 'heroicon-o-user-group', 'url' => QuranCircleResource::getUrl('create'), 'color' => 'success'],
            ['label' => 'حلقة فردية جديدة', 'icon' => 'heroicon-o-user', 'url' => QuranIndividualCircleResource::getUrl('create'), 'color' => 'success'],
            ['label' => 'باقة قرآن جديدة', 'icon' => 'heroicon-o-cube', 'url' => QuranPackageResource::getUrl('create'), 'color' => 'success'],
            ['label' => 'باقة أكاديمية جديدة', 'icon' => 'heroicon-o-cube-transparent', 'url' => AcademicPackageResource::getUrl('create'), 'color' => 'info'],
            ['label' => 'دورة تفاعلية جديدة', 'icon' => 'heroicon-o-video-camera', 'url' => InteractiveCourseResource::getUrl('create'), 'color' => 'info'],
            ['label' => 'دورة مسجلة جديدة', 'icon' => 'heroicon-o-play-circle', 'url' => RecordedCourseResource::getUrl('create'), 'color' => 'info'],
        ];
    }

    public static function canView(): bool
    {
        return AcademyContextService::isSuperAdmin()
            && AcademyContextService::getCurrentAcademy() !== null;
    }
}
