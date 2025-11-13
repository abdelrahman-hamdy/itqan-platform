/**
 * WireChat Debug Script
 * Add this to your layout to debug real-time messaging
 */

(function() {
    'use strict';

    const DEBUG_STYLES = {
        success: 'background: #10b981; color: white; padding: 2px 6px; border-radius: 3px;',
        error: 'background: #ef4444; color: white; padding: 2px 6px; border-radius: 3px;',
        info: 'background: #3b82f6; color: white; padding: 2px 6px; border-radius: 3px;',
        warning: 'background: #f59e0b; color: white; padding: 2px 6px; border-radius: 3px;',
        echo: 'background: #8b5cf6; color: white; padding: 2px 6px; border-radius: 3px;',
        livewire: 'background: #ec4899; color: white; padding: 2px 6px; border-radius: 3px;'
    };

    console.log('%c🔍 WireChat Debug Mode Activated', 'font-size: 16px; font-weight: bold; color: #8b5cf6;');
    console.log('%cTimestamp: ' + new Date().toLocaleString(), 'color: #6b7280;');

    // Check if Echo is available
    if (typeof window.Echo === 'undefined') {
        console.error('%c❌ Laravel Echo NOT LOADED', DEBUG_STYLES.error);
        console.error('Make sure Echo is properly imported in your bootstrap.js');
        return;
    } else {
        console.log('%c✅ Laravel Echo Loaded', DEBUG_STYLES.success);
    }

    // Check Echo configuration
    if (window.Echo.connector) {
        console.group('%c📡 Echo Configuration', DEBUG_STYLES.info);
        console.log('Broadcaster:', window.Echo.connector.name);
        console.log('Options:', {
            wsHost: window.Echo.connector.options.wsHost,
            wsPort: window.Echo.connector.options.wsPort,
            wssPort: window.Echo.connector.options.wssPort,
            forceTLS: window.Echo.connector.options.forceTLS,
            scheme: window.Echo.connector.options.scheme,
            key: window.Echo.connector.options.key
        });
        console.groupEnd();
    }

    // Monitor Pusher/Echo connection
    if (window.Echo.connector && window.Echo.connector.pusher) {
        const pusher = window.Echo.connector.pusher;

        console.group('%c🔌 WebSocket Connection Monitor', DEBUG_STYLES.echo);

        pusher.connection.bind('state_change', function(states) {
            const stateColors = {
                'connecting': DEBUG_STYLES.warning,
                'connected': DEBUG_STYLES.success,
                'disconnected': DEBUG_STYLES.error,
                'unavailable': DEBUG_STYLES.error,
                'failed': DEBUG_STYLES.error
            };

            console.log(
                '%c' + states.previous + ' → ' + states.current,
                stateColors[states.current] || DEBUG_STYLES.info
            );

            if (states.current === 'connected') {
                console.log('%c✅ WebSocket Connected!', DEBUG_STYLES.success);
                console.log('Socket ID:', pusher.connection.socket_id);
            } else if (states.current === 'failed') {
                console.error('%c❌ WebSocket Connection Failed', DEBUG_STYLES.error);
                console.error('Check if Reverb is running: ./chat-status.sh');
            }
        });

        pusher.connection.bind('error', function(err) {
            console.error('%c❌ WebSocket Error', DEBUG_STYLES.error);
            console.error(err);
        });

        // Show initial connection state
        console.log('Current State:', pusher.connection.state);
        console.groupEnd();
    }

    // Monitor Private Channel Subscriptions
    const originalPrivate = window.Echo.private;
    window.Echo.private = function(channel) {
        console.group('%c🔐 Private Channel Subscription', DEBUG_STYLES.echo);
        console.log('Channel:', channel);
        console.log('Timestamp:', new Date().toLocaleTimeString());
        console.groupEnd();

        const subscription = originalPrivate.call(window.Echo, channel);

        // Monitor all events on this channel
        const originalListen = subscription.listen;
        subscription.listen = function(event, callback) {
            console.log('%c👂 Listening for event: ' + event + ' on ' + channel, DEBUG_STYLES.echo);

            return originalListen.call(this, event, function(data) {
                console.group('%c📨 Event Received: ' + event, DEBUG_STYLES.success);
                console.log('Channel:', channel);
                console.log('Data:', data);
                console.log('Timestamp:', new Date().toLocaleTimeString());
                console.groupEnd();

                callback(data);
            });
        };

        return subscription;
    };

    // Monitor Livewire Component Lifecycle
    if (typeof window.Livewire !== 'undefined') {
        console.log('%c✅ Livewire Loaded', DEBUG_STYLES.success);

        // Hook into Livewire events
        document.addEventListener('livewire:init', () => {
            console.log('%c🔄 Livewire Initialized', DEBUG_STYLES.livewire);

            Livewire.hook('morph.added', ({ el }) => {
                if (el.classList && (el.classList.contains('message') || el.getAttribute('wire:key')?.includes('message'))) {
                    console.log('%c➕ Message Added to DOM', DEBUG_STYLES.success, el);
                }
            });

            Livewire.hook('message.sent', (message, component) => {
                if (component.name?.includes('chat')) {
                    console.group('%c📤 Livewire Request Sent', DEBUG_STYLES.livewire);
                    console.log('Component:', component.name);
                    console.log('Method:', message.updateQueue?.[0]?.method || 'unknown');
                    console.log('Payload:', message.updateQueue?.[0]?.payload || {});
                    console.log('Timestamp:', new Date().toLocaleTimeString());
                    console.groupEnd();
                }
            });

            Livewire.hook('message.received', (message, component) => {
                if (component.name?.includes('chat')) {
                    console.group('%c📥 Livewire Response Received', DEBUG_STYLES.livewire);
                    console.log('Component:', component.name);
                    console.log('Response:', message.response);
                    console.log('Timestamp:', new Date().toLocaleTimeString());
                    console.groupEnd();
                }
            });

            Livewire.hook('commit', ({ component, commit, respond }) => {
                if (component.name?.includes('chat')) {
                    console.log('%c💾 Livewire Commit', DEBUG_STYLES.livewire, {
                        component: component.name,
                        snapshot: commit.snapshot
                    });
                }
            });
        });
    } else {
        console.warn('%c⚠️ Livewire NOT LOADED', DEBUG_STYLES.warning);
    }

    // Monitor AJAX Requests to chat endpoints
    const originalFetch = window.fetch;
    window.fetch = function(...args) {
        const url = args[0];
        if (typeof url === 'string' && (url.includes('/chats') || url.includes('/livewire'))) {
            console.group('%c🌐 HTTP Request', DEBUG_STYLES.info);
            console.log('URL:', url);
            console.log('Method:', args[1]?.method || 'GET');
            console.log('Timestamp:', new Date().toLocaleTimeString());
            console.groupEnd();
        }
        return originalFetch.apply(this, args);
    };

    // Monitor broadcasting authentication
    const originalXHR = window.XMLHttpRequest;
    window.XMLHttpRequest = function() {
        const xhr = new originalXHR();
        const originalOpen = xhr.open;

        xhr.open = function(method, url) {
            if (url.includes('/broadcasting/auth')) {
                console.group('%c🔑 Broadcasting Auth Request', DEBUG_STYLES.warning);
                console.log('URL:', url);
                console.log('Method:', method);

                xhr.addEventListener('load', function() {
                    if (xhr.status === 200) {
                        console.log('%c✅ Auth Success', DEBUG_STYLES.success);
                        try {
                            const response = JSON.parse(xhr.responseText);
                            console.log('Auth Response:', response);
                        } catch (e) {
                            console.log('Response:', xhr.responseText);
                        }
                    } else {
                        console.error('%c❌ Auth Failed: ' + xhr.status, DEBUG_STYLES.error);
                        console.error('Response:', xhr.responseText);
                    }
                    console.groupEnd();
                });

                xhr.addEventListener('error', function() {
                    console.error('%c❌ Auth Error', DEBUG_STYLES.error);
                    console.groupEnd();
                });
            }
            return originalOpen.apply(this, arguments);
        };

        return xhr;
    };

    // Expose debug helper functions
    window.wirechatDebug = {
        echoStatus: function() {
            if (!window.Echo?.connector?.pusher) {
                console.log('Echo not initialized');
                return;
            }

            const pusher = window.Echo.connector.pusher;
            console.group('🔍 Echo Status');
            console.log('Connection State:', pusher.connection.state);
            console.log('Socket ID:', pusher.connection.socket_id);
            console.log('Channels:', Object.keys(pusher.channels.channels));
            console.groupEnd();
        },

        testBroadcast: function(conversationId) {
            if (!conversationId) {
                console.error('Usage: wirechatDebug.testBroadcast(conversationId)');
                return;
            }

            console.group('🧪 Testing Broadcast Reception');
            console.log('Subscribing to conversation.' + conversationId);

            window.Echo.private('conversation.' + conversationId)
                .listen('.Namu\\WireChat\\Events\\MessageCreated', (e) => {
                    console.log('%c✅ Test Message Received!', DEBUG_STYLES.success);
                    console.log('Event Data:', e);
                });

            console.log('Listening for MessageCreated events...');
            console.log('Send a message to see if it appears here');
            console.groupEnd();
        },

        listChannels: function() {
            if (!window.Echo?.connector?.pusher) {
                console.log('Echo not initialized');
                return;
            }

            const channels = window.Echo.connector.pusher.channels.channels;
            console.group('📡 Active Channels');
            Object.keys(channels).forEach(channelName => {
                console.log('Channel:', channelName);
                console.log('  Subscribed:', channels[channelName].subscribed);
                console.log('  Events:', channels[channelName].callbacks._callbacks || {});
            });
            console.groupEnd();
        },

        help: function() {
            console.log('%c📚 WireChat Debug Helper Commands', 'font-size: 14px; font-weight: bold;');
            console.log('wirechatDebug.echoStatus() - Show Echo connection status');
            console.log('wirechatDebug.testBroadcast(conversationId) - Test broadcast reception');
            console.log('wirechatDebug.listChannels() - List all active channels');
            console.log('wirechatDebug.help() - Show this help');
        }
    };

    console.log('%c💡 Debug helpers available: wirechatDebug.help()', DEBUG_STYLES.info);

    // Show periodic connection status
    setInterval(() => {
        if (window.Echo?.connector?.pusher) {
            const state = window.Echo.connector.pusher.connection.state;
            if (state !== 'connected') {
                console.warn('%c⚠️ WebSocket not connected: ' + state, DEBUG_STYLES.warning);
            }
        }
    }, 30000); // Check every 30 seconds

})();
