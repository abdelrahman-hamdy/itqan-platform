/**
 * WireChat Real-Time Integration (Updated for WireChat)
 *
 * This script subscribes to WireChat conversation channels and handles MessageCreated events
 */

(function() {
    'use strict';

    console.log('%c🔗 WireChat Real-Time Bridge (v2)', 'background: #a855f7; color: white; padding: 5px 10px; font-weight: bold;');

    // Track if already initialized
    let isInitialized = false;

    // Track subscribed conversations to avoid duplicates
    const subscribedConversations = new Set();

    // Check if both Echo and Livewire are ready
    function isReady() {
        return typeof window.Echo !== 'undefined' && typeof window.Livewire !== 'undefined';
    }

    // Initialize with multiple Livewire version support
    function tryInitialize() {
        if (isInitialized) {
            console.log('⏭️  Already initialized, skipping...');
            return true;
        }

        // Check if Echo is loaded
        if (typeof window.Echo === 'undefined') {
            console.warn('⚠️  Laravel Echo not loaded yet...');
            return false;
        }

        // Check if Livewire is loaded
        if (typeof window.Livewire === 'undefined') {
            console.warn('⚠️  Livewire not loaded yet...');
            return false;
        }

        console.log('✅ Echo and Livewire detected. Initializing...');
        isInitialized = true;
        initializeWireChatBridge();
        return true;
    }

    // Try immediate initialization
    if (!tryInitialize()) {
        console.warn('⚠️  Waiting for Echo and Livewire...');

        // Try multiple event names for different Livewire versions
        const events = ['livewire:init', 'livewire:load', 'livewire:navigated', 'DOMContentLoaded'];

        events.forEach(eventName => {
            document.addEventListener(eventName, function() {
                if (!isInitialized) {
                    console.log(`📡 Received ${eventName} event`);
                    tryInitialize();
                }
            });
        });

        // Fallback: poll for both Echo and Livewire
        let attempts = 0;
        const maxAttempts = 100; // 10 seconds
        const checkInterval = setInterval(() => {
            attempts++;
            if (tryInitialize()) {
                clearInterval(checkInterval);
                console.log('✅ Echo and Livewire loaded via polling');
            } else if (attempts >= maxAttempts) {
                clearInterval(checkInterval);
                console.error('❌ Echo or Livewire failed to load after 10 seconds');
                console.error('Echo loaded:', typeof window.Echo !== 'undefined');
                console.error('Livewire loaded:', typeof window.Livewire !== 'undefined');
            }
        }, 100);
    }

    function initializeWireChatBridge() {
        console.log('🚀 Initializing WireChat bridge...');

        // Get current user ID from meta tag
        const userId = document.querySelector('meta[name="user-id"]')?.content;

        if (!userId) {
            console.warn('⚠️  User ID not found. Real-time updates may not work.');
            return;
        }

        console.log(`👤 Current User ID: ${userId}`);

        // Subscribe to user's conversations
        subscribeToUserConversations(userId);

        // Initialize presence after a short delay
        setTimeout(() => {
            subscribeToPresenceChannel();
        }, 1000);

        console.log('✅ WireChat bridge initialized');

        // GLOBAL DEBUGGING: Log ALL Echo events
        console.log('%c🌍 GLOBAL DEBUGGING ENABLED', 'background: red; color: white; padding: 5px; font-size: 14px');
        console.log('%c📢 Will log ALL broadcasts received on ANY channel', 'color: orange; font-weight: bold');

        if (window.Echo.connector && window.Echo.connector.pusher) {
            window.Echo.connector.pusher.connection.bind_global(function(eventName, data) {
                console.log('%c🌐 GLOBAL EVENT', 'background: purple; color: white; padding: 3px');
                console.log('Event Name:', eventName);
                console.log('Data:', data);
            });
        }
    }

    /**
     * Subscribe to all conversations for the current user
     */
    function subscribeToUserConversations(userId) {
        // Debug URL parsing
        console.log('🔍 Current URL:', window.location.href);
        console.log('🔍 Pathname:', window.location.pathname);

        // Check if we're on a chat page with a conversation ID
        const currentConversationId = window.location.pathname.match(/\/chat\/(\d+)/)?.[1];

        console.log('🔍 Extracted conversation ID:', currentConversationId || 'none');

        if (currentConversationId) {
            console.log(`📡 Found current conversation: ${currentConversationId}`);
            subscribeToConversation(currentConversationId);
        } else {
            console.warn('⚠️  No conversation ID found in URL');
        }

        // Listen for when new conversations are loaded (Livewire navigation)
        if (window.Livewire) {
            try {
                // Livewire v3
                if (window.Livewire.on) {
                    window.Livewire.on('conversation-loaded', (data) => {
                        if (data && data.conversationId) {
                            console.log(`📡 New conversation loaded: ${data.conversationId}`);
                            subscribeToConversation(data.conversationId);
                        }
                    });
                }
            } catch (e) {
                console.warn('⚠️  Could not set up Livewire listeners:', e.message);
            }
        }

        // Watch for URL changes (SPA navigation)
        const observer = new MutationObserver(() => {
            const conversationId = window.location.pathname.match(/\/chat\/(\d+)/)?.[1];
            if (conversationId && !subscribedConversations.has(conversationId)) {
                console.log(`📡 URL changed, subscribing to conversation: ${conversationId}`);
                subscribeToConversation(conversationId);
            }
        });

        observer.observe(document.body, { childList: true, subtree: true });
    }

    /**
     * Subscribe to a specific conversation channel
     */
    function subscribeToConversation(conversationId) {
        // Avoid duplicate subscriptions
        if (subscribedConversations.has(conversationId)) {
            console.log(`⏭️  Already subscribed to conversation ${conversationId}`);
            return;
        }

        subscribedConversations.add(conversationId);

        const channelName = `conversation.${conversationId}`;
        console.log(`📡 Subscribing to: private-${channelName}`);

        const channel = window.Echo.private(channelName);

        channel.subscribed(() => {
            console.log(`%c✅ Subscribed to private-${channelName}`, 'color: #4CAF50; font-weight: bold');
        });

        // Use direct .on() binding with exact event name from broadcast
        channel.on('.Namu\\WireChat\\Events\\MessageCreated', (e) => {
            console.log('%c📨 MessageCreated received via .on()!', 'color: #00FF00; font-weight: bold; font-size: 16px', e);
            handleMessageEvent(e, conversationId);
        });

        // Also try all variations for compatibility
        channel.listen('.Namu\\WireChat\\Events\\MessageCreated', (e) => {
            console.log('%c📨 MessageCreated via .listen() with dot!', 'color: #4CAF50; font-weight: bold', e);
            handleMessageEvent(e, conversationId);
        })
        .listen('Namu\\WireChat\\Events\\MessageCreated', (e) => {
            console.log('%c📨 MessageCreated via .listen() without dot!', 'color: #4CAF50; font-weight: bold', e);
            handleMessageEvent(e, conversationId);
        })
        .listen('.MessageCreated', (e) => {
            console.log('%c📨 MessageCreated simple!', 'color: #4CAF50; font-weight: bold', e);
            handleMessageEvent(e, conversationId);
        })
        .listen('MessageCreated', (e) => {
            console.log('%c📨 MessageCreated simple no dot!', 'color: #4CAF50; font-weight: bold', e);
            handleMessageEvent(e, conversationId);
        })
        // Listen for typing indicators
        .listenForWhisper('typing', (e) => {
            console.log('👀 Someone is typing...', e);
            showTypingIndicator(e);
        })
        // Listen for message read events
        .listen('.message.read', (e) => {
            console.log('📖 Message read event:', e);
            handleMessageReadEvent(e);
        })
        .error((error) => {
            console.error('%c❌ Channel subscription error', 'color: #f44336; font-weight: bold', error);
        });

        // Debug: Log channel information
        console.log('🔍 Channel object:', channel);
        console.log('🔍 Channel name:', channel.name);

        // CATCH-ALL: Listen to ALL events on this channel for debugging
        channel.on('pusher:subscription_succeeded', function(data) {
            console.log('%c🎉 SUBSCRIPTION SUCCEEDED!', 'color: #00FF00; font-weight: bold; font-size: 16px', data);
            console.log('%c👂 Now listening for ALL events on this channel...', 'color: #FFA500; font-weight: bold');
        });

        // Listen to raw Pusher events
        if (window.Echo.connector && window.Echo.connector.pusher) {
            window.Echo.connector.pusher.connection.bind('message', function(event) {
                if (event.channel === `private-${channelName}`) {
                    console.log('%c🔔 RAW EVENT RECEIVED', 'color: #FF00FF; font-weight: bold; font-size: 14px');
                    console.log('Channel:', event.channel);
                    console.log('Event:', event.event);
                    console.log('Data:', event.data);
                }
            });
        }
    }

    /**
     * Handle incoming MessageCreated events
     */
    function handleMessageEvent(data, conversationId) {
        console.log(`🎯 Handling MessageCreated event for conversation ${conversationId}:`, data);

        // Extract message info
        const messageData = data.message || data;
        const messageId = messageData.id;
        const messageConversationId = messageData.conversation_id;

        console.log('📋 Message info:', {
            messageId,
            messageConversationId
        });

        // Refresh WireChat component
        refreshWireChat(messageConversationId);

        // Play notification sound
        playNotificationSound();

        // Show browser notification if permitted
        if (Notification.permission === 'granted') {
            showBrowserNotification(messageData);
        }
    }

    /**
     * Refresh WireChat Livewire component
     */
    function refreshWireChat(conversationId) {
        console.log('🔄 Refreshing WireChat component for conversation:', conversationId);

        if (!window.Livewire) {
            console.warn('⚠️  Livewire not available. Will rely on page refresh...');
            return;
        }

        try {
            let refreshed = false;

            // Method 1: Dispatch Livewire global event
            if (window.Livewire.dispatch) {
                window.Livewire.dispatch('message-received', {
                    conversationId: conversationId
                });
                console.log('✅ Livewire event dispatched: message-received');
                refreshed = true;
            }

            // Method 2: Emit event to all Livewire components (Livewire v3)
            if (window.Livewire.emit) {
                window.Livewire.emit('message-received', {
                    conversationId: conversationId
                });
                console.log('✅ Livewire event emitted: message-received');
                refreshed = true;
            }

            // Method 3: Try to refresh specific chat components
            setTimeout(() => {
                if (window.Livewire.all) {
                    const components = window.Livewire.all();
                    let componentRefreshed = false;

                    components.forEach(component => {
                        // Check if it's a WireChat component
                        const componentName = component.fingerprint?.name || component.name || component.__name || '';

                        // Only refresh chat-related components
                        if (componentName.toLowerCase().includes('wirechat') ||
                            componentName.toLowerCase().includes('chat.chat') ||
                            componentName.toLowerCase().includes('chats.chats')) {

                            try {
                                // Try multiple refresh methods
                                if (component.$wire?.$refresh) {
                                    component.$wire.$refresh();
                                    console.log(`✅ Refreshed component via $wire.$refresh: ${componentName}`);
                                    componentRefreshed = true;
                                } else if (component.call) {
                                    component.call('$refresh');
                                    console.log(`✅ Refreshed component via call: ${componentName}`);
                                    componentRefreshed = true;
                                } else if (typeof component.$wire === 'object' && component.$wire.call) {
                                    component.$wire.call('$refresh');
                                    console.log(`✅ Refreshed component via $wire.call: ${componentName}`);
                                    componentRefreshed = true;
                                }
                            } catch (e) {
                                console.warn(`⚠️  Could not refresh ${componentName}:`, e.message);
                            }
                        }
                    });

                    if (componentRefreshed) {
                        refreshed = true;
                    }
                }
            }, 100);

            // Method 4: Trigger custom event on window (some components may listen to this)
            setTimeout(() => {
                window.dispatchEvent(new CustomEvent('wirechat-message-received', {
                    detail: { conversationId: conversationId }
                }));
                console.log('✅ Custom event dispatched: wirechat-message-received');
            }, 150);

            if (!refreshed) {
                console.warn('⚠️  Could not trigger any refresh method');
            }
        } catch (error) {
            console.error('❌ Error refreshing WireChat:', error);
        }
    }

    /**
     * Play notification sound
     */
    function playNotificationSound() {
        try {
            const audio = new Audio('/sounds/chat/notification.mp3');
            audio.volume = 0.5;
            audio.play().catch(e => {
                console.log('🔇 Could not play sound:', e.message);
            });
        } catch (error) {
            console.log('🔇 Notification sound not available');
        }
    }

    /**
     * Show browser notification
     */
    function showBrowserNotification(data) {
        try {
            const title = '💬 New Message';
            const options = {
                body: data.body || 'You have a new message',
                icon: '/images/logo.png',
                badge: '/images/badge.png',
                tag: 'wirechat-message',
                requireInteraction: false
            };

            new Notification(title, options);
            console.log('🔔 Browser notification shown');
        } catch (error) {
            console.log('🔕 Could not show notification:', error.message);
        }
    }

    // Request notification permission on load
    if (window.Notification && Notification.permission === 'default') {
        Notification.requestPermission().then(permission => {
            console.log(`🔔 Notification permission: ${permission}`);
        });
    }

    /**
     * Subscribe to presence channel for online status
     */
    function subscribeToPresenceChannel() {
        try {
            const userId = document.querySelector('meta[name="user-id"]')?.content;
            const academyId = document.querySelector('meta[name="academy-id"]')?.content;

            if (!userId) return;

            // Subscribe to academy-specific presence channel (multi-tenancy)
            const presenceChannel = academyId ? `online.academy.${academyId}` : 'online';

            console.log(`👥 Subscribing to presence channel: ${presenceChannel}`);

            // Use private channel for Reverb compatibility (join() is for Pusher presence channels)
            window.Echo.private(presenceChannel)
                .subscribed(() => {
                    console.log(`✅ Subscribed to presence channel: ${presenceChannel}`);
                })
                .listen('.user.online', (data) => {
                    console.log(`✅ User online:`, data);
                    if (data.user) {
                        markUserOnline(data.user.id);
                    }
                })
                .listen('.user.offline', (data) => {
                    console.log(`❌ User offline:`, data);
                    if (data.user) {
                        markUserOffline(data.user.id);
                    }
                })
                .error((error) => {
                    console.warn('⚠️  Presence channel error (non-critical):', error);
                });
        } catch (error) {
            console.warn('⚠️  Failed to subscribe to presence channel (non-critical):', error);
        }
    }

    /**
     * Update online users list
     */
    function updateOnlineUsers(users) {
        const onlineUserIds = users.map(u => u.id);

        // Update all user elements with online/offline status
        document.querySelectorAll('[data-user-id]').forEach(element => {
            const userId = parseInt(element.dataset.userId);
            if (onlineUserIds.includes(userId)) {
                markUserOnline(userId);
            } else {
                markUserOffline(userId);
            }
        });
    }

    /**
     * Mark user as online
     */
    function markUserOnline(userId) {
        // Find all elements for this user
        document.querySelectorAll(`[data-user-id="${userId}"]`).forEach(element => {
            // Add online indicator
            let indicator = element.querySelector('.status-indicator');
            if (!indicator) {
                indicator = document.createElement('span');
                indicator.className = 'status-indicator';
                element.appendChild(indicator);
            }
            indicator.classList.remove('offline');
            indicator.classList.add('online');
            indicator.title = 'Online';
        });
    }

    /**
     * Mark user as offline
     */
    function markUserOffline(userId) {
        document.querySelectorAll(`[data-user-id="${userId}"]`).forEach(element => {
            const indicator = element.querySelector('.status-indicator');
            if (indicator) {
                indicator.classList.remove('online');
                indicator.classList.add('offline');
                indicator.title = 'Offline';
            }
        });
    }

    /**
     * Show typing indicator
     */
    function showTypingIndicator(data) {
        try {
            if (window.Livewire && window.Livewire.dispatch) {
                window.Livewire.dispatch('user-typing', data);
            }
        } catch (error) {
            console.log('Could not show typing indicator:', error);
        }
    }

    /**
     * Handle message read events
     */
    function handleMessageReadEvent(data) {
        try {
            console.log('📖 Processing message read event:', data);
            if (window.Livewire && window.Livewire.dispatch) {
                window.Livewire.dispatch('message-read', data);
            }
        } catch (error) {
            console.log('Could not handle message read event:', error);
        }
    }

    /**
     * Debug: Log all Livewire components
     */
    function debugLivewireComponents() {
        if (!window.Livewire) {
            console.log('❌ Livewire not available');
            return;
        }

        try {
            if (window.Livewire.all) {
                const components = window.Livewire.all();
                console.log('📦 Found Livewire components:', components.length);
                components.forEach(component => {
                    const componentName = component.fingerprint?.name || component.name || component.__name || 'unknown';
                    console.log(`💬 Chat component: ${componentName} (ID: ${component.id})`);
                });
            }
        } catch (error) {
            console.warn('⚠️  Could not list Livewire components:', error);
        }
    }

    // Log Livewire components after initialization
    setTimeout(debugLivewireComponents, 2000);

})();

