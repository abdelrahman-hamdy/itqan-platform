<?php

namespace App\Http\Controllers\Filament;

use Filament\Facades\Filament;
use Filament\Panel;
use Illuminate\Http\RedirectResponse;

/**
 * Override Filament's RedirectToTenantController to fix Livewire redirect return type issue.
 *
 * The original controller uses redirect() helper which returns Livewire\Features\SupportRedirects\Redirector
 * when called in a Livewire context, but the return type expects Illuminate\Http\RedirectResponse.
 *
 * This override creates RedirectResponse directly to ensure correct return type.
 */
class RedirectToTenantController
{
    public function __invoke(): RedirectResponse
    {
        $panel = Filament::getCurrentPanel();
        $tenant = Filament::getUserDefaultTenant(Filament::auth()->user());

        if (! $tenant) {
            return $this->redirectToTenantRegistration($panel);
        }

        $url = $panel->getUrl($tenant);

        if (blank($url)) {
            abort(404);
        }

        // Create RedirectResponse directly to avoid Livewire Redirector return type issue
        return new RedirectResponse($url);
    }

    protected function redirectToTenantRegistration(Panel $panel): RedirectResponse
    {
        if (! ($panel->hasTenantRegistration() && filament()->getTenantRegistrationPage()::canView())) {
            abort(404);
        }

        // Create RedirectResponse directly
        return new RedirectResponse($panel->getTenantRegistrationUrl());
    }
}
