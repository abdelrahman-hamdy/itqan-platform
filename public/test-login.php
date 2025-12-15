<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

$email = 'abdelrahmanhamdy320@gmail.com';
$password = 'Admin@Dev98';

// Find user
$user = \App\Models\User::where('email', $email)->first();

if (!$user) {
    die("User not found!");
}

echo "User found: " . $user->email . "<br>";
echo "User ID: " . $user->id . "<br>";
echo "Academy ID: " . $user->academy_id . "<br>";
echo "Status: " . $user->status . "<br>";
echo "Active Status: " . $user->active_status . "<br>";
echo "User Type: " . $user->user_type . "<br>";
echo "Password Hash: " . substr($user->password, 0, 20) . "...<br>";

// Test password
$passwordCheck = Hash::check($password, $user->password);
echo "Password matches: " . ($passwordCheck ? "YES" : "NO") . "<br>";

// Test Auth::attempt
$attempt = Auth::attempt(['email' => $email, 'password' => $password]);
echo "Auth::attempt result: " . ($attempt ? "SUCCESS" : "FAILED") . "<br>";

// Check isActive
echo "isActive(): " . ($user->isActive() ? "YES" : "NO") . "<br>";
