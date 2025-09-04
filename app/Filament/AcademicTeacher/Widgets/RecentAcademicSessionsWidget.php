<?php

namespace App\Filament\AcademicTeacher\Widgets;

use App\Models\AcademicSession;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;

class RecentAcademicSessionsWidget extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $user = Auth::user();
        $teacherProfile = $user->academicTeacherProfile;

        return $table
            ->query(
                AcademicSession::query()
                    ->where('academic_teacher_id', $teacherProfile?->id ?? 0)
                    ->latest('scheduled_at')
                    ->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('عنوان الجلسة')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('session_type')
                    ->label('النوع')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'individual' => 'blue',
                        'interactive_course' => 'green',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'individual' => 'درس فردي',
                        'interactive_course' => 'دورة تفاعلية',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('scheduled_at')
                    ->label('التاريخ والوقت')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('الحالة')
                    ->colors([
                        'warning' => 'scheduled',
                        'success' => 'completed',
                        'danger' => 'cancelled',
                        'info' => 'ongoing',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'scheduled' => 'مجدولة',
                        'completed' => 'مكتملة',
                        'cancelled' => 'ملغية',
                        'ongoing' => 'جارية',
                        default => $state,
                    }),
            ])
            ->paginated(false);
    }
}
