<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\Country;
use App\Enums\EducationalQualification;
use App\Enums\RelationshipType;
use App\Enums\TeachingLanguage;
use App\Enums\WeekDays;
use App\Http\Controllers\Controller;
use App\Http\Traits\Api\ApiResponses;
use App\Models\AcademicGradeLevel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controller for profile form options
 *
 * Provides dynamic dropdown options for profile edit forms
 * in the mobile app. This ensures the mobile app stays in sync
 * with the web version's options.
 */
class ProfileOptionsController extends Controller
{
    use ApiResponses;

    /**
     * Get all profile form options
     *
     * Returns options needed for student, teacher, and parent profile forms.
     * Grade levels are tenant-scoped to the user's academy.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $academyId = $user?->academy_id;

        // Get grade levels for the user's academy
        $gradeLevels = [];
        if ($academyId) {
            $gradeLevels = AcademicGradeLevel::where('academy_id', $academyId)
                ->where('is_active', true)
                ->ordered()
                ->get()
                ->map(fn ($level) => [
                    'id' => $level->id,
                    'name' => $level->name,
                    'name_en' => $level->name_en,
                    'display_name' => $level->getDisplayName(),
                ])
                ->values()
                ->toArray();
        }

        return $this->success([
            // Common options
            'countries' => $this->getCountries(),
            'genders' => $this->getGenders(),

            // Student options
            'grade_levels' => $gradeLevels,

            // Teacher options
            'educational_qualifications' => $this->getEducationalQualifications(),
            'teaching_languages' => $this->getTeachingLanguages(),
            'week_days' => $this->getWeekDays(),

            // Parent options
            'relationship_types' => $this->getRelationshipTypes(),
        ], __('Profile options retrieved successfully'));
    }

    /**
     * Get countries list with localized labels
     */
    private function getCountries(): array
    {
        return collect(Country::cases())
            ->map(fn (Country $country) => [
                'value' => $country->value,
                'label' => $country->label(),
            ])
            ->values()
            ->toArray();
    }

    /**
     * Get gender options
     */
    private function getGenders(): array
    {
        return [
            ['value' => 'male', 'label' => __('enums.gender.male')],
            ['value' => 'female', 'label' => __('enums.gender.female')],
        ];
    }

    /**
     * Get educational qualification options
     */
    private function getEducationalQualifications(): array
    {
        return collect(EducationalQualification::cases())
            ->map(fn (EducationalQualification $qualification) => [
                'value' => $qualification->value,
                'label' => $qualification->label(),
            ])
            ->values()
            ->toArray();
    }

    /**
     * Get teaching language options
     */
    private function getTeachingLanguages(): array
    {
        return collect(TeachingLanguage::cases())
            ->map(fn (TeachingLanguage $language) => [
                'value' => $language->value,
                'label' => $language->label(),
            ])
            ->values()
            ->toArray();
    }

    /**
     * Get week days options
     */
    private function getWeekDays(): array
    {
        return collect(WeekDays::cases())
            ->map(fn (WeekDays $day) => [
                'value' => $day->value,
                'label' => $day->label(),
            ])
            ->values()
            ->toArray();
    }

    /**
     * Get relationship type options (for parents)
     */
    private function getRelationshipTypes(): array
    {
        return collect(RelationshipType::cases())
            ->map(fn (RelationshipType $type) => [
                'value' => $type->value,
                'label' => $type->label(),
            ])
            ->values()
            ->toArray();
    }
}
