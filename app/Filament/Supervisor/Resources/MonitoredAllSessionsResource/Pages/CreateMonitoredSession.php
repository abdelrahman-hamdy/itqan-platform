<?php

namespace App\Filament\Supervisor\Resources\MonitoredAllSessionsResource\Pages;

use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DateTimePicker;
use App\Models\User;
use App\Models\AcademicTeacherProfile;
use App\Models\InteractiveCourse;
use App\Enums\SessionDuration;
use App\Enums\SessionStatus;
use App\Filament\Supervisor\Resources\MonitoredAllSessionsResource;
use App\Models\AcademicSession;
use App\Models\InteractiveCourseSession;
use App\Models\QuranSession;
use App\Services\AcademyContextService;
use Filament\Forms;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateMonitoredSession extends CreateRecord
{
    protected static string $resource = MonitoredAllSessionsResource::class;

    protected ?string $sessionType = null;

    public function mount(): void
    {
        parent::mount();
        $this->sessionType = request()->query('type', 'quran');
    }

    protected function getFormSchema(): array
    {
        return [
            Section::make('نوع الجلسة')
                ->schema([
                    Select::make('session_type')
                        ->label('نوع الجلسة')
                        ->options($this->getSessionTypeOptions())
                        ->required()
                        ->default($this->sessionType)
                        ->live()
                        ->afterStateUpdated(fn ($state) => $this->sessionType = $state),
                ]),

            Section::make('معلومات الجلسة')
                ->schema([
                    // Teacher selection based on session type
                    Select::make('quran_teacher_id')
                        ->label('معلم القرآن')
                        ->options(fn () => $this->getQuranTeacherOptions())
                        ->required()
                        ->searchable()
                        ->visible(fn ($get) => $get('session_type') === 'quran'),

                    Select::make('academic_teacher_id')
                        ->label('المعلم الأكاديمي')
                        ->options(fn () => $this->getAcademicTeacherOptions())
                        ->required()
                        ->searchable()
                        ->visible(fn ($get) => $get('session_type') === 'academic'),

                    Select::make('course_id')
                        ->label('الدورة التفاعلية')
                        ->options(fn () => $this->getInteractiveCourseOptions())
                        ->required()
                        ->searchable()
                        ->visible(fn ($get) => $get('session_type') === 'interactive'),

                    TextInput::make('title')
                        ->label('عنوان الجلسة')
                        ->required()
                        ->maxLength(255),

                    Select::make('status')
                        ->label('الحالة')
                        ->options(SessionStatus::options())
                        ->default(SessionStatus::SCHEDULED->value)
                        ->required(),

                    Textarea::make('description')
                        ->label('وصف الجلسة')
                        ->helperText('أهداف ومحتوى الجلسة')
                        ->rows(2)
                        ->columnSpanFull(),
                ])->columns(2),

            Section::make('التوقيت')
                ->schema([
                    DateTimePicker::make('scheduled_at')
                        ->label('موعد الجلسة')
                        ->required()
                        ->timezone(AcademyContextService::getTimezone())
                        ->default(now()->addDay()),

                    Select::make('duration_minutes')
                        ->label('مدة الجلسة')
                        ->options(SessionDuration::options())
                        ->default(60)
                        ->required(),
                ])->columns(2),

            Section::make('ملاحظات')
                ->schema([
                    Textarea::make('supervisor_notes')
                        ->label('ملاحظات المشرف')
                        ->rows(3)
                        ->helperText('ملاحظات من المشرف بعد المراجعة'),
                ]),
        ];
    }

    protected function getSessionTypeOptions(): array
    {
        $options = [];

        if (MonitoredAllSessionsResource::hasAssignedQuranTeachers()) {
            $options['quran'] = 'جلسة قرآن';
        }

        if (MonitoredAllSessionsResource::hasAssignedAcademicTeachers()) {
            $options['academic'] = 'جلسة أكاديمية';
        }

        if (MonitoredAllSessionsResource::hasDerivedInteractiveCourses()) {
            $options['interactive'] = 'جلسة دورة تفاعلية';
        }

        return $options;
    }

    protected function getQuranTeacherOptions(): array
    {
        $teacherIds = MonitoredAllSessionsResource::getAssignedQuranTeacherIds();

        return User::whereIn('id', $teacherIds)
            ->get()
            ->mapWithKeys(fn ($user) => [$user->id => $user->full_name ?? $user->name ?? $user->email])
            ->toArray();
    }

    protected function getAcademicTeacherOptions(): array
    {
        $profileIds = MonitoredAllSessionsResource::getAssignedAcademicTeacherProfileIds();

        return AcademicTeacherProfile::whereIn('id', $profileIds)
            ->with('user')
            ->get()
            ->mapWithKeys(fn ($profile) => [$profile->id => $profile->user?->name ?? 'غير محدد'])
            ->toArray();
    }

    protected function getInteractiveCourseOptions(): array
    {
        $courseIds = MonitoredAllSessionsResource::getDerivedInteractiveCourseIds();

        return InteractiveCourse::whereIn('id', $courseIds)
            ->pluck('title', 'id')
            ->toArray();
    }

    protected function handleRecordCreation(array $data): Model
    {
        $sessionType = $data['session_type'] ?? 'quran';
        unset($data['session_type']);

        // Get academy ID
        $academy = AcademyContextService::getCurrentAcademy();
        $data['academy_id'] = $academy?->id;

        // Set created_by
        $data['created_by'] = auth()->id();

        switch ($sessionType) {
            case 'academic':
                return AcademicSession::create($data);

            case 'interactive':
                return InteractiveCourseSession::create($data);

            default:
                return QuranSession::create($data);
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'تم إنشاء الجلسة بنجاح';
    }
}
