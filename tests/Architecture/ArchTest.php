<?php

/**
 * Architecture Tests for Itqan Platform
 *
 * These tests enforce architectural conventions and prevent
 * regressions in code organization patterns.
 */

// No debug statements in production code
arch('no debug statements in app code')
    ->expect('App')
    ->not->toUse(['dd', 'dump', 'var_dump', 'print_r', 'ray']);

// No env() calls outside config files
arch('no env calls outside config')
    ->expect('App')
    ->not->toUse('env')
    ->ignoring('App\Providers');

// Controllers should have Controller suffix (excluding traits)
arch('controllers have proper suffix')
    ->expect('App\Http\Controllers')
    ->toHaveSuffix('Controller')
    ->ignoring('App\Http\Controllers\Traits');

// Enums should be proper enums
arch('enums are backed enums')
    ->expect('App\Enums')
    ->toBeEnums();

// Exceptions should extend base Exception
arch('exceptions extend exception')
    ->expect('App\Exceptions')
    ->toExtend('Exception');

// Policies have proper suffix
arch('policies have proper suffix')
    ->expect('App\Policies')
    ->toHaveSuffix('Policy');

// Observers have proper suffix
arch('observers have proper suffix')
    ->expect('App\Observers')
    ->toHaveSuffix('Observer');

// Jobs should implement ShouldQueue (excluding traits)
arch('jobs implement should queue')
    ->expect('App\Jobs')
    ->toImplement('Illuminate\Contracts\Queue\ShouldQueue')
    ->ignoring('App\Jobs\Traits');
