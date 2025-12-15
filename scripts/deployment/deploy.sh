#!/bin/bash
# ===========================================
# ITQAN PLATFORM - PRODUCTION DEPLOYMENT
# ===========================================
# Usage: ./deploy.sh [--skip-npm] [--skip-migrate] [--maintenance]

set -e

# Configuration
APP_DIR="/var/www/itqan-platform"
BRANCH="main"
PHP_BIN="php"
COMPOSER_BIN="composer"
NPM_BIN="npm"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Parse arguments
SKIP_NPM=false
SKIP_MIGRATE=false
MAINTENANCE_MODE=false

for arg in "$@"; do
    case $arg in
        --skip-npm)
            SKIP_NPM=true
            ;;
        --skip-migrate)
            SKIP_MIGRATE=true
            ;;
        --maintenance)
            MAINTENANCE_MODE=true
            ;;
    esac
done

echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}  ITQAN PLATFORM DEPLOYMENT${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""

cd $APP_DIR

# Enable maintenance mode if requested
if [ "$MAINTENANCE_MODE" = true ]; then
    echo -e "${YELLOW}[1/8] Enabling maintenance mode...${NC}"
    $PHP_BIN artisan down --render="errors::503" --retry=60
else
    echo -e "${YELLOW}[1/8] Skipping maintenance mode...${NC}"
fi

# Pull latest changes
echo -e "${YELLOW}[2/8] Pulling latest changes from $BRANCH...${NC}"
git fetch origin
git reset --hard origin/$BRANCH

# Install PHP dependencies
echo -e "${YELLOW}[3/8] Installing PHP dependencies...${NC}"
$COMPOSER_BIN install --optimize-autoloader --no-dev --no-interaction

# Install Node dependencies and build
if [ "$SKIP_NPM" = false ]; then
    echo -e "${YELLOW}[4/8] Installing Node dependencies...${NC}"
    $NPM_BIN ci

    echo -e "${YELLOW}[5/8] Building frontend assets...${NC}"
    $NPM_BIN run build
else
    echo -e "${YELLOW}[4/8] Skipping Node dependencies...${NC}"
    echo -e "${YELLOW}[5/8] Skipping frontend build...${NC}"
fi

# Run migrations
if [ "$SKIP_MIGRATE" = false ]; then
    echo -e "${YELLOW}[6/8] Running database migrations...${NC}"
    $PHP_BIN artisan migrate --force
else
    echo -e "${YELLOW}[6/8] Skipping database migrations...${NC}"
fi

# Optimize Laravel
echo -e "${YELLOW}[7/8] Optimizing application...${NC}"
$PHP_BIN artisan config:cache
$PHP_BIN artisan route:cache
$PHP_BIN artisan view:cache
$PHP_BIN artisan event:cache
$PHP_BIN artisan filament:cache-components

# Restart queue workers
echo -e "${YELLOW}[8/8] Restarting queue workers...${NC}"
$PHP_BIN artisan queue:restart

# Restart supervisor processes
if command -v supervisorctl &> /dev/null; then
    sudo supervisorctl restart all
fi

# Disable maintenance mode
if [ "$MAINTENANCE_MODE" = true ]; then
    $PHP_BIN artisan up
fi

echo ""
echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}  DEPLOYMENT COMPLETED SUCCESSFULLY!${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""
echo -e "Deployed at: $(date)"
echo -e "Branch: $BRANCH"
echo -e "Commit: $(git rev-parse --short HEAD)"
