// In host's sw.js
importScripts('/js/wirechat/sw.js'); 


// Example: Custom event listener in main SW
self.addEventListener('install', event => {
    self.skipWaiting();
});

self.addEventListener('activate', event => {
    event.waitUntil(self.clients.claim());
});
