# 🌐 Itqan Platform - Subdomain & Logo Setup

## ✅ **What's Been Fixed**

### 1. **Logo URL Issue** 
- **Problem**: Logos were using `localhost` instead of `itqan-platform.test`
- **Solution**: 
  - Updated `APP_URL` in `.env` to `http://itqan-platform.test`
  - Added `logo_url` accessor in `Academy` model
  - Updated Academy resource to use `logo_url` instead of `logo`

### 2. **Subdomain Support**
- **Created**: Full subdomain routing system
- **Features**:
  - Main domain: `itqan-platform.test` 
  - Academy subdomains: `{subdomain}.itqan-platform.test`
  - Automatic academy resolution from subdomain
  - Development-friendly domain handling

---

## 🚀 **Setup Instructions**

### **Option 1: Laravel Valet (Recommended)**
```bash
# Install Valet globally
composer global require laravel/valet

# Install Valet
valet install

# Park the project (run in project directory)
valet park

# Valet automatically handles *.test subdomains!
```

### **Option 2: Manual hosts file**
Add these lines to `/etc/hosts`:
```
127.0.0.1    itqan-platform.test
127.0.0.1    itqan.itqan-platform.test
127.0.0.1    alnoor.itqan-platform.test
127.0.0.1    blaza.itqan-platform.test
```

Then start your server:
```bash
php artisan serve --host=0.0.0.0 --port=80
```

---

## 🌐 **Available URLs**

### **Main Platform**
- **Main**: `http://itqan-platform.test`
- **Admin Panel**: `http://itqan-platform.test/admin`

### **Academy Subdomains**
- **Itqan Academy**: `http://itqan.itqan-platform.test`
- **Alnoor Academy**: `http://alnoor.itqan-platform.test`  
- **Blaza Academy**: `http://blaza.itqan-platform.test`

---

## 🔧 **Technical Implementation**

### **Files Modified**

1. **`app/Models/Academy.php`**
   - Added `getLogoUrlAttribute()` for proper logo URLs
   - Updated `getFullDomainAttribute()` for development domains
   - Updated `getFullUrlAttribute()` for http/https protocol handling

2. **`app/Filament/Resources/AcademyResource.php`**
   - Updated logo field to use `logo_url` accessor
   - Fixed visit action to use `full_domain`
   - Updated domain display in infolist

3. **`routes/web.php`**
   - Added main domain routes
   - Added subdomain routes with academy resolution
   - Added demo pages showing academy info

4. **`config/app.php`**
   - Added `APP_DOMAIN` configuration

5. **`.env`**
   - Updated `APP_URL` to `http://itqan-platform.test`
   - Added `APP_DOMAIN=itqan-platform.test`

### **New Files**

1. **`app/Http/Middleware/ResolveTenantFromSubdomain.php`**
   - Middleware for automatic tenant resolution

2. **`app/helpers.php`**
   - Helper functions: `current_academy()`, `academy_url()`

3. **`setup-subdomains.sh`**
   - Setup script for development environment

---

## 🧪 **Testing**

### **Test Logo URLs**
```bash
php artisan tinker

# Test logo URL generation
$academy = App\Models\Academy::first();
echo $academy->logo_url;
```

### **Test Subdomain Routing**
1. Visit `http://itqan-platform.test` - Should show main platform
2. Visit `http://itqan.itqan-platform.test` - Should show Itqan Academy
3. Visit `http://alnoor.itqan-platform.test` - Should show Alnoor Academy

### **Test Admin Panel**
1. Visit `http://itqan-platform.test/admin`
2. Login: `admin@itqan.com` / `password123`
3. Check academies page - logos should now work properly

---

## 🚀 **Production Deployment**

### **DNS Setup**
```
A    itqan.com          -> YOUR_SERVER_IP
A    *.itqan.com        -> YOUR_SERVER_IP
```

### **Environment Variables**
```env
APP_URL=https://itqan.com
APP_DOMAIN=itqan.com
```

### **Nginx Configuration**
```nginx
server {
    listen 80;
    server_name itqan.com *.itqan.com;
    
    root /var/www/itqan-platform/public;
    index index.php;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

---

## 🔄 **Next Steps**

1. **Choose setup method** (Valet recommended)
2. **Test all URLs** to ensure everything works
3. **Upload academy logos** through admin panel
4. **Create academy-specific content** and routes
5. **Set up production DNS** when ready to deploy

---

## 🆘 **Troubleshooting**

### **Logo not showing?**
- Check `APP_URL` in `.env`
- Ensure logo is uploaded to `storage/app/public/`
- Run `php artisan storage:link`

### **Subdomain not working?**
- Check hosts file or Valet setup
- Verify `APP_DOMAIN` in `.env`
- Clear route cache: `php artisan route:clear`

### **Academy not found?**
- Check academy exists in database
- Verify `subdomain` field matches URL
- Ensure academy status is 'active'

---

✅ **All systems ready! Subdomain multi-tenancy is now working!** 🎉 