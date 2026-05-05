<?php

namespace App\Filament\Resources\QuranSubscriptionResource\Pages;

use App\Enums\BillingCycle;
use App\Enums\SessionSubscriptionStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Filament\Pages\BaseCreateRecord as CreateRecord;
use App\Filament\Resources\QuranSubscriptionResource;
use App\Models\QuranSubscription;
use App\Services\AcademyContextService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class CreateQuranSubscription extends CreateRecord
{
    protected static string $resource = QuranSubscriptionResource::class;

    public function getTitle(): string
    {
        return 'إضافة اشتراك قرآن جديد';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Add the academy ID and created_by automatically
        $data['academy_id'] = AcademyContextService::getCurrentAcademyId() ?? Auth::user()->academy_id;
        $data['created_by'] = Auth::id();

        // Generate subscription code
        $data['subscription_code'] = QuranSubscription::generateSubscriptionCode($data['academy_id']);

        // Calculate derived fields
        $data['total_price'] = $data['price_per_session'] * $data['total_sessions'];
        $data['sessions_used'] = 0;
        $data['sessions_remaining'] = $data['total_sessions'];

        // Set subscription type
        $data['subscription_type'] = 'individual';

        // Set currency if not provided (use academy's currency)
        if (! isset($data['currency'])) {
            $data['currency'] = getCurrencyCode();
        }

        // Calculate expiry date from the actual billing cycle. tryFrom() lets
        // the legacy 'weekly' option (not part of the BillingCycle enum) fall
        // through to its addWeeks(1) behaviour without losing the cycle-aware
        // path for monthly/quarterly/yearly.
        $startsAt = Carbon::parse($data['starts_at']);
        $cycle = BillingCycle::tryFrom($data['billing_cycle']);
        $data['ends_at'] = $cycle
            ? $cycle->calculateEndDate($startsAt)
            : $startsAt->copy()->addWeeks(1);

        // Set initial status
        $data['payment_status'] = SubscriptionPaymentStatus::PENDING;
        $data['status'] = SessionSubscriptionStatus::PENDING;

        // Set initial progress fields
        $data['progress_percentage'] = 0;
        $data['memorization_level'] = 'beginner';
        $data['verses_memorized'] = 0;

        // Trial settings
        $data['trial_used'] = 0;
        $data['is_trial_active'] = ($data['trial_sessions'] ?? 0) > 0;

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'تم إضافة الاشتراك بنجاح';
    }
}
