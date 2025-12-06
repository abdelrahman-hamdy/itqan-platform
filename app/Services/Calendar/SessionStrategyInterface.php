<?php

namespace App\Services\Calendar;

use App\Services\Scheduling\Validators\ScheduleValidatorInterface;
use Illuminate\Support\Collection;

/**
 * Strategy interface for calendar session management
 *
 * Defines the contract for handling different session types (Quran, Academic)
 * in the unified teacher calendar system.
 */
interface SessionStrategyInterface
{
    /**
     * Get all schedulable items for the current teacher
     *
     * @return Collection Collection of schedulable items with metadata
     */
    public function getSchedulableItems(): Collection;

    /**
     * Get tab configuration for the calendar interface
     *
     * Returns array of tabs with their configuration:
     * [
     *   'tab_key' => [
     *     'label' => 'Tab Label',
     *     'icon' => 'heroicon-o-icon',
     *     'items_method' => 'getMethodName'
     *   ]
     * ]
     *
     * @return array Tab configuration array
     */
    public function getTabConfiguration(): array;

    /**
     * Get the appropriate validator for a given item type
     *
     * @param string $itemType Type of item (e.g., 'group_circle', 'individual_circle', 'private_lesson')
     * @param mixed $item The actual item model instance
     * @return ScheduleValidatorInterface Validator instance for this item type
     */
    public function getValidator(string $itemType, $item): ScheduleValidatorInterface;

    /**
     * Create schedule sessions based on validated data
     *
     * @param array $data Form data containing schedule details
     * @param ScheduleValidatorInterface $validator Validator instance to use
     * @return void
     */
    public function createSchedule(array $data, ScheduleValidatorInterface $validator): void;

    /**
     * Get footer widgets for the calendar page
     *
     * @return array Array of widget class names
     */
    public function getFooterWidgets(): array;

    /**
     * Get available session types for this strategy
     *
     * @return array Array of session type identifiers
     */
    public function getSessionTypes(): array;

    /**
     * Get the section heading for the calendar management area
     *
     * @return string Section heading text
     */
    public function getSectionHeading(): string;

    /**
     * Get the section description for the calendar management area
     *
     * @return string Section description text
     */
    public function getSectionDescription(): string;

    /**
     * Get the label for the tabs component
     *
     * @return string Tabs label text
     */
    public function getTabsLabel(): string;
}
