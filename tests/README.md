# Itqan Platform Test Suite

This document describes the testing infrastructure for the Itqan Platform.

## Test Framework

The project uses **Pest PHP** as the testing framework with the following plugins:
- `pestphp/pest` - Core Pest framework
- `pestphp/pest-plugin-laravel` - Laravel integration
- `pestphp/pest-plugin-livewire` - Livewire component testing
- `pestphp/pest-plugin-faker` - Faker integration for test data
- `pestphp/pest-plugin-arch` - Architecture testing

## Running Tests

```bash
# Run all tests
php artisan test

# Run with coverage (requires Xdebug or PCOV)
php artisan test --coverage

# Run specific test files
php artisan test --filter=UserTest

# Run by test type
php artisan test tests/Unit
php artisan test tests/Feature
php artisan test tests/Architecture
```

## Test Structure

```
tests/
├── Architecture/           # Architecture tests
│   └── ArchitectureTest.php
├── Browser/                # Browser navigation tests
│   └── NavigationTest.php
├── Feature/                # Feature/Integration tests
│   ├── Api/
│   │   ├── AuthApiTest.php
│   │   ├── ParentApiTest.php
│   │   └── StudentApiTest.php
│   ├── Auth/
│   │   └── LoginTest.php
│   └── Livewire/
│       ├── AcademySelectorTest.php
│       └── NotificationCenterTest.php
├── Unit/                   # Unit tests
│   ├── Enums/
│   │   └── SessionStatusTest.php
│   ├── Models/
│   │   ├── QuranSessionTest.php
│   │   └── UserTest.php
│   ├── Policies/
│   │   └── SessionPolicyTest.php
│   └── Services/
│       ├── CalendarServiceTest.php
│       ├── NotificationServiceTest.php
│       └── SessionStatusServiceTest.php
├── Pest.php                # Pest configuration
└── TestCase.php            # Base test case
```

## Test Categories

### Architecture Tests (13 tests)
Tests architectural constraints like:
- Models extend Eloquent Model
- Controllers have Controller suffix
- Form requests extend FormRequest
- Services are classes
- Policies have Policy suffix
- Livewire components extend Component
- Enums are enums
- No dd/dump in production code
- Jobs implement ShouldQueue

### Unit Tests (145+ tests)

#### Models
- User model - relationships, roles, factories, status checks
- QuranSession model - factory states, relationships, status casting

#### Services
- CalendarService - session grouping, calendar queries
- NotificationService - notification creation, filtering
- SessionStatusService - status transitions, validation

#### Policies
- SessionPolicy - authorization rules, role-based access

#### Enums
- SessionStatus - enum values, transitions, colors

### Feature Tests (25+ tests)

#### Authentication
- Login page display
- User authentication with valid/invalid credentials
- Role-based authentication
- Logout functionality

#### API
- Token generation and validation
- Protected endpoint access
- Student API endpoints
- Parent API endpoints

#### Livewire Components
- AcademySelector component
- NotificationCenter component

## Test Helpers

Available in `tests/Pest.php`:

```php
// Create a super admin and act as them
asSuperAdmin();

// Create an admin for an academy
asAdmin($academy = null);

// Create an academy
createAcademy($attributes = []);

// Create a user of specific type
createUser($type = 'student', $academy = null, $attributes = []);
```

## Database

Tests use a separate MySQL database: `itqan_platform_test`

Configuration in `.env.testing`:
```
DB_DATABASE=itqan_platform_test
```

The `LazilyRefreshDatabase` trait is used for test isolation.

## Writing New Tests

### Using Pest Syntax

```php
<?php

use App\Models\User;

describe('User Model', function () {
    it('can be created', function () {
        $user = User::factory()->create();

        expect($user)->toBeInstanceOf(User::class);
    });
});
```

### Testing API Endpoints

```php
<?php

use App\Models\Academy;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

describe('API', function () {
    beforeEach(function () {
        $this->academy = Academy::factory()->create([
            'subdomain' => 'test-academy',
            'is_active' => true,
        ]);
    });

    it('allows authenticated access', function () {
        $user = User::factory()->student()->forAcademy($this->academy)->create();

        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson('/api/v1/student/profile', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200);
    });
});
```

### Testing Livewire Components

```php
<?php

use App\Livewire\MyComponent;
use Livewire\Livewire;

describe('MyComponent', function () {
    it('renders successfully', function () {
        Livewire::test(MyComponent::class)
            ->assertStatus(200);
    });
});
```

## Skipped Tests

Some tests are intentionally skipped due to:
- Complex database setup requirements
- Missing database columns in test environment
- Features requiring external services

These are marked with `->skip('reason')` and documented for future implementation.

## CI/CD Integration

```yaml
# GitHub Actions example
- name: Run Tests
  run: php artisan test --parallel
```
