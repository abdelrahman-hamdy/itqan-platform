<?php

namespace App\Http\Requests\Api\V1\Teacher\Calendar;

use App\Services\AcademyContextService;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class BatchScheduleSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->attributes->has('effective_teacher');
    }

    public function rules(): array
    {
        $todayInAcademy = Carbon::now(AcademyContextService::getTimezone())->toDateString();

        $scheduleDaysRules = $this->input('item_type') === 'trial'
            ? ['array']
            : ['required', 'array', 'min:1'];

        return [
            'teacher_id' => ['nullable', 'integer'],
            'item_id' => ['required', 'integer'],
            'item_type' => ['required', 'string', 'in:group,individual,trial,private_lesson,interactive_course'],
            'schedule_days' => $scheduleDaysRules,
            'schedule_days.*' => ['string', 'in:Saturday,Sunday,Monday,Tuesday,Wednesday,Thursday,Friday'],
            'schedule_time' => ['required', 'string', 'regex:/^([01]\d|2[0-3]):(00|15|30|45)$/'],
            'schedule_start_date' => ['required', 'date', "after_or_equal:{$todayInAcademy}"],
            'session_count' => ['required', 'integer', 'min:1', 'max:52'],
        ];
    }

    public function messages(): array
    {
        return [
            'schedule_time.regex' => __('calendar.quarter_hour_required'),
        ];
    }
}
