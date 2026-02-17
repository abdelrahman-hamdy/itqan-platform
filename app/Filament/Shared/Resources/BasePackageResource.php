<?php
namespace App\Filament\Shared\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use App\Enums\SessionDuration;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

abstract class BasePackageResource extends Resource
{
    abstract protected static function scopeEloquentQuery(Builder $query): Builder;
    abstract protected static function getTableActions(): array;
    abstract protected static function getTableBulkActions(): array;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('معلومات الباقة الأساسية')->schema([
                TextInput::make('name')->label('اسم الباقة')->required()->maxLength(255)->columnSpanFull(),
                Textarea::make('description')->label('وصف الباقة')->rows(3)->columnSpanFull(),
            ]),
            Section::make('إعدادات الحصص')->description('تكوين عدد ومدة الحصص الشهرية')->schema([
                Grid::make(2)->schema([
                    TextInput::make('sessions_per_month')->label('عدد الحصص في الشهر')
                        ->required()->numeric()->minValue(1)->maxValue(30)->default(8),
                    Select::make('session_duration_minutes')->label('مدة الحصة (دقيقة)')
                        ->options(SessionDuration::options())->default(60)->required(),
                ]),
            ]),
            Section::make('الأسعار')->description('أسعار الباقة لدورات الفوترة المختلفة')->schema([
                Grid::make(3)->schema([
                    TextInput::make('monthly_price')->label('السعر الشهري')->required()->numeric()->minValue(0)->prefix(getCurrencySymbol()),
                    TextInput::make('quarterly_price')->label('السعر ربع السنوي (3 أشهر)')->required()->numeric()->minValue(0)->prefix(getCurrencySymbol()),
                    TextInput::make('yearly_price')->label('السعر السنوي')->required()->numeric()->minValue(0)->prefix(getCurrencySymbol()),
                ]),
            ]),
            Section::make('مميزات الباقة')->description('المميزات والخدمات المتضمنة في الباقة')->schema([
                TagsInput::make('features')->label('مميزات الباقة')->placeholder('اضغط Enter لإضافة ميزة جديدة')->reorderable()->columnSpanFull(),
            ]),
            Section::make('إعدادات عامة')->schema([
                Grid::make(2)->schema([
                    Toggle::make('is_active')->label('الباقة مفعلة')->default(true)->helperText('يمكن للطلاب الاشتراك في الباقات المفعلة فقط'),
                    TextInput::make('sort_order')->label('ترتيب العرض')->numeric()->default(0)->helperText('ترتيب ظهور الباقة في القائمة (0 = الأولى)'),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns(static::getTableColumns())->filters(static::getTableFilters())
            ->recordActions(static::getTableActions())->toolbarActions(static::getTableBulkActions())->defaultSort('sort_order');
    }

    protected static function getTableColumns(): array
    {
        return [
            TextColumn::make('name')->label('اسم الباقة')->searchable()->sortable()->weight('bold'),
            TextColumn::make('sessions_per_month')->label('عدد الحصص/شهر')->sortable()->alignCenter()->badge()->color('info'),
            TextColumn::make('session_duration_minutes')->label('مدة الحصة')->sortable()->alignCenter()->suffix(' دقيقة')->badge()->color('warning'),
            TextColumn::make('monthly_price')->label('السعر الشهري')->money(getCurrencyCode())->sortable()->alignEnd(),
            TextColumn::make('quarterly_price')->label('السعر الربع سنوي')->money(getCurrencyCode())->toggleable(isToggledHiddenByDefault: true)->alignEnd(),
            TextColumn::make('yearly_price')->label('السعر السنوي')->money(getCurrencyCode())->toggleable(isToggledHiddenByDefault: true)->alignEnd(),
            IconColumn::make('is_active')->label('مفعلة')->boolean()->trueIcon('heroicon-o-check-circle')->falseIcon('heroicon-o-x-circle')->trueColor('success')->falseColor('danger'),
            TextColumn::make('sort_order')->label('الترتيب')->sortable()->alignCenter()->toggleable(isToggledHiddenByDefault: true),
            TextColumn::make('created_at')->label('تاريخ الإنشاء')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
        ];
    }

    protected static function getTableFilters(): array
    {
        return [
            TernaryFilter::make('is_active')->label('الحالة')->placeholder('جميع الباقات')->trueLabel('المفعلة')->falseLabel('غير المفعلة'),
            TrashedFilter::make(),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['academy']);
        return static::scopeEloquentQuery($query);
    }
}
