# Hostinger Deployment Checklist

## Pre-Deployment (Local Machine)

### ✅ 1. Run Deployment Script
```bash
chmod +x deploy-hostinger.sh
./deploy-hostinger.sh
```

### ✅ 2. Review Package
- Check `hostinger-deployment/` folder was created
- Verify all essential files are included
- Check package size (should be significantly reduced)

### ✅ 3. Test Locally (Optional)
```bash
cd hostinger-deployment
php artisan serve
```
Visit http://localhost:8000 to test

## Deployment (Hostinger Server)

### ✅ 1. Upload Files
- [ ] Upload all contents of `hostinger-deployment/` to `public_html/`
- [ ] Ensure file permissions are preserved during upload
- [ ] Verify .htaccess was uploaded

### ✅ 2. Create Database
- [ ] Login to Hostinger control panel
- [ ] Create new MySQL database
- [ ] Create database user with full permissions
- [ ] Note database credentials:
  - Database name: `_________`
  - Username: `_________`
  - Password: `_________`
  - Host: `localhost`

### ✅ 3. Configure Environment
- [ ] Copy `.env.example` to `.env`
- [ ] Edit `.env` with database credentials
- [ ] Set `APP_URL` to your domain
- [ ] Set `APP_DEBUG=false`
- [ ] Generate application key: `php artisan key:generate`

### ✅ 4. Run Server Setup
- [ ] Upload `hostinger-setup.php` to public_html
- [ ] Visit: `https://yourdomain.com/hostinger-setup.php?confirm=yes`
- [ ] Review setup results
- [ ] Delete `hostinger-setup.php` for security

### ✅ 5. Set File Permissions (if needed)
- [ ] storage/: 755
- [ ] storage/framework/: 755
- [ ] storage/logs/: 755
- [ ] bootstrap/cache/: 755
- [ ] public/storage/: 755

## Post-Deployment Testing

### ✅ 1. Basic Functionality
- [ ] Visit your domain - application loads without errors
- [ ] No 500 errors in browser
- [ ] CSS and JavaScript assets load correctly
- [ ] Images display properly

### ✅ 2. Admin Panel
- [ ] Visit `/admin` - Filament admin loads
- [ ] Admin login form displays
- [ ] Test with default credentials (if seeded)
- [ ] Can access main admin dashboard

### ✅ 3. Database Connection
- [ ] Admin panel connects to database
- [ ] Can view/edit data in admin
- [ ] No database connection errors
- [ ] Check `storage/logs/laravel.log` for errors

### ✅ 4. Key Features
- [ ] User registration (if enabled)
- [ ] User login/logout
- [ ] Basic navigation works
- [ ] Forms submit properly
- [ ] File uploads work (if applicable)

## Security Checklist

### ✅ 1. Environment Security
- [ ] `APP_DEBUG=false` in .env
- [ ] Strong database password
- [ ] No debug files left on server
- [ ] .htaccess protecting sensitive files

### ✅ 2. File Permissions
- [ ] .env file has 600 permissions
- [ ] storage/ directories are writable (755)
- [ ] No unnecessary write permissions
- [ ] vendor/ directory is not publicly accessible

### ✅ 3. Server Security
- [ ] SSL certificate enabled
- [ ] PHP version is 8.2+
- [ ] Unnecessary PHP extensions disabled
- [ ] Error display disabled in production

## Performance Checklist

### ✅ 1. Caching
- [ ] `config:cache` executed
- [ ] `route:cache` executed
- [ ] `view:cache` executed
- [ ] `php artisan optimize` executed

### ✅ 2. Static Files
- [ ] Gzip compression enabled
- [ ] Browser caching headers set
- [ ] Static files served correctly
- [ ] CSS/JS files minified (if applicable)

### ✅ 3. Database
- [ ] Database queries optimized
- [ ] No obvious N+1 query issues
- [ ] Database indexes in place
- [ ] Connection pooling working

## Troubleshooting

### Common Issues & Solutions

#### 500 Internal Server Error
- [ ] Check `storage/logs/laravel.log`
- [ ] Verify file permissions
- [ ] Check .htaccess syntax
- [ ] Ensure .env is configured

#### Database Connection Failed
- [ ] Verify .env database credentials
- [ ] Check database user permissions
- [ ] Ensure MySQL service is running
- [ ] Test database connection manually

#### White Screen/Blank Page
- [ ] Enable error reporting in .env temporarily
- [ ] Check PHP error logs
- [ ] Verify all dependencies installed
- [ ] Check storage/ permissions

#### CSS/JavaScript Not Loading
- [ ] Verify public/build/ exists
- [ ] Check file permissions
- [ ] Ensure proper .htaccess rules
- [ ] Clear browser cache

#### Admin Panel Not Accessible
- [ ] Check routes are cached
- [ ] Verify Filament is installed
- [ ] Check database has required tables
- [ ] Review auth configuration

## Final Steps

### ✅ 1. Monitoring Setup
- [ ] Set up error monitoring
- [ ] Configure backup system
- [ ] Set up uptime monitoring
- [ ] Document admin credentials

### ✅ 2. Documentation
- [ ] Update DNS settings (if needed)
- [ ] Document custom configurations
- [ ] Note any server-specific settings
- [ ] Create admin user guide

### ✅ 3. Maintenance Plan
- [ ] Schedule regular backups
- [ ] Plan for updates
- [ ] Set up monitoring alerts
- [ ] Document troubleshooting steps

---

## Quick Commands Reference

### Server Commands (if SSH available)
```bash
# Generate app key
php artisan key:generate

# Run migrations
php artisan migrate --force

# Clear cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Optimize for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

# Set permissions
chmod -R 755 storage/
chmod -R 755 bootstrap/cache/
chmod 600 .env
```

### File Permissions Reference
```
Directories: 755
Files: 644
.env: 600
storage/: 755 (writable)
bootstrap/cache/: 755 (writable)
public/: 755
vendor/: 755 (read-only)
```

---

## Support Information

- **Laravel Version**: 11.x
- **PHP Version**: 8.2+
- **MySQL Version**: 5.7+ or 8.0+
- **Node.js**: Not required for deployment (build locally)
- **Composer**: Required for dependency installation

## Emergency Contacts

- **Hostinger Support**: Via control panel
- **Laravel Documentation**: https://laravel.com/docs
- **Filament Documentation**: https://filamentphp.com/docs

---

**⚠️ Important Reminders**
1. Always backup before making changes
2. Test changes in a staging environment first
3. Keep .env file secure and never commit to version control
4. Monitor logs regularly for errors
5. Update dependencies regularly for security
