<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

class LanguageController extends Controller
{
    /**
     * Supported locales
     */
    protected array $supportedLocales = ['ar', 'en'];

    /**
     * Switch the application language
     */
    public function switch(Request $request, string $locale): RedirectResponse
    {
        if (!in_array($locale, $this->supportedLocales)) {
            $locale = config('app.locale', 'ar');
        }

        Session::put('locale', $locale);
        App::setLocale($locale);

        // Update user preference if authenticated
        if ($request->user() && method_exists($request->user(), 'update')) {
            $request->user()->update(['preferred_locale' => $locale]);
        }

        return redirect()->back()->with('toast', [
            'type' => 'success',
            'message' => $locale === 'ar' ? 'تم تغيير اللغة بنجاح' : 'Language changed successfully',
        ]);
    }

    /**
     * Get current locale information
     */
    public function current(): array
    {
        return [
            'locale' => App::getLocale(),
            'direction' => App::getLocale() === 'ar' ? 'rtl' : 'ltr',
            'name' => App::getLocale() === 'ar' ? 'العربية' : 'English',
        ];
    }
}
