@extends('help.layouts.article', [
    'role' => 'admin',
    'slug' => 'quran-sessions',
])

@section('content')

<h2>ما هي جلسة القرآن؟</h2>

<p>
    جلسة القرآن هي لقاء فردي بين الطالب ومعلمه يتم فيه التلاوة والحفظ والمراجعة.
    كل جلسة لها <strong>كود فريد</strong> وحالة محددة وتوقيت وواجبات مرتبطة.
    يمكن للمدير مراجعة جميع الجلسات ومتابعة حالتها من هذا القسم.
</p>

<h2>الوصول إلى قسم الجلسات</h2>

<p>
    من القائمة الجانبية، اختر <strong>إدارة القرآن ← جلسات القرآن</strong>.
    يُعرض جدول بجميع جلسات الأكاديمية قابلة للتصفية والفرز.
</p>

<img src="/images/help/admin/quran-sessions-list.png"
     alt="قائمة جلسات القرآن — عرض الجلسات مع حالتها وتوقيتها وكودها"
     class="help-screenshot">

<h2>دورة حياة الجلسة</h2>

<p>تمر كل جلسة بأربع حالات بشكل تلقائي:</p>

<div class="overflow-x-auto">
    <table class="min-w-full text-sm border border-gray-200 rounded-lg overflow-hidden mt-2">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-2 text-right font-semibold text-gray-600 border-b">الحالة</th>
                <th class="px-4 py-2 text-right font-semibold text-gray-600 border-b">المعنى</th>
                <th class="px-4 py-2 text-right font-semibold text-gray-600 border-b">يحدث تلقائياً</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 text-gray-600">
            <tr>
                <td class="px-4 py-2 font-medium text-blue-700">مجدولة</td>
                <td class="px-4 py-2">الجلسة محجوزة في تاريخ مستقبلي</td>
                <td class="px-4 py-2">عند إنشاء الجلسة</td>
            </tr>
            <tr>
                <td class="px-4 py-2 font-medium text-green-700">مباشر</td>
                <td class="px-4 py-2">الجلسة جارية الآن</td>
                <td class="px-4 py-2">عند حلول وقت الجلسة</td>
            </tr>
            <tr>
                <td class="px-4 py-2 font-medium text-gray-700">مكتملة</td>
                <td class="px-4 py-2">انتهت الجلسة بنجاح</td>
                <td class="px-4 py-2">بعد انتهاء وقت الجلسة</td>
            </tr>
            <tr>
                <td class="px-4 py-2 font-medium text-red-700">ملغاة</td>
                <td class="px-4 py-2">تم إلغاء الجلسة</td>
                <td class="px-4 py-2">يدوياً أو عند تعليق الاشتراك</td>
            </tr>
        </tbody>
    </table>
</div>

<div class="help-tip">
    <i class="ri-lightbulb-line help-callout-icon"></i>
    <div>
        تعمل تحولات الحالة تلقائياً عبر مهمة مجدولة تعمل كل دقيقة. لا حاجة لتدخل يدوي في الحالة الاعتيادية.
    </div>
</div>

<h2>قراءة جدول الجلسات</h2>

<p>يعرض الجدول الأعمدة التالية:</p>
<ul>
    <li><strong>كود الجلسة:</strong> معرف فريد بصيغة <code>QS-XXXX-NNN</code></li>
    <li><strong>العنوان:</strong> اسم الجلسة (قد يُولَّد تلقائياً)</li>
    <li><strong>النوع:</strong> فردي أو جماعي (حلقة)</li>
    <li><strong>الحالة:</strong> مجدولة / مباشر / مكتملة / ملغاة</li>
    <li><strong>الموعد المقرر:</strong> التاريخ والوقت بتوقيت الأكاديمية</li>
    <li><strong>المدة (دقيقة):</strong> مدة الجلسة المخططة</li>
</ul>

<h2>تصفية الجلسات</h2>

<p>يدعم جدول الجلسات التصفية حسب:</p>
<ul>
    <li><strong>الحالة:</strong> مجدولة / مباشر / مكتملة / ملغاة</li>
    <li><strong>النوع:</strong> فردي / جماعي</li>
    <li><strong>التاريخ:</strong> جلسات اليوم، هذا الأسبوع، أو نطاق مخصص</li>
    <li><strong>المعلم:</strong> فلترة جلسات معلم بعينه</li>
</ul>

<h2>عرض تفاصيل جلسة</h2>

<div class="help-step">
    <div class="help-step-number">1</div>
    <div class="help-step-content">
        <h3>انقر على أيقونة "عرض"</h3>
        <p>ستنتقل إلى صفحة تفاصيل الجلسة التي تشمل المعلومات الكاملة.</p>
    </div>
</div>

<div class="help-step">
    <div class="help-step-number">2</div>
    <div class="help-step-content">
        <h3>معلومات الجلسة الأساسية</h3>
        <ul>
            <li>الكود، النوع، الحالة، الطالب، المعلم</li>
            <li>الموعد المقرر والمدة (بتوقيت الأكاديمية)</li>
            <li>العنوان والوصف ومحتوى الدرس</li>
        </ul>
    </div>
</div>

<div class="help-step">
    <div class="help-step-number">3</div>
    <div class="help-step-content">
        <h3>الواجبات القرآنية</h3>
        <p>تشتمل جلسات القرآن على ثلاثة أنواع من الواجبات يعيّنها المعلم:</p>
        <ul>
            <li>
                <strong>حفظ جديد:</strong> السورة والصفحات المطلوب حفظها
                (مثال: سورة البقرة، صفحة 25 إلى 27)
            </li>
            <li>
                <strong>مراجعة:</strong> إعادة تلاوة صفحات محفوظة سابقاً
            </li>
            <li>
                <strong>مراجعة شاملة:</strong> مراجعة سور كاملة تحديدها بالاختيار المتعدد
            </li>
        </ul>
    </div>
</div>

<div class="help-note">
    <i class="ri-information-line help-callout-icon"></i>
    <div>
        يستطيع <strong>المعلم فقط</strong> تعيين الواجبات من لوحة تحكمه. دور المدير هنا هو المراجعة والمتابعة لا التعديل.
    </div>
</div>

<h2>ملاحظات على التوقيت</h2>

<p>
    تُحفظ جميع المواعيد في قاعدة البيانات بتوقيت UTC، لكنها تُعرض في الواجهة <strong>بتوقيت الأكاديمية</strong> المحدد في إعداداتها.
    إذا كانت أكاديميتك في الرياض (GMT+3)، فالجلسة المقررة الساعة 10 صباحاً ستظهر هكذا بغض النظر عن الخادم.
</p>

<div class="help-warning">
    <i class="ri-error-warning-line help-callout-icon"></i>
    <div>
        <strong>تنبيه:</strong> لا تحذف جلسات مكتملة لأنها مرتبطة بسجلات الحضور والتقارير والواجبات. لإلغاء جلسة مجدولة، استخدم إجراء <em>إلغاء الجلسة</em> المتاح في قائمة الإجراءات.
    </div>
</div>

@endsection
