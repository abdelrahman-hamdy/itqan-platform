
@use('Wirechat\Wirechat\Facades\Wirechat')

@if(auth()->check() && Wirechat::notificationsEnabled())

   <div dusk="notification_manager"
        x-data="{
        showNotification(e) {
            const message = e.message;
            const redirect_url = e.redirect_url;

            if (Notification.permission !== 'granted') {
                return;
            }

            let title = message.sendable?.display_name || 'User';
            let body = message.body;
            let icon = message.sendable?.cover_url;

            if (message.conversation.type == 'group') {
                title = message.conversation?.group?.name;
                body = message.sendable?.display_name + ': ' + message.body;
                icon = message.conversation?.group?.cover_url;
            }

            const options = {
                body: body,
                icon: icon,
                vibrate: [200, 100, 200],
                tag: 'wirechat-notification-' + message.conversation_id,
                renotify: true,
                data: {
                    url: redirect_url,
                    type: 'SHOW_NOTIFICATION',
                    tag: 'wirechat-notification-' + message.conversation_id
                }
            };

            if ('serviceWorker' in navigator) {
            navigator.serviceWorker.ready
                .then((registration )=> {
                    // Service worker is fully ready
                    registration.active.postMessage({
                        type: 'SHOW_NOTIFICATION',
                        title: title,
                        options: options
                    });
                })
                .catch(error => {
                    // Fallback to regular notifications
                        this.newNotification(title, options);
                });


        } else {
            this.newNotification(title, options);
        }
        },

        newNotification(title,options){


          const notification=   new Notification(title, options);
                
            notification.onclick = (event) => {
                event.preventDefault();
                const convId = message.conversation_id || 'default';
                const windowName = 'wirechat-conversation';
                const url = event.currentTarget.data.url;
                const openedWindow = window.open(url, windowName);
                if (openedWindow) {
                    openedWindow.focus();
                }
                //Close current notification
                event.currentTarget.close();
            };

        },

        registerServiceWorker() {
                if ('serviceWorker' in navigator) {
                    navigator.serviceWorker.register(`{{asset(config('wirechat.notifications.main_sw_script','sw.js'))}}`)
                }
            }
        }"
        x-init="
        registerServiceWorker();

        userId = @js(auth()->id());
        encodedType = @js(\Wirechat\Wirechat\Helpers\MorphClassResolver::encode(auth()->user()->getMorphClass()));

        {{-- We listen to notify participant event --}}
        Echo.private(`itqan.participant.${encodedType}.${userId}`)
            .listen('.Wirechat\\Wirechat\\Events\\NotifyParticipant', (e) => {

                {{--Ignore if user is currently open in the chat  --}}
                if (e.redirect_url !== window.location.href) {
                    // Only show notification if permission is already granted
                // Don't request permission automatically - it must be done via user gesture
                if ('Notification' in window && Notification.permission === 'granted') {
                    showNotification(e);
                }
                }
            });

        document.addEventListener('chat-opened', (event) => {
            const conversation = event.detail.conversation;
            const tag = 'wirechat-notification-' + conversation;

            if (navigator.serviceWorker.controller) {
                navigator.serviceWorker.controller.postMessage({
                    type: 'CLOSE_NOTIFICATION',
                    tag: tag
                });
            }
        });
    ">
    </div>


@endif
