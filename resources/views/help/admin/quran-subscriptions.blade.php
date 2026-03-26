@extends('help.layouts.article', [
    'role' => 'admin',
    'slug' => 'quran-subscriptions',
])

@section('content')

<img src="/images/help/admin/quran-subscriptions-list.png"
     alt="قائمة اشتراكات القرآن — عرض الطلاب والباقات وحالة الاشتراك"
     class="help-screenshot">

<h2>ما هو اشتراك القرآن؟</h2>

<p>
    اشتراك القرآن هو العقد الذي يربط الطالب ببرنامج تحفيظ أو تلاوة القرآن لفترة زمنية محددة.
    يحدد الاشتراك: الطالب، المعلم، الباقة، عدد الجلسات الكلي، تواريخ البداية والنهاية، وحالة الدفع.
    المنصة تتتبع تلقائياً الجلسات المكتملة والمتبقية.
</p>

<h2>أنواع الاشتراكات</h2>

<ul>
    <li><strong>فردي:</strong> طالب واحد مع معلم واحد (الحلقات الفردية)</li>
    <li><strong>جماعي (حلقة):</strong> مجموعة طلاب في حلقة مع معلم واحد</li>
</ul>

<div class="overflow-x-auto my-4">
    <table class="min-w-full text-sm border border-gray-200 rounded-lg overflow-hidden">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-2 text-right font-semibold text-gray-600 border-b">بطاقة الإحصاء</th>
                <th class="px-4 py-2 text-right font-semibold text-gray-600 border-b">المحتوى</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 text-gray-600">
            <tr><td class="px-4 py-2 font-medium text-green-700">نشطة</td><td class="px-4 py-2">الاشتراكات الجارية حالياً</td></tr>
            <tr><td class="px-4 py-2 font-medium text-orange-700">تنتهي هذا الأسبوع</td><td class="px-4 py-2">اشتراكات على وشك الانتهاء خلال 7 أيام</td></tr>
            <tr><td class="px-4 py-2 font-medium text-yellow-700">معلقة</td><td class="px-4 py-2">الاشتراكات الموقوفة مؤقتاً</td></tr>
            <tr><td class="px-4 py-2 font-medium text-blue-700">منتظرة</td><td class="px-4 py-2">الاشتراكات التي لم تبدأ بعد</td></tr>
            <tr><td class="px-4 py-2 font-medium text-red-700">منتهية</td><td class="px-4 py-2">الاشتراكات المنتهية الصلاحية</td></tr>
            <tr><td class="px-4 py-2 font-medium text-purple-700">مُمدَّدة</td><td class="px-4 py-2">اشتراكات في فترة السماح (grace period)</td></tr>
        </tbody>
    </table>
</div>

<p class="mb-3">خيارات التصفية والترتيب:</p>
<ul>
    <li><strong>النوع:</strong> قرآن / أكاديمي</li>
    <li><strong>الحالة:</strong> نشط / معلق / ملغى / منتهي / مُمدَّد</li>
    <li><strong>بحث:</strong> باسم الطالب أو المعلم</li>
    <li><strong>الترتيب:</strong> الأحدث / الأقدم / ينتهي قريباً / الجلسات المتبقية</li>
    <li><strong>الصفحة:</strong> 15 اشتراكاً في كل صفحة</li>
</ul>

<h2>الإجراءات المتاحة على الاشتراك</h2>

<p>من صفحة تفاصيل الاشتراك تتوفر الإجراءات التالية:</p>
<ul>
    <li><strong>تفعيل:</strong> تحويل الاشتراك من معلق إلى نشط</li>
    <li><strong>إيقاف مؤقت:</strong> تعليق الاشتراك مع الاحتفاظ بالجلسات المتبقية</li>
    <li><strong>استئناف:</strong> إعادة تشغيل اشتراك موقوف</li>
    <li><strong>تمديد (فترة سماح):</strong> منح وقت إضافي بعد انتهاء الاشتراك</li>
    <li><strong>إلغاء:</strong> إنهاء الاشتراك نهائياً</li>
</ul>

<h2>إنشاء اشتراك جديد</h2>

<div class="help-note">
    <i class="ri-information-line help-callout-icon"></i>
    <div>
        <strong>ملاحظة:</strong> إنشاء الاشتراكات الجديدة يتم حالياً من لوحة Filament على
        <code dir="ltr">/panel</code> ضمن إدارة القرآن ← اشتراكات القرآن.
        صفحة <code dir="ltr">/manage/subscriptions</code> تُستخدم لإدارة الاشتراكات القائمة وتطبيق الإجراءات عليها.
    </div>
</div>

<div class="help-step">
    <div class="help-step-number">1</div>
    <div class="help-step-content">
        <h3>افتح قسم اشتراكات القرآن</h3>
        <p>من لوحة Filament على رابط <strong>/panel</strong>، اختر <strong>إدارة القرآن ← اشتراكات القرآن</strong>، ثم انقر <strong>إنشاء اشتراك</strong>.</p>
    </div>
</div>

<div class="help-step">
    <div class="help-step-number">2</div>
    <div class="help-step-content">
        <h3>حدد نوع الاشتراك</h3>
        <p>اختر <strong>فردي</strong> أو <strong>جماعي (حلقة)</strong>. يحدد هذا الاختيار الحقول التالية في النموذج.</p>
    </div>
</div>

<div class="help-step">
    <div class="help-step-number">3</div>
    <div class="help-step-content">
        <h3>اختر الطالب والمعلم والباقة</h3>
        <ul>
            <li><strong>الطالب:</strong> ابحث باسمه أو كوده</li>
            <li><strong>المعلم:</strong> اختر من المعلمين النشطين</li>
            <li><strong>الباقة:</strong> تُعبّأ حقول عدد الجلسات والمدة تلقائياً بعد الاختيار</li>
        </ul>
    </div>
</div>

<div class="help-step">
    <div class="help-step-number">4</div>
    <div class="help-step-content">
        <h3>مراجعة إعدادات الجلسات</h3>
        <ul>
            <li><strong>إجمالي الجلسات:</strong> يُملأ تلقائياً من الباقة ويمكن تعديله</li>
            <li><strong>مدة الجلسة:</strong> تُملأ من الباقة</li>
            <li><strong>الجلسات المتبقية:</strong> حقل للقراءة فقط يحسب ما تبقى</li>
            <li><strong>الجلسات الفائتة:</strong> يُحدَّث تلقائياً عند تسجيل غياب</li>
        </ul>
    </div>
</div>

<div class="help-step">
    <div class="help-step-number">5</div>
    <div class="help-step-content">
        <h3>إضافة تفضيلات الطالب (اختياري)</h3>
        <ul>
            <li><strong>الأيام المفضلة:</strong> الأيام التي يرغب الطالب في الدراسة فيها</li>
            <li><strong>الوقت المفضل:</strong> الصباح، المساء، إلخ</li>
            <li><strong>ملاحظات الطالب:</strong> أي طلبات خاصة</li>
        </ul>
    </div>
</div>

<div class="help-step">
    <div class="help-step-number">6</div>
    <div class="help-step-content">
        <h3>حدد التواريخ وحالة الدفع</h3>
        <ul>
            <li><strong>تاريخ البداية:</strong> متى يبدأ الاشتراك</li>
            <li><strong>تاريخ النهاية:</strong> نهاية فترة الاشتراك</li>
            <li><strong>نوع الاشتراك:</strong> شهري / ربع سنوي / سنوي</li>
            <li><strong>المبلغ:</strong> يُملأ تلقائياً من الباقة ويمكن تعديله</li>
            <li><strong>حالة الدفع:</strong> مدفوع / معلق / مجاني</li>
        </ul>
    </div>
</div>

<div class="help-step">
    <div class="help-step-number">7</div>
    <div class="help-step-content">
        <h3>التجديد التلقائي (اختياري)</h3>
        <p>
            يمكن تفعيل خيار <strong>التجديد التلقائي</strong> إذا كان الطالب لديه طريقة دفع محفوظة.
            المنصة ستنشئ اشتراكاً جديداً تلقائياً عند انتهاء الفترة الحالية.
        </p>
    </div>
</div>

<div class="help-step">
    <div class="help-step-number">8</div>
    <div class="help-step-content">
        <h3>أضف ملاحظات إدارية (اختياري)</h3>
        <p>يمكنك إضافة ملاحظات للمدير أو للمشرف تظهر فقط لطاقم الأكاديمية.</p>
    </div>
</div>

<div class="help-step">
    <div class="help-step-number">9</div>
    <div class="help-step-content">
        <h3>احفظ الاشتراك</h3>
        <p>انقر <strong>حفظ</strong>. يُنشأ الاشتراك فوراً ويبدأ النظام بتتبع الجلسات.</p>
    </div>
</div>

<h2>حالات الاشتراك وما تعنيه</h2>

<div class="overflow-x-auto">
    <table class="min-w-full text-sm border border-gray-200 rounded-lg overflow-hidden mt-2">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-2 text-right font-semibold text-gray-600 border-b">الحالة</th>
                <th class="px-4 py-2 text-right font-semibold text-gray-600 border-b">المعنى</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 text-gray-600">
            <tr><td class="px-4 py-2 font-medium text-green-700">نشط</td><td class="px-4 py-2">الاشتراك جارٍ والجلسات تُحسب</td></tr>
            <tr><td class="px-4 py-2 font-medium text-yellow-700">معلق</td><td class="px-4 py-2">تم إيقاف الاشتراك مؤقتاً (مشكلة دفع أو طلب الطالب)</td></tr>
            <tr><td class="px-4 py-2 font-medium text-red-700">ملغى</td><td class="px-4 py-2">الاشتراك أُلغي نهائياً</td></tr>
            <tr><td class="px-4 py-2 font-medium text-gray-700">منتهي</td><td class="px-4 py-2">انقضت فترة الاشتراك أو نفدت الجلسات</td></tr>
            <tr><td class="px-4 py-2 font-medium text-purple-700">مُمدَّد</td><td class="px-4 py-2">الاشتراك في فترة السماح (grace period) — منحة وقت إضافي بعد الانتهاء</td></tr>
        </tbody>
    </table>
</div>

<div class="help-warning">
    <i class="ri-error-warning-line help-callout-icon"></i>
    <div>
        عند <strong>تعليق الاشتراك</strong>، يظهر تحذير في ملف الحلقة الفردية المرتبطة، ولا يمكن جدولة جلسات جديدة حتى تُفعَّل الاشتراك مرة أخرى.
    </div>
</div>

@endsection
