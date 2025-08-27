<?php

require_once __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$request = Illuminate\Http\Request::create(
    '/test-auth',
    'GET'
);

$response = $kernel->handle($request);

// Bootstrap the app
$kernel->terminate($request, $response);

// Now test authentication
use App\Models\User;
use Illuminate\Support\Facades\Auth;

// Find a teacher user
$teacher = User::whereIn('user_type', ['quran_teacher', 'academic_teacher', 'admin', 'super_admin'])->first();

if (! $teacher) {
    echo "No teacher/admin user found in database!\n";
    echo 'Available user types: '.User::distinct()->pluck('user_type')->join(', ')."\n";
    exit(1);
}

echo "Found teacher user:\n";
echo "- ID: {$teacher->id}\n";
echo "- Name: {$teacher->name}\n";
echo "- Email: {$teacher->email}\n";
echo "- Type: {$teacher->user_type}\n\n";

// Test authentication
Auth::login($teacher);

if (Auth::check()) {
    echo "✓ User authenticated successfully\n";
    echo '- Auth ID: '.Auth::id()."\n";
    echo '- Auth Type: '.Auth::user()->user_type."\n\n";

    // Test the middleware condition
    $allowedTypes = ['quran_teacher', 'academic_teacher', 'admin', 'super_admin'];
    $canControl = in_array(Auth::user()->user_type, $allowedTypes);

    echo 'Can control participants: '.($canControl ? 'YES' : 'NO')."\n";

    // Make a test request to the mute endpoint
    echo "\nTesting API endpoint...\n";

    $client = new \GuzzleHttp\Client([
        'base_uri' => 'http://localhost:8000',
        'cookies' => true,
        'verify' => false,
    ]);

    // Get CSRF token
    $response = $client->get('/');
    $html = (string) $response->getBody();
    preg_match('/<meta name="csrf-token" content="(.+?)"/', $html, $matches);
    $csrfToken = $matches[1] ?? '';

    echo 'CSRF Token: '.substr($csrfToken, 0, 20)."...\n";

    try {
        $response = $client->post('/livekit/mute-all-students', [
            'headers' => [
                'X-CSRF-TOKEN' => $csrfToken,
                'Accept' => 'application/json',
            ],
            'json' => [
                'room_name' => 'test-room',
                'muted' => true,
            ],
        ]);

        echo 'Response Status: '.$response->getStatusCode()."\n";
        echo 'Response Body: '.$response->getBody()."\n";
    } catch (\GuzzleHttp\Exception\ClientException $e) {
        echo 'Error Status: '.$e->getResponse()->getStatusCode()."\n";
        echo 'Error Body: '.$e->getResponse()->getBody()."\n";
    }

} else {
    echo "✗ Failed to authenticate user\n";
}
