<?php

require_once __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$kernel->handle($request);

use App\Models\Academy;
use App\Models\InteractiveCourse;

echo "=== TESTING ROUTE GENERATION FOR INTERACTIVE COURSES ===\n\n";

// Get academy
$academy = Academy::where('subdomain', 'itqan-academy')->first();
if (! $academy) {
    echo "❌ Academy not found!\n";
    exit;
} else {
    echo "✅ Academy found: {$academy->name} (subdomain: {$academy->subdomain})\n\n";
}

// Get a course
$course = InteractiveCourse::where('academy_id', $academy->id)->first();
if (! $course) {
    echo "❌ No interactive courses found!\n";
    exit;
} else {
    echo "✅ Course found: {$course->title} (ID: {$course->id})\n\n";
}

// Test route generation
echo "=== TESTING ROUTE GENERATION ===\n";
try {
    $url = route('interactive-courses.show', ['subdomain' => $academy->subdomain, 'course' => $course->id]);
    echo "✅ Route generated successfully: {$url}\n\n";
} catch (\Exception $e) {
    echo '❌ Route generation failed: '.$e->getMessage()."\n\n";
}

// Test route exists
echo "=== TESTING ROUTE EXISTS ===\n";
$routes = app('router')->getRoutes();
$routeFound = false;
foreach ($routes as $route) {
    if ($route->getName() === 'interactive-courses.show') {
        $routeFound = true;
        echo '✅ Route found: '.$route->uri()."\n";
        echo '   Methods: '.implode(',', $route->methods())."\n";
        echo '   Domain: '.($route->domain() ?? 'none')."\n";
        echo '   Middleware: '.implode(',', $route->middleware())."\n";
        break;
    }
}

if (! $routeFound) {
    echo "❌ Route 'interactive-courses.show' NOT found!\n";
}

// Test alternative URLs
echo "\n=== TESTING MANUAL URL CONSTRUCTION ===\n";
$manualUrl = "https://{$academy->subdomain}.itqan-platform.test/interactive-courses/{$course->id}";
echo "Manual URL: {$manualUrl}\n";

// Test if we can access the controller method directly
echo "\n=== TESTING CONTROLLER ACCESS ===\n";
try {
    $controller = new App\Http\Controllers\StudentProfileController;
    echo "✅ Controller can be instantiated\n";

    // Check if method exists
    if (method_exists($controller, 'showInteractiveCourse')) {
        echo "✅ showInteractiveCourse method exists\n";
    } else {
        echo "❌ showInteractiveCourse method DOES NOT exist\n";
    }
} catch (\Exception $e) {
    echo '❌ Controller error: '.$e->getMessage()."\n";
}
