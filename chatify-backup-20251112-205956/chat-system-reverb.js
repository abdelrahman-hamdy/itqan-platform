/**
 * Enhanced Chat System with Real-time Features - Standalone Version
 * Itqan Platform - Production Ready
 * No build tools required - works directly in browser
 */

(function() {
    'use strict';

    // Check if Echo and Pusher are loaded
    if (typeof Pusher === 'undefined') {
        console.error('❌ Pusher library not loaded. Please include it before this script.');
        return;
    }

    class EnhancedChatSystem {
        constructor() {
            this.currentUserId = null;
            this.currentConversationId = null;
            this.currentGroupId = null;
            this.typingTimer = null;
            this.isTyping = false;
            this.onlineUsers = new Set();
            this.messageQueue = [];
            this.reconnectAttempts = 0;
            this.maxReconnectAttempts = 5;
            this.readObserver = null;

            console.log('🚀 Initializing Enhanced Chat System...');
            this.init();
        }

        /**
         * Initialize the chat system
         */
        init() {
            // Get user ID from config or meta tag
            this.currentUserId = window.chatConfig?.userId ||
                                 document.querySelector('meta[name="user-id"]')?.content;

            if (!this.currentUserId) {
                console.error('❌ User ID not found. Chat system cannot initialize.');
                return;
            }

            console.log(`✅ User ID: ${this.currentUserId}`);

            // Initialize Echo connection
            this.initializeEcho();

            // Bind UI event listeners
            this.bindEventListeners();

            // Request notification permission
            this.requestNotificationPermission();

            // Load user preferences
            this.loadUserPreferences();

            // Initialize service worker
            this.initializeServiceWorker();

            // Check for offline messages
            this.syncOfflineMessages();

            console.log('✅ Enhanced Chat System initialized successfully!');
        }

        /**
         * Initialize Laravel Echo for WebSocket connection
         */
        initializeEcho() {
            console.log('🔌 Connecting to Reverb WebSocket...');

            // Determine if we should use TLS based on page protocol
            // Use TLS whenever the page is served over HTTPS to avoid mixed-content issues
            const isLocal = window.location.hostname === 'localhost' ||
                            window.location.hostname === '127.0.0.1';

            const useTLS = window.location.protocol === 'https:';
            const scheme = useTLS ? 'https' : 'http';

            console.log(`🔧 WebSocket Config: hostname=${window.location.hostname}, protocol=${window.location.protocol}, useTLS=${useTLS}, scheme=${scheme}`);

            // Build Echo config for Reverb
            // IMPORTANT: Reverb uses 'scheme' not 'forceTLS'
            const echoConfig = {
                broadcaster: 'reverb',
                key: window.chatConfig?.reverbKey || 'vil71wafgpp6do1miwn1',
                wsHost: window.chatConfig?.reverbHost || window.location.hostname,
                wsPort: Number(window.chatConfig?.reverbPort || 8085),
                wssPort: Number(window.chatConfig?.reverbPort || 8085),
                forceTLS: false, // Scheme controls the protocol for Reverb
                scheme: window.chatConfig?.reverbScheme || scheme, // 'http' = ws://, 'https' = wss://
                enabledTransports: ['ws', 'wss'],
                disableStats: true,
                authEndpoint: '/broadcasting/auth',
                auth: {
                    headers: {
                        'X-CSRF-TOKEN': this.getCsrfToken(),
                        'Accept': 'application/json',
                    }
                }
            };

            console.log('📡 Final Echo config:', echoConfig);

            // Configure or reuse Echo with Reverb
            const existingEcho = (typeof window.Echo === 'object' && window.Echo?.connector) ? window.Echo : null;
            const EchoCtor = (typeof window.Echo === 'function') ? window.Echo : (typeof Echo === 'function' ? Echo : null);

            if (existingEcho) {
                const currentHost = existingEcho.options?.wsHost ?? existingEcho.connector?.options?.wsHost;
                const currentScheme = existingEcho.options?.scheme ?? existingEcho.connector?.options?.scheme;
                const currentPort = existingEcho.options?.wsPort ?? existingEcho.options?.wssPort ?? existingEcho.connector?.options?.wsPort;
                const desiredHost = echoConfig.wsHost;
                const desiredScheme = echoConfig.scheme;
                const desiredPort = echoConfig.wsPort ?? echoConfig.wssPort;

                if (currentHost !== desiredHost || currentScheme !== desiredScheme || (desiredPort && currentPort && String(currentPort) !== String(desiredPort))) {
                    console.log('🔄 Replacing existing Echo instance with desired Reverb config', { currentHost, desiredHost, currentScheme, desiredScheme, currentPort, desiredPort });
                    try { existingEcho.disconnect?.(); } catch (e) { /* ignore */ }
                    if (EchoCtor) {
                        window.Echo = new EchoCtor(echoConfig);
                    } else {
                        console.error('❌ Laravel Echo constructor not found. Please include it before this script.');
                        return;
                    }
                } else {
                    console.log('♻️ Reusing existing Laravel Echo instance');
                }
            } else if (EchoCtor) {
                window.Echo = new EchoCtor(echoConfig);
            } else {
                console.error('❌ Laravel Echo not loaded. Please include it before this script.');
                return;
            }

            // Monitor connection status
            this.monitorConnection();

            // Join user's private channel
            this.joinUserChannel();
        }

        /**
         * Monitor WebSocket connection status
         */
        monitorConnection() {
            if (!window.Echo?.connector?.pusher?.connection) {
                console.warn('⚠️ Pusher connection not available');
                return;
            }

            const connection = window.Echo.connector.pusher.connection;

            connection.bind('connected', () => {
                this.onConnected();
            });

            connection.bind('disconnected', () => {
                this.onDisconnected();
            });

            connection.bind('error', (error) => {
                this.onConnectionError(error);
            });

            connection.bind('state_change', (states) => {
                console.log(`🔄 Connection state: ${states.previous} -> ${states.current}`);
            });
        }

        /**
         * Handle successful connection
         */
        onConnected() {
            console.log('✅ WebSocket connected successfully');
            this.reconnectAttempts = 0;
            this.updateConnectionStatus('online');

            // Sync any queued messages
            this.flushMessageQueue();

            // Update user's online status
            this.updateOnlineStatus(true);

            // Show success notification
            this.showNotification('Connected to chat server', 'success');
        }

        /**
         * Handle disconnection
         */
        onDisconnected() {
            console.log('❌ WebSocket disconnected');
            this.updateConnectionStatus('offline');

            // Attempt reconnection
            this.attemptReconnection();
        }

        /**
         * Handle connection errors
         */
        onConnectionError(error) {
            console.error('❌ WebSocket error:', error);
            this.updateConnectionStatus('error');
            this.showNotification('Connection error. Retrying...', 'error');
        }

        /**
         * Attempt to reconnect
         */
        attemptReconnection() {
            if (this.reconnectAttempts < this.maxReconnectAttempts) {
                this.reconnectAttempts++;
                const delay = Math.min(1000 * Math.pow(2, this.reconnectAttempts), 30000);

                console.log(`🔄 Reconnecting in ${delay}ms... (Attempt ${this.reconnectAttempts})`);

                setTimeout(() => {
                    if (window.Echo?.connector?.pusher) {
                        window.Echo.connector.pusher.connect();
                    }
                }, delay);
            } else {
                this.showError('Unable to connect to chat server. Please refresh the page.');
            }
        }

        /**
         * Join user's private channel for notifications
         */
        joinUserChannel() {
            console.log(`📡 Joining private channel: chat.${this.currentUserId}`);

            window.Echo.private(`chat.${this.currentUserId}`)
                .subscribed(() => {
                    console.log(`✅ Subscribed: private-chat.${this.currentUserId}`);
                })
                .listen('message.sent', (e) => {
                    console.log('📨 New message received:', e);
                    this.handleNewMessage(e);
                })
                .listen('.message.sent', (e) => {
                    console.log('📨 New message received (.name):', e);
                    this.handleNewMessage(e);
                })
                .listen('message.new', (e) => {
                    console.log('🆕 New message (legacy name) received:', e);
                    this.handleNewMessage(e);
                })
                .listen('.message.new', (e) => {
                    console.log('🆕 New message (legacy .name) received:', e);
                    this.handleNewMessage(e);
                })
                .listen('message.read', (e) => {
                    console.log('✅ Message read:', e);
                    this.handleMessageRead(e);
                })
                .listen('.message.read', (e) => {
                    console.log('✅ Message read (.name):', e);
                    this.handleMessageRead(e);
                })
                .listen('message.delivered', (e) => {
                    console.log('📬 Message delivered:', e);
                    this.handleMessageDelivered(e);
                })
                .listen('.message.delivered', (e) => {
                    console.log('📬 Message delivered (.name):', e);
                    this.handleMessageDelivered(e);
                })
                .listen('user.typing', (e) => {
                    console.log('⌨️ User typing:', e);
                    this.handleTypingIndicator(e);
                })
                .listen('.user.typing', (e) => {
                    console.log('⌨️ User typing (.name):', e);
                    this.handleTypingIndicator(e);
                })
                .listen('conversation.updated', (e) => {
                    console.log('🔄 Conversation updated:', e);
                    this.handleConversationUpdate(e);
                })
                .listen('.conversation.updated', (e) => {
                    console.log('🔄 Conversation updated (.name):', e);
                    this.handleConversationUpdate(e);
                })
                .notification((notification) => {
                    console.log('🔔 Notification received:', notification);
                    this.handleNotification(notification);
                })
                .error((error) => {
                    console.error('❌ Channel error:', error);
                });

            console.log('✅ Joined private channel successfully');
        }

        /**
         * Handle new incoming message
         */
        handleNewMessage(data) {
            // Normalize payload from different events
            // Case 1: { message: {...} }
            // Case 2: { id, from_id, to_id, body, created_at, ... }
            let message = data?.message || (data && data.id && data.body ? data : null);

            if (!message) {
                console.warn('⚠️ Received message event without parsable payload, ignoring.', data);
                return;
            }

            // Don't show if it's our own message
            if (String(message.from_id) === String(this.currentUserId)) {
                console.log('Skipping own message');
                return;
            }

            console.log('Processing new message:', message);

            // Add message to UI
            this.appendMessage(message);

            // Play notification sound
            if (this.shouldPlaySound()) {
                this.playNotificationSound();
            }

            // Show desktop notification
            if (this.shouldShowNotification()) {
                this.showDesktopNotification(message);
            }

            // Update unread count
            this.incrementUnreadCount(message.to_id);

            // Show in-app notification
            this.showNotification(`New message from ${message.sender_name || 'User'}`, 'info');
        }

        /**
         * Handle typing events
         */
        handleTypingInput() {
            if (!this.isTyping) {
                this.isTyping = true;
                this.sendTypingIndicator(true);
            }

            clearTimeout(this.typingTimer);

            this.typingTimer = setTimeout(() => {
                this.isTyping = false;
                this.sendTypingIndicator(false);
            }, 1500);
        }

        /**
         * Send typing indicator
         */
        async sendTypingIndicator(isTyping) {
            if (!this.currentConversationId && !this.currentGroupId) {
                return;
            }

            try {
                await fetch('/chat/typing', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.getCsrfToken(),
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        conversation_id: this.currentConversationId,
                        group_id: this.currentGroupId,
                        is_typing: isTyping
                    })
                });
            } catch (error) {
                console.error('Error sending typing indicator:', error);
            }
        }

        /**
         * Handle typing indicator from other users
         */
        handleTypingIndicator(data) {
            const { user_id, user_name, is_typing } = data;

            if (user_id == this.currentUserId) return;

            if (is_typing) {
                this.showTypingIndicator(user_name);
            } else {
                this.hideTypingIndicator();
            }
        }

        /**
         * Show typing indicator
         */
        showTypingIndicator(userName) {
            const indicator = document.getElementById('typing-indicator');
            if (!indicator) return;

            indicator.innerHTML = `
                <div class="flex items-center gap-2 text-sm text-gray-500 px-4 py-2">
                    <span>${userName} is typing</span>
                    <div class="flex gap-1">
                        <span class="w-2 h-2 bg-gray-400 rounded-full animate-bounce"></span>
                        <span class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.2s"></span>
                        <span class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.4s"></span>
                    </div>
                </div>
            `;
            indicator.classList.remove('hidden');
        }

        /**
         * Hide typing indicator
         */
        hideTypingIndicator() {
            const indicator = document.getElementById('typing-indicator');
            if (!indicator) return;

            indicator.classList.add('hidden');
            indicator.innerHTML = '';
        }

        /**
         * Handle message read event
         */
        handleMessageRead(data) {
            console.log('Updating read status for message:', data.message_id);
            this.updateMessageStatus(data.message_id, 'read');
        }

        /**
         * Handle message delivered event
         */
        handleMessageDelivered(data) {
            console.log('Updating delivered status for message:', data.message_id);
            this.updateMessageStatus(data.message_id, 'delivered');
        }

        /**
         * Update message status in UI
         */
        updateMessageStatus(messageId, status) {
            const messageElement = document.querySelector(`[data-message-id="${messageId}"]`);
            if (!messageElement) return;

            const statusIcon = messageElement.querySelector('.message-status');
            if (!statusIcon) return;

            if (status === 'read') {
                statusIcon.innerHTML = '✓✓';
                statusIcon.classList.add('text-blue-500');
                statusIcon.classList.remove('text-gray-400');
            } else if (status === 'delivered') {
                statusIcon.innerHTML = '✓✓';
                statusIcon.classList.add('text-gray-500');
            } else if (status === 'sent') {
                statusIcon.innerHTML = '✓';
                statusIcon.classList.add('text-gray-400');
            }
        }

        /**
         * Append message to UI
         */
        appendMessage(message) {
            const container = document.getElementById('messages-container');
            if (!container) {
                console.warn('Messages container not found');
                return;
            }

            const messageElement = this.createMessageElement(message);
            container.appendChild(messageElement);

            // Scroll to bottom
            container.scrollTop = container.scrollHeight;

            console.log('✅ Message appended to UI');
        }

        /**
         * Create message HTML element
         */
        createMessageElement(message) {
            const isSent = message.from_id == this.currentUserId;
            const div = document.createElement('div');
            div.className = `message flex ${isSent ? 'justify-end' : 'justify-start'} mb-4`;
            div.dataset.messageId = message.id;

            const statusIcon = isSent ? '<span class="message-status text-gray-400 text-xs ml-2">✓</span>' : '';

            div.innerHTML = `
                <div class="max-w-[70%]">
                    <div class="${isSent ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-900'} rounded-lg px-4 py-2">
                        <p class="text-sm">${this.escapeHtml(message.body)}</p>
                        <div class="flex items-center justify-end gap-1 mt-1">
                            <span class="text-xs opacity-75">${this.formatTime(message.created_at)}</span>
                            ${statusIcon}
                        </div>
                    </div>
                </div>
            `;

            return div;
        }

        /**
         * Escape HTML to prevent XSS
         */
        escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        /**
         * Format timestamp
         */
        formatTime(timestamp) {
            const date = new Date(timestamp);
            return date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
        }

        /**
         * Show desktop notification
         */
        showDesktopNotification(message) {
            if (!('Notification' in window)) return;
            if (Notification.permission !== 'granted') return;
            if (document.hasFocus()) return;

            const notification = new Notification(message.sender_name || 'New Message', {
                body: message.body,
                icon: message.sender_avatar || '/images/default-avatar.png',
                badge: '/images/chat-badge.png',
                tag: `message-${message.id}`,
            });

            notification.onclick = () => {
                window.focus();
                notification.close();
            };

            setTimeout(() => notification.close(), 5000);
        }

        /**
         * Request notification permission
         */
        requestNotificationPermission() {
            if (!('Notification' in window)) return;

            if (Notification.permission === 'default') {
                Notification.requestPermission().then(permission => {
                    console.log(`🔔 Notification permission: ${permission}`);
                });
            }
        }

        /**
         * Play notification sound
         */
        playNotificationSound() {
            try {
                const audio = new Audio('/sounds/chat/new-message-sound.mp3');
                audio.volume = 0.5;
                audio.play().catch(e => console.log('Could not play sound:', e));
            } catch (error) {
                console.log('Error playing notification sound:', error);
            }
        }

        /**
         * Initialize service worker
         */
        initializeServiceWorker() {
            // Temporarily disabled to prevent caching issues during development
            console.log('🚫 Service Worker disabled for development');
            /* if ('serviceWorker' in navigator) {
                navigator.serviceWorker.register('/sw-chat.js')
                    .then(registration => {
                        console.log('✅ Service Worker registered');
                    })
                    .catch(error => {
                        console.log('Service Worker registration failed:', error);
                    });
            } */
        }

        /**
         * Sync offline messages
         */
        async syncOfflineMessages() {
            const queue = JSON.parse(localStorage.getItem('offline_messages') || '[]');

            if (queue.length === 0) return;

            console.log(`📤 Syncing ${queue.length} offline messages...`);

            for (const message of queue) {
                // Send message logic here
            }

            localStorage.removeItem('offline_messages');
        }

        /**
         * Bind UI event listeners
         */
        bindEventListeners() {
            console.log('🔗 Binding event listeners...');

            // Message input typing
            const messageInput = document.getElementById('message-input');
            if (messageInput) {
                messageInput.addEventListener('input', () => this.handleTypingInput());
            }

            // Add more event listeners as needed
        }

        /**
         * Load user preferences
         */
        loadUserPreferences() {
            this.preferences = {
                sound: localStorage.getItem('chat_sound') !== 'false',
                notifications: localStorage.getItem('chat_notifications') !== 'false',
            };

            console.log('⚙️ Loaded preferences:', this.preferences);
        }

        /**
         * Helper functions
         */
        getCsrfToken() {
            return document.querySelector('meta[name="csrf-token"]')?.content ||
                   window.chatConfig?.csrfToken || '';
        }

        updateConnectionStatus(status) {
            const statusElement = document.getElementById('connection-status');
            if (statusElement) {
                statusElement.className = `connection-status ${status}`;
                statusElement.textContent = status;
            }

            console.log(`📡 Connection status: ${status}`);
        }

        updateOnlineStatus(isOnline) {
            // Send to server
            fetch('/chat/setActiveStatus', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.getCsrfToken(),
                },
                body: JSON.stringify({ status: isOnline ? 1 : 0 })
            }).catch(error => console.error('Error updating online status:', error));
        }

        incrementUnreadCount(conversationId) {
            const badge = document.querySelector(`[data-conversation-id="${conversationId}"] .unread-badge`);
            if (!badge) return;

            const current = parseInt(badge.textContent) || 0;
            badge.textContent = current + 1;
            badge.style.display = 'block';
        }

        flushMessageQueue() {
            console.log(`📤 Flushing ${this.messageQueue.length} queued messages`);
            // Implement message queue flushing
        }

        shouldPlaySound() {
            return this.preferences?.sound !== false;
        }

        shouldShowNotification() {
            return this.preferences?.notifications !== false;
        }

        showNotification(message, type = 'info') {
            console.log(`🔔 ${type.toUpperCase()}: ${message}`);

            // Create notification element
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 ${
                type === 'success' ? 'bg-green-500' :
                type === 'error' ? 'bg-red-500' :
                type === 'warning' ? 'bg-yellow-500' :
                'bg-blue-500'
            } text-white`;
            notification.textContent = message;

            document.body.appendChild(notification);

            setTimeout(() => {
                notification.remove();
            }, 3000);
        }

        showError(message) {
            this.showNotification(message, 'error');
        }

        handleConversationUpdate(data) {
            console.log('Conversation updated:', data);
        }

        handleNotification(notification) {
            console.log('Notification:', notification);
        }
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            window.enhancedChat = new EnhancedChatSystem();
        });
    } else {
        window.enhancedChat = new EnhancedChatSystem();
    }

    console.log('✅ Enhanced Chat System script loaded');

})();