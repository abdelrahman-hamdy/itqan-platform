<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BusinessServiceRequestResource\Pages;
use App\Models\BusinessServiceRequest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BusinessServiceRequestResource extends Resource
{
    protected static ?string $model = BusinessServiceRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'خدمات الأعمال';

    protected static ?string $navigationLabel = 'طلبات الأعمال';

    protected static ?string $modelLabel = 'طلب خدمة';

    protected static ?string $pluralModelLabel = 'طلبات الأعمال';

    protected static ?int $navigationSort = 2;

    /**
     * Check if the current user can access this resource
     */
    public static function canAccess(): bool
    {
        return \App\Services\AcademyContextService::isSuperAdmin();
    }

    /**
     * Check if the current user can create records
     */
    public static function canCreate(): bool
    {
        return \App\Services\AcademyContextService::isSuperAdmin();
    }

    /**
     * Check if the current user can edit records
     */
    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return \App\Services\AcademyContextService::isSuperAdmin();
    }

    /**
     * Check if the current user can delete records
     */
    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return \App\Services\AcademyContextService::isSuperAdmin();
    }

    /**
     * Check if the current user can view records
     */
    public static function canView(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return \App\Services\AcademyContextService::isSuperAdmin();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات العميل')
                    ->schema([
                        Forms\Components\TextInput::make('client_name')
                            ->label('اسم العميل')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('client_phone')
                            ->label('رقم الهاتف')
                            ->tel()
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('client_email')
                            ->label('البريد الإلكتروني')
                            ->email()
                            ->required()
                            ->maxLength(255),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('تفاصيل المشروع')
                    ->schema([
                        Forms\Components\Select::make('service_category_id')
                            ->label('نوع الخدمة')
                            ->relationship('serviceCategory', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),

                        Forms\Components\Select::make('project_budget')
                            ->label('الميزانية المتوقعة')
                            ->options([
                                '500-1000' => '500 - 1000 ر.س',
                                '1000-3000' => '1000 - 3000 ر.س',
                                '3000-5000' => '3000 - 5000 ر.س',
                                '5000-10000' => '5000 - 10000 ر.س',
                                '10000+' => 'أكثر من 10000 ر.س',
                            ])
                            ->placeholder('اختر الميزانية'),

                        Forms\Components\Select::make('project_deadline')
                            ->label('الموعد المطلوب')
                            ->options([
                                'urgent' => 'عاجل (أسبوع)',
                                'normal' => 'عادي (أسبوعين)',
                                'flexible' => 'مرن (شهر أو أكثر)',
                            ])
                            ->placeholder('اختر الموعد'),

                        Forms\Components\Textarea::make('project_description')
                            ->label('وصف المشروع')
                            ->required()
                            ->rows(6)
                            ->placeholder('اكتب تفاصيل مشروعك والمتطلبات المحددة...'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('معلومات إدارية')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('حالة الطلب')
                            ->options([
                                'pending' => 'في الانتظار',
                                'reviewed' => 'تم المراجعة',
                                'approved' => 'مقبول',
                                'rejected' => 'مرفوض',
                                'completed' => 'مكتمل',
                            ])
                            ->default('pending')
                            ->required(),

                        Forms\Components\Textarea::make('admin_notes')
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
                Tables\Columns\TextColumn::make('client_name')
                    ->label('اسم العميل')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('client_email')
                    ->label('البريد الإلكتروني')
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('client_phone')
                    ->label('رقم الهاتف')
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('serviceCategory.name')
                    ->label('نوع الخدمة')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('project_budget')
                    ->label('الميزانية')
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        '500-1000' => '500 - 1000 ر.س',
                        '1000-3000' => '1000 - 3000 ر.س',
                        '3000-5000' => '3000 - 5000 ر.س',
                        '5000-10000' => '5000 - 10000 ر.س',
                        '10000+' => 'أكثر من 10000 ر.س',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('project_deadline')
                    ->label('الموعد')
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'urgent' => 'عاجل (أسبوع)',
                        'normal' => 'عادي (أسبوعين)',
                        'flexible' => 'مرن (شهر أو أكثر)',
                        default => $state,
                    }),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('الحالة')
                    ->colors([
                        'warning' => 'pending',
                        'info' => 'reviewed',
                        'success' => 'approved',
                        'danger' => 'rejected',
                        'gray' => 'completed',
                    ])
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'pending' => 'في الانتظار',
                        'reviewed' => 'تم المراجعة',
                        'approved' => 'مقبول',
                        'rejected' => 'مرفوض',
                        'completed' => 'مكتمل',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الطلب')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('حالة الطلب')
                    ->options([
                        'pending' => 'في الانتظار',
                        'reviewed' => 'تم المراجعة',
                        'approved' => 'مقبول',
                        'rejected' => 'مرفوض',
                        'completed' => 'مكتمل',
                    ]),

                Tables\Filters\SelectFilter::make('service_category_id')
                    ->label('نوع الخدمة')
                    ->relationship('serviceCategory', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('created_at')
                    ->label('تاريخ الطلب')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('من تاريخ'),
                        Forms\Components\DatePicker::make('created_until')
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
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('عرض'),
                Tables\Actions\EditAction::make()
                    ->label('تعديل'),
                Tables\Actions\Action::make('change_status')
                    ->label('تغيير الحالة')
                    ->icon('heroicon-o-arrow-path')
                    ->form([
                        Forms\Components\Select::make('status')
                            ->label('الحالة الجديدة')
                            ->options([
                                'pending' => 'في الانتظار',
                                'reviewed' => 'تم المراجعة',
                                'approved' => 'مقبول',
                                'rejected' => 'مرفوض',
                                'completed' => 'مكتمل',
                            ])
                            ->required(),
                        Forms\Components\Textarea::make('admin_notes')
                            ->label('ملاحظات إدارية'),
                    ])
                    ->action(function (array $data, BusinessServiceRequest $record): void {
                        $record->update($data);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('حذف المحدد'),
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
            'index' => Pages\ListBusinessServiceRequests::route('/'),
            'create' => Pages\CreateBusinessServiceRequest::route('/create'),
            'view' => Pages\ViewBusinessServiceRequest::route('/{record}'),
            'edit' => Pages\EditBusinessServiceRequest::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'pending')->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
