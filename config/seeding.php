<?php

/**
 * Seeder & Test Data Credentials Configuration
 *
 * These values are used by seeders and test data commands.
 * They should NEVER be used in production.
 *
 * Override via .env for local development or CI/CD environments.
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Default Seeder Password
    |--------------------------------------------------------------------------
    |
    | The password used for all seeded users (DatabaseSeeder, ComprehensiveDataSeeder,
    | SuperAdminDemoSeeder, QuranCircleTestSeeder).
    |
    */
    'default_password' => env('SEED_DEFAULT_PASSWORD', 'password123'),

    /*
    |--------------------------------------------------------------------------
    | Super Admin Credentials (SuperAdminSeeder)
    |--------------------------------------------------------------------------
    |
    | The dedicated super admin account created by SuperAdminSeeder.
    |
    */
    'super_admin' => [
        'email' => env('SEED_SUPER_ADMIN_EMAIL', 'admin@itqan.com'),
        'password' => env('SEED_SUPER_ADMIN_PASSWORD', 'Admin@Dev98'),
        'first_name' => env('SEED_SUPER_ADMIN_FIRST_NAME', 'Super'),
        'last_name' => env('SEED_SUPER_ADMIN_LAST_NAME', 'Admin'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Test Data Credentials (GenerateTestData command)
    |--------------------------------------------------------------------------
    |
    | Credentials used by `php artisan app:generate-test-data`.
    | Also referenced by route checker commands.
    |
    */
    'test_password' => env('SEED_TEST_PASSWORD', 'Test@123'),

    'test_email_domain' => env('SEED_TEST_EMAIL_DOMAIN', 'test.itqan.com'),

    /*
    |--------------------------------------------------------------------------
    | Demo Seeder Email Domain (SuperAdminDemoSeeder)
    |--------------------------------------------------------------------------
    */
    'demo_email_domain' => env('SEED_DEMO_EMAIL_DOMAIN', 'itqan-platform.test'),

    /*
    |--------------------------------------------------------------------------
    | Quran Circle Test Seeder
    |--------------------------------------------------------------------------
    */
    'quran_test' => [
        'teacher_email' => env('SEED_QURAN_TEACHER_EMAIL', 'teacher@test.com'),
        'student_email' => env('SEED_QURAN_STUDENT_EMAIL', 'student@test.com'),
        'student2_email' => env('SEED_QURAN_STUDENT2_EMAIL', 'student2@test.com'),
        'password' => env('SEED_QURAN_TEST_PASSWORD', 'password'),
    ],

];
