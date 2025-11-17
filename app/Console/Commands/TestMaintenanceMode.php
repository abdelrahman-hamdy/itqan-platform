<?php

namespace App\Console\Commands;

use App\Models\Academy;
use Illuminate\Console\Command;

class TestMaintenanceMode extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:maintenance';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the maintenance mode feature for academies';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing Maintenance Mode Feature');
        $this->info('=================================');
        $this->newLine();

        // Get all academies
        $academies = Academy::all();

        if ($academies->isEmpty()) {
            $this->error('No academies found in the database.');
            return Command::FAILURE;
        }

        $this->info("Found {$academies->count()} academies:");
        $this->newLine();

        $headers = ['ID', 'Name', 'Subdomain', 'Maintenance Mode', 'Status'];
        $rows = [];

        foreach ($academies as $academy) {
            $rows[] = [
                $academy->id,
                $academy->name,
                $academy->subdomain,
                $academy->maintenance_mode ? '✅ ON' : '❌ OFF',
                $academy->status_display,
            ];
        }

        $this->table($headers, $rows);
        $this->newLine();

        // Test enabling maintenance mode for the first academy
        $firstAcademy = $academies->first();
        $this->info("Testing maintenance mode toggle for: {$firstAcademy->name}");
        $this->newLine();

        // Enable maintenance mode
        $this->info('Enabling maintenance mode...');
        $firstAcademy->maintenance_mode = true;
        $firstAcademy->academic_settings = array_merge(
            $firstAcademy->academic_settings ?? [],
            ['maintenance_message' => 'نقوم حالياً بتحديث النظام لتحسين الأداء. نعتذر عن الإزعاج.']
        );
        $firstAcademy->save();

        $this->info('✅ Maintenance mode enabled with custom message');

        // Verify the change
        $firstAcademy->refresh();
        if ($firstAcademy->maintenance_mode) {
            $this->info('✅ Maintenance mode is confirmed ON');
            $this->info('✅ Custom message: ' . $firstAcademy->academic_settings['maintenance_message']);
        } else {
            $this->error('❌ Failed to enable maintenance mode');
        }

        $this->newLine();

        // Ask if user wants to keep it enabled
        if ($this->confirm('Do you want to disable maintenance mode now?', true)) {
            // Disable maintenance mode
            $firstAcademy->maintenance_mode = false;
            $firstAcademy->save();

            $this->info('✅ Maintenance mode disabled');

            // Verify the change
            $firstAcademy->refresh();
            if (!$firstAcademy->maintenance_mode) {
                $this->info('✅ Maintenance mode is confirmed OFF');
            } else {
                $this->error('❌ Failed to disable maintenance mode');
            }
        }

        $this->newLine();
        $this->info('=================================');
        $this->info('Test completed successfully!');
        $this->info('=================================');
        $this->newLine();

        $this->info('To test the frontend:');
        $this->info('1. Enable maintenance mode for an academy in Filament admin panel');
        $this->info('2. Visit the academy\'s subdomain URL');
        $this->info('3. You should see the maintenance page');
        $this->info('4. Admin users should be able to bypass it');

        return Command::SUCCESS;
    }
}