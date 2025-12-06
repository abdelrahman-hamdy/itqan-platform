#!/bin/bash

# ============================================================
# Itqan Platform - Full Development Environment
# ============================================================
# Runs all necessary services for development:
#   1. Laravel Server (port 8000)
#   2. Vite Dev Server (port 5173)
#   3. Queue Worker (processes background jobs)
#   4. Laravel Reverb (WebSocket server, port 8080)
#   5. Scheduler (runs cron jobs every minute)
#   6. Pail (real-time log viewer)
# ============================================================

set -e

echo "=================================================="
echo "  Itqan Platform - Full Development Environment"
echo "=================================================="
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# Check if concurrently is installed
if ! command -v npx &> /dev/null; then
    echo -e "${RED}Error: npx is not installed. Please install Node.js first.${NC}"
    exit 1
fi

# Kill any existing processes on required ports
echo -e "${YELLOW}Cleaning up existing processes...${NC}"
lsof -ti:8000 2>/dev/null | xargs kill -9 2>/dev/null || true
lsof -ti:5173 2>/dev/null | xargs kill -9 2>/dev/null || true
lsof -ti:8080 2>/dev/null | xargs kill -9 2>/dev/null || true
lsof -ti:8085 2>/dev/null | xargs kill -9 2>/dev/null || true

# Also kill any existing artisan processes
pkill -f "artisan serve" 2>/dev/null || true
pkill -f "artisan queue" 2>/dev/null || true
pkill -f "artisan reverb" 2>/dev/null || true
pkill -f "artisan schedule:work" 2>/dev/null || true
pkill -f "artisan pail" 2>/dev/null || true
echo -e "${GREEN}Cleanup done!${NC}"

# Clear caches before starting
echo -e "${YELLOW}Clearing caches...${NC}"
php artisan config:clear > /dev/null 2>&1
php artisan view:clear > /dev/null 2>&1
php artisan route:clear > /dev/null 2>&1
echo -e "${GREEN}Caches cleared!${NC}"
echo ""

# Display service info
echo -e "${CYAN}Starting services:${NC}"
echo -e "  ${BLUE}[server]${NC}    Laravel Server       → http://localhost:8000"
echo -e "  ${PURPLE}[vite]${NC}      Vite Dev Server      → http://localhost:5173"
echo -e "  ${GREEN}[queue]${NC}     Queue Worker         → Processing jobs"
echo -e "  ${YELLOW}[reverb]${NC}    WebSocket Server     → ws://localhost:8080"
echo -e "  ${CYAN}[schedule]${NC}  Scheduler            → Running cron jobs"
echo -e "  ${RED}[logs]${NC}      Pail Log Viewer      → Real-time logs"
echo ""
echo -e "${YELLOW}Press Ctrl+C to stop all services${NC}"
echo "=================================================="
echo ""

# Give ports time to be released
sleep 1

# Run all services concurrently
# Removed --kill-others-on-fail to allow services to continue if one fails
npx concurrently \
    -c "#93c5fd,#c4b5fd,#22c55e,#fbbf24,#06b6d4,#fb7185" \
    -n "server,vite,queue,reverb,schedule,logs" \
    "php artisan serve" \
    "npm run dev" \
    "php artisan queue:listen --tries=1" \
    "php artisan reverb:start" \
    "php artisan schedule:work" \
    "php artisan pail --timeout=0"
