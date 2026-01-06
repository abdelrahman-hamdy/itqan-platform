#!/bin/bash
# =============================================================================
# Itqan Platform - Production Deployment Script
# =============================================================================
# Usage: ./deploy.sh [--fresh] [--skip-npm]
#   --fresh     Run fresh migrations (WARNING: destroys data)
#   --skip-npm  Skip npm build (use if assets are pre-built)
# =============================================================================

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
APP_DIR="/var/www/itqan-platform"
BACKUP_DIR="/var/www/backups"
DATE=$(date +%Y%m%d_%H%M%S)

# Parse arguments
FRESH_MIGRATE=false
SKIP_NPM=false
for arg in "$@"; do
    case $arg in
        --fresh)
            FRESH_MIGRATE=true
            ;;
        --skip-npm)
            SKIP_NPM=true
            ;;
    esac
done

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}  Itqan Platform Deployment Script${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""

# Check if we're in the right directory
if [ ! -f "artisan" ]; then
    echo -e "${RED}Error: artisan not found. Run this script from the Laravel project root.${NC}"
    exit 1
fi

# Pre-deployment checks
echo -e "${YELLOW}[1/12] Running pre-deployment checks...${NC}"

# Check .env exists
if [ ! -f ".env" ]; then
    echo -e "${RED}Error: .env file not found!${NC}"
    exit 1
fi

# Check APP_DEBUG is false
if grep -q "APP_DEBUG=true" .env; then
    echo -e "${RED}Error: APP_DEBUG is set to true! Set to false for production.${NC}"
    exit 1
fi

# Check QUEUE_CONNECTION is not sync
if grep -q "QUEUE_CONNECTION=sync" .env; then
    echo -e "${RED}Warning: QUEUE_CONNECTION is set to sync. Use redis for production.${NC}"
    read -p "Continue anyway? (y/n) " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        exit 1
    fi
fi

echo -e "${GREEN}Pre-deployment checks passed.${NC}"

# Enable maintenance mode
echo -e "${YELLOW}[2/12] Enabling maintenance mode...${NC}"
php artisan down --render="errors::maintenance" || true

# Pull latest code (if using git)
echo -e "${YELLOW}[3/12] Pulling latest code...${NC}"
if [ -d ".git" ]; then
    git pull origin main
else
    echo "Not a git repository, skipping..."
fi

# Install/update PHP dependencies
echo -e "${YELLOW}[4/12] Installing PHP dependencies...${NC}"
composer install --no-dev --optimize-autoloader --no-interaction

# Install/update Node dependencies and build assets
if [ "$SKIP_NPM" = false ]; then
    echo -e "${YELLOW}[5/12] Installing Node dependencies...${NC}"
    npm ci --production=false

    echo -e "${YELLOW}[6/12] Building frontend assets...${NC}"
    npm run build
else
    echo -e "${YELLOW}[5/12] Skipping Node dependencies (--skip-npm)${NC}"
    echo -e "${YELLOW}[6/12] Skipping frontend build (--skip-npm)${NC}"
fi

# Run database migrations
echo -e "${YELLOW}[7/12] Running database migrations...${NC}"
if [ "$FRESH_MIGRATE" = true ]; then
    echo -e "${RED}WARNING: Running fresh migrations! This will DESTROY all data!${NC}"
    read -p "Are you sure? Type 'yes' to confirm: " confirm
    if [ "$confirm" = "yes" ]; then
        php artisan migrate:fresh --force --seed
    else
        echo "Aborted."
        php artisan up
        exit 1
    fi
else
    php artisan migrate --force
fi

# Clear and cache configuration
echo -e "${YELLOW}[8/12] Optimizing configuration...${NC}"
php artisan config:clear
php artisan config:cache

# Cache routes
echo -e "${YELLOW}[9/12] Caching routes...${NC}"
php artisan route:cache

# Cache views
echo -e "${YELLOW}[10/12] Caching views...${NC}"
php artisan view:cache

# Restart queue workers
echo -e "${YELLOW}[11/12] Restarting queue workers...${NC}"
php artisan queue:restart

# Disable maintenance mode
echo -e "${YELLOW}[12/12] Disabling maintenance mode...${NC}"
php artisan up

echo ""
echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}  Deployment completed successfully!${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""
echo -e "Post-deployment checklist:"
echo -e "  ${BLUE}[  ]${NC} Verify health check: curl https://your-domain.com/health/ready"
echo -e "  ${BLUE}[  ]${NC} Check queue workers: sudo supervisorctl status"
echo -e "  ${BLUE}[  ]${NC} Check Reverb: sudo supervisorctl status itqan-reverb"
echo -e "  ${BLUE}[  ]${NC} Test WebSocket: verify live updates work"
echo -e "  ${BLUE}[  ]${NC} Monitor logs: tail -f storage/logs/laravel.log"
echo ""
