@extends('help.layouts.article', [
    'role' => 'admin',
    'slug' => 'quran-teachers',
])

@section('content')

<h2>نظرة عامة</h2>

<p>
    يُدار معلمو القرآن من قسم <strong>إدارة المستخدمين ← معلمو القرآن</strong> في لوحة تحكم الأكاديمية.
    يمكنك من هذا القسم إنشاء حسابات المعلمين وتحديد بياناتهم الشخصية وتفعيل أو تعطيل وصولهم إلى المنصة.
</p>

<img src="/images/help/admin/quran-teachers-list.png"
     alt="قائمة معلمي القرآن — عرض المعلمين مع رمز المعلم وحالة الحساب"
     class="help-screenshot">

<h2>إضافة معلم قرآن جديد</h2>

<div class="help-step">
    <div class="help-step-number">1</div>
    <div class="help-step-content">
        <h3>افتح قسم معلمي القرآن</h3>
        <p>من القائمة الجانبية، اختر <strong>إدارة المستخدمين ← معلمو القرآن</strong>، ثم انقر <strong>إضافة معلم</strong>.</p>
    </div>
</div>

<div class="help-step">
    <div class="help-step-number">2</div>
    <div class="help-step-content">
        <h3>أدخل بيانات الحساب</h3>
        <ul>
            <li><strong>البريد الإلكتروني:</strong> يُستخدم لتسجيل الدخول — يجب أن يكون فريداً في النظام</li>
            <li><strong>كلمة المرور:</strong> الكلمة الأولى للمعلم (يمكنه تغييرها لاحقاً)</li>
            <li>
                <strong>كود المعلم:</strong> يُولَّد تلقائياً بصيغة <code>QT-XXXX</code> ولا يمكن تعديله
            </li>
        </ul>
    </div>
</div>

<div class="help-step">
    <div class="help-step-number">3</div>
    <div class="help-step-content">
        <h3>أدخل البيانات الشخصية</h3>
        <ul>
            <li><strong>الاسم الأول والأخير</strong></li>
            <li><strong>رقم الجوال:</strong> يُدخل بصيغة دولية (مثال: <code>+966501234567</code>)</li>
            <li><strong>الجنس:</strong> ذكر أو أنثى (يُؤثر على عرض الصورة الرمزية الافتراضية)</li>
            <li><strong>نبذة شخصية:</strong> وصف قصير عن المعلم يظهر لأولياء الأمور</li>
        </ul>
    </div>
</div>

<div class="help-step">
    <div class="help-step-number">4</div>
    <div class="help-step-content">
        <h3>رفع صورة المعلم (اختياري)</h3>
        <p>
            يدعم حقل الصورة الرفع المباشر مع أداة قص دائرية.
            إذا لم تُرفع صورة، تُعرض صورة رمزية افتراضية حسب الجنس.
        </p>
    </div>
</div>

<div class="help-step">
    <div class="help-step-number">5</div>
    <div class="help-step-content">
        <h3>احفظ الملف الشخصي</h3>
        <p>انقر <strong>حفظ</strong>. يتم إنشاء الحساب فوراً ويمكن للمعلم تسجيل الدخول بالبريد الإلكتروني وكلمة المرور المحددة.</p>
    </div>
</div>

<div class="help-tip">
    <i class="ri-lightbulb-line help-callout-icon"></i>
    <div>
        أبلغ المعلم بكود المعلم <code>QT-XXXX</code> وبريده الإلكتروني. يستطيع المعلم تسجيل الدخول عبر لوحة المعلم على نطاق أكاديميتك (<strong>/teacher-panel</strong>).
    </div>
</div>

<h2>تفعيل وتعطيل حساب المعلم</h2>

<p>يمكن تفعيل أو تعطيل حساب أي معلم دون حذفه:</p>

<div class="help-step">
    <div class="help-step-number">1</div>
    <div class="help-step-content">
        <h3>من جدول المعلمين</h3>
        <p>
            يظهر في جدول المعلمين عمود <strong>الحالة</strong> يُبيّن إن كان المعلم مفعّلاً أم لا.
            انقر على إجراء <strong>تبديل الحالة</strong> في خيارات السطر لتفعيل/تعطيل المعلم فوراً.
        </p>
    </div>
</div>

<div class="help-step">
    <div class="help-step-number">2</div>
    <div class="help-step-content">
        <h3>من صفحة تعديل المعلم</h3>
        <p>
            افتح سجل المعلم للتعديل وفي قسم <em>معلومات الحساب</em> قم بتشغيل/إيقاف مفتاح <strong>نشط</strong>.
        </p>
    </div>
</div>

<div class="help-warning">
    <i class="ri-error-warning-line help-callout-icon"></i>
    <div>
        <strong>تنبيه:</strong> تعطيل حساب المعلم يمنعه من تسجيل الدخول فوراً لكنه لا يؤثر على الجلسات والاشتراكات المرتبطة به. يجب إعادة تعيين الجلسات الجديدة يدوياً إلى معلم آخر إن لزم الأمر.
    </div>
</div>

<h2>تصفية وبحث المعلمين</h2>

<p>يوفر جدول المعلمين خيارات تصفية متعددة:</p>
<ul>
    <li><strong>الحالة</strong>: نشط / غير نشط / الكل</li>
    <li><strong>الجنس</strong>: ذكر / أنثى</li>
    <li><strong>البحث النصي</strong>: بالاسم أو البريد الإلكتروني أو كود المعلم</li>
</ul>

<h2>عرض جلسات المعلم</h2>

<p>
    بعد فتح ملف المعلم عبر زر <strong>عرض</strong>، يمكنك الاطلاع على قائمة جلساته المجدولة والمكتملة والحلقات المرتبطة به.
    هذا مفيد لمتابعة أداء المعلم وحجم عمله.
</p>

@endsection
