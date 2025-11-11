<?php

namespace App\Console\Commands;

use App\Models\QuranSession;
use App\Models\User;
use App\Services\MeetingDataChannelService;
use Illuminate\Console\Command;

class TestDataChannelCommand extends Command
{
    protected $signature = 'meeting:test-data-channel {session_id} {--command=mute_all_students}';

    protected $description = 'Test the meeting data channel system with a sample command';

    private MeetingDataChannelService $dataChannelService;

    public function __construct(MeetingDataChannelService $dataChannelService)
    {
        parent::__construct();
        $this->dataChannelService = $dataChannelService;
    }

    public function handle(): int
    {
        $sessionId = $this->argument('session_id');
        $command = $this->option('command');

        try {
            // Find the session
            $session = QuranSession::findOrFail($sessionId);
            $this->info("Found session: {$session->title}");

            // Find a teacher user (first user with teacher role)
            $teacher = User::whereHas('roles', function ($query) {
                $query->where('name', 'quran_teacher');
            })->first();

            if (! $teacher) {
                $this->error('No teacher user found');

                return 1;
            }

            $this->info("Using teacher: {$teacher->first_name} {$teacher->last_name}");

            // Test the command based on input
            $this->info("Sending command: {$command}");

            $result = match ($command) {
                'mute_all_students' => $this->dataChannelService->muteAllStudents($session, $teacher),
                'allow_student_microphones' => $this->dataChannelService->allowStudentMicrophones($session, $teacher),
                'clear_all_hand_raises' => $this->dataChannelService->clearAllHandRaises($session, $teacher),
                default => $this->dataChannelService->sendTeacherControlCommand(
                    $session,
                    $teacher,
                    $command,
                    ['test' => true, 'message' => 'Test command from console']
                )
            };

            $this->info('Command sent successfully!');
            $this->line('Message ID: '.$result['message_id']);
            $this->line('Sent at: '.$result['sent_at']);

            $this->table(['Channel', 'Status', 'Details'], [
                [
                    'LiveKit Data Channel',
                    $result['delivery_results']['livekit_data_channel']['status'] ?? 'unknown',
                    json_encode($result['delivery_results']['livekit_data_channel'] ?? []),
                ],
                [
                    'WebSocket',
                    $result['delivery_results']['websocket']['status'] ?? 'unknown',
                    json_encode($result['delivery_results']['websocket'] ?? []),
                ],
                [
                    'Database State',
                    $result['delivery_results']['database_state']['status'] ?? 'unknown',
                    json_encode($result['delivery_results']['database_state'] ?? []),
                ],
                [
                    'Server-Sent Events',
                    $result['delivery_results']['sse']['status'] ?? 'unknown',
                    json_encode($result['delivery_results']['sse'] ?? []),
                ],
            ]);

            // Test state retrieval
            $this->info("\nTesting state retrieval...");
            $state = $this->dataChannelService->getMeetingStateForParticipant($session, $teacher);
            $this->line('Current state commands count: '.count($state['relevant_commands']));

            return 0;
        } catch (\Exception $e) {
            $this->error("Error: {$e->getMessage()}");

            return 1;
        }
    }
}
