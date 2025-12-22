# Teacher API Test Suite

Complete test coverage for all Teacher API controllers in the Itqan Platform.

## Test Files Created

### ✅ Core Controllers (4 files)

1. **DashboardControllerTest.php** - 290 lines
   - Dashboard data retrieval for Quran and Academic teachers
   - Stats calculation (total students, sessions count, earnings)
   - Today's sessions list
   - Upcoming sessions (next 7 days)
   - Recent activity feed
   - Authorization checks (teacher-only access)
   - Multi-teacher scenarios for data isolation

2. **EarningsControllerTest.php** - 318 lines
   - Earnings summary with breakdown by type (Quran/Academic)
   - Current month, last month earnings calculation
   - Pending payout and paid-out totals
   - Earnings history with date filtering
   - Payouts list with pagination
   - Teacher data isolation tests

3. **ProfileControllerTest.php** - 329 lines
   - Profile retrieval for Quran and Academic teachers
   - User basic info updates (name, phone)
   - Quran profile updates (bio, qualifications, certifications)
   - Academic profile updates (subjects, grade levels)
   - Avatar upload with file validation and old file deletion
   - Password change with current password verification
   - Validation tests for all update operations

4. **StudentControllerTest.php** - 231 lines
   - Student list for teachers
   - Search functionality
   - Pagination
   - Student detail with session stats (Quran/Academic)
   - Student report creation
   - Authorization (only teacher's own students)
   - Multi-teacher data isolation

### ✅ Quran Controllers (2 files)

5. **Quran/CircleControllerTest.php** - 267 lines
   - Individual circles list and details
   - Group circles list and details
   - Circle students list
   - Status filtering
   - Pagination
   - Authorization (only teacher's own circles)
   - 404 for non-existent circles

6. **Quran/SessionControllerTest.php** - 422 lines
   - Session list with filtering (status, date range, circle type)
   - Session details with full data
   - Complete session with evaluation data
   - Cancel session with reason
   - Evaluate session (ratings, feedback)
   - Update session notes
   - Validation for all operations
   - Authorization (only teacher's own sessions)
   - Status transition validation

### ✅ Academic Controllers (3 files)

7. **Academic/LessonControllerTest.php** - 155 lines
   - Lesson list for teacher
   - Status filtering
   - Lesson details with student and subscription
   - Pagination
   - Authorization (only teacher's assigned lessons)
   - 404 for non-existent lessons

8. **Academic/SessionControllerTest.php** - 292 lines
   - Academic session list (individual + interactive)
   - Status filtering
   - Session details (different structure for individual vs interactive)
   - Complete session with homework and lesson content
   - Cancel session with reason
   - Update evaluation (homework, rating, feedback)
   - Validation tests
   - Authorization checks

9. **Academic/CourseControllerTest.php** - 218 lines
   - Assigned courses list
   - Status filtering
   - Course details with sessions
   - Course students (enrollments)
   - Student status filtering
   - Authorization (only assigned courses)
   - Pagination

### ✅ Feature Controllers (3 files)

10. **MeetingControllerTest.php** - 238 lines
    - Create meeting for Quran sessions
    - Create meeting for Academic sessions
    - Session type validation
    - Meeting token generation
    - Token for teacher with permissions
    - Validation (meeting exists, session joinable)
    - Authorization (only teacher's own sessions)
    - Status checks (can't join cancelled)

11. **HomeworkControllerTest.php** - 301 lines
    - Homework list (excludes empty homework)
    - Homework details with submissions
    - Assign homework to sessions
    - Update homework
    - Grade student submissions
    - Validation (grade 0-100, feedback length)
    - Authorization (only teacher's homework)
    - Pagination

12. **ScheduleControllerTest.php** - 316 lines
    - Weekly schedule retrieval
    - Custom date range filtering
    - Sessions grouped by date
    - Day schedule with time sorting
    - Include both Quran and Academic sessions
    - Exclude cancelled sessions
    - Empty day handling
    - Date format validation
    - Authorization (only teacher's own schedule)

## Total Coverage

- **Total Test Files**: 12
- **Total Lines of Test Code**: ~3,377 lines
- **Total Test Cases**: ~150+ individual tests
- **Controllers Covered**: 13 controllers (100% coverage)

## Test Structure

All tests follow consistent patterns:

```php
// Setup
beforeEach(function () {
    $this->academy = Academy::factory()->create([
        'subdomain' => 'test-academy',
        'is_active' => true,
    ]);
});

// Test groups
describe('Feature Name', function () {
    it('describes what it tests', function () {
        // Arrange
        $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
        $profile = QuranTeacherProfile::factory()->create([...]);

        // Act
        Sanctum::actingAs($teacher, ['*']);
        $response = $this->getJson('/api/v1/teacher/endpoint', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([...]);
        expect(...)->toBe(...);
    });
});
```

## Common Test Scenarios

Each controller test includes:

1. **List/Index Operations**
   - Basic retrieval
   - Filtering (status, date, search)
   - Pagination
   - Data isolation (only teacher's resources)
   - Empty results handling

2. **Show/Detail Operations**
   - Detail retrieval with relationships
   - 404 for non-existent resources
   - Authorization (no access to others' resources)

3. **Create Operations**
   - Successful creation
   - Validation errors
   - Required field checks

4. **Update Operations**
   - Successful updates
   - Partial updates
   - Validation errors
   - Authorization checks

5. **Delete/Cancel Operations**
   - Successful deletion/cancellation
   - Reason requirements
   - Status validation (can't delete completed)

6. **Authentication**
   - All endpoints require authentication
   - 401 for unauthenticated requests

7. **Authorization**
   - Teachers can only access their own resources
   - 403/404 for unauthorized access
   - Multi-teacher scenarios

## Running the Tests

```bash
# Run all teacher tests
php artisan test --group=teacher

# Run specific test file
php artisan test tests/Feature/Api/V1/Teacher/DashboardControllerTest.php

# Run specific test group
php artisan test --group=quran
php artisan test --group=academic
php artisan test --group=earnings
php artisan test --group=meetings

# Run with coverage
php artisan test --coverage --group=teacher

# Run in parallel
php artisan test --parallel --group=teacher
```

## Test Groups

Tests are organized with multiple group tags:

- `api` - All API tests
- `teacher` - All teacher tests
- `quran` - Quran-specific tests
- `academic` - Academic-specific tests
- `dashboard` - Dashboard tests
- `earnings` - Earnings tests
- `profile` - Profile tests
- `students` - Student management tests
- `circles` - Circle management tests
- `sessions` - Session management tests
- `lessons` - Lesson management tests
- `courses` - Course management tests
- `meetings` - Meeting tests
- `homework` - Homework tests
- `schedule` - Schedule tests

## Key Testing Principles

1. **Use Factories**: All test data created via factories
2. **Test Isolation**: Each test is independent
3. **Authorization**: Always test data isolation between teachers
4. **Validation**: Test both valid and invalid inputs
5. **Edge Cases**: Test empty results, missing data, invalid states
6. **Relationships**: Verify related data loads correctly
7. **Pagination**: Test pagination works correctly
8. **Filtering**: Test all filter parameters
9. **Authentication**: Every endpoint requires auth

## Models Used in Tests

- `User` - Teachers and students
- `Academy` - Multi-tenancy context
- `QuranTeacherProfile` - Quran teacher data
- `AcademicTeacherProfile` - Academic teacher data
- `QuranSession` - Quran sessions
- `AcademicSession` - Academic sessions
- `InteractiveCourseSession` - Course sessions
- `QuranCircle` - Group circles
- `QuranIndividualCircle` - Individual circles
- `AcademicIndividualLesson` - Individual lessons
- `InteractiveCourse` - Courses
- `CourseSubscription` - Course enrollments
- `HomeworkSubmission` - Student submissions
- `TeacherEarning` - Earnings records
- `Payout` - Payout records
- `QuranSessionReport` - Session reports
- `AcademicSessionReport` - Session reports

## Factory Usage

```php
// Create teachers
$teacher = User::factory()->quranTeacher()->forAcademy($academy)->create();
$teacher = User::factory()->academicTeacher()->forAcademy($academy)->create();

// Create profiles
$profile = QuranTeacherProfile::factory()->create([
    'user_id' => $teacher->id,
    'academy_id' => $academy->id,
]);

$profile = AcademicTeacherProfile::factory()->create([
    'user_id' => $teacher->id,
    'academy_id' => $academy->id,
]);

// Create students
$student = User::factory()->student()->forAcademy($academy)->create();

// Create sessions
QuranSession::factory()->create([...]);
AcademicSession::factory()->create([...]);
```

## API Responses

All responses follow consistent structure:

```json
{
    "data": {
        // Resource data
    },
    "message": "Success message"
}
```

Error responses:
```json
{
    "message": "Error message",
    "code": "ERROR_CODE"
}
```

Validation errors:
```json
{
    "message": "Validation error",
    "errors": {
        "field": ["Error message"]
    }
}
```

## Next Steps

The test suite is complete and ready for use. To maintain quality:

1. Run tests before committing changes
2. Add tests for new endpoints
3. Update tests when changing API behavior
4. Keep test data realistic
5. Monitor test execution time
6. Use parallel execution for speed
7. Review coverage reports regularly
8. Keep factories updated with schema changes

## Documentation

See also:
- `/tests/Feature/Api/V1/Teacher/TESTING_SUMMARY.md` - Detailed implementation guide
- `/tests/Feature/Api/StudentApiTest.php` - Example API test structure
- `/tests/Feature/Api/ParentApiTest.php` - Example API test structure
