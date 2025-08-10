# LiveKit Environment Variables Setup

Add these variables to your `.env` file:

```bash
# LiveKit Server Configuration
LIVEKIT_SERVER_URL=wss://your-livekit-server.com
LIVEKIT_API_KEY=your_api_key_here
LIVEKIT_API_SECRET=your_api_secret_here

# Meeting Room Defaults
LIVEKIT_MAX_PARTICIPANTS=50
LIVEKIT_EMPTY_TIMEOUT=300
LIVEKIT_MAX_DURATION=7200
LIVEKIT_ENABLE_RECORDING=true
LIVEKIT_AUTO_RECORD=false

# Access Token Settings
LIVEKIT_TOKEN_TTL=10800
LIVEKIT_TOKEN_NBF=0

# Recording Configuration
LIVEKIT_RECORDING_ENABLED=true
LIVEKIT_RECORDING_STORAGE=s3
LIVEKIT_RECORDING_S3_BUCKET=your-recordings-bucket
LIVEKIT_RECORDING_S3_REGION=us-east-1
LIVEKIT_RECORDING_S3_ACCESS_KEY=your_s3_access_key
LIVEKIT_RECORDING_S3_SECRET_KEY=your_s3_secret_key
LIVEKIT_RECORDING_VIDEO_QUALITY=high
LIVEKIT_RECORDING_LAYOUT=grid

# Webhook Configuration
LIVEKIT_WEBHOOKS_ENABLED=true
LIVEKIT_WEBHOOK_ENDPOINT=/webhooks/livekit
LIVEKIT_WEBHOOK_SECRET=your_webhook_secret

# Feature Flags
LIVEKIT_ADAPTIVE_STREAM=true
LIVEKIT_DYNACAST=true
LIVEKIT_SIMULCAST=true
LIVEKIT_E2E_ENCRYPTION=false
LIVEKIT_NOISE_CANCELLATION=true

# UI Customization
LIVEKIT_UI_THEME=light
LIVEKIT_UI_PRIMARY_COLOR=#3B82F6
LIVEKIT_UI_ENABLE_CHAT=true
LIVEKIT_UI_ENABLE_SCREEN_SHARE=true

# Performance Settings
LIVEKIT_VIDEO_RESOLUTION=720p
LIVEKIT_VIDEO_FPS=30
LIVEKIT_AUDIO_BITRATE=64
LIVEKIT_VIDEO_BITRATE=1500

# Development Settings
LIVEKIT_DEBUG=false
LIVEKIT_LOG_LEVEL=info
```

## Setup Options:

### Option 1: LiveKit Cloud (Recommended for Production)
1. Sign up at https://cloud.livekit.io/
2. Create a new project
3. Copy the Server URL, API Key, and Secret
4. Update your .env file with the credentials

### Option 2: Self-Hosted LiveKit Server
1. Follow the deployment guide: https://docs.livekit.io/deploy/
2. Use Docker or Kubernetes for easy setup
3. Configure your own server URL and generate API keys

### Option 3: Local Development with Docker
```bash
# Quick local setup for testing
docker run --rm -p 7880:7880 -p 7881:7881 -p 7882:7882/udp \
  -e LIVEKIT_KEYS="your_api_key: your_api_secret" \
  livekit/livekit-server:latest \
  --dev --node-ip=127.0.0.1
```

Then use:
```bash
LIVEKIT_SERVER_URL=ws://localhost:7880
LIVEKIT_API_KEY=your_api_key
LIVEKIT_API_SECRET=your_api_secret
```
