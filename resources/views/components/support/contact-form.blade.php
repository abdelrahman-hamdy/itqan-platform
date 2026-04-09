@php
    $academy = auth()->user()->academy ?? null;
    if (!$academy) return;

    // Cache settings lookup to avoid DB query on every profile page load
    $cacheKey = "support_form_settings:{$academy->id}";
    $formSettings = cache()->remember($cacheKey, 3600, function () use ($academy) {
        $settings = \App\Models\AcademySettings::where('academy_id', $academy->id)->first();
        return [
            'enabled' => $settings?->getSetting('support_contact_form_enabled', false),
            'message_ar' => $settings?->getSetting('support_contact_form_message_ar', ''),
            'message_en' => $settings?->getSetting('support_contact_form_message_en', ''),
        ];
    });

    if (!$formSettings['enabled']) return;

    $locale = app()->getLocale();
    $customMessage = $locale === 'ar' ? $formSettings['message_ar'] : $formSettings['message_en'];
    $message = !empty($customMessage) ? $customMessage : __('support.contact_form_default_message');

    $subdomain = $academy->subdomain ?? 'itqan-academy';
    $routePrefix = auth()->user()->isStudent() ? 'student.support' : 'teacher.support';
@endphp

<div class="bg-gradient-to-br from-emerald-50 to-teal-50 rounded-xl border border-emerald-200 p-5 md:p-6 mb-6 md:mb-8">
    <div class="flex items-start gap-4">
        <div class="w-12 h-12 bg-emerald-100 rounded-xl flex items-center justify-center shrink-0">
            <i class="ri-customer-service-2-line text-xl text-emerald-600"></i>
        </div>
        <div class="flex-1">
            <h3 class="text-base font-semibold text-gray-900 mb-2">{{ __('support.contact_form_title') }}</h3>
            <p class="text-sm text-gray-600 leading-relaxed mb-4">{{ $message }}</p>
            <a href="{{ route($routePrefix . '.create', ['subdomain' => $subdomain]) }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors text-sm font-medium">
                <i class="ri-send-plane-line"></i>
                {{ __('support.contact_form_button') }}
            </a>
        </div>
    </div>
</div>
