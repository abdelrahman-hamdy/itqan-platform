# API Response Service Guide

## Overview

The `ApiResponseService` provides a standardized approach to formatting JSON responses across all 420+ API endpoints in the Itqan Platform. This service ensures consistency, reduces code duplication, and makes API responses predictable for frontend consumers.

## Architecture

### Files

1. **Service**: `app/Services/ApiResponseService.php`
   - Core service with all response formatting methods
   - Handles success, error, pagination, and specialized responses

2. **Trait**: `app/Http/Controllers/Traits/ApiResponses.php`
   - Provides convenient controller access to the service
   - Eliminates need to inject service in every controller

3. **Legacy Trait**: `app/Http/Traits/Api/ApiResponses.php`
   - Older trait with similar functionality
   - Can be gradually migrated to use the new service

## Response Format

All API responses follow this standardized structure:

```json
{
    "success": true|false,
    "message": "Operation message",
    "data": {...} | [...] | null,
    "errors": [...],      // Only for error responses
    "meta": {             // Only for paginated responses
        "current_page": 1,
        "last_page": 10,
        "per_page": 15,
        "total": 150,
        "from": 1,
        "to": 15
    }
}
```

## Usage in Controllers

### Step 1: Import the Trait

```php
<?php

namespace App\Http\Controllers\Api\V1\MyController;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\ApiResponses;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MyController extends Controller
{
    use ApiResponses;

    // Your controller methods...
}
```

### Step 2: Use Response Methods

## Available Methods

### 1. Success Response

```php
public function index(): JsonResponse
{
    $data = [
        'users' => User::all(),
        'count' => User::count(),
    ];

    return $this->successResponse(
        data: $data,
        message: __('Users retrieved successfully')
    );
}
```

**Response:**
```json
{
    "success": true,
    "message": "Users retrieved successfully",
    "data": {
        "users": [...],
        "count": 150
    }
}
```

### 2. Error Response

```php
public function show($id): JsonResponse
{
    $user = User::find($id);

    if (!$user) {
        return $this->errorResponse(
            message: __('User not found'),
            code: 404
        );
    }

    return $this->successResponse($user);
}
```

**Response:**
```json
{
    "success": false,
    "message": "User not found",
    "data": null
}
```

### 3. Not Found Response (404)

```php
public function show($id): JsonResponse
{
    $resource = Resource::find($id);

    if (!$resource) {
        return $this->notFoundResponse(__('Resource not found'));
    }

    return $this->successResponse($resource);
}
```

### 4. Unauthorized Response (401)

```php
public function secureAction(Request $request): JsonResponse
{
    if (!$request->user()->hasPermission('action')) {
        return $this->unauthorizedResponse(__('You are not authorized to perform this action'));
    }

    // Process action...
}
```

### 5. Forbidden Response (403)

```php
public function adminAction(Request $request): JsonResponse
{
    if (!$request->user()->isAdmin()) {
        return $this->forbiddenResponse(__('This action requires admin privileges'));
    }

    // Process admin action...
}
```

### 6. Validation Error Response (422)

```php
public function store(Request $request): JsonResponse
{
    $validator = Validator::make($request->all(), [
        'name' => 'required|string|max:255',
        'email' => 'required|email|unique:users',
    ]);

    if ($validator->fails()) {
        return $this->validationErrorResponse(
            errors: $validator->errors()->toArray(),
            message: __('Validation failed')
        );
    }

    // Create resource...
}
```

**Response:**
```json
{
    "success": false,
    "message": "Validation failed",
    "data": null,
    "errors": {
        "email": ["The email has already been taken."],
        "name": ["The name field is required."]
    }
}
```

### 7. Created Response (201)

```php
public function store(Request $request): JsonResponse
{
    $user = User::create($request->validated());

    return $this->createdResponse(
        data: $user,
        message: __('User created successfully')
    );
}
```

**Response (HTTP 201):**
```json
{
    "success": true,
    "message": "User created successfully",
    "data": {
        "id": 123,
        "name": "John Doe",
        "email": "john@example.com"
    }
}
```

### 8. No Content Response (204)

```php
public function destroy($id): JsonResponse
{
    $user = User::findOrFail($id);
    $user->delete();

    return $this->noContentResponse();
}
```

**Response (HTTP 204):**
```
null
```

### 9. Paginated Response

```php
public function index(Request $request): JsonResponse
{
    $users = User::query()
        ->with('profile')
        ->paginate($request->input('per_page', 15));

    return $this->paginatedResponse(
        paginator: $users,
        message: __('Users retrieved successfully')
    );
}
```

**Response:**
```json
{
    "success": true,
    "message": "Users retrieved successfully",
    "data": [
        {"id": 1, "name": "User 1"},
        {"id": 2, "name": "User 2"}
    ],
    "meta": {
        "current_page": 1,
        "last_page": 10,
        "per_page": 15,
        "total": 150,
        "from": 1,
        "to": 15
    }
}
```

### 10. Collection Response

```php
public function active(): JsonResponse
{
    $activeUsers = User::where('is_active', true)->get();

    return $this->collectionResponse(
        collection: $activeUsers,
        message: __('Active users retrieved successfully')
    );
}
```

**Response:**
```json
{
    "success": true,
    "message": "Active users retrieved successfully",
    "data": [
        {"id": 1, "name": "User 1"},
        {"id": 2, "name": "User 2"}
    ]
}
```

### 11. Custom Response

```php
public function stats(): JsonResponse
{
    return $this->customResponse(
        data: [
            'message' => __('Statistics retrieved'),
            'data' => ['total_users' => 150, 'active_users' => 120],
            'timestamp' => now()->toISOString()
        ],
        success: true
    );
}
```

### 12. Server Error Response (500)

```php
public function process(): JsonResponse
{
    try {
        // Risky operation...
    } catch (\Exception $e) {
        logger()->error('Processing failed', ['error' => $e->getMessage()]);

        return $this->serverErrorResponse(__('An unexpected error occurred'));
    }
}
```

### 13. Operation Result Response

```php
public function toggle($id): JsonResponse
{
    $user = User::findOrFail($id);
    $result = $user->toggleActive();

    return $this->operationResultResponse(
        success: $result,
        successMessage: __('User status updated successfully'),
        errorMessage: __('Failed to update user status'),
        data: $user->fresh()
    );
}
```

## Real-World Examples

### Example 1: Student Dashboard Controller

```php
namespace App\Http\Controllers\Api\V1\Student;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\ApiResponses;

class DashboardController extends Controller
{
    use ApiResponses;

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $studentProfile = $user->studentProfile()->first();

        // Not found check
        if (!$studentProfile) {
            return $this->notFoundResponse(__('Student profile not found.'));
        }

        $dashboardData = [
            'student' => [
                'id' => $studentProfile->id,
                'name' => $studentProfile->full_name,
                'student_code' => $studentProfile->student_code,
            ],
            'stats' => $this->getStats($user),
            'sessions' => $this->getTodaySessions($user),
        ];

        // Success response
        return $this->successResponse(
            data: $dashboardData,
            message: __('Dashboard data retrieved successfully')
        );
    }
}
```

### Example 2: Parent Dashboard Controller

```php
namespace App\Http\Controllers\Api\V1\ParentApi;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\ApiResponses;

class DashboardController extends Controller
{
    use ApiResponses;

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $parentProfile = $user->parentProfile()->first();

        if (!$parentProfile) {
            return $this->notFoundResponse(__('Parent profile not found.'));
        }

        $children = $this->getChildrenData($parentProfile);
        $stats = $this->calculateStats($children);

        return $this->successResponse(
            data: [
                'parent' => $parentProfile,
                'children' => $children,
                'stats' => $stats,
            ],
            message: __('Dashboard data retrieved successfully')
        );
    }
}
```

### Example 3: Teacher Dashboard Controller

```php
namespace App\Http\Controllers\Api\V1\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\ApiResponses;

class DashboardController extends Controller
{
    use ApiResponses;

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $dashboardData = [
            'teacher' => [
                'id' => $user->id,
                'name' => $user->name,
                'is_quran_teacher' => $user->isQuranTeacher(),
                'is_academic_teacher' => $user->isAcademicTeacher(),
            ],
            'stats' => $this->getStats($user),
            'today_sessions' => $this->getTodaySessions($user),
            'upcoming_sessions' => $this->getUpcomingSessions($user),
        ];

        return $this->successResponse(
            data: $dashboardData,
            message: __('Dashboard data retrieved successfully')
        );
    }
}
```

## Migration Guide

### Migrating Existing Controllers

#### Before (Direct response()->json())
```php
public function index()
{
    $users = User::all();

    return response()->json([
        'success' => true,
        'message' => 'Users retrieved',
        'data' => $users
    ], 200);
}
```

#### After (Using ApiResponses trait)
```php
use App\Http\Controllers\Traits\ApiResponses;

class UserController extends Controller
{
    use ApiResponses;

    public function index()
    {
        $users = User::all();

        return $this->successResponse(
            data: $users,
            message: __('Users retrieved')
        );
    }
}
```

### Benefits of Migration

1. **Consistency**: All responses follow the same format
2. **Less Code**: No need to manually construct response arrays
3. **Type Safety**: Method parameters provide clear expectations
4. **Localization**: Built-in support for `__()` translation
5. **Maintainability**: Changes to response format in one place
6. **Testing**: Easier to test standardized responses

## Best Practices

### 1. Always Use Localization

```php
// Good
return $this->successResponse($data, __('Operation successful'));

// Avoid
return $this->successResponse($data, 'Operation successful');
```

### 2. Provide Meaningful Messages

```php
// Good
return $this->notFoundResponse(__('Student with ID :id not found', ['id' => $id]));

// Avoid
return $this->notFoundResponse(__('Not found'));
```

### 3. Include Relevant Data

```php
// Good - Include helpful context
return $this->errorResponse(
    message: __('Cannot delete user with active sessions'),
    code: 400,
    errors: ['active_sessions' => $activeSessions->count()]
);

// Avoid - Generic error
return $this->errorResponse(__('Error'));
```

### 4. Use Named Parameters

```php
// Good - Clear and readable
return $this->successResponse(
    data: $users,
    message: __('Users retrieved'),
    code: 200
);

// Works but less clear
return $this->successResponse($users, __('Users retrieved'), 200);
```

### 5. Handle Collections Properly

```php
// Good - Use collectionResponse for Collections
$users = User::all();
return $this->collectionResponse($users);

// Good - Use paginatedResponse for paginators
$users = User::paginate(15);
return $this->paginatedResponse($users);

// Avoid - Don't convert to array manually
$users = User::all()->toArray();
return $this->successResponse($users);
```

## Testing

### Testing API Responses

```php
public function test_dashboard_returns_success_response()
{
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->getJson('/api/v1/student/dashboard');

    $response->assertOk()
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'student',
                'stats',
                'sessions'
            ]
        ])
        ->assertJson([
            'success' => true
        ]);
}
```

## Performance Considerations

The `ApiResponseService` is designed to be lightweight:

- Singleton pattern via service container (instantiated once per request)
- Minimal processing overhead
- No database queries or external calls
- Direct JSON serialization

## Future Enhancements

Potential future additions to the service:

1. **Response Caching**: Cache frequently accessed responses
2. **Response Compression**: Gzip compression for large responses
3. **API Versioning**: Version-specific response formats
4. **Response Transformers**: Custom data transformation pipelines
5. **Metrics Collection**: Track response times and patterns

## Summary

The `ApiResponseService` provides a robust, consistent, and maintainable approach to API responses across the Itqan Platform. By using the `ApiResponses` trait in your controllers, you get:

- Standardized response format
- Reduced code duplication
- Better maintainability
- Improved developer experience
- Easier testing

All API controllers should gradually migrate to use this service for consistency across the platform's 420+ API endpoints.
