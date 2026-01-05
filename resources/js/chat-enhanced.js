/**
 * Enhanced Chat System with Real-time Features
 * Itqan Platform - Production Ready
 *
 * Note: Echo and Pusher are initialized globally via bootstrap.js
 * This module uses window.Echo for real-time features
 */

import { Logger, ChatLogger } from './utils/logger';
import { ErrorHandler, handleApiError } from './utils/error-handler';
import { getCsrfToken, getCsrfFormDataHeaders } from './utils/csrf';
import { TIMEOUTS, LIMITS, STATUS, LABELS_AR } from './utils/constants';

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

        // Store bound handlers for cleanup (prevents memory leaks)
        this._boundHandlers = {
            onConnected: null,
            onDisconnected: null,
            onConnectionError: null,
            messageInputHandler: null,
            messageKeyHandler: null,
            sendButtonHandler: null,
            attachButtonHandler: null,
            fileInputHandler: null,
        };

        this.init();
    }

    /**
     * Initialize the chat system
     */
    init() {
        this.currentUserId = document.querySelector('meta[name="user-id"]')?.content;

        if (!this.currentUserId) {
            ChatLogger.error('User ID not found. Chat system cannot initialize.');
            return;
        }

        this.initializeEcho();
        this.bindEventListeners();
        // NOTE: Removed requestNotificationPermission() from initialization
        // Browser notification permission must be requested on user gesture
        // It will be requested when user explicitly enables notifications
        this.loadUserPreferences();
        this.initializeServiceWorker();

        // Check for offline messages
        this.syncOfflineMessages();
    }

    /**
     * Initialize Laravel Echo for WebSocket connection
     * Uses the existing window.Echo instance from bootstrap.js to avoid duplicate connections
     */
    initializeEcho() {
        // Use existing Echo instance initialized in resources/js/echo.js via bootstrap.js
        // This prevents duplicate WebSocket connections and event listener conflicts
        if (!window.Echo) {
            ChatLogger.error('Echo not initialized. Ensure bootstrap.js is loaded before chat-enhanced.js');
            ChatLogger.error('Real-time chat features will not work.');
            return;
        }

        // Echo is already configured and connected via echo.js
        // Just monitor its connection and join channels
        ChatLogger.log('Using existing Echo instance');

        // Monitor connection status
        this.monitorConnection();

        // Join user's private channel
        this.joinUserChannel();
    }

    /**
     * Monitor WebSocket connection status
     */
    monitorConnection() {
        // Store bound handlers for cleanup
        this._boundHandlers.onConnected = () => this.onConnected();
        this._boundHandlers.onDisconnected = () => this.onDisconnected();
        this._boundHandlers.onConnectionError = (error) => this.onConnectionError(error);

        window.Echo.connector.pusher.connection.bind('connected', this._boundHandlers.onConnected);
        window.Echo.connector.pusher.connection.bind('disconnected', this._boundHandlers.onDisconnected);
        window.Echo.connector.pusher.connection.bind('error', this._boundHandlers.onConnectionError);
    }

    /**
     * Handle successful connection
     */
    onConnected() {
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
        this.updateConnectionStatus('offline');

        // Attempt reconnection
        this.attemptReconnection();
    }

    /**
     * Handle connection errors
     */
    onConnectionError(error) {
        ChatLogger.error('WebSocket error:', error);
        this.updateConnectionStatus('error');
    }

    /**
     * Attempt to reconnect
     */
    attemptReconnection() {
        if (this.reconnectAttempts < this.maxReconnectAttempts) {
            this.reconnectAttempts++;
            const delay = Math.min(1000 * Math.pow(2, this.reconnectAttempts), 30000);

            setTimeout(() => {
                window.Echo.connector.pusher.connect();
            }, delay);
        } else {
            this.showError(LABELS_AR.ERRORS.CONNECTION_FAILED);
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
                ChatLogger.error('Presence channel error:', error);
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
                    'X-CSRF-TOKEN': getCsrfToken(),
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
            ChatLogger.error('Error sending message:', error);
            this.updateMessageStatus(tempId, 'failed');

            // Add to offline queue
            this.addToOfflineQueue(message);

            this.showError(LABELS_AR.ERRORS.NETWORK);
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
                    'X-CSRF-TOKEN': getCsrfToken()
                },
                body: JSON.stringify({
                    conversation_id: this.currentConversationId,
                    group_id: this.currentGroupId,
                    is_typing: isTyping
                })
            });
        } catch (error) {
            ChatLogger.debug('Error sending typing indicator:', error);
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
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'Content-Type': 'application/json'
                }
            });

            this.updateMessageStatus(messageId, 'read');

        } catch (error) {
            ChatLogger.error('Error marking message as read:', error);
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
            this.showNotification(`${user.name} ${LABELS_AR.CHAT.JOINED_CONVERSATION}`);
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
            return;
        }

        if (Notification.permission === 'default') {
            Notification.requestPermission();
        }
    }

    /**
     * Play notification sound
     */
    playNotificationSound() {
        const audio = new Audio('/sounds/chat/new-message-sound.mp3');
        audio.volume = 0.5;
        audio.play().catch(() => { /* Audio playback failed silently */ });
    }

    /**
     * Initialize service worker for offline support
     */
    initializeServiceWorker() {
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw-chat.js')
                .catch(() => { /* Service Worker registration failed silently */ });
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

        for (const message of queue) {
            try {
                await this.sendMessage(message.body, message.attachment, message.reply_to);
            } catch {
                // Silently handle sync errors - messages will be retried
            }
        }

        localStorage.removeItem('offline_messages');
    }

    /**
     * Bind UI event listeners
     */
    bindEventListeners() {
        // Store element references for cleanup
        this._elements = {
            messageInput: document.getElementById('message-input'),
            sendButton: document.getElementById('send-button'),
            attachButton: document.getElementById('attach-button'),
            fileInput: document.getElementById('file-input'),
            messagesContainer: document.getElementById('messages-container'),
        };

        // Message input - store bound handlers for cleanup
        if (this._elements.messageInput) {
            this._boundHandlers.messageInputHandler = () => this.handleTypingInput();
            this._boundHandlers.messageKeyHandler = (e) => {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    this.sendMessageFromInput();
                }
            };
            this._elements.messageInput.addEventListener('input', this._boundHandlers.messageInputHandler);
            this._elements.messageInput.addEventListener('keypress', this._boundHandlers.messageKeyHandler);
        }

        // Send button
        if (this._elements.sendButton) {
            this._boundHandlers.sendButtonHandler = () => this.sendMessageFromInput();
            this._elements.sendButton.addEventListener('click', this._boundHandlers.sendButtonHandler);
        }

        // File attachment
        if (this._elements.attachButton) {
            this._boundHandlers.attachButtonHandler = () => {
                document.getElementById('file-input')?.click();
            };
            this._elements.attachButton.addEventListener('click', this._boundHandlers.attachButtonHandler);
        }

        // File input change
        if (this._elements.fileInput) {
            this._boundHandlers.fileInputHandler = (e) => this.handleFileSelect(e);
            this._elements.fileInput.addEventListener('change', this._boundHandlers.fileInputHandler);
        }

        // Conversation items
        document.querySelectorAll('.conversation-item').forEach(item => {
            item.addEventListener('click', (e) => {
                const conversationId = e.currentTarget.dataset.conversationId;
                const isGroup = e.currentTarget.dataset.isGroup === 'true';
                this.openConversation(conversationId, isGroup);
            });
        });

        // Note: Message pagination is handled by WireChat's Livewire component ($wire.loadMore)

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
     * Note: getCsrfToken is now imported from utils/csrf.js for consistency
     */

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

        // Build message HTML with proper XSS protection
        const replyHtml = message.reply_to
            ? `<div class="reply-to">${LABELS_AR.CHAT.REPLY_TO}: ${this.escapeHtml(message.reply_to_text)}</div>`
            : '';
        const bodyHtml = `<div class="message-text">${this.escapeHtml(message.body)}</div>`;
        const attachmentHtml = message.attachment ? this.renderAttachment(message.attachment) : '';
        const timeHtml = `<span class="message-time">${this.escapeHtml(this.formatTime(message.created_at))}</span>`;

        div.innerHTML = `
            <div class="message-content">
                ${replyHtml}
                ${bodyHtml}
                ${attachmentHtml}
                <div class="message-meta">
                    ${timeHtml}
                    ${statusIcon}
                </div>
            </div>
        `;

        return div;
    }

    /**
     * Render attachment with proper XSS protection
     * @param {Object} attachment - Attachment object with url, name, type, size
     * @returns {string} Safe HTML for attachment
     */
    renderAttachment(attachment) {
        if (!attachment) return '';

        // Sanitize URL and filename to prevent XSS
        const safeUrl = this.sanitizeUrl(attachment.url);
        const safeName = this.sanitizeFilename(attachment.name);
        const safeSize = this.formatFileSize(attachment.size || 0);

        if (attachment.type === 'image') {
            return `<img src="${safeUrl}" alt="${safeName}" class="message-image" onclick="this.classList.toggle('expanded')" loading="lazy">`;
        } else if (attachment.type === 'video') {
            return `<video src="${safeUrl}" controls class="message-video" preload="metadata"></video>`;
        } else if (attachment.type === 'audio') {
            return `<audio src="${safeUrl}" controls class="message-audio" preload="metadata"></audio>`;
        } else {
            return `
                <div class="message-file">
                    <i class="fas fa-file"></i>
                    <a href="${safeUrl}" download="${safeName}">${safeName}</a>
                    <span class="file-size">${safeSize}</span>
                </div>
            `;
        }
    }

    /**
     * Escape HTML to prevent XSS attacks
     * @param {string} text - Text to escape
     * @returns {string} Escaped HTML-safe text
     */
    escapeHtml(text) {
        if (text === null || text === undefined) return '';
        const div = document.createElement('div');
        div.textContent = String(text);
        return div.innerHTML;
    }

    /**
     * Sanitize URL to prevent XSS via javascript: or data: URLs
     * @param {string} url - URL to sanitize
     * @returns {string} Safe URL or placeholder
     */
    sanitizeUrl(url) {
        if (!url || typeof url !== 'string') return '#invalid-url';

        try {
            const parsed = new URL(url, window.location.origin);
            // Only allow http, https protocols
            if (!['http:', 'https:'].includes(parsed.protocol)) {
                ChatLogger.warn('Blocked potentially dangerous URL protocol:', { protocol: parsed.protocol });
                return '#blocked-url';
            }
            return parsed.href;
        } catch (error) {
            ChatLogger.warn('Invalid URL blocked:', { url });
            return '#invalid-url';
        }
    }

    /**
     * Sanitize filename to prevent XSS
     * @param {string} filename - Filename to sanitize
     * @returns {string} Safe filename
     */
    sanitizeFilename(filename) {
        if (!filename || typeof filename !== 'string') return 'file';
        // Remove potentially dangerous characters and escape HTML
        return this.escapeHtml(filename.replace(/[<>"'&]/g, '_'));
    }

    formatTime(timestamp) {
        const date = new Date(timestamp);
        const now = new Date();
        const diff = now - date;
        const days = Math.floor(diff / (1000 * 60 * 60 * 24));

        if (days === 0) {
            return date.toLocaleTimeString('ar-SA', { hour: '2-digit', minute: '2-digit' });
        } else if (days === 1) {
            return LABELS_AR.CHAT.YESTERDAY;
        } else if (days < 7) {
            return date.toLocaleDateString('ar-SA', { weekday: 'short' });
        } else {
            return date.toLocaleDateString('ar-SA', { month: 'short', day: 'numeric' });
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
                img.alt = this.escapeHtml(file.name);
                div.appendChild(img);
            } else {
                // Use DOM methods instead of innerHTML to prevent XSS
                const icon = document.createElement('i');
                icon.className = 'fas fa-file';
                const nameSpan = document.createElement('span');
                nameSpan.textContent = ' ' + file.name; // textContent is safe
                div.appendChild(icon);
                div.appendChild(nameSpan);
            }

            const removeBtn = document.createElement('button');
            removeBtn.className = 'remove-file';
            removeBtn.textContent = '×';
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
                    'X-CSRF-TOKEN': getCsrfToken()
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
            ChatLogger.error('Error loading messages:', error);
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
                    'X-CSRF-TOKEN': getCsrfToken()
                },
                body: JSON.stringify({
                    id: conversationId
                })
            });

            // Update unread count
            this.updateUnreadCount(conversationId, 0);
        } catch (error) {
            ChatLogger.error('Error marking conversation as read:', error);
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

    // Note: Message pagination is handled by WireChat's Livewire component ($wire.loadMore)

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
                'X-CSRF-TOKEN': getCsrfToken()
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
            element.textContent = `${count} ${LABELS_AR.CHAT.ONLINE_COUNT}`;
        }
    }

    showError(message) {
        // Use unified toast system
        if (window.toast) {
            window.toast.error(message);
        }
    }

    showNotification(message) {
        // Use unified toast system
        if (window.toast) {
            window.toast.info(message);
        }
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
                'X-CSRF-TOKEN': getCsrfToken()
            }
        });
    }

    handleNotification(notification) {
        // Handle different notification types
        switch (notification.type) {
            case 'App\\Notifications\\NewMessage':
                this.handleNewMessageNotification(notification);
                break;
            case 'App\\Notifications\\MentionNotification':
                this.handleMentionNotification(notification);
                break;
            default:
                // Unknown notification type - ignore
                break;
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
        this.showNotification(`${notification.data.sender_name} ${LABELS_AR.CHAT.MENTIONED_YOU}`);
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
        // Leave any joined channels
        this.leaveConversation();
        if (this.currentUserId) {
            window.Echo?.leave(`chat.${this.currentUserId}`);
        }

        // Remove Pusher connection handlers
        if (window.Echo?.connector?.pusher?.connection) {
            const connection = window.Echo.connector.pusher.connection;
            if (this._boundHandlers.onConnected) {
                connection.unbind('connected', this._boundHandlers.onConnected);
            }
            if (this._boundHandlers.onDisconnected) {
                connection.unbind('disconnected', this._boundHandlers.onDisconnected);
            }
            if (this._boundHandlers.onConnectionError) {
                connection.unbind('error', this._boundHandlers.onConnectionError);
            }
        }

        // Remove DOM event listeners
        if (this._elements?.messageInput) {
            if (this._boundHandlers.messageInputHandler) {
                this._elements.messageInput.removeEventListener('input', this._boundHandlers.messageInputHandler);
            }
            if (this._boundHandlers.messageKeyHandler) {
                this._elements.messageInput.removeEventListener('keypress', this._boundHandlers.messageKeyHandler);
            }
        }

        if (this._elements?.sendButton && this._boundHandlers.sendButtonHandler) {
            this._elements.sendButton.removeEventListener('click', this._boundHandlers.sendButtonHandler);
        }

        if (this._elements?.attachButton && this._boundHandlers.attachButtonHandler) {
            this._elements.attachButton.removeEventListener('click', this._boundHandlers.attachButtonHandler);
        }

        if (this._elements?.fileInput && this._boundHandlers.fileInputHandler) {
            this._elements.fileInput.removeEventListener('change', this._boundHandlers.fileInputHandler);
        }

        // Clear timers
        clearTimeout(this.typingTimer);

        // Disconnect observer
        this.readObserver?.disconnect();

        // Clear data
        this.onlineUsers.clear();
        this.messageQueue = [];

        // Clear references
        this._elements = null;
        this._boundHandlers = null;
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