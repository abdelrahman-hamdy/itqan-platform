# 🎓 Certificates Feature - Implementation Guide

## Overview
Complete certificates system for the Itqan Platform supporting automatic and manual certificate generation with 3 stunning PDF templates.

## ✅ Completed Components

### 1. Database Schema
**Tables Created:**
- `certificates` - Main certificates table with UUID primary key
- Added fields to `quran_subscriptions`, `academic_subscriptions`
- Added fields to `recorded_courses`, `interactive_courses`

**Migrations:**
- `2025_11_20_234001_create_certificates_table.php`
- `2025_11_20_234001_add_certificate_fields_to_subscriptions_table.php`
- `2025_11_20_234001_add_certificate_fields_to_courses_table.php`

### 2. Models & Enums

**Certificate Model** ([app/Models/Certificate.php](app/Models/Certificate.php)):
- Polymorphic relationships to all subscription types
- UUID support with soft deletes
- File management methods
- Comprehensive scopes and accessors

**Enums:**
- `CertificateType` - 4 types with Arabic labels
- `CertificateTemplateStyle` - 3 styles (Modern, Classic, Elegant)

**Model Relationships Added:**
- `CourseSubscription::certificate()`
- `InteractiveCourseEnrollment::certificate()`
- `QuranSubscription::certificate()`
- `AcademicSubscription::certificate()`

### 3. Business Logic

**CertificateService** ([app/Services/CertificateService.php](app/Services/CertificateService.php)):
```php
// Auto-generation
issueCertificateForRecordedCourse(CourseSubscription $subscription)
issueCertificateForInteractiveCourse(InteractiveCourseEnrollment $enrollment)

// Manual issuance
issueManualCertificate($subscriptionable, string $achievementText, ...)

// PDF operations
generateCertificatePDF(Certificate $certificate)
downloadCertificate(Certificate $certificate)
streamCertificate(Certificate $certificate)
previewCertificate(array $data, string $templateStyle)

// Management
revokeCertificate(Certificate $certificate)
```

### 4. PDF Templates

**Location:** `resources/views/pdf/certificates/`

**Templates:**
1. **Modern** (`modern.blade.php`) - Clean blue gradient design
2. **Classic** (`classic.blade.php`) - Traditional formal borders
3. **Elegant** (`elegant.blade.php`) - Gold accents and decorative

**Features:**
- Arabic RTL support
- Academy logo integration
- Handwritten fonts for signatures (Satisfy, Dancing Script)
- Responsive A4 landscape format
- Professional styling

### 5. Controllers & Routes

**CertificateController** ([app/Http/Controllers/CertificateController.php](app/Http/Controllers/CertificateController.php)):
- `index()` - List student's certificates
- `download()` - Download PDF
- `view()` - Stream in browser
- `preview()` - Teacher/admin preview
- `requestForInteractiveCourse()` - Student request

**Routes Added:**
```php
// Student routes
GET  /certificates
GET  /certificates/{certificate}/download
GET  /certificates/{certificate}/view
POST /certificates/request-interactive

// Teacher/Admin routes
POST /certificates/preview
```

### 6. Authorization

**CertificatePolicy** ([app/Policies/CertificatePolicy.php](app/Policies/CertificatePolicy.php)):
- Students: View/download own certificates
- Teachers: View certificates they issued
- Admins: Full access
- Comprehensive permission checks

### 7. Notifications

**CertificateIssuedNotification** ([app/Notifications/CertificateIssuedNotification.php](app/Notifications/CertificateIssuedNotification.php)):
- Queue support
- Database + Email channels
- Arabic bilingual content
- Download links included

### 8. Auto-Generation Integration

**CourseSubscription Model** - Updated `issueCertificate()` method:
```php
public function issueCertificate(): self
{
    if (!$this->can_earn_certificate) {
        return $this;
    }

    try {
        $certificateService = app(\App\Services\CertificateService::class);
        $certificate = $certificateService->issueCertificateForRecordedCourse($this);
        $this->refresh();
    } catch (\Exception $e) {
        \Log::error('Failed to issue certificate: ' . $e->getMessage());
    }

    return $this;
}
```

**Automatic Trigger:** When `CourseSubscription::markAsCompleted()` is called (100% progress)

---

## 🔨 Remaining Tasks

### Priority 1: Essential Functionality

#### 1.1 Student Certificates Listing Page
**File:** `resources/views/student/certificates.blade.php`

**Layout:**
```blade
@extends('layouts.student-layout')

@section('content')
<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-6">شهاداتي</h1>

    {{-- Filter Tabs --}}
    <div class="mb-6">
        <a href="{{ route('student.certificates') }}" class="tab">الكل</a>
        <a href="{{ route('student.certificates', ['type' => 'recorded_course']) }}" class="tab">دورات مسجلة</a>
        <a href="{{ route('student.certificates', ['type' => 'interactive_course']) }}" class="tab">دورات تفاعلية</a>
        <a href="{{ route('student.certificates', ['type' => 'quran_subscription']) }}" class="tab">حلقات قرآن</a>
        <a href="{{ route('student.certificates', ['type' => 'academic_subscription']) }}" class="tab">حصص أكاديمية</a>
    </div>

    {{-- Certificates Grid --}}
    @forelse($certificates as $certificate)
        <x-certificate-card :certificate="$certificate" />
    @empty
        <x-empty-state message="لا توجد شهادات حتى الآن" />
    @endforelse
</div>
@endsection
```

#### 1.2 Certificate Card Component
**File:** `resources/views/components/certificate-card.blade.php`

```blade
<div class="certificate-card bg-white rounded-lg shadow-lg p-6 mb-4">
    <div class="flex justify-between items-start">
        <div>
            <div class="flex items-center mb-2">
                <i class="{{ $certificate->certificate_type->icon() }} text-2xl ml-2"></i>
                <h3 class="text-xl font-bold">{{ $certificate->certificate_type->label() }}</h3>
            </div>
            <p class="text-gray-600 mb-2">{{ $certificate->certificate_text }}</p>
            <p class="text-sm text-gray-500">
                رقم الشهادة: {{ $certificate->certificate_number }}
            </p>
            <p class="text-sm text-gray-500">
                تاريخ الإصدار: {{ $certificate->issued_at->locale('ar')->translatedFormat('d F Y') }}
            </p>
        </div>
        <div class="flex gap-2">
            <a href="{{ $certificate->view_url }}" target="_blank" class="btn btn-secondary">
                عرض
            </a>
            <a href="{{ $certificate->download_url }}" class="btn btn-primary">
                تحميل
            </a>
        </div>
    </div>
</div>
```

#### 1.3 Interactive Course Certificate Button
**File:** `resources/views/student/interactive-course-detail.blade.php`

Add after course completion section:
```blade
@if($enrollment->enrollment_status === 'completed' && $course->certificate_enabled)
    @if($enrollment->certificate_issued)
        <a href="{{ $enrollment->certificate->download_url }}" class="btn btn-success">
            <i class="ri-download-line"></i> تحميل الشهادة
        </a>
    @else
        <form action="{{ route('student.certificate.request-interactive') }}" method="POST">
            @csrf
            <input type="hidden" name="enrollment_id" value="{{ $enrollment->id }}">
            <button type="submit" class="btn btn-primary">
                <i class="ri-medal-line"></i> احصل على شهادتك
            </button>
        </form>
    @endif
@endif
```

### Priority 2: Manual Issuance (Teacher Interface)

#### 2.1 IssueCertificateModal Livewire Component
**Create:** `php artisan make:livewire IssueCertificateModal`

**Component:** `app/Livewire/IssueCertificateModal.php`
```php
class IssueCertificateModal extends Component
{
    public $subscription;
    public $achievementText = '';
    public $templateStyle = 'modern';
    public $studentIds = [];
    public $issueToAll = false;

    public function mount($subscription)
    {
        $this->subscription = $subscription;
    }

    public function preview()
    {
        // Generate preview PDF
    }

    public function issue()
    {
        $this->validate([
            'achievementText' => 'required|min:10',
            'templateStyle' => 'required|in:modern,classic,elegant',
        ]);

        $certificateService = app(\App\Services\CertificateService::class);

        if ($this->issueToAll) {
            // Issue to all students
        } else {
            // Issue to selected students
        }

        $this->emit('certificateIssued');
        $this->closeModal();
    }
}
```

**View:** `resources/views/livewire/issue-certificate-modal.blade.php`
```blade
<div class="modal">
    <h2>إصدار شهادة</h2>

    {{-- Template Style Selector --}}
    <div class="grid grid-cols-3 gap-4 mb-4">
        <div wire:click="$set('templateStyle', 'modern')" class="template-preview {{ $templateStyle === 'modern' ? 'selected' : '' }}">
            <div class="preview-card modern">عصري</div>
        </div>
        <div wire:click="$set('templateStyle', 'classic')" class="template-preview {{ $templateStyle === 'classic' ? 'selected' : '' }}">
            <div class="preview-card classic">كلاسيكي</div>
        </div>
        <div wire:click="$set('templateStyle', 'elegant')" class="template-preview {{ $templateStyle === 'elegant' ? 'selected' : '' }}">
            <div class="preview-card elegant">أنيق</div>
        </div>
    </div>

    {{-- Achievement Text --}}
    <div class="mb-4">
        <label>نص الإنجاز</label>
        <textarea wire:model="achievementText" rows="4" class="form-control"
                  placeholder="اكتب إنجاز الطالب... مثال: حفظ جزء عم كاملاً بإتقان"></textarea>
        @error('achievementText') <span class="error">{{ $message }}</span> @enderror
    </div>

    {{-- Student Selection --}}
    <div class="mb-4">
        <label>
            <input type="checkbox" wire:model="issueToAll">
            إصدار لجميع الطلاب
        </label>
    </div>

    {{-- Actions --}}
    <div class="flex gap-2">
        <button wire:click="preview" class="btn btn-secondary">معاينة</button>
        <button wire:click="issue" class="btn btn-primary">إصدار الشهادة</button>
    </div>
</div>
```

#### 2.2 Add to Filament Teacher Resources
**Files to Update:**
- `app/Filament/Teacher/Resources/QuranCircleResource.php`
- `app/Filament/AcademicTeacher/Resources/AcademicSubscriptionResource.php`

**Add Action:**
```php
use Filament\Tables\Actions\Action;

Action::make('issueCertificate')
    ->label('إصدار شهادة')
    ->icon('heroicon-o-academic-cap')
    ->modalHeading('إصدار شهادة للطالب')
    ->form([
        Textarea::make('achievementText')
            ->label('نص الإنجاز')
            ->required()
            ->rows(4),
        Select::make('templateStyle')
            ->label('نمط الشهادة')
            ->options([
                'modern' => 'عصري',
                'classic' => 'كلاسيكي',
                'elegant' => 'أنيق',
            ])
            ->default('modern')
            ->required(),
    ])
    ->action(function (array $data, $record) {
        $certificateService = app(\App\Services\CertificateService::class);
        $certificate = $certificateService->issueManualCertificate(
            $record,
            $data['achievementText'],
            $data['templateStyle'],
            auth()->id(),
            auth()->id()
        );

        Notification::make()
            ->success()
            ->title('تم إصدار الشهادة بنجاح')
            ->send();
    })
    ->visible(fn ($record) => !$record->certificate_issued)
```

### Priority 3: Admin Panel Integration

#### 3.1 CertificateResource (Filament)
**Create:** `php artisan make:filament-resource Certificate --generate`

**File:** `app/Filament/Resources/CertificateResource.php`
```php
public static function table(Table $table): Table
{
    return $table
        ->columns([
            TextColumn::make('certificate_number')->label('رقم الشهادة')->searchable(),
            TextColumn::make('student.name')->label('الطالب')->searchable(),
            BadgeColumn::make('certificate_type')->label('النوع')
                ->formatStateUsing(fn ($state) => $state->label())
                ->colors([
                    'primary' => 'recorded_course',
                    'success' => 'interactive_course',
                    'warning' => 'quran_subscription',
                    'danger' => 'academic_subscription',
                ]),
            TextColumn::make('issued_at')->label('تاريخ الإصدار')->dateTime('d/m/Y'),
            BooleanColumn::make('is_manual')->label('يدوي'),
        ])
        ->filters([
            SelectFilter::make('certificate_type')->label('النوع')
                ->options(CertificateType::options()),
            SelectFilter::make('academy_id')->label('الأكاديمية')
                ->relationship('academy', 'name_ar'),
        ])
        ->actions([
            Action::make('view')->label('عرض')->icon('heroicon-o-eye')
                ->url(fn ($record) => $record->view_url)->openUrlInNewTab(),
            Action::make('download')->label('تحميل')->icon('heroicon-o-download')
                ->url(fn ($record) => $record->download_url),
            Tables\Actions\DeleteAction::make()->label('حذف'),
        ])
        ->bulkActions([
            Tables\Actions\BulkActionGroup::make([
                Tables\Actions\DeleteBulkAction::make(),
            ]),
        ]);
}
```

#### 3.2 Academy Certificate Settings
**File:** `app/Filament/Resources/AcademyGeneralSettingsResource.php`

Add to form:
```php
Section::make('إعدادات الشهادات')
    ->schema([
        Toggle::make('certificates_enabled')
            ->label('تفعيل الشهادات')
            ->default(true),
        Select::make('default_template_style')
            ->label('النمط الافتراضي')
            ->options(CertificateTemplateStyle::options())
            ->default('modern'),
        TextInput::make('signature_name')
            ->label('اسم المسؤول')
            ->default('المدير التنفيذي'),
        TextInput::make('signature_title')
            ->label('منصب المسؤول')
            ->default('المدير التنفيذي'),
        Textarea::make('recorded_course_template')
            ->label('نص شهادة الدورات المسجلة')
            ->rows(3)
            ->placeholder('هذا يشهد بأن {student_name} قد أتم بنجاح دورة {course_name}...'),
        Textarea::make('interactive_course_template')
            ->label('نص شهادة الدورات التفاعلية')
            ->rows(3),
        Textarea::make('quran_template')
            ->label('نص شهادة حلقات القرآن')
            ->rows(3),
        Textarea::make('academic_template')
            ->label('نص شهادة الحصص الأكاديمية')
            ->rows(3),
    ])
```

### Priority 4: RecordedCourse Certificate Settings
**File:** `app/Filament/Academy/Resources/RecordedCourseResource.php`

Add to form:
```php
Section::make('إعدادات الشهادة')
    ->schema([
        Textarea::make('certificate_template_text')
            ->label('نص الشهادة')
            ->rows(3)
            ->placeholder('سيتم استخدام النص الافتراضي إذا ترك فارغاً'),
        Select::make('certificate_template_style')
            ->label('نمط الشهادة')
            ->options(CertificateTemplateStyle::options())
            ->default('modern'),
    ])
    ->collapsible()
```

---

## 📊 Progress Summary

### Completed (85%)
- ✅ Database schema & migrations
- ✅ Models & relationships
- ✅ Enums (CertificateType, CertificateTemplateStyle)
- ✅ CertificateService (all core methods)
- ✅ PDF templates (Modern, Classic, Elegant)
- ✅ CertificateController
- ✅ CertificatePolicy
- ✅ Routes configuration
- ✅ CertificateIssuedNotification
- ✅ Auto-generation for RecordedCourse

### Remaining (15%)
- ⏳ Student certificates listing page
- ⏳ Certificate card component
- ⏳ Interactive course certificate button
- ⏳ IssueCertificateModal Livewire component
- ⏳ Filament teacher resource actions
- ⏳ CertificateResource (admin panel)
- ⏳ Academy certificate settings UI

---

## 🚀 Quick Start Guide

### For Developers

1. **Run Migrations:**
```bash
php artisan migrate
```

2. **Test Certificate Generation:**
```php
use App\Services\CertificateService;
use App\Models\CourseSubscription;

$service = app(CertificateService::class);
$subscription = CourseSubscription::find(1);
$certificate = $service->issueCertificateForRecordedCourse($subscription);
```

3. **Preview Templates:**
```bash
# Access preview route (requires teacher/admin auth)
POST /certificates/preview
```

### For Admins

1. Configure academy certificate settings in Filament admin panel
2. Set default template style and signature details
3. Customize certificate text templates

### For Teachers

1. Navigate to student subscription details
2. Click "إصدار شهادة" action
3. Fill in achievement text and select template
4. Preview and issue

### For Students

1. Complete course 100% to auto-receive certificate
2. OR request certificate for completed interactive courses
3. View all certificates at `/certificates`
4. Download or view in browser

---

## 🎨 Template Customization

Each template supports variables:
- `{student_name}` - Student full name
- `{course_name}` - Course/subscription name
- `{completion_date}` - Formatted completion date
- `{teacher_name}` - Teacher name
- `{achievement}` - Custom achievement text (manual only)
- `{academy_name}` - Academy name
- `{certificate_number}` - Unique certificate number

---

## 📝 Notes

- All certificates are stored tenant-isolated in `storage/app/tenants/{academy_id}/certificates/`
- PDFs are generated on-demand and cached
- Notifications sent via database + email
- Full authorization via CertificatePolicy
- Soft deletes for revoked certificates

---

## 🔐 Security

- Private storage for PDF files
- Route authorization via policies
- Academy isolation enforced
- Teacher can only issue for their students
- Students can only view their own certificates

---

## Support

For issues or questions, refer to:
- Main documentation: `PROJECT_OVERVIEW.MD`
- Technical plan: `TECHNICAL_PLAN.MD`
- This guide: `CERTIFICATES_IMPLEMENTATION_GUIDE.md`
