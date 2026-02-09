<?php

namespace App\Enums;

/**
 * Homework Grading Action Enum
 *
 * Defines the possible actions when grading homework.
 */
enum HomeworkGradingAction: string
{
    case GRADE_AND_RETURN = 'grade_and_return';
    case UPDATE_GRADE = 'update_grade';
    case RETURN_TO_STUDENT = 'return_to_student';

    /**
     * Get the localized label.
     */
    public function label(): string
    {
        return match ($this) {
            self::GRADE_AND_RETURN => __('enums.homework_grading_action.grade_and_return'),
            self::UPDATE_GRADE => __('enums.homework_grading_action.update_grade'),
            self::RETURN_TO_STUDENT => __('enums.homework_grading_action.return_to_student'),
        };
    }
}
