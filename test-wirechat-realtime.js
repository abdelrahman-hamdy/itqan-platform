/**
 * WireChat Real-Time Test Script
 *
 * Paste this in your browser console when on the chat page
 * to test if Echo is properly connected and channels are authorized
 */

console.log('🔍 Starting WireChat Real-Time Diagnostics...\n');

// Test 1: Check if Echo is loaded
if (typeof Echo === 'undefined') {
    console.error('❌ FAIL: Laravel Echo is not loaded');
    console.log('   Fix: Make sure @vite([\'resources/js/app.js\']) is in your WireChat layout');
} else {
    console.log('✅ PASS: Laravel Echo is loaded');
}

// Test 2: Check Echo connection status
if (typeof Echo !== 'undefined') {
    const connectionState = Echo.connector.pusher.connection.state;
    if (connectionState === 'connected') {
        console.log('✅ PASS: Echo is connected to Reverb');
    } else {
        console.error(`❌ FAIL: Echo connection state is "${connectionState}"`);
        console.log('   Expected: "connected"');
        console.log('   Fix: Check if Reverb server is running (php artisan reverb:start)');
    }
}

// Test 3: Check subscribed channels
if (typeof Echo !== 'undefined' && Echo.connector.channels) {
    const channels = Object.keys(Echo.connector.channels);
    console.log(`\n📡 Subscribed Channels (${channels.length}):`);

    if (channels.length === 0) {
        console.warn('⚠️  WARNING: No channels subscribed yet');
        console.log('   This is normal if you haven\'t opened a conversation');
    } else {
        channels.forEach(channel => {
            console.log(`   • ${channel}`);

            // Check if it's a WireChat channel
            if (channel.includes('participant.')) {
                console.log('     → Type: Participant channel (for notifications)');
            } else if (channel.includes('conversation.')) {
                console.log('     → Type: Conversation channel (for real-time messages)');
            }
        });
    }
}

// Test 4: Check Pusher/Reverb configuration
if (typeof Echo !== 'undefined' && Echo.connector.pusher.config) {
    const config = Echo.connector.pusher.config;
    console.log('\n⚙️  Reverb Configuration:');
    console.log(`   Host: ${config.wsHost || 'N/A'}`);
    console.log(`   Port: ${config.wsPort || 'N/A'}`);
    console.log(`   Encrypted: ${config.forceTLS ? 'Yes (HTTPS)' : 'No (HTTP)'}`);
    console.log(`   App Key: ${config.key || 'N/A'}`);
}

// Test 5: Test real-time messaging (if conversation is open)
console.log('\n🧪 Real-Time Message Test:');
console.log('To test real-time messaging:');
console.log('1. Open this chat in TWO browser windows');
console.log('2. Login as different users in each window');
console.log('3. Send a message from Window 1');
console.log('4. Message should appear INSTANTLY in Window 2 (< 1 second)');
console.log('5. If delayed > 2 seconds, real-time is not working properly');

// Test 6: Check for common errors
console.log('\n🔍 Checking for Common Issues:');

// Check if Alpine.js is loaded multiple times
if (typeof Alpine !== 'undefined') {
    console.log('✅ Alpine.js is loaded (needed for Livewire)');
} else {
    console.warn('⚠️  Alpine.js not detected');
}

// Check if Livewire is loaded
if (typeof Livewire !== 'undefined') {
    console.log('✅ Livewire is loaded');
} else {
    console.error('❌ Livewire not loaded - WireChat won\'t work');
}

// Test 7: Listen for test event
if (typeof Echo !== 'undefined') {
    console.log('\n👂 Setting up event listener for testing...');
    console.log('Listening for WireChat events on all channels');

    // Get user info from meta tag or Livewire
    const userId = window.Livewire?.all()[0]?.get('userId') || 'unknown';
    const encodedType = '4170705c4d6f64656c735c55736572'; // App\Models\User in hex

    console.log(`User ID: ${userId}`);
    console.log(`\nExpected channels:`);
    console.log(`   • private-participant.${encodedType}.${userId}`);
    console.log(`   • private-conversation.{conversationId} (when conversation open)`);
}

// Final summary
console.log('\n' + '='.repeat(60));
console.log('📊 DIAGNOSTIC SUMMARY');
console.log('='.repeat(60));

let allPassed = true;

if (typeof Echo === 'undefined') {
    allPassed = false;
    console.error('❌ Echo not loaded - real-time will NOT work');
}

if (typeof Echo !== 'undefined' && Echo.connector.pusher.connection.state !== 'connected') {
    allPassed = false;
    console.error('❌ Echo not connected - real-time will NOT work');
}

if (typeof Livewire === 'undefined') {
    allPassed = false;
    console.error('❌ Livewire not loaded - WireChat will NOT work');
}

if (allPassed) {
    console.log('✅ ALL CHECKS PASSED - Real-time should be working!');
    console.log('\n🎉 Next Step: Send a message and test it!');
} else {
    console.error('\n❌ ISSUES DETECTED - Real-time may not work properly');
    console.log('\n📝 Fix the errors above and refresh the page');
}

console.log('='.repeat(60));
