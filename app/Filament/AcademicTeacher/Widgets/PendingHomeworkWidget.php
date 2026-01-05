<?php

namespace App\Filament\AcademicTeacher\Widgets;

use App\Enums\HomeworkSubmissionStatus;
use App\Models\AcademicHomework;
use App\Models\AcademicHomeworkSubmission;
use App\Models\AcademicSession;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;

class PendingHomeworkWidget extends BaseWidget
{
    // Prevent auto-display on dashboard - Dashboard explicitly adds this widget
    protected static bool $isDiscoverable = false;

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 4;

    protected function getTableHeading(): string
    {
        return 'الواجبات المنتظرة للتصحيح';
    }

    public function table(Table $table): Table
    {
        $user = Auth::user();
        $teacherProfile = $user->academicTeacherProfile;

        // Get homework IDs belonging to this teacher
        $teacherHomeworkIds = $teacherProfile
            ? AcademicHomework::where('teacher_id', $teacherProfile->user_id)
                ->pluck('id')
                ->toArray()
            : [];

        return $table
            ->query(
                AcademicHomeworkSubmission::query()
                    ->whereIn('academic_homework_id', $teacherHomeworkIds)
                    ->whereIn('submission_status', [
                        HomeworkSubmissionStatus::SUBMITTED->value,
                        HomeworkSubmissionStatus::LATE->value,
                    ])
                    ->orderBy('submitted_at', 'desc')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('student.name')
                    ->label('الطالب')
                    ->searchable()
                    ->placeholder('غير محدد'),

                Tables\Columns\TextColumn::make('homework.session.academicIndividualLesson.academicSubject.name')
                    ->label('المادة')
                    ->placeholder('غير محدد'),

                Tables\Columns\TextColumn::make('homework.title')
                    ->label('عنوان الواجب')
                    ->limit(30)
                    ->placeholder('غير محدد'),

                Tables\Columns\TextColumn::make('submitted_at')
                    ->label('تاريخ التسليم')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('submission_status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state?->value ?? $state) {
                        'submitted' => 'تم التسليم',
                        'late' => 'متأخر',
                        default => $state?->value ?? $state,
                    })
                    ->color(fn ($state) => match ($state?->value ?? $state) {
                        'submitted' => 'info',
                        'late' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('is_late')
                    ->label('تأخير')
                    ->badge()
                    ->formatStateUsing(fn ($state, $record) => $state ? ($record->days_late . ' أيام') : 'في الوقت')
                    ->color(fn ($state) => $state ? 'danger' : 'success'),
            ])
            ->actions([
                Tables\Actions\Action::make('grade')
                    ->label('تصحيح')
                    ->icon('heroicon-o-pencil-square')
                    ->url(fn ($record) => route('filament.academic-teacher.resources.academic-sessions.view', [
                        'tenant' => $record->academy?->subdomain ?? Auth::user()->academy?->subdomain ?? 'default',
                        'record' => $record->homework?->academic_session_id,
                    ]))
                    ->color('primary'),
            ])
            ->emptyStateHeading('لا توجد واجبات منتظرة')
            ->emptyStateDescription('ستظهر هنا الواجبات التي تحتاج للتصحيح')
            ->emptyStateIcon('heroicon-o-clipboard-document-check')
            ->paginated(false);
    }
}
