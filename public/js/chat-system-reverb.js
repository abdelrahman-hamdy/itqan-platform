/**
 * Itqan Chat System - Direct Reverb WebSocket Implementation
 * No Echo, No Pusher - Pure WebSocket connection to Laravel Reverb
 */

class ChatSystem {
  constructor(config) {
    this.config = config;
    this.ws = null;
    this.connectionStatus = 'disconnected';
    this.currentContactId = null;
    this.messages = new Map();
    this.contacts = [];
    
    console.log('🚀 Initializing Chat System with Reverb WebSocket');
    console.log('📋 Config:', this.config);
    
    this.init();
  }

  init() {
    this.setupWebSocket();
    // Don't load contacts immediately - wait for WebSocket connection
    this.setupEventListeners();
    this.setupAutoResize();
  }

  /**
   * Setup direct WebSocket connection to Laravel Reverb
   */
  setupWebSocket() {
    console.log('🔧 Setting up direct Reverb WebSocket connection...');
    
    try {
      const wsUrl = `ws://127.0.0.1:8085/app/vil71wafgpp6do1miwn1?protocol=7&client=js&version=1.0.0`;
      console.log('🚀 Connecting to Reverb at:', wsUrl);
      
      this.ws = new WebSocket(wsUrl);
      
      this.ws.onopen = () => {
        console.log('✅ Reverb WebSocket connected successfully for user:', this.config.userId);
        console.log('🌐 WebSocket URL:', wsUrl);
        this.connectionStatus = 'connected';
        this.showConnectionStatus('متصل', 'success');
        
        // Don't subscribe immediately - wait for connection_established message with socket ID
      };
      
      this.ws.onmessage = (event) => {
        console.log('📨 Raw WebSocket data received:', event.data);
        try {
          const message = JSON.parse(event.data);
          console.log('📨 Parsed WebSocket message:', message);
          this.handleWebSocketMessage(message);
        } catch (e) {
          console.error('❌ Failed to parse WebSocket message:', e);
          console.log('📨 Raw message that failed to parse:', event.data);
        }
      };
      
      this.ws.onerror = (error) => {
        console.error('❌ Reverb WebSocket error:', error);
        this.connectionStatus = 'error';
        this.showConnectionStatus('خطأ في الاتصال', 'error');
      };
      
      this.ws.onclose = (event) => {
        console.log('🔌 Reverb WebSocket connection closed:', event.code, event.reason);
        this.connectionStatus = 'disconnected';
        this.showConnectionStatus('منقطع', 'error');
        
        // Attempt to reconnect after 3 seconds
        setTimeout(() => {
          console.log('🔄 Attempting to reconnect...');
          this.setupWebSocket();
        }, 3000);
      };
      
    } catch (error) {
      console.error('❌ Failed to setup Reverb WebSocket:', error);
      this.showConnectionStatus('فشل الإعداد', 'error');
    }
  }

  subscribeToPrivateChannel() {
    if (!this.socketId) {
      console.log('⚠️ No socket ID available, cannot subscribe to private channel');
      return;
    }
    
    try {
      const channelName = `private-chatify.${this.config.userId}`;
      console.log(`🔐 Subscribing to private channel: ${channelName} for user ${this.config.userId}`);
      
      // Use public channel for testing instead of private
      if (this.config.usePublicChannel) {
        const publicChannelName = `public-chatify-test`;
        const subscribeMessage = JSON.stringify({
          event: 'pusher:subscribe',
          data: {
            channel: publicChannelName
          }
        });
        
        this.ws.send(subscribeMessage);
        console.log(`✅ Subscribed to public test channel: ${publicChannelName}`);
        return;
      }
      
      // For private channels, we need to authenticate first
      const authEndpoint = this.config.authEndpoint;
      const authData = {
        socket_id: this.socketId,
        channel_name: channelName
      };
      
      console.log('🔐 Authenticating channel with data:', authData);
      console.log('🔐 Auth endpoint:', authEndpoint);
      
      fetch(authEndpoint, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
          'X-CSRF-TOKEN': this.config.csrfToken,
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'include', // Include cookies for session authentication
        body: new URLSearchParams(authData)
      })
      .then(response => {
        console.log('🔐 Auth response status:', response.status);
        console.log('🔐 Auth response headers:', Object.fromEntries(response.headers.entries()));
        
        // Clone response to read as text first for debugging
        return response.clone().text().then(text => {
          console.log('🔐 Auth response body (raw):', text);
          
          if (!response.ok) {
            throw new Error(`Auth failed with status: ${response.status}, body: ${text}`);
          }
          
          try {
            return JSON.parse(text);
          } catch (e) {
            console.error('🔐 JSON parse error. Response was:', text);
            throw new Error(`Invalid JSON response: ${text.substring(0, 200)}...`);
          }
        });
      })
      .then(data => {
        console.log('🔐 Auth response data:', data);
        
        if (data.auth) {
          const subscribeMessage = JSON.stringify({
            event: 'pusher:subscribe',
            data: {
              channel: channelName,
              auth: data.auth
            }
          });

          this.ws.send(subscribeMessage);
          console.log(`✅ Subscribed to authenticated private channel: ${channelName}`);
        } else {
          console.error('❌ No auth data received:', data);
          // Fallback to public channel if private auth fails
          console.log('🔄 Falling back to public channel');
          this.fallbackToPublicChannel();
        }
      })
      .catch(error => {
        console.error('❌ Channel authentication failed:', error);
        // Fallback to public channel if private auth fails  
        console.log('🔄 Falling back to public channel');
        this.fallbackToPublicChannel();
      });

    } catch (error) {
      console.error('❌ Authentication setup failed:', error);
      this.fallbackToPublicChannel();
    }
  }

  fallbackToPublicChannel() {
    console.log('🔄 Using fallback public channel for messaging');
    const publicChannelName = `public-chatify-test`;
    const subscribeMessage = JSON.stringify({
      event: 'pusher:subscribe',
      data: {
        channel: publicChannelName
      }
    });
    
    this.ws.send(subscribeMessage);
    console.log(`✅ Subscribed to fallback public channel: ${publicChannelName}`);
  }

  handleWebSocketMessage(message) {
    console.log('📨 [USER ' + this.config.userId + '] Handling WebSocket message event:', message.event);
    console.log('📨 [USER ' + this.config.userId + '] Message channel:', message.channel);
    console.log('📨 [USER ' + this.config.userId + '] Message data:', message.data);
    
    if (message.event === 'pusher:connection_established') {
      console.log('✅ [USER ' + this.config.userId + '] Connection established');
      
      // Store socket ID for authentication
      const data = JSON.parse(message.data);
      this.socketId = data.socket_id;
      console.log('🔗 [USER ' + this.config.userId + '] Socket ID stored:', this.socketId);
      
      // Now that we have socket ID, subscribe to private channel
      this.subscribeToPrivateChannel();
      
      return;
    }
    
    if (message.event === 'pusher_internal:subscription_succeeded') {
      console.log('✅ [USER ' + this.config.userId + '] Channel subscription successful for channel:', message.channel);
      console.log('🔔 [USER ' + this.config.userId + '] This user will receive messages on channel:', message.channel);
      
      // Store subscribed channel for debugging
      this.subscribedChannel = message.channel;
      
      // Now that we're connected and subscribed, load the chat UI
      this.initializeChatUI();
      
      return;
    }
    
    // Handle different possible message event names
    if (message.event === 'messaging' || message.event === 'message' || message.event === 'new-message') {
      console.log('📨 New chat message received via WebSocket:', message.data);
      let messageData;
      
      try {
        messageData = typeof message.data === 'string' ? JSON.parse(message.data) : message.data;
        console.log('📨 Parsed message data:', messageData);
        
        // Extract the actual message from the HTML response
        if (messageData.message && typeof messageData.message === 'string' && messageData.message.includes('message-card')) {
          console.log('📨 Received HTML message, extracting data from broadcast');
          // For HTML responses, we'll trigger a message refresh instead
          if (this.currentContactId) {
            this.loadMessages(this.currentContactId);
          }
        } else {
          this.handleIncomingMessage(messageData);
        }
      } catch (e) {
        console.error('❌ Failed to parse message data:', e);
        console.log('📨 Raw message data:', message.data);
      }
      
      return;
    }
    
    // Log unhandled events for debugging
    if (message.event && !message.event.startsWith('pusher')) {
      console.log('⚠️ Unhandled WebSocket event:', message.event, message);
    }
  }

  initializeChatUI() {
    console.log('🎨 Initializing chat UI...');
    
    // Load contacts and set up UI elements
    this.loadContacts();
    
    // Set up event listeners if not already done
    if (!this.uiInitialized) {
      this.setupEventListeners();
      this.setupAutoResize();
      this.uiInitialized = true;
    }
  }

  handleIncomingMessage(data) {
    console.log('💬 Processing incoming WebSocket message:', data);
    
    // Add message to UI for ANY user (including own messages from other tabs/devices)
    if (data.from_id && this.currentContactId) {
      // Only show if we're viewing the conversation with this contact
      const contactId = data.from_id == this.config.userId ? data.to_id : data.from_id;
      
      if (contactId == this.currentContactId) {
        // Check if message already exists to prevent duplicates
        const messageExists = document.querySelector(`[data-message-id="${data.id}"]`);
        if (!messageExists) {
          this.addMessageToUI({
            id: data.id,
            body: data.body,
            from_id: data.from_id,
            created_at: data.created_at,
            attachment: data.attachment
          }, false);
        }
      }
      
      // Update contact list for any message
      this.updateContactLastMessage(contactId, data.body);
      
      // Show notification if not in focus and not own message
      if (!document.hasFocus() && data.from_id !== this.config.userId) {
        this.showNotification('رسالة جديدة', data.body);
      }
    }
  }

  /**
   * Load contacts list
   */
  async loadContacts() {
    console.log('📞 Loading contacts...');
    console.log('📞 Contacts API URL:', this.config.apiEndpoints.contacts);
    
    try {
      const response = await fetch(this.config.apiEndpoints.contacts, {
        headers: {
          'X-CSRF-TOKEN': this.config.csrfToken,
          'Accept': 'application/json'
        }
      });
      
      console.log('📞 Contacts response status:', response.status);
      
      if (!response.ok) {
        console.error('❌ Contacts API failed:', response.status, response.statusText);
        return;
      }
      
      const data = await response.json();
      console.log('📞 Contacts data received:', data);
      
      this.contacts = data.contacts || [];
      console.log('📞 Contacts processed:', this.contacts.length, 'contacts');
      
      this.renderContacts();
      
    } catch (error) {
      console.error('❌ Failed to load contacts:', error);
      
      // Show error in UI
      const contactsList = document.querySelector('.contacts-list');
      if (contactsList) {
        contactsList.innerHTML = '<div class="error-message">فشل في تحميل جهات الاتصال</div>';
      }
    }
  }

  /**
   * Render contacts in sidebar
   */
  renderContacts() {
    console.log('🎨 Rendering contacts in UI...');
    
    // Hide loading state
    const loadingElement = document.querySelector('#contacts-loading');
    if (loadingElement) {
      loadingElement.classList.add('hidden');
    }
    
    // Get contacts list container (ID not class!)
    const contactsList = document.querySelector('#contacts-list');
    console.log('🎨 Contacts list element found:', !!contactsList);
    
    if (!contactsList) {
      console.error('❌ #contacts-list element not found in DOM!');
      return;
    }

    // Show contacts list container
    contactsList.classList.remove('hidden');
    
    // Clear and populate
    console.log('🎨 Clearing contacts list...');
    contactsList.innerHTML = '';
    
    if (this.contacts.length === 0) {
      // Show empty state
      const emptyElement = document.querySelector('#contacts-empty');
      if (emptyElement) {
        emptyElement.classList.remove('hidden');
      }
      contactsList.classList.add('hidden');
      return;
    }
    
    console.log('🎨 Processing', this.contacts.length, 'contacts...');
    this.contacts.forEach((contact, index) => {
      console.log(`🎨 Creating element for contact ${index + 1}:`, contact);
      const contactElement = this.createContactElement(contact);
      contactsList.appendChild(contactElement);
      console.log('🎨 Contact element added to DOM');
    });
    
    console.log('✅ Contacts rendering completed');
  }

  createContactElement(contact) {
    const div = document.createElement('div');
    div.className = 'contact-item p-4 hover:bg-gray-100 cursor-pointer border-b border-gray-200 flex items-center';
    div.dataset.contactId = contact.id;
    
    // Get user type specific styling
    const userType = contact.user_type || contact.type || 'student';
    const typeConfig = this.getUserTypeConfig(userType);
    const initial = contact.name ? contact.name.charAt(0).toUpperCase() : 'U';
    const statusText = contact.isOnline ? 'متصل' : this.getLastSeenText(contact);
    const statusColor = contact.isOnline ? 'text-green-600' : 'text-gray-500';
    
    div.innerHTML = `
      <div class="relative flex-shrink-0 ml-3">
        <div class="w-12 h-12 rounded-full ${typeConfig.border} overflow-hidden ${typeConfig.bg}">
          ${contact.avatar ? 
            `<img src="${contact.avatar}" alt="${contact.name}" class="w-full h-full object-cover">` : 
            `<div class="w-full h-full flex items-center justify-center ${typeConfig.text} ${typeConfig.bgFallback}">
              <span class="font-semibold text-lg">${initial}</span>
            </div>`
          }
        </div>
        <div class="absolute w-4 h-4 rounded-full ${contact.isOnline ? 'bg-green-500' : 'bg-gray-300'} border-2 border-white -bottom-0.5 -right-0.5"></div>
      </div>
      <div class="flex-1 min-w-0">
        <div class="font-semibold text-gray-900 truncate">${contact.name}</div>
        <div class="text-sm ${statusColor} truncate">${this.getLastMessageText(contact)}</div>
      </div>
      <div class="flex-shrink-0 text-right">
        <div class="text-xs ${statusColor}">${statusText}</div>
      </div>
    `;
    
    div.addEventListener('click', () => this.selectContact(contact.id));
    
    return div;
  }

  /**
   * Get user type specific configuration for styling
   */
  getUserTypeConfig(userType) {
    const typeConfig = {
      'quran_teacher': {
        border: 'border-blue-200',
        bg: 'bg-blue-50',
        text: 'text-blue-600',
        bgFallback: 'bg-blue-100'
      },
      'academic_teacher': {
        border: 'border-green-200',
        bg: 'bg-green-50',
        text: 'text-green-600',
        bgFallback: 'bg-green-100'
      },
      'parent': {
        border: 'border-purple-200',
        bg: 'bg-purple-50',
        text: 'text-purple-600',
        bgFallback: 'bg-purple-100'
      },
      'supervisor': {
        border: 'border-orange-200',
        bg: 'bg-orange-50',
        text: 'text-orange-600',
        bgFallback: 'bg-orange-100'
      },
      'academy_admin': {
        border: 'border-red-200',
        bg: 'bg-red-50',
        text: 'text-red-600',
        bgFallback: 'bg-red-100'
      },
      'admin': {
        border: 'border-red-200',
        bg: 'bg-red-50',
        text: 'text-red-600',
        bgFallback: 'bg-red-100'
      }
    };
    
    return typeConfig[userType] || {
      border: 'border-gray-200',
      bg: 'bg-gray-100',
      text: 'text-gray-600',
      bgFallback: 'bg-gray-200'
    };
  }

  /**
   * Get formatted last seen text
   */
  getLastSeenText(contact) {
    if (contact.isOnline) return 'متصل';
    if (contact.lastSeen) {
      const lastSeen = new Date(contact.lastSeen);
      const now = new Date();
      const diffHours = Math.floor((now - lastSeen) / (1000 * 60 * 60));
      
      if (diffHours < 1) return 'منذ قليل';
      if (diffHours < 24) return `منذ ${diffHours} ساعة`;
      return `منذ ${Math.floor(diffHours / 24)} يوم`;
    }
    return 'غير متصل';
  }

  /**
   * Select a contact and load conversation
   */
  async selectContact(contactId) {
    this.currentContactId = contactId;
    
    // Find the selected contact data
    const selectedContactData = this.contacts.find(c => c.id == contactId);
    
    // Update UI
    document.querySelectorAll('.contact-item').forEach(item => {
      item.classList.remove('active');
    });
    
    const selectedContact = document.querySelector(`[data-contact-id="${contactId}"]`);
    if (selectedContact) {
      selectedContact.classList.add('active');
    }
    
    // Update chat header with contact information
    this.updateChatHeader(selectedContactData);
    
    // Load messages for this contact
    await this.loadMessages(contactId);
  }

  /**
   * Update chat header with selected contact information
   */
  updateChatHeader(contact) {
    if (!contact) return;
    
    console.log('📋 Updating chat header for contact:', contact);
    
    // Update contact name
    const contactNameElement = document.getElementById('chat-contact-name');
    if (contactNameElement) {
      contactNameElement.textContent = contact.name || 'مستخدم';
    }
    
    // Update contact avatar with better styling
    const contactAvatarElement = document.getElementById('chat-contact-avatar');
    if (contactAvatarElement) {
      const initial = contact.name ? contact.name.charAt(0).toUpperCase() : 'U';
      const userType = contact.user_type || contact.type || 'student';
      const typeConfig = this.getUserTypeConfig(userType);
      
      // Update the parent container styling
      const avatarContainer = contactAvatarElement.parentElement;
      if (avatarContainer) {
        avatarContainer.className = `w-10 h-10 rounded-full ${typeConfig.border} overflow-hidden ${typeConfig.bg} flex items-center justify-center text-white font-medium mr-3 flex-shrink-0`;
        
        if (contact.avatar) {
          avatarContainer.innerHTML = `<img src="${contact.avatar}" alt="${contact.name}" class="w-full h-full object-cover">`;
        } else {
          avatarContainer.innerHTML = `<span class="font-semibold ${typeConfig.text}">${initial}</span>`;
          avatarContainer.className = `w-10 h-10 rounded-full ${typeConfig.border} overflow-hidden ${typeConfig.bgFallback} flex items-center justify-center font-medium mr-3 flex-shrink-0`;
        }
      }
    }
    
    // Update contact status with better formatting
    const contactStatusElement = document.getElementById('chat-contact-status');
    if (contactStatusElement) {
      const statusText = contact.isOnline ? 'متصل' : this.getLastSeenText(contact);
      const statusClass = contact.isOnline ? 'text-green-600' : 'text-gray-500';
      contactStatusElement.textContent = statusText;
      contactStatusElement.className = `text-sm ${statusClass} truncate`;
    }
    
    console.log('✅ Chat header updated successfully');
  }

  /**
   * Load messages for a specific contact
   */
  async loadMessages(contactId) {
    try {
      console.log('💬 Loading messages for contact:', contactId);
      console.log('💬 Fetch endpoint:', this.config.apiEndpoints.fetchMessages);
      
      const formData = new FormData();
      formData.append('id', contactId);
      
      const response = await fetch(this.config.apiEndpoints.fetchMessages, {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': this.config.csrfToken,
          'Accept': 'application/json'
        },
        body: formData
      });
      
      console.log('💬 Messages response status:', response.status);
      
      if (!response.ok) {
        console.error('❌ Failed to fetch messages:', response.status, response.statusText);
        return;
      }
      
      const data = await response.json();
      console.log('💬 Messages data:', data);
      
      this.messages.set(contactId, data.messages || []);
      this.renderMessages(contactId);
      
    } catch (error) {
      console.error('❌ Failed to load messages:', error);
    }
  }

  /**
   * Show messages for selected contact
   */
  showMessages(contactId) {
    console.log('📖 Showing messages for contact:', contactId);
    this.currentContactId = contactId;
    
    // Show loading state and hide messages with animation
    const messagesList = document.getElementById('messages-list');
    const messagesLoading = document.getElementById('messages-loading');
    
    messagesList.classList.add('opacity-0', 'translate-y-2');
    messagesList.style.display = 'none';
    messagesLoading.style.display = 'flex';
    
    // Load messages for this contact
    this.loadMessages(contactId);
  }

  /**
   * Render messages in chat area
   */
  renderMessages(contactId) {
    console.log('🎬 Rendering messages for contact:', contactId);
    
    // Show active chat area, hide empty state
    const emptyState = document.querySelector('#chat-empty-state');
    const activeChat = document.querySelector('#active-chat');
    
    if (emptyState) emptyState.classList.add('hidden');
    if (activeChat) activeChat.classList.remove('hidden');
    
    // Hide messages loading, show messages list with animation
    const messagesLoadingEl = document.querySelector('#messages-loading');
    const messagesListEl = document.querySelector('#messages-list');
    
    if (messagesLoadingEl) messagesLoadingEl.style.display = 'none';
    if (messagesListEl) {
      messagesListEl.classList.remove('hidden', 'opacity-0', 'translate-y-2');
      messagesListEl.classList.add('opacity-100', 'translate-y-0');
      messagesListEl.style.display = 'block';
    }

    if (!messagesListEl) {
      console.error('❌ #messages-list element not found!');
      return;
    }

    const messages = this.messages.get(contactId) || [];
    messagesListEl.innerHTML = '';
    
    messages.forEach((message, index) => {
      const messageElement = this.createMessageElement(message);
      messagesListEl.appendChild(messageElement);
      
      // Add staggered animation for each message
      setTimeout(() => {
        messageElement.classList.add('animate-in');
      }, index * 50 + 10); // 50ms delay between each message
    });
    
    // Scroll after all animations start
    setTimeout(() => {
      this.scrollToBottom();
    }, messages.length * 50 + 200);
  }

  createMessageElement(message) {
    const div = document.createElement('div');
    const isOwn = message.from_id == this.config.userId;
    
    div.className = `message-card ${isOwn ? 'mc-sender' : 'mc-receiver'}`;
    div.setAttribute('data-message-id', message.id);
    
    div.innerHTML = `
      <div class="message-card-content">
        ${this.escapeHtml(message.body)}
        <div class="message-time">${this.formatTime(message.created_at)}</div>
      </div>
    `;
    
    return div;
  }

  /**
   * Send a new message
   */
  async sendMessage(messageText) {
    if (!messageText.trim() || !this.currentContactId) return;
    
    // Prevent double-sending
    if (this.sendingMessage) {
      console.log('⚠️ Message already being sent, ignoring duplicate request');
      return;
    }
    
    this.sendingMessage = true;
    console.log('📤 Sending message:', messageText);
    
    try {
      const formData = new FormData();
      formData.append('id', this.currentContactId);
      formData.append('message', messageText);
      
      const response = await fetch(this.config.apiEndpoints.sendMessage, {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': this.config.csrfToken,
          'Accept': 'application/json'
        },
        body: formData
      });
      
      const data = await response.json();
      console.log('📤 Send response status:', response.status);
      console.log('📤 Send response data:', data);
      
      // Check for different success indicators
      if (data.status === 'success' || data.status === '200' || response.ok) {
        console.log('✅ Message sent successfully');
        
        // Add message to sender's UI immediately (fallback for WebSocket)
        this.addMessageToUI({
          id: Date.now(),
          body: messageText,
          from_id: this.config.userId,
          created_at: new Date().toISOString()
        });
        
        // Clear input
        const messageInput = document.querySelector('#message-input');
        if (messageInput) {
          messageInput.value = '';
          messageInput.style.height = 'auto';
        }
        
        // Update send button state
        const sendButton = document.querySelector('#send-btn');
        if (sendButton) {
          sendButton.disabled = true;
        }
      } else {
        console.error('❌ Message send failed:', data);
        
        // Show error details if available
        if (data.error) {
          console.error('❌ Error details:', data.error);
        }
        
        // Re-enable interface
        const sendButton = document.querySelector('#send-btn');
        if (sendButton) {
          sendButton.disabled = false;
        }
      }
      
    } catch (error) {
      console.error('❌ Failed to send message:', error);
    } finally {
      this.sendingMessage = false;
    }
  }

  addMessageToUI(message, isOwn) {
    console.log('📝 Adding message to UI:', message);
    const messagesList = document.querySelector('#messages-list');
    const messagesLoading = document.querySelector('#messages-loading');
    
    if (!messagesList) {
      console.error('❌ Messages list not found');
      return;
    }

    // Show messages list and hide loading
    messagesList.classList.remove('hidden');
    if (messagesLoading) {
      messagesLoading.classList.add('hidden');
    }

    // Check for duplicates
    const existingMessage = messagesList.querySelector(`[data-message-id="${message.id}"]`);
    if (existingMessage) {
      console.log('⚠️ Message already exists, skipping:', message.id);
      return;
    }

    const messageElement = this.createMessageElement({
      ...message,
      from_id: isOwn ? this.config.userId : message.from_id
    });

    messagesList.appendChild(messageElement);
    
    // Add smooth animation
    setTimeout(() => {
      messageElement.classList.add('animate-in');
    }, 10);
    
    console.log('✅ Message added to UI successfully');
    
    // Scroll after a brief delay to allow animation
    setTimeout(() => {
      this.scrollToBottom();
    }, 100);
  }

  /**
   * Setup event listeners
   */
  setupEventListeners() {
    // Send message on Enter key
    const messageInput = document.querySelector('#message-input');
    const sendButton = document.querySelector('#send-btn');
    
    if (messageInput) {
      // Enable/disable send button based on input content
      const updateSendButton = () => {
        if (sendButton) {
          const hasText = messageInput.value.trim().length > 0;
          sendButton.disabled = !hasText;
        }
      };
      
      messageInput.addEventListener('input', updateSendButton);
      messageInput.addEventListener('keyup', updateSendButton);
      messageInput.addEventListener('paste', () => {
        setTimeout(updateSendButton, 10); // Allow paste to complete
      });
      
      messageInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
          e.preventDefault();
          const text = messageInput.value.trim();
          if (text) {
            this.sendMessage(text);
          }
        }
      });
    }
    
    // Send button click
    if (sendButton) {
      sendButton.addEventListener('click', (e) => {
        e.preventDefault();
        const input = document.querySelector('#message-input');
        if (input) {
          const text = input.value.trim();
          if (text) {
            this.sendMessage(text);
          }
        }
      });
    }
    
    // File attachment button
    const attachmentBtn = document.querySelector('#attachment-btn');
    const fileInput = document.querySelector('#file-input');
    if (attachmentBtn && fileInput) {
      attachmentBtn.addEventListener('click', () => {
        fileInput.click();
      });
      
      fileInput.addEventListener('change', (e) => {
        const file = e.target.files[0];
        if (file) {
          console.log('📎 File selected:', file.name);
          // TODO: Implement file upload
        }
      });
    }

    // Search functionality
    const searchInput = document.querySelector('#search-input');
    if (searchInput) {
      searchInput.addEventListener('input', (e) => {
        this.searchContacts(e.target.value);
      });
    }
  }

  /**
   * Filter contacts based on search query
   */
  searchContacts(query) {
    const contactsList = document.getElementById('contacts-list');
    if (!contactsList) return;

    const contactItems = contactsList.querySelectorAll('.contact-item, [data-contact-id], .p-3, .cursor-pointer');
    const searchQuery = query.toLowerCase().trim();

    contactItems.forEach(item => {
      const name = item.querySelector('.contact-name, .font-medium, .text-gray-900, .font-semibold')?.textContent.toLowerCase() || '';
      const lastMessage = item.querySelector('.last-message, .text-sm, .text-gray-500, .text-gray-600')?.textContent.toLowerCase() || '';
      
      if (searchQuery === '' || name.includes(searchQuery) || lastMessage.includes(searchQuery)) {
        item.style.display = '';
        item.classList.remove('hidden');
      } else {
        item.style.display = 'none';
        item.classList.add('hidden');
      }
    });

    // Show/hide empty state based on visible items
    const visibleItems = contactsList.querySelectorAll('.contact-item:not(.hidden), [data-contact-id]:not(.hidden), .p-3:not(.hidden)');
    const emptyState = document.getElementById('contacts-empty');
    
    if (visibleItems.length === 0 && searchQuery !== '') {
      if (emptyState) {
        emptyState.classList.remove('hidden');
        emptyState.querySelector('p').textContent = 'لا توجد نتائج للبحث';
      }
    } else {
      if (emptyState) {
        emptyState.classList.add('hidden');
        emptyState.querySelector('p').textContent = 'لا توجد جهات اتصال';
      }
    }
  }

  /**
   * Show connection status
   */
  showConnectionStatus(message, type) {
    const statusElement = document.querySelector('.connection-status');
    if (statusElement) {
      statusElement.textContent = message;
      statusElement.className = `connection-status ${type}`;
    }
  }

  /**
   * Utility functions
   */
  escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }

  formatTime(timestamp) {
    const date = new Date(timestamp);
    return date.toLocaleTimeString('ar-EG', {
      hour: '2-digit',
      minute: '2-digit'
    });
  }

  scrollToBottom() {
    const messagesContainer = document.querySelector('#messages-container');
    const messagesList = document.querySelector('#messages-list');
    
    // Try to scroll the messages list first, then fallback to container
    if (messagesList) {
      messagesList.scrollTop = messagesList.scrollHeight;
    } else if (messagesContainer) {
      messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }
  }

  setupAutoResize() {
    const messageInput = document.querySelector('#message-input');
    if (messageInput) {
      messageInput.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 120) + 'px';
      });
    }
  }

  getLastMessageText(contact) {
    if (!contact.lastMessage) {
      return 'لا توجد رسائل';
    }
    
    // Handle different message formats
    if (typeof contact.lastMessage === 'string') {
      return contact.lastMessage;
    }
    
    if (typeof contact.lastMessage === 'object' && contact.lastMessage.body) {
      return contact.lastMessage.body;
    }
    
    return 'لا توجد رسائل';
  }

  updateContactLastMessage(contactId, message) {
    const contact = this.contacts.find(c => c.id == contactId);
    if (contact) {
      contact.lastMessage = message;
      this.renderContacts();
    }
  }

  showNotification(title, body) {
    if ('Notification' in window && Notification.permission === 'granted') {
      new Notification(title, {
        body: body,
        icon: '/images/itqan-logo.svg'
      });
    }
  }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
  // Check if chat config is available
  if (typeof window.chatConfig !== 'undefined') {
    console.log('🚀 Starting Reverb Chat System...');
    window.chatSystem = new ChatSystem(window.chatConfig);
  } else {
    console.log('⚠️ Chat config not found, waiting for initialization...');
  }
});

// Export for manual initialization
window.ChatSystem = ChatSystem;
