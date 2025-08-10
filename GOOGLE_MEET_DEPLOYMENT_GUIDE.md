# Google Meet Integration - دليل النشر والإعداد

## 📋 نظرة عامة

تم تطوير نظام متكامل لإدارة الجلسات التعليمية وربطها بـ Google Meet. يشمل النظام:

- ✅ إعدادات Google Meet في لوحات الإدارة
- ✅ تقويم شخصي للمعلمين والطلاب
- ✅ إنشاء الجلسات والاجتماعات تلقائياً
- ✅ نظام احتياطي عند فشل حسابات المعلمين
- ✅ وظائف Cron للأتمتة الكاملة

---

## 🚀 خطوات النشر

### 1. تطبيق قاعدة البيانات

```bash
# تطبيق التحديثات الجديدة
php artisan migrate

# التأكد من عدم وجود أخطاء
php artisan migrate:status
```

### 2. تثبيت المكتبات المطلوبة

```bash
# مكتبة Google API Client
composer require google/apiclient:"^2.15"

# Carbon للتعامل مع التواريخ (عادة مثبت مسبقاً)
composer require nesbot/carbon
```

### 3. إعداد متغيرات البيئة

أضف المتغيرات التالية إلى ملف `.env`:

```env
# Google API Keys (سيتم الحصول عليها من Google Cloud Console)
GOOGLE_CLIENT_ID=your_google_client_id
GOOGLE_CLIENT_SECRET=your_google_client_secret

# اختياري: إعدادات إضافية
GOOGLE_REDIRECT_URI=https://yourdomain.com/google/callback
QUEUE_CONNECTION=database
```

### 4. إعداد طابور المعالجة

```bash
# إنشاء جدول الوظائف
php artisan queue:table
php artisan migrate

# تشغيل معالج الطابور (في بيئة الإنتاج)
php artisan queue:work --daemon --sleep=3 --tries=3
```

### 5. تشغيل المُجدوِل (Cron Jobs)

أضف السطر التالي إلى crontab الخادم:

```bash
# فتح crontab
crontab -e

# إضافة هذا السطر
* * * * * cd /var/www/your-project && php artisan schedule:run >> /dev/null 2>&1
```

---

## 🔧 إعداد Google Cloud Platform

### الخطوة 1: إنشاء مشروع Google Cloud

1. اذهب إلى [Google Cloud Console](https://console.cloud.google.com/)
2. أنشئ مشروع جديد أو اختر مشروع موجود
3. احفظ **Project ID** - ستحتاجه لاحقاً

### الخطوة 2: تفعيل APIs المطلوبة

```bash
# أو من الواجهة: APIs & Services > Enable APIs & Services
```

فعّل هذه الـ APIs:
- ✅ Google Calendar API
- ✅ Google Meet API (إن وُجد)
- ✅ OAuth2 API

### الخطوة 3: إنشاء OAuth 2.0 Credentials

1. اذهب إلى `APIs & Services` > `Credentials`
2. اضغط `Create Credentials` > `OAuth 2.0 Client ID`
3. اختر `Web Application`
4. أضف Redirect URIs:
   ```
   https://yourdomain.com/google/callback
   https://academy1.yourdomain.com/google/callback
   https://academy2.yourdomain.com/google/callback
   ```
5. احفظ **Client ID** و **Client Secret**

### الخطوة 4: إنشاء Service Account (للحساب الاحتياطي)

1. اذهب إلى `APIs & Services` > `Credentials`
2. اضغط `Create Credentials` > `Service Account`
3. أدخل اسم واضح مثل "Platform Meeting Fallback"
4. اضغط `Create and Continue`
5. أعطه دور `Editor` أو `Calendar API User`
6. اضغط `Done`
7. اضغط على Service Account المُنشأ
8. اذهب إلى تبويب `Keys`
9. اضغط `Add Key` > `Create New Key` > `JSON`
10. احفظ ملف JSON - ستحتاجه لاحقاً

---

## ⚙️ تكوين النظام من لوحة الإدارة

### 1. تسجيل الدخول كـ Super Admin

```url
https://yourdomain.com/admin/login
```

### 2. الوصول لإعدادات Google Meet

```
الإعدادات العامة > إعدادات Google Meet
```

### 3. إدخال البيانات المطلوبة

#### إعدادات Google Cloud Project:
- **معرف مشروع Google Cloud**: `your-project-id-123456`
- **Client ID**: من الخطوة 3 أعلاه
- **Client Secret**: من الخطوة 3 أعلاه
- **OAuth Redirect URI**: `https://yourdomain.com/google/callback`

#### الحساب الاحتياطي:
- ✅ تفعيل الحساب الاحتياطي
- **البريد الإلكتروني**: البريد المرتبط بـ Service Account
- **بيانات الاعتماد**: انسخ محتوى ملف JSON كاملاً
- **الحد الأقصى**: `100` اجتماع يومياً

#### إعدادات الاجتماعات:
- ✅ إنشاء الاجتماعات تلقائياً
- **وقت التحضير**: `60` دقيقة
- **مدة الجلسة الافتراضية**: `60` دقيقة

#### إعدادات الإشعارات:
- ✅ إرسال تذكيرات الاجتماعات
- ✅ إشعار عند قطع اتصال المعلم
- **أوقات التذكير**: `60,15` (ساعة و 15 دقيقة)

### 4. اختبار الإعدادات

اضغط زر **"اختبار الاتصال"** للتأكد من صحة الإعدادات.

---

## 🧪 اختبار النظام

### 1. اختبار وظائف Cron

```bash
# اختبار شامل لجميع الوظائف
php artisan test:cron-jobs

# اختبار وظيفة واحدة فقط
php artisan test:cron-jobs --job=prepare

# وضع التجربة (بدون تنفيذ فعلي)
php artisan test:cron-jobs --dry-run --verbose
```

### 2. اختبار المُجدوِل

```bash
# عرض الوظائف المجدولة
php artisan schedule:list

# تشغيل المُجدوِل يدوياً
php artisan schedule:run

# اختبار وظيفة واحدة
php artisan sessions:prepare
php artisan sessions:generate --weeks=1
php artisan tokens:cleanup
```

### 3. اختبار ربط Google للمعلمين

1. سجل دخول كمعلم
2. اذهب إلى `/calendar`
3. اضغط "ربط Google Calendar"
4. أكمل عملية OAuth
5. تأكد من ظهور "مربوط بـ Google"

### 4. اختبار إنشاء الجلسات

```bash
# تحقق من وجود بيانات أساسية
php artisan tinker

# في tinker:
App\Models\QuranSubscription::count()
App\Models\QuranCircle::count()
App\Models\QuranTeacherProfile::count()

# إنشاء جلسات تجريبية
App\Jobs\GenerateWeeklyScheduleSessions::dispatch(1);

# التحقق من الجلسات المُنشأة
App\Models\QuranSession::whereDate('created_at', today())->count()
```

---

## 📊 مراقبة النظام

### 1. ملفات السجلات

```bash
# سجل Laravel العام
tail -f storage/logs/laravel.log

# سجل الوظائف
tail -f storage/logs/laravel.log | grep "google\|session\|cron"

# سجل طابور المعالجة
php artisan queue:monitor
```

### 2. قاعدة البيانات

```sql
-- التحقق من الجلسات القادمة
SELECT * FROM quran_sessions 
WHERE scheduled_at BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 2 HOUR)
AND status = 'scheduled';

-- التحقق من الرموز المنتهية
SELECT * FROM google_tokens WHERE expires_at < NOW();

-- إحصائيات سريعة
SELECT 
    COUNT(*) as total_sessions,
    COUNT(CASE WHEN meeting_link IS NOT NULL THEN 1 END) as with_links,
    COUNT(CASE WHEN preparation_completed_at IS NOT NULL THEN 1 END) as prepared
FROM quran_sessions 
WHERE scheduled_at >= NOW();
```

### 3. اختبار الأداء

```bash
# اختبار سرعة إنشاء الاجتماعات
time php artisan test:cron-jobs --job=prepare

# مراقبة استخدام الذاكرة
php artisan tinker
memory_get_usage(true) / 1024 / 1024; // MB
```

---

## 🔧 استكشاف الأخطاء وإصلاحها

### المشاكل الشائعة:

#### 1. "Google API Error: Invalid Credentials"

**الحل:**
```bash
# تحقق من صحة بيانات .env
cat .env | grep GOOGLE

# تحقق من إعدادات قاعدة البيانات
php artisan tinker
App\Models\AcademyGoogleSettings::first()?->testConnection();
```

#### 2. "Queue Jobs Not Processing"

**الحل:**
```bash
# تأكد من تشغيل معالج الطابور
php artisan queue:work

# تحقق من الوظائف المتعطلة
php artisan queue:failed
php artisan queue:retry all
```

#### 3. "Cron Jobs Not Running"

**الحل:**
```bash
# تأكد من crontab
crontab -l

# اختبر المُجدوِل يدوياً
php artisan schedule:run -v

# تحقق من أذونات الملفات
ls -la storage/logs/
chmod -R 755 storage/
```

#### 4. "Calendar Page Not Loading"

**الحل:**
```bash
# تحقق من الـ routes
php artisan route:list | grep calendar

# تحقق من الـ middleware
php artisan tinker
auth()->user()?->roles?->pluck('name');

# مسح الكاش
php artisan config:clear
php artisan view:clear
php artisan route:clear
```

---

## 🎯 التحقق من نجاح النشر

### قائمة التحقق الشاملة:

#### ✅ قاعدة البيانات:
- [ ] تطبيق جميع migrations بنجاح
- [ ] إنشاء الجداول الجديدة: `google_tokens`, `session_schedules`, `academy_google_settings`, `platform_google_accounts`
- [ ] وجود عمودين جديدين في `users`: `google_id`, `google_calendar_connected_at`

#### ✅ إعدادات Google:
- [ ] إنشاء Google Cloud Project
- [ ] تفعيل Calendar API
- [ ] إنشاء OAuth credentials
- [ ] إنشاء Service Account
- [ ] إدخال البيانات في لوحة الإدارة
- [ ] نجاح اختبار الاتصال

#### ✅ واجهات المستخدم:
- [ ] وصول إلى `/admin/google-settings`
- [ ] وصول إلى `/calendar` للمعلمين
- [ ] وصول إلى `/calendar` للطلاب
- [ ] عمل ربط Google للمعلمين
- [ ] عرض التقويم بشكل صحيح

#### ✅ الأتمتة:
- [ ] تشغيل cron jobs بنجاح
- [ ] عمل طابور المعالجة
- [ ] إنشاء الجلسات تلقائياً
- [ ] تحضير الاجتماعات قبل موعدها
- [ ] تنظيف الرموز المنتهية

#### ✅ الاختبارات:
- [ ] `php artisan test:cron-jobs` يعمل بدون أخطاء
- [ ] `php artisan schedule:run` يعمل بنجاح
- [ ] إنشاء جلسة تجريبية ناجح
- [ ] ربط حساب Google ناجح

---

## 📞 الدعم الفني

### في حالة مواجهة مشاكل:

1. **تحقق من السجلات:**
   ```bash
   tail -f storage/logs/laravel.log
   ```

2. **راسلني مع:**
   - رسالة الخطأ كاملة
   - محتوى `php artisan test:cron-jobs`
   - نتيجة `php artisan schedule:list`
   - إعدادات Google Cloud

3. **معلومات مفيدة للدعم:**
   ```bash
   php -v
   composer --version
   php artisan --version
   php artisan config:show queue
   ```

---

## 🔮 المراحل التالية

بعد نجاح النشر، يمكن تطوير:

1. **تحليلات متقدمة** للجلسات والحضور
2. **تسجيل الجلسات** تلقائياً
3. **تقارير شاملة** للمعلمين والإدارة
4. **تكامل مع أنظمة الدفع** لربط الجلسات بالاشتراكات
5. **تطبيق موبايل** للوصول السريع للتقويم

---

**✨ النظام جاهز للاستخدام! نتمنى لك تجربة ممتازة مع نظام إدارة الجلسات المتكامل.**