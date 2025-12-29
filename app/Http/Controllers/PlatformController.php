<?php

namespace App\Http\Controllers;

use App\Models\BusinessServiceCategory;
use App\Enums\SessionStatus;

class PlatformController extends Controller
{
    /**
     * Show the platform landing page
     */
    public function home(): \Illuminate\View\View
    {
        // Fetch first 9 active service categories
        $services = BusinessServiceCategory::active()
            ->withCount('portfolioItems')
            ->take(9)
            ->get();

        return view('platform.landing', compact('services'));
    }

    /**
     * Show the platform about page
     */
    public function about(): \Illuminate\View\View
    {
        return view('platform.about');
    }

    /**
     * Show the platform features page
     */
    public function features(): \Illuminate\View\View
    {
        return view('platform.features');
    }

    /**
     * Show the platform contact page
     */
    public function contact(): \Illuminate\View\View
    {
        return view('platform.contact');
    }
}
