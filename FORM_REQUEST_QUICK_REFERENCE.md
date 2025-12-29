# Form Request Classes Quick Reference

## Quick Lookup Table

| Controller | Method | Form Request Class | Purpose |
|-----------|--------|-------------------|---------|
| **CalendarController** | | | |
| | `getEvents()` | `GetCalendarEventsRequest` | Fetch calendar events with filters |
| | `getAvailableSlots()` | `GetAvailableSlotsRequest` | Get available time slots for scheduling |
| | `checkConflicts()` | `CheckCalendarConflictsRequest` | Check for scheduling conflicts |
| | `getWeeklyAvailability()` | `GetWeeklyAvailabilityRequest` | Get teacher weekly availability |
| | `getStats()` | `GetCalendarStatsRequest` | Get calendar statistics |
| | `export()` | `ExportCalendarRequest` | Export calendar events |
| **QuranSessionController** | | | |
| | `updateNotes()` | `UpdateQuranSessionNotesRequest` | Update session notes |
| | `markCancelled()` | `CancelQuranSessionRequest` | Cancel a session |
| | `markAbsent()` | `MarkQuranSessionAbsentRequest` | Mark student as absent |
| | `addFeedback()` | `AddQuranSessionFeedbackRequest` | Add student feedback |
| **LiveKitController** | | | |
| | `getToken()` | `GetLiveKitTokenRequest` | Get LiveKit access token |
| | `muteParticipant()` | `MuteParticipantRequest` | Mute/unmute participant |
| | `getRoomParticipants()` | `GetRoomParticipantsRequest` | Get room participants |
| | `getRoomPermissions()` | `GetRoomPermissionsRequest` | Get room permissions |
| **LessonController** | | | |
| | `addNote()` | `AddLessonNoteRequest` | Add note to lesson |
| | `rate()` | `RateLessonRequest` | Rate a lesson |
| | `updateProgress()` | `UpdateLessonProgressRequest` | Update lesson progress |
| **QuranGroupCircleScheduleController** | | | |
| | `store()` | `StoreGroupCircleScheduleRequest` | Create/update circle schedule |
| | `previewSessions()` | `PreviewGroupCircleSessionsRequest` | Preview upcoming sessions |
| | `scheduleSession()` | `ScheduleGroupCircleSessionRequest` | Schedule single session |
| **MeetingDataChannelController** | | | |
| | `sendTeacherCommand()` | `SendTeacherCommandRequest` | Send teacher control command |
| | `acknowledgeMessage()` | `AcknowledgeMeetingMessageRequest` | Acknowledge meeting message |
| | `grantMicrophoneToStudent()` | `GrantMicrophoneToStudentRequest` | Grant microphone permission |
| **QuranIndividualCircleController** | | | |
| | `getAvailableTimeSlots()` | `GetAvailableTimeSlotsRequest` | Get available time slots |
| | `updateSettings()` | `UpdateIndividualCircleSettingsRequest` | Update circle settings |

## Form Request Classes by Feature

### Calendar Management
- `GetCalendarEventsRequest`
- `GetAvailableSlotsRequest`
- `CheckCalendarConflictsRequest`
- `GetWeeklyAvailabilityRequest`
- `GetCalendarStatsRequest`
- `ExportCalendarRequest`

### Quran Session Management
- `UpdateQuranSessionNotesRequest`
- `CancelQuranSessionRequest`
- `MarkQuranSessionAbsentRequest`
- `AddQuranSessionFeedbackRequest`

### LiveKit Video Conferencing
- `GetLiveKitTokenRequest`
- `MuteParticipantRequest`
- `GetRoomParticipantsRequest`
- `GetRoomPermissionsRequest`

### Lesson/Course Management
- `AddLessonNoteRequest`
- `RateLessonRequest`
- `UpdateLessonProgressRequest`

### Quran Circle Scheduling
- `StoreGroupCircleScheduleRequest`
- `PreviewGroupCircleSessionsRequest`
- `ScheduleGroupCircleSessionRequest`
- `GetAvailableTimeSlotsRequest`
- `UpdateIndividualCircleSettingsRequest`

### Meeting/Communication
- `SendTeacherCommandRequest`
- `AcknowledgeMeetingMessageRequest`
- `GrantMicrophoneToStudentRequest`

## Form Requests with Authorization Logic

Some Form Request classes include authorization checks in their `authorize()` method:

1. **GetWeeklyAvailabilityRequest**
   - Only allows teachers (quran_teacher or academic_teacher)

2. **MuteParticipantRequest**
   - Only allows teachers (quran_teacher or academic_teacher)

3. **GetRoomParticipantsRequest**
   - Only allows teachers (quran_teacher or academic_teacher)

## Common Validation Patterns

### Date/Time Validation
- `date` - Date format validation
- `after:now` - Must be in the future
- `after_or_equal:today` - Must be today or later
- `date_format:H:i` - Time format (HH:MM)
- `date_format:Y-m` - Month format (YYYY-MM)

### Range Validation
- `min:15|max:240` - Duration in minutes (15 to 240)
- `min:1|max:5` - Rating scale (1 to 5 stars)
- `min:7|max:90` - Days range (7 to 90)

### String Validation
- `string|max:255` - Short text (e.g., titles)
- `string|max:500` - Medium text (e.g., reasons)
- `string|max:1000` - Long text (e.g., notes, feedback)
- `string|max:5000` - Very long text (e.g., lesson content)

### Array Validation
- `array` - Must be an array
- `array|min:1` - Non-empty array
- `array.*.field` - Nested array field validation

### Enum Validation
- `in:value1,value2,...` - Must be one of the specified values
- Example: `in:ics,csv` for export formats
- Example: `in:sunday,monday,...` for weekdays

## Usage Example

### Before (Inline Validation):
```php
public function store(Request $request)
{
    $request->validate([
        'title' => 'required|string|max:255',
        'date' => 'required|date|after:now',
    ]);

    // Business logic...
}
```

### After (Form Request):
```php
use App\Http\Requests\StoreEventRequest;

public function store(StoreEventRequest $request)
{
    // Validation already done, proceed with business logic...
}
```

### Creating a Form Request:
```bash
php artisan make:request StoreEventRequest
```

### Form Request Structure:
```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Return true to allow all authenticated users
        // Or add custom authorization logic
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'date' => 'required|date|after:now',
        ];
    }
}
```

## Best Practices

1. **Naming Convention**: Use descriptive names that indicate the action
   - `Store{Resource}Request` for creation
   - `Update{Resource}Request` for updates
   - `Get{Resource}Request` for retrieval with validation

2. **Authorization**: Implement `authorize()` method when needed
   - Return `true` for public access
   - Add custom logic for role-based access

3. **Custom Messages**: Add custom error messages for better UX
   ```php
   public function messages(): array
   {
       return [
           'title.required' => 'The title field is required.',
       ];
   }
   ```

4. **Custom Attributes**: Define friendly attribute names
   ```php
   public function attributes(): array
   {
       return [
           'email' => 'email address',
       ];
   }
   ```

5. **Reusability**: Create base Form Requests for common patterns
   ```php
   abstract class BaseFormRequest extends FormRequest
   {
       // Common validation methods
   }
   ```

## Testing Form Requests

```php
use Tests\TestCase;
use App\Http\Requests\StoreEventRequest;

class StoreEventRequestTest extends TestCase
{
    public function test_validation_passes_with_valid_data()
    {
        $request = new StoreEventRequest();
        $validator = Validator::make([
            'title' => 'Test Event',
            'date' => now()->addDay()->toDateString(),
        ], $request->rules());

        $this->assertTrue($validator->passes());
    }
}
```

## Related Documentation

- Laravel Form Request Validation: https://laravel.com/docs/11.x/validation#form-request-validation
- Validation Rules Reference: https://laravel.com/docs/11.x/validation#available-validation-rules
- Authorization: https://laravel.com/docs/11.x/authorization
