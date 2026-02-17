<?php
namespace App\Filament\Shared\Resources;

use App\Enums\SessionDuration;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

abstract class BasePackageResource extends Resource
{
    abstract protected static function scopeEloquentQuery(Builder $query): Builder;
    abstract protected static function getTableActions(): array;
    abstract protected static function getTableBulkActions(): array;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('معلومات الباقة الأساسية')->schema([
                Forms\Components\TextInput::make('name')->label('اسم الباقة')->required()->maxLength(255)->columnSpanFull(),
                Forms\Components\Textarea::make('description')->label('وصف الباقة')->rows(3)->columnSpanFull(),
            ]),
            Forms\Components\Section::make('إعدادات الحصص')->description('تكوين عدد ومدة الحصص الشهرية')->schema([
                Forms\Components\Grid::make(2)->schema([
                    Forms\Components\TextInput::make('sessions_per_month')->label('عدد الحصص في الشهر')
                        ->required()->numeric()->minValue(1)->maxValue(30)->default(8),
                    Forms\Components\Select::make('session_duration_minutes')->label('مدة الحصة (دقيقة)')
                        ->options(SessionDuration::options())->default(60)->required(),
                ]),
            ]),
            Forms\Components\Section::make('الأسعار')->description('أسعار الباقة لدورات الفوترة المختلفة')->schema([
                Forms\Components\Grid::make(3)->schema([
                    Forms\Components\TextInput::make('monthly_price')->label('السعر الشهري')->required()->numeric()->minValue(0)->prefix(getCurrencySymbol()),
                    Forms\Components\TextInput::make('quarterly_price')->label('السعر ربع السنوي (3 أشهر)')->required()->numeric()->minValue(0)->prefix(getCurrencySymbol()),
                    Forms\Components\TextInput::make('yearly_price')->label('السعر السنوي')->required()->numeric()->minValue(0)->prefix(getCurrencySymbol()),
                ]),
            ]),
            Forms\Components\Section::make('مميزات الباقة')->description('المميزات والخدمات المتضمنة في الباقة')->schema([
                Forms\Components\TagsInput::make('features')->label('مميزات الباقة')->placeholder('اضغط Enter لإضافة ميزة جديدة')->reorderable()->columnSpanFull(),
            ]),
            Forms\Components\Section::make('إعدادات عامة')->schema([
                Forms\Components\Grid::make(2)->schema([
                    Forms\Components\Toggle::make('is_active')->label('الباقة مفعلة')->default(true)->helperText('يمكن للطلاب الاشتراك في الباقات المفعلة فقط'),
                    Forms\Components\TextInput::make('sort_order')->label('ترتيب العرض')->numeric()->default(0)->helperText('ترتيب ظهور الباقة في القائمة (0 = الأولى)'),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns(static::getTableColumns())->filters(static::getTableFilters())
            ->actions(static::getTableActions())->bulkActions(static::getTableBulkActions())->defaultSort('sort_order');
    }

    protected static function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('name')->label('اسم الباقة')->searchable()->sortable()->weight('bold'),
            Tables\Columns\TextColumn::make('sessions_per_month')->label('عدد الحصص/شهر')->sortable()->alignCenter()->badge()->color('info'),
            Tables\Columns\TextColumn::make('session_duration_minutes')->label('مدة الحصة')->sortable()->alignCenter()->suffix(' دقيقة')->badge()->color('warning'),
            Tables\Columns\TextColumn::make('monthly_price')->label('السعر الشهري')->money(getCurrencyCode())->sortable()->alignEnd(),
            Tables\Columns\TextColumn::make('quarterly_price')->label('السعر الربع سنوي')->money(getCurrencyCode())->toggleable(isToggledHiddenByDefault: true)->alignEnd(),
            Tables\Columns\TextColumn::make('yearly_price')->label('السعر السنوي')->money(getCurrencyCode())->toggleable(isToggledHiddenByDefault: true)->alignEnd(),
            Tables\Columns\IconColumn::make('is_active')->label('مفعلة')->boolean()->trueIcon('heroicon-o-check-circle')->falseIcon('heroicon-o-x-circle')->trueColor('success')->falseColor('danger'),
            Tables\Columns\TextColumn::make('sort_order')->label('الترتيب')->sortable()->alignCenter()->toggleable(isToggledHiddenByDefault: true),
            Tables\Columns\TextColumn::make('created_at')->label('تاريخ الإنشاء')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
        ];
    }

    protected static function getTableFilters(): array
    {
        return [
            Tables\Filters\TernaryFilter::make('is_active')->label('الحالة')->placeholder('جميع الباقات')->trueLabel('المفعلة')->falseLabel('غير المفعلة'),
            Tables\Filters\TrashedFilter::make(),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['academy']);
        return static::scopeEloquentQuery($query);
    }
}
