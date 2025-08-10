<?php

namespace App\Filament\Teacher\Widgets;

use App\Models\QuranSession;
use Filament\Facades\Filament;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Filament\Support\Enums\FontWeight;

class RecentSessionsWidget extends BaseWidget
{
    protected static ?string $heading = 'الجلسات الأخيرة';
    
    protected static ?int $sort = 3;
    
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $user = Auth::user();
        
        if (!$user->isQuranTeacher() || !$user->quranTeacherProfile) {
            $query = QuranSession::query()->whereRaw('1 = 0'); // Return no results
        } else {
            $query = QuranSession::query()
                ->where('quran_teacher_id', $user->quranTeacherProfile->id)
                ->with(['student', 'subscription'])
                ->latest('scheduled_at');
        }

        return $table
            ->query($query)
            ->columns([
                Tables\Columns\TextColumn::make('session_code')
                    ->label('رمز الجلسة')
                    ->weight(FontWeight::Bold)
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('title')
                    ->label('عنوان الجلسة')
                    ->limit(30)
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('student.name')
                    ->label('الطالب')
                    ->searchable(),
                    
                Tables\Columns\BadgeColumn::make('session_type')
                    ->label('النوع')
                    ->colors([
                        'primary' => 'individual',
                        'success' => 'group',
                        'warning' => 'trial',
                        'info' => 'makeup',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'individual' => 'فردية',
                        'group' => 'جماعية',
                        'trial' => 'تجريبية',
                        'makeup' => 'تعويضية',
                        default => $state,
                    }),
                    
                Tables\Columns\TextColumn::make('scheduled_at')
                    ->label('الموعد')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
                    
                Tables\Columns\BadgeColumn::make('status')
                    ->label('الحالة')
                    ->colors([
                        'warning' => 'scheduled',
                        'info' => 'in_progress',
                        'success' => 'completed',
                        'danger' => 'cancelled',
                        'gray' => 'no_show',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'scheduled' => 'مجدولة',
                        'in_progress' => 'جارية',
                        'completed' => 'مكتملة',
                        'cancelled' => 'ملغية',
                        'no_show' => 'غياب',
                        default => $state,
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('عرض')
                    ->icon('heroicon-o-eye')
                    ->url(fn (QuranSession $record): string => 
                        route('filament.teacher.resources.quran-sessions.view', [
                            'tenant' => Filament::getTenant(),
                            'record' => $record->id
                        ])
                    ),
            ])
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5);
    }
}