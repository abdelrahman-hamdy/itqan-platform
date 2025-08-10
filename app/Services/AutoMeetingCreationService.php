<?php

namespace App\Services;

use App\Models\QuranSession;
use App\Models\VideoSettings;
use App\Models\TeacherVideoSettings;
use App\Models\Academy;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class AutoMeetingCreationService
{
    private LiveKitService $livekitService;

    public function __construct(LiveKitService $livekitService)
    {
        $this->livekitService = $livekitService;
    }

    /**
     * Create meetings for all eligible sessions across all academies
     */
    public function createMeetingsForAllAcademies(): array
    {
        $results = [
            'total_academies_processed' => 0,
            'total_sessions_processed' => 0,
            'meetings_created' => 0,
            'meetings_failed' => 0,
            'errors' => [],
        ];

        Log::info('Starting auto meeting creation process for all academies');

        try {
            $academies = Academy::where('is_active', true)->get();
            $results['total_academies_processed'] = $academies->count();

            foreach ($academies as $academy) {
                $academyResults = $this->createMeetingsForAcademy($academy);
                
                $results['total_sessions_processed'] += $academyResults['sessions_processed'];
                $results['meetings_created'] += $academyResults['meetings_created'];
                $results['meetings_failed'] += $academyResults['meetings_failed'];
                
                if (!empty($academyResults['errors'])) {
                    $results['errors'][$academy->id] = $academyResults['errors'];
                }
            }

            Log::info('Auto meeting creation completed', $results);

        } catch (\Exception $e) {
            Log::error('Failed to process auto meeting creation for all academies', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $results['errors']['global'] = $e->getMessage();
        }

        return $results;
    }

    /**
     * Create meetings for eligible sessions in a specific academy
     */
    public function createMeetingsForAcademy(Academy $academy): array
    {
        $results = [
            'academy_id' => $academy->id,
            'academy_name' => $academy->name,
            'sessions_processed' => 0,
            'meetings_created' => 0,
            'meetings_failed' => 0,
            'errors' => [],
        ];

        Log::info('Processing academy for auto meeting creation', [
            'academy_id' => $academy->id,
            'academy_name' => $academy->name
        ]);

        try {
            // Get video settings for this academy
            $videoSettings = VideoSettings::forAcademy($academy);
            
            // Skip if auto creation is disabled
            if (!$videoSettings->shouldAutoCreateMeetings()) {
                Log::info('Auto meeting creation disabled for academy', [
                    'academy_id' => $academy->id
                ]);
                return $results;
            }

            // Get eligible sessions
            $eligibleSessions = $this->getEligibleSessions($academy, $videoSettings);
            $results['sessions_processed'] = $eligibleSessions->count();

            Log::info('Found eligible sessions for meeting creation', [
                'academy_id' => $academy->id,
                'session_count' => $eligibleSessions->count()
            ]);

            // Create meetings for each eligible session
            foreach ($eligibleSessions as $session) {
                try {
                    $this->createMeetingForSession($session, $videoSettings);
                    $results['meetings_created']++;
                    
                    Log::info('Meeting created for session', [
                        'session_id' => $session->id,
                        'academy_id' => $academy->id
                    ]);
                    
                } catch (\Exception $e) {
                    $results['meetings_failed']++;
                    $results['errors'][] = [
                        'session_id' => $session->id,
                        'error' => $e->getMessage()
                    ];
                    
                    Log::error('Failed to create meeting for session', [
                        'session_id' => $session->id,
                        'academy_id' => $academy->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

        } catch (\Exception $e) {
            Log::error('Failed to process academy for auto meeting creation', [
                'academy_id' => $academy->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $results['errors'][] = [
                'type' => 'academy_processing_error',
                'error' => $e->getMessage()
            ];
        }

        return $results;
    }

    /**
     * Get sessions that are eligible for meeting creation
     */
    private function getEligibleSessions(Academy $academy, VideoSettings $videoSettings): Collection
    {
        $now = now();
        
        // Calculate the time window for meeting creation
        $startTime = $now;
        $endTime = $now->copy()->addHours(2); // Look ahead 2 hours
        
        // Get sessions that:
        // 1. Belong to this academy
        // 2. Are scheduled in the near future
        // 3. Don't already have meeting rooms created
        // 4. Are in 'scheduled' status
        // 5. Should have meetings created now based on the timing settings
        
        $sessions = QuranSession::where('academy_id', $academy->id)
            ->where('status', 'scheduled')
            ->whereNull('meeting_room_name') // No meeting room created yet
            ->whereNotNull('scheduled_at')
            ->whereBetween('scheduled_at', [
                $startTime,
                $endTime
            ])
            ->with(['academy', 'teacher', 'individualCircle', 'circle'])
            ->get()
            ->filter(function ($session) use ($videoSettings) {
                // Check if it's time to create the meeting
                $scheduledAt = Carbon::parse($session->scheduled_at);
                $createAt = $videoSettings->getMeetingCreationTime($scheduledAt);
                
                // Should create meeting if current time is past the creation time
                return now()->gte($createAt);
            })
            ->filter(function ($session) use ($videoSettings) {
                // Check time and day restrictions
                $scheduledAt = Carbon::parse($session->scheduled_at);
                
                return $videoSettings->isTimeAllowed($scheduledAt) 
                    && !$videoSettings->isDayBlocked($scheduledAt);
            });

        return $sessions;
    }

    /**
     * Create a meeting for a specific session
     */
    private function createMeetingForSession(QuranSession $session, VideoSettings $videoSettings): void
    {
        DB::beginTransaction();
        
        try {
            // Get teacher settings to merge with academy settings
            $teacherSettings = null;
            if ($session->teacher) {
                $teacherSettings = TeacherVideoSettings::forTeacher($session->teacher, $session->academy);
            }

            // Build meeting options
            $meetingOptions = $this->buildMeetingOptions($session, $videoSettings, $teacherSettings);

            // Create the meeting using the session's existing method
            $meetingUrl = $session->generateMeetingLink($meetingOptions);

            // Mark the session as having auto-generated meeting
            $session->update([
                'meeting_auto_generated' => true,
                'meeting_created_at' => now(),
            ]);

            DB::commit();

            Log::info('Successfully created meeting for session', [
                'session_id' => $session->id,
                'meeting_url' => $meetingUrl,
                'room_name' => $session->meeting_room_name
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to create meeting for session', [
                'session_id' => $session->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }

    /**
     * Build meeting options combining academy and teacher settings
     */
    private function buildMeetingOptions(
        QuranSession $session, 
        VideoSettings $videoSettings, 
        ?TeacherVideoSettings $teacherSettings
    ): array {
        $options = [
            'max_participants' => $videoSettings->default_max_participants,
            'recording_enabled' => $videoSettings->enable_recording_by_default,
            'session_type' => $session->session_type ?? 'quran',
            'max_duration' => $session->duration_minutes ?? 120,
        ];

        // Apply teacher preferences if available
        if ($teacherSettings) {
            $meetingConfig = $teacherSettings->getMeetingConfiguration($videoSettings);
            
            // Override academy settings with teacher preferences
            $options = array_merge($options, [
                'max_participants' => $meetingConfig['max_participants'],
                'recording_enabled' => $meetingConfig['recording_enabled'],
                'video_quality' => $meetingConfig['video_quality'],
                'audio_quality' => $meetingConfig['audio_quality'],
                'enable_screen_sharing' => $meetingConfig['enable_screen_sharing'],
                'enable_chat' => $meetingConfig['enable_chat'],
                'mute_on_join' => $meetingConfig['mute_on_join'],
                'theme' => $meetingConfig['theme'],
            ]);
        }

        return $options;
    }

    /**
     * Clean up expired meetings that should be ended
     */
    public function cleanupExpiredMeetings(): array
    {
        $results = [
            'sessions_checked' => 0,
            'meetings_ended' => 0,
            'meetings_failed_to_end' => 0,
            'errors' => [],
        ];

        Log::info('Starting expired meetings cleanup');

        try {
            // Find sessions that should have ended
            $expiredSessions = QuranSession::whereNotNull('meeting_room_name')
                ->where('status', ['scheduled', 'ongoing'])
                ->whereNotNull('scheduled_at')
                ->with('academy')
                ->get()
                ->filter(function ($session) {
                    $videoSettings = VideoSettings::forAcademy($session->academy);
                    
                    if (!$videoSettings->auto_end_meetings) {
                        return false;
                    }
                    
                    $scheduledEndTime = Carbon::parse($session->scheduled_at)
                        ->addMinutes($session->duration_minutes ?? 60);
                    $actualEndTime = $videoSettings->getMeetingEndTime($scheduledEndTime);
                    
                    return now()->gte($actualEndTime);
                });

            $results['sessions_checked'] = $expiredSessions->count();

            foreach ($expiredSessions as $session) {
                try {
                    $success = $session->endMeeting();
                    
                    if ($success) {
                        $results['meetings_ended']++;
                        Log::info('Ended expired meeting', [
                            'session_id' => $session->id,
                            'room_name' => $session->meeting_room_name
                        ]);
                    } else {
                        $results['meetings_failed_to_end']++;
                        Log::warning('Failed to end expired meeting', [
                            'session_id' => $session->id,
                            'room_name' => $session->meeting_room_name
                        ]);
                    }
                    
                } catch (\Exception $e) {
                    $results['meetings_failed_to_end']++;
                    $results['errors'][] = [
                        'session_id' => $session->id,
                        'error' => $e->getMessage()
                    ];
                    
                    Log::error('Error ending expired meeting', [
                        'session_id' => $session->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

        } catch (\Exception $e) {
            Log::error('Failed to cleanup expired meetings', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $results['errors'][] = [
                'type' => 'cleanup_process_error',
                'error' => $e->getMessage()
            ];
        }

        Log::info('Expired meetings cleanup completed', $results);
        return $results;
    }

    /**
     * Get statistics about auto meeting creation
     */
    public function getStatistics(): array
    {
        return [
            'total_auto_generated_meetings' => QuranSession::where('meeting_auto_generated', true)->count(),
            'active_meetings' => QuranSession::whereNotNull('meeting_room_name')
                ->whereIn('status', ['scheduled', 'ongoing'])
                ->count(),
            'meetings_created_today' => QuranSession::where('meeting_auto_generated', true)
                ->whereDate('meeting_created_at', today())
                ->count(),
            'meetings_created_this_week' => QuranSession::where('meeting_auto_generated', true)
                ->whereBetween('meeting_created_at', [now()->startOfWeek(), now()->endOfWeek()])
                ->count(),
            'academies_with_auto_creation_enabled' => VideoSettings::where('auto_create_meetings', true)->count(),
        ];
    }

    /**
     * Test meeting creation for a specific session (for testing purposes)
     */
    public function testMeetingCreation(QuranSession $session): array
    {
        try {
            $videoSettings = VideoSettings::forAcademy($session->academy);
            
            if (!$videoSettings->shouldAutoCreateMeetings()) {
                throw new \Exception('Auto meeting creation is disabled for this academy');
            }

            $teacherSettings = null;
            if ($session->teacher) {
                $teacherSettings = TeacherVideoSettings::forTeacher($session->teacher, $session->academy);
            }

            // Create the meeting
            $this->createMeetingForSession($session, $videoSettings);

            return [
                'success' => true,
                'message' => 'Test meeting created successfully',
                'session_id' => $session->id,
                'meeting_url' => $session->meeting_link,
                'room_name' => $session->meeting_room_name
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to create test meeting: ' . $e->getMessage(),
                'session_id' => $session->id,
                'error' => $e->getMessage()
            ];
        }
    }
}
