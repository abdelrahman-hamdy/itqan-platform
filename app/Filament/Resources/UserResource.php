<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\Academy;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    
    protected static ?string $navigationLabel = 'المستخدمون';
    
    protected static ?string $navigationGroup = 'إدارة النظام';
    
    protected static ?int $navigationSort = 2;
    
    protected static ?string $modelLabel = 'مستخدم';
    
    protected static ?string $pluralModelLabel = 'المستخدمون';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('المعلومات الشخصية')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('first_name')
                                    ->label('الاسم الأول')
                                    ->required()
                                    ->maxLength(255),
                                    
                                Forms\Components\TextInput::make('last_name')
                                    ->label('اسم العائلة')
                                    ->required()
                                    ->maxLength(255),
                            ]),
                            
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('email')
                                    ->label('البريد الإلكتروني')
                                    ->email()
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(255),
                                    
                                Forms\Components\TextInput::make('phone')
                                    ->label('رقم الهاتف')
                                    ->tel()
                                    ->maxLength(20),
                            ]),
                            
                        Forms\Components\Select::make('academy_id')
                            ->label('الأكاديمية')
                            ->relationship('academy', 'name')
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->label('اسم الأكاديمية')
                                    ->required(),
                                Forms\Components\TextInput::make('subdomain')
                                    ->label('النطاق الفرعي')
                                    ->required(),
                            ]),
                            
                        Forms\Components\FileUpload::make('avatar')
                            ->label('الصورة الشخصية')
                            ->image()
                            ->directory('avatars')
                            ->visibility('public'),
                    ]),
                    
                Forms\Components\Section::make('معلومات الحساب')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('role')
                                    ->label('الدور')
                                    ->options([
                                        'super_admin' => 'مدير النظام',
                                        'academy_admin' => 'مدير أكاديمية',
                                        'teacher' => 'معلم',
                                        'supervisor' => 'مشرف',
                                        'student' => 'طالب',
                                        'parent' => 'ولي أمر',
                                    ])
                                    ->required()
                                    ->native(false),
                                    
                                Forms\Components\Select::make('status')
                                    ->label('حالة الحساب')
                                    ->options([
                                        'active' => 'نشط',
                                        'pending' => 'في الانتظار',
                                        'inactive' => 'غير نشط',
                                        'suspended' => 'معلق',
                                    ])
                                    ->default('pending')
                                    ->required()
                                    ->native(false),
                            ]),
                            
                        Forms\Components\TextInput::make('password')
                            ->label('كلمة المرور')
                            ->password()
                            ->dehydrateStateUsing(fn ($state) => bcrypt($state))
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $context): bool => $context === 'create')
                            ->minLength(8),
                    ]),
                    
                Forms\Components\Section::make('معلومات إضافية')
                    ->schema([
                        Forms\Components\Textarea::make('bio')
                            ->label('نبذة تعريفية')
                            ->maxLength(1000)
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('avatar')
                    ->label('الصورة')
                    ->circular()
                    ->defaultImageUrl(url('/images/default-avatar.png')),
                    
                Tables\Columns\TextColumn::make('full_name')
                    ->label('الاسم الكامل')
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('email')
                    ->label('البريد الإلكتروني')
                    ->searchable()
                    ->copyable(),
                    
                Tables\Columns\BadgeColumn::make('role')
                    ->label('الدور')
                    ->colors([
                        'danger' => 'super_admin',
                        'warning' => 'academy_admin',
                        'primary' => 'teacher',
                        'secondary' => 'supervisor',
                        'success' => 'student',
                        'info' => 'parent',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'super_admin' => 'مدير النظام',
                        'academy_admin' => 'مدير أكاديمية',
                        'teacher' => 'معلم',
                        'supervisor' => 'مشرف',
                        'student' => 'طالب',
                        'parent' => 'ولي أمر',
                        default => $state,
                    }),
                    
                Tables\Columns\TextColumn::make('academy.name')
                    ->label('الأكاديمية')
                    ->searchable()
                    ->sortable()
                    ->placeholder('غير محدد'),
                    
                Tables\Columns\BadgeColumn::make('status')
                    ->label('الحالة')
                    ->colors([
                        'success' => 'active',
                        'warning' => 'pending',
                        'danger' => 'inactive',
                        'secondary' => 'suspended',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'active' => 'نشط',
                        'pending' => 'في الانتظار',
                        'inactive' => 'غير نشط',
                        'suspended' => 'معلق',
                        default => $state,
                    }),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->label('الدور')
                    ->options([
                        'super_admin' => 'مدير النظام',
                        'academy_admin' => 'مدير أكاديمية',
                        'teacher' => 'معلم',
                        'supervisor' => 'مشرف',
                        'student' => 'طالب',
                        'parent' => 'ولي أمر',
                    ]),
                    
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'active' => 'نشط',
                        'pending' => 'في الانتظار',
                        'inactive' => 'غير نشط',
                        'suspended' => 'معلق',
                    ]),
                    
                Tables\Filters\SelectFilter::make('academy_id')
                    ->label('الأكاديمية')
                    ->relationship('academy', 'name')
                    ->searchable()
                    ->preload(),
                    
                Tables\Filters\Filter::make('created_at')
                    ->label('تاريخ الإنشاء')
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
                Tables\Actions\DeleteAction::make()
                    ->label('حذف'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('حذف المحدد'),
                        
                    Tables\Actions\BulkAction::make('activate')
                        ->label('تفعيل المحدد')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(fn ($records) => $records->each(fn ($record) => $record->update(['status' => 'active']))),
                        
                    Tables\Actions\BulkAction::make('suspend')
                        ->label('تعليق المحدد')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(fn ($records) => $records->each(fn ($record) => $record->update(['status' => 'suspended']))),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make('المعلومات الشخصية')
                    ->schema([
                        Components\Split::make([
                            Components\Grid::make(2)
                                ->schema([
                                    Components\TextEntry::make('full_name')
                                        ->label('الاسم الكامل'),
                                    Components\TextEntry::make('email')
                                        ->label('البريد الإلكتروني')
                                        ->copyable(),
                                    Components\TextEntry::make('phone')
                                        ->label('رقم الهاتف')
                                        ->placeholder('غير محدد'),
                                    Components\TextEntry::make('academy.name')
                                        ->label('الأكاديمية')
                                        ->placeholder('غير محدد'),
                                ]),
                            Components\ImageEntry::make('avatar')
                                ->label('الصورة الشخصية')
                                ->circular()
                                ->defaultImageUrl(url('/images/default-avatar.png'))
                                ->grow(false),
                        ]),
                    ]),
                    
                Components\Section::make('معلومات الحساب')
                    ->schema([
                        Components\Grid::make(3)
                            ->schema([
                                Components\TextEntry::make('role')
                                    ->label('الدور')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'super_admin' => 'danger',
                                        'academy_admin' => 'warning',
                                        'teacher' => 'primary',
                                        'supervisor' => 'secondary',
                                        'student' => 'success',
                                        'parent' => 'info',
                                        default => 'gray',
                                    })
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        'super_admin' => 'مدير النظام',
                                        'academy_admin' => 'مدير أكاديمية',
                                        'teacher' => 'معلم',
                                        'supervisor' => 'مشرف',
                                        'student' => 'طالب',
                                        'parent' => 'ولي أمر',
                                        default => $state,
                                    }),
                                    
                                Components\TextEntry::make('status')
                                    ->label('حالة الحساب')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'active' => 'success',
                                        'pending' => 'warning',
                                        'inactive' => 'danger',
                                        'suspended' => 'secondary',
                                        default => 'gray',
                                    })
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        'active' => 'نشط',
                                        'pending' => 'في الانتظار',
                                        'inactive' => 'غير نشط',
                                        'suspended' => 'معلق',
                                        default => $state,
                                    }),
                                    
                                Components\TextEntry::make('created_at')
                                    ->label('تاريخ الإنشاء')
                                    ->dateTime('Y-m-d H:i'),
                            ]),
                    ]),
                    
                Components\Section::make('معلومات إضافية')
                    ->schema([
                        Components\TextEntry::make('bio')
                            ->label('نبذة تعريفية')
                            ->placeholder('لا توجد نبذة تعريفية')
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'view' => Pages\ViewUser::route('/{record}'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
    
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
} 