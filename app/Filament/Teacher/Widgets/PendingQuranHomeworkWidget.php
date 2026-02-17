<?php

namespace App\Filament\Teacher\Widgets;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Actions\Action;
use App\Models\QuranSession;
use App\Models\QuranSessionHomework;
use Filament\Facades\Filament;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;

class PendingQuranHomeworkWidget extends BaseWidget
{
    // Prevent auto-display on dashboard - Dashboard explicitly adds this widget
    protected static bool $isDiscoverable = false;

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 4;

    protected function getTableHeading(): string
    {
        return 'الواجبات النشطة';
    }

    public function table(Table $table): Table
    {
        $user = Auth::user();
        $teacherProfile = $user->quranTeacherProfile;

        // Get session IDs belonging to this teacher
        $teacherSessionIds = $teacherProfile
            ? QuranSession::where('quran_teacher_id', $teacherProfile->id)
                ->pluck('id')
                ->toArray()
            : [];

        return $table
            ->query(
                QuranSessionHomework::query()
                    ->whereIn('session_id', $teacherSessionIds)
                    ->where('is_active', true)
                    ->orderBy('due_date', 'asc')
                    ->limit(10)
            )
            ->columns([
                TextColumn::make('session.student.name')
                    ->label('الطالب')
                    ->searchable()
                    ->placeholder('غير محدد'),

                TextColumn::make('homework_type')
                    ->label('نوع الواجب')
                    ->badge()
                    ->getStateUsing(function ($record) {
                        $types = [];
                        if ($record->has_new_memorization) {
                            $types[] = 'حفظ جديد';
                        }
                        if ($record->has_review) {
                            $types[] = 'مراجعة';
                        }
                        if ($record->has_comprehensive_review) {
                            $types[] = 'مراجعة شاملة';
                        }

                        return implode(' + ', $types) ?: 'غير محدد';
                    })
                    ->color('primary'),

                TextColumn::make('total_pages')
                    ->label('الصفحات')
                    ->suffix(' صفحة')
                    ->alignCenter(),

                TextColumn::make('due_date')
                    ->label('تاريخ التسليم')
                    ->date('d/m/Y')
                    ->sortable()
                    ->color(fn ($record) => $record->is_overdue ? 'danger' : ($record->due_date?->isToday() ? 'warning' : 'gray')),

                TextColumn::make('difficulty_level')
                    ->label('المستوى')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'easy' => 'سهل',
                        'medium' => 'متوسط',
                        'hard' => 'صعب',
                        default => 'متوسط',
                    })
                    ->color(fn ($state) => match ($state) {
                        'easy' => 'success',
                        'medium' => 'warning',
                        'hard' => 'danger',
                        default => 'gray',
                    }),

                IconColumn::make('is_overdue')
                    ->label('متأخر')
                    ->boolean()
                    ->trueIcon('heroicon-o-exclamation-circle')
                    ->falseIcon('heroicon-o-check-circle')
                    ->trueColor('danger')
                    ->falseColor('success'),
            ])
            ->recordActions([
                Action::make('view')
                    ->label('عرض الجلسة')
                    ->icon('heroicon-o-eye')
                    ->url(fn ($record) => route('filament.teacher.resources.quran-sessions.view', [
                        'tenant' => Filament::getTenant(),
                        'record' => $record->session_id,
                    ]))
                    ->color('primary'),
            ])
            ->emptyStateHeading('لا توجد واجبات نشطة')
            ->emptyStateDescription('ستظهر هنا الواجبات المُكلف بها الطلاب')
            ->emptyStateIcon('heroicon-o-clipboard-document-check')
            ->paginated(false);
    }
}
