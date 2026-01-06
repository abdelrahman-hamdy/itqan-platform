<?php

namespace App\Filament\Teacher\Resources\QuranCircleResource\RelationManagers;

use App\Enums\SessionStatus;
use App\Models\QuranSession;
use App\Services\AcademyContextService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SessionsRelationManager extends RelationManager
{
    protected static string $relationship = 'sessions';

    protected static ?string $title = 'الجلسات';

    protected static ?string $modelLabel = 'جلسة';

    protected static ?string $pluralModelLabel = 'الجلسات';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DateTimePicker::make('scheduled_at')
                    ->label('موعد الجلسة')
                    ->required()
                    ->native(false)
                    ->seconds(false)
                    ->timezone(AcademyContextService::getTimezone())
                    ->displayFormat('Y-m-d H:i'),

                Forms\Components\Select::make('status')
                    ->label('الحالة')
                    ->options(SessionStatus::options())
                    ->default(SessionStatus::SCHEDULED->value)
                    ->required(),

                Forms\Components\Textarea::make('notes')
                    ->label('ملاحظات')
                    ->rows(3),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('scheduled_at')
            ->columns([
                TextColumn::make('scheduled_at')
                    ->label('تاريخ الجلسة')
                    ->dateTime('Y-m-d H:i')
                    ->timezone(fn ($record) => $record->academy->timezone->value)
                    ->sortable(),

                BadgeColumn::make('status')
                    ->label('الحالة')
                    ->colors(SessionStatus::colorOptions())
                    ->formatStateUsing(function ($state): string {
                        if ($state instanceof SessionStatus) {
                            return $state->label();
                        }
                        $status = SessionStatus::tryFrom($state);

                        return $status?->label() ?? $state;
                    }),

                TextColumn::make('duration_minutes')
                    ->label('المدة (دقيقة)')
                    ->suffix(' دقيقة'),

                TextColumn::make('students_count')
                    ->label('عدد الطلاب')
                    ->counts('attendance'),

                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('إضافة جلسة'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('تعديل'),
                Tables\Actions\DeleteAction::make()
                    ->label('حذف'),
                Tables\Actions\Action::make('start_session')
                    ->label('بدء الجلسة')
                    ->icon('heroicon-o-play')
                    ->color('success')
                    ->visible(fn (QuranSession $record): bool => $record->status instanceof SessionStatus
                            ? $record->status->canStart()
                            : in_array($record->status, [SessionStatus::SCHEDULED->value, SessionStatus::READY->value]))
                    ->action(function (QuranSession $record) {
                        $record->update([
                            'status' => SessionStatus::ONGOING,
                            'started_at' => now(),
                        ]);
                    }),
                Tables\Actions\Action::make('complete_session')
                    ->label('إنهاء الجلسة')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (QuranSession $record): bool => $record->status instanceof SessionStatus
                            ? $record->status === SessionStatus::ONGOING
                            : $record->status === SessionStatus::ONGOING->value)
                    ->action(function (QuranSession $record) {
                        $record->update([
                            'status' => SessionStatus::COMPLETED,
                            'ended_at' => now(),
                        ]);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('scheduled_at', 'desc');
    }
}
