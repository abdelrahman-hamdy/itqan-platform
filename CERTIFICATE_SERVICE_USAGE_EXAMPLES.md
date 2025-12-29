# Certificate Service - Usage Examples

## Basic Usage (Unchanged from Before)

### Issue Certificate for Recorded Course
```php
use App\Services\CertificateService;

public function __construct(
    private CertificateService $certificateService
) {}

public function issueCertificate(CourseSubscription $subscription)
{
    try {
        $certificate = $this->certificateService
            ->issueCertificateForRecordedCourse($subscription);
        
        return response()->json([
            'message' => 'Certificate issued successfully',
            'certificate_number' => $certificate->certificate_number,
            'download_url' => $certificate->download_url,
        ]);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 400);
    }
}
```

### Issue Manual Certificate for Quran Subscription
```php
public function issueQuranCertificate(QuranSubscription $subscription)
{
    $achievementText = 'حفظ جزء عم كاملاً بإتقان مع التجويد';
    $templateStyle = CertificateTemplateStyle::TEMPLATE_1;
    
    $certificate = $this->certificateService->issueManualCertificate(
        subscriptionable: $subscription,
        achievementText: $achievementText,
        templateStyle: $templateStyle,
        issuedBy: auth()->id()
    );
    
    return $certificate;
}
```

### Download Certificate
```php
public function download(Certificate $certificate)
{
    return $this->certificateService->downloadCertificate($certificate);
}
```

### Stream Certificate (View in Browser)
```php
public function view(Certificate $certificate)
{
    return $this->certificateService->streamCertificate($certificate);
}
```

## Advanced Usage (Using Components Directly)

### 1. Query Certificates Without Full Service

```php
use App\Services\Certificate\CertificateRepository;

public function __construct(
    private CertificateRepository $repository
) {}

public function getStudentCertificates(int $studentId)
{
    return $this->repository->getByStudent($studentId);
}

public function checkIfCertificateExists(QuranSubscription $subscription)
{
    return $this->repository->existsForSubscription($subscription);
}
```

### 2. Generate Preview Without Saving

```php
use App\Services\Certificate\CertificatePdfGenerator;
use App\Services\Certificate\CertificateTemplateEngine;
use App\Enums\CertificateTemplateStyle;

public function __construct(
    private CertificatePdfGenerator $pdfGenerator,
    private CertificateTemplateEngine $templateEngine
) {}

public function previewCertificate(Request $request)
{
    $data = [
        'student_name' => $request->input('student_name'),
        'certificate_text' => $request->input('certificate_text'),
        'teacher_name' => $request->input('teacher_name'),
        'academy_name' => 'أكاديمية إتقان',
        'certificate_number' => 'PREVIEW-2025-001',
        'issued_date_formatted' => now()->locale('ar')->translatedFormat('d F Y'),
    ];
    
    $templateStyle = CertificateTemplateStyle::from($request->input('template_style'));
    
    $pdf = $this->pdfGenerator->generatePreviewPdf($data, $templateStyle);
    
    return response($pdf->Output('', 'S'), 200)
        ->header('Content-Type', 'application/pdf')
        ->header('Content-Disposition', 'inline; filename="preview.pdf"');
}
```

### 3. Custom Template Processing

```php
use App\Services\Certificate\CertificateTemplateEngine;

public function __construct(
    private CertificateTemplateEngine $templateEngine
) {}

public function customizeTemplate(Academy $academy)
{
    // Get all available templates
    $templates = $this->templateEngine->getAvailableTemplates();
    
    // Get preview of a template
    $preview = $this->templateEngine->getTemplatePreview(
        CertificateType::QURAN_SUBSCRIPTION,
        $academy
    );
    
    return view('admin.certificates.templates', [
        'templates' => $templates,
        'preview' => $preview,
    ]);
}
```

### 4. Bulk Email Certificates

```php
use App\Services\Certificate\CertificateEmailService;

public function __construct(
    private CertificateEmailService $emailService
) {}

public function resendCertificates(array $certificateIds)
{
    $certificates = Certificate::whereIn('id', $certificateIds)->get();
    
    $results = $this->emailService->sendBulkCertificates($certificates->toArray());
    
    return response()->json([
        'success' => $results['success'],
        'failed' => $results['failed'],
        'errors' => $results['errors'],
    ]);
}

public function queueCertificateNotification(Certificate $certificate)
{
    // Send certificate email after 1 hour
    $this->emailService->queueCertificateEmail($certificate, delayInMinutes: 60);
    
    return 'Certificate email queued';
}
```

### 5. Custom Email Template

```php
public function sendWithCustomEmail(Certificate $certificate)
{
    $this->emailService->sendWithCustomTemplate(
        certificate: $certificate,
        template: 'emails.certificates.premium',
        data: [
            'special_message' => 'تهانينا على هذا الإنجاز الرائع',
            'academy_logo_url' => $certificate->academy->logo_url,
        ]
    );
}
```

## Testing Examples

### Unit Test - Repository
```php
use App\Services\Certificate\CertificateRepository;
use Tests\TestCase;

class CertificateRepositoryTest extends TestCase
{
    public function test_generates_unique_certificate_number()
    {
        $repo = new CertificateRepository();
        
        $number1 = $repo->generateCertificateNumber();
        $number2 = $repo->generateCertificateNumber();
        
        $this->assertNotEquals($number1, $number2);
        $this->assertStringStartsWith('CERT-' . now()->year, $number1);
    }
    
    public function test_checks_if_certificate_exists_for_subscription()
    {
        $subscription = QuranSubscription::factory()->create();
        $certificate = Certificate::factory()->create([
            'certificateable_type' => QuranSubscription::class,
            'certificateable_id' => $subscription->id,
        ]);
        
        $repo = new CertificateRepository();
        
        $this->assertTrue($repo->existsForSubscription($subscription));
    }
}
```

### Unit Test - Template Engine
```php
use App\Services\Certificate\CertificateTemplateEngine;
use Tests\TestCase;

class CertificateTemplateEngineTest extends TestCase
{
    public function test_replaces_placeholders()
    {
        $engine = new CertificateTemplateEngine();
        
        $template = 'مرحباً {student_name}، لقد أتممت {course_name}';
        $result = $engine->replacePlaceholders($template, [
            'student_name' => 'أحمد',
            'course_name' => 'دورة القرآن',
        ]);
        
        $this->assertEquals('مرحباً أحمد، لقد أتممت دورة القرآن', $result);
    }
}
```

### Integration Test - Full Certificate Issuance
```php
use App\Services\CertificateService;
use Tests\TestCase;

class CertificateServiceTest extends TestCase
{
    public function test_issues_certificate_for_completed_course()
    {
        $subscription = CourseSubscription::factory()->create([
            'progress_percentage' => 100,
        ]);
        
        $service = app(CertificateService::class);
        $certificate = $service->issueCertificateForRecordedCourse($subscription);
        
        $this->assertNotNull($certificate);
        $this->assertEquals($subscription->student_id, $certificate->student_id);
        $this->assertTrue($certificate->fileExists());
        
        // Verify subscription updated
        $subscription->refresh();
        $this->assertTrue($subscription->certificate_issued);
    }
}
```

## Filament Integration Examples

### Issue Certificate Action in Resource
```php
use App\Services\CertificateService;
use Filament\Tables\Actions\Action;

public static function table(Table $table): Table
{
    return $table
        ->actions([
            Action::make('issue_certificate')
                ->label('إصدار شهادة')
                ->icon('heroicon-o-academic-cap')
                ->visible(fn (QuranSubscription $record) => 
                    !$record->certificate_issued
                )
                ->form([
                    Textarea::make('achievement_text')
                        ->label('نص الإنجاز')
                        ->required()
                        ->rows(3),
                    Select::make('template_style')
                        ->label('نمط الشهادة')
                        ->options(CertificateTemplateStyle::class)
                        ->required(),
                ])
                ->action(function (QuranSubscription $record, array $data) {
                    $certificateService = app(CertificateService::class);
                    
                    $certificate = $certificateService->issueManualCertificate(
                        subscriptionable: $record,
                        achievementText: $data['achievement_text'],
                        templateStyle: $data['template_style'],
                        issuedBy: auth()->id()
                    );
                    
                    Notification::make()
                        ->success()
                        ->title('تم إصدار الشهادة بنجاح')
                        ->body("رقم الشهادة: {$certificate->certificate_number}")
                        ->send();
                })
        ]);
}
```

### Preview Certificate in Livewire Component
```php
use App\Services\Certificate\CertificatePdfGenerator;
use Livewire\Component;

class CertificatePreview extends Component
{
    public $studentName;
    public $achievementText;
    public $templateStyle;
    
    public function generatePreview()
    {
        $pdfGenerator = app(CertificatePdfGenerator::class);
        
        $data = [
            'student_name' => $this->studentName,
            'certificate_text' => $this->achievementText,
            'teacher_name' => auth()->user()->name,
            'academy_name' => tenant('name'),
            'certificate_number' => 'PREVIEW',
            'issued_date_formatted' => now()->locale('ar')->translatedFormat('d F Y'),
        ];
        
        $pdf = $pdfGenerator->generatePreviewPdf(
            $data,
            CertificateTemplateStyle::from($this->templateStyle)
        );
        
        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->Output('', 'S');
        }, 'preview.pdf');
    }
}
```

## Best Practices

1. **Always use dependency injection**
   ```php
   // Good
   public function __construct(private CertificateService $service) {}
   
   // Avoid
   $service = new CertificateService(...);
   ```

2. **Use specific components when you only need part of the functionality**
   ```php
   // If you only need to query certificates
   public function __construct(private CertificateRepository $repository) {}
   
   // If you only need to send emails
   public function __construct(private CertificateEmailService $emailService) {}
   ```

3. **Handle exceptions appropriately**
   ```php
   try {
       $certificate = $this->certificateService->issueCertificateForRecordedCourse($subscription);
   } catch (\Exception $e) {
       Log::error('Certificate issuance failed', [
           'subscription_id' => $subscription->id,
           'error' => $e->getMessage(),
       ]);
       throw $e;
   }
   ```

4. **Use type hints and return types**
   ```php
   public function downloadCertificate(Certificate $certificate): \Symfony\Component\HttpFoundation\BinaryFileResponse
   {
       return $this->certificateService->downloadCertificate($certificate);
   }
   ```
