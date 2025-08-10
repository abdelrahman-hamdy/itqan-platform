#!/bin/bash

# LiveKit Local Development Startup Script
# Run this script to start LiveKit alongside your Laravel project

set -e

echo "🚀 Starting LiveKit Local Development Environment..."
echo "=================================================="

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Check if Docker is running
if ! docker info > /dev/null 2>&1; then
    echo -e "${RED}❌ Error: Docker is not running${NC}"
    echo "Please start Docker Desktop and try again."
    exit 1
fi

# Check if docker-compose is available
if ! command -v docker-compose > /dev/null 2>&1; then
    echo -e "${RED}❌ Error: docker-compose is not installed${NC}"
    echo "Please install docker-compose and try again."
    exit 1
fi

echo -e "${BLUE}📋 Checking existing containers...${NC}"

# Stop existing containers if running
if docker-compose -f docker-compose.livekit.yml ps -q | grep -q .; then
    echo -e "${YELLOW}⚠️  Existing LiveKit containers found. Stopping them...${NC}"
    docker-compose -f docker-compose.livekit.yml down
fi

echo -e "${BLUE}🔧 Creating required directories...${NC}"

# Create storage directories
mkdir -p storage/livekit-recordings
mkdir -p livekit-config

# Set proper permissions
chmod 755 storage/livekit-recordings

echo -e "${BLUE}🐳 Starting LiveKit services...${NC}"

# Start LiveKit server and Redis
docker-compose -f docker-compose.livekit.yml up -d

# Wait for services to be ready
echo -e "${BLUE}⏳ Waiting for services to start...${NC}"
sleep 5

# Check if LiveKit is responding
echo -e "${BLUE}🔍 Checking LiveKit server health...${NC}"
for i in {1..10}; do
    if curl -s http://localhost:7880/ > /dev/null; then
        echo -e "${GREEN}✅ LiveKit server is running on http://localhost:7880${NC}"
        break
    else
        if [ $i -eq 10 ]; then
            echo -e "${RED}❌ LiveKit server failed to start${NC}"
            echo "Check logs with: docker-compose -f docker-compose.livekit.yml logs livekit"
            exit 1
        fi
        echo -e "${YELLOW}⏳ Waiting for LiveKit server... (attempt $i/10)${NC}"
        sleep 3
    fi
done

# Check if Redis is responding
echo -e "${BLUE}🔍 Checking Redis connection...${NC}"
if docker exec itqan-livekit-redis redis-cli ping > /dev/null 2>&1; then
    echo -e "${GREEN}✅ Redis is running on localhost:6379${NC}"
else
    echo -e "${YELLOW}⚠️  Redis may not be fully ready yet${NC}"
fi

echo -e "${GREEN}🎉 LiveKit Local Development Environment Started!${NC}"
echo "=================================================="
echo -e "${BLUE}📊 Service Information:${NC}"
echo "  • LiveKit Server: http://localhost:7880"
echo "  • Redis: localhost:6379" 
echo "  • WebRTC Ports: 50000-50100 (UDP)"
echo "  • Recordings: storage/livekit-recordings/"
echo ""
echo -e "${BLUE}🔧 Environment Configuration:${NC}"
echo "Add these to your .env file if not already present:"
echo ""
echo "LIVEKIT_SERVER_URL=http://localhost:7880"
echo "LIVEKIT_API_KEY=APIKey1"
echo "LIVEKIT_API_SECRET=ApiSecret1"
echo "LIVEKIT_WEBHOOK_SECRET=webhook_secret_key_123"
echo "LIVEKIT_RECORDING_ENABLED=true"
echo ""
echo -e "${BLUE}🧪 Quick Tests:${NC}"
echo "  • Test connection: curl http://localhost:7880/"
echo "  • Test Laravel integration: php artisan meetings:create-scheduled --dry-run -v"
echo "  • Admin panel: /admin/video-settings"
echo ""
echo -e "${BLUE}📊 Management Commands:${NC}"
echo "  • View logs: docker-compose -f docker-compose.livekit.yml logs -f"
echo "  • Stop services: docker-compose -f docker-compose.livekit.yml down"
echo "  • Restart: docker-compose -f docker-compose.livekit.yml restart"
echo ""
echo -e "${GREEN}✨ Ready to create video meetings locally! ✨${NC}"
