<?php

namespace App\Filament\Resources\QuranSubscriptionResource\Pages;

use App\Filament\Resources\QuranSubscriptionResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

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
        $data['academy_id'] = Auth::user()->academy_id ?? 1; // Default academy or get from user
        $data['created_by'] = Auth::id();
        
        // Generate subscription code
        $academyId = $data['academy_id'];
        $count = \App\Models\QuranSubscription::where('academy_id', $academyId)->count() + 1;
        $data['subscription_code'] = 'QS-' . $academyId . '-' . str_pad($count, 6, '0', STR_PAD_LEFT);
        
        // Calculate derived fields
        $data['total_price'] = $data['price_per_session'] * $data['total_sessions'];
        $data['sessions_used'] = 0;
        $data['sessions_remaining'] = $data['total_sessions'];
        
        // Set subscription type
        $data['subscription_type'] = 'individual';
        
        // Set currency if not provided
        if (!isset($data['currency'])) {
            $data['currency'] = 'SAR';
        }
        
        // Calculate expiry date based on billing cycle
        $startsAt = Carbon::parse($data['starts_at']);
        $data['expires_at'] = match($data['billing_cycle']) {
            'weekly' => $startsAt->copy()->addWeeks(1),
            'monthly' => $startsAt->copy()->addMonth(),
            'quarterly' => $startsAt->copy()->addMonths(3),
            'yearly' => $startsAt->copy()->addYear(),
            default => $startsAt->copy()->addMonth()
        };
        
        // Set initial status
        $data['payment_status'] = 'pending';
        $data['subscription_status'] = 'pending';
        
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
        return 'تم إنشاء الاشتراك بنجاح';
    }
} 