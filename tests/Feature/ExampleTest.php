<?php

/*
|--------------------------------------------------------------------------
| Example Test - Use this as a template for writing new tests
|--------------------------------------------------------------------------
*/

use App\Models\Academy;

it('can create an academy', function () {
    $academy = Academy::factory()->create([
        'name' => 'Test Academy',
        'subdomain' => 'test-academy-' . uniqid(),
    ]);

    expect($academy)->toBeInstanceOf(Academy::class);
    expect($academy->name)->toBe('Test Academy');
    expect($academy->is_active)->toBeTrue();
});
