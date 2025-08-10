<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VideoSettingsResource\Pages;
use App\Models\VideoSettings;
use App\Traits\ScopedToAcademy;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Tabs;

class VideoSettingsResource extends BaseSettingsResource
{
    use ScopedToAcademy;

    protected static ?string $model = VideoSettings::class;
    protected static ?string $navigationIcon = 'heroicon-o-video-camera';
    protected static ?string $navigationLabel = 'إعدادات الفيديو';
    protected static ?string $modelLabel = 'إعدادات الفيديو';
    protected static ?string $pluralModelLabel = 'إعدادات الفيديو والاجتماعات';
    protected static ?string $navigationGroup = 'الإعدادات';
    protected static ?int $navigationSort = 15;

    public static function canCreate(): bool { return false; }
    public static function canDeleteAny(): bool { return false; }
    public static function canDelete($record): bool { return false; }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Tabs::make('Video Settings')->tabs([
                
                Tabs\Tab::make('الإنشاء التلقائي')->schema([
                    Section::make('إعدادات الإنشاء التلقائي للاجتماعات')->schema([
                        Grid::make(2)->schema([
                            Forms\Components\Toggle::make('auto_create_meetings')
                                ->label('إنشاء الاجتماعات تلقائياً')
                                ->default(true),
                            Forms\Components\TextInput::make('create_meetings_minutes_before')
                                ->label('إنشاء قبل الجلسة (دقائق)')
                                ->numeric()->default(30)->suffix('دقيقة'),
                        ]),
                        Grid::make(2)->schema([
                            Forms\Components\Toggle::make('auto_end_meetings')
                                ->label('إنهاء الاجتماعات تلقائياً')
                                ->default(true),
                            Forms\Components\TextInput::make('auto_end_minutes_after')
                                ->label('إنهاء بعد الجلسة (دقائق)')
                                ->numeric()->default(15)->suffix('دقيقة'),
                        ]),
                    ]),
                ]),
                
                Tabs\Tab::make('جودة الاجتماعات')->schema([
                    Section::make('الإعدادات الافتراضية')->schema([
                        Grid::make(3)->schema([
                            Forms\Components\Select::make('default_video_quality')
                                ->label('جودة الفيديو')
                                ->options(['low' => 'منخفضة', 'medium' => 'متوسطة', 'high' => 'عالية'])
                                ->default('high'),
                            Forms\Components\Select::make('default_audio_quality')
                                ->label('جودة الصوت')
                                ->options(['low' => 'منخفضة', 'medium' => 'متوسطة', 'high' => 'عالية'])
                                ->default('high'),
                            Forms\Components\TextInput::make('default_max_participants')
                                ->label('الحد الأقصى للمشاركين')
                                ->numeric()->default(50),
                        ]),
                    ]),
                ]),
                
                Tabs\Tab::make('الميزات')->schema([
                    Section::make('الميزات الأساسية')->schema([
                        Grid::make(2)->schema([
                            Forms\Components\Toggle::make('enable_screen_sharing')
                                ->label('مشاركة الشاشة')->default(true),
                            Forms\Components\Toggle::make('enable_chat')
                                ->label('الدردشة النصية')->default(true),
                        ]),
                        Grid::make(2)->schema([
                            Forms\Components\Toggle::make('enable_recording_by_default')
                                ->label('تمكين التسجيل افتراضياً')->default(false),
                            Forms\Components\Toggle::make('enable_noise_cancellation')
                                ->label('إلغاء الضوضاء')->default(true),
                        ]),
                    ]),
                ]),
                
            ])->columnSpanFull()->persistTabInQueryString(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageVideoSettings::route('/'),
        ];
    }
}
