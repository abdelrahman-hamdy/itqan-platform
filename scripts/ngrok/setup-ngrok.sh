#!/bin/bash

echo "╔═══════════════════════════════════════════════════════════╗"
echo "║           ngrok Setup for LiveKit Webhooks                ║"
echo "╚═══════════════════════════════════════════════════════════╝"
echo ""

# Check if ngrok is already authenticated
if ngrok config check &>/dev/null; then
    echo "✅ ngrok is already configured!"
    echo ""
else
    echo "📋 Steps to get your ngrok auth token:"
    echo ""
    echo "   1. Open: https://dashboard.ngrok.com/signup"
    echo "   2. Sign up for a FREE account (GitHub/Google login available)"
    echo "   3. After signup, go to: https://dashboard.ngrok.com/get-started/your-authtoken"
    echo "   4. Copy your auth token"
    echo ""
    echo "Enter your ngrok auth token: "
    read -r AUTH_TOKEN
    
    if [ -z "$AUTH_TOKEN" ]; then
        echo "❌ Auth token cannot be empty"
        exit 1
    fi
    
    ngrok config add-authtoken "$AUTH_TOKEN"
    echo ""
    echo "✅ ngrok authenticated successfully!"
    echo ""
fi

echo "🚀 Starting ngrok tunnel..."
echo ""
echo "   Your local app: http://itqan-platform.test"
echo "   ngrok will create a public URL that forwards to your local app"
echo ""
echo "⚠️  Keep this terminal window open while testing webhooks"
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

# Start ngrok
ngrok http 80 --host-header=itqan-platform.test --log=stdout

