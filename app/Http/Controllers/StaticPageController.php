<?php

namespace App\Http\Controllers;

use Illuminate\View\View;
use App\Models\Academy;
use Illuminate\Http\Request;

class StaticPageController extends Controller
{
    /**
     * Display the Terms & Conditions page
     */
    public function terms(Request $request): View
    {
        $academy = $request->academy ?? Academy::first();

        if (! $academy) {
            abort(404, 'Academy not found');
        }

        return view('academy.static.terms', compact('academy'));
    }

    /**
     * Display the Privacy Policy page
     */
    public function privacyPolicy(Request $request): View
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
    public function aboutUs(Request $request): View
    {
        $academy = $request->academy ?? Academy::first();

        if (! $academy) {
            abort(404, 'Academy not found');
        }

        return view('academy.static.about-us', compact('academy'));
    }
}
