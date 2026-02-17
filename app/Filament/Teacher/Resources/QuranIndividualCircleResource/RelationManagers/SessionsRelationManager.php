<?php

namespace App\Filament\Teacher\Resources\QuranIndividualCircleResource\RelationManagers;

use Filament\Schemas\Schema;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Enums\SessionStatus;
use App\Models\QuranSession;
use App\Services\AcademyContextService;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SessionsRelationManager extends RelationManager
{
    protected static string $relationship = 'sessions';

    protected static ?string $title = 'الجلسات';

    protected static ?string $modelLabel = 'جلسة';

    protected static ?string $pluralModelLabel = 'الجلسات';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                DateTimePicker::make('scheduled_at')
                    ->label('موعد الجلسة')
                    ->required()
                    ->native(false)
                    ->seconds(false)
                    ->timezone(AcademyContextService::getTimezone())
                    ->displayFormat('Y-m-d H:i'),

                Select::make('status')
                    ->label('الحالة')
                    ->options(SessionStatus::options())
                    ->default(SessionStatus::SCHEDULED->value)
                    ->required(),

                Textarea::make('notes')
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

                TextColumn::make('status')
                    ->badge()
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

                TextColumn::make('attendances_count')
                    ->label('عدد الطلاب')
                    ->counts('attendances'),

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
                CreateAction::make()
                    ->label('إضافة جلسة'),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('تعديل'),
                DeleteAction::make()
                    ->label('حذف'),
                Action::make('start_session')
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
                Action::make('complete_session')
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
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('scheduled_at', 'desc');
    }
}
