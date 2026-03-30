<?php

namespace App\Filament\Resources\QuranTrialRequestResource\Pages;

use App\Enums\LearningGoal;
use App\Enums\QuranLearningLevel;
use App\Enums\TimeSlot;
use App\Enums\UserType;
use App\Filament\Resources\QuranTrialRequestResource;
use App\Models\QuranTeacherProfile;
use App\Models\QuranTrialRequest;
use App\Models\User;
use App\Services\AcademyContextService;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;

class ListQuranTrialRequests extends ListRecords
{
    protected static string $resource = QuranTrialRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create')
                ->label('إنشاء طلب جديد')
                ->icon('heroicon-o-plus')
                ->color('primary')
                ->modalHeading('إنشاء طلب جلسة تجريبية')
                ->modalDescription('إنشاء طلب جلسة تجريبية جديد بواسطة الإدارة')
                ->modalWidth('3xl')
                ->schema([
                    Section::make('بيانات الطالب والمعلم')
                        ->schema([
                            Grid::make(2)
                                ->schema([
                                    Select::make('student_id')
                                        ->label('الطالب')
                                        ->options(function () {
                                            $academyId = AcademyContextService::getCurrentAcademyId();

                                            return User::where('user_type', UserType::STUDENT->value)
                                                ->where('academy_id', $academyId)
                                                ->get()
                                                ->pluck('name', 'id');
                                        })
                                        ->searchable()
                                        ->preload()
                                        ->required()
                                        ->live()
                                        ->afterStateUpdated(function (Set $set, ?string $state) {
                                            if (! $state) {
                                                $set('student_name', null);
                                                $set('student_age', null);
                                                $set('phone', null);
                                                $set('email', null);

                                                return;
                                            }

                                            $user = User::find($state);
                                            if ($user) {
                                                $set('student_name', $user->name);
                                                $set('phone', $user->phone);
                                                $set('email', $user->email);
                                            }
                                        }),

                                    Select::make('teacher_id')
                                        ->label('المعلم')
                                        ->options(function () {
                                            $academyId = AcademyContextService::getCurrentAcademyId();

                                            return QuranTeacherProfile::where('academy_id', $academyId)
                                                ->active()
                                                ->get()
                                                ->mapWithKeys(fn ($teacher) => [
                                                    $teacher->id => ($teacher->display_name
                                                        ?? ($teacher->full_name ?? __('معلم غير محدد'))
                                                        .' ('.($teacher->teacher_code ?? 'N/A').')'),
                                                ])
                                                ->toArray();
                                        })
                                        ->searchable()
                                        ->preload()
                                        ->required(),
                                ]),

                            Grid::make(2)
                                ->schema([
                                    TextInput::make('student_name')
                                        ->label('اسم الطالب')
                                        ->required()
                                        ->helperText('يتم تعبئته تلقائياً عند اختيار الطالب'),

                                    TextInput::make('student_age')
                                        ->label('العمر')
                                        ->numeric()
                                        ->minValue(3)
                                        ->maxValue(100),
                                ]),

                            Grid::make(2)
                                ->schema([
                                    TextInput::make('phone')
                                        ->label('رقم الهاتف')
                                        ->tel()
                                        ->extraInputAttributes(['dir' => 'ltr']),

                                    TextInput::make('email')
                                        ->label('البريد الإلكتروني')
                                        ->email()
                                        ->extraInputAttributes(['dir' => 'ltr']),
                                ]),
                        ]),

                    Section::make('تفاصيل التعلم')
                        ->schema([
                            Grid::make(2)
                                ->schema([
                                    Select::make('current_level')
                                        ->label('المستوى الحالي')
                                        ->options(QuranLearningLevel::options())
                                        ->required()
                                        ->native(false),

                                    Select::make('preferred_time')
                                        ->label('الوقت المفضل')
                                        ->options(TimeSlot::options())
                                        ->native(false),
                                ]),

                            CheckboxList::make('learning_goals')
                                ->label('أهداف التعلم')
                                ->options(LearningGoal::options())
                                ->columns(2),

                            Textarea::make('notes')
                                ->label('ملاحظات')
                                ->rows(3)
                                ->placeholder('أي ملاحظات إضافية حول الطالب أو الطلب'),
                        ]),
                ])
                ->action(function (array $data) {
                    $academy = current_academy();

                    $trialRequest = QuranTrialRequest::create([
                        'academy_id' => $academy->id,
                        'student_id' => $data['student_id'],
                        'teacher_id' => $data['teacher_id'],
                        'student_name' => $data['student_name'],
                        'student_age' => $data['student_age'] ?? null,
                        'phone' => $data['phone'] ?? null,
                        'email' => $data['email'] ?? null,
                        'current_level' => $data['current_level'],
                        'preferred_time' => $data['preferred_time'] ?? null,
                        'learning_goals' => $data['learning_goals'] ?? null,
                        'notes' => $data['notes'] ?? null,
                        'status' => 'pending',
                        'created_by' => auth()->id(),
                    ]);

                    Notification::make()
                        ->success()
                        ->title('تم إنشاء طلب الجلسة التجريبية')
                        ->body("تم إنشاء الطلب رقم {$trialRequest->request_code} بنجاح")
                        ->send();
                }),
        ];
    }
}
