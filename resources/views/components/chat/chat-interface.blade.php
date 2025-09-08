{{-- Chat Interface Component --}}
<style>
/* Chat Interface Styles - Isolated and stable */
.chat-interface {
  isolation: isolate;
  position: relative;
  z-index: 1;
}

.chat-interface * {
  box-sizing: border-box;
}

/* Message Cards - Fixed Alignment */
.chat-interface .message-card {
  margin-bottom: 1rem;
  position: relative;
  width: 100%;
  clear: both;
  display: block;
}

/* Sent messages (right side) */
.chat-interface .message-card.mc-sender {
  text-align: right;
}

.chat-interface .message-card.mc-sender .message-card-content {
  background: #3b82f6;
  color: white;
  border-radius: 18px 18px 4px 18px;
  padding: 12px 16px;
  max-width: 70%;
  display: inline-block;
  margin-left: 30%; /* Push to right */
  word-wrap: break-word;
}

/* Received messages (left side) */
.chat-interface .message-card.mc-receiver {
  text-align: left;
}

.chat-interface .message-card.mc-receiver .message-card-content {
  background: #fff;
  color: #1f2937;
  border-radius: 18px 18px 18px 4px;
  padding: 12px 16px;
  max-width: 70%;
  display: inline-block;
  margin-right: 30%; /* Push to left */
  word-wrap: break-word;
}

.chat-interface .message-time {
  font-size: 0.75rem;
  opacity: 0.7;
  margin-top: 4px;
  display: block;
}

.chat-interface .actions {
  opacity: 0;
  position: absolute;
  top: 4px;
  right: 4px;
  background: rgba(0,0,0,0.8);
  border-radius: 4px;
  padding: 4px;
  transition: opacity 0.2s ease;
  pointer-events: none;
}

.chat-interface .message-card:hover .actions {
  opacity: 1;
  pointer-events: auto;
}

.chat-interface .actions i {
  color: white;
  font-size: 12px;
  cursor: pointer;
}

/* Stable Layout Containers */
.chat-main-container {
  height: calc(100vh - 8rem);
  min-height: 500px;
  max-height: 800px;
  position: relative;
  isolation: isolate;
}

.chat-sidebar {
  flex: 0 0 320px;
  min-width: 320px;
  max-width: 400px;
}

.chat-main {
  flex: 1;
  min-width: 0;
  position: relative;
}

.chat-messages-container {
  flex: 1;
  position: relative;
  overflow-y: auto;
  background: #f9fafb;
  scroll-behavior: smooth;
}

/* Add smooth scrolling to messages list */
.chat-interface #messages-list {
  scroll-behavior: smooth;
}

.chat-input-container {
  flex: 0 0 auto;
  background: white;
  border-top: 1px solid #e5e7eb;
  position: relative;
  z-index: 10;
}

/* Clearfix for floated messages */
.chat-messages-list::after {
  content: '';
  display: table;
  clear: both;
}

/* Override any external float/position styles */
.chat-interface .message-card {
  float: none !important;
  position: relative !important;
  transform: none !important;
  animation: none !important;
  transition: none !important;
}

.chat-interface .message-card-content {
  transform: none !important;
  animation: none !important;
  transition: none !important;
}

/* Prevent any layout shifts */
.chat-interface .message-card .actions {
  position: absolute !important;
}

.chat-interface .message-time {
  transform: none !important;
  animation: none !important;
}

/* Force spinning animation for loading icons */
@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

.chat-interface .animate-spin {
  animation: spin 1s linear infinite !important;
}

/* Force last messages to be grey, override any green colors */
.chat-interface .last-message,
.chat-interface .text-green-600,
.chat-interface .text-green-500,
.chat-interface .text-success,
.chat-interface [class*="text-green"] {
  color: #6b7280 !important;
}

/* Instant visibility for chat bubbles - NO animations for initial load */
.chat-interface .message-card {
  opacity: 1;
  transform: translateY(0);
}

.chat-interface .message-card.animate-in {
  opacity: 1;
  transform: translateY(0);
}

/* Animation ONLY for new messages (not initial load) */
.chat-interface .message-card.new-message-animation {
  animation: slideInMessage 0.4s ease-out forwards;
}

@keyframes slideInMessage {
  0% {
    opacity: 0;
    transform: translateY(15px) scale(0.95);
  }
  100% {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}
</style>

<div class="chat-interface">
  <div class="bg-white rounded-xl shadow-lg overflow-hidden chat-main-container flex" style="box-shadow: 0 0 50px 10px rgba(0, 0, 0, 0.1);">
    <!-- Contacts Sidebar -->
    <div class="chat-sidebar border-l border-gray-200 bg-gray-50 flex flex-col">
      <!-- Contacts Header -->
      <div class="p-4 border-b border-gray-200 bg-white flex-shrink-0" style="min-height: 72px;">
        <div class="flex items-center justify-between h-10">
          <h2 class="text-lg font-semibold text-gray-900">جهات الاتصال</h2>
        </div>
      </div>
      
      <!-- Search -->
      <div class="p-4 border-b border-gray-200">
        <div class="relative">
          <input 
            type="text" 
            id="search-input" 
            placeholder="البحث في المحادثات..."
            class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-primary focus:ring-0 transition-colors duration-200 bg-gray-50"
            style="box-shadow: none !important;"
          >
          <i class="ri-search-line absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
        </div>
      </div>

      <!-- Contacts List -->
      <div class="flex-1 overflow-y-auto" id="contacts-container">
        <div id="contacts-loading" class="p-8 text-center text-gray-500">
          <i class="ri-loader-2-line text-2xl text-primary animate-spin mb-2"></i>
          <p>جاري تحميل جهات الاتصال...</p>
        </div>
        <div id="contacts-list" class="hidden">
          <!-- Contacts will be loaded here -->
        </div>
        <div id="contacts-empty" class="hidden p-8 text-center text-gray-500">
          <i class="ri-user-line text-4xl mb-2"></i>
          <p>لا توجد جهات اتصال</p>
        </div>
      </div>
    </div>

    <!-- Chat Area -->
    <div class="chat-main flex flex-col">
      <!-- Empty State -->
      <div id="chat-empty-state" class="flex-1 flex items-center justify-center text-gray-500">
        <div class="text-center">
          <i class="ri-message-line text-6xl mb-4 text-gray-300"></i>
          <h3 class="text-xl font-semibold mb-2">اختر محادثة للبدء</h3>
          <p class="text-gray-400">حدد جهة اتصال من القائمة لبدء المحادثة</p>
        </div>
      </div>

      <!-- Active Chat -->
      <div id="active-chat" class="hidden flex flex-col h-full">
        <!-- Chat Header -->
        <div class="p-4 border-b border-gray-200 bg-white flex-shrink-0" style="min-height: 72px;">
          <div class="flex items-center justify-between h-10">
            <div class="flex items-center">
              <div class="w-10 h-10 rounded-full bg-gradient-to-r from-blue-500 to-purple-600 flex items-center justify-center text-white font-medium flex-shrink-0">
                <span id="chat-contact-avatar">U</span>
              </div>
              <div class="min-w-0 flex-1 mr-4">
                <h3 id="chat-contact-name" class="font-semibold text-gray-900 truncate">اختر محادثة</h3>
                <p id="chat-contact-status" class="text-sm text-gray-500 truncate">اختر جهة اتصال لبدء المحادثة</p>
              </div>
            </div>
            <div class="flex items-center space-x-2 space-x-reverse flex-shrink-0">
              <!-- Options removed for cleaner interface -->
            </div>
          </div>
        </div>

        <!-- Messages Container -->
        <div class="chat-messages-container relative" id="messages-container">
          <!-- Background Pattern Layer -->
          <div class="absolute inset-0 opacity-15 z-0" style="background-image: url('/storage/app-design-assets/chat-bg-pattern.png'); background-repeat: repeat; background-size: 200px;"></div>
          
          <!-- Gradient Overlay -->
          <div class="absolute inset-0 bg-gradient-to-br from-blue-500/20 via-indigo-500/20 to-indigo-600/20 z-1"></div>
          
          <!-- Scroll to Bottom Button -->
          <div id="scroll-to-bottom" class="hidden absolute bottom-4 left-1/2 transform -translate-x-1/2 z-30">
            <button class="bg-white hover:bg-gray-50 text-gray-700 hover:text-gray-900 rounded-full w-12 h-12 shadow-lg transition-all duration-200 flex items-center justify-center border border-gray-200">
              <i class="ri-arrow-down-line text-lg"></i>
            </button>
          </div>
          
          <!-- Loading State -->
          <div id="messages-loading" class="absolute inset-0 flex items-center justify-center bg-gray-50 z-20">
            <div class="text-center text-gray-500">
              <i class="ri-loader-2-line text-2xl text-primary animate-spin mb-2"></i>
              <p>جاري تحميل الرسائل...</p>
            </div>
          </div>
          
          <!-- Messages List -->
          <div id="messages-list" class="chat-messages-list p-4 space-y-4 hidden min-h-full absolute inset-0 z-2 overflow-y-auto" style="scroll-behavior: auto !important; transition: none !important;">
            <!-- Messages will be loaded here -->
          </div>
          
          <!-- Typing Indicator -->
          <div id="typing-indicator" class="hidden p-4 absolute bottom-0 left-0 right-0 bg-gray-50">
            <div class="flex items-center text-gray-500">
              <div class="w-8 h-8 rounded-full bg-gray-300 flex items-center justify-center mr-3">
                <i class="ri-user-line text-sm"></i>
              </div>
              <div class="bg-white rounded-lg px-3 py-2 shadow-sm">
                <div class="flex items-center space-x-1">
                  <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce"></div>
                  <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.1s"></div>
                  <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Message Input -->
        <div class="chat-input-container p-4">
          <form id="message-form" class="flex items-stretch gap-3">
            <input type="hidden" id="current-chat-id" value="">
            
            <!-- Attachment Button -->
            <button type="button" id="attachment-btn" class="flex-shrink-0 text-gray-500 hover:text-gray-700 rounded-lg hover:bg-gray-100 flex items-center justify-center border border-gray-300 bg-gray-50" style="width: 48px; height: 48px;">
              <i class="ri-attachment-line text-lg"></i>
            </button>
            
            <!-- Message Input -->
            <div class="flex-1">
              <textarea 
                id="message-input" 
                placeholder="اكتب رسالتك هنا..."
                class="w-full resize-none border border-gray-300 rounded-lg px-4 py-3 bg-white focus:outline-none focus:border-primary focus:ring-0 transition-colors duration-200"
                rows="1"
                style="height: 48px; max-height: 120px; box-shadow: none !important;"></textarea>
            </div>
            
            <!-- Send Button -->
            <button 
              type="submit" 
              id="send-btn"
              disabled
              class="flex-shrink-0 bg-primary hover:bg-primary/90 disabled:bg-primary/50 disabled:cursor-not-allowed text-white rounded-lg transition-all duration-200 flex items-center justify-center gap-2 px-4"
              style="height: 48px;">
              <i class="ri-send-plane-fill text-lg"></i>
              <span class="text-sm font-medium">إرسال</span>
            </button>
          </form>
          
          <!-- File Upload (Hidden) -->
          <input type="file" id="file-input" class="hidden" accept="image/*,application/pdf,.doc,.docx">
        </div>
      </div>
    </div>
  </div>
</div>

{{-- Laravel Echo & Pusher --}}
<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.17.1/dist/echo.iife.js"></script>

{{-- Chat Meta Data --}}
<script id="chat-meta" type="application/json">
{
  "userId": "{{ auth()->id() }}",
  "userName": "{{ auth()->user()->name }}",
  "userType": "{{ auth()->user()->user_type }}",
  "csrfToken": "{{ csrf_token() }}",
  "pusherKey": "{{ config('chatify.pusher.key') }}",
  "pusherHost": "{{ config('chatify.pusher.options.host') }}",
  "pusherPort": "{{ config('chatify.pusher.options.port') }}",
  "pusherScheme": "{{ config('chatify.pusher.options.scheme') }}",
  "pusherUseTLS": {{ config('chatify.pusher.options.useTLS') ? 'true' : 'false' }},
  "apiEndpoints": {
    "sendMessage": "{{ route('send.message') }}",
    "fetchMessages": "{{ route('fetch.messages') }}",
    "getContacts": "{{ route('contacts.get') }}",
    "pusherAuth": "{{ route('pusher.auth') }}"
  }
}
</script>