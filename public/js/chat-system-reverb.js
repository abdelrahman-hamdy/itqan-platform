/**
 * Itqan Chat System - Direct Reverb WebSocket Implementation
 * No Echo, No Pusher - Pure WebSocket connection to Laravel Reverb
 */

class ChatSystem {
  constructor() {
    this.config = window.chatConfig || {};
    this.ws = null;
    this.connectionStatus = 'disconnected';
    this.currentContactId = null;
    this.messages = new Map();
    this.contacts = [];
    
    // Infinite scroll state
    this.isLoadingMessages = false;
    this.currentPage = new Map(); // Track current page for each contact
    this.hasMoreMessages = new Map(); // Track if more messages available for each contact
    this.lastMessageId = new Map(); // Track last message ID for each contact
    
    console.log('🚀 Initializing Chat System with Reverb WebSocket');
    console.log('📋 Config:', this.config);
    
    this.init();
  }

  init() {
    this.setupWebSocket();
    // Load contacts immediately - don't wait for WebSocket connection
    this.loadContacts();
    this.setupEventListeners();
    this.setupAutoResize();
    this.setupInfiniteScroll();
    this.checkForAutoOpenChat();
  }

  /**
   * Check if there's a user ID in the URL to automatically open a chat
   */
  checkForAutoOpenChat() {
    // Check for user ID in query parameter first
    const urlParams = new URLSearchParams(window.location.search);
    let userId = urlParams.get('user');
    
    // If not in URL, check config for autoOpenUserId
    if (!userId && this.config.autoOpenUserId) {
      userId = this.config.autoOpenUserId;
    }
    
    if (userId && !isNaN(userId)) {
      console.log('🚀 Auto-opening chat with user ID:', userId);
      setTimeout(() => {
        this.openChatWithUser(userId);
      }, 1000);
    }
  }

  /**
   * Open chat with a specific user by ID
   */
  async openChatWithUser(userId) {
    try {
      console.log('🔄 Opening chat with user ID:', userId);
      
      // First, try to find the user in existing contacts
      const existingContact = this.contacts.find(c => c.id == userId);
      if (existingContact) {
        console.log('✅ Found user in contacts, opening chat:', existingContact.name);
        this.openChat(existingContact);
        return;
      }
      
      // If not found in contacts, fetch user data directly
      console.log('🔍 User not in contacts, fetching user data...');
      const response = await fetch('/chat/idInfo', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': this.config.csrfToken
        },
        body: JSON.stringify({
          id: userId,
          type: 'user'
        })
      });

      if (response.ok) {
        const data = await response.json();
        if (!data.error && data.user) {
          console.log('✅ Fetched user data, opening chat:', data.user.name);
          this.openChat(data.user);
        } else {
          console.error('❌ Cannot message this user:', data.message);
          this.showNotification(data.message || 'غير مسموح لك بمراسلة هذا المستخدم', 'error');
        }
      } else {
        console.error('❌ Failed to fetch user data:', response.status);
        this.showNotification('فشل في العثور على المستخدم', 'error');
      }
    } catch (error) {
      console.error('❌ Error opening chat with user:', error);
      this.showNotification('حدث خطأ في فتح المحادثة', 'error');
    }
  }

  /**
   * Setup direct WebSocket connection to Laravel Reverb
   */
  setupWebSocket() {
    console.log('🔧 Setting up direct Reverb WebSocket connection...');
    
    try {
      const wsUrl = `ws://127.0.0.1:8085/app/vil71wafgpp6do1miwn1?protocol=7&client=js&version=1.0.0`;
      console.log('🚀 Chat System Reverb Loaded - FIXED SCROLL VERSION');
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
      const channelName = `private-chat.${this.config.userId}`;
      console.log(`🔐 Subscribing to private channel: ${channelName} for user ${this.config.userId}`);
      
      // Use public channel for testing instead of private
      if (this.config.usePublicChannel) {
        const publicChannelName = `public-chat-test`;
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
    const publicChannelName = `public-chat-test`;
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
      console.log('📨 New chat message received via WebSocket');
      let messageData;
      
      try {
        messageData = typeof message.data === 'string' ? JSON.parse(message.data) : message.data;
        
        // Extract the actual message from the HTML response
        if (messageData.message && typeof messageData.message === 'string' && messageData.message.includes('message-card')) {
          // For HTML responses, we'll trigger a message refresh instead
          if (this.currentContactId) {
            this.loadMessages(this.currentContactId);
          }
          // Always refresh contacts list for sidebar updates
          console.log('📋 Refreshing contacts for sidebar after HTML message');
          this.loadContacts();
        } else {
          // Handle structured message data
          console.log('📨 Handling structured message data for sidebar');
          this.handleIncomingMessage(messageData);
        }
      } catch (e) {
        console.error('❌ Failed to parse message data:', e);
        // Still refresh contacts on error
        this.loadContacts();
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
    
    if (!data.from_id) {
      console.warn('⚠️ Received message without from_id:', data);
      return;
    }
    
    // Determine the contact ID (the other person in the conversation)
    const contactId = data.from_id == this.config.userId ? data.to_id : data.from_id;
    
    // Add message to UI if we're viewing the conversation with this contact
    if (this.currentContactId && contactId == this.currentContactId) {
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
    
    // Always update contact list for any message
    this.updateContactLastMessage(contactId, data.body);
    
    // Show notification if not in focus and not own message
    if (!document.hasFocus() && data.from_id !== this.config.userId) {
      this.showNotification('رسالة جديدة', data.body);
    }
    
    // Trigger custom event for top bar updates
    window.dispatchEvent(new CustomEvent('new-message-received', { 
      detail: { 
        fromId: data.from_id, 
        toId: data.to_id,
        message: data.body 
      } 
    }));
  }

  /**
   * Load contacts list
   */
  async loadContacts() {
    console.log('📞 Loading contacts...');
    console.log('📞 Contacts API URL:', this.config.apiEndpoints.contacts);
    
    try {
      const response = await fetch(this.config.apiEndpoints.contacts, {
        method: 'GET',
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        credentials: 'same-origin'
      });

      const data = await response.json();
      console.log('📞 Contacts API response:', data);
      
      // Map contacts and fix property name mismatch (backend 'unseen' -> frontend 'unreadCount')
      this.contacts = (data.contacts || []).map(contact => ({
        ...contact,
        unreadCount: contact.unseen || 0  // Map 'unseen' to 'unreadCount'
      }));
      console.log('📞 Contacts processed:', this.contacts.length, 'contacts');
      
      this.renderContacts();
      
    } catch (error) {
      console.error('❌ Failed to load contacts:', error);
      
      // Show error in UI
      const contactsList = document.querySelector('#contacts-list');
      if (contactsList) {
        contactsList.innerHTML = '<div class="error-message p-4 text-center text-red-600">فشل في تحميل جهات الاتصال</div>';
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
        ${contact.unreadCount && contact.unreadCount > 0 ? 
          `<div class="mt-1 bg-blue-600 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center">
            ${contact.unreadCount > 9 ? '9+' : contact.unreadCount}
          </div>` : ''
        }
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
    console.log('🎯 FIRST-TIME CHAT CLICK: selectContact called for:', contactId);
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
    
    // FORCE IMMEDIATE BOTTOM POSITION BEFORE LOADING
    const messagesListEl = document.querySelector('#messages-list');
    if (messagesListEl) {
      console.log('🚨 FORCING INITIAL SCROLL TO BOTTOM BEFORE MESSAGE LOAD');
      messagesListEl.scrollTop = messagesListEl.scrollHeight;
    }
    
    // Load messages for this contact
    await this.loadMessages(contactId);
    
    // Mark all messages from this contact as read
    await this.markMessagesAsRead(contactId);
    
    // Clear unread count for selected contact (after marking as read)
    if (selectedContactData && selectedContactData.unreadCount > 0) {
      console.log('📋 Clearing unread count for contact:', selectedContactData.name);
      selectedContactData.unreadCount = 0;
      // Re-render to update the UI without unread badge
      this.renderContacts();
    }
  }

  /**
   * Mark all messages from a contact as read
   */
  async markMessagesAsRead(contactId) {
    try {
      console.log('📖 Marking messages as read for contact:', contactId);
      
      const formData = new FormData();
      formData.append('id', contactId);
      
      const response = await fetch('/chat/api/makeSeen', {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': this.config.csrfToken,
          'Accept': 'application/json'
        },
        body: formData
      });
      
      if (!response.ok) {
        console.error('❌ Failed to mark messages as read:', response.status, response.statusText);
        return false;
      }
      
      const data = await response.json();
      console.log('✅ Messages marked as read successfully:', data);
      
      // Trigger unread count update in top bar
      window.dispatchEvent(new CustomEvent('messages-marked-read', {
        detail: { contactId: contactId }
      }));
      
      return true;
      
    } catch (error) {
      console.error('❌ Error marking messages as read:', error);
      return false;
    }
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
  async loadMessages(contactId, page = 1, prepend = false) {
    try {
      console.log('💬 Loading messages for contact:', contactId, 'page:', page);
      console.log('💬 Fetch endpoint:', this.config.apiEndpoints.fetchMessages);
      
      const formData = new FormData();
      formData.append('id', contactId);
      formData.append('page', page);
      formData.append('per_page', 30); // Standard pagination size
      
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
      
      // Initialize pagination state for this contact
      if (!this.currentPage.has(contactId)) {
        this.currentPage.set(contactId, 1);
        this.hasMoreMessages.set(contactId, true);
      }
      
      // Update pagination state
      this.currentPage.set(contactId, page);
      this.hasMoreMessages.set(contactId, page < data.last_page);
      this.lastMessageId.set(contactId, data.last_message_id);
      
      console.log(`📊 Pagination state for contact ${contactId}:`, {
        currentPage: page,
        lastPage: data.last_page,
        hasMore: page < data.last_page,
        totalMessages: data.total
      });
      
      if (prepend) {
        // Prepend older messages to beginning of array
        const existingMessages = this.messages.get(contactId) || [];
        this.messages.set(contactId, [...(data.messages || []), ...existingMessages]);
      } else {
        // Replace messages (initial load)
        this.messages.set(contactId, data.messages || []);
      }
      
      this.renderMessages(contactId, prepend);
      
    } catch (error) {
      console.error('❌ Failed to load messages:', error);
    }
  }

  /**
   * Setup infinite scroll for messages container
   */
  setupInfiniteScroll() {
    // Use event delegation since messages-list might not exist yet
    document.addEventListener('scroll', (e) => {
      const messagesList = document.getElementById('messages-list');
      if (e.target === messagesList) {
        // Check if user scrolled to top (with small threshold)
        if (messagesList.scrollTop <= 10) {
          console.log('🔝 Scrolled to top, loading older messages...');
          this.loadOlderMessages();
        }
        
        // Handle scroll-to-bottom button visibility
        this.handleScrollToBottomButton(messagesList);
      }
    }, true); // Use capture phase

    // Setup scroll-to-bottom button click handler
    this.setupScrollToBottomButton();

    console.log('🔄 Infinite scroll setup completed with event delegation');
  }

  /**
   * Setup scroll-to-bottom button functionality
   */
  setupScrollToBottomButton() {
    const scrollButton = document.getElementById('scroll-to-bottom');
    if (scrollButton) {
      scrollButton.addEventListener('click', () => {
        this.scrollToBottomSmooth();
      });
    }
  }

  /**
   * Handle scroll-to-bottom button visibility
   */
  handleScrollToBottomButton(messagesList) {
    if (!messagesList) return;
    
    const scrollButton = document.getElementById('scroll-to-bottom');
    if (!scrollButton) return;
    
    const isNearBottom = messagesList.scrollHeight - messagesList.scrollTop - messagesList.clientHeight < 100;
    
    if (isNearBottom) {
      scrollButton.classList.add('hidden');
    } else {
      scrollButton.classList.remove('hidden');
    }
  }

  /**
   * Scroll to bottom with smooth animation (ONLY when user clicks button or new message)
   */
  scrollToBottomSmooth() {
    const messagesList = document.getElementById('messages-list');
    if (messagesList) {
      messagesList.scrollTo({
        top: messagesList.scrollHeight,
        behavior: 'smooth'
      });
    }
  }

  /**
   * Load older messages when user scrolls to top
   */
  async loadOlderMessages() {
    if (!this.currentContactId || this.isLoadingMessages) {
      return;
    }

    const hasMore = this.hasMoreMessages.get(this.currentContactId);
    if (hasMore === false) {
      console.log('📝 No more messages to load for contact:', this.currentContactId);
      return;
    }

    this.isLoadingMessages = true;
    const currentPage = this.currentPage.get(this.currentContactId) || 1;
    const nextPage = currentPage + 1;

    console.log('📜 Loading older messages - Page:', nextPage);

    // Show loading indicator at top
    this.showLoadingIndicator(true);

    try {
      await this.loadMessages(this.currentContactId, nextPage, true);
      console.log('✅ Older messages loaded successfully');
    } catch (error) {
      console.error('❌ Failed to load older messages:', error);
      this.isLoadingMessages = false;
    } finally {
      this.showLoadingIndicator(false);
    }
  }

  /**
   * Show/hide loading indicator at top of messages
   */
  showLoadingIndicator(show) {
    let indicator = document.getElementById('messages-loading-top');
    
    if (!indicator && show) {
      // Create loading indicator
      indicator = document.createElement('div');
      indicator.id = 'messages-loading-top';
      indicator.className = 'flex items-center justify-center py-3 text-gray-500';
      indicator.innerHTML = `
        <div class="flex items-center space-x-2 space-x-reverse">
          <i class="ri-loader-2-line animate-spin text-blue-600"></i>
          <span class="text-sm">جارٍ تحميل الرسائل القديمة...</span>
        </div>
      `;
      
      const messagesList = document.getElementById('messages-list');
      if (messagesList) {
        messagesList.prepend(indicator);
      }
    }
    
    if (indicator) {
      indicator.style.display = show ? 'flex' : 'none';
      if (!show) {
        setTimeout(() => {
          indicator.remove();
        }, 300);
      }
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
   * Render messages in chat area - REDESIGNED FOR INSTANT BOTTOM POSITIONING
   */
  renderMessages(contactId, prepend = false) {
    console.log('🎬 Rendering messages for contact:', contactId, 'prepend:', prepend);
    
    const emptyState = document.querySelector('#chat-empty-state');
    const activeChat = document.querySelector('#active-chat');
    const messagesLoadingEl = document.querySelector('#messages-loading');
    const messagesListEl = document.querySelector('#messages-list');

    if (!messagesListEl) {
      console.error('❌ #messages-list element not found!');
      return;
    }

    // Show chat UI instantly
    if (emptyState) emptyState.classList.add('hidden');
    if (activeChat) activeChat.classList.remove('hidden');
    if (messagesLoadingEl) messagesLoadingEl.style.display = 'none';
    
    // Make messages list visible IMMEDIATELY with no transitions
    messagesListEl.classList.remove('hidden');
    messagesListEl.style.display = 'block';
    messagesListEl.style.opacity = '1';
    messagesListEl.style.transform = 'none';
    messagesListEl.style.transition = 'none';
    messagesListEl.style.scrollBehavior = 'auto';
    
    // FORCE IMMEDIATE SCROLL TO BOTTOM AFTER VISIBILITY CHANGE
    messagesListEl.scrollTop = messagesListEl.scrollHeight;

    const messages = this.messages.get(contactId) || [];
    
    if (prepend) {
      // Infinite loading - preserve scroll position
      const scrollBefore = messagesListEl.scrollTop;
      const heightBefore = messagesListEl.scrollHeight;
      
      messagesListEl.innerHTML = '';
      messages.forEach(message => {
        const messageElement = this.createMessageElement(message);
        messageElement.classList.add('animate-in'); // Show immediately
        messagesListEl.appendChild(messageElement);
      });
      
      // Restore scroll position instantly
      const heightAfter = messagesListEl.scrollHeight;
      messagesListEl.scrollTop = scrollBefore + (heightAfter - heightBefore);
      this.isLoadingMessages = false;
      
    } else {
      // Initial load - INSTANT BOTTOM POSITIONING
      messagesListEl.innerHTML = '';
      
      // Add all messages without any delays
      messages.forEach(message => {
        const messageElement = this.createMessageElement(message);
        messageElement.classList.add('animate-in'); // Show immediately
        messagesListEl.appendChild(messageElement);
      });
      
      // MULTIPLE AGGRESSIVE SCROLL ATTEMPTS FOR FIRST LOAD
      console.log('🔥 INITIAL LOAD: Multiple scroll attempts starting...');
      messagesListEl.scrollTop = messagesListEl.scrollHeight;
      
      // Immediate next-frame attempt
      requestAnimationFrame(() => {
        messagesListEl.scrollTop = messagesListEl.scrollHeight;
        console.log('🔄 Frame 1 - Position:', messagesListEl.scrollTop, 'Height:', messagesListEl.scrollHeight);
        
        // Double frame attempt
        requestAnimationFrame(() => {
          messagesListEl.scrollTop = messagesListEl.scrollHeight;
          console.log('🔄 Frame 2 - Position:', messagesListEl.scrollTop, 'Height:', messagesListEl.scrollHeight);
        });
      });
      
      // Also use timeout-based attempts
      this.scrollToBottomInstant(messagesListEl);
      console.log('✅ INITIAL SCROLL POSITION SET TO:', messagesListEl.scrollTop, 'HEIGHT:', messagesListEl.scrollHeight);
    }
  }
  
  /**
   * Scroll to bottom instantly without any animation or delay
   */
  scrollToBottomInstant(messagesListEl) {
    if (!messagesListEl) {
      messagesListEl = document.getElementById('messages-list');
    }
    if (messagesListEl) {
      // Force layout calculation and multiple scroll attempts
      messagesListEl.scrollTop = messagesListEl.scrollHeight;
      
      // Multiple immediate attempts to ensure full bottom positioning
      setTimeout(() => {
        messagesListEl.scrollTop = messagesListEl.scrollHeight;
        console.log('🔄 Retry 1 - Position:', messagesListEl.scrollTop, 'Height:', messagesListEl.scrollHeight);
      }, 0);
      
      setTimeout(() => {
        messagesListEl.scrollTop = messagesListEl.scrollHeight;
        console.log('🔄 Retry 2 - Position:', messagesListEl.scrollTop, 'Height:', messagesListEl.scrollHeight);
      }, 1);
      
      setTimeout(() => {
        messagesListEl.scrollTop = messagesListEl.scrollHeight;
        console.log('🔄 Final - Position:', messagesListEl.scrollTop, 'Height:', messagesListEl.scrollHeight);
      }, 10);
    }
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
    
    // Add smooth animation for NEW messages only
    setTimeout(() => {
      messageElement.classList.add('new-message-animation');
    }, 10);
    
    console.log('✅ Message added to UI successfully');
    
    // Only use smooth scroll for NEW messages, not initial load
    setTimeout(() => {
      this.scrollToBottomSmooth();
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

  showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg transform transition-all duration-300 translate-x-full`;
    
    const colors = {
      info: 'bg-blue-500 text-white',
      success: 'bg-green-500 text-white', 
      error: 'bg-red-500 text-white',
      warning: 'bg-yellow-500 text-white'
    };
    
    notification.className += ` ${colors[type] || colors.info}`;
    
    notification.innerHTML = `
      <div class="flex items-center gap-3">
        <span>${message}</span>
        <button onclick="this.parentElement.parentElement.remove()" class="text-white opacity-70 hover:opacity-100">
          <i class="ri-close-line"></i>
        </button>
      </div>
    `;
    
    document.body.appendChild(notification);
    
    // Show notification
    setTimeout(() => {
      notification.classList.remove('translate-x-full');
    }, 100);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
      notification.classList.add('translate-x-full');
      setTimeout(() => notification.remove(), 300);
    }, 5000);
  }

  updateContactLastMessage(contactId, message) {
    console.log('📋 Updating contact last message for ID:', contactId, 'Message:', message);
    
    const contact = this.contacts.find(c => c.id == contactId);
    if (contact) {
      console.log('✅ Found contact to update:', contact.name);
      
      // Update contact data
      contact.lastMessage = message;
      contact.lastMessageTime = new Date().toISOString();
      
      // Update unread count if not currently viewing this contact
      if (this.currentContactId != contactId) {
        contact.unreadCount = (contact.unreadCount || 0) + 1;
        console.log('📈 Increased unread count for', contact.name, 'to:', contact.unreadCount);
      }
      
      // Move contact to top of list for recent activity
      const contactIndex = this.contacts.indexOf(contact);
      this.contacts.splice(contactIndex, 1);
      this.contacts.unshift(contact);
      
      // Re-render contacts to show updates
      console.log('🔄 Re-rendering contacts after message update');
      this.renderContacts();
      
      // Force a UI refresh to ensure changes are visible
      setTimeout(() => {
        console.log('🔄 Force refreshing contact list after 100ms');
        this.renderContacts();
      }, 100);
      
    } else {
      console.warn('⚠️ Contact not found for ID:', contactId, 'Available contacts:', this.contacts.map(c => c.id));
      // If contact not found, refresh the entire contact list
      console.log('🔄 Contact not found, refreshing entire contact list');
      this.loadContacts();
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
