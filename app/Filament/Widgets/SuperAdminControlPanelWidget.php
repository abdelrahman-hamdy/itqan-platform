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

    public function getViewData(): array
    {
        $academy = AcademyContextService::getCurrentAcademy();

        if (! $academy) {
            return [
                'quran' => ['pending' => [], 'sessions' => ['total' => 0, 'ongoing' => 0, 'completed' => 0, 'scheduled' => 0, 'cancelled' => 0]],
                'academic' => ['pending' => [], 'sessions' => []],
                'general' => ['pending' => [], 'inactiveUsers' => []],
                'quickStats' => ['total_pending' => 0, 'today_sessions' => 0, 'ongoing_sessions' => 0, 'completed_today' => 0, 'active_subscriptions' => 0],
                'actions' => ['quran' => [], 'academic' => [], 'general' => []],
                'academyName' => '',
            ];
        }

        $cacheKey = 'sa_cp_' . $academy->id;

        $data = Cache::remember($cacheKey, 30, function () use ($academy) {
            $quran = $this->getQuranSectionData($academy);
            $academic = $this->getAcademicSectionData($academy);
            $general = $this->getGeneralSectionData($academy);

            $allPendingCount = collect($quran['pending'])->sum('count')
                + collect($academic['pending'])->sum('count')
                + collect($general['pending'])->sum('count');

            $todaySessions = $quran['sessions']['total'];
            $ongoingSessions = $quran['sessions']['ongoing'];
            $completedToday = $quran['sessions']['completed'];

            foreach ($academic['sessions'] as $sessionType) {
                $todaySessions += $sessionType['total'];
                $ongoingSessions += $sessionType['ongoing'];
                $completedToday += $sessionType['completed'];
            }

            $activeSubscriptions = QuranSubscription::where('academy_id', $academy->id)
                    ->where('status', SessionSubscriptionStatus::ACTIVE->value)->count()
                + AcademicSubscription::where('academy_id', $academy->id)
                    ->where('status', SessionSubscriptionStatus::ACTIVE->value)->count();

            return [
                'quran' => $quran,
                'academic' => $academic,
                'general' => $general,
                'quickStats' => [
                    'total_pending' => $allPendingCount,
                    'today_sessions' => $todaySessions,
                    'ongoing_sessions' => $ongoingSessions,
                    'completed_today' => $completedToday,
                    'active_subscriptions' => $activeSubscriptions,
                ],
                'academyName' => $academy->name,
            ];
        });

        $data['actions'] = [
            'quran' => $this->getQuranActions(),
            'academic' => $this->getAcademicActions(),
            'general' => $this->getGeneralActions(),
        ];

        return $data;
    }

    private function getQuranSectionData(Academy $academy): array
    {
        $academyId = $academy->id;
        $todayStart = now()->startOfDay();
        $todayEnd = now()->endOfDay();

        return [
            'pending' => [
                'subscriptions' => [
                    'count' => QuranSubscription::where('academy_id', $academyId)
                        ->where('status', SessionSubscriptionStatus::PENDING->value)->count(),
                    'label' => 'اشتراكات قرآن معلقة',
                    'icon' => 'heroicon-o-credit-card',
                    'color' => 'warning',
                    'url' => route('filament.admin.resources.quran-subscriptions.index', [
                        'filters[status][value]' => 'pending',
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
                        'filters[status][value]' => 'pending',
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
                'expiring_subs' => [
                    'count' => QuranSubscription::where('academy_id', $academyId)
                        ->where('status', SessionSubscriptionStatus::ACTIVE->value)
                        ->where('ends_at', '<=', now()->addDays(7))
                        ->where('ends_at', '>', now())->count(),
                    'label' => 'اشتراكات تنتهي قريباً',
                    'icon' => 'heroicon-o-exclamation-triangle',
                    'color' => 'danger',
                    'url' => route('filament.admin.resources.quran-subscriptions.index', [
                        'filters[status][value]' => 'active',
                    ]),
                    'urgent' => true,
                ],
            ],
            'sessions' => $this->getSessionTypeData(
                QuranSession::where('academy_id', $academyId),
                $todayStart,
                $todayEnd,
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
        $todayStart = now()->startOfDay();
        $todayEnd = now()->endOfDay();

        return [
            'pending' => [
                'subscriptions' => [
                    'count' => AcademicSubscription::where('academy_id', $academyId)
                        ->where('status', SessionSubscriptionStatus::PENDING->value)->count(),
                    'label' => 'اشتراكات أكاديمية معلقة',
                    'icon' => 'heroicon-o-credit-card',
                    'color' => 'warning',
                    'url' => route('filament.admin.resources.academic-subscriptions.index', [
                        'filters[status][value]' => 'pending',
                    ]),
                    'urgent' => true,
                ],
                'expiring_subs' => [
                    'count' => AcademicSubscription::where('academy_id', $academyId)
                        ->where('status', SessionSubscriptionStatus::ACTIVE->value)
                        ->where('ends_at', '<=', now()->addDays(7))
                        ->where('ends_at', '>', now())->count(),
                    'label' => 'اشتراكات تنتهي قريباً',
                    'icon' => 'heroicon-o-exclamation-triangle',
                    'color' => 'danger',
                    'url' => route('filament.admin.resources.academic-subscriptions.index', [
                        'filters[status][value]' => 'active',
                    ]),
                    'urgent' => true,
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
                        'filters[submission_status][value]' => 'submitted',
                    ]),
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
                        'filters[is_approved][value]' => '0',
                    ]),
                    'urgent' => false,
                ],
            ],
            'sessions' => [
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
            ],
        ];
    }

    private function getGeneralSectionData(Academy $academy): array
    {
        $academyId = $academy->id;

        return [
            'pending' => [
                'pending_payments' => [
                    'count' => Payment::where('academy_id', $academyId)
                        ->where('status', PaymentStatus::PENDING->value)->count(),
                    'label' => 'مدفوعات معلقة',
                    'icon' => 'heroicon-o-banknotes',
                    'color' => 'danger',
                    'url' => route('filament.admin.resources.payments.index', [
                        'filters[status][values][0]' => 'pending',
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
                        'filters[status][values][0]' => 'failed',
                    ]),
                    'urgent' => true,
                ],
                'business_requests' => [
                    'count' => BusinessServiceRequest::where('status', BusinessRequestStatus::PENDING->value)->count(),
                    'label' => 'طلبات خدمات',
                    'icon' => 'heroicon-o-briefcase',
                    'color' => 'warning',
                    'url' => route('filament.admin.resources.business-service-requests.index', [
                        'filters[status][value]' => 'pending',
                    ]),
                    'urgent' => false,
                ],
            ],
            'inactiveUsers' => [
                [
                    'count' => User::where('academy_id', $academyId)
                        ->where('active_status', false)
                        ->where('user_type', UserType::STUDENT->value)->count(),
                    'label' => 'طلاب غير نشطين',
                    'icon' => 'heroicon-o-user',
                    'color' => 'gray',
                    'url' => StudentProfileResource::getUrl('index'),
                    'urgent' => false,
                ],
                [
                    'count' => User::where('academy_id', $academyId)
                        ->where('active_status', false)
                        ->where('user_type', UserType::QURAN_TEACHER->value)->count(),
                    'label' => 'معلمو قرآن غير نشطين',
                    'icon' => 'heroicon-o-book-open',
                    'color' => 'gray',
                    'url' => QuranTeacherProfileResource::getUrl('index'),
                    'urgent' => false,
                ],
                [
                    'count' => User::where('academy_id', $academyId)
                        ->where('active_status', false)
                        ->where('user_type', UserType::ACADEMIC_TEACHER->value)->count(),
                    'label' => 'معلمون أكاديميون غير نشطين',
                    'icon' => 'heroicon-o-academic-cap',
                    'color' => 'gray',
                    'url' => AcademicTeacherProfileResource::getUrl('index'),
                    'urgent' => false,
                ],
                [
                    'count' => User::where('academy_id', $academyId)
                        ->where('active_status', false)
                        ->where('user_type', UserType::PARENT->value)->count(),
                    'label' => 'أولياء أمور غير نشطين',
                    'icon' => 'heroicon-o-users',
                    'color' => 'gray',
                    'url' => ParentProfileResource::getUrl('index'),
                    'urgent' => false,
                ],
            ],
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
