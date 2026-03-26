@extends('help.layouts.article', [
    'role' => 'admin',
    'slug' => 'quran-teachers',
])

@section('content')

<h2>نظرة عامة</h2>

<p>
    يُدار معلمو القرآن والمعلمون الأكاديميون من قسم <strong>المعلمون</strong> في لوحة الإدارة الأمامية
    على الرابط <code dir="ltr">/manage/teachers</code>.
    يمكنك من هذا القسم إضافة المعلمين وتحرير بياناتهم وتفعيل أو تعطيل وصولهم وإعادة تعيين كلمات مرورهم.
</p>

<div class="overflow-x-auto my-4">
    <table class="min-w-full text-sm border border-gray-200 rounded-lg overflow-hidden">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-2 text-right font-semibold text-gray-600 border-b">بطاقة الإحصاء</th>
                <th class="px-4 py-2 text-right font-semibold text-gray-600 border-b">المحتوى</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 text-gray-600">
            <tr><td class="px-4 py-2 font-medium">إجمالي المعلمين</td><td class="px-4 py-2">الكل (نشط + غير نشط)</td></tr>
            <tr><td class="px-4 py-2 font-medium">معلمو القرآن</td><td class="px-4 py-2">ذكور / إناث</td></tr>
            <tr><td class="px-4 py-2 font-medium">المعلمون الأكاديميون</td><td class="px-4 py-2">ذكور / إناث</td></tr>
        </tbody>
    </table>
</div>

<p class="mb-3">خيارات التصفية والبحث:</p>
<ul>
    <li><strong>بحث نصي:</strong> بالاسم أو البريد الإلكتروني أو كود المعلم</li>
    <li><strong>نوع المعلم:</strong> قرآن / أكاديمي</li>
    <li><strong>الجنس:</strong> ذكر / أنثى</li>
    <li><strong>الحالة:</strong> نشط / غير نشط</li>
    <li><strong>الترتيب:</strong> الاسم / الكيانات النشطة / التقييم / الأحدث / الأقدم</li>
    <li><strong>الصفحة:</strong> 15 معلماً في كل صفحة</li>
</ul>

<h2>إضافة معلم قرآن جديد</h2>

<div class="help-step">
    <div class="help-step-number">1</div>
    <div class="help-step-content">
        <h3>افتح قسم المعلمين</h3>
        <p>انتقل إلى <strong>/manage/teachers</strong> من لوحة الإدارة الأمامية، ثم انقر <strong>إضافة معلم</strong> (أو ابحث عن زر "+" في الأعلى).</p>
    </div>
</div>

<div class="help-step">
    <div class="help-step-number">2</div>
    <div class="help-step-content">
        <h3>أدخل البيانات الأساسية</h3>
        <ul>
            <li><strong>الاسم الأول والأخير</strong></li>
            <li><strong>البريد الإلكتروني:</strong> يُستخدم لتسجيل الدخول — يجب أن يكون فريداً في النظام</li>
            <li><strong>رقم الجوال:</strong> يُدخل بصيغة دولية (مثال: <code>+966501234567</code>)</li>
            <li><strong>الجنس:</strong> ذكر أو أنثى (يُؤثر على عرض الصورة الرمزية الافتراضية)</li>
            <li><strong>صورة المعلم (اختياري):</strong> يدعم الرفع المباشر مع أداة قص دائرية</li>
        </ul>
    </div>
</div>

<div class="help-step">
    <div class="help-step-number">3</div>
    <div class="help-step-content">
        <h3>أدخل البيانات التعليمية</h3>
        <ul>
            <li><strong>المستوى التعليمي:</strong> الدرجة العلمية</li>
            <li><strong>الجامعة:</strong> اسم الجامعة أو المعهد</li>
            <li><strong>سنوات الخبرة:</strong> عدد سنوات التدريس</li>
        </ul>
    </div>
</div>

<div class="help-step">
    <div class="help-step-number">4</div>
    <div class="help-step-content">
        <h3>أدخل التخصصات (حسب نوع المعلم)</h3>
        <p>للمعلمين الأكاديميين: اختر المواد التي يدرّسها (قائمة متعددة الاختيار) والمراحل الدراسية المناسبة لهم والأيام المتاحة.</p>
        <p>لمعلمي القرآن: أضف الإجازات والشهادات القرآنية الحاصل عليها المعلم.</p>
    </div>
</div>

<div class="help-step">
    <div class="help-step-number">5</div>
    <div class="help-step-content">
        <h3>احفظ الملف الشخصي</h3>
        <p>انقر <strong>حفظ</strong>. يتم إنشاء الحساب فوراً ويمكن للمعلم تسجيل الدخول بالبريد الإلكتروني وكلمة المرور المحددة. يُولَّد كود المعلم تلقائياً بصيغة <code>QT-XXXX</code>.</p>
    </div>
</div>

<div class="help-tip">
    <i class="ri-lightbulb-line help-callout-icon"></i>
    <div>
        أبلغ المعلم بكود المعلم <code>QT-XXXX</code> وبريده الإلكتروني.
        يستطيع المعلم تسجيل الدخول بشكل أساسي عبر الموقع الأمامي على رابط
        <strong>/login</strong> في نطاق أكاديميتك. ولوحة Filament (<strong>/teacher-panel</strong>) متاحة كواجهة بديلة اختيارية.
    </div>
</div>

<h2>تفعيل وتعطيل حساب المعلم</h2>

<p>يمكن تفعيل أو تعطيل حساب أي معلم دون حذفه من صفحة تفاصيل المعلم في الإجراءات السريعة:</p>

<div class="help-step">
    <div class="help-step-number">1</div>
    <div class="help-step-content">
        <h3>من قائمة المعلمين</h3>
        <p>
            يظهر في قائمة المعلمين مؤشر الحالة (نشط / غير نشط) لكل معلم.
            انقر على إجراء <strong>تبديل الحالة</strong> في قائمة الإجراءات السريعة لتفعيل/تعطيل المعلم فوراً.
        </p>
    </div>
</div>

<div class="help-step">
    <div class="help-step-number">2</div>
    <div class="help-step-content">
        <h3>من صفحة تفاصيل المعلم</h3>
        <p>
            افتح صفحة المعلم وستجد الإجراءات السريعة: <strong>تعديل</strong>، <strong>تبديل الحالة</strong>،
            <strong>إعادة تعيين كلمة المرور</strong>، <strong>حذف</strong>.
        </p>
    </div>
</div>

<div class="help-warning">
    <i class="ri-error-warning-line help-callout-icon"></i>
    <div>
        <strong>تنبيه:</strong> تعطيل حساب المعلم يمنعه من تسجيل الدخول فوراً لكنه لا يؤثر على الجلسات والاشتراكات المرتبطة به. يجب إعادة تعيين الجلسات الجديدة يدوياً إلى معلم آخر إن لزم الأمر.
    </div>
</div>

<h2>صفحة تفاصيل المعلم</h2>

<p>
    بعد فتح صفحة المعلم، يمكنك الاطلاع على:
</p>
<ul>
    <li><strong>الكيانات النشطة:</strong> عدد الحلقات والدروس الحالية للمعلم</li>
    <li><strong>إحصاءات الجلسات الشهرية:</strong> عدد الجلسات المكتملة هذا الشهر</li>
    <li><strong>معدل إتمام الجلسات:</strong> نسبة الجلسات المكتملة من إجمالي الجلسات</li>
    <li><strong>الجلسات الأخيرة:</strong> قائمة بآخر الجلسات مع حالتها</li>
    <li><strong>الإجراءات السريعة:</strong> تعديل / تبديل الحالة / إعادة تعيين كلمة المرور / حذف</li>
</ul>

@endsection
