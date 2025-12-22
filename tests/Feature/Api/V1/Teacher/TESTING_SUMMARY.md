# Teacher API Test Suite - Summary

## Created Test Files

The following comprehensive Pest PHP test files have been created for the Teacher API controllers:

### ✅ Completed Tests (7 files)

1. **DashboardControllerTest.php** - Tests teacher dashboard data
   - Dashboard data retrieval for Quran and Academic teachers
   - Stats calculation (students, sessions, earnings)
   - Today's sessions and upcoming sessions
   - Recent activity feed
   - Authorization checks

2. **EarningsControllerTest.php** - Tests teacher earnings and payouts
   - Earnings summary with breakdown by type
   - Earnings history with date filtering
   - Payouts list with pagination
   - Authorization and data isolation

3. **ProfileControllerTest.php** - Tests teacher profile management
   - Profile retrieval for both teacher types
   - Profile updates (user info, Quran profile, Academic profile)
   - Avatar upload with file validation
   - Password change with security checks

4. **StudentControllerTest.php** - Tests student management
   - Student list for teachers
   - Student detail with session stats
   - Student report creation
   - Search and pagination
   - Authorization checks

5. **Quran/CircleControllerTest.php** - Tests Quran circle management
   - Individual circles list and details
   - Group circles list and details
   - Circle students
   - Filtering and pagination
   - Authorization checks

6. **Quran/SessionControllerTest.php** - Tests Quran session management
   - Session list with filtering
   - Session details
   - Complete session with evaluation
   - Cancel session with reason
   - Evaluate session
   - Update notes
   - Validation and authorization

### 📋 Remaining Tests to Create (6 files)

The following test files need to be created following the same patterns:

7. **Academic/LessonControllerTest.php**
8. **Academic/SessionControllerTest.php**
9. **Academic/CourseControllerTest.php**
10. **MeetingControllerTest.php**
11. **HomeworkControllerTest.php**
12. **ScheduleControllerTest.php**

## Test Patterns and Best Practices

All tests follow these patterns:

### 1. Setup
```php
beforeEach(function () {
    $this->academy = Academy::factory()->create([
        'subdomain' => 'test-academy',
        'is_active' => true,
    ]);
});
```

### 2. Teacher Creation
```php
// Quran Teacher
$teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
$profile = QuranTeacherProfile::factory()->create([
    'user_id' => $teacher->id,
    'academy_id' => $this->academy->id,
]);

// Academic Teacher
$teacher = User::factory()->academicTeacher()->forAcademy($this->academy)->create();
$profile = AcademicTeacherProfile::factory()->create([
    'user_id' => $teacher->id,
    'academy_id' => $this->academy->id,
]);
```

### 3. Authentication
```php
Sanctum::actingAs($teacher, ['*']);

$response = $this->getJson('/api/v1/teacher/endpoint', [
    'X-Academy-Subdomain' => $this->academy->subdomain,
]);
```

### 4. Test Categories

Each controller test file should include these categories:

- **List/Index Tests**
  - Basic retrieval
  - Filtering (status, date, search)
  - Pagination
  - Authorization (only own resources)
  - Authentication required

- **Show/Detail Tests**
  - Detail retrieval
  - Authorization (no access to others' resources)
  - 404 for non-existent resources
  - Authentication required

- **Create Tests** (if applicable)
  - Successful creation
  - Validation errors
  - Authorization
  - Authentication required

- **Update Tests** (if applicable)
  - Successful update
  - Validation errors
  - Authorization (only own resources)
  - Authentication required

- **Delete Tests** (if applicable)
  - Successful deletion
  - Authorization
  - 404 for non-existent
  - Authentication required

- **Custom Action Tests**
  - Complete session
  - Cancel session
  - Evaluate
  - etc.

### 5. Common Assertions
```php
// Success response structure
$response->assertStatus(200)
    ->assertJsonStructure([
        'data' => [...],
        'message',
    ]);

// Validation error
$response->assertStatus(422)
    ->assertJsonValidationErrors(['field']);

// Authorization
$response->assertStatus(403);

// Not found
$response->assertStatus(404);

// Unauthenticated
$response->assertStatus(401);

// Database assertions
$this->assertDatabaseHas('table', ['field' => 'value']);
```

## Template for Remaining Tests

### Academic/LessonControllerTest.php Template

```php
<?php

use App\Models\Academy;
use App\Models\AcademicIndividualLesson;
use App\Models\AcademicTeacherProfile;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

uses()->group('api', 'teacher', 'academic', 'lessons');

beforeEach(function () {
    $this->academy = Academy::factory()->create([
        'subdomain' => 'test-academy',
        'is_active' => true,
    ]);
});

describe('Academic Lesson API', function () {
    describe('list lessons', function () {
        it('returns lessons for teacher', function () {
            // Test implementation
        });

        it('filters lessons by status', function () {
            // Test implementation
        });

        it('only shows teacher own lessons', function () {
            // Test implementation
        });

        it('paginates lessons', function () {
            // Test implementation
        });

        it('requires authentication', function () {
            // Test implementation
        });
    });

    describe('show lesson', function () {
        it('returns lesson details', function () {
            // Test implementation
        });

        it('prevents access to other teachers lessons', function () {
            // Test implementation
        });

        it('requires authentication', function () {
            // Test implementation
        });
    });
});
```

### Academic/SessionControllerTest.php Template

Similar to Quran/SessionControllerTest.php but for academic sessions:
- Test AcademicSession model
- Test academic-specific fields (homework, lesson_content, topics_covered)
- Test interactive course sessions
- Test evaluation and completion

### Academic/CourseControllerTest.php Template

```php
describe('Academic Course API', function () {
    describe('list courses', function () {
        it('returns assigned courses for teacher', function () {
            // Create InteractiveCourse with assigned_teacher_id
        });
    });

    describe('show course', function () {
        it('returns course details with sessions', function () {
            // Test course details including session list
        });
    });

    describe('course students', function () {
        it('returns enrolled students', function () {
            // Test CourseSubscription list
        });
    });
});
```

### MeetingControllerTest.php Template

```php
describe('Meeting API', function () {
    describe('create meeting', function () {
        it('creates meeting for quran session', function () {
            // Test LiveKit room creation
        });

        it('creates meeting for academic session', function () {
            // Test meeting creation
        });

        it('validates session access', function () {
            // Test authorization
        });
    });

    describe('get meeting token', function () {
        it('generates token for teacher', function () {
            // Test token generation
        });

        it('validates session status', function () {
            // Test session must be joinable
        });
    });
});
```

### HomeworkControllerTest.php Template

```php
describe('Homework API', function () {
    describe('list homework', function () {
        it('returns homework for academic teacher', function () {
            // Test homework list
        });
    });

    describe('show homework', function () {
        it('returns homework details with submissions', function () {
            // Test homework detail
        });
    });

    describe('assign homework', function () {
        it('assigns homework to session', function () {
            // Test homework assignment
        });
    });

    describe('grade submission', function () {
        it('grades student submission', function () {
            // Test grading
        });

        it('prevents grading other teachers submissions', function () {
            // Test authorization
        });
    });
});
```

### ScheduleControllerTest.php Template

```php
describe('Schedule API', function () {
    describe('weekly schedule', function () {
        it('returns sessions for date range', function () {
            // Test schedule retrieval
        });

        it('groups sessions by date', function () {
            // Test grouping
        });

        it('filters by date range', function () {
            // Test date filtering
        });
    });

    describe('day schedule', function () {
        it('returns sessions for specific day', function () {
            // Test day schedule
        });

        it('sorts sessions by time', function () {
            // Test sorting
        });
    });
});
```

## Running the Tests

```bash
# Run all teacher tests
php artisan test --group=teacher

# Run specific controller tests
php artisan test tests/Feature/Api/V1/Teacher/DashboardControllerTest.php

# Run tests for specific teacher type
php artisan test --group=quran
php artisan test --group=academic

# Run with coverage
php artisan test --coverage --group=teacher
```

## Key Testing Points

1. **Authorization**: Always test that teachers can only access their own resources
2. **Multi-teacher scenarios**: Create multiple teachers to test data isolation
3. **Validation**: Test both valid and invalid inputs
4. **Edge cases**: Test completed/cancelled sessions, empty lists, etc.
5. **Relationships**: Verify related data is loaded correctly
6. **Pagination**: Test pagination works correctly
7. **Filtering**: Test all filter parameters work as expected
8. **Authentication**: Always test unauthenticated access returns 401

## Notes

- All tests use `uses()->group()` for organization
- LazilyRefreshDatabase trait is used automatically by Pest
- Tests follow Arrange-Act-Assert pattern
- Factory data is realistic and follows database constraints
- API responses follow consistent structure with 'data' and 'message' keys
