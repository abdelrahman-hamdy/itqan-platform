<?php

namespace App\Filament\AcademicTeacher\Resources\AcademicSessionResource\Pages;

use App\Filament\AcademicTeacher\Resources\AcademicSessionResource;
use App\Models\AcademicSession;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;

class ViewAcademicSession extends ViewRecord
{
    protected static string $resource = AcademicSessionResource::class;

    protected static ?string $title = 'عرض تفاصيل الجلسة الأكاديمية';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('بيانات الجلسة')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('session_code')
                                    ->label('رمز الجلسة')
                                    ->disabled()
                                    ->dehydrated(false),

                                Forms\Components\TextInput::make('title')
                                    ->label('عنوان الجلسة')
                                    ->disabled()
                                    ->dehydrated(false),

                                Forms\Components\Select::make('session_type')
                                    ->label('نوع الجلسة')
                                    ->options([
                                        'individual' => 'درس فردي',
                                        'interactive_course' => 'دورة تفاعلية',
                                    ])
                                    ->disabled()
                                    ->dehydrated(false),

                                Forms\Components\Select::make('status')
                                    ->label('حالة الجلسة')
                                    ->options([
                                        'unscheduled' => 'غير مجدولة',
                                        'scheduled' => 'مجدولة',
                                        'ready' => 'جاهزة للبدء',
                                        'ongoing' => 'جارية',
                                        'completed' => 'مكتملة',
                                        'cancelled' => 'ملغية',
                                    ])
                                    ->disabled()
                                    ->dehydrated(false),
                            ]),
                    ]),

                Forms\Components\Section::make('معلومات الطالب والجدولة')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('student.name')
                                    ->label('اسم الطالب')
                                    ->disabled()
                                    ->dehydrated(false),

                                Forms\Components\DateTimePicker::make('scheduled_at')
                                    ->label('التاريخ والوقت المجدول')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->displayFormat('Y-m-d H:i'),

                                Forms\Components\TextInput::make('duration_minutes')
                                    ->label('مدة الجلسة')
                                    ->suffix(' دقيقة')
                                    ->disabled()
                                    ->dehydrated(false),

                                Forms\Components\DateTimePicker::make('created_at')
                                    ->label('تاريخ الإنشاء')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->displayFormat('Y-m-d H:i'),
                            ]),
                    ]),

                Forms\Components\Section::make('تفاصيل إضافية')
                    ->schema([
                        Forms\Components\Textarea::make('description')
                            ->label('وصف الجلسة')
                            ->disabled()
                            ->dehydrated(false)
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('academicIndividualLesson.academicSubject.name')
                            ->label('المادة الأكاديمية')
                            ->disabled()
                            ->dehydrated(false)
                            ->visible(fn (AcademicSession $record) => $record->session_type === 'individual'),

                        Forms\Components\TextInput::make('interactiveCourseSession.course.title')
                            ->label('عنوان الدورة')
                            ->disabled()
                            ->dehydrated(false)
                            ->visible(fn (AcademicSession $record) => $record->session_type === 'interactive_course'),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('join_meeting')
                ->label('دخول الجلسة')
                ->icon('heroicon-o-video-camera')
                ->color('success')
                ->visible(fn (AcademicSession $record): bool => in_array($record->status->value ?? $record->status, ['scheduled', 'ready', 'ongoing'])
                    && $record->scheduled_at
                    && $record->scheduled_at->isBefore(now()->addMinutes(15))
                )
                ->action(function (AcademicSession $record) {
                    // For meeting functionality, we'll redirect to the frontend meeting interface
                    // since it contains the meeting UI integration
                    $subdomain = Auth::user()->academy->subdomain ?? 'itqan-academy';
                    $meetingUrl = route('teacher.sessions.show', [
                        'subdomain' => $subdomain,
                        'sessionId' => $record->id,
                    ]);

                    return redirect()->away($meetingUrl);
                }),

            Actions\EditAction::make()
                ->label('تعديل الجلسة'),
        ];
    }

    public function getTitle(): string
    {
        return 'جلسة: '.($this->record->title ?? 'غير محددة');
    }
}
