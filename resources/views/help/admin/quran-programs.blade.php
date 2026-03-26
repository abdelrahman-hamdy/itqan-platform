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

<img src="/images/help/admin/quran-programs-overview.png"
     alt="لوحة تحكم الأكاديمية — القائمة الجانبية تُظهر قسم إدارة القرآن"
     class="help-screenshot">

<div class="help-step">
    <div class="help-step-number">1</div>
    <div class="help-step-content">
        <h3>افتح لوحة الإدارة الأمامية</h3>
        <p>
            انتقل إلى <strong>/manage/dashboard</strong> على نطاق أكاديميتك. جميع عمليات القرآن متاحة من لوحة الإدارة الأمامية مباشرةً من القائمة الجانبية.
        </p>
    </div>
</div>

<div class="help-step">
    <div class="help-step-number">2</div>
    <div class="help-step-content">
        <h3>المحتويات الرئيسية في لوحة الإدارة</h3>
        <p>تضم لوحة الإدارة الأمامية الأقسام التالية لإدارة القرآن:</p>
        <ul>
            <li><strong>المعلمون</strong> (<code dir="ltr">/manage/teachers</code>) — إدارة حسابات معلمي القرآن</li>
            <li><strong>الحلقات الفردية</strong> (<code dir="ltr">/manage/individual-circles</code>) — ربط كل طالب بمعلمه وتحديد نوع التخصص</li>
            <li><strong>الاشتراكات</strong> (<code dir="ltr">/manage/subscriptions</code>) — إدارة اشتراكات الطلاب وتتبع الجلسات المتبقية</li>
            <li><strong>الجلسات</strong> (<code dir="ltr">/manage/sessions</code>) — متابعة الجلسات المجدولة والمكتملة والواجبات</li>
            <li><strong>طلبات التجربة</strong> (<code dir="ltr">/manage/trial-sessions</code>) — مراجعة طلبات الجلسات التجريبية المجانية</li>
        </ul>
    </div>
</div>

<div class="help-note">
    <i class="ri-information-line help-callout-icon"></i>
    <div>
        يُدار <strong>معلمو القرآن</strong> من قسم <em>المعلمون</em> في لوحة الإدارة الأمامية
        (<code dir="ltr">/manage/teachers</code>).
        أما <strong>باقات القرآن</strong> (الأسعار وعدد الجلسات) فتُدار من لوحة Filament
        (<code dir="ltr">/panel</code>) ضمن إعدادات الأكاديمية المتقدمة.
    </div>
</div>

<h2>التدفق العام لإدارة برنامج القرآن</h2>

<p>يُنصح باتباع هذا الترتيب عند الإعداد لأول مرة:</p>

<div class="help-step">
    <div class="help-step-number">1</div>
    <div class="help-step-content">
        <h3>إنشاء الباقات (من لوحة Filament)</h3>
        <p>حدد عدد الجلسات الشهرية ومدتها وأسعارها الثلاثة (شهري، ربع سنوي، سنوي) من لوحة Filament على <code dir="ltr">/panel</code>. إعدادات الباقات والتسعير تُدار من هناك.</p>
    </div>
</div>

<div class="help-step">
    <div class="help-step-number">2</div>
    <div class="help-step-content">
        <h3>إضافة المعلمين</h3>
        <p>أنشئ حسابات المعلمين من قسم <em>المعلمون</em> في لوحة الإدارة الأمامية
        (<code dir="ltr">/manage/teachers</code>) وفعّل حساباتهم.</p>
    </div>
</div>

<div class="help-step">
    <div class="help-step-number">3</div>
    <div class="help-step-content">
        <h3>تسجيل الطلاب واشتراكاتهم</h3>
        <p>راجع وأدر اشتراكات الطلاب من قسم <em>الاشتراكات</em>
        (<code dir="ltr">/manage/subscriptions</code>) في لوحة الإدارة الأمامية.</p>
    </div>
</div>

<div class="help-step">
    <div class="help-step-number">4</div>
    <div class="help-step-content">
        <h3>إدارة الحلقات الفردية</h3>
        <p>راجع الحلقات وعدّل بياناتها من قسم <em>الحلقات الفردية</em>
        (<code dir="ltr">/manage/individual-circles</code>). تُنشأ الحلقات تلقائياً عند تفعيل الاشتراكات.</p>
    </div>
</div>

<div class="help-step">
    <div class="help-step-number">5</div>
    <div class="help-step-content">
        <h3>متابعة الجلسات</h3>
        <p>تتولى المنصة جدولة الجلسات تلقائياً. يمكنك متابعتها من قسم <em>الجلسات</em>
        (<code dir="ltr">/manage/sessions</code>) في لوحة الإدارة الأمامية.</p>
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
