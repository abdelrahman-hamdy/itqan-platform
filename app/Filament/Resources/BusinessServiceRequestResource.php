<?php

namespace App\Filament\Resources;

use App\Services\AcademyContextService;
use Illuminate\Database\Eloquent\Model;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\BusinessServiceRequestResource\Pages\ListBusinessServiceRequests;
use App\Filament\Resources\BusinessServiceRequestResource\Pages\CreateBusinessServiceRequest;
use App\Filament\Resources\BusinessServiceRequestResource\Pages\ViewBusinessServiceRequest;
use App\Filament\Resources\BusinessServiceRequestResource\Pages\EditBusinessServiceRequest;
use App\Enums\SessionStatus;
use App\Enums\SessionSubscriptionStatus;
use App\Filament\Resources\BusinessServiceRequestResource\Pages;
use App\Models\BusinessServiceRequest;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BusinessServiceRequestResource extends Resource
{
    protected static ?string $model = BusinessServiceRequest::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static string | \UnitEnum | null $navigationGroup = 'خدمات الأعمال';

    protected static ?string $navigationLabel = 'طلبات الأعمال';

    protected static ?string $modelLabel = 'طلب خدمة';

    protected static ?string $pluralModelLabel = 'طلبات الأعمال';

    protected static ?int $navigationSort = 2;

    /**
     * Check if the current user can access this resource
     */
    public static function canAccess(): bool
    {
        return AcademyContextService::isSuperAdmin();
    }

    /**
     * Check if the current user can create records
     */
    public static function canCreate(): bool
    {
        return AcademyContextService::isSuperAdmin();
    }

    /**
     * Check if the current user can edit records
     */
    public static function canEdit(Model $record): bool
    {
        return AcademyContextService::isSuperAdmin();
    }

    /**
     * Check if the current user can delete records
     */
    public static function canDelete(Model $record): bool
    {
        return AcademyContextService::isSuperAdmin();
    }

    /**
     * Check if the current user can view records
     */
    public static function canView(Model $record): bool
    {
        return AcademyContextService::isSuperAdmin();
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['serviceCategory']);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('معلومات العميل')
                    ->schema([
                        TextInput::make('client_name')
                            ->label('اسم العميل')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('client_phone')
                            ->label('رقم الهاتف')
                            ->tel()
                            ->required()
                            ->maxLength(255),

                        TextInput::make('client_email')
                            ->label('البريد الإلكتروني')
                            ->email()
                            ->required()
                            ->maxLength(255),
                    ])
                    ->columns(3),

                Section::make('تفاصيل المشروع')
                    ->schema([
                        Select::make('service_category_id')
                            ->label('نوع الخدمة')
                            ->relationship('serviceCategory', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),

                        Select::make('project_budget')
                            ->label('الميزانية المتوقعة')
                            ->options([
                                '500-1000' => '500 - 1000 ر.س',
                                '1000-3000' => '1000 - 3000 ر.س',
                                '3000-5000' => '3000 - 5000 ر.س',
                                '5000-10000' => '5000 - 10000 ر.س',
                                '10000+' => 'أكثر من 10000 ر.س',
                            ])
                            ->placeholder('اختر الميزانية'),

                        Select::make('project_deadline')
                            ->label('الموعد المطلوب')
                            ->options([
                                'urgent' => 'عاجل (أسبوع)',
                                'normal' => 'عادي (أسبوعين)',
                                'flexible' => 'مرن (شهر أو أكثر)',
                            ])
                            ->placeholder('اختر الموعد'),

                        Textarea::make('project_description')
                            ->label('وصف المشروع')
                            ->required()
                            ->rows(6)
                            ->placeholder('اكتب تفاصيل مشروعك والمتطلبات المحددة...'),
                    ])
                    ->columns(2),

                Section::make('معلومات إدارية')
                    ->schema([
                        Select::make('status')
                            ->label('حالة الطلب')
                            ->options([
                                SessionSubscriptionStatus::PENDING->value => 'في الانتظار',
                                'reviewed' => 'تم المراجعة',
                                'approved' => 'مقبول',
                                'rejected' => 'مرفوض',
                                SessionStatus::COMPLETED->value => 'مكتمل',
                            ])
                            ->default(SessionSubscriptionStatus::PENDING->value)
                            ->required(),

                        Textarea::make('admin_notes')
                            ->label('ملاحظات إدارية')
                            ->rows(4)
                            ->placeholder('ملاحظات خاصة بالطلب...'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('client_name')
                    ->label('اسم العميل')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('client_email')
                    ->label('البريد الإلكتروني')
                    ->searchable()
                    ->copyable(),

                TextColumn::make('client_phone')
                    ->label('رقم الهاتف')
                    ->searchable()
                    ->copyable(),

                TextColumn::make('serviceCategory.name')
                    ->label('نوع الخدمة')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('project_budget')
                    ->label('الميزانية')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        '500-1000' => '500 - 1000 ر.س',
                        '1000-3000' => '1000 - 3000 ر.س',
                        '3000-5000' => '3000 - 5000 ر.س',
                        '5000-10000' => '5000 - 10000 ر.س',
                        '10000+' => 'أكثر من 10000 ر.س',
                        default => $state,
                    }),

                TextColumn::make('project_deadline')
                    ->label('الموعد')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'urgent' => 'عاجل (أسبوع)',
                        'normal' => 'عادي (أسبوعين)',
                        'flexible' => 'مرن (شهر أو أكثر)',
                        default => $state,
                    }),

                TextColumn::make('status')
                    ->badge()
                    ->label('الحالة')
                    ->colors([
                        'warning' => SessionSubscriptionStatus::PENDING->value,
                        'info' => 'reviewed',
                        'success' => 'approved',
                        'danger' => 'rejected',
                        'gray' => SessionStatus::COMPLETED->value,
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        SessionSubscriptionStatus::PENDING->value => 'في الانتظار',
                        'reviewed' => 'تم المراجعة',
                        'approved' => 'مقبول',
                        'rejected' => 'مرفوض',
                        SessionStatus::COMPLETED->value => 'مكتمل',
                        default => $state,
                    }),

                TextColumn::make('created_at')
                    ->label('تاريخ الطلب')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('حالة الطلب')
                    ->options([
                        SessionSubscriptionStatus::PENDING->value => 'في الانتظار',
                        'reviewed' => 'تم المراجعة',
                        'approved' => 'مقبول',
                        'rejected' => 'مرفوض',
                        SessionStatus::COMPLETED->value => 'مكتمل',
                    ]),

                SelectFilter::make('service_category_id')
                    ->label('نوع الخدمة')
                    ->relationship('serviceCategory', 'name')
                    ->searchable()
                    ->preload(),

                Filter::make('created_at')
                    ->label('تاريخ الطلب')
                    ->schema([
                        DatePicker::make('created_from')
                            ->label('من تاريخ'),
                        DatePicker::make('created_until')
                            ->label('إلى تاريخ'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->deferFilters(false)
            ->recordActions([
                ViewAction::make()
                    ->label('عرض'),
                EditAction::make()
                    ->label('تعديل'),
                DeleteAction::make()
                    ->label('حذف')
                    ->requiresConfirmation()
                    ->modalHeading('تأكيد الحذف')
                    ->modalDescription('هل أنت متأكد من حذف هذا الطلب؟ لا يمكن التراجع عن هذا الإجراء.')
                    ->modalSubmitActionLabel('حذف')
                    ->modalCancelActionLabel('إلغاء'),
                Action::make('mark_reviewed')
                    ->label('مراجعة')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->visible(fn (BusinessServiceRequest $record): bool => $record->status === SessionSubscriptionStatus::PENDING->value)
                    ->requiresConfirmation()
                    ->modalHeading('تأكيد المراجعة')
                    ->modalDescription('هل تريد وضع علامة مراجعة على هذا الطلب؟')
                    ->modalSubmitActionLabel('مراجعة')
                    ->modalCancelActionLabel('إلغاء')
                    ->action(function (BusinessServiceRequest $record): void {
                        $record->update(['status' => 'reviewed']);
                    }),

                Action::make('change_status')
                    ->label('تغيير الحالة')
                    ->icon('heroicon-o-arrow-path')
                    ->schema([
                        Select::make('status')
                            ->label('الحالة الجديدة')
                            ->options([
                                SessionSubscriptionStatus::PENDING->value => 'في الانتظار',
                                'reviewed' => 'تم المراجعة',
                                'approved' => 'مقبول',
                                'rejected' => 'مرفوض',
                                SessionStatus::COMPLETED->value => 'مكتمل',
                            ])
                            ->required(),
                        Textarea::make('admin_notes')
                            ->label('ملاحظات إدارية'),
                    ])
                    ->action(function (array $data, BusinessServiceRequest $record): void {
                        $record->update($data);
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('mark_reviewed')
                        ->label('وضع علامة مراجعة')
                        ->icon('heroicon-o-eye')
                        ->color('info')
                        ->requiresConfirmation()
                        ->modalHeading('تأكيد المراجعة الجماعية')
                        ->modalDescription('هل تريد وضع علامة مراجعة على الطلبات المحددة؟')
                        ->modalSubmitActionLabel('مراجعة')
                        ->modalCancelActionLabel('إلغاء')
                        ->action(function ($records): void {
                            $records->each(function ($record) {
                                if ($record->status === SessionSubscriptionStatus::PENDING->value) {
                                    $record->update(['status' => 'reviewed']);
                                }
                            });
                        }),

                    BulkAction::make('mark_approved')
                        ->label('قبول المحدد')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('تأكيد القبول الجماعي')
                        ->modalDescription('هل تريد قبول الطلبات المحددة؟')
                        ->modalSubmitActionLabel('قبول')
                        ->modalCancelActionLabel('إلغاء')
                        ->action(function ($records): void {
                            $records->each(function ($record) {
                                $record->update(['status' => 'approved']);
                            });
                        }),

                    DeleteBulkAction::make()
                        ->label('حذف المحدد')
                        ->requiresConfirmation()
                        ->modalHeading('تأكيد الحذف الجماعي')
                        ->modalDescription('هل أنت متأكد من حذف الطلبات المحددة؟ لا يمكن التراجع عن هذا الإجراء.')
                        ->modalSubmitActionLabel('حذف المحدد')
                        ->modalCancelActionLabel('إلغاء'),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBusinessServiceRequests::route('/'),
            'create' => CreateBusinessServiceRequest::route('/create'),
            'view' => ViewBusinessServiceRequest::route('/{record}'),
            'edit' => EditBusinessServiceRequest::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where('status', SessionSubscriptionStatus::PENDING->value)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $count = static::getModel()::where('status', SessionSubscriptionStatus::PENDING->value)->count();

        return $count > 0 ? 'warning' : null;
    }
}
