@extends('help.layouts.article', [
    'role' => 'admin',
    'slug' => 'quran-packages',
])

@section('content')

<h2>ما هي باقة القرآن؟</h2>

<p>
    باقة القرآن هي خطة الاشتراك التي تُحدد عدد الجلسات الشهرية ومدة كل جلسة وأسعار الاشتراك.
    عند إنشاء اشتراك لطالب، يختار المدير الباقة المناسبة التي تُحدد تلقائياً عدد الجلسات المتاحة له.
</p>

<h2>إنشاء باقة جديدة</h2>

<div class="help-step">
    <div class="help-step-number">1</div>
    <div class="help-step-content">
        <h3>افتح قسم باقات القرآن</h3>
        <p>من القائمة الجانبية في لوحة تحكم الأكاديمية، اختر <strong>إدارة القرآن ← باقات القرآن</strong>.</p>
    </div>
</div>

<div class="help-step">
    <div class="help-step-number">2</div>
    <div class="help-step-content">
        <h3>انقر على "إنشاء باقة"</h3>
        <p>ستفتح نموذج إنشاء الباقة الذي يضم الأقسام التالية.</p>
    </div>
</div>

<div class="help-step">
    <div class="help-step-number">3</div>
    <div class="help-step-content">
        <h3>أدخل المعلومات الأساسية</h3>
        <ul>
            <li><strong>اسم الباقة:</strong> مثل "باقة التحفيظ الأساسية" أو "باقة التلاوة المكثفة"</li>
            <li><strong>وصف الباقة:</strong> شرح مختصر يظهر للطلاب وأولياء الأمور</li>
            <li><strong>ترتيب العرض:</strong> رقم يحدد ترتيب ظهور الباقة في القوائم (الأصغر يظهر أولاً)</li>
        </ul>
    </div>
</div>

<div class="help-step">
    <div class="help-step-number">4</div>
    <div class="help-step-content">
        <h3>إعدادات الجلسات</h3>
        <ul>
            <li><strong>عدد الجلسات في الشهر:</strong> مثل 8 أو 12 أو 20 جلسة</li>
            <li>
                <strong>مدة الجلسة (بالدقائق):</strong> اختر من الخيارات المتاحة:
                <code>45</code> أو <code>60</code> أو <code>75</code> أو <code>90</code> دقيقة
            </li>
        </ul>
    </div>
</div>

<div class="help-step">
    <div class="help-step-number">5</div>
    <div class="help-step-content">
        <h3>حدد الأسعار</h3>
        <p>تتيح المنصة ثلاثة مستويات تسعير لتشجيع الاشتراكات طويلة الأمد:</p>
        <ul>
            <li><strong>السعر الشهري:</strong> سعر الاشتراك لشهر واحد</li>
            <li><strong>السعر الربع سنوي:</strong> سعر الاشتراك لـ 3 أشهر (يُظهر كتوفير)</li>
            <li><strong>السعر السنوي:</strong> سعر الاشتراك لـ 12 شهراً (أعلى توفير)</li>
        </ul>
    </div>
</div>

<div class="help-step">
    <div class="help-step-number">6</div>
    <div class="help-step-content">
        <h3>أضف مميزات الباقة (اختياري)</h3>
        <p>
            يمكنك إضافة قائمة نقطية بمميزات الباقة مثل "تقارير أسبوعية" أو "متابعة الواجبات".
            تظهر هذه المميزات في صفحة عرض الباقة للطلاب.
        </p>
    </div>
</div>

<div class="help-step">
    <div class="help-step-number">7</div>
    <div class="help-step-content">
        <h3>فعّل الباقة واحفظ</h3>
        <p>
            تأكد من تفعيل مفتاح <strong>نشطة</strong> حتى تظهر الباقة عند إنشاء اشتراكات جديدة.
            ثم انقر على <strong>حفظ</strong>.
        </p>
    </div>
</div>

<div class="help-tip">
    <i class="ri-lightbulb-line help-callout-icon"></i>
    <div>
        <strong>نصيحة:</strong> يمكنك إنشاء عدة باقات بمستويات مختلفة (مبتدئ، متوسط، متقدم) بعدد جلسات متفاوت.
        هذا يتيح للأسر اختيار الباقة المناسبة لجدولهم وميزانيتهم.
    </div>
</div>

<h2>تعديل أو تعطيل باقة</h2>

<div class="help-step">
    <div class="help-step-number">1</div>
    <div class="help-step-content">
        <h3>ابحث عن الباقة في القائمة</h3>
        <p>من جدول الباقات، انقر على أيقونة <strong>تعديل</strong> بجانب الباقة المراد تغييرها.</p>
    </div>
</div>

<div class="help-step">
    <div class="help-step-number">2</div>
    <div class="help-step-content">
        <h3>عدّل الحقول المطلوبة</h3>
        <p>يمكنك تعديل أي حقل بما فيها الأسعار وعدد الجلسات. التغييرات تؤثر على الاشتراكات الجديدة فقط.</p>
    </div>
</div>

<div class="help-step">
    <div class="help-step-number">3</div>
    <div class="help-step-content">
        <h3>تعطيل الباقة (عدم الحذف)</h3>
        <p>
            لإيقاف ظهور باقة في الاشتراكات الجديدة دون حذفها، قم بإيقاف تشغيل مفتاح <strong>نشطة</strong>.
            الاشتراكات الحالية المرتبطة بها لن تتأثر.
        </p>
    </div>
</div>

<div class="help-warning">
    <i class="ri-error-warning-line help-callout-icon"></i>
    <div>
        <strong>تنبيه:</strong> تجنب حذف الباقات المرتبطة باشتراكات نشطة. استخدم خيار <em>تعطيل</em> بدلاً من الحذف للحفاظ على سلامة البيانات التاريخية.
    </div>
</div>

<h2>فلترة الباقات وتصفحها</h2>

<p>يتيح جدول الباقات عدة خيارات للتصفية:</p>
<ul>
    <li>فلترة حسب <strong>الحالة</strong> (نشطة / غير نشطة)</li>
    <li>ترتيب حسب <strong>تاريخ الإنشاء</strong> أو <strong>اسم الباقة</strong></li>
    <li>البحث النصي عن اسم الباقة</li>
</ul>

@endsection
