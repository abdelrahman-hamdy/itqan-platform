#!/bin/bash

# Itqan Platform - Hostinger Shared Hosting Deployment Script
# This script prepares the application for deployment to Hostinger shared hosting

echo "🚀 Itqan Platform - Hostinger Deployment Preparation"
echo "=================================================="

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check if we're in the right directory
if [ ! -f "artisan" ]; then
    echo -e "${RED}Error: This script must be run from the Laravel project root directory${NC}"
    exit 1
fi

echo -e "${YELLOW}Step 1: Cleaning up for production deployment...${NC}"

# Remove development files and folders
echo "Removing development files..."
rm -rf .git/
rm -rf node_modules/
rm -rf tests/
rm -rf .github/
rm -f .env.example
rm -f phpunit.xml
rm -f pint.json
rm -f sail
rm -f webpack.mix.js

# Remove development documentation files
rm -f *.md
rm -f *.txt
rm -f *.log
rm -f debug_*.php
rm -f test_*.php
rm -f monitor_logs.sh
rm -f restore-chat-permissions.sh
rm -f setup-*.sh
rm -f start-*.sh
rm -f stop-*.sh
rm -f fix-*.php
rm -f run-*.php
rm -f migrate-*.php
rm -f remove-test-*.php
rm -f create-table-*.php
rm -f add-*-column.php
rm -f echo-server.log
rm -f cookies*.txt
rm -f temp_*.txt
rm -f test_*.txt
rm -f network-test.html
rm -f emergency-fix.html
rm -f current_homepage.html
rm -f fresh_homepage.html
rm -f homepage.html
rm -f login_response.html
rm -f minimal-test.html
rm -f livekit_test.html

# Remove demo and test directories
rm -rf "lessons demo/"

echo -e "${GREEN}✓ Development files removed${NC}"

echo -e "${YELLOW}Step 2: Installing production dependencies...${NC}"

# Install production dependencies
composer install --optimize-autoloader --no-dev --no-interaction

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Dependencies installed${NC}"
else
    echo -e "${RED}✗ Failed to install dependencies${NC}"
    exit 1
fi

echo -e "${YELLOW}Step 3: Optimizing for production...${NC}"

# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Optimize for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

echo -e "${GREEN}✓ Application optimized${NC}"

echo -e "${YELLOW}Step 4: Setting up build directories...${NC}"

# Create necessary directories
mkdir -p storage/framework/cache/data
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p storage/logs
mkdir -p bootstrap/cache
mkdir -p public/storage

# Set proper permissions
chmod -R 755 storage/
chmod -R 755 bootstrap/cache/
chmod -R 755 public/storage/

echo -e "${GREEN}✓ Directories created and permissions set${NC}"

echo -e "${YELLOW}Step 5: Creating deployment package structure...${NC}"

# Create deployment directory
DEPLOY_DIR="hostinger-deployment"
rm -rf $DEPLOY_DIR
mkdir -p $DEPLOY_DIR

# Copy essential files
echo "Copying essential files..."
cp -r app/ $DEPLOY_DIR/
cp -r bootstrap/ $DEPLOY_DIR/
cp -r config/ $DEPLOY_DIR/
cp -r database/ $DEPLOY_DIR/
cp -r resources/ $DEPLOY_DIR/
cp -r routes/ $DEPLOY_DIR/
cp -r storage/ $DEPLOY_DIR/
cp -r public/ $DEPLOY_DIR/
cp -r vendor/ $DEPLOY_DIR/
cp composer.json $DEPLOY_DIR/
cp composer.lock $DEPLOY_DIR/
cp artisan $DEPLOY_DIR/

# Copy essential root files
cp .htaccess $DEPLOY_DIR/ 2>/dev/null || echo "No .htaccess found, will create one"
cp package.json $DEPLOY_DIR/ 2>/dev/null
cp package-lock.json $DEPLOY_DIR/ 2>/dev/null
cp tailwind.config.js $DEPLOY_DIR/ 2>/dev/null
cp postcss.config.js $DEPLOY_DIR/ 2>/dev/null
cp vite.config.js $DEPLOY_DIR/ 2>/dev/null

echo -e "${GREEN}✓ Deployment package created in $DEPLOY_DIR/${NC}"

echo -e "${YELLOW}Step 6: Creating Hostinger-specific .env template...${NC}"

# Create .env template for Hostinger
cat > $DEPLOY_DIR/.env.example << 'EOF'
APP_NAME="Itqan Platform"
APP_ENV=production
APP_KEY=base64:GENERATE_WITH_PHP_ARTISAN_KEY_GENERATE
APP_DEBUG=false
APP_URL=https://yourdomain.com

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_username
DB_PASSWORD=your_password

BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120

MEMCACHED_HOST=127.0.0.1

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

PUSHER_APP_ID=
PUSHER_APP_KEY=
PUSHER_APP_SECRET=
PUSHER_HOST=
PUSHER_PORT=443
PUSHER_SCHEME=https
PUSHER_APP_CLUSTER=mt1

VITE_APP_NAME="${APP_NAME}"

# LiveKit Configuration (adjust for your setup)
LIVEKIT_API_KEY=your_livekit_api_key
LIVEKIT_API_SECRET=your_livekit_api_secret
LIVEKIT_WS_URL=wss://your-livekit-url

# Croft Configuration
CROFT_API_KEY=your_croft_api_key

# Additional Settings for Shared Hosting
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax
EOF

echo -e "${GREEN}✓ .env.example created${NC}"

echo -e "${YELLOW}Step 7: Creating Hostinger .htaccess...${NC}"

# Create Hostinger-optimized .htaccess
cat > $DEPLOY_DIR/.htaccess << 'EOF'
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    RewriteEngine On

    # Handle Authorization Header
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

    # Redirect Trailing Slashes If Not A Folder...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.*)/$
    RewriteRule ^ %1 [L,R=301]

    # Send Requests To Front Controller...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>

# Security Headers
<IfModule mod_headers.c>
    Header always set X-Content-Type-Options nosniff
    Header always set X-Frame-Options DENY
    Header always set X-XSS-Protection "1; mode=block"
</IfModule>

# Disable directory browsing
Options -Indexes

# Protect sensitive files
<FilesMatch "\.(env|log|sql|md)$">
    Order allow,deny
    Deny from all
</FilesMatch>

# Enable Gzip Compression
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript application/javascript application/x-javascript
</IfModule>

# Browser Caching
<IfModule mod_expires.c>
    ExpiresActive on
    ExpiresByType text/css "access plus 1 year"
    ExpiresByType application/javascript "access plus 1 year"
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType image/jpg "access plus 1 year"
    ExpiresByType image/jpeg "access plus 1 year"
    ExpiresByType image/gif "access plus 1 year"
    ExpiresByType image/svg+xml "access plus 1 year"
</IfModule>
EOF

echo -e "${GREEN}✓ .htaccess created${NC}"

echo -e "${YELLOW}Step 8: Creating deployment instructions...${NC}"

# Create deployment instructions
cat > $DEPLOY_DIR/DEPLOYMENT_INSTRUCTIONS.md << 'EOF'
# Hostinger Deployment Instructions

## Quick Start

1. **Upload Files**
   - Upload all contents of this folder to your domain's `public_html/` directory
   - Note: The public folder contents should be directly in public_html root

2. **Create Database**
   - Create a MySQL database in Hostinger control panel
   - Note the database name, username, and password

3. **Configure Environment**
   - Copy `.env.example` to `.env`
   - Edit `.env` with your database credentials
   - Generate application key: `php artisan key:generate`

4. **Run Migrations**
   - Run: `php artisan migrate --force`

5. **Set Permissions**
   - storage/: 755
   - bootstrap/cache/: 755
   - public/storage/: 755

## File Structure
Your public_html should look like:
```
public_html/
├── index.php (from public/)
├── .htaccess
├── .env
├── app/
├── bootstrap/
├── config/
├── database/
├── resources/
├── routes/
├── storage/
├── vendor/
└── composer files
```

## Testing
- Visit your domain to see the application
- Go to `/admin` for admin panel
- Check `storage/logs/laravel.log` for errors

## Troubleshooting
- 500 Error: Check file permissions and .htaccess
- Database Error: Verify .env credentials
- Permission Error: Set 755 for directories, 644 for files
EOF

echo -e "${GREEN}✓ Deployment instructions created${NC}"

echo -e "${YELLOW}Step 9: Creating package size report...${NC}"

# Calculate package size
PACKAGE_SIZE=$(du -sh $DEPLOY_DIR | cut -f1)
echo "Deployment package size: $PACKAGE_SIZE"
echo "Package location: $DEPLOY_DIR/"

echo -e "${GREEN}✓ Package size calculated${NC}"

echo ""
echo -e "${GREEN}🎉 Deployment preparation completed successfully!${NC}"
echo ""
echo -e "${YELLOW}Next Steps:${NC}"
echo "1. Upload the contents of '$DEPLOY_DIR/' to your Hostinger public_html/"
echo "2. Create a MySQL database in Hostinger control panel"
echo "3. Copy .env.example to .env and configure your database settings"
echo "4. Run 'php artisan key:generate' to generate your APP_KEY"
echo "5. Run 'php artisan migrate --force' to set up the database"
echo "6. Set proper file permissions (755 for directories, 644 for files)"
echo ""
echo -e "${YELLOW}Files to upload:${NC}"
echo "- All files and folders from the '$DEPLOY_DIR/' directory"
echo "- Make sure to set proper file permissions on the server"
echo ""
echo -e "${YELLOW}Security reminder:${NC}"
echo "- Change APP_DEBUG=false in .env"
echo "- Use strong database passwords"
echo "- Enable SSL certificate in Hostinger"
echo ""
echo -e "${GREEN}Happy deploying! 🚀${NC}"
