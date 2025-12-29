# API Resources Quick Reference

Quick reference guide for all available API Resource classes in the Itqan Platform.

## Total Resources: 30 files

### Newly Created (9 files)
1. TeacherCollection.php
2. StudentCollection.php
3. PaymentCollection.php
4. AttendanceCollection.php
5. CircleCollection.php
6. HomeworkCollection.php
7. QuizCollection.php
8. QuizAttemptResource.php
9. QuizAttemptCollection.php

### Already Existing (21 files)
Sessions, Subscriptions, Teachers, Students, Payments, Attendance, Homework, Quiz, Circles, Academy, User resources

## Quick Usage

### With ApiResponseService
```php
use App\Http\Controllers\Traits\ApiResponses;
use App\Http\Resources\Api\V1\Session\SessionResource;

return $this->successResponse(
    data: SessionResource::make($session),
    message: __('Success')
);
```

### Collections with Metadata
```php
use App\Http\Resources\Api\V1\Payment\PaymentCollection;

return new PaymentCollection($payments);
// Returns data + financial statistics
```

See API_RESOURCES_COMPLETE_GUIDE.md for full documentation.
