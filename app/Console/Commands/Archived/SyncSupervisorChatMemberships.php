<?php

namespace App\Console\Commands\Archived;

use App\Models\Academy;
use Exception;
use App\Models\ChatGroup;
use App\Models\ChatGroupMember;
use App\Services\SupervisorResolutionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncSupervisorChatMemberships extends Command
{
    protected $signature = 'chat:sync-supervisors
                            {--dry-run : Preview changes without making them}
                            {--academy= : Process specific academy by subdomain}
                            {--fix-missing : Add supervisors to groups that are missing them}
                            {--update-old : Update groups with old/incorrect supervisors}';

    protected $description = 'Synchronize supervisor memberships in all chat groups based on current supervisor assignments.';

    protected SupervisorResolutionService $supervisorService;

    protected bool $dryRun;

    protected int $addedMemberships = 0;

    protected int $removedMemberships = 0;

    protected int $updatedGroups = 0;

    protected int $skippedGroups = 0;

    protected int $errors = 0;

    public function handle(SupervisorResolutionService $supervisorService): int
    {
        $this->supervisorService = $supervisorService;
        $this->dryRun = $this->option('dry-run');

        if ($this->dryRun) {
            $this->warn('🔍 DRY RUN MODE - No changes will be made');
            $this->newLine();
        }

        $this->info('📊 Analyzing chat group supervisor assignments...');
        $this->analyzeCurrentState();

        if ($this->option('fix-missing')) {
            $this->addMissingSupervisors();
        }

        if ($this->option('update-old')) {
            $this->updateIncorrectSupervisors();
        }

        // If no specific options, run both
        if (! $this->option('fix-missing') && ! $this->option('update-old')) {
            $this->addMissingSupervisors();
            $this->updateIncorrectSupervisors();
        }

        $this->newLine();
        $this->displaySummary();

        return self::SUCCESS;
    }

    protected function analyzeCurrentState(): void
    {
        $academy = null;
        if ($this->option('academy')) {
            $academy = Academy::where('subdomain', $this->option('academy'))->first();
            if (! $academy) {
                $this->error("Academy not found: {$this->option('academy')}");

                return;
            }
            $this->info("Analyzing academy: {$academy->name}");
        }

        // Count chat groups
        $groupQuery = ChatGroup::where('is_active', true);
        if ($academy) {
            $groupQuery->where('academy_id', $academy->id);
        }
        $totalGroups = $groupQuery->count();

        // Count groups with supervisors
        $withSupervisor = (clone $groupQuery)->whereNotNull('supervisor_id')->count();
        $withoutSupervisor = $totalGroups - $withSupervisor;

        $this->info("Total active chat groups: {$totalGroups}");
        $this->info("Groups with supervisor: {$withSupervisor}");
        $this->warn("Groups without supervisor: {$withoutSupervisor}");

        // Count groups where supervisor might be outdated
        $outdatedCount = $this->countGroupsWithOutdatedSupervisors($academy);
        if ($outdatedCount > 0) {
            $this->warn("Groups with potentially outdated supervisors: {$outdatedCount}");
        }

        $this->newLine();
    }

    protected function countGroupsWithOutdatedSupervisors($academy = null): int
    {
        $count = 0;
        $query = ChatGroup::where('is_active', true)->whereNotNull('supervisor_id');

        if ($academy) {
            $query->where('academy_id', $academy->id);
        }

        $groups = $query->with(['owner'])->get();

        foreach ($groups as $group) {
            // Get the current correct supervisor for the group owner (teacher)
            $owner = $group->owner;
            if (! $owner) {
                continue;
            }

            $currentSupervisor = $this->supervisorService->getSupervisorForTeacher($owner);

            // Check if the group's supervisor_id matches
            if ($currentSupervisor && $group->supervisor_id !== $currentSupervisor->id) {
                $count++;
            } elseif (! $currentSupervisor && $group->supervisor_id) {
                // Group has supervisor but teacher no longer has one
                $count++;
            }
        }

        return $count;
    }

    protected function addMissingSupervisors(): void
    {
        $this->newLine();
        $this->info('➕ Adding supervisors to groups missing them...');

        $academy = null;
        if ($this->option('academy')) {
            $academy = Academy::where('subdomain', $this->option('academy'))->first();
        }

        $query = ChatGroup::where('is_active', true)->whereNull('supervisor_id');
        if ($academy) {
            $query->where('academy_id', $academy->id);
        }

        $groups = $query->with(['owner', 'members'])->get();
        $progressBar = $this->output->createProgressBar($groups->count());

        foreach ($groups as $group) {
            try {
                $owner = $group->owner;
                if (! $owner) {
                    $this->skippedGroups++;
                    $progressBar->advance();

                    continue;
                }

                // Get supervisor for the owner (teacher)
                $supervisor = $this->supervisorService->getSupervisorForTeacher($owner);

                if (! $supervisor) {
                    // Teacher has no supervisor, skip
                    $this->skippedGroups++;
                    $progressBar->advance();

                    continue;
                }

                if (! $this->dryRun) {
                    DB::transaction(function () use ($group, $supervisor) {
                        // Update group's supervisor_id
                        $group->update(['supervisor_id' => $supervisor->id]);

                        // Add supervisor as member if not already
                        $existingMember = $group->members()->where('user_id', $supervisor->id)->first();
                        if (! $existingMember) {
                            ChatGroupMember::create([
                                'chat_group_id' => $group->id,
                                'user_id' => $supervisor->id,
                                'role' => 'supervisor',
                                'added_by' => null,
                            ]);
                            $this->addedMemberships++;
                        }
                    });
                }

                $this->updatedGroups++;
            } catch (Exception $e) {
                $this->errors++;
                Log::error('Error adding supervisor to group', [
                    'group_id' => $group->id,
                    'error' => $e->getMessage(),
                ]);
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();
    }

    protected function updateIncorrectSupervisors(): void
    {
        $this->newLine();
        $this->info('🔄 Updating groups with incorrect supervisors...');

        $academy = null;
        if ($this->option('academy')) {
            $academy = Academy::where('subdomain', $this->option('academy'))->first();
        }

        $query = ChatGroup::where('is_active', true)->whereNotNull('supervisor_id');
        if ($academy) {
            $query->where('academy_id', $academy->id);
        }

        $groups = $query->with(['owner', 'members'])->get();
        $progressBar = $this->output->createProgressBar($groups->count());

        foreach ($groups as $group) {
            try {
                $owner = $group->owner;
                if (! $owner) {
                    $this->skippedGroups++;
                    $progressBar->advance();

                    continue;
                }

                // Get current correct supervisor for the owner (teacher)
                $correctSupervisor = $this->supervisorService->getSupervisorForTeacher($owner);

                // Check if update is needed
                $needsUpdate = false;
                $oldSupervisorId = $group->supervisor_id;

                if ($correctSupervisor && $group->supervisor_id !== $correctSupervisor->id) {
                    $needsUpdate = true;
                } elseif (! $correctSupervisor && $group->supervisor_id) {
                    // Teacher no longer has supervisor
                    $needsUpdate = true;
                }

                if (! $needsUpdate) {
                    $progressBar->advance();

                    continue;
                }

                if (! $this->dryRun) {
                    DB::transaction(function () use ($group, $correctSupervisor, $oldSupervisorId) {
                        // Remove old supervisor membership
                        if ($oldSupervisorId) {
                            $removed = $group->members()
                                ->where('user_id', $oldSupervisorId)
                                ->where('role', 'supervisor')
                                ->delete();
                            if ($removed) {
                                $this->removedMemberships++;
                            }
                        }

                        // Update group's supervisor_id
                        $group->update(['supervisor_id' => $correctSupervisor?->id]);

                        // Add new supervisor as member
                        if ($correctSupervisor) {
                            $existingMember = $group->members()->where('user_id', $correctSupervisor->id)->first();
                            if (! $existingMember) {
                                ChatGroupMember::create([
                                    'chat_group_id' => $group->id,
                                    'user_id' => $correctSupervisor->id,
                                    'role' => 'supervisor',
                                    'added_by' => null,
                                ]);
                                $this->addedMemberships++;
                            }
                        }
                    });
                }

                $this->updatedGroups++;
            } catch (Exception $e) {
                $this->errors++;
                Log::error('Error updating supervisor for group', [
                    'group_id' => $group->id,
                    'error' => $e->getMessage(),
                ]);
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();
    }

    protected function displaySummary(): void
    {
        $this->newLine();
        $this->info('═══════════════════════════════════════════');
        $this->info('                 SUMMARY                    ');
        $this->info('═══════════════════════════════════════════');

        if ($this->dryRun) {
            $this->warn('DRY RUN - No changes were made');
            $this->newLine();
        }

        $this->info("Groups updated: {$this->updatedGroups}");
        $this->info("Supervisor memberships added: {$this->addedMemberships}");
        $this->info("Supervisor memberships removed: {$this->removedMemberships}");
        $this->info("Groups skipped (no teacher/supervisor): {$this->skippedGroups}");

        if ($this->errors > 0) {
            $this->error("Errors encountered: {$this->errors}");
            $this->warn('Check the logs for details.');
        }

        $this->info('═══════════════════════════════════════════');

        if (! $this->dryRun && $this->updatedGroups > 0) {
            $this->info('✅ Synchronization completed successfully!');
        }
    }
}
