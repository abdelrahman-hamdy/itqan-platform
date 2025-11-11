````md
# Quran & Academic SaaS – **Technical Implementation Guide**  
*(Laravel 11 · Livewire + Filament 4 · Single-DB Multitenancy)*  

> Copy this file into `OVERVIEW.md` inside your Cursor workspace.  
> It defines **packages, installation fixes, migrations, seeders, policies, UI hooks, and workflows** for every requirement you supplied (Chatify customisation, quizzes, extra 1-to-1 sessions, default “itqan-academy” tenant, etc.).

---

## 0 · Prerequisites

| Tool | Min Version | Notes |
|------|-------------|-------|
| PHP 8.3 | `memory_limit=-1` recommended during `composer install` |
| Composer 2.7 | run `composer self-update` |
| Node >= 20 | for Vite/Tailwind |
| MySQL 8 | single schema, UTF8MB4 |
| Redis 7 | queues & cache; Horizon later |
| Git | optional but strongly advised |

---

## 1 · Composer Installation (fixes)

```bash
# create project
composer create-project laravel/laravel quran-academy

cd quran-academy

# composer memory / process flags to avoid OOM & symlink errors on macOS
COMPOSER_PROCESS_TIMEOUT=2000 \
COMPOSER_ALLOW_SUPERUSER=1 \
composer require \
    spatie/laravel-multitenancy:^3.0 \
    spatie/laravel-permission:^6.0 \
    filament/filament:^4.0@beta \
    bezhansalleh/filament-shield:^6.0@beta \
    livewire/livewire:^3.0 \
    spatie/laravel-activitylog:^5.0 \
    spatie/laravel-settings:^2.0 \
    spatie/laravel-translatable:^6.0 \
    spatie/laravel-google-calendar:^4.0 \
    paymob/paymob-php:^1.0 \
    tap-payments/tap-laravel:^2.0 \
    munafio/chatify:^2.3 \
    pusher/pusher-php-server:^7.2 \
    spatie/laravel-media-library:^11.0 \
    barryvdh/laravel-dompdf:^2.0
````

> **💡 Tip:** If Composer still fails, add the flag `--prefer-dist --no-scripts` then run `composer run-script post-autoload-dump` manually afterwards.

---

## 2 · Vendor Publishing & Migrations

```bash
php artisan vendor:publish --provider="Spatie\Multitenancy\MultitenancyServiceProvider"
php artisan vendor:publish --provider="Bezhansalleh\FilamentShield\FilamentShieldServiceProvider" --tag=filament-shield-config
php artisan vendor:publish --provider="Bezhansalleh\FilamentShield\FilamentShieldServiceProvider" --tag=filament-shield-migrations
php artisan vendor:publish --provider="Spatie\Activitylog\ActivitylogServiceProvider" --tag="activitylog-config"
php artisan vendor:publish --tag=chatify-config
php artisan vendor:publish --tag=chatify-views
php artisan migrate
```

---

## 3 · Multitenancy Configuration

1. **Driver:** `spatie/laravel-multitenancy` single-DB mode.
2. **TenantColumn:** `tenant_id` on every tenant-scoped table.
3. **TenantFinder:** `InitializeTenancyByDomainOrSubdomain` but **allow root domain** ⇒ Default tenant “itqan-academy” is resolved when host *doesn’t* match another tenant.
4. **Storage Prefix:** define `storage/app/tenants/{tenant_id}` via `Tenancy::setStoragePath()`.

### Seeder – `database/seeders/TenantSeeder.php`

```php
public function run(): void
{
    $tenant = Tenant::create([
        'id'            => 'itqan-academy',
        'name'          => 'Itqan Academy',
        'domain'        => config('app.url'),   // root domain
        'default_locale'=> 'ar',
        'timezone'      => 'Asia/Riyadh',
    ]);

    // create SuperAdmin user
    $super = User::create([
        'name'     => 'Super Admin',
        'email'    => 'super@itqan.com',
        'password' => bcrypt('secret'),
        'tenant_id'=> 'itqan-academy',
    ])->assignRole('SuperAdmin');

    // seed default packages, subjects, etc…
}
```

Run: `php artisan db:seed --class=TenantSeeder`.

---

## 4 · Roles & Policies (Filament Shield)

```bash
php artisan shield:generate
# ➞ generates RoleSeeder, policies for all models
php artisan db:seed --class=RoleSeeder
# Roles: SuperAdmin, Admin, Supervisor, Teacher, Student, Parent
```

* **Spatie\Permission**: add `protected $guard_name = 'web'` to `Role` & `Permission` models.
* Filament navigation groups auto-hide resources based on role.

---

## 5 · Identity / Theme per Academy

| Setting                 | Field Type     | Storage                                 |
| ----------------------- | -------------- | --------------------------------------- |
| `academy.name`          | string         | `spatie/laravel-settings`               |
| `academy.subtitle`      | string         |                                         |
| `academy.about`         | markdown       |                                         |
| `academy.primary_color` | string (hex)   |                                         |
| `academy.logo`          | media (single) | MediaLibrary disk `tenants/{tenant_id}` |

*Expose these via **Filament Settings page**.*

Tailwind: inside `tailwind.config.js`

```js
theme: {
  extend: {
    colors: {
      primary: 'var(--academy-primary-color)',
    }
  }
}
```

Filament: set CSS variable on `<html>` via middleware that pulls tenant settings.

---

## 6 · Chat System (Chatify Fork)

### 6.1 Chatify Customisation

| Aspect                | Implementation                                                                                                                                                    |
| --------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Tenant Isolation**  | Add global scope `tenant_id` to `Chatify\Models\Message`.                                                                                                         |
| **UI Branding**       | Overwrite `resources/views/vendor/Chatify/pages` ➜ inject academy logo & Tailwind colors.                                                                         |
| **Group Chats**       | Extend Chatify `Groups` to auto-create chat **per Quran Circle** & **per Live Course**; add many-to-many `group_participants`.                                    |
| **Role Matrix**       | Custom Gate in `App\Policies\ChatPolicy`:<br>`canStart(User $user, User $receiver)` – implements matrix below.                                                    |
| **Supervisor Mirror** | Event listener on `MessageSent` → broadcast to dedicated **observer WebSocket channel** (`"observer.{tenant_id}.{conversation_id}"`). Supervisors join read-only. |
| **Attachments**       | Configure `Chatify` to store files via MediaLibrary disk; size limit via `config('chatify.attachments.size')`.                                                    |

#### Role Matrix Logic

| Sender ↓ \ Receiver → | SuperAdmin      | Admin | Supervisor   | Teacher      | Student      | Parent       |
| --------------------- | --------------- | ----- | ------------ | ------------ | ------------ | ------------ |
| **SuperAdmin**        | ✔               | ✔     | ✔            | ✔            | ✔            | ✔            |
| **Teacher**           | –               | –     | ✔            | –            | ✔            | ✔            |
| **Student**           | –               | –     | ✔            | ✔            | –            | –            |
| **Supervisor**        | ✔ (their Admin) | –     | –            | ✔ (assigned) | ✔ (assigned) | ✔ (assigned) |
| **Parent**            | –               | –     | ✔ (assigned) | ✔            | –            | –            |

> *en-dashes (–) = not allowed.*

### 6.2 Middleware

```php
class CanChatMiddleware {
  public function handle($request, Closure $next) {
      abort_unless(app(ChatPolicy::class)->canStart(
          auth()->user(), User::find($request->receiver_id)
      ), 403);
      return $next($request);
  }
}
```

Attach to Chatify routes.

---

## 7 · Quizzes Module

### 7.1 Migrations

```php
Schema::create('quizzes', function (Blueprint $t) {
    $t->uuid('id')->primary();
    $t->uuid('teacher_id');
    $t->foreignUuid('tenant_id');
    $t->string('title');
    $t->unsignedInteger('max_attempts')->default(1);
    $t->unsignedTinyInteger('pass_threshold')->default(60); // %
    $t->timestamps();
});

Schema::create('quiz_questions', function (Blueprint $t) {
    $t->uuid('id')->primary();
    $t->uuid('quiz_id');
    $t->text('question');
    $t->unsignedSmallInteger('points')->default(1);
    $t->timestamps();
});

Schema::create('quiz_choices', function (Blueprint $t) {
    $t->uuid('id')->primary();
    $t->uuid('question_id');
    $t->text('choice');
    $t->boolean('is_correct')->default(false);
});

Schema::create('quiz_attempts', function (Blueprint $t) {
    $t->uuid('id')->primary();
    $t->uuid('quiz_id');
    $t->uuid('student_id');
    $t->unsignedSmallInteger('score')->nullable();
    $t->boolean('passed')->nullable();
    $t->timestamps();
});

Schema::create('quiz_attempt_details', function (Blueprint $t) {
    $t->uuid('attempt_id');
    $t->uuid('question_id');
    $t->uuid('choice_id')->nullable(); // null if skipped
    $t->boolean('is_correct')->nullable();
    $t->primary(['attempt_id','question_id']);
});
```

### 7.2 Services

* `QuizService::createQuiz(array $dto)` – randomises question order at runtime.
* `AttemptService::grade(QuizAttempt $attempt)` – calculates score, stores per-question result.

### 7.3 Filament Resources

| Resource             | Screens                               |
| -------------------- | ------------------------------------- |
| **QuizResource**     | List / Create / Edit (teacher)        |
| **QuestionResource** | Nested within quiz                    |
| **ChoiceForm**       | Repeater component                    |
| **AttemptResource**  | Read-only table for teacher & student |

---

## 8 · Extra Sessions for 1-to-1 Subscriptions

### 8.1 Migration

```php
Schema::create('subscription_addons', function (Blueprint $t) {
    $t->uuid('id')->primary();
    $t->uuid('subscription_id');
    $t->unsignedSmallInteger('extra_sessions');
    $t->decimal('price', 8, 2);
    $t->boolean('paid')->default(false);
    $t->timestamps();
});
```

### 8.2 Workflow

1. Student clicks **“Buy Extra Sessions”** → Filament action opens modal.
2. Choose quantity → creates `invoice` (`item_type = addon`).
3. Paymob/Tap webhook marks **paid**.
4. Observer increments `lessons_remaining`.
5. Cron job on subscription expiry ignores unused add-on sessions.

---

## 9 · Scheduled & Queued Jobs

| Job                   | Schedule     | Purpose                                         |
| --------------------- | ------------ | ----------------------------------------------- |
| `GenerateMeetLinks`   | every minute | create link 15 min before session               |
| `SendSessionReminder` | queue        | push + email 10 min before                      |
| `ExpireSubscriptions` | nightly      | flag expired subs, zero leftover addon sessions |
| `ProcessQuizGrading`  | queue        | heavy grading async (if 500+ students)          |

---

## 10 · CI / CD (GitHub Actions)

```yaml
name: deploy
on:
  push:
    branches: [ main ]
jobs:
  build:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
      - name: Install deps
        run: composer install --prefer-dist --no-interaction --optimize-autoloader
      - name: Run tests
        run: php artisan test
      - name: Deploy via SSH
        uses: easingthemes/ssh-deploy@main
        with:
          # …
```

---

## 11 · Tailwind & RTL

* Add `@tailwindcss/typography` and `@tailwindcss/forms`.
* In `vite.config.js` inject `dir="rtl"` for Arabic locale.
* Use Filament’s built-in RTL toggle: `Filament::layoutDirection('rtl');`.

---

## 12 · Testing (Pest)

```bash
composer require pestphp/pest --dev
php artisan pest:install
```

* **Feature tests**: subscription purchase, chat policy, quiz grading.
* **Unit tests**: `CanChatPolicy`, `QuizService`.

---

## 13 · Future Enhancements

* Horizon + Soketi for production WebSockets.
* Auto-renew subscriptions via scheduled billing profiles.
* LiveKit/Jitsi swap-in if Google Meet recording becomes limiting.
* BI dashboards via Filament Widgets + Laravel Pulse.

---

> **All set!**
> Run `php artisan serve` → login `super@itqan.com` → access `/admin`.
> Start creating tenants, teachers, circles, quizzes, and watch everything operate under the single-DB multitenancy model.

```
::contentReference[oaicite:0]{index=0}
```
