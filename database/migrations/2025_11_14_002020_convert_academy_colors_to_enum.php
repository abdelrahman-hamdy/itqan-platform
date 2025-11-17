<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Enums\TailwindColor;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Map common hex colors to TailwindColor enum values
        $colorMap = [
            // Sky/Blue variations
            '#0ea5e9' => TailwindColor::SKY->value,
            '#0EA5E9' => TailwindColor::SKY->value,
            '#38bdf8' => TailwindColor::SKY->value,
            '#4169E1' => TailwindColor::BLUE->value,
            '#4169e1' => TailwindColor::BLUE->value,
            '#3b82f6' => TailwindColor::BLUE->value,
            '#2563eb' => TailwindColor::BLUE->value,

            // Green/Emerald variations
            '#10B981' => TailwindColor::EMERALD->value,
            '#10b981' => TailwindColor::EMERALD->value,
            '#22c55e' => TailwindColor::GREEN->value,
            '#16a34a' => TailwindColor::GREEN->value,

            // Cornflower Blue to Blue
            '#6495ED' => TailwindColor::BLUE->value,
            '#6495ed' => TailwindColor::BLUE->value,

            // Other common colors
            '#ef4444' => TailwindColor::RED->value,
            '#f97316' => TailwindColor::ORANGE->value,
            '#f59e0b' => TailwindColor::AMBER->value,
            '#eab308' => TailwindColor::YELLOW->value,
            '#84cc16' => TailwindColor::LIME->value,
            '#14b8a6' => TailwindColor::TEAL->value,
            '#06b6d4' => TailwindColor::CYAN->value,
            '#6366f1' => TailwindColor::INDIGO->value,
            '#8b5cf6' => TailwindColor::VIOLET->value,
            '#a855f7' => TailwindColor::PURPLE->value,
            '#d946ef' => TailwindColor::FUCHSIA->value,
            '#ec4899' => TailwindColor::PINK->value,
            '#f43f5e' => TailwindColor::ROSE->value,
        ];

        // Update academies table using raw queries to bypass enum casting
        $academies = DB::table('academies')->get();

        foreach ($academies as $academy) {
            $updates = [];

            // Convert brand_color if it's a hex value
            if ($academy->brand_color && isset($colorMap[$academy->brand_color])) {
                $updates['brand_color'] = $colorMap[$academy->brand_color];
            } elseif ($academy->brand_color && !in_array($academy->brand_color, array_column(TailwindColor::cases(), 'value'))) {
                // If it's not in the map and not already an enum value, default to sky
                $updates['brand_color'] = TailwindColor::SKY->value;
            }

            // Convert secondary_color if it's a hex value
            if ($academy->secondary_color && isset($colorMap[$academy->secondary_color])) {
                $updates['secondary_color'] = $colorMap[$academy->secondary_color];
            } elseif ($academy->secondary_color && !in_array($academy->secondary_color, array_column(TailwindColor::cases(), 'value'))) {
                // If it's not in the map and not already an enum value, default to emerald
                $updates['secondary_color'] = TailwindColor::EMERALD->value;
            }

            if (!empty($updates)) {
                DB::table('academies')->where('id', $academy->id)->update($updates);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverse map enum values back to hex colors
        $reverseMap = [
            TailwindColor::SKY->value => '#0ea5e9',
            TailwindColor::BLUE->value => '#3b82f6',
            TailwindColor::EMERALD->value => '#10B981',
            TailwindColor::GREEN->value => '#22c55e',
            TailwindColor::RED->value => '#ef4444',
            TailwindColor::ORANGE->value => '#f97316',
            TailwindColor::AMBER->value => '#f59e0b',
            TailwindColor::YELLOW->value => '#eab308',
            TailwindColor::LIME->value => '#84cc16',
            TailwindColor::TEAL->value => '#14b8a6',
            TailwindColor::CYAN->value => '#06b6d4',
            TailwindColor::INDIGO->value => '#6366f1',
            TailwindColor::VIOLET->value => '#8b5cf6',
            TailwindColor::PURPLE->value => '#a855f7',
            TailwindColor::FUCHSIA->value => '#d946ef',
            TailwindColor::PINK->value => '#ec4899',
            TailwindColor::ROSE->value => '#f43f5e',
        ];

        $academies = DB::table('academies')->get();

        foreach ($academies as $academy) {
            $updates = [];

            if ($academy->brand_color && isset($reverseMap[$academy->brand_color])) {
                $updates['brand_color'] = $reverseMap[$academy->brand_color];
            }

            if ($academy->secondary_color && isset($reverseMap[$academy->secondary_color])) {
                $updates['secondary_color'] = $reverseMap[$academy->secondary_color];
            }

            if (!empty($updates)) {
                DB::table('academies')->where('id', $academy->id)->update($updates);
            }
        }
    }
};
