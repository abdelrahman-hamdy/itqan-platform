<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AdminResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Services\AcademyContextService;

class AdminResource extends BaseResource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationGroup = 'إدارة المستخدمين';

    protected static ?string $navigationLabel = 'المديرون';

    protected static ?string $modelLabel = 'مدير';

    protected static ?string $pluralModelLabel = 'المديرون';

    protected static ?int $navigationSort = 6;

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->where('user_type', 'admin');
        
        // For super admin in admin panel
        if (request()->is('admin/*')) {
            $academyId = AcademyContextService::getCurrentAcademyId();
            
            if ($academyId) {
                // When academy is selected, show both:
                // 1. Super admins (academy_id = null) 
                // 2. Academy-specific admins for the selected academy
                $query->where(function ($q) use ($academyId) {
                    $q->whereNull('academy_id') // Super admins
                      ->orWhere('academy_id', $academyId); // Academy admins for selected academy
                });
            }
            // If no academy context, show all admins
        } else {
            // For academy panel, only show current academy's admins
            $academyId = AcademyContextService::getCurrentAcademyId();
            if ($academyId) {
                $query->where('academy_id', $academyId);
            }
        }
        
        return $query;
    }



    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('المعلومات الأساسية')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('email')
                                    ->label('البريد الإلكتروني')
                                    ->email()
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('first_name')
                                    ->label('الاسم الأول')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('last_name')
                                    ->label('اسم العائلة')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('phone')
                                    ->label('رقم الهاتف')
                                    ->tel()
                                    ->required()
                                    ->maxLength(255),
                            ]),
                        Forms\Components\FileUpload::make('avatar')
                            ->label('الصورة الشخصية')
                            ->image()
                            ->imageEditor()
                            ->circleCropper()
                            ->directory('avatars/admins')
                            ->maxSize(2048),
                    ]),
                Forms\Components\Section::make('معلومات الحساب')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('password')
                                    ->label('كلمة المرور')
                                    ->password()
                                    ->dehydrated(fn ($state) => filled($state))
                                    ->required(fn (string $context): bool => $context === 'create'),
                                Forms\Components\Select::make('user_type')
                                    ->label('نوع المستخدم')
                                    ->options([
                                        'admin' => 'مدير',
                                    ])
                                    ->required()
                                    ->default('admin')
                                    ->disabled(), // Always admin for this resource
                                Forms\Components\Toggle::make('status')
                                    ->label('نشط')
                                    ->default(true)
                                    ->dehydrateStateUsing(fn ($state) => $state ? 'active' : 'inactive'),
                            ]),
                        Forms\Components\Hidden::make('academy_id')
                            ->default(fn () => AcademyContextService::getCurrentAcademyId())
                            ->dehydrated()
                            ->required(),
                        Forms\Components\Textarea::make('notes')
                            ->label('ملاحظات')
                            ->rows(3)
                            ->maxLength(1000),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('avatar')
                    ->label('الصورة')
                    ->circular(),
                Tables\Columns\TextColumn::make('name')
                    ->label('الاسم الكامل')
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('البريد الإلكتروني')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('رقم الهاتف')
                    ->searchable(),
                Tables\Columns\TextColumn::make('academy.name')
                    ->label('الأكاديمية')
                    ->badge()
                    ->getStateUsing(function ($record) {
                        if (is_null($record->academy_id)) {
                            return 'مدير عام'; // Super Admin
                        }
                        return $record->academy?->name ?? 'غير محدد';
                    })
                    ->color(function ($record) {
                        return is_null($record->academy_id) ? 'warning' : 'info';
                    })
                    ->visible(static::isViewingAllAcademies()),
                Tables\Columns\BadgeColumn::make('user_type')
                    ->label('نوع المستخدم')
                    ->getStateUsing(function ($record) {
                        if (is_null($record->academy_id)) {
                            return 'super_admin';
                        }
                        return 'academy_admin';
                    })
                    ->colors([
                        'warning' => 'super_admin',
                        'success' => 'academy_admin',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'super_admin' => 'مدير عام',
                        'academy_admin' => 'مدير أكاديمية',
                        default => $state,
                    }),
                Tables\Columns\IconColumn::make('status')
                    ->label('الحالة')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->getStateUsing(fn($record) => $record->status === 'active'),
                Tables\Columns\TextColumn::make('last_login_at')
                    ->label('آخر تسجيل دخول')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('user_type')
                    ->label('نوع المستخدم')
                    ->options([
                        'admin' => 'مدير',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'active' => 'نشط',
                        'inactive' => 'غير نشط',
                        'pending' => 'في الانتظار',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListAdmins::route('/'),
            'create' => Pages\CreateAdmin::route('/create'),
            'view' => Pages\ViewAdmin::route('/{record}'),
            'edit' => Pages\EditAdmin::route('/{record}/edit'),
        ];
    }
}
