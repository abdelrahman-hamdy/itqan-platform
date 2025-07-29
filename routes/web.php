<?php

use Illuminate\Support\Facades\Route;
use App\Models\Academy;

/*
|--------------------------------------------------------------------------
| Main Domain Routes
|--------------------------------------------------------------------------
*/

// Main domain routes (itqan-platform.test or default academy)
Route::domain(config('app.domain'))->group(function () {
    Route::get('/', function () {
        // Check if there's a default academy (itqan-academy)
        $defaultAcademy = Academy::where('subdomain', 'itqan-academy')->first();
        
        if ($defaultAcademy) {
            $output = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; text-align: center; border: 1px solid #ddd; border-radius: 8px;'>
                <h1 style='color: #2563eb;'>🎓 Itqan Platform</h1>
                <p><strong>Default Academy:</strong> {$defaultAcademy->name}</p>
                <p><strong>Domain:</strong> " . request()->getHost() . "</p>
                <hr>
                <h3>Available Academies:</h3>";
                
            $academies = Academy::where('status', 'active')->get();
            foreach($academies as $academy) {
                $output .= "<p><a href='http://{$academy->full_domain}' style='color: #2563eb; text-decoration: none;'>{$academy->name} ({$academy->subdomain})</a></p>";
            }
            
            $output .= "
                <hr>
                <a href='/admin' style='display: inline-block; margin-top: 20px; padding: 10px 20px; background: #2563eb; color: white; text-decoration: none; border-radius: 4px;'>Admin Panel</a>
            </div>
            ";
            
            return $output;
        }
        
        return view('welcome');
    });
});

/*
|--------------------------------------------------------------------------
| Subdomain Routes  
|--------------------------------------------------------------------------
*/

// Subdomain routes ({subdomain}.itqan-platform.test)
Route::domain('{subdomain}.' . config('app.domain'))->group(function () {
    Route::get('/', function ($subdomain) {
        // Find academy by subdomain
        $academy = Academy::where('subdomain', $subdomain)->first();
        
        if (!$academy) {
            abort(404, 'Academy not found');
        }
        
        if ($academy->status !== 'active' || !$academy->is_active) {
            abort(503, 'Academy is currently unavailable');
        }
        
        return "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; text-align: center; border: 1px solid #ddd; border-radius: 8px;'>
            <h1 style='color: #2563eb;'>🎓 {$academy->name}</h1>
            <p><strong>Subdomain:</strong> {$subdomain}</p>
            <p><strong>Full Domain:</strong> {$academy->full_domain}</p>
            <p><strong>Status:</strong> {$academy->status}</p>
            <p><strong>Logo URL:</strong> " . ($academy->logo_url ?? 'No logo uploaded') . "</p>
            <hr>
            <p>🚀 <strong>Subdomain routing is working!</strong></p>
            <a href='http://itqan-platform.test/admin' style='display: inline-block; margin-top: 20px; padding: 10px 20px; background: #2563eb; color: white; text-decoration: none; border-radius: 4px;'>Go to Admin Panel</a>
        </div>
        ";
    });
    
    // Add more academy-specific routes here
    // Route::get('/courses', [CoursesController::class, 'index']);
    // Route::get('/teachers', [TeachersController::class, 'index']);
});
