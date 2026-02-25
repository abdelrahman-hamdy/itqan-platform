@extends('help.layouts.article', [
    'role' => 'admin',
    'slug' => 'quran-programs',
])

@section('content')

<h2>نظرة عامة على قسم القرآن الكريم</h2>

<p>
    يتيح قسم <strong>إدارة القرآن الكريم</strong> في لوحة تحكم الأكاديمية إدارة برامج تحفيظ وتلاوة القرآن الكريم بشكل متكامل،
    من إنشاء الباقات وتحديد الأسعار، إلى تعيين المعلمين وتتبع تقدم كل طالب في الحفظ والمراجعة.
</p>

<h2>الوصول إلى قسم القرآن في لوحة التحكم</h2>

<div class="help-step">
    <div class="help-step-number">1</div>
    <div class="help-step-content">
        <h3>افتح لوحة إدارة الأكاديمية</h3>
        <p>
            انتقل إلى <strong>/panel</strong> على نطاق أكاديميتك. ستجد في القائمة الجانبية مجموعة <strong>إدارة القرآن</strong>.
        </p>
    </div>
</div>

<div class="help-step">
    <div class="help-step-number">2</div>
    <div class="help-step-content">
        <h3>المحتويات الرئيسية للقسم</h3>
        <p>يضمّ قسم إدارة القرآن الموارد التالية:</p>
        <ul>
            <li><strong>باقات القرآن</strong> — إنشاء خطط الأسعار وعدد الجلسات الشهرية</li>
            <li><strong>الحلقات الفردية</strong> — ربط كل طالب بمعلمه وتحديد نوع التخصص</li>
            <li><strong>اشتراكات القرآن</strong> — إنشاء اشتراكات الطلاب وتتبع الجلسات المتبقية</li>
            <li><strong>جلسات القرآن</strong> — متابعة الجلسات المجدولة والمكتملة والواجبات</li>
            <li><strong>طلبات التجربة</strong> — مراجعة طلبات الجلسات التجريبية المجانية</li>
        </ul>
    </div>
</div>

<div class="help-note">
    <i class="ri-information-line help-callout-icon"></i>
    <div>
        يُدار <strong>معلمو القرآن</strong> من قسم <em>إدارة المستخدمين</em> المنفصل في القائمة الجانبية، وليس ضمن قسم إدارة القرآن مباشرةً.
    </div>
</div>

<h2>التدفق العام لإدارة برنامج القرآن</h2>

<p>يُنصح باتباع هذا الترتيب عند الإعداد لأول مرة:</p>

<div class="help-step">
    <div class="help-step-number">1</div>
    <div class="help-step-content">
        <h3>إنشاء الباقات</h3>
        <p>حدد عدد الجلسات الشهرية ومدتها وأسعارها الثلاثة (شهري، ربع سنوي، سنوي).</p>
    </div>
</div>

<div class="help-step">
    <div class="help-step-number">2</div>
    <div class="help-step-content">
        <h3>إضافة المعلمين</h3>
        <p>أنشئ حسابات المعلمين من قسم <em>معلمو القرآن</em> في إدارة المستخدمين وفعّل حساباتهم.</p>
    </div>
</div>

<div class="help-step">
    <div class="help-step-number">3</div>
    <div class="help-step-content">
        <h3>تسجيل الطلاب واشتراكاتهم</h3>
        <p>أنشئ اشتراكاً لكل طالب مرتبطاً بالباقة والمعلم المناسبين.</p>
    </div>
</div>

<div class="help-step">
    <div class="help-step-number">4</div>
    <div class="help-step-content">
        <h3>إنشاء الحلقة الفردية</h3>
        <p>أنشئ حلقة فردية تربط الطالب بمعلمه وتُحدد فيها التخصص ومستوى الحفظ.</p>
    </div>
</div>

<div class="help-step">
    <div class="help-step-number">5</div>
    <div class="help-step-content">
        <h3>متابعة الجلسات</h3>
        <p>تتولى المنصة جدولة الجلسات تلقائياً. يمكنك متابعتها من قسم <em>جلسات القرآن</em>.</p>
    </div>
</div>

<h2>روابط سريعة</h2>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-3 not-prose">
    @php
        $quickLinks = [
            ['slug' => 'quran-packages',      'icon' => 'ri-price-tag-3-line',   'title' => 'إدارة باقات القرآن'],
            ['slug' => 'quran-teachers',       'icon' => 'ri-user-star-line',     'title' => 'إدارة معلمي القرآن'],
            ['slug' => 'quran-circles',        'icon' => 'ri-group-line',          'title' => 'إدارة الحلقات الفردية'],
            ['slug' => 'quran-subscriptions',  'icon' => 'ri-calendar-check-line', 'title' => 'إدارة اشتراكات القرآن'],
            ['slug' => 'quran-sessions',       'icon' => 'ri-vidicon-line',         'title' => 'إدارة جلسات القرآن'],
        ];
    @endphp
    @foreach($quickLinks as $link)
        <a href="{{ route('help.article', ['role' => 'admin', 'slug' => $link['slug']]) }}"
           class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg border border-gray-200 hover:border-primary/40 hover:bg-primary/5 transition-all group">
            <i class="{{ $link['icon'] }} text-primary text-lg"></i>
            <span class="text-sm font-medium text-gray-700 group-hover:text-primary transition-colors">{{ $link['title'] }}</span>
            <i class="ri-arrow-left-line text-gray-300 group-hover:text-primary text-xs mr-auto transition-colors"></i>
        </a>
    @endforeach
</div>

@endsection
