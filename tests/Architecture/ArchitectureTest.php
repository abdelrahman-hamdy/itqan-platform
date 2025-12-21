<?php

/**
 * Architecture Tests using Pest's arch() function
 *
 * These tests verify the codebase adheres to architectural patterns
 * and coding standards.
 */

// Models Architecture
arch('models extend Eloquent Model')
    ->expect('App\Models')
    ->toExtend('Illuminate\Database\Eloquent\Model')
    ->ignoring([
        'App\Models\Traits',
        'App\Models\Concerns',
    ]);

// Controllers Architecture
arch('controllers extend base Controller')
    ->expect('App\Http\Controllers')
    ->toExtend('App\Http\Controllers\Controller')
    ->ignoring([
        'App\Http\Controllers\Controller',
        'App\Http\Controllers\Traits',
    ]);

arch('controllers have Controller suffix')
    ->expect('App\Http\Controllers')
    ->toHaveSuffix('Controller')
    ->ignoring([
        'App\Http\Controllers\Controller',
        'App\Http\Controllers\Traits',
    ]);

// Requests Architecture
arch('form requests extend FormRequest')
    ->expect('App\Http\Requests')
    ->toExtend('Illuminate\Foundation\Http\FormRequest');

// Services Architecture
arch('services are classes')
    ->expect('App\Services')
    ->toBeClasses()
    ->ignoring([
        'App\Services\Traits',
        'App\Services\Calendar\SessionStrategyInterface',
        'App\Services\Scheduling\ScheduleValidatorInterface',
        'App\Services\Scheduling\Validators\ScheduleValidatorInterface',
        'App\Services\SessionStrategyInterface',
    ]);

// Policies Architecture
arch('policies have Policy suffix')
    ->expect('App\Policies')
    ->toHaveSuffix('Policy');

// Middleware Architecture
arch('middleware are classes')
    ->expect('App\Http\Middleware')
    ->toBeClasses();

// Livewire Architecture
arch('livewire components extend Component')
    ->expect('App\Livewire')
    ->toExtend('Livewire\Component');

// Enums Architecture
arch('enums are enums')
    ->expect('App\Enums')
    ->toBeEnums();

// No Debugging Statements
arch('no dd or dump in production code')
    ->expect('App')
    ->not->toUse(['dd', 'dump']);

// Jobs Architecture
arch('jobs implement ShouldQueue')
    ->expect('App\Jobs')
    ->toImplement('Illuminate\Contracts\Queue\ShouldQueue');

// Events Architecture
arch('events are classes')
    ->expect('App\Events')
    ->toBeClasses();

// Listeners Architecture
arch('listeners are classes')
    ->expect('App\Listeners')
    ->toBeClasses();
