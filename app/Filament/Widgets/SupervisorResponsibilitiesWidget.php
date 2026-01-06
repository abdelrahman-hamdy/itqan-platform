<?php

namespace App\Filament\Widgets;

use App\Models\InteractiveCourse;
use App\Models\QuranCircle;
use App\Models\QuranIndividualCircle;
use App\Models\SupervisorProfile;
use App\Models\User;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Model;

class SupervisorResponsibilitiesWidget extends BaseWidget
{
    public ?Model $record = null;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'المسؤوليات';

    // Prevent this widget from appearing on dashboards
    protected static bool $isDiscoverable = false;

    public function table(Table $table): Table
    {
        // Return empty query if record is not set
        if (! $this->record) {
            return $table
                ->query(fn () => SupervisorProfile::query()->whereRaw('1 = 0'))
                ->columns([]);
        }

        return $table
            ->query(fn () => $this->record->responsibilities()->with('responsable'))
            ->columns([
                Tables\Columns\TextColumn::make('type_label')
                    ->label('النوع')
                    ->getStateUsing(fn ($record) => $this->getTypeLabel($record))
                    ->badge()
                    ->color(fn ($state) => $this->getTypeColor($state)),

                Tables\Columns\TextColumn::make('name')
                    ->label('الاسم')
                    ->getStateUsing(fn ($record) => $this->getResourceName($record)),

                Tables\Columns\TextColumn::make('details')
                    ->label('التفاصيل')
                    ->getStateUsing(fn ($record) => $this->getResourceDetails($record)),

                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->getStateUsing(fn ($record) => $this->getResourceStatus($record))
                    ->badge()
                    ->color(fn ($state) => $state === 'نشط' ? 'success' : 'warning'),

                Tables\Columns\TextColumn::make('count')
                    ->label('العدد')
                    ->getStateUsing(fn ($record) => $this->getResourceCount($record))
                    ->alignCenter(),
            ])
            ->emptyStateHeading('لا توجد مسؤوليات محددة')
            ->emptyStateDescription('يمكنك تحديد المعلمين والدورات من صفحة التعديل')
            ->emptyStateIcon('heroicon-o-clipboard-document-list');
    }

    /**
     * Get the type label in Arabic.
     */
    protected function getTypeLabel($record): string
    {
        if ($record->responsable_type === User::class) {
            $userType = $record->responsable?->user_type;

            return match ($userType) {
                'quran_teacher' => 'معلم قرآن',
                'academic_teacher' => 'معلم أكاديمي',
                default => 'معلم',
            };
        }

        return match ($record->responsable_type) {
            InteractiveCourse::class => 'دورة تفاعلية',
            default => 'غير معروف',
        };
    }

    /**
     * Get the color for each type.
     */
    protected function getTypeColor(string $label): string
    {
        return match ($label) {
            'معلم قرآن' => 'success',
            'معلم أكاديمي' => 'warning',
            'دورة تفاعلية' => 'info',
            default => 'gray',
        };
    }

    /**
     * Get the resource name.
     */
    protected function getResourceName($record): string
    {
        $responsable = $record->responsable;

        if (! $responsable) {
            return 'غير متوفر';
        }

        if ($record->responsable_type === User::class) {
            return $responsable->name ?? 'معلم بدون اسم';
        }

        if ($record->responsable_type === InteractiveCourse::class) {
            return $responsable->title ?? 'دورة بدون عنوان';
        }

        return 'غير معروف';
    }

    /**
     * Get additional details for the resource.
     */
    protected function getResourceDetails($record): string
    {
        $responsable = $record->responsable;

        if (! $responsable) {
            return '-';
        }

        if ($record->responsable_type === User::class) {
            return $responsable->email ?? '-';
        }

        if ($record->responsable_type === InteractiveCourse::class) {
            return $responsable->assignedTeacher?->user?->name ?? 'بدون معلم';
        }

        return '-';
    }

    /**
     * Get the status of the resource.
     */
    protected function getResourceStatus($record): string
    {
        $responsable = $record->responsable;

        if (! $responsable) {
            return 'غير متوفر';
        }

        if ($record->responsable_type === User::class) {
            return $responsable->active_status ? 'نشط' : 'غير نشط';
        }

        if ($record->responsable_type === InteractiveCourse::class) {
            return in_array($responsable->status, ['published', 'active']) ? 'نشط' : 'غير نشط';
        }

        return 'غير معروف';
    }

    /**
     * Get the count for the resource.
     */
    protected function getResourceCount($record): string
    {
        $responsable = $record->responsable;

        if (! $responsable) {
            return '-';
        }

        if ($record->responsable_type === User::class) {
            $userType = $responsable->user_type;

            if ($userType === 'quran_teacher') {
                // Count active circles (group + individual)
                // QuranCircle.quran_teacher_id stores User.id
                $groupCircles = QuranCircle::where('quran_teacher_id', $responsable->id)
                    ->where('status', true)
                    ->count();
                // QuranIndividualCircle.quran_teacher_id also stores User.id
                $individualCircles = QuranIndividualCircle::where('quran_teacher_id', $responsable->id)
                    ->where('status', 'active')
                    ->count();

                return (string) ($groupCircles + $individualCircles).' حلقة';
            }

            if ($userType === 'academic_teacher') {
                // Count active lessons
                $academicProfile = $responsable->academicTeacherProfile;
                if ($academicProfile) {
                    $lessons = $academicProfile->privateSessions()->where('academic_individual_lessons.status', 'active')->count();

                    return (string) $lessons.' درس';
                }

                return '0 درس';
            }
        }

        if ($record->responsable_type === InteractiveCourse::class) {
            return (string) ($responsable->enrollments_count ?? $responsable->enrollments()->count()).' طالب';
        }

        return '-';
    }
}
