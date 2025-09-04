# Real-time Chat Setup Guide

## Best Free Alternatives to Pusher

### 🎯 Recommended: Soketi (100% Pusher Compatible)
**Best overall choice - drop-in Pusher replacement**

- ✅ 100% Pusher protocol compatible
- ✅ Self-hosted and free
- ✅ Better performance than Laravel Echo Server
- ✅ Works with existing Chatify integration
- ✅ No code changes needed

### 🔧 Laravel Echo Server (Official Laravel Solution)
**Good for development, simple setup**

- ✅ Official Laravel package
- ✅ Easy setup for development
- ✅ Redis-based
- ⚠️ Less performant than Soketi

### 🚀 Ably (Generous Free Tier)
**Commercial service with excellent free tier**

- ✅ 6 million messages/month free
- ✅ Better reliability than self-hosted
- ✅ Global CDN and scaling
- ✅ Drop-in Pusher replacement

## Option 1: Soketi Setup (Recommended)

### Installation
```bash
# Install Soketi globally
npm install -g @soketi/soketi

# Or run with Docker
docker run -p 6001:6001 -p 9601:9601 quay.io/soketi/soketi:latest
```

### Configuration
Update your `.env` file:
```env
BROADCAST_CONNECTION=pusher

# Soketi Configuration (Pusher Compatible)
PUSHER_APP_ID=soketi-app-id
PUSHER_APP_KEY=soketi-app-key
PUSHER_APP_SECRET=soketi-app-secret
PUSHER_APP_CLUSTER=mt1
PUSHER_HOST=127.0.0.1
PUSHER_PORT=6001
PUSHER_SCHEME=http
```

### Start Soketi Server
```bash
soketi start --config=soketi.json
```

Create `soketi.json` in your project root:
```json
{
  "debug": true,
  "port": 6001,
  "metrics": {
    "enabled": true,
    "port": 9601
  }
}
```

## Option 2: Laravel Echo Server (Simple Setup)

### Installation
```bash
npm install -g laravel-echo-server
```

### Initialize and Configure
```bash
# Initialize in your project
laravel-echo-server init
```

This creates `laravel-echo-server.json`:
```json
{
  "authHost": "http://localhost:8000",
  "authEndpoint": "/broadcasting/auth",
  "clients": [
    {
      "appId": "your-app-id",
      "key": "your-app-key"
    }
  ],
  "database": "redis",
  "databaseConfig": {
    "redis": {
      "port": "6379",
      "host": "127.0.0.1"
    }
  },
  "devMode": true,
  "host": null,
  "port": "6001",
  "protocol": "http",
  "socketio": {},
  "secureOptions": {},
  "sslCertPath": "",
  "sslKeyPath": "",
  "sslCertChainPath": "",
  "sslPassphrase": "",
  "subscribers": {
    "http": true,
    "redis": true
  },
  "apiOriginAllow": {
    "allowCors": false,
    "allowOrigin": "",
    "allowMethods": "",
    "allowHeaders": ""
  }
}
```

### Update .env for Echo Server
```env
BROADCAST_CONNECTION=redis

# Echo Server Configuration
PUSHER_APP_ID=your-app-id
PUSHER_APP_KEY=your-app-key
PUSHER_APP_SECRET=your-app-secret
PUSHER_APP_CLUSTER=mt1
PUSHER_HOST=127.0.0.1
PUSHER_PORT=6001
PUSHER_SCHEME=http
```

### Start Echo Server
```bash
laravel-echo-server start
```

## Option 3: Ably (Free Commercial Service)

### Setup Ably Account
1. Go to https://ably.com
2. Sign up for free account (6M messages/month)
3. Create a new app
4. Get your API key

### Install Ably Adapter
```bash
composer require ably/laravel-broadcaster
```

### Configure Ably
Add to `config/broadcasting.php`:
```php
'ably' => [
    'driver' => 'ably',
    'key' => env('ABLY_KEY'),
],
```

Update `.env`:
```env
BROADCAST_CONNECTION=ably
ABLY_KEY=your_ably_api_key
```

## Quick Start: Soketi (Easiest)

For immediate setup, run these commands:

```bash
# Install Soketi
npm install -g @soketi/soketi

# Start Soketi server
soketi start --debug
```

Update your `.env`:
```env
BROADCAST_CONNECTION=pusher
PUSHER_APP_ID=app-id
PUSHER_APP_KEY=app-key
PUSHER_APP_SECRET=app-secret
PUSHER_APP_CLUSTER=mt1
PUSHER_HOST=127.0.0.1
PUSHER_PORT=6001
PUSHER_SCHEME=http
```

Clear cache:
```bash
php artisan config:clear
```

Your chat will work immediately with no code changes!

## Troubleshooting

### Messages not appearing in real-time?
1. Check browser console for WebSocket errors
2. Verify Pusher credentials are correct
3. Check if BROADCAST_CONNECTION=pusher in .env
4. Ensure queue worker is running if using queued events

### Getting CORS errors?
Add your local domain to Pusher app settings under "App Settings > Enable authorized connections"

### Connection refused errors?
Make sure your firewall allows WebSocket connections on port 443

## Testing Pusher Connection
You can test if Pusher is working by checking the browser console. You should see:
```
Pusher : State changed : connecting -> connected
```

If you see "unavailable" or connection errors, check your credentials and network.
