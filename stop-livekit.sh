#!/bin/bash

# LiveKit Local Development Stop Script
# Run this script to stop LiveKit services

set -e

echo "🛑 Stopping LiveKit Local Development Environment..."
echo "=================================================="

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Check if docker-compose is available
if ! command -v docker-compose > /dev/null 2>&1; then
    echo -e "${RED}❌ Error: docker-compose is not installed${NC}"
    exit 1
fi

echo -e "${BLUE}📋 Checking running containers...${NC}"

# Check if containers are running
if docker-compose -f docker-compose.livekit.yml ps -q | grep -q .; then
    echo -e "${YELLOW}🛑 Stopping LiveKit containers...${NC}"
    docker-compose -f docker-compose.livekit.yml down
    
    # Wait a moment for containers to fully stop
    sleep 2
    
    echo -e "${GREEN}✅ LiveKit services stopped successfully${NC}"
else
    echo -e "${YELLOW}ℹ️  No LiveKit containers were running${NC}"
fi

# Optional: Remove volumes (uncomment if you want to clear all data)
# echo -e "${BLUE}🗑️  Removing volumes...${NC}"
# docker-compose -f docker-compose.livekit.yml down -v

echo -e "${GREEN}🎉 LiveKit Environment Stopped!${NC}"
echo "=================================================="
echo -e "${BLUE}📊 Status:${NC}"
echo "  • LiveKit Server: Stopped"
echo "  • Redis: Stopped"
echo "  • Recordings: Preserved in storage/livekit-recordings/"
echo ""
echo -e "${BLUE}🔄 To restart:${NC}"
echo "  • Run: ./start-livekit.sh"
echo "  • Or: docker-compose -f docker-compose.livekit.yml up -d"
echo ""
echo -e "${GREEN}✨ Services stopped cleanly! ✨${NC}"
