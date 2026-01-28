# Itqan Platform - Server Documentation

Production server reference for deployment, configuration, and maintenance.

---

## Server Overview

| Property         | Value                                      |
|------------------|--------------------------------------------|
| **Hostname**     | `srv1147550`                               |
| **IP (IPv4)**    | `72.62.92.156`                             |
| **IP (IPv6)**    | `2a02:4780:41:52de::1`                     |
| **OS**           | Ubuntu 22.04.5 LTS (Jammy Jellyfish)       |
| **Disk**         | 388 GB (SSD)                               |
| **RAM**          | 31 GB                                      |
| **SSH User**     | `deploy`                                   |
| **Web User**     | `www-data`                                 |
| **Domain**       | `itqanway.com` (wildcard `*.itqanway.com`) |

---

## Software Stack

| Software     | Version           | Config Location                              |
|--------------|-------------------|----------------------------------------------|
| **Nginx**    | 1.18.0            | `/etc/nginx/sites-enabled/itqan-platform`    |
| **PHP**      | 8.4.16 (FPM)      | `/etc/php/8.4/fpm/pool.d/www.conf`           |
| **MySQL**    | 8.0.44            | Default Ubuntu config                        |
| **Redis**    | 6.0.16            | Default Ubuntu config                        |
| **Node.js**  | 20.19.6           | —                                            |
| **npm**      | 10.8.2            | —                                            |
| **Composer** | 2.9.2             | —                                            |
| **Supervisor** | System package  | `/etc/supervisor/conf.d/`                    |
| **Certbot**  | Let's Encrypt     | `/etc/letsencrypt/live/itqanway.com-0001/`   |

> **Note:** PHP 8.2 and 8.3 FPM are also installed but Nginx is configured to use the **8.4 socket**.

---

## Directory Structure

```
/var/www/itqan-platform/          # Laravel application root
├── app/                          # Application code
├── bootstrap/cache/              # Framework cache (775, deploy:www-data)
├── config/                       # Configuration files
├── database/                     # Migrations, seeders, factories
├── deployment/                   # Deployment scripts & supervisor configs
│   ├── deploy.sh                 # Full deployment script
│   └── supervisor/               # Supervisor config templates
│       ├── itqan-worker.conf     # Queue worker (2 processes)
│       ├── itqan-reverb.conf     # WebSocket server
│       └── itqan-scheduler.conf  # Laravel scheduler (alternative to cron)
├── lang/                         # Translation files (ar, en)
├── node_modules/                 # Node dependencies
├── public/                       # Web root (755, deploy:www-data)
│   └── build/                    # Vite compiled assets
├── resources/                    # Blade views, CSS, JS source
├── routes/                       # Route definitions
├── storage/                      # Logs, cache, uploads (775, deploy:www-data)
│   ├── app/tenants/              # Tenant-isolated file storage
│   └── logs/
│       ├── laravel.log           # Application log
│       ├── worker.log            # Queue worker log
│       └── reverb.log            # WebSocket server log
├── vendor/                       # Composer dependencies
├── .env                          # Environment config (755, deploy:deploy)
├── artisan                       # Laravel CLI
├── composer.json / .lock         # PHP dependencies
├── package.json / -lock.json     # Node dependencies
├── vite.config.js                # Vite build config
└── tailwind.config.js            # TailwindCSS config

/home/deploy/
├── backups/                      # Database and file backups
└── .ssh/                         # SSH keys
```

---

## Environment Configuration (.env)

Key production settings (secrets redacted):

```env
APP_NAME="Itqan Platform"
APP_ENV=production
APP_DEBUG=false
APP_TIMEZONE=Asia/Riyadh
APP_URL=https://itqanway.com
APP_DOMAIN=itqanway.com
APP_LOCALE=ar
APP_FALLBACK_LOCALE=en

DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=itqan_platform

CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
BROADCAST_CONNECTION=reverb

REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379

REVERB_HOST=itqanway.com
REVERB_PORT=443
REVERB_SCHEME=https

MAIL_MAILER=smtp
MAIL_HOST=smtp.hostinger.com
MAIL_PORT=465
MAIL_ENCRYPTION=ssl
MAIL_FROM_ADDRESS=noreply@itqanway.com

LIVEKIT_SERVER_URL=wss://conference.itqanway.com

SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax
SESSION_LIFETIME=120
FILESYSTEM_DISK=local
LOG_CHANNEL=stack
LOG_LEVEL=error
```

---

## Nginx Configuration

File: `/etc/nginx/sites-enabled/itqan-platform`

- HTTP (port 80) redirects all traffic to HTTPS (301)
- HTTPS (port 443) with wildcard SSL for `*.itqanway.com`
- PHP handled via FastCGI to `unix:/var/run/php/php8.4-fpm.sock`
- WebSocket proxy: `/app` -> `127.0.0.1:8080` (Laravel Reverb)
- Static assets served directly by Nginx
- Security headers: `X-Frame-Options`, `X-Content-Type-Options`
- Hidden files (`.env`, `.git`) blocked with `deny all`

**SSL Certificate:** `/etc/letsencrypt/live/itqanway.com-0001/` (Let's Encrypt, auto-renewed by certbot)

---

## PHP-FPM Configuration

File: `/etc/php/8.4/fpm/pool.d/www.conf`

| Setting              | Value                         |
|----------------------|-------------------------------|
| User/Group           | `www-data`                    |
| Listen               | `/run/php/php8.4-fpm.sock`    |
| Process Manager      | `dynamic`                     |
| Max Children         | 5                             |
| Start Servers        | 2                             |
| Min Spare Servers    | 1                             |
| Max Spare Servers    | 3                             |
| Upload Max Filesize  | 2M (CLI), check FPM ini       |
| Post Max Size        | 8M (CLI), check FPM ini       |
| Memory Limit         | Unlimited (CLI)               |

---

## Background Processes (Supervisor)

### Active Supervisor Programs

| Program         | Command                                          | Processes | Log File                         |
|-----------------|--------------------------------------------------|-----------|----------------------------------|
| `itqan-worker`  | `php artisan queue:work redis --sleep=3 --tries=3 --max-time=3600` | 2 | `storage/logs/worker.log` |
| `reverb`        | `php artisan reverb:start --host=0.0.0.0 --port=8080` | 1 | `storage/logs/reverb.log` |

Config files on server: `/etc/supervisor/conf.d/itqan-worker.conf`, `/etc/supervisor/conf.d/reverb.conf`

Template files in repo: `deployment/supervisor/`

### Supervisor Commands (require sudo)

```bash
sudo supervisorctl status                    # View all process statuses
sudo supervisorctl restart itqan-worker:*    # Restart queue workers
sudo supervisorctl restart reverb            # Restart WebSocket server
sudo supervisorctl reread && sudo supervisorctl update  # Reload configs
```

### Cron Jobs

```
* * * * * cd /var/www/itqan-platform && php artisan schedule:run >> /dev/null 2>&1
```

Runs the Laravel scheduler every minute, which triggers all scheduled commands defined in `routes/console.php`.

---

## Deployment Procedures

### Quick Deploy (Code Changes Only)

Use this when only PHP/Blade files changed (no new dependencies or migrations):

```bash
# Via SSH MCP or direct SSH
cd /var/www/itqan-platform
git pull origin main
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart
```

### Full Deploy (Using deploy.sh)

For changes that include dependencies, migrations, or frontend assets:

```bash
cd /var/www/itqan-platform
./deployment/deploy.sh                  # Standard deployment
./deployment/deploy.sh --skip-npm       # Skip frontend build
./deployment/deploy.sh --fresh          # DANGER: Fresh migration (destroys data)
```

The deploy script performs these steps:
1. Pre-deployment checks (`.env` safety, debug mode off)
2. Enable maintenance mode (`php artisan down`)
3. `git pull origin main`
4. `composer install --no-dev --optimize-autoloader`
5. `npm ci && npm run build` (unless `--skip-npm`)
6. `php artisan migrate --force`
7. Clear and cache config, routes, views
8. `php artisan queue:restart`
9. Disable maintenance mode (`php artisan up`)

### Deploy via Claude Code (SSH MCP)

When using the SSH MCP server tool:

```
1. Commit and push locally:     git add -A && git commit && git push origin main
2. Pull on server:              ssh> cd /var/www/itqan-platform && git pull origin main
3. Clear/rebuild caches:        ssh> php artisan config:clear && php artisan cache:clear && php artisan route:clear && php artisan view:clear && php artisan config:cache && php artisan route:cache && php artisan view:cache
4. Restart queue:               ssh> php artisan queue:restart
5. If migrations needed:        ssh> php artisan migrate --force
6. If dependencies changed:     ssh> composer install --no-dev --optimize-autoloader
7. If frontend assets changed:  ssh> npm ci && npm run build
```

---

## File Permissions

| Path               | Owner:Group      | Mode |
|--------------------|------------------|------|
| Application files  | `deploy:www-data`| 755  |
| `storage/`         | `deploy:www-data`| 775  |
| `bootstrap/cache/` | `deploy:www-data`| 775  |
| `public/`          | `deploy:www-data`| 755  |
| `.env`             | `deploy:deploy`  | 755  |

If permissions get broken:

```bash
sudo chown -R deploy:www-data /var/www/itqan-platform
sudo chmod -R 755 /var/www/itqan-platform
sudo chmod -R 775 /var/www/itqan-platform/storage
sudo chmod -R 775 /var/www/itqan-platform/bootstrap/cache
```

---

## Database

| Property       | Value                  |
|----------------|------------------------|
| Engine         | MySQL 8.0              |
| Host           | `127.0.0.1`            |
| Port           | `3306`                 |
| Database       | `itqan_platform`       |
| Charset        | utf8mb4                |

### Backup

```bash
# Create backup
mysqldump -u root -p itqan_platform > /home/deploy/backups/itqan_$(date +%Y%m%d_%H%M%S).sql

# Restore backup
mysql -u root -p itqan_platform < /home/deploy/backups/backup_file.sql
```

---

## Monitoring & Logs

### Application Logs

```bash
# Real-time application log
tail -f /var/www/itqan-platform/storage/logs/laravel.log

# Queue worker log
tail -f /var/www/itqan-platform/storage/logs/worker.log

# WebSocket (Reverb) log
tail -f /var/www/itqan-platform/storage/logs/reverb.log

# Nginx access/error logs
tail -f /var/log/nginx/access.log
tail -f /var/log/nginx/error.log
```

### Service Health Checks

```bash
# Check all services are running
systemctl status nginx
systemctl status php8.4-fpm
systemctl status mysql
systemctl status redis-server
systemctl status supervisor

# Check supervisor processes
sudo supervisorctl status

# Check Laravel health
cd /var/www/itqan-platform && php artisan about
```

---

## Troubleshooting

### Application 500 errors

```bash
# Check Laravel log
tail -50 /var/www/itqan-platform/storage/logs/laravel.log

# Clear all caches
cd /var/www/itqan-platform
php artisan config:clear && php artisan cache:clear && php artisan route:clear && php artisan view:clear

# Rebuild caches
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

### Queue not processing jobs

```bash
# Check worker status
sudo supervisorctl status itqan-worker:*

# Restart workers
sudo supervisorctl restart itqan-worker:*

# Or signal graceful restart via artisan
php artisan queue:restart

# Check failed jobs
cd /var/www/itqan-platform && php artisan queue:failed
```

### WebSocket not connecting

```bash
# Check Reverb status
sudo supervisorctl status reverb

# Restart Reverb
sudo supervisorctl restart reverb

# Verify Nginx proxy passes /app to port 8080
curl -I https://itqanway.com/app
```

### Permission denied errors

```bash
sudo chown -R deploy:www-data /var/www/itqan-platform/storage
sudo chmod -R 775 /var/www/itqan-platform/storage
sudo chown -R deploy:www-data /var/www/itqan-platform/bootstrap/cache
sudo chmod -R 775 /var/www/itqan-platform/bootstrap/cache
```

### Nginx config changes

```bash
# Test config syntax
sudo nginx -t

# Reload (no downtime)
sudo systemctl reload nginx

# Full restart
sudo systemctl restart nginx
```

### PHP-FPM restart

```bash
sudo systemctl restart php8.4-fpm
```

### SSL Certificate Renewal

Let's Encrypt certificates auto-renew via certbot. To manually renew:

```bash
sudo certbot renew
sudo systemctl reload nginx
```

---

## Key URLs

| URL                              | Purpose                |
|----------------------------------|------------------------|
| `https://itqanway.com`          | Main application       |
| `https://{tenant}.itqanway.com` | Tenant subdomain       |
| `wss://itqanway.com/app`        | WebSocket (Reverb)     |
| `wss://conference.itqanway.com` | LiveKit video server   |
