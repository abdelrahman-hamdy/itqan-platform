<?php

namespace App\Console\Commands;

use App\Models\Academy;
use Database\Seeders\MobileE2ESeeder;
use Illuminate\Console\Command;

/**
 * Remove ALL Mobile E2E test data from the database without re-seeding.
 *
 * Safe for production: only touches [E2E-Mobile] prefixed records and the
 * e2e-parent@itqan.com user. Does NOT delete the base E2E test users
 * (student, quran teacher, academic teacher) — use CleanTestData for that.
 *
 * Usage:
 *   php artisan e2e:clean-mobile --force
 *   php artisan e2e:clean-mobile --dry-run   # Preview what will be deleted
 */
class CleanMobileE2EData extends Command
{
    protected $signature = 'e2e:clean-mobile
        {--force : Skip confirmation prompt}
        {--dry-run : Show what would be deleted without deleting}
        {--include-base : Also clean base E2E data (sessions, subscriptions, profiles)}';

    protected $description = 'Remove all Mobile E2E test data ([E2E-Mobile] prefix) from production';

    public function handle(): int
    {
        $academy = Academy::where('subdomain', 'e2e-test')->first();

        if (! $academy) {
            $this->error('No e2e-test academy found. Nothing to clean.');

            return self::FAILURE;
        }

        if ($this->option('dry-run')) {
            return $this->dryRun($academy);
        }

        if (! $this->option('force') && ! $this->confirm('This will DELETE all Mobile E2E test data. Continue?')) {
            $this->info('Aborted.');

            return self::SUCCESS;
        }

        // Run the mobile seeder's cleanup method directly
        $seeder = new MobileE2ESeeder;
        // The seeder needs $this->command for info/warn output
        $seeder->setCommand($this);
        // Set academy via reflection (it's a private property)
        $ref = new \ReflectionClass($seeder);
        $prop = $ref->getProperty('academy');
        $prop->setAccessible(true);
        $prop->setValue($seeder, $academy);

        $seeder->cleanMobileData();

        // Optionally clean base E2E data too
        if ($this->option('include-base')) {
            $this->info('Also cleaning base E2E data...');
            $this->call('db:seed', [
                '--class' => 'Database\\Seeders\\E2ETestDataSeeder',
                '--force' => true,
            ]);
            $this->info('Base E2E data re-seeded (which cleans old data first).');
        }

        $this->info('Mobile E2E cleanup complete.');

        return self::SUCCESS;
    }

    private function dryRun(Academy $academy): int
    {
        $prefix = '[E2E-Mobile]';
        $academyId = $academy->id;

        $this->info("DRY RUN — Academy: {$academy->name} (ID: {$academyId})");
        $this->newLine();

        // Certificates
        $certCount = \App\Models\Certificate::withoutGlobalScopes()
            ->where('academy_id', $academyId)
            ->where('certificate_text', 'like', "{$prefix}%")
            ->count();
        $this->line("  Certificates: {$certCount}");

        // Quizzes
        $quizCount = \App\Models\Quiz::withoutGlobalScopes()
            ->where('academy_id', $academyId)
            ->where('title', 'like', "{$prefix}%")
            ->count();
        $this->line("  Quizzes: {$quizCount}");

        // Notifications
        $e2eUserIds = \App\Models\User::whereIn('email', [
            'e2e-student@itqan.com',
            'e2e-teacher@itqan.com',
            'e2e-academic@itqan.com',
            'e2e-parent@itqan.com',
        ])->pluck('id');

        $notifCount = \Illuminate\Support\Facades\DB::table('notifications')
            ->where('notifiable_type', \App\Models\User::class)
            ->whereIn('notifiable_id', $e2eUserIds)
            ->where('data->title', 'like', "{$prefix}%")
            ->count();
        $this->line("  Notifications: {$notifCount}");

        // Parent profiles
        $parentCount = \App\Models\ParentProfile::withoutGlobalScopes()
            ->where('academy_id', $academyId)
            ->whereHas('user', fn ($q) => $q->where('email', 'e2e-parent@itqan.com'))
            ->count();
        $this->line("  Parent profiles: {$parentCount}");

        // Parent user
        $parentUserExists = \App\Models\User::where('email', 'e2e-parent@itqan.com')->exists();
        $this->line('  Parent user (e2e-parent@itqan.com): '.($parentUserExists ? 'EXISTS' : 'not found'));

        // Chat conversations
        $chatCount = 0;
        if (class_exists(\Namu\WireChat\Models\Conversation::class) && $e2eUserIds->isNotEmpty()) {
            try {
                $convIds = \Illuminate\Support\Facades\DB::table('wire_participants')
                    ->where('participantable_type', \App\Models\User::class)
                    ->whereIn('participantable_id', $e2eUserIds)
                    ->pluck('conversation_id')
                    ->unique();
                $chatCount = $convIds->count();
            } catch (\Exception $e) {
                $chatCount = '?';
            }
        }
        $this->line("  Chat conversations (involving E2E users): {$chatCount}");

        $this->newLine();
        $this->info('Run without --dry-run to delete.');

        return self::SUCCESS;
    }
}
