<?php

namespace App\Http\Controllers;

use App\Models\Academy;
use Illuminate\Http\Request;
use App\Enums\SessionStatus;

class StaticPageController extends Controller
{
    /**
     * Display the Terms & Conditions page
     */
    public function terms(Request $request): \Illuminate\View\View
    {
        $academy = $request->academy ?? Academy::first();

        if (! $academy) {
            abort(404, 'Academy not found');
        }

        return view('academy.static.terms', compact('academy'));
    }

    /**
     * Display the Refund Policy page
     */
    public function refundPolicy(Request $request): \Illuminate\View\View
    {
        $academy = $request->academy ?? Academy::first();

        if (! $academy) {
            abort(404, 'Academy not found');
        }

        return view('academy.static.refund-policy', compact('academy'));
    }

    /**
     * Display the Privacy Policy page
     */
    public function privacyPolicy(Request $request): \Illuminate\View\View
    {
        $academy = $request->academy ?? Academy::first();

        if (! $academy) {
            abort(404, 'Academy not found');
        }

        return view('academy.static.privacy-policy', compact('academy'));
    }

    /**
     * Display the About Us page
     */
    public function aboutUs(Request $request): \Illuminate\View\View
    {
        $academy = $request->academy ?? Academy::first();

        if (! $academy) {
            abort(404, 'Academy not found');
        }

        return view('academy.static.about-us', compact('academy'));
    }
}
