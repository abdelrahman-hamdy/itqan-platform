/**
 * Service Worker for Enhanced Chat System
 * Handles offline support, push notifications, and caching
 */

const CACHE_NAME = 'itqan-chat-v1';
const OFFLINE_QUEUE_NAME = 'offline-messages';

// Files to cache for offline support
const urlsToCache = [
    '/css/chatify-rtl.css',
    '/css/chat-enhanced.css',
    '/js/chat-enhanced.js',
    '/sounds/chat/new-message-sound.mp3',
    '/images/default-avatar.png',
    '/images/chat-badge.png'
];

// Install event - cache essential files
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => {
                console.log('Opened cache');
                // Cache files individually to prevent failure if one doesn't exist
                return Promise.allSettled(
                    urlsToCache.map(url =>
                        cache.add(url).catch(err => {
                            console.warn(`Failed to cache ${url}:`, err.message);
                            return null;
                        })
                    )
                );
            })
            .then(() => {
                console.log('Service Worker installed successfully (some files may have failed to cache)');
                return self.skipWaiting();
            })
            .catch(err => {
                console.error('Service Worker installation error:', err);
                return self.skipWaiting(); // Skip waiting even on error
            })
    );
});

// Activate event - clean up old caches
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames.map(cacheName => {
                    if (cacheName !== CACHE_NAME && cacheName.startsWith('itqan-chat-')) {
                        console.log('Deleting old cache:', cacheName);
                        return caches.delete(cacheName);
                    }
                })
            );
        }).then(() => self.clients.claim())
    );
});

// Fetch event - serve from cache when offline
self.addEventListener('fetch', event => {
    const { request } = event;
    const url = new URL(request.url);

    // Handle chat message sending when offline
    if (url.pathname === '/chat/sendMessage' && request.method === 'POST') {
        event.respondWith(handleMessageSend(request));
        return;
    }

    // Handle API requests
    if (url.pathname.startsWith('/chat/') || url.pathname.startsWith('/api/chat/')) {
        event.respondWith(
            fetch(request)
                .catch(() => {
                    // Return cached response or error
                    return caches.match(request)
                        .then(response => {
                            if (response) {
                                return response;
                            }
                            // Return offline response
                            return new Response(JSON.stringify({
                                status: 'offline',
                                message: 'You are currently offline'
                            }), {
                                headers: { 'Content-Type': 'application/json' }
                            });
                        });
                })
        );
        return;
    }

    // Network first, then cache strategy for other requests
    event.respondWith(
        fetch(request)
            .then(response => {
                // Clone the response before caching
                if (response.status === 200) {
                    const responseClone = response.clone();
                    caches.open(CACHE_NAME).then(cache => {
                        cache.put(request, responseClone);
                    });
                }
                return response;
            })
            .catch(() => {
                // Try to get from cache
                return caches.match(request)
                    .then(response => {
                        if (response) {
                            return response;
                        }
                        // Return offline page for navigation requests
                        if (request.mode === 'navigate') {
                            return caches.match('/offline.html');
                        }
                        // Return placeholder for images
                        if (request.destination === 'image') {
                            return caches.match('/images/default-avatar.png');
                        }
                    });
            })
    );
});

// Handle offline message sending
async function handleMessageSend(request) {
    try {
        // Try to send the message
        const response = await fetch(request.clone());

        if (response.ok) {
            // Message sent successfully
            return response;
        }

        throw new Error('Failed to send message');
    } catch (error) {
        // Queue the message for later
        const formData = await request.formData();
        const message = {
            id: 'offline_' + Date.now(),
            body: formData.get('message'),
            to_id: formData.get('to_id'),
            group_id: formData.get('group_id'),
            timestamp: new Date().toISOString(),
            status: 'queued'
        };

        // Store in IndexedDB
        await queueOfflineMessage(message);

        // Return success response to the app
        return new Response(JSON.stringify({
            status: 'queued',
            message: message,
            info: 'Message will be sent when connection is restored'
        }), {
            headers: { 'Content-Type': 'application/json' }
        });
    }
}

// Queue offline message in IndexedDB
async function queueOfflineMessage(message) {
    const db = await openDB();
    const tx = db.transaction(['offline_messages'], 'readwrite');
    const store = tx.objectStore('offline_messages');
    await store.add(message);
}

// Open IndexedDB
function openDB() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open('ItqanChatDB', 1);

        request.onerror = () => reject(request.error);
        request.onsuccess = () => resolve(request.result);

        request.onupgradeneeded = (event) => {
            const db = event.target.result;

            if (!db.objectStoreNames.contains('offline_messages')) {
                db.createObjectStore('offline_messages', {
                    keyPath: 'id',
                    autoIncrement: false
                });
            }

            if (!db.objectStoreNames.contains('cached_messages')) {
                const messageStore = db.createObjectStore('cached_messages', {
                    keyPath: 'id',
                    autoIncrement: false
                });
                messageStore.createIndex('conversation', 'conversation_id', { unique: false });
                messageStore.createIndex('timestamp', 'created_at', { unique: false });
            }
        };
    });
}

// Background sync for offline messages
self.addEventListener('sync', event => {
    if (event.tag === 'sync-messages') {
        event.waitUntil(syncOfflineMessages());
    }
});

// Sync offline messages when back online
async function syncOfflineMessages() {
    const db = await openDB();
    const tx = db.transaction(['offline_messages'], 'readonly');
    const store = tx.objectStore('offline_messages');
    const messages = await store.getAll();

    for (const message of messages) {
        try {
            const formData = new FormData();
            formData.append('message', message.body);
            formData.append('to_id', message.to_id);
            if (message.group_id) {
                formData.append('group_id', message.group_id);
            }

            const response = await fetch('/chat/sendMessage', {
                method: 'POST',
                body: formData
            });

            if (response.ok) {
                // Remove from offline queue
                const deleteTx = db.transaction(['offline_messages'], 'readwrite');
                await deleteTx.objectStore('offline_messages').delete(message.id);
            }
        } catch (error) {
            console.error('Failed to sync message:', error);
        }
    }
}

// Push notification handling
self.addEventListener('push', event => {
    const options = {
        body: 'New message received',
        icon: '/images/chat-badge.png',
        badge: '/images/chat-badge.png',
        vibrate: [200, 100, 200],
        data: {},
        actions: [
            {
                action: 'view',
                title: 'View',
                icon: '/images/view-icon.png'
            },
            {
                action: 'close',
                title: 'Close',
                icon: '/images/close-icon.png'
            }
        ]
    };

    if (event.data) {
        try {
            const data = event.data.json();
            options.body = data.body || options.body;
            options.data = data;

            if (data.sender_name) {
                options.body = `${data.sender_name}: ${data.body}`;
            }

            if (data.sender_avatar) {
                options.icon = data.sender_avatar;
            }
        } catch (e) {
            console.error('Error parsing push data:', e);
        }
    }

    event.waitUntil(
        self.registration.showNotification('Itqan Chat', options)
    );
});

// Notification click handling
self.addEventListener('notificationclick', event => {
    event.notification.close();

    if (event.action === 'view') {
        // Open or focus the chat window
        event.waitUntil(
            clients.matchAll({ type: 'window' })
                .then(clientList => {
                    // Check if chat window is already open
                    for (const client of clientList) {
                        if (client.url.includes('/chat')) {
                            client.focus();

                            // Send message to open specific conversation
                            if (event.notification.data.conversation_id) {
                                client.postMessage({
                                    action: 'open_conversation',
                                    conversation_id: event.notification.data.conversation_id
                                });
                            }
                            return;
                        }
                    }

                    // Open new window if not found
                    const url = event.notification.data.conversation_id
                        ? `/chat/${event.notification.data.conversation_id}`
                        : '/chat';

                    return clients.openWindow(url);
                })
        );
    }
});

// Message from client
self.addEventListener('message', event => {
    if (event.data.action === 'skipWaiting') {
        self.skipWaiting();
    }

    if (event.data.action === 'clearCache') {
        event.waitUntil(
            caches.delete(CACHE_NAME)
                .then(() => {
                    return caches.open(CACHE_NAME);
                })
                .then(cache => {
                    // Cache files individually to prevent failure if one doesn't exist
                    return Promise.allSettled(
                        urlsToCache.map(url =>
                            cache.add(url).catch(err => {
                                console.warn(`Failed to cache ${url}:`, err.message);
                                return null;
                            })
                        )
                    );
                })
        );
    }

    if (event.data.action === 'cacheMessages') {
        event.waitUntil(cacheMessages(event.data.messages));
    }
});

// Cache messages in IndexedDB for offline access
async function cacheMessages(messages) {
    const db = await openDB();
    const tx = db.transaction(['cached_messages'], 'readwrite');
    const store = tx.objectStore('cached_messages');

    for (const message of messages) {
        await store.put(message);
    }
}

// Periodic background sync for keeping messages updated
self.addEventListener('periodicsync', event => {
    if (event.tag === 'update-messages') {
        event.waitUntil(updateCachedMessages());
    }
});

// Update cached messages
async function updateCachedMessages() {
    try {
        const response = await fetch('/chat/getRecentMessages', {
            credentials: 'include'
        });

        if (response.ok) {
            const messages = await response.json();
            await cacheMessages(messages);
        }
    } catch (error) {
        console.error('Failed to update cached messages:', error);
    }
}

console.log('Chat Service Worker loaded successfully');