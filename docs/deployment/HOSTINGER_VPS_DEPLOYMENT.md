# Hostinger VPS Deployment Guide

Complete guide for deploying the Itqan Platform on a Hostinger VPS (Ubuntu).

## Table of Contents
1. [Server Requirements](#server-requirements)
2. [Initial Server Setup](#initial-server-setup)
3. [Install Required Software](#install-required-software)
4. [Configure Nginx](#configure-nginx)
5. [Deploy Laravel Application](#deploy-laravel-application)
6. [Database Setup](#database-setup)
7. [Configure Supervisor](#configure-supervisor)
8. [Setup SSL Certificate](#setup-ssl-certificate)
9. [Configure Cron Jobs](#configure-cron-jobs)
10. [LiveKit Setup](#livekit-setup)
11. [Post-Deployment](#post-deployment)
12. [Troubleshooting](#troubleshooting)

---

## Server Requirements

### Minimum Specifications
- **CPU**: 2 vCPU
- **RAM**: 4 GB (8 GB recommended)
- **Storage**: 50 GB SSD
- **OS**: Ubuntu 22.04 LTS (recommended)
- **Network**: Static IP, Open ports 22, 80, 443

### Required Software
- PHP 8.2+
- MySQL 8.0+
- Redis 7+
- Nginx 1.24+
- Node.js 20+
- Composer 2.7+
- Supervisor
- Git

---

## Initial Server Setup

### 1.1 Connect to VPS
```bash
ssh root@YOUR_SERVER_IP
```

### 1.2 Update System
```bash
apt update && apt upgrade -y
```

### 1.3 Create Deploy User
```bash
# Create user
adduser deploy
usermod -aG sudo deploy

# Setup SSH key authentication
mkdir -p /home/deploy/.ssh
cp ~/.ssh/authorized_keys /home/deploy/.ssh/
chown -R deploy:deploy /home/deploy/.ssh
chmod 700 /home/deploy/.ssh
chmod 600 /home/deploy/.ssh/authorized_keys
```

### 1.4 Configure Firewall
```bash
ufw allow OpenSSH
ufw allow 'Nginx Full'
ufw allow 6001  # Laravel Reverb WebSocket
ufw enable
```

### 1.5 Secure SSH (Optional but Recommended)
```bash
# Edit SSH config
nano /etc/ssh/sshd_config

# Set these values:
# PermitRootLogin no
# PasswordAuthentication no
# PubkeyAuthentication yes

systemctl restart sshd
```

---

## Install Required Software

### 2.1 Install PHP 8.2
```bash
# Add PHP repository
add-apt-repository ppa:ondrej/php -y
apt update

# Install PHP and extensions
apt install -y php8.2-fpm php8.2-cli php8.2-common php8.2-mysql \
    php8.2-zip php8.2-gd php8.2-mbstring php8.2-curl php8.2-xml \
    php8.2-bcmath php8.2-intl php8.2-readline php8.2-redis \
    php8.2-imagick php8.2-soap php8.2-fileinfo
```

### 2.2 Install MySQL 8
```bash
apt install -y mysql-server

# Secure MySQL installation
mysql_secure_installation

# Create database and user
mysql -u root -p
```

```sql
CREATE DATABASE itqan_platform CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'itqan_user'@'localhost' IDENTIFIED BY 'ItqanDatabaseAdmin@2025';
GRANT ALL PRIVILEGES ON itqan_platform.* TO 'admin'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 2.3 Install Redis
```bash
apt install -y redis-server

# Configure Redis
nano /etc/redis/redis.conf
# Set: supervised systemd

systemctl restart redis
systemctl enable redis
```

### 2.4 Install Nginx
```bash
sudo apt install -y nginx
sudo systemctl enable nginx
sudo systemctl start nginx
```

### 2.5 Install Node.js 20
```bash
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
```

### 2.6 Install Composer
```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

### 2.7 Install Supervisor
```bash
sudo apt install -y supervisor
sudo systemctl enable supervisor
sudo systemctl start supervisor
```

### 2.8 Install Certbot (SSL)
```bash
sudo apt install -y certbot python3-certbot-nginx
```

---

## Configure Nginx

### 3.1 Create Nginx Configuration
```bash
sudo nano /etc/nginx/sites-available/itqan-platform
```

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name itqanway.com *.itqanway.com;
    root /var/www/itqan-platform/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header X-XSS-Protection "1; mode=block";

    index index.php;

    charset utf-8;

    # Gzip compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_proxied any;
    gzip_types text/plain text/css text/xml text/javascript application/javascript application/json application/xml;
    gzip_disable "MSIE [1-6]\.";

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Laravel Reverb WebSocket
    location /app {
        proxy_pass http://127.0.0.1:8080;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_read_timeout 60s;
        proxy_send_timeout 60s;
    }

    # Static file caching
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|woff|woff2|ttf|svg)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
    }

    # File upload limits
    client_max_body_size 100M;
}
```

### 3.2 Enable Site
```bash
sudo ln -s /etc/nginx/sites-available/itqan-platform /etc/nginx/sites-enabled/
sudo rm /etc/nginx/sites-enabled/default  # Remove default site
sudo nginx -t
sudo systemctl reload nginx
```

---

## Deploy Laravel Application

### 4.1 Create Directory Structure
```bash
sudo mkdir -p /var/www/itqan-platform
sudo chown -R deploy:www-data /var/www/itqan-platform
sudo chmod -R 775 /var/www/itqan-platform
```

### 4.2 Clone Repository
```bash
# As deploy user
su - deploy
cd /var/www/itqan-platform
git clone https://github.com/abdelrahman-hamdy/itqan-platform.git .
```

### 4.3 Install Dependencies
```bash
# PHP dependencies
composer install --optimize-autoloader --no-dev

# Node dependencies & build
npm install
npm run build
```

### 4.4 Configure Environment
```bash
cp .env.example .env
nano .env
```

```env
APP_NAME="Itqan Platform"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_TIMEZONE=Asia/Riyadh
APP_URL=https://itqanway.com
APP_LOCALE=ar
APP_FALLBACK_LOCALE=en

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=itqan_platform
DB_USERNAME=itqan_user
DB_PASSWORD=YOUR_DATABASE_PASSWORD

# Cache & Sessions
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=true

# Redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Broadcasting (Laravel Reverb)
BROADCAST_CONNECTION=reverb

REVERB_APP_ID=itqan-platform
REVERB_APP_KEY=your-reverb-app-key
REVERB_APP_SECRET=your-reverb-app-secret
REVERB_HOST=itqanway.com
REVERB_PORT=443
REVERB_SCHEME=https

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"

# Filesystem
FILESYSTEM_DISK=local

# Mail (configure based on your provider)
MAIL_MAILER=smtp
MAIL_HOST=smtp.hostinger.com
MAIL_PORT=465
MAIL_USERNAME=noreply@itqanway.com
MAIL_PASSWORD=your-email-password
MAIL_ENCRYPTION=ssl
MAIL_FROM_ADDRESS=noreply@itqanway.com
MAIL_FROM_NAME="${APP_NAME}"

# LiveKit (if using video meetings)
LIVEKIT_API_KEY=your-livekit-api-key
LIVEKIT_API_SECRET=your-livekit-api-secret
LIVEKIT_SERVER_URL=wss://your-livekit-server
```

### 4.5 Generate Application Key
```bash
php artisan key:generate
```

### 4.6 Set Permissions
```bash
sudo chown -R deploy:www-data /var/www/itqan-platform
sudo chmod -R 775 /var/www/itqan-platform/storage
sudo chmod -R 775 /var/www/itqan-platform/bootstrap/cache

# Create storage link
php artisan storage:link
```

### 4.7 Optimize Laravel
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan filament:cache-components
```

---

## Database Setup

### 5.1 Run Migrations
```bash
php artisan migrate --force
```

### 5.2 Seed Database (if needed)
```bash
php artisan db:seed --force
```

### 5.3 Create Admin User
```bash
php artisan tinker
```

```php
$user = \App\Models\User::create([
    'name' => 'Abdelrahman Hamdy',
    'email' => 'abdelrahmanhamdy320@gmail.com',
    'password' => bcrypt('pass123'),
    'role' => 'super_admin',
]);
```

---

## Configure Supervisor

Supervisor manages Laravel queue workers and Reverb WebSocket server.

### 6.1 Create Queue Worker Configuration
```bash
sudo nano /etc/supervisor/conf.d/itqan-worker.conf
```

```ini
[program:itqan-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/itqan-platform/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=deploy
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/itqan-platform/storage/logs/worker.log
stopwaitsecs=3600
```

### 6.2 Create Reverb Configuration
```bash
sudo nano /etc/supervisor/conf.d/itqan-reverb.conf
```

```ini
[program:itqan-reverb]
command=php /var/www/itqan-platform/artisan reverb:start --host=127.0.0.1 --port=8080
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=deploy
redirect_stderr=true
stdout_logfile=/var/www/itqan-platform/storage/logs/reverb.log
```

### 6.3 Start Supervisor
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start all
```

---

## Setup SSL Certificate

### 7.1 Obtain SSL Certificate
```bash
sudo certbot --nginx -d itqanway.com -d www.itqanway.com
```

### 7.2 Auto-Renewal Test
```bash
sudo certbot renew --dry-run
```

---

## Configure Cron Jobs

### 8.1 Add Laravel Scheduler
```bash
crontab -e -u deploy
```

Add this line:
```cron
* * * * * cd /var/www/itqan-platform && php artisan schedule:run >> /dev/null 2>&1
```

---

## LiveKit Setup

If using LiveKit for video meetings:

### 9.1 Option A: Self-Hosted LiveKit (Same or Different Server)
See [docs/deployment/LIVEKIT_RECORDINGS_SETUP.md](LIVEKIT_RECORDINGS_SETUP.md)

### 9.2 Option B: LiveKit Cloud
1. Create account at [https://cloud.livekit.io](https://cloud.livekit.io)
2. Create a project
3. Get API Key and Secret
4. Update `.env` with credentials

---

## Post-Deployment

### 10.1 Verify Application
```bash
# Test site access
curl -I https://itqanway.com

# Check logs
tail -f /var/www/itqan-platform/storage/logs/laravel.log
```

### 10.2 Setup Monitoring (Optional)
```bash
# Install htop for basic monitoring
sudo apt install -y htop

# View system resources
htop
```

### 10.3 Automated Backups
Create backup script:
```bash
nano /home/deploy/backup.sh
```

```bash
#!/bin/bash
DATE=$(date +%Y-%m-%d)
BACKUP_DIR="/home/deploy/backups"

# Create backup directory
mkdir -p $BACKUP_DIR

# Database backup
mysqldump -u itqan_user -p'YOUR_PASSWORD' itqan_platform > $BACKUP_DIR/db_$DATE.sql

# Files backup (storage)
tar -czf $BACKUP_DIR/storage_$DATE.tar.gz /var/www/itqan-platform/storage/app

# Remove backups older than 7 days
find $BACKUP_DIR -type f -mtime +7 -delete
```

```bash
chmod +x /home/deploy/backup.sh

# Add to cron (daily at 3 AM)
crontab -e -u deploy
# Add: 0 3 * * * /home/deploy/backup.sh
```

---

## Deployment Commands Summary

### Quick Deploy Script
Create `/home/deploy/deploy.sh`:
```bash
#!/bin/bash
set -e

cd /var/www/itqan-platform

# Pull latest changes
git pull origin main

# Install dependencies
composer install --optimize-autoloader --no-dev
npm install
npm run build

# Laravel optimization
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan filament:cache-components

# Restart services
sudo supervisorctl restart all

echo "Deployment completed successfully!"
```

```bash
chmod +x /home/deploy/deploy.sh
```

---

## Troubleshooting

### Common Issues

**1. 502 Bad Gateway**
```bash
# Check PHP-FPM status
sudo systemctl status php8.2-fpm
sudo systemctl restart php8.2-fpm
```

**2. Permission Denied**
```bash
sudo chown -R deploy:www-data /var/www/itqan-platform
sudo chmod -R 775 /var/www/itqan-platform/storage
```

**3. Queue Not Processing**
```bash
sudo supervisorctl status
sudo supervisorctl restart itqan-worker:*
```

**4. WebSocket Connection Failed**
```bash
# Check Reverb status
sudo supervisorctl status itqan-reverb

# Check logs
tail -f /var/www/itqan-platform/storage/logs/reverb.log
```

**5. Database Connection Error**
```bash
# Test MySQL connection
mysql -u itqan_user -p itqan_platform

# Check credentials in .env
cat /var/www/itqan-platform/.env | grep DB_
```

**6. SSL Certificate Issues**
```bash
sudo certbot certificates
sudo certbot renew --force-renewal
```

### Useful Commands

```bash
# Clear all caches
php artisan optimize:clear

# View application logs
tail -f /var/www/itqan-platform/storage/logs/laravel.log

# Check Nginx error log
sudo tail -f /var/log/nginx/error.log

# Check supervisor logs
sudo tail -f /var/log/supervisor/supervisord.log

# Restart all services
sudo systemctl restart nginx php8.2-fpm redis mysql
sudo supervisorctl restart all
```

---

## Security Checklist

- [ ] SSH key authentication only (disable password)
- [ ] Firewall configured (ufw)
- [ ] MySQL secured (mysql_secure_installation)
- [ ] APP_DEBUG=false in production
- [ ] SSL certificate installed
- [ ] Regular backups configured
- [ ] Log rotation configured
- [ ] Strong passwords for all services
- [ ] Failed login protection (fail2ban)
- [ ] Regular system updates

---

## Support

- Laravel Documentation: https://laravel.com/docs
- Nginx Documentation: https://nginx.org/en/docs/
- Hostinger Knowledge Base: https://support.hostinger.com
- LiveKit Documentation: https://docs.livekit.io
