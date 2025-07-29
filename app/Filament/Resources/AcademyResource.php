<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AcademyResource\Pages;
use App\Filament\Resources\AcademyResource\RelationManagers;
use App\Models\Academy;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AcademyResource extends Resource
{
    protected static ?string $model = Academy::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';
    
    protected static ?string $navigationLabel = 'الأكاديميات';
    
    protected static ?string $navigationGroup = 'إدارة النظام';
    
    protected static ?int $navigationSort = 1;
    
    protected static ?string $modelLabel = 'أكاديمية';
    
    protected static ?string $pluralModelLabel = 'الأكاديميات';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات الأكاديمية')
                    ->description('البيانات الأساسية للأكاديمية')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('اسم الأكاديمية')
                            ->required()
                            ->maxLength(255),
                            
                        Forms\Components\TextInput::make('subdomain')
                            ->label('النطاق الفرعي')
                            ->required()
                            ->unique(Academy::class, 'subdomain', ignoreRecord: true)
                            ->maxLength(255)
                            ->regex('/^[a-z0-9-]+$/')
                            ->helperText('مثال: alnoor (سيصبح alnoor.itqan-platform.test)'),
                            
                        Forms\Components\Placeholder::make('full_domain')
                            ->label('الرابط الكامل')
                            ->content(fn ($get) => $get('subdomain') ? $get('subdomain') . '.itqan-platform.test' : 'سيتم إنشاؤه تلقائياً')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                    
                Forms\Components\Section::make('إعدادات الإدارة')
                    ->schema([
                        Forms\Components\Select::make('admin_id')
                            ->label('مدير الأكاديمية')
                            ->options(User::where('role', 'academy_admin')->pluck('name', 'id'))
                            ->searchable()
                            ->preload(),
                            
                        Forms\Components\Toggle::make('is_active')
                            ->label('مفعلة')
                            ->default(true)
                            ->helperText('إلغاء التفعيل سيمنع الوصول للأكاديمية'),
                            
                        Forms\Components\Select::make('status')
                            ->label('حالة الأكاديمية')
                            ->options([
                                'active' => 'نشطة',
                                'suspended' => 'معلقة',
                                'maintenance' => 'صيانة',
                            ])
                            ->default('active')
                            ->required(),
                    ])
                    ->columns(2),
                    
                Forms\Components\Section::make('إعدادات إضافية')
                    ->schema([
                        Forms\Components\Textarea::make('description')
                            ->label('وصف الأكاديمية')
                            ->maxLength(500)
                            ->rows(3),
                            
                        Forms\Components\ColorPicker::make('brand_color')
                            ->label('لون العلامة التجارية')
                            ->default('#0ea5e9'),
                            
                        Forms\Components\FileUpload::make('logo')
                            ->label('شعار الأكاديمية')
                            ->image()
                            ->imageEditor()
                            ->maxSize(2048),
                    ])
                    ->collapsible(),
                    
                Forms\Components\Section::make('الإحصائيات المالية')
                    ->description('يتم تحديثها تلقائياً بناءً على المعاملات')
                    ->schema([
                        Forms\Components\TextInput::make('total_revenue')
                            ->label('إجمالي الإيرادات')
                            ->numeric()
                            ->prefix('ر.س')
                            ->default(0),
                            
                        Forms\Components\TextInput::make('monthly_revenue')
                            ->label('إيرادات الشهر الحالي')
                            ->numeric()
                            ->prefix('ر.س')
                            ->default(0),
                            
                        Forms\Components\TextInput::make('pending_payments')
                            ->label('المدفوعات المعلقة')
                            ->numeric()
                            ->prefix('ر.س')
                            ->default(0),
                            
                        Forms\Components\TextInput::make('active_subscriptions')
                            ->label('الاشتراكات النشطة')
                            ->numeric()
                            ->default(0),
                            
                        Forms\Components\TextInput::make('growth_rate')
                            ->label('معدل النمو (%)')
                            ->numeric()
                            ->suffix('%')
                            ->default(0),
                    ])
                    ->columns(3)
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('logo')
                    ->label('')
                    ->circular()
                    ->size(40)
                    ->defaultImageUrl(fn () => 'https://ui-avatars.com/api/?name=Academy&background=0ea5e9&color=fff'),
                    
                Tables\Columns\TextColumn::make('name')
                    ->label('اسم الأكاديمية')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                    
                Tables\Columns\TextColumn::make('full_domain')
                    ->label('النطاق')
                    ->copyable()
                    ->copyMessage('تم نسخ الرابط')
                    ->color('primary'),
                    
                Tables\Columns\TextColumn::make('admin.name')
                    ->label('المدير')
                    ->searchable()
                    ->sortable()
                    ->default('غير محدد'),
                    
                Tables\Columns\BadgeColumn::make('status')
                    ->label('الحالة')
                    ->colors([
                        'success' => 'active',
                        'warning' => 'suspended',
                        'secondary' => 'maintenance',
                    ])
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'active' => 'نشطة',
                        'suspended' => 'معلقة', 
                        'maintenance' => 'صيانة',
                        default => $state,
                    }),
                    
                Tables\Columns\TextColumn::make('users_count')
                    ->label('المستخدمين')
                    ->counts('users')
                    ->sortable()
                    ->alignCenter(),
                    
                Tables\Columns\TextColumn::make('total_revenue')
                    ->label('الإيرادات')
                    ->money('SAR')
                    ->sortable()
                    ->alignEnd(),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'active' => 'نشطة',
                        'suspended' => 'معلقة',
                        'maintenance' => 'صيانة',
                    ]),
                    
                Tables\Filters\Filter::make('has_admin')
                    ->label('لها مدير')
                    ->query(fn ($query) => $query->whereNotNull('admin_id')),
                    
                Tables\Filters\Filter::make('created_this_month')
                    ->label('أنشئت هذا الشهر')
                    ->query(fn ($query) => $query->whereMonth('created_at', now()->month)),
            ])
            ->actions([
                Tables\Actions\Action::make('visit')
                    ->label('زيارة')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('gray')
                    ->url(fn (Academy $record): string => "http://{$record->full_domain}")
                    ->openUrlInNewTab(),
                    
                Tables\Actions\ViewAction::make()
                    ->label('عرض'),
                    
                Tables\Actions\EditAction::make()
                    ->label('تعديل'),
                    
                Tables\Actions\DeleteAction::make()
                    ->label('حذف'),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->label('حذف المحدد'),
                    
                Tables\Actions\BulkAction::make('activate')
                    ->label('تفعيل المحدد')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->action(function ($records) {
                        $records->each(fn ($record) => $record->update(['status' => 'active']));
                    }),
                    
                Tables\Actions\BulkAction::make('suspend')
                    ->label('تعليق المحدد')
                    ->icon('heroicon-o-pause-circle')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(function ($records) {
                        $records->each(fn ($record) => $record->update(['status' => 'suspended']));
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('معلومات الأكاديمية')
                    ->schema([
                        Infolists\Components\Split::make([
                            Infolists\Components\Grid::make(2)
                                ->schema([
                                    Infolists\Components\TextEntry::make('name')
                                        ->label('اسم الأكاديمية'),
                                    Infolists\Components\TextEntry::make('full_domain')
                                        ->label('النطاق')
                                        ->formatStateUsing(fn (string $state): string => $state),
                                    Infolists\Components\TextEntry::make('admin.name')
                                        ->label('مدير الأكاديمية'),
                                    Infolists\Components\TextEntry::make('status')
                                        ->label('الحالة')
                                        ->badge()
                                        ->colors([
                                            'success' => 'active',
                                            'warning' => 'suspended',
                                            'secondary' => 'maintenance',
                                        ]),
                                ]),
                            Infolists\Components\ImageEntry::make('logo_url')
                                ->label('الشعار')
                                ->height(100)
                                ->width(100),
                        ])->from('lg'),
                    ]),
                    
                Infolists\Components\Section::make('إحصائيات')
                    ->schema([
                        Infolists\Components\Grid::make(4)
                            ->schema([
                                Infolists\Components\TextEntry::make('users_count')
                                    ->label('إجمالي المستخدمين')
                                    ->numeric(),
                                Infolists\Components\TextEntry::make('teachers_count')
                                    ->label('المعلمين')
                                    ->numeric(),
                                Infolists\Components\TextEntry::make('students_count')
                                    ->label('الطلاب')
                                    ->numeric(),
                                Infolists\Components\TextEntry::make('total_revenue')
                                    ->label('إجمالي الإيرادات')
                                    ->money('SAR'),
                            ]),
                    ]),
            ]);
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
            'index' => Pages\ListAcademies::route('/'),
            'create' => Pages\CreateAcademy::route('/create'),
            'view' => Pages\ViewAcademy::route('/{record}'),
            'edit' => Pages\EditAcademy::route('/{record}/edit'),
        ];
    }
    
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
}
