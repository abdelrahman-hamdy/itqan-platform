<?php

namespace App\Http\Responses;

use App\Constants\DefaultAcademy;
use Filament\Auth\Http\Responses\Contracts\LogoutResponse as LogoutResponseContract;
use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;

class FilamentLogoutResponse implements LogoutResponseContract
{
    public function toResponse($request): RedirectResponse|Redirector
    {
        // Extract subdomain from request host (session is already invalidated at this point)
        $subdomain = null;
        $host = $request->getHost();
        $baseDomain = config('app.domain', 'itqan-platform.test');

        if (str_contains($host, $baseDomain)) {
            $sub = str_replace('.' . $baseDomain, '', $host);
            if ($sub && $sub !== $baseDomain) {
                $subdomain = $sub;
            }
        }

        $subdomain = $subdomain ?: DefaultAcademy::subdomain();

        return redirect()->route('login', ['subdomain' => $subdomain]);
    }
}
