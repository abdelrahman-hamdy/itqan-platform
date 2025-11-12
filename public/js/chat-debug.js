/**
 * Enhanced Chat Debug Logger
 * Add this to your chat page to see detailed console logs
 */

(function() {
    'use strict';

    console.log('%c🔍 CHAT DEBUG MODE ENABLED', 'background: #4CAF50; color: white; padding: 5px 10px; font-weight: bold; font-size: 14px;');
    console.log('All chat events will be logged with detailed information');
    console.log('───────────────────────────────────────────────────────────────');

    // Monitor Echo connection
    if (window.Echo && window.Echo.connector && window.Echo.connector.pusher) {
        const pusher = window.Echo.connector.pusher;
        const connection = pusher.connection;

        // Log connection state changes
        connection.bind('state_change', function(states) {
            console.log(`%c[CONNECTION] State: ${states.previous} → ${states.current}`,
                'color: #2196F3; font-weight: bold',
                {
                    previous: states.previous,
                    current: states.current,
                    timestamp: new Date().toLocaleTimeString()
                }
            );
        });

        // Log connection established
        connection.bind('connected', function() {
            console.log('%c[CONNECTION] ✅ Connected to Reverb',
                'color: #4CAF50; font-weight: bold',
                {
                    socketId: pusher.connection.socket_id,
                    timestamp: new Date().toLocaleTimeString()
                }
            );
        });

        // Log connection errors
        connection.bind('error', function(error) {
            console.error('%c[CONNECTION] ❌ Error',
                'color: #f44336; font-weight: bold',
                error
            );
        });

        // Log disconnection
        connection.bind('disconnected', function() {
            console.warn('%c[CONNECTION] ⚠️  Disconnected from Reverb',
                'color: #FF9800; font-weight: bold',
                {
                    timestamp: new Date().toLocaleTimeString()
                }
            );
        });

        console.log('%c[CONNECTION] Current State: ' + connection.state,
            'color: #2196F3; font-weight: bold'
        );
    }

    // Intercept Echo channel subscriptions
    if (window.Echo) {
        const originalPrivate = window.Echo.private.bind(window.Echo);

        window.Echo.private = function(channel) {
            console.log(`%c[CHANNEL] 📡 Subscribing to: ${channel}`,
                'color: #9C27B0; font-weight: bold',
                {
                    channel: `private-${channel}`,
                    timestamp: new Date().toLocaleTimeString()
                }
            );

            const channelInstance = originalPrivate(channel);

            // Wrap the listen method to log events
            const originalListen = channelInstance.listen.bind(channelInstance);
            channelInstance.listen = function(event, callback) {
                console.log(`%c[CHANNEL] 👂 Listening for event: ${event}`,
                    'color: #9C27B0; font-weight: bold',
                    {
                        channel: `private-${channel}`,
                        event: event,
                        timestamp: new Date().toLocaleTimeString()
                    }
                );

                return originalListen(event, function(data) {
                    console.log(`%c[EVENT] 📨 Received: ${event}`,
                        'color: #4CAF50; font-weight: bold; font-size: 12px',
                        {
                            channel: `private-${channel}`,
                            event: event,
                            data: data,
                            timestamp: new Date().toLocaleTimeString()
                        }
                    );

                    // Pretty print the data
                    console.log('%cEvent Data:', 'color: #666; font-weight: bold');
                    console.table(data);

                    return callback(data);
                });
            };

            // Log subscription success
            channelInstance.subscribed(function() {
                console.log(`%c[CHANNEL] ✅ Subscribed: private-${channel}`,
                    'color: #4CAF50; font-weight: bold',
                    {
                        timestamp: new Date().toLocaleTimeString()
                    }
                );
            });

            // Log subscription errors
            channelInstance.error(function(error) {
                console.error(`%c[CHANNEL] ❌ Subscription Error: private-${channel}`,
                    'color: #f44336; font-weight: bold',
                    {
                        error: error,
                        timestamp: new Date().toLocaleTimeString()
                    }
                );
            });

            return channelInstance;
        };

        console.log('%c[DEBUG] Echo interception enabled', 'color: #4CAF50; font-weight: bold');
    }

    // Intercept fetch requests to log AJAX calls
    const originalFetch = window.fetch;
    window.fetch = function(...args) {
        const url = args[0];
        const options = args[1] || {};

        // Only log chat-related requests
        if (url.includes('/chat/') || url.includes('sendMessage')) {
            console.log(`%c[AJAX] 📤 Request: ${url}`,
                'color: #FF5722; font-weight: bold',
                {
                    method: options.method || 'GET',
                    url: url,
                    timestamp: new Date().toLocaleTimeString()
                }
            );

            return originalFetch.apply(this, args).then(response => {
                console.log(`%c[AJAX] 📥 Response: ${url}`,
                    response.ok ? 'color: #4CAF50; font-weight: bold' : 'color: #f44336; font-weight: bold',
                    {
                        status: response.status,
                        statusText: response.statusText,
                        timestamp: new Date().toLocaleTimeString()
                    }
                );
                return response;
            });
        }

        return originalFetch.apply(this, args);
    };

    console.log('%c[DEBUG] Fetch interception enabled', 'color: #4CAF50; font-weight: bold');

    // Add a test function to send a broadcast manually
    window.testBroadcast = function(userId) {
        console.log('%c[TEST] 🧪 Sending test broadcast to user: ' + userId, 'color: #FF9800; font-weight: bold; font-size: 14px');
        console.log('Check the Laravel logs to see if the broadcast is sent');
        console.log('Monitor with: tail -f storage/logs/laravel.log | grep BROADCAST');
    };

    console.log('%c💡 TIP: Use window.testBroadcast(userId) to test broadcasts', 'color: #2196F3; font-style: italic');
    console.log('───────────────────────────────────────────────────────────────');

})();
