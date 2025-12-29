{{-- Academic Teacher Panel - Unread Messages Count --}}
<style>
.message-count-container {
    position: relative;
    display: inline-flex;
    align-items: center;
    margin-left: 1rem;
}

.message-count-icon {
    width: 2.5rem;
    height: 2.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #4b5563;
    font-size: 1.25rem;
    border-radius: 9999px;
    transition: all 0.2s ease;
    position: relative;
}

.message-count-icon:hover {
    color: #1f2937;
    background-color: #f3f4f6;
}

.message-count-badge {
    position: absolute;
    top: -8px;
    right: -8px;
    background-color: #10b981;
    color: white;
    font-size: 0.75rem;
    font-weight: bold;
    padding: 2px 6px;
    border-radius: 9999px;
    min-width: 18px;
    text-align: center;
    display: none;
}

.message-count-badge.visible {
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
</style>

<div class="message-count-container">
    <a href="{{ url('/chats') }}"
       class="message-count-icon"
       title="الرسائل">
        <i class="ri-message-2-line"></i>
        <span id="filament-unread-count-badge" class="message-count-badge">0</span>
    </a>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Function to fetch and update unread count for Filament panel
    function updateFilamentUnreadCount() {
        fetch('/api/chat/unreadCount', {
            method: 'GET',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
            const badge = document.getElementById('filament-unread-count-badge');
            if (badge && data.unread_count !== undefined) {
                if (data.unread_count > 0) {
                    badge.textContent = data.unread_count > 99 ? '99+' : data.unread_count;
                    badge.classList.add('visible');
                } else {
                    badge.classList.remove('visible');
                }
            }
        })
        .catch(error => {
        });
    }

    // Initial load
    updateFilamentUnreadCount();

    // Update every 5 seconds for faster real-time feel
    setInterval(updateFilamentUnreadCount, 5000);

    // Listen for messages marked as read
    window.addEventListener('messages-marked-read', (e) => {
        updateFilamentUnreadCount();
    });
});
</script>
