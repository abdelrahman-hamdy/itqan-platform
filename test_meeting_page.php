<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Simulate authenticated user for session 12
$session = App\Models\QuranSession::find(12);
$user = App\Models\User::find(3); // Teacher user

if (! $session || ! $user) {
    exit('Session or user not found');
}

// Login the user
auth()->login($user);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo csrf_token(); ?>">
    <title>Test Meeting - Session <?php echo $session->id; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto bg-white rounded-lg shadow-lg p-6">
        <h1 class="text-2xl font-bold mb-4">Test Meeting - Session <?php echo $session->id; ?></h1>
        
        <div class="mb-4">
            <p><strong>Session:</strong> <?php echo $session->id; ?> (<?php echo $session->session_type; ?>)</p>
            <p><strong>User:</strong> <?php echo $user->name; ?> (<?php echo $user->user_type; ?>)</p>
            <p><strong>Status:</strong> <?php echo $session->status->value; ?></p>
            <p><strong>Meeting Room:</strong> <?php echo $session->meeting_room_name; ?></p>
            <p><strong>Meeting ID:</strong> <?php echo $session->meeting_id; ?></p>
        </div>
        
        <div class="mb-4">
            <button id="testTokenBtn" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                Test Token Generation
            </button>
            <button id="joinMeetingBtn" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600 ml-2">
                Join Meeting
            </button>
        </div>
        
        <div id="results" class="bg-gray-50 p-4 rounded border"></div>
        
        <!-- Meeting container -->
        <div id="meetingContainer" class="hidden mt-6">
            <div id="videoContainer" class="bg-black rounded-lg h-96 mb-4"></div>
            <div class="flex gap-2">
                <button id="toggleAudio" class="bg-red-500 text-white px-4 py-2 rounded">Mute</button>
                <button id="toggleVideo" class="bg-red-500 text-white px-4 py-2 rounded">Stop Video</button>
                <button id="leaveMeeting" class="bg-gray-500 text-white px-4 py-2 rounded">Leave</button>
            </div>
        </div>
    </div>

    <script>
        // Configuration
        window.sessionId = <?php echo $session->id; ?>;
        window.sessionType = 'quran';
        
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const resultsDiv = document.getElementById('results');
        
        function log(message, type = 'info') {
            const timestamp = new Date().toLocaleTimeString();
            const color = type === 'error' ? 'text-red-600' : type === 'success' ? 'text-green-600' : 'text-blue-600';
            resultsDiv.innerHTML += `<div class="${color}">[${timestamp}] ${message}</div>`;
            resultsDiv.scrollTop = resultsDiv.scrollHeight;
        }
        
        // Test token generation
        document.getElementById('testTokenBtn').addEventListener('click', async () => {
            log('Testing token generation...');
            
            try {
                const response = await fetch('/api/sessions/meeting/token', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        session_type: window.sessionType,
                        session_id: window.sessionId
                    })
                });
                
                if (!response.ok) {
                    const errorText = await response.text();
                    throw new Error(`HTTP ${response.status}: ${errorText}`);
                }
                
                const data = await response.json();
                log('✅ Token generated successfully!', 'success');
                log(`Server URL: ${data.server_url}`);
                log(`Room Name: ${data.room_name}`);
                log(`Token Length: ${data.token.length}`);
                
                // Store token for meeting join
                window.meetingToken = data.token;
                window.serverUrl = data.server_url;
                window.roomName = data.room_name;
                
            } catch (error) {
                log(`❌ Error: ${error.message}`, 'error');
            }
        });
        
        // Join meeting (simplified test)
        document.getElementById('joinMeetingBtn').addEventListener('click', async () => {
            if (!window.meetingToken) {
                log('❌ Please generate token first', 'error');
                return;
            }
            
            log('🚀 Joining meeting...');
            log(`Connecting to: ${window.serverUrl}`);
            log(`Room: ${window.roomName}`);
            
            // Show meeting container
            document.getElementById('meetingContainer').classList.remove('hidden');
            log('✅ Meeting interface loaded!', 'success');
            log('📹 Video container ready for LiveKit connection');
        });
        
        // Initialize
        log('🔧 Test page loaded');
        log('📋 Ready to test meeting functionality');
    </script>
</body>
</html>
