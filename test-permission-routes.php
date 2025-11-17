<?php

/**
 * Quick test script to verify LiveKit permission routes are accessible
 * Run: php test-permission-routes.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🧪 Testing LiveKit Permission Routes\n";
echo str_repeat("=", 60) . "\n\n";

// Get all routes
$routes = app('router')->getRoutes();

// Filter LiveKit routes
$livekitRoutes = [];
foreach ($routes as $route) {
    $uri = $route->uri();
    if (str_contains($uri, 'livekit')) {
        $livekitRoutes[] = [
            'method' => implode('|', $route->methods()),
            'uri' => $uri,
            'action' => $route->getActionName(),
        ];
    }
}

// Display routes
echo "✅ Found " . count($livekitRoutes) . " LiveKit routes:\n\n";

foreach ($livekitRoutes as $route) {
    $method = str_pad($route['method'], 12);
    $uri = str_pad($route['uri'], 50);
    echo "  {$method} {$uri}\n";
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "🔍 Testing specific permission routes:\n\n";

// Test specific routes
$testRoutes = [
    'GET /livekit/rooms/permissions',
    'POST /livekit/participants/mute-all-students',
    'POST /livekit/participants/disable-all-students-camera',
];

foreach ($testRoutes as $testRoute) {
    [$method, $uri] = explode(' ', $testRoute);

    // Check if route exists
    $found = false;
    foreach ($livekitRoutes as $route) {
        if (str_contains($route['method'], $method) && $route['uri'] === ltrim($uri, '/')) {
            $found = true;
            echo "  ✅ {$testRoute} - FOUND\n";
            break;
        }
    }

    if (!$found) {
        echo "  ❌ {$testRoute} - NOT FOUND\n";
    }
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "💡 Next steps:\n";
echo "  1. All routes should show as FOUND\n";
echo "  2. Hard refresh browser (Cmd+Shift+R / Ctrl+Shift+R)\n";
echo "  3. Check console for '🔍 Mic Toggle Debug' or '🔍 Camera Toggle Debug'\n";
echo "  4. Share those debug logs if issues persist\n";
echo "\n";
