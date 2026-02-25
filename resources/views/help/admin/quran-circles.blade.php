@extends('help.layouts.article', [
    'role' => 'admin',
    'slug' => 'quran-circles',
])

@section('content')

<img src="/images/help/admin/quran-circles-list.png"
     alt="قائمة الحلقات الفردية — عرض الطلاب والمعلمين ونوع التخصص"
     class="help-screenshot">

<h2>ما هي الحلقة الفردية؟</h2>

<p>
    الحلقة الفردية هي العلاقة التعليمية بين طالب واحد ومعلم واحد في برنامج القرآن الكريم.
    تُحدد فيها نوع التخصص (تحفيظ، تلاوة، تفسير، تجويد، شامل)، ومستوى الطالب،
    وتتبع فيها المنصة تقدمه في الحفظ والمراجعة عبر كل جلسة.
</p>

<div class="help-note">
    <i class="ri-information-line help-callout-icon"></i>
    <div>
        الحلقة الفردية مرتبطة <strong>باشتراك نشط</strong>. لإنشاء حلقة لطالب، يجب أن يكون لديه اشتراك قرآن فعّال أولاً.
    </div>
</div>

<h2>إنشاء حلقة فردية جديدة</h2>

<div class="help-step">
    <div class="help-step-number">1</div>
    <div class="help-step-content">
        <h3>افتح قسم الحلقات الفردية</h3>
        <p>من القائمة الجانبية، اختر <strong>إدارة القرآن ← الحلقات الفردية</strong>، ثم انقر <strong>إنشاء حلقة</strong>.</p>
    </div>
</div>

<div class="help-step">
    <div class="help-step-number">2</div>
    <div class="help-step-content">
        <h3>اختر الطالب والمعلم</h3>
        <ul>
            <li><strong>الطالب:</strong> ابحث عن الطالب بالاسم أو الكود</li>
            <li><strong>المعلم:</strong> اختر المعلم المناسب من قائمة المعلمين النشطين</li>
        </ul>
    </div>
</div>

<div class="help-step">
    <div class="help-step-number">3</div>
    <div class="help-step-content">
        <h3>حدد التخصص</h3>
        <p>اختر نوع البرنامج القرآني للطالب:</p>
        <ul>
            <li><strong>تحفيظ:</strong> يركز على حفظ آيات وسور جديدة</li>
            <li><strong>تلاوة:</strong> يركز على حسن التلاوة والأداء</li>
            <li><strong>تفسير:</strong> دراسة معاني وتفسير القرآن</li>
            <li><strong>تجويد:</strong> تعلم أحكام التجويد والتطبيق العملي</li>
            <li><strong>شامل:</strong> دمج التحفيظ مع التلاوة والتجويد</li>
        </ul>
    </div>
</div>

<div class="help-step">
    <div class="help-step-number">4</div>
    <div class="help-step-content">
        <h3>حدد مستوى الحفظ</h3>
        <ul>
            <li><strong>مبتدئ:</strong> بداية رحلة الحفظ</li>
            <li><strong>متوسط:</strong> لديه قاعدة حفظ جيدة</li>
            <li><strong>متقدم:</strong> حافظ لأجزاء كبيرة من القرآن</li>
        </ul>
    </div>
</div>

<div class="help-step">
    <div class="help-step-number">5</div>
    <div class="help-step-content">
        <h3>احفظ الحلقة</h3>
        <p>انقر <strong>حفظ</strong>. ستُنشأ الحلقة وتُربط باشتراك الطالب النشط تلقائياً.</p>
    </div>
</div>

<h2>تتبع تقدم الطالب</h2>

<p>
    تتحدث حقول التقدم في الحلقة تلقائياً بعد كل جلسة يُعيّن فيها المعلم واجباً.
    لا تحتاج لتعديلها يدوياً في الحالات الاعتيادية.
</p>

<div class="overflow-x-auto">
    <table class="min-w-full text-sm border border-gray-200 rounded-lg overflow-hidden mt-2">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-2 text-right font-semibold text-gray-600 border-b">الحقل</th>
                <th class="px-4 py-2 text-right font-semibold text-gray-600 border-b">المصدر</th>
                <th class="px-4 py-2 text-right font-semibold text-gray-600 border-b">الوصف</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            <tr>
                <td class="px-4 py-2 font-medium">إجمالي الصفحات المحفوظة</td>
                <td class="px-4 py-2 text-gray-500">واجب الحفظ الجديد</td>
                <td class="px-4 py-2 text-gray-600">تُجمع من صفحات الحفظ في كل جلسة</td>
            </tr>
            <tr>
                <td class="px-4 py-2 font-medium">إجمالي الصفحات المراجعة</td>
                <td class="px-4 py-2 text-gray-500">واجب المراجعة</td>
                <td class="px-4 py-2 text-gray-600">تُجمع من صفحات المراجعة في كل جلسة</td>
            </tr>
            <tr>
                <td class="px-4 py-2 font-medium">إجمالي السور المراجعة</td>
                <td class="px-4 py-2 text-gray-500">المراجعة الشاملة</td>
                <td class="px-4 py-2 text-gray-600">السور الكاملة المراجعة شمولياً</td>
            </tr>
        </tbody>
    </table>
</div>

<div class="help-tip">
    <i class="ri-lightbulb-line help-callout-icon"></i>
    <div>
        يمكنك مراجعة قسم <em>إحصاءات التقدم</em> في ملف الحلقة لمتابعة رحلة الطالب عبر الزمن دون الحاجة لفتح كل جلسة على حدة.
    </div>
</div>

<h2>تحذير الاشتراك المعلق</h2>

<p>
    إذا تم تعليق اشتراك الطالب أو إيقافه، يظهر تحذير بارز أعلى نموذج الحلقة يُنبّه المدير.
    لا يمكن إجراء تغييرات على الحلقة في هذه الحالة إلا بعد معالجة وضع الاشتراك.
</p>

<h2>حذف حلقة فردية</h2>

<div class="help-warning">
    <i class="ri-error-warning-line help-callout-icon"></i>
    <div>
        <strong>تنبيه:</strong> حذف الحلقة لا يُلغي الاشتراك المرتبط بها. إذا كنت تريد إيقاف برنامج طالب نهائياً، يجب أيضاً إلغاء اشتراكه من قسم الاشتراكات.
    </div>
</div>

@endsection
