<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\StudentProfile;

echo "Fixing phone number inconsistencies...\n";

// Define valid country codes (ordered by length, longest first)
$countryCodes = [
    '+966' => 'SA', // Saudi Arabia
    '+971' => 'AE', // UAE
    '+965' => 'KW', // Kuwait
    '+974' => 'QA', // Qatar
    '+968' => 'OM', // Oman
    '+973' => 'BH', // Bahrain
    '+962' => 'JO', // Jordan
    '+961' => 'LB', // Lebanon
    '+970' => 'PS', // Palestine
    '+964' => 'IQ', // Iraq
    '+967' => 'YE', // Yemen
    '+20' => 'EG',  // Egypt
];

$students = StudentProfile::whereNotNull('parent_phone')->get();

echo "Checking {$students->count()} students...\n";

$fixed = 0;
foreach ($students as $student) {
    if (!$student->parent_phone || !str_starts_with($student->parent_phone, '+')) {
        continue;
    }

    // Find matching country code
    $matchedCode = null;
    $matchedCountry = null;

    foreach ($countryCodes as $code => $country) {
        if (str_starts_with($student->parent_phone, $code)) {
            $matchedCode = $code;
            $matchedCountry = $country;
            break;
        }
    }

    if ($matchedCode && ($student->parent_phone_country_code != $matchedCode || $student->parent_phone_country != $matchedCountry)) {
        echo "Fixing student: {$student->student_code}\n";
        echo "  Phone: {$student->parent_phone}\n";
        echo "  Old country code: {$student->parent_phone_country_code} -> New: {$matchedCode}\n";
        echo "  Old country: {$student->parent_phone_country} -> New: {$matchedCountry}\n";

        $student->parent_phone_country_code = $matchedCode;
        $student->parent_phone_country = $matchedCountry;
        $student->save();
        $fixed++;
        echo "  ✓ Fixed!\n\n";
    }
}

echo "Done! Fixed {$fixed} student(s).\n";
