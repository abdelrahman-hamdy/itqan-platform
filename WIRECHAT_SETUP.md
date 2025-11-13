# WireChat Setup Complete ✅

## 🚀 Quick Start

### Start All Services
```bash
./restart-chat.sh
```

### Check Service Status
```bash
./chat-status.sh
```

### Stop All Services
```bash
./stop-chat.sh
```

## 📡 Service Details

### Services Running
1. **Laravel Reverb** - WebSocket server on port 8085 (HTTPS/WSS)
2. **Queue Worker** - Processing message broadcasts

### Configuration
- **WebSocket URL:** `wss://itqan-platform.test:8085`
- **SSL/TLS:** Enabled with Valet certificates
- **Broadcasting Queue:** `messages`
- **Default Queue:** `default`

## 🔧 Environment Variables

SSL/TLS configuration in `.env`:
```env
REVERB_APP_ID=852167
REVERB_APP_KEY=vil71wafgpp6do1miwn1
REVERB_APP_SECRET=2lppkjqbygmqte1gp9ge
REVERB_HOST=itqan-platform.test
REVERB_PORT=8085
REVERB_SERVER_HOST=0.0.0.0
REVERB_SERVER_PORT=8085
REVERB_SCHEME=https

# SSL/TLS Configuration
REVERB_TLS_CERT=/Users/abdelrahmanhamdy/.config/valet/Certificates/itqan-platform.test.crt
REVERB_TLS_KEY=/Users/abdelrahmanhamdy/.config/valet/Certificates/itqan-platform.test.key
REVERB_TLS_VERIFY_PEER=false
```

## 📝 Log Files

Monitor real-time logs:
```bash
# Reverb WebSocket server
tail -f /tmp/wirechat-logs/reverb.log

# Queue worker (message broadcasts)
tail -f /tmp/wirechat-logs/queue-messages.log
```

## 🌐 Access Chat

Visit: **https://itqan-platform.test/chats**

## 🛠️ Troubleshooting

### WebSocket Connection Failed
1. Check if Reverb is running: `./chat-status.sh`
2. Verify SSL certificates exist:
   ```bash
   ls -la ~/.config/valet/Certificates/itqan-platform.test.*
   ```
3. Check Reverb logs: `tail -f /tmp/wirechat-logs/reverb.log`
4. Restart services: `./restart-chat.sh`

### Messages Not Sending
1. Check queue worker is running: `./chat-status.sh`
2. Check queue logs: `tail -f /tmp/wirechat-logs/queue-messages.log`
3. Verify Redis is running: `redis-cli ping` (should return "PONG")

### Clear Browser Cache
If chat UI doesn't load properly:
1. Hard refresh: `Cmd + Shift + R` (Mac) or `Ctrl + Shift + R` (Windows)
2. Clear application storage in browser DevTools

## 📚 Features Enabled

- ✅ Private 1-on-1 conversations
- ✅ Group chats
- ✅ Real-time messaging
- ✅ Media attachments
- ✅ File attachments
- ✅ Message replies
- ✅ Typing indicators
- ✅ Unread badges
- ✅ Push notifications
- ✅ Dark mode support

## 🔐 Security

- ✅ HTTPS/WSS encryption
- ✅ Authentication required
- ✅ Channel authorization
- ✅ File upload validation
- ✅ XSS protection

## 📖 Documentation

- **WireChat Docs:** https://wirechat.namuio.com
- **Laravel Reverb:** https://laravel.com/docs/reverb
- **Config File:** `config/wirechat.php`

## 🐛 Known Issues

- WireChat v0.2.11 is in **beta** - not recommended for production
- Test thoroughly before deploying

## 💡 Pro Tips

1. **Auto-start on boot:** Add restart script to your system startup
2. **Monitor performance:** Use Laravel Telescope for debugging
3. **Scale Reverb:** Enable Redis scaling for multiple servers
4. **Backup:** Database tables use `wire_` prefix

## 📞 Support

If you encounter issues:
1. Check logs in `/tmp/wirechat-logs/`
2. Verify all services are running: `./chat-status.sh`
3. Restart services: `./restart-chat.sh`
4. Check WireChat GitHub issues: https://github.com/namumakwembo/wirechat/issues
