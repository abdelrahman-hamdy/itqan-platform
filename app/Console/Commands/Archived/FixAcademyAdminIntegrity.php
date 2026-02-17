<?php

namespace App\Console\Commands\Archived;

use Exception;
use App\Models\Academy;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixAcademyAdminIntegrity extends Command
{
    protected $signature = 'academy:fix-admin-integrity
                            {--dry-run : Show what would be fixed without making changes}
                            {--fix : Actually fix the issues}';

    protected $description = 'Check and fix Academy-Admin data integrity issues';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $fix = $this->option('fix');

        if (! $dryRun && ! $fix) {
            $this->info('Use --dry-run to see issues or --fix to resolve them.');

            return self::SUCCESS;
        }

        $this->info($dryRun ? 'Running in DRY-RUN mode (no changes will be made)' : 'Running in FIX mode');
        $this->newLine();

        $issues = [];

        // 1. Find academies without admin
        $this->info('1. Checking academies without admin...');
        $academiesWithoutAdmin = Academy::whereNull('admin_id')->get();
        if ($academiesWithoutAdmin->count() > 0) {
            $this->warn("   Found {$academiesWithoutAdmin->count()} academies without admin:");
            foreach ($academiesWithoutAdmin as $academy) {
                $this->line("   - [{$academy->id}] {$academy->name}");
                $issues[] = ['type' => 'academy_no_admin', 'academy' => $academy];
            }
        } else {
            $this->info('   All academies have admins assigned.');
        }
        $this->newLine();

        // 2. Find admins with academy_id but academy.admin_id doesn't match
        $this->info('2. Checking admin users with mismatched academy assignments...');
        $adminsWithAcademy = User::where('user_type', 'admin')
            ->whereNotNull('academy_id')
            ->get();

        $mismatchedAdmins = [];
        foreach ($adminsWithAcademy as $admin) {
            $academy = Academy::find($admin->academy_id);
            if ($academy && $academy->admin_id !== $admin->id) {
                $mismatchedAdmins[] = [
                    'admin' => $admin,
                    'academy' => $academy,
                    'actual_admin_id' => $academy->admin_id,
                ];
            }
        }

        if (count($mismatchedAdmins) > 0) {
            $this->warn('   Found '.count($mismatchedAdmins).' mismatched admin assignments:');
            foreach ($mismatchedAdmins as $mismatch) {
                $this->line("   - Admin [{$mismatch['admin']->id}] {$mismatch['admin']->name} has academy_id={$mismatch['academy']->id}");
                $this->line("     but Academy [{$mismatch['academy']->id}] {$mismatch['academy']->name} has admin_id={$mismatch['actual_admin_id']}");
                $issues[] = ['type' => 'mismatch', 'data' => $mismatch];
            }
        } else {
            $this->info('   All admin assignments are consistent.');
        }
        $this->newLine();

        // 3. Find duplicate admins per academy (multiple admins with same academy_id)
        $this->info('3. Checking for duplicate admins per academy...');
        $duplicates = User::where('user_type', 'admin')
            ->whereNotNull('academy_id')
            ->select('academy_id', DB::raw('COUNT(*) as count'))
            ->groupBy('academy_id')
            ->having('count', '>', 1)
            ->get();

        if ($duplicates->count() > 0) {
            $this->warn("   Found {$duplicates->count()} academies with multiple admins:");
            foreach ($duplicates as $dup) {
                $academy = Academy::find($dup->academy_id);
                $admins = User::where('user_type', 'admin')
                    ->where('academy_id', $dup->academy_id)
                    ->get();

                $this->line("   - Academy [{$dup->academy_id}] ".($academy?->name ?? 'Unknown')." has {$dup->count} admins:");
                foreach ($admins as $admin) {
                    $isAssigned = $academy && $academy->admin_id === $admin->id;
                    $marker = $isAssigned ? ' (ASSIGNED)' : '';
                    $this->line("     * [{$admin->id}] {$admin->name}{$marker}");
                }
                $issues[] = ['type' => 'duplicate', 'academy_id' => $dup->academy_id, 'admins' => $admins];
            }
        } else {
            $this->info('   No duplicate admins found.');
        }
        $this->newLine();

        // 4. Find academies with admin_id pointing to non-admin user
        $this->info('4. Checking for invalid admin_id references...');
        $academiesWithInvalidAdmin = Academy::whereNotNull('admin_id')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('users')
                    ->whereColumn('users.id', 'academies.admin_id')
                    ->where('users.user_type', 'admin');
            })
            ->get();

        if ($academiesWithInvalidAdmin->count() > 0) {
            $this->warn("   Found {$academiesWithInvalidAdmin->count()} academies with invalid admin_id:");
            foreach ($academiesWithInvalidAdmin as $academy) {
                $user = User::find($academy->admin_id);
                $this->line("   - Academy [{$academy->id}] {$academy->name} has admin_id={$academy->admin_id}");
                if ($user) {
                    $this->line("     User exists but is not admin (user_type={$user->user_type})");
                } else {
                    $this->line('     User does not exist!');
                }
                $issues[] = ['type' => 'invalid_admin', 'academy' => $academy, 'user' => $user];
            }
        } else {
            $this->info('   All admin_id references are valid.');
        }
        $this->newLine();

        // Summary
        $this->info('=== SUMMARY ===');
        $this->line('Total issues found: '.count($issues));

        if (count($issues) === 0) {
            $this->info('No data integrity issues found!');

            return self::SUCCESS;
        }

        if ($fix) {
            $this->newLine();
            $this->info('=== FIXING ISSUES ===');

            DB::beginTransaction();
            try {
                $fixed = 0;

                foreach ($issues as $issue) {
                    switch ($issue['type']) {
                        case 'mismatch':
                            // Sync user.academy_id to match academy.admin_id
                            $admin = $issue['data']['admin'];
                            $academy = $issue['data']['academy'];

                            // Clear this admin's academy_id since they're not the actual admin
                            $admin->timestamps = false;
                            $admin->update(['academy_id' => null]);
                            $this->line("Fixed: Cleared academy_id for admin [{$admin->id}] {$admin->name}");
                            $fixed++;
                            break;

                        case 'duplicate':
                            // Keep only the admin who is actually assigned via admin_id
                            $academyId = $issue['academy_id'];
                            $academy = Academy::find($academyId);
                            $admins = $issue['admins'];

                            foreach ($admins as $admin) {
                                if (! $academy || $academy->admin_id !== $admin->id) {
                                    $admin->timestamps = false;
                                    $admin->update(['academy_id' => null]);
                                    $this->line("Fixed: Cleared academy_id for duplicate admin [{$admin->id}] {$admin->name}");
                                    $fixed++;
                                }
                            }
                            break;

                        case 'invalid_admin':
                            // Clear invalid admin_id
                            $academy = $issue['academy'];
                            $academy->timestamps = false;
                            $academy->update(['admin_id' => null]);
                            $this->line("Fixed: Cleared invalid admin_id for academy [{$academy->id}] {$academy->name}");
                            $fixed++;
                            break;

                        case 'academy_no_admin':
                            $this->warn("Cannot auto-fix: Academy [{$issue['academy']->id}] {$issue['academy']->name} needs manual admin assignment");
                            break;
                    }
                }

                DB::commit();
                $this->newLine();
                $this->info("Fixed {$fixed} issues. Run with --dry-run to verify.");
            } catch (Exception $e) {
                DB::rollBack();
                $this->error('Error fixing issues: '.$e->getMessage());

                return self::FAILURE;
            }
        } else {
            $this->newLine();
            $this->info('Run with --fix to resolve these issues.');
        }

        return self::SUCCESS;
    }
}
