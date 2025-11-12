# ✅ Itqan Academy Setup Complete

## 🎓 Academy Information

**Academy Name:** أكاديمية إتقان (Itqan Academy)
**Subdomain:** itqan-academy
**Country:** Saudi Arabia (SA)
**Timezone:** Asia/Riyadh
**Status:** Active ✅

---

## 🌐 Access URLs

### Main Academy Website
```
http://itqan-academy.itqan-platform.test
```

### Admin Panel (Filament)
```
http://itqan-academy.itqan-platform.test/admin
```

### Alternative (if using Valet)
```
http://itqan-academy.itqan-platform.test
```

---

## 👤 Super Admin Credentials

### Login Information
```
Email:    admin@itqan-academy.com
Password: Admin@123456
Role:     Super Admin
Status:   Active
```

### User Details
- **Full Name:** Super Admin
- **First Name:** Super
- **Last Name:** Admin
- **Phone:** +966500000000
- **User Type:** admin
- **Academy ID:** 1
- **User ID:** 1
- **Email Verified:** ✅ Yes
- **Phone Verified:** ✅ Yes

---

## ⚙️ Academy Settings

The academy has been configured with the following default settings:

### Session Settings
- **Default Session Duration:** 60 minutes
- **Preparation Time:** 15 minutes before session
- **Buffer Time:** 5 minutes between sessions
- **Late Tolerance:** 10 minutes

### Attendance Settings
- **Attendance Threshold:** 80%

### Trial Settings
- **Trial Session Duration:** 30 minutes
- **Trial Expiration:** 7 days

---

## 🚀 Getting Started

### 1. Access the Admin Panel

Open your browser and navigate to:
```
http://itqan-academy.itqan-platform.test/admin
```

### 2. Login

Use the credentials provided above:
- **Email:** admin@itqan-academy.com
- **Password:** Admin@123456

### 3. First Steps After Login

1. **Update Your Profile**
   - Change your password
   - Add a profile picture
   - Update contact information

2. **Configure Academy Settings**
   - Navigate to Academy Settings
   - Update academy logo and branding
   - Configure payment methods
   - Set up email notifications

3. **Add Content**
   - Create teacher accounts
   - Add students
   - Create courses (Quran/Academic/Interactive)
   - Set up schedules

---

## 📊 Database Information

### Created Records

**Academy:**
- ID: 1
- Name: أكاديمية إتقان
- Subdomain: itqan-academy

**User:**
- ID: 1
- Email: admin@itqan-academy.com
- Type: admin

**Academy Settings:**
- Linked to Academy ID: 1
- All defaults configured

---

## 🔐 Security Recommendations

### Immediate Actions

1. **Change Default Password**
   ```
   Login → Profile → Change Password
   ```
   - Use a strong password (12+ characters)
   - Include uppercase, lowercase, numbers, and symbols
   - Do not share with anyone

2. **Enable Two-Factor Authentication** (if available)
   - Navigate to Security Settings
   - Enable 2FA for admin account

3. **Update Contact Email** (Optional)
   - Change to your personal email
   - Verify the new email address

### Access Control

- Create separate admin accounts for each team member
- Do not share the superadmin credentials
- Use role-based access control
- Regularly audit user accounts

---

## 🧪 Testing Your Setup

### 1. Test Homepage
Visit: `http://itqan-academy.itqan-platform.test`
**Expected:** Academy homepage loads

### 2. Test Admin Login
Visit: `http://itqan-academy.itqan-platform.test/admin`
**Expected:** Login page → Enter credentials → Dashboard loads

### 3. Test Chat System (If Enabled)
Visit: `http://itqan-academy.itqan-platform.test/chat`
**Expected:** Chat interface loads with real-time features

### 4. Test Database
```bash
php artisan tinker
```
```php
// Verify academy exists
\App\Models\Academy::find(1);

// Verify admin user exists
\App\Models\User::find(1);

// Test authentication
\Auth::loginUsingId(1);
\Auth::user()->name; // Should return "Super Admin"
```

---

## 📁 File Structure

Your academy data is stored in:

### Database Tables
- `academies` - Academy information
- `users` - User accounts (including admin)
- `academy_settings` - Academy configuration

### Storage Locations
- `/storage/app/public/avatars/` - User profile pictures
- `/storage/app/public/academies/` - Academy logos/files
- `/public/uploads/` - Public uploaded files

---

## 🌍 Multi-Tenant Setup

This platform supports multiple academies on the same installation:

### Current Setup
- **Main Domain:** itqan-platform.test
- **Academy Subdomain:** itqan-academy.itqan-platform.test

### To Add More Academies
```php
// Create another academy with different subdomain
$academy2 = Academy::create([
    'name' => 'Another Academy',
    'subdomain' => 'another-academy',
    'country' => 'SA',
    // ... other fields
]);
```

Each academy is completely isolated:
- Separate users
- Separate content
- Separate data
- Own subdomain

---

## 🔧 Configuration Files

### Environment (.env)
Make sure these are set correctly:
```env
APP_URL=http://itqan-platform.test
SESSION_DOMAIN=.itqan-platform.test
SANCTUM_STATEFUL_DOMAINS=*.itqan-platform.test,localhost:3000
```

### Valet/Herd Configuration
If using Laravel Valet or Herd:
```bash
# Link the project
valet link itqan-platform

# Secure with SSL (optional)
valet secure itqan-platform

# Check links
valet links
```

---

## 📞 Support & Troubleshooting

### Common Issues

#### 1. Cannot Access Subdomain
**Solution:**
```bash
# Flush DNS cache
sudo dscacheutil -flushcache; sudo killall -HUP mDNSResponder

# Restart Valet
valet restart
```

#### 2. Login Not Working
**Solution:**
- Clear browser cookies
- Clear Laravel cache: `php artisan cache:clear`
- Check database: User exists and status is 'active'

#### 3. 404 on Admin Panel
**Solution:**
```bash
php artisan route:clear
php artisan config:clear
php artisan optimize:clear
```

### Getting Help

1. **Check Logs**
   ```bash
   tail -f storage/logs/laravel.log
   ```

2. **Run Diagnostics**
   ```bash
   php artisan about
   ```

3. **Test Database Connection**
   ```bash
   php artisan tinker
   DB::connection()->getPdo();
   ```

---

## 📝 Next Steps

### Content Setup

1. **Quran Teachers**
   - Add Quran teacher profiles
   - Set qualifications (Ijazah, etc.)
   - Configure pricing

2. **Academic Teachers**
   - Add academic subjects
   - Create grade levels
   - Add teacher profiles
   - Create packages

3. **Students & Parents**
   - Add student accounts
   - Link to parent accounts
   - Assign to classes/circles

4. **Courses & Schedules**
   - Create Quran circles
   - Set up academic lessons
   - Configure interactive courses
   - Build schedules

### Marketing & Branding

1. **Update Academy Branding**
   - Upload academy logo
   - Set primary/secondary colors
   - Add academy description

2. **Public Pages**
   - Customize homepage
   - Add about us page
   - Configure contact information

---

## ✅ Verification Checklist

Before going live, verify:

- [ ] Admin can login successfully
- [ ] Password has been changed from default
- [ ] Academy logo uploaded
- [ ] Primary/secondary colors configured
- [ ] Contact information updated
- [ ] Email settings configured (SMTP)
- [ ] Payment gateway configured (if needed)
- [ ] At least one teacher account created
- [ ] At least one test student created
- [ ] Sample course/lesson created
- [ ] Chat system tested (if enabled)
- [ ] All pages load without errors
- [ ] Database backups configured

---

## 🎉 You're All Set!

Your **Itqan Academy** is now ready to use!

### Quick Access
- **URL:** http://itqan-academy.itqan-platform.test/admin
- **Email:** admin@itqan-academy.com
- **Password:** Admin@123456

**Important:** Please change the default password immediately after first login!

---

**Created:** November 12, 2025
**Status:** ✅ Active and Ready
**Database:** Fully Migrated (93 migrations)
**Chat System:** Enhanced Real-time Features Active
