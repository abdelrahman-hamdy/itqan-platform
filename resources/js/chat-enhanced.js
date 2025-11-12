/**
 * Enhanced Chat System with Real-time Features
 * Itqan Platform - Production Ready
 */

import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

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

        this.init();
    }

    /**
     * Initialize the chat system
     */
    init() {
        this.currentUserId = document.querySelector('meta[name="user-id"]')?.content;

        if (!this.currentUserId) {
            console.error('User ID not found. Chat system cannot initialize.');
            return;
        }

        this.initializeEcho();
        this.bindEventListeners();
        this.requestNotificationPermission();
        this.loadUserPreferences();
        this.initializeServiceWorker();

        // Check for offline messages
        this.syncOfflineMessages();
    }

    /**
     * Initialize Laravel Echo for WebSocket connection
     */
    initializeEcho() {
        window.Pusher = Pusher;

        // Configure Echo with Reverb
        window.Echo = new Echo({
            broadcaster: 'reverb',
            key: window.REVERB_APP_KEY || 'vil71wafgpp6do1miwn1',
            wsHost: window.REVERB_HOST || window.location.hostname,
            wsPort: window.REVERB_PORT || 8085,
            wssPort: window.REVERB_PORT || 8085,
            forceTLS: window.location.protocol === 'https:',
            enabledTransports: ['ws', 'wss'],
            disableStats: true,
            auth: {
                headers: {
                    'X-CSRF-TOKEN': this.getCsrfToken(),
                    'Authorization': 'Bearer ' + (localStorage.getItem('auth_token') || '')
                }
            },
            authEndpoint: '/broadcasting/auth'
        });

        // Monitor connection status
        this.monitorConnection();

        // Join user's private channel
        this.joinUserChannel();
    }

    /**
     * Monitor WebSocket connection status
     */
    monitorConnection() {
        window.Echo.connector.pusher.connection.bind('connected', () => {
            this.onConnected();
        });

        window.Echo.connector.pusher.connection.bind('disconnected', () => {
            this.onDisconnected();
        });

        window.Echo.connector.pusher.connection.bind('error', (error) => {
            this.onConnectionError(error);
        });
    }

    /**
     * Handle successful connection
     */
    onConnected() {
        console.log('✅ WebSocket connected');
        this.reconnectAttempts = 0;
        this.updateConnectionStatus('online');

        // Sync any queued messages
        this.flushMessageQueue();

        // Update user's online status
        this.updateOnlineStatus(true);
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
        console.error('WebSocket error:', error);
        this.updateConnectionStatus('error');
    }

    /**
     * Attempt to reconnect
     */
    attemptReconnection() {
        if (this.reconnectAttempts < this.maxReconnectAttempts) {
            this.reconnectAttempts++;
            const delay = Math.min(1000 * Math.pow(2, this.reconnectAttempts), 30000);

            console.log(`Reconnecting in ${delay}ms... (Attempt ${this.reconnectAttempts})`);

            setTimeout(() => {
                window.Echo.connector.pusher.connect();
            }, delay);
        } else {
            this.showError('Unable to connect to chat server. Please refresh the page.');
        }
    }

    /**
     * Join user's private channel for notifications
     */
    joinUserChannel() {
        Echo.private(`chat.${this.currentUserId}`)
            .listen('.message.sent', (e) => {
                this.handleNewMessage(e);
            })
            .listen('.message.read', (e) => {
                this.handleMessageRead(e);
            })
            .listen('.message.delivered', (e) => {
                this.handleMessageDelivered(e);
            })
            .listen('.user.typing', (e) => {
                this.handleTypingIndicator(e);
            })
            .listen('.conversation.updated', (e) => {
                this.handleConversationUpdate(e);
            })
            .notification((notification) => {
                this.handleNotification(notification);
            });
    }

    /**
     * Join a conversation channel
     */
    joinConversation(conversationId, isGroup = false) {
        // Leave previous conversation
        if (this.currentConversationId) {
            this.leaveConversation();
        }

        this.currentConversationId = conversationId;

        if (isGroup) {
            this.joinGroupConversation(conversationId);
        } else {
            this.joinPrivateConversation(conversationId);
        }
    }

    /**
     * Join a private conversation
     */
    joinPrivateConversation(conversationId) {
        Echo.private(`conversation.${conversationId}`)
            .listen('.message.sent', (e) => {
                this.handleNewMessage(e);
            })
            .listen('.user.typing', (e) => {
                this.handleTypingIndicator(e);
            });
    }

    /**
     * Join a group conversation with presence
     */
    joinGroupConversation(groupId) {
        this.currentGroupId = groupId;

        Echo.join(`presence-group.${groupId}`)
            .here((users) => {
                this.updateOnlineUsers(users);
            })
            .joining((user) => {
                this.handleUserJoined(user);
            })
            .leaving((user) => {
                this.handleUserLeft(user);
            })
            .listen('.message.sent', (e) => {
                this.handleNewMessage(e);
            })
            .listen('.user.typing', (e) => {
                this.handleTypingIndicator(e);
            })
            .error((error) => {
                console.error('Presence channel error:', error);
            });
    }

    /**
     * Leave current conversation
     */
    leaveConversation() {
        if (this.currentConversationId) {
            Echo.leave(`conversation.${this.currentConversationId}`);
        }

        if (this.currentGroupId) {
            Echo.leave(`presence-group.${this.currentGroupId}`);
        }

        this.currentConversationId = null;
        this.currentGroupId = null;
        this.onlineUsers.clear();
    }

    /**
     * Handle new incoming message
     */
    handleNewMessage(data) {
        const message = data.message;

        // Don't show if it's our own message
        if (message.from_id === parseInt(this.currentUserId)) {
            return;
        }

        // Add message to UI
        this.appendMessage(message);

        // Play notification sound
        if (this.shouldPlaySound()) {
            this.playNotificationSound();
        }

        // Show desktop notification
        if (this.shouldShowNotification(message)) {
            this.showDesktopNotification(message);
        }

        // Update unread count
        this.incrementUnreadCount(message.conversation_id || message.group_id);

        // Send delivery receipt
        this.sendDeliveryReceipt(message.id);
    }

    /**
     * Send a message
     */
    async sendMessage(text, attachments = null, replyTo = null) {
        if (!text.trim() && !attachments) return;

        const tempId = 'temp_' + Date.now();
        const message = {
            id: tempId,
            body: text,
            from_id: this.currentUserId,
            to_id: this.currentConversationId,
            group_id: this.currentGroupId,
            attachment: attachments,
            reply_to: replyTo,
            created_at: new Date().toISOString(),
            status: 'sending'
        };

        // Optimistic UI update
        this.appendMessage(message, true);

        try {
            const formData = new FormData();
            formData.append('message', text);

            if (this.currentConversationId) {
                formData.append('to_id', this.currentConversationId);
            }

            if (this.currentGroupId) {
                formData.append('group_id', this.currentGroupId);
            }

            if (attachments) {
                attachments.forEach((file, index) => {
                    formData.append(`attachments[${index}]`, file);
                });
            }

            if (replyTo) {
                formData.append('reply_to', replyTo);
            }

            const response = await fetch('/chat/sendMessage', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': this.getCsrfToken(),
                    'Accept': 'application/json'
                },
                body: formData
            });

            const data = await response.json();

            if (data.status === 'success') {
                // Replace temp message with real one
                this.replaceTempMessage(tempId, data.message);
                this.updateMessageStatus(data.message.id, 'sent');
            } else {
                throw new Error(data.message || 'Failed to send message');
            }

        } catch (error) {
            console.error('Error sending message:', error);
            this.updateMessageStatus(tempId, 'failed');

            // Add to offline queue
            this.addToOfflineQueue(message);

            this.showError('Failed to send message. It will be sent when connection is restored.');
        }
    }

    /**
     * Handle typing events
     */
    handleTypingInput(input) {
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
        try {
            await fetch('/chat/typing', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.getCsrfToken()
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
        const { user_id, is_typing, user_name } = data;

        if (user_id === parseInt(this.currentUserId)) return;

        const typingElement = document.getElementById('typing-indicator');

        if (is_typing) {
            this.showTypingIndicator(user_name);
        } else {
            this.hideTypingIndicator(user_name);
        }
    }

    /**
     * Show typing indicator
     */
    showTypingIndicator(userName) {
        const indicator = document.getElementById('typing-indicator');
        if (!indicator) return;

        indicator.innerHTML = `
            <div class="typing-animation">
                <span class="typing-user">${userName}</span> is typing
                <span class="typing-dots">
                    <span></span>
                    <span></span>
                    <span></span>
                </span>
            </div>
        `;
        indicator.classList.add('active');
    }

    /**
     * Hide typing indicator
     */
    hideTypingIndicator(userName) {
        const indicator = document.getElementById('typing-indicator');
        if (!indicator) return;

        setTimeout(() => {
            indicator.classList.remove('active');
            indicator.innerHTML = '';
        }, 500);
    }

    /**
     * Mark message as read
     */
    async markMessageAsRead(messageId) {
        try {
            await fetch(`/chat/messages/${messageId}/read`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': this.getCsrfToken(),
                    'Content-Type': 'application/json'
                }
            });

            this.updateMessageStatus(messageId, 'read');

        } catch (error) {
            console.error('Error marking message as read:', error);
        }
    }

    /**
     * Handle message read event
     */
    handleMessageRead(data) {
        this.updateMessageStatus(data.message_id, 'read');

        // Update UI to show double checkmark
        const messageElement = document.querySelector(`[data-message-id="${data.message_id}"]`);
        if (messageElement) {
            const statusIcon = messageElement.querySelector('.message-status');
            if (statusIcon) {
                statusIcon.innerHTML = '✓✓';
                statusIcon.classList.add('read');
            }
        }
    }

    /**
     * Handle message delivered event
     */
    handleMessageDelivered(data) {
        this.updateMessageStatus(data.message_id, 'delivered');

        // Update UI to show double checkmark (gray)
        const messageElement = document.querySelector(`[data-message-id="${data.message_id}"]`);
        if (messageElement) {
            const statusIcon = messageElement.querySelector('.message-status');
            if (statusIcon) {
                statusIcon.innerHTML = '✓✓';
                statusIcon.classList.add('delivered');
            }
        }
    }

    /**
     * Update online users list
     */
    updateOnlineUsers(users) {
        this.onlineUsers.clear();

        users.forEach(user => {
            this.onlineUsers.add(user.id);
            this.updateUserOnlineStatus(user.id, true);
        });

        this.updateOnlineCount();
    }

    /**
     * Handle user joined
     */
    handleUserJoined(user) {
        this.onlineUsers.add(user.id);
        this.updateUserOnlineStatus(user.id, true);
        this.updateOnlineCount();

        if (this.shouldShowNotification()) {
            this.showNotification(`${user.name} joined the conversation`);
        }
    }

    /**
     * Handle user left
     */
    handleUserLeft(user) {
        this.onlineUsers.delete(user.id);
        this.updateUserOnlineStatus(user.id, false);
        this.updateOnlineCount();
    }

    /**
     * Update user online status in UI
     */
    updateUserOnlineStatus(userId, isOnline) {
        const userElements = document.querySelectorAll(`[data-user-id="${userId}"]`);

        userElements.forEach(element => {
            const statusDot = element.querySelector('.status-dot');
            if (statusDot) {
                if (isOnline) {
                    statusDot.classList.add('online');
                    statusDot.classList.remove('offline');
                } else {
                    statusDot.classList.add('offline');
                    statusDot.classList.remove('online');
                }
            }
        });
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
            requireInteraction: false,
            silent: false,
            data: {
                messageId: message.id,
                conversationId: message.conversation_id || message.group_id
            }
        });

        notification.onclick = () => {
            window.focus();
            this.openConversation(message.conversation_id || message.group_id);
            notification.close();
        };

        // Auto close after 5 seconds
        setTimeout(() => notification.close(), 5000);
    }

    /**
     * Request notification permission
     */
    requestNotificationPermission() {
        if (!('Notification' in window)) {
            console.log('This browser does not support notifications');
            return;
        }

        if (Notification.permission === 'default') {
            Notification.requestPermission().then(permission => {
                if (permission === 'granted') {
                    console.log('Notification permission granted');
                }
            });
        }
    }

    /**
     * Play notification sound
     */
    playNotificationSound() {
        const audio = new Audio('/sounds/chat/new-message-sound.mp3');
        audio.volume = 0.5;
        audio.play().catch(e => console.log('Could not play sound:', e));
    }

    /**
     * Initialize service worker for offline support
     */
    initializeServiceWorker() {
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw-chat.js')
                .then(registration => {
                    console.log('Service Worker registered:', registration);
                })
                .catch(error => {
                    console.error('Service Worker registration failed:', error);
                });
        }
    }

    /**
     * Add message to offline queue
     */
    addToOfflineQueue(message) {
        const queue = JSON.parse(localStorage.getItem('offline_messages') || '[]');
        queue.push(message);
        localStorage.setItem('offline_messages', JSON.stringify(queue));
    }

    /**
     * Sync offline messages when connection is restored
     */
    async syncOfflineMessages() {
        const queue = JSON.parse(localStorage.getItem('offline_messages') || '[]');

        if (queue.length === 0) return;

        console.log(`Syncing ${queue.length} offline messages...`);

        for (const message of queue) {
            try {
                await this.sendMessage(message.body, message.attachment, message.reply_to);
            } catch (error) {
                console.error('Error syncing offline message:', error);
            }
        }

        localStorage.removeItem('offline_messages');
    }

    /**
     * Bind UI event listeners
     */
    bindEventListeners() {
        // Message input
        const messageInput = document.getElementById('message-input');
        if (messageInput) {
            messageInput.addEventListener('input', () => this.handleTypingInput());
            messageInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    this.sendMessageFromInput();
                }
            });
        }

        // Send button
        const sendButton = document.getElementById('send-button');
        if (sendButton) {
            sendButton.addEventListener('click', () => this.sendMessageFromInput());
        }

        // File attachment
        const attachButton = document.getElementById('attach-button');
        if (attachButton) {
            attachButton.addEventListener('click', () => {
                document.getElementById('file-input')?.click();
            });
        }

        // File input change
        const fileInput = document.getElementById('file-input');
        if (fileInput) {
            fileInput.addEventListener('change', (e) => this.handleFileSelect(e));
        }

        // Conversation items
        document.querySelectorAll('.conversation-item').forEach(item => {
            item.addEventListener('click', (e) => {
                const conversationId = e.currentTarget.dataset.conversationId;
                const isGroup = e.currentTarget.dataset.isGroup === 'true';
                this.openConversation(conversationId, isGroup);
            });
        });

        // Scroll to load more messages
        const messagesContainer = document.getElementById('messages-container');
        if (messagesContainer) {
            messagesContainer.addEventListener('scroll', () => {
                if (messagesContainer.scrollTop === 0) {
                    this.loadMoreMessages();
                }
            });
        }

        // Mark messages as read on scroll
        if ('IntersectionObserver' in window) {
            this.setupReadObserver();
        }
    }

    /**
     * Setup intersection observer for read receipts
     */
    setupReadObserver() {
        const options = {
            root: document.getElementById('messages-container'),
            rootMargin: '0px',
            threshold: 0.5
        };

        this.readObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const messageId = entry.target.dataset.messageId;
                    const isRead = entry.target.dataset.read === 'true';

                    if (!isRead && messageId) {
                        this.markMessageAsRead(messageId);
                    }
                }
            });
        }, options);
    }

    /**
     * Helper functions
     */
    getCsrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.content || '';
    }

    updateConnectionStatus(status) {
        const statusElement = document.getElementById('connection-status');
        if (statusElement) {
            statusElement.className = `connection-status ${status}`;
            statusElement.textContent = status.charAt(0).toUpperCase() + status.slice(1);
        }
    }

    appendMessage(message, isTemp = false) {
        const container = document.getElementById('messages-container');
        if (!container) return;

        const messageElement = this.createMessageElement(message, isTemp);
        container.appendChild(messageElement);

        // Scroll to bottom
        container.scrollTop = container.scrollHeight;

        // Observe for read receipt
        if (!isTemp && this.readObserver) {
            this.readObserver.observe(messageElement);
        }
    }

    createMessageElement(message, isTemp = false) {
        const div = document.createElement('div');
        div.className = `message ${message.from_id == this.currentUserId ? 'sent' : 'received'} ${isTemp ? 'temp' : ''}`;
        div.dataset.messageId = message.id;
        div.dataset.read = 'false';

        let statusIcon = '';
        if (message.from_id == this.currentUserId) {
            if (message.status === 'read') {
                statusIcon = '<span class="message-status read">✓✓</span>';
            } else if (message.status === 'delivered') {
                statusIcon = '<span class="message-status delivered">✓✓</span>';
            } else if (message.status === 'sent') {
                statusIcon = '<span class="message-status sent">✓</span>';
            } else if (message.status === 'sending') {
                statusIcon = '<span class="message-status sending">⏱</span>';
            } else if (message.status === 'failed') {
                statusIcon = '<span class="message-status failed">❌</span>';
            }
        }

        div.innerHTML = `
            <div class="message-content">
                ${message.reply_to ? `<div class="reply-to">Replying to: ${message.reply_to_text}</div>` : ''}
                <div class="message-text">${this.escapeHtml(message.body)}</div>
                ${message.attachment ? this.renderAttachment(message.attachment) : ''}
                <div class="message-meta">
                    <span class="message-time">${this.formatTime(message.created_at)}</span>
                    ${statusIcon}
                </div>
            </div>
        `;

        return div;
    }

    renderAttachment(attachment) {
        if (attachment.type === 'image') {
            return `<img src="${attachment.url}" alt="Image" class="message-image" onclick="this.classList.toggle('expanded')">`;
        } else if (attachment.type === 'video') {
            return `<video src="${attachment.url}" controls class="message-video"></video>`;
        } else if (attachment.type === 'audio') {
            return `<audio src="${attachment.url}" controls class="message-audio"></audio>`;
        } else {
            return `
                <div class="message-file">
                    <i class="fas fa-file"></i>
                    <a href="${attachment.url}" download="${attachment.name}">${attachment.name}</a>
                    <span class="file-size">${this.formatFileSize(attachment.size)}</span>
                </div>
            `;
        }
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    formatTime(timestamp) {
        const date = new Date(timestamp);
        const now = new Date();
        const diff = now - date;
        const days = Math.floor(diff / (1000 * 60 * 60 * 24));

        if (days === 0) {
            return date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
        } else if (days === 1) {
            return 'Yesterday';
        } else if (days < 7) {
            return date.toLocaleDateString('en-US', { weekday: 'short' });
        } else {
            return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
        }
    }

    formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
    }

    replaceTempMessage(tempId, realMessage) {
        const tempElement = document.querySelector(`[data-message-id="${tempId}"]`);
        if (tempElement) {
            const newElement = this.createMessageElement(realMessage);
            tempElement.replaceWith(newElement);
        }
    }

    updateMessageStatus(messageId, status) {
        const element = document.querySelector(`[data-message-id="${messageId}"]`);
        if (element) {
            element.dataset.status = status;
            const statusIcon = element.querySelector('.message-status');
            if (statusIcon) {
                if (status === 'read') {
                    statusIcon.innerHTML = '✓✓';
                    statusIcon.className = 'message-status read';
                } else if (status === 'delivered') {
                    statusIcon.innerHTML = '✓✓';
                    statusIcon.className = 'message-status delivered';
                } else if (status === 'sent') {
                    statusIcon.innerHTML = '✓';
                    statusIcon.className = 'message-status sent';
                } else if (status === 'failed') {
                    statusIcon.innerHTML = '❌';
                    statusIcon.className = 'message-status failed';
                }
            }
        }
    }

    sendMessageFromInput() {
        const input = document.getElementById('message-input');
        if (!input) return;

        const text = input.value.trim();
        if (!text) return;

        this.sendMessage(text);
        input.value = '';
        input.focus();
    }

    handleFileSelect(event) {
        const files = Array.from(event.target.files);
        if (files.length === 0) return;

        // Preview files
        this.previewFiles(files);

        // Store for sending
        this.pendingAttachments = files;
    }

    previewFiles(files) {
        const preview = document.getElementById('file-preview');
        if (!preview) return;

        preview.innerHTML = '';

        files.forEach(file => {
            const div = document.createElement('div');
            div.className = 'file-preview-item';

            if (file.type.startsWith('image/')) {
                const img = document.createElement('img');
                img.src = URL.createObjectURL(file);
                div.appendChild(img);
            } else {
                div.innerHTML = `<i class="fas fa-file"></i> ${file.name}`;
            }

            const removeBtn = document.createElement('button');
            removeBtn.className = 'remove-file';
            removeBtn.innerHTML = '×';
            removeBtn.onclick = () => this.removeFile(file);
            div.appendChild(removeBtn);

            preview.appendChild(div);
        });

        preview.style.display = 'flex';
    }

    removeFile(file) {
        this.pendingAttachments = this.pendingAttachments.filter(f => f !== file);
        if (this.pendingAttachments.length === 0) {
            document.getElementById('file-preview').style.display = 'none';
        }
    }

    async openConversation(conversationId, isGroup = false) {
        this.currentConversationId = conversationId;

        // Update UI
        document.querySelectorAll('.conversation-item').forEach(item => {
            item.classList.remove('active');
        });
        document.querySelector(`[data-conversation-id="${conversationId}"]`)?.classList.add('active');

        // Join conversation channel
        this.joinConversation(conversationId, isGroup);

        // Load messages
        await this.loadMessages(conversationId);

        // Mark as read
        await this.markConversationAsRead(conversationId);
    }

    async loadMessages(conversationId) {
        try {
            const response = await fetch(`/chat/fetchMessages`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.getCsrfToken()
                },
                body: JSON.stringify({
                    id: conversationId,
                    page: 1
                })
            });

            const data = await response.json();

            if (data.messages) {
                this.displayMessages(data.messages);
            }
        } catch (error) {
            console.error('Error loading messages:', error);
        }
    }

    displayMessages(messages) {
        const container = document.getElementById('messages-container');
        if (!container) return;

        container.innerHTML = '';

        messages.forEach(message => {
            this.appendMessage(message);
        });

        // Scroll to bottom
        container.scrollTop = container.scrollHeight;
    }

    async markConversationAsRead(conversationId) {
        try {
            await fetch('/chat/makeSeen', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.getCsrfToken()
                },
                body: JSON.stringify({
                    id: conversationId
                })
            });

            // Update unread count
            this.updateUnreadCount(conversationId, 0);
        } catch (error) {
            console.error('Error marking conversation as read:', error);
        }
    }

    updateUnreadCount(conversationId, count = null) {
        const badge = document.querySelector(`[data-conversation-id="${conversationId}"] .unread-badge`);
        if (!badge) return;

        if (count === 0) {
            badge.style.display = 'none';
        } else if (count !== null) {
            badge.textContent = count;
            badge.style.display = 'block';
        } else {
            // Increment
            const current = parseInt(badge.textContent) || 0;
            badge.textContent = current + 1;
            badge.style.display = 'block';
        }
    }

    incrementUnreadCount(conversationId) {
        this.updateUnreadCount(conversationId);
    }

    async loadMoreMessages() {
        if (this.loadingMessages) return;

        this.loadingMessages = true;

        // Implementation for loading older messages
        // ...

        this.loadingMessages = false;
    }

    shouldPlaySound() {
        return localStorage.getItem('chat_sound') !== 'false';
    }

    shouldShowNotification() {
        return localStorage.getItem('chat_notifications') !== 'false';
    }

    loadUserPreferences() {
        // Load user preferences from localStorage
        const preferences = {
            sound: localStorage.getItem('chat_sound') !== 'false',
            notifications: localStorage.getItem('chat_notifications') !== 'false',
            enterToSend: localStorage.getItem('chat_enter_send') !== 'false'
        };

        return preferences;
    }

    updateOnlineStatus(isOnline) {
        // Update user's online status
        fetch('/chat/setActiveStatus', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': this.getCsrfToken()
            },
            body: JSON.stringify({
                status: isOnline ? 'online' : 'offline'
            })
        });
    }

    updateOnlineCount() {
        const count = this.onlineUsers.size;
        const element = document.getElementById('online-count');
        if (element) {
            element.textContent = `${count} online`;
        }
    }

    showError(message) {
        // Show error notification
        const notification = document.createElement('div');
        notification.className = 'chat-notification error';
        notification.textContent = message;
        document.body.appendChild(notification);

        setTimeout(() => {
            notification.remove();
        }, 5000);
    }

    showNotification(message) {
        // Show info notification
        const notification = document.createElement('div');
        notification.className = 'chat-notification info';
        notification.textContent = message;
        document.body.appendChild(notification);

        setTimeout(() => {
            notification.remove();
        }, 3000);
    }

    flushMessageQueue() {
        // Send queued messages
        while (this.messageQueue.length > 0) {
            const message = this.messageQueue.shift();
            this.sendMessage(message.body, message.attachment, message.reply_to);
        }
    }

    sendDeliveryReceipt(messageId) {
        // Send delivery receipt
        fetch(`/chat/messages/${messageId}/delivered`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': this.getCsrfToken()
            }
        });
    }

    handleNotification(notification) {
        console.log('Notification received:', notification);
        // Handle different notification types
        switch (notification.type) {
            case 'App\\Notifications\\NewMessage':
                this.handleNewMessageNotification(notification);
                break;
            case 'App\\Notifications\\MentionNotification':
                this.handleMentionNotification(notification);
                break;
            default:
                console.log('Unknown notification type:', notification.type);
        }
    }

    handleNewMessageNotification(notification) {
        // Handle new message notification
        if (notification.data.conversation_id !== this.currentConversationId) {
            // Show notification badge on conversation
            this.incrementUnreadCount(notification.data.conversation_id);
        }
    }

    handleMentionNotification(notification) {
        // Handle mention notification
        this.showNotification(`${notification.data.sender_name} mentioned you`);
    }

    handleConversationUpdate(data) {
        // Update conversation in the list
        const item = document.querySelector(`[data-conversation-id="${data.conversation_id}"]`);
        if (item) {
            // Update last message preview
            const preview = item.querySelector('.last-message');
            if (preview) {
                preview.textContent = data.last_message;
            }

            // Update timestamp
            const time = item.querySelector('.conversation-time');
            if (time) {
                time.textContent = this.formatTime(data.updated_at);
            }
        }
    }

    /**
     * Destroy the chat system
     */
    destroy() {
        // Clean up event listeners
        window.Echo?.disconnect();

        // Clear timers
        clearTimeout(this.typingTimer);

        // Disconnect observer
        this.readObserver?.disconnect();

        // Clear data
        this.onlineUsers.clear();
        this.messageQueue = [];
    }
}

// Initialize chat system when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.enhancedChat = new EnhancedChatSystem();
    });
} else {
    window.enhancedChat = new EnhancedChatSystem();
}

// Export for use in other modules
export default EnhancedChatSystem;