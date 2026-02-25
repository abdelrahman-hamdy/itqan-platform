@extends('help.layouts.article', [
    'role' => 'admin',
    'slug' => 'quran-subscriptions',
])

@section('content')

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

<h2>إنشاء اشتراك جديد</h2>

<div class="help-step">
    <div class="help-step-number">1</div>
    <div class="help-step-content">
        <h3>افتح قسم اشتراكات القرآن</h3>
        <p>من القائمة الجانبية، اختر <strong>إدارة القرآن ← اشتراكات القرآن</strong>، ثم انقر <strong>إنشاء اشتراك</strong>.</p>
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
        </tbody>
    </table>
</div>

<div class="help-warning">
    <i class="ri-error-warning-line help-callout-icon"></i>
    <div>
        عند <strong>تعليق الاشتراك</strong>، يظهر تحذير في ملف الحلقة الفردية المرتبطة، ولا يمكن جدولة جلسات جديدة حتى تُفعَّل الاشتراك مرة أخرى.
    </div>
</div>

<h2>تصفية الاشتراكات</h2>

<p>يوفر جدول الاشتراكات خيارات تصفية متعددة:</p>
<ul>
    <li><strong>النوع:</strong> فردي / جماعي</li>
    <li><strong>الحالة:</strong> نشط / معلق / ملغى / منتهي</li>
    <li><strong>حالة الدفع:</strong> مدفوع / معلق / مجاني</li>
    <li><strong>البحث:</strong> باسم الطالب أو المعلم</li>
</ul>

@endsection
