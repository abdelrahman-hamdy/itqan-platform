<?php

namespace App\Http\Controllers;

use Illuminate\View\View;
use App\Models\BusinessServiceCategory;

class PlatformController extends Controller
{
    /**
     * Show the platform landing page
     */
    public function home(): View
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
    public function about(): View
    {
        return view('platform.about');
    }

    /**
     * Show the platform features page
     */
    public function features(): View
    {
        return view('platform.features');
    }

    /**
     * Show the platform contact page
     */
    public function contact(): View
    {
        return view('platform.contact');
    }
}
