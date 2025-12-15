<?php

namespace App\Filament\AcademicTeacher\Widgets;

use App\Models\AcademicSession;
use App\Models\HomeworkSubmission;
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

        // Get session IDs belonging to this teacher
        $teacherSessionIds = $teacherProfile
            ? AcademicSession::where('academic_teacher_id', $teacherProfile->id)
                ->pluck('id')
                ->toArray()
            : [];

        return $table
            ->query(
                HomeworkSubmission::query()
                    ->where('submitable_type', AcademicSession::class)
                    ->whereIn('submitable_id', $teacherSessionIds)
                    ->whereIn('submission_status', ['submitted', 'late', 'resubmitted'])
                    ->orderBy('submitted_at', 'desc')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('student.name')
                    ->label('الطالب')
                    ->searchable()
                    ->placeholder('غير محدد'),

                Tables\Columns\TextColumn::make('submitable.academicIndividualLesson.academicSubject.name')
                    ->label('المادة')
                    ->placeholder('غير محدد'),

                Tables\Columns\TextColumn::make('submitted_at')
                    ->label('تاريخ التسليم')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('submission_status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'submitted' => 'تم التسليم',
                        'late' => 'متأخر',
                        'resubmitted' => 'إعادة تسليم',
                        default => $state,
                    })
                    ->color(fn ($state) => match ($state) {
                        'submitted' => 'success',
                        'late' => 'warning',
                        'resubmitted' => 'info',
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
                        'record' => $record->submitable_id,
                    ]))
                    ->color('primary'),
            ])
            ->emptyStateHeading('لا توجد واجبات منتظرة')
            ->emptyStateDescription('ستظهر هنا الواجبات التي تحتاج للتصحيح')
            ->emptyStateIcon('heroicon-o-clipboard-document-check')
            ->paginated(false);
    }
}
