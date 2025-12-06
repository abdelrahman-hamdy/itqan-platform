# Hostinger Shared Hosting Deployment Guide

## Overview
This guide will help you deploy the Itqan Platform to Hostinger shared hosting for testing purposes.

## Prerequisites
- Hostinger shared hosting account
- FTP/SFTP access or File Manager
- MySQL database access
- PHP 8.2+ support (required)

## Step 1: Pre-Deployment Preparation

### 1.1 Optimize for Production
Run these commands locally before uploading:
```bash
# Install dependencies (if not already done)
composer install --optimize-autoloader --no-dev

# Clear cache
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Build frontend assets (optional for testing)
npm run build
```

### 1.2 Create Deployment Package
The files to upload are:
- All files EXCEPT:
  - `node_modules/`
  - `vendor/` (will be uploaded separately)
  - `.git/`
  - `storage/logs/`
  - `.env` (you'll create a new one on server)
  - Development files (tests, docs, etc.)

## Step 2: Upload Files

### 2.1 Upload Structure
Upload files to your domain's `public_html/` directory. The structure should be:
```
public_html/
├── public/          → Your public/ folder contents
├── app/             → Your app/ folder
├── bootstrap/       → Your bootstrap/ folder
├── config/          → Your config/ folder
├── database/        → Your database/ folder
├── resources/       → Your resources/ folder
├── routes/          → Your routes/ folder
├── storage/         → Your storage/ folder
├── vendor/          → Upload composer dependencies
├── composer.json
├── composer.lock
├── package.json
├── artisan
├── .htaccess
└── Other Laravel files
```

### 2.2 Move Public Folder Contents
**IMPORTANT**: Hostinger shared hosting requires all Laravel files to be in public_html, but the public folder contents should be directly in public_html root.

**Option A: File Manager Method**
1. Extract the Laravel project
2. Upload all folders to a temporary directory
3. Move contents of `public/` folder to `public_html/`
4. Move all other folders to `public_html/` (creating subdirectories)

**Option B: FTP Method**
1. Upload the entire project to a temp folder
2. Move files using FTP client's move function:
   - Move `public_html_temp/public/*` to `public_html/`
   - Move `public_html_temp/app` to `public_html/app`
   - Move `public_html_temp/bootstrap` to `public_html/bootstrap`
   - And so on...

## Step 3: Database Configuration

### 3.1 Create MySQL Database
1. In Hostinger control panel, create a new MySQL database
2. Create a database user with full permissions
3. Note the database credentials:
   - Database name
   - Username
   - Password
   - Host (usually `localhost`)

### 3.2 Create .env File
Create a new `.env` file in your `public_html/` root with:

```env
APP_NAME="Itqan Platform"
APP_ENV=production
APP_KEY=base64:YOUR_GENERATED_KEY
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
```

### 3.3 Generate Application Key
Run via Hostinger's PHP console or SSH:
```bash
php artisan key:generate
```

## Step 4: Install Dependencies

### 4.1 Upload Vendor Folder
**Option A: Upload via FTP**
1. Run `composer install --optimize-autoloader --no-dev` locally
2. Upload the entire `vendor/` folder via FTP

**Option B: Use Hostinger PHP**
If you have SSH access:
```bash
composer install --optimize-autoloader --no-dev
```

## Step 5: Database Migration

### 5.1 Run Migrations
1. Upload the `database/` folder with all migrations
2. Run migrations via Hostinger's PHP console:
```bash
php artisan migrate --force
```

### 5.2 Seed Database (if needed)
```bash
php artisan db:seed --force
```

## Step 6: File Permissions

Set the following permissions via File Manager or FTP:
```
storage/          → 755
storage/app/      → 755
storage/framework/ → 755
storage/logs/     → 755
bootstrap/cache/  → 755
public/storage/   → 755
```

## Step 7: Configure .htaccess

Ensure you have the following `.htaccess` in your `public_html/` root:

```apache
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
```

## Step 8: Configure Web Server

### 8.1 PHP Version
Ensure you're using PHP 8.2+ in Hostinger control panel.

### 8.2 Required PHP Extensions
Verify these extensions are enabled:
- php-mysql
- php-curl
- php-json
- php-mbstring
- php-xml
- php-zip
- php-gd
- php-fileinfo

## Step 9: Test Deployment

### 9.1 Basic Test
Visit your domain to see if the application loads.

### 9.2 Test Admin Access
Navigate to `/admin` to access the Filament admin panel.

### 9.3 Check Logs
Monitor `storage/logs/laravel.log` for any errors.

## Step 10: Performance Optimization

### 10.1 Cache Configuration
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

### 10.2 Static File Optimization
- Enable Gzip compression in Hostinger control panel
- Set up browser caching headers
- Optimize images

## Troubleshooting

### Common Issues

1. **500 Internal Server Error**
   - Check `storage/logs/laravel.log`
   - Verify file permissions
   - Check .htaccess syntax

2. **Database Connection Failed**
   - Verify .env database credentials
   - Ensure MySQL service is running
   - Check database user permissions

3. **Permission Denied**
   - Set proper file permissions (755 for directories, 644 for files)
   - Ensure web server can write to storage/

4. **Class Not Found**
   - Run `composer dump-autoload`
   - Clear cache: `php artisan cache:clear`

5. **Assets Not Loading**
   - Run `npm run build` and upload public/build/
   - Check that public/ contents are in root

### Support
- Check Hostinger documentation for PHP configuration
- Review Laravel logs in `storage/logs/`
- Test with simple PHP script to verify server setup

## Security Notes
- Change default admin credentials after first login
- Set APP_DEBUG=false in production
- Use strong passwords for database
- Enable SSL certificate
- Regular backups of database and files

## Next Steps
After successful testing, consider:
- Setting up automated backups
- Configuring monitoring
- Setting up staging environment
- Performance monitoring
- Security audits
