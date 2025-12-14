<?php

namespace App\Filament\Teacher\Widgets;

use App\Models\QuranSession;
use App\Models\QuranTrialRequest;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class QuickActionsWidget extends Widget
{
    protected static string $view = 'filament.teacher.widgets.quick-actions';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public function getViewData(): array
    {
        $user = Auth::user();
        $teacher = $user->quranTeacherProfile;

        if (! $teacher) {
            return [
                'actions' => [],
                'todaySession' => null,
                'pendingTrials' => 0,
                'pendingHomework' => 0,
            ];
        }

        // Get today's upcoming session
        $todaySession = QuranSession::where('quran_teacher_id', $teacher->id)
            ->whereDate('scheduled_at', today())
            ->whereIn('status', ['scheduled', 'ready'])
            ->orderBy('scheduled_at')
            ->first();

        // Get pending trial requests count
        $pendingTrials = QuranTrialRequest::where('teacher_id', $teacher->id)
            ->where('status', 'pending')
            ->count();

        // Get pending homework to review count
        $pendingHomework = 0; // TODO: Implement when homework system is ready

        return [
            'todaySession' => $todaySession,
            'pendingTrials' => $pendingTrials,
            'pendingHomework' => $pendingHomework,
        ];
    }
}
