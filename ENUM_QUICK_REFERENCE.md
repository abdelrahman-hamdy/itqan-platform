# Enum Quick Reference

Quick reference for all 8 newly created enums.

## 1. CircleStatus

**File**: `app/Enums/CircleStatus.php`

| Case | Value | Arabic Label |
|------|-------|--------------|
| PENDING | pending | في انتظار البداية |
| ACTIVE | active | نشط |
| COMPLETED | completed | مكتمل |
| SUSPENDED | suspended | معلق |
| CANCELLED | cancelled | ملغي |

**Usage**:
```php
use App\Enums\CircleStatus;

$circle->status = CircleStatus::ACTIVE;
if ($circle->status->isActive()) { ... }
```

---

## 2. UserType

**File**: `app/Enums/UserType.php`

| Case | Value | Arabic Label |
|------|-------|--------------|
| SUPER_ADMIN | super_admin | مدير النظام |
| ACADEMY_ADMIN | admin | مدير الأكاديمية |
| SUPERVISOR | supervisor | مشرف |
| QURAN_TEACHER | quran_teacher | معلم قرآن |
| ACADEMIC_TEACHER | academic_teacher | معلم أكاديمي |
| STUDENT | student | طالب |
| PARENT | parent | ولي أمر |

**Usage**:
```php
use App\Enums\UserType;

$user->user_type = UserType::QURAN_TEACHER;
if ($user->user_type->isTeacher()) { ... }
```

---

## 3. PaymentMethod

**File**: `app/Enums/PaymentMethod.php`

| Case | Value | Arabic Label |
|------|-------|--------------|
| CREDIT_CARD | credit_card | بطاقة ائتمان |
| DEBIT_CARD | debit_card | بطاقة خصم |
| BANK_TRANSFER | bank_transfer | تحويل بنكي |
| WALLET | wallet | محفظة إلكترونية |
| CASH | cash | نقداً |
| MADA | mada | مدى |
| VISA | visa | فيزا |
| MASTERCARD | mastercard | ماستركارد |
| APPLE_PAY | apple_pay | Apple Pay |
| STC_PAY | stc_pay | STC Pay |
| URPAY | urpay | UrPay |

**Usage**:
```php
use App\Enums\PaymentMethod;

$payment->payment_method = PaymentMethod::MADA;
if ($payment->payment_method->requiresGateway()) { ... }
```

---

## 4. QuranSpecialization

**File**: `app/Enums/QuranSpecialization.php`

| Case | Value | Arabic Label |
|------|-------|--------------|
| MEMORIZATION | memorization | الحفظ |
| RECITATION | recitation | التلاوة |
| INTERPRETATION | interpretation | التفسير |
| ARABIC_LANGUAGE | arabic_language | اللغة العربية |
| COMPLETE | complete | متكامل |

**Usage**:
```php
use App\Enums\QuranSpecialization;

$circle->specialization = QuranSpecialization::MEMORIZATION;
if ($circle->specialization->includesMemorization()) { ... }
```

---

## 5. MemorizationLevel

**File**: `app/Enums/MemorizationLevel.php`

| Case | Value | Arabic Label |
|------|-------|--------------|
| BEGINNER | beginner | مبتدئ |
| ELEMENTARY | elementary | ابتدائي |
| INTERMEDIATE | intermediate | متوسط |
| ADVANCED | advanced | متقدم |
| EXPERT | expert | خبير |

**Usage**:
```php
use App\Enums\MemorizationLevel;

$circle->memorization_level = MemorizationLevel::INTERMEDIATE;
$level = MemorizationLevel::fromPagesCount(150); // Returns INTERMEDIATE
```

---

## 6. AgeGroup

**File**: `app/Enums/AgeGroup.php`

| Case | Value | Arabic Label |
|------|-------|--------------|
| CHILDREN | children | أطفال |
| YOUTH | youth | شباب |
| ADULTS | adults | كبار |
| ALL_AGES | all_ages | كل الفئات |

**Usage**:
```php
use App\Enums\AgeGroup;

$circle->age_group = AgeGroup::CHILDREN;
if ($circle->age_group->includesAge(10)) { ... }
```

---

## 7. GenderType

**File**: `app/Enums/GenderType.php`

| Case | Value | Arabic Label |
|------|-------|--------------|
| MALE | male | رجال |
| FEMALE | female | نساء |
| MIXED | mixed | مختلط |

**Usage**:
```php
use App\Enums\GenderType;

$circle->gender_type = GenderType::FEMALE;
if ($circle->gender_type->canJoin('female')) { ... }
```

---

## 8. ScheduleStatus

**File**: `app/Enums/ScheduleStatus.php`

| Case | Value | Arabic Label |
|------|-------|--------------|
| ACTIVE | active | نشط |
| PAUSED | paused | موقوف مؤقتاً |
| COMPLETED | completed | مكتمل |
| CANCELLED | cancelled | ملغي |

**Usage**:
```php
use App\Enums\ScheduleStatus;

$schedule->status = ScheduleStatus::ACTIVE;
if ($schedule->status->canGenerateSessions()) { ... }
```

---

## Common Methods Available on All Enums

### Core Methods
- `label()` - Get Arabic label from translation file
- `icon()` - Get Remix Icon class
- `color()` - Get Filament color name
- `badgeClasses()` - Get Tailwind CSS badge classes

### Static Methods
- `values()` - Get array of all enum values
- `options()` - Get value => label array for forms

### Example
```php
// Get label
$label = CircleStatus::ACTIVE->label(); // "نشط"

// Get icon
$icon = CircleStatus::ACTIVE->icon(); // "ri-play-circle-line"

// Get color
$color = CircleStatus::ACTIVE->color(); // "success"

// Get badge classes
$classes = CircleStatus::ACTIVE->badgeClasses(); // "bg-green-100 text-green-800"

// Get all values
$values = CircleStatus::values(); // ['pending', 'active', 'completed', 'suspended', 'cancelled']

// Get form options
$options = CircleStatus::options(); // ['pending' => 'في انتظار البداية', ...]
```

---

## Model Casting

Use these enums in your models with type casting:

```php
protected $casts = [
    'status' => CircleStatus::class,
    'user_type' => UserType::class,
    'payment_method' => PaymentMethod::class,
    'specialization' => QuranSpecialization::class,
    'memorization_level' => MemorizationLevel::class,
    'age_group' => AgeGroup::class,
    'gender_type' => GenderType::class,
    'schedule_status' => ScheduleStatus::class,
];
```

---

## Validation

Use in validation rules:

```php
use Illuminate\Validation\Rules\Enum;

'status' => ['required', new Enum(CircleStatus::class)],
'user_type' => ['required', new Enum(UserType::class)],
'payment_method' => ['required', new Enum(PaymentMethod::class)],
```

Or with Rule::enum():

```php
use Illuminate\Validation\Rule;

'status' => ['required', Rule::enum(CircleStatus::class)],
```

---

## Filament Select Fields

```php
use Filament\Forms\Components\Select;

Select::make('status')
    ->options(CircleStatus::options())
    ->required(),

Select::make('user_type')
    ->options(UserType::options())
    ->required(),
```

---

## Blade Views

```php
{{-- Display with badge --}}
<span class="{{ $circle->status->badgeClasses() }}">
    <i class="{{ $circle->status->icon() }}"></i>
    {{ $circle->status->label() }}
</span>

{{-- Conditional rendering --}}
@if($user->user_type->isTeacher())
    {{-- Teacher content --}}
@endif
```
