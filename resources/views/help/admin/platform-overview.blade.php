@extends('help.layouts.article', [
    'role' => 'admin',
    'slug' => 'platform-overview',
])

@section('content')

<h2>ما هي منصة إتقان؟</h2>

<p>
    منصة إتقان هي نظام إدارة تعليمي شامل يُتيح لك إنشاء وإدارة أكاديميتك التعليمية بالكامل عبر الإنترنت.
    تدعم المنصة تعليم القرآن الكريم والمواد الأكاديمية مع أدوات متكاملة لإدارة المعلمين والطلاب
    والجلسات والمدفوعات والتقارير.
</p>

{{-- ── 1. Platform → Academies hierarchy ────────────────────────────────── --}}

<h2>بنية المنصة: منصة واحدة — أكاديميات متعددة</h2>

<p>
    المنصة تعمل كـ"مظلة" تحتها أكاديميات مستقلة. لكل أكاديمية رابط فرعي خاص بها،
    وبياناتها منفصلة تمامًا عن الأكاديميات الأخرى.
</p>

<div class="my-6 p-5 bg-gray-50 rounded-2xl border border-gray-200 select-none">

    {{-- Platform box --}}
    <div class="flex justify-center mb-4">
        <div class="inline-flex items-center gap-2 bg-blue-700 text-white rounded-2xl px-8 py-3 shadow-lg font-bold text-base">
            <i class="ri-global-line text-xl"></i>
            منصة إتقان &nbsp;—&nbsp; <span class="font-mono text-sm font-normal opacity-80" dir="ltr">itqanway.com</span>
        </div>
    </div>

    {{-- Connector lines --}}
    <div class="flex justify-center mb-1">
        <div class="flex gap-12 sm:gap-24">
            <div class="w-px h-6 bg-gray-400 mx-auto"></div>
            <div class="w-px h-6 bg-gray-400 mx-auto"></div>
            <div class="w-px h-6 bg-gray-400 mx-auto"></div>
        </div>
    </div>
    <div class="flex justify-center mb-3">
        <div class="h-px bg-gray-400" style="width: calc(100% - 4rem); max-width: 480px;"></div>
    </div>
    <div class="flex justify-center gap-12 sm:gap-24 mb-1">
        <div class="w-px h-4 bg-gray-400"></div>
        <div class="w-px h-4 bg-gray-400"></div>
        <div class="w-px h-4 bg-gray-400"></div>
    </div>

    {{-- Academy boxes --}}
    <div class="flex flex-wrap justify-center gap-3">
        <div class="bg-white border-2 border-blue-300 rounded-xl px-5 py-3 text-center shadow-sm">
            <i class="ri-building-2-line text-blue-500 text-xl block mb-1"></i>
            <p class="text-sm font-bold text-blue-800">أكاديمية النور</p>
            <p class="text-xs text-gray-400 font-mono mt-0.5" dir="ltr">alnoor.itqanway.com</p>
        </div>
        <div class="bg-white border-2 border-blue-300 rounded-xl px-5 py-3 text-center shadow-sm">
            <i class="ri-building-2-line text-blue-500 text-xl block mb-1"></i>
            <p class="text-sm font-bold text-blue-800">أكاديمية الفجر</p>
            <p class="text-xs text-gray-400 font-mono mt-0.5" dir="ltr">alfajr.itqanway.com</p>
        </div>
        <div class="bg-white border-2 border-dashed border-blue-200 rounded-xl px-5 py-3 text-center opacity-60">
            <i class="ri-add-circle-line text-blue-400 text-xl block mb-1"></i>
            <p class="text-sm font-bold text-blue-600">أكاديميتك...</p>
            <p class="text-xs text-gray-400 font-mono mt-0.5" dir="ltr">your-name.itqanway.com</p>
        </div>
    </div>

</div>

<div class="help-note">
    <i class="ri-information-line help-callout-icon"></i>
    <div>
        أنت مدير أكاديمية واحدة فقط، ولا ترى بيانات الأكاديميات الأخرى.
        المدير العام هو الوحيد الذي يرى جميع الأكاديميات معًا.
    </div>
</div>

{{-- ── 2. User Roles ──────────────────────────────────────────────────────── --}}

<h2>أنواع المستخدمين في المنصة</h2>

<p>
    تضم المنصة <strong>سبعة أنواع</strong> من المستخدمين، لكلٍّ منهم صلاحيات وواجهة مختلفة:
</p>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-3 my-5">

    <div class="flex items-start gap-3 p-4 rounded-xl border-2 border-amber-200 bg-amber-50">
        <div class="w-11 h-11 rounded-full bg-amber-500 flex items-center justify-center flex-shrink-0 shadow">
            <i class="ri-shield-user-line text-white text-lg"></i>
        </div>
        <div>
            <p class="font-bold text-amber-800 text-sm">المدير العام <span class="text-xs font-normal text-amber-600">(Super Admin)</span></p>
            <p class="text-xs text-amber-700 mt-0.5 leading-relaxed">يدير جميع الأكاديميات في المنصة. وصول كامل لكل شيء. يُضاف مباشرةً من فريق إتقان.</p>
        </div>
    </div>

    <div class="flex items-start gap-3 p-4 rounded-xl border-2 border-blue-200 bg-blue-50">
        <div class="w-11 h-11 rounded-full bg-blue-500 flex items-center justify-center flex-shrink-0 shadow">
            <i class="ri-user-settings-line text-white text-lg"></i>
        </div>
        <div>
            <p class="font-bold text-blue-800 text-sm">مدير الأكاديمية <span class="text-xs font-normal text-blue-600">(Admin)</span></p>
            <p class="text-xs text-blue-700 mt-0.5 leading-relaxed">يدير أكاديميته فقط: يضيف المعلمين والطلاب، يُنشئ الباقات والاشتراكات، يتابع المدفوعات.</p>
        </div>
    </div>

    <div class="flex items-start gap-3 p-4 rounded-xl border-2 border-purple-200 bg-purple-50">
        <div class="w-11 h-11 rounded-full bg-purple-500 flex items-center justify-center flex-shrink-0 shadow">
            <i class="ri-book-2-line text-white text-lg"></i>
        </div>
        <div>
            <p class="font-bold text-purple-800 text-sm">معلم القرآن <span class="text-xs font-normal text-purple-600">(Quran Teacher)</span></p>
            <p class="text-xs text-purple-700 mt-0.5 leading-relaxed">يرى جلساته وطلابه فقط. يُعيّن الواجبات القرآنية ويسجّل تقدم الحفظ والمراجعة.</p>
        </div>
    </div>

    <div class="flex items-start gap-3 p-4 rounded-xl border-2 border-indigo-200 bg-indigo-50">
        <div class="w-11 h-11 rounded-full bg-indigo-500 flex items-center justify-center flex-shrink-0 shadow">
            <i class="ri-pencil-ruler-2-line text-white text-lg"></i>
        </div>
        <div>
            <p class="font-bold text-indigo-800 text-sm">المعلم الأكاديمي <span class="text-xs font-normal text-indigo-600">(Academic Teacher)</span></p>
            <p class="text-xs text-indigo-700 mt-0.5 leading-relaxed">يدير دروسه الأكاديمية الخاصة ويرفع الواجبات ومحتوى الدروس ويتابع طلابه.</p>
        </div>
    </div>

    <div class="flex items-start gap-3 p-4 rounded-xl border-2 border-orange-200 bg-orange-50">
        <div class="w-11 h-11 rounded-full bg-orange-500 flex items-center justify-center flex-shrink-0 shadow">
            <i class="ri-eye-line text-white text-lg"></i>
        </div>
        <div>
            <p class="font-bold text-orange-800 text-sm">المشرف <span class="text-xs font-normal text-orange-600">(Supervisor)</span></p>
            <p class="text-xs text-orange-700 mt-0.5 leading-relaxed">يتابع الجلسات والطلاب ويشرف على ضمان الجودة دون صلاحية التعديل الكاملة.</p>
        </div>
    </div>

    <div class="flex items-start gap-3 p-4 rounded-xl border-2 border-green-200 bg-green-50">
        <div class="w-11 h-11 rounded-full bg-green-500 flex items-center justify-center flex-shrink-0 shadow">
            <i class="ri-graduation-cap-line text-white text-lg"></i>
        </div>
        <div>
            <p class="font-bold text-green-800 text-sm">الطالب <span class="text-xs font-normal text-green-600">(Student)</span></p>
            <p class="text-xs text-green-700 mt-0.5 leading-relaxed">يتابع جلساته وواجباته وتقدمه عبر الموقع الأمامي (البوابة) وليس لوحة الإدارة.</p>
        </div>
    </div>

    <div class="flex items-start gap-3 p-4 rounded-xl border-2 border-teal-200 bg-teal-50 sm:col-span-2 sm:max-w-sm">
        <div class="w-11 h-11 rounded-full bg-teal-500 flex items-center justify-center flex-shrink-0 shadow">
            <i class="ri-parent-line text-white text-lg"></i>
        </div>
        <div>
            <p class="font-bold text-teal-800 text-sm">ولي الأمر <span class="text-xs font-normal text-teal-600">(Parent)</span></p>
            <p class="text-xs text-teal-700 mt-0.5 leading-relaxed">يتابع تقدم أبنائه وجلساتهم وتقاريرهم عبر الموقع الأمامي.</p>
        </div>
    </div>

</div>

{{-- ── 3. Frontend vs Filament ────────────────────────────────────────────── --}}

<h2>الفرق بين لوحة الإدارة والموقع الأمامي</h2>

<p>
    هذا هو المفهوم الأكثر إرباكًا للمستخدمين الجدد. المنصة لها <strong>واجهتان مختلفتان تمامًا</strong>:
</p>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4 my-5">

    {{-- Filament Admin Panel --}}
    <div class="rounded-2xl border-2 border-gray-700 bg-gray-900 text-white p-5">
        <div class="flex items-center gap-2 mb-3">
            <div class="w-9 h-9 rounded-lg bg-yellow-400 flex items-center justify-center flex-shrink-0">
                <i class="ri-dashboard-3-line text-gray-900 text-lg"></i>
            </div>
            <div>
                <p class="font-bold text-sm text-white leading-none">لوحة الإدارة</p>
                <p class="text-xs text-gray-400 mt-0.5">Filament Admin Panel</p>
            </div>
        </div>
        <p class="text-gray-300 text-xs leading-relaxed mb-3">
            واجهة احترافية داكنة مخصصة للمديرين والمعلمين لإدارة البيانات والمحتوى والمستخدمين.
        </p>
        <div class="space-y-1.5">
            <div class="flex items-center gap-1.5 text-xs text-gray-300">
                <i class="ri-checkbox-circle-fill text-green-400"></i> المدير العام والمدير
            </div>
            <div class="flex items-center gap-1.5 text-xs text-gray-300">
                <i class="ri-checkbox-circle-fill text-green-400"></i> معلمو القرآن والأكاديمي
            </div>
            <div class="flex items-center gap-1.5 text-xs text-gray-300">
                <i class="ri-checkbox-circle-fill text-green-400"></i> المشرفون
            </div>
        </div>
        <div class="mt-3 pt-3 border-t border-gray-700">
            <p class="text-xs text-gray-400">الرابط يبدأ بـ</p>
            <p class="text-xs font-mono text-yellow-300 mt-0.5" dir="ltr">/panel &nbsp;أو&nbsp; /teacher-panel</p>
        </div>
    </div>

    {{-- Frontend Portal --}}
    <div class="rounded-2xl border-2 border-primary bg-primary/5 p-5">
        <div class="flex items-center gap-2 mb-3">
            <div class="w-9 h-9 rounded-lg bg-primary flex items-center justify-center flex-shrink-0">
                <i class="ri-computer-line text-white text-lg"></i>
            </div>
            <div>
                <p class="font-bold text-sm text-gray-900 leading-none">الموقع الأمامي</p>
                <p class="text-xs text-gray-500 mt-0.5">Student & Parent Portal</p>
            </div>
        </div>
        <p class="text-gray-600 text-xs leading-relaxed mb-3">
            الموقع العادي ذو المظهر الفاتح للطلاب وأولياء الأمور لمتابعة الدراسة والجلسات.
        </p>
        <div class="space-y-1.5">
            <div class="flex items-center gap-1.5 text-xs text-gray-600">
                <i class="ri-checkbox-circle-fill text-primary"></i> الطلاب
            </div>
            <div class="flex items-center gap-1.5 text-xs text-gray-600">
                <i class="ri-checkbox-circle-fill text-primary"></i> أولياء الأمور
            </div>
        </div>
        <div class="mt-3 pt-3 border-t border-primary/20">
            <p class="text-xs text-gray-400">الرابط يبدأ بـ</p>
            <p class="text-xs font-mono text-primary mt-0.5" dir="ltr">/login &nbsp;أو&nbsp; /dashboard</p>
        </div>
    </div>

</div>

<div class="help-tip">
    <i class="ri-lightbulb-line help-callout-icon"></i>
    <div>
        إذا حاول طالب أو ولي أمر الدخول عبر رابط لوحة الإدارة (<code dir="ltr">/panel</code>)،
        لن يتمكن من تسجيل الدخول. كل واجهة لها رابط مستقل.
    </div>
</div>

{{-- ── 4. Login URLs ──────────────────────────────────────────────────────── --}}

<h2>روابط تسجيل الدخول لكل نوع مستخدم</h2>

<p>
    في الأمثلة التالية، استبدل <code dir="ltr">your-academy</code> بالاسم الفرعي لأكاديميتك
    (مثل: <code dir="ltr">alnoor</code> أو <code dir="ltr">alfajr</code>).
</p>

<div class="overflow-x-auto my-5 rounded-xl border border-gray-200 shadow-sm">
    <table class="min-w-full text-sm">
        <thead>
            <tr class="bg-gray-800 text-white">
                <th class="px-4 py-3 text-right font-semibold">نوع المستخدم</th>
                <th class="px-4 py-3 text-right font-semibold">رابط تسجيل الدخول</th>
                <th class="px-4 py-3 text-right font-semibold hidden sm:table-cell">نوع الواجهة</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            <tr class="bg-amber-50">
                <td class="px-4 py-3">
                    <span class="flex items-center gap-2">
                        <i class="ri-shield-user-line text-amber-600"></i>
                        <span class="font-medium text-amber-800">المدير العام</span>
                    </span>
                </td>
                <td class="px-4 py-3"><code class="text-xs bg-amber-100 text-amber-800 px-2 py-1 rounded" dir="ltr">itqanway.com/admin</code></td>
                <td class="px-4 py-3 text-xs text-gray-500 hidden sm:table-cell">لوحة إدارة</td>
            </tr>
            <tr>
                <td class="px-4 py-3">
                    <span class="flex items-center gap-2">
                        <i class="ri-user-settings-line text-blue-600"></i>
                        <span class="font-medium text-blue-800">مدير الأكاديمية</span>
                    </span>
                </td>
                <td class="px-4 py-3"><code class="text-xs bg-blue-50 text-blue-800 px-2 py-1 rounded" dir="ltr">your-academy.itqanway.com/panel</code></td>
                <td class="px-4 py-3 text-xs text-gray-500 hidden sm:table-cell">لوحة إدارة</td>
            </tr>
            <tr class="bg-gray-50">
                <td class="px-4 py-3">
                    <span class="flex items-center gap-2">
                        <i class="ri-book-2-line text-purple-600"></i>
                        <span class="font-medium text-purple-800">معلم القرآن</span>
                    </span>
                </td>
                <td class="px-4 py-3"><code class="text-xs bg-purple-50 text-purple-800 px-2 py-1 rounded" dir="ltr">your-academy.itqanway.com/teacher-panel</code></td>
                <td class="px-4 py-3 text-xs text-gray-500 hidden sm:table-cell">لوحة إدارة</td>
            </tr>
            <tr>
                <td class="px-4 py-3">
                    <span class="flex items-center gap-2">
                        <i class="ri-pencil-ruler-2-line text-indigo-600"></i>
                        <span class="font-medium text-indigo-800">المعلم الأكاديمي</span>
                    </span>
                </td>
                <td class="px-4 py-3"><code class="text-xs bg-indigo-50 text-indigo-800 px-2 py-1 rounded" dir="ltr">your-academy.itqanway.com/academic-teacher-panel</code></td>
                <td class="px-4 py-3 text-xs text-gray-500 hidden sm:table-cell">لوحة إدارة</td>
            </tr>
            <tr class="bg-gray-50">
                <td class="px-4 py-3">
                    <span class="flex items-center gap-2">
                        <i class="ri-eye-line text-orange-600"></i>
                        <span class="font-medium text-orange-800">المشرف</span>
                    </span>
                </td>
                <td class="px-4 py-3"><code class="text-xs bg-orange-50 text-orange-800 px-2 py-1 rounded" dir="ltr">your-academy.itqanway.com/supervisor-panel</code></td>
                <td class="px-4 py-3 text-xs text-gray-500 hidden sm:table-cell">لوحة إدارة</td>
            </tr>
            <tr>
                <td class="px-4 py-3">
                    <span class="flex items-center gap-2">
                        <i class="ri-graduation-cap-line text-green-600"></i>
                        <span class="font-medium text-green-800">الطالب</span>
                    </span>
                </td>
                <td class="px-4 py-3"><code class="text-xs bg-green-50 text-green-800 px-2 py-1 rounded" dir="ltr">your-academy.itqanway.com/login</code></td>
                <td class="px-4 py-3 text-xs text-gray-500 hidden sm:table-cell">بوابة أمامية</td>
            </tr>
            <tr class="bg-gray-50">
                <td class="px-4 py-3">
                    <span class="flex items-center gap-2">
                        <i class="ri-parent-line text-teal-600"></i>
                        <span class="font-medium text-teal-800">ولي الأمر</span>
                    </span>
                </td>
                <td class="px-4 py-3"><code class="text-xs bg-teal-50 text-teal-800 px-2 py-1 rounded" dir="ltr">your-academy.itqanway.com/login</code></td>
                <td class="px-4 py-3 text-xs text-gray-500 hidden sm:table-cell">بوابة أمامية</td>
            </tr>
        </tbody>
    </table>
</div>

{{-- ── 5. 5 Dashboards Map ─────────────────────────────────────────────────── --}}

<h2>لوحات التحكم المتاحة في المنصة</h2>

<p>
    المنصة تحتوي على <strong>خمس لوحات تحكم</strong> مختلفة (إضافةً إلى الموقع الأمامي للطلاب وأولياء الأمور):
</p>

<div class="my-5 space-y-3">

    <div class="flex items-center gap-4 p-4 bg-amber-50 border border-amber-200 rounded-xl">
        <div class="w-12 h-12 rounded-xl bg-amber-500 flex items-center justify-center flex-shrink-0 shadow">
            <i class="ri-shield-star-line text-white text-xl"></i>
        </div>
        <div class="flex-1 min-w-0">
            <p class="font-bold text-amber-800 text-sm">لوحة المدير العام</p>
            <p class="text-xs text-amber-700 mt-0.5">إدارة جميع الأكاديميات — تُدخل عبر: <code class="bg-amber-100 px-1 rounded" dir="ltr">itqanway.com/admin</code></p>
        </div>
    </div>

    <div class="flex items-center gap-4 p-4 bg-blue-50 border border-blue-200 rounded-xl">
        <div class="w-12 h-12 rounded-xl bg-blue-500 flex items-center justify-center flex-shrink-0 shadow">
            <i class="ri-building-2-line text-white text-xl"></i>
        </div>
        <div class="flex-1 min-w-0">
            <p class="font-bold text-blue-800 text-sm">لوحة مدير الأكاديمية</p>
            <p class="text-xs text-blue-700 mt-0.5">إدارة أكاديمية واحدة — تُدخل عبر: <code class="bg-blue-100 px-1 rounded" dir="ltr">{academy}.itqanway.com/panel</code></p>
        </div>
    </div>

    <div class="flex items-center gap-4 p-4 bg-purple-50 border border-purple-200 rounded-xl">
        <div class="w-12 h-12 rounded-xl bg-purple-500 flex items-center justify-center flex-shrink-0 shadow">
            <i class="ri-book-2-line text-white text-xl"></i>
        </div>
        <div class="flex-1 min-w-0">
            <p class="font-bold text-purple-800 text-sm">لوحة معلم القرآن</p>
            <p class="text-xs text-purple-700 mt-0.5">جلسات وطلاب وواجبات — تُدخل عبر: <code class="bg-purple-100 px-1 rounded" dir="ltr">{academy}.itqanway.com/teacher-panel</code></p>
        </div>
    </div>

    <div class="flex items-center gap-4 p-4 bg-indigo-50 border border-indigo-200 rounded-xl">
        <div class="w-12 h-12 rounded-xl bg-indigo-500 flex items-center justify-center flex-shrink-0 shadow">
            <i class="ri-pencil-ruler-2-line text-white text-xl"></i>
        </div>
        <div class="flex-1 min-w-0">
            <p class="font-bold text-indigo-800 text-sm">لوحة المعلم الأكاديمي</p>
            <p class="text-xs text-indigo-700 mt-0.5">دروس ومحتوى أكاديمي — تُدخل عبر: <code class="bg-indigo-100 px-1 rounded" dir="ltr">{academy}.itqanway.com/academic-teacher-panel</code></p>
        </div>
    </div>

    <div class="flex items-center gap-4 p-4 bg-orange-50 border border-orange-200 rounded-xl">
        <div class="w-12 h-12 rounded-xl bg-orange-500 flex items-center justify-center flex-shrink-0 shadow">
            <i class="ri-eye-line text-white text-xl"></i>
        </div>
        <div class="flex-1 min-w-0">
            <p class="font-bold text-orange-800 text-sm">لوحة المشرف</p>
            <p class="text-xs text-orange-700 mt-0.5">متابعة وإشراف — تُدخل عبر: <code class="bg-orange-100 px-1 rounded" dir="ltr">{academy}.itqanway.com/supervisor-panel</code></p>
        </div>
    </div>

    <div class="flex items-center gap-4 p-4 bg-green-50 border border-green-200 rounded-xl">
        <div class="w-12 h-12 rounded-xl bg-green-500 flex items-center justify-center flex-shrink-0 shadow">
            <i class="ri-computer-line text-white text-xl"></i>
        </div>
        <div class="flex-1 min-w-0">
            <p class="font-bold text-green-800 text-sm">الموقع الأمامي (للطلاب وأولياء الأمور)</p>
            <p class="text-xs text-green-700 mt-0.5">متابعة الدراسة — تُدخل عبر: <code class="bg-green-100 px-1 rounded" dir="ltr">{academy}.itqanway.com/login</code></p>
        </div>
    </div>

</div>

{{-- ── 6. Basic Workflow ──────────────────────────────────────────────────── --}}

<h2>تدفق العمل الأساسي لإدارة الأكاديمية</h2>

<p>الخطوات المعتادة لتشغيل الأكاديمية من البداية:</p>

<div class="help-step">
    <div class="help-step-number">1</div>
    <div class="help-step-content">
        <h3>إضافة المعلمين</h3>
        <p>
            أضف معلمي القرآن والمعلمين الأكاديميين من قسم <strong>إدارة القرآن ← معلمو القرآن</strong>.
            سيحصل كل معلم على حساب خاص وكود فريد للدخول إلى لوحة تحكمه.
        </p>
    </div>
</div>

<div class="help-step">
    <div class="help-step-number">2</div>
    <div class="help-step-content">
        <h3>إنشاء الباقات</h3>
        <p>
            حدد باقات القرآن أو الدروس الأكاديمية بأسعارها وعدد الجلسات الشهرية ومدة كل جلسة.
            الباقة هي "المنتج" الذي تبيعه للطلاب.
        </p>
    </div>
</div>

<div class="help-step">
    <div class="help-step-number">3</div>
    <div class="help-step-content">
        <h3>تسجيل الطلاب</h3>
        <p>
            أضف الطلاب وأولياء الأمور وربطهم ببعضهم. سيحصل كل طالب على حساب للدخول
            إلى الموقع الأمامي لمتابعة جلساته.
        </p>
    </div>
</div>

<div class="help-step">
    <div class="help-step-number">4</div>
    <div class="help-step-content">
        <h3>إنشاء الاشتراكات</h3>
        <p>
            اربط الطالب بالمعلم المناسب عبر اشتراك في إحدى الباقات، وحدد الأيام والأوقات المفضلة للجلسات.
        </p>
    </div>
</div>

<div class="help-step">
    <div class="help-step-number">5</div>
    <div class="help-step-content">
        <h3>الجلسات تنشأ وتعمل تلقائياً</h3>
        <p>
            تُنشأ الجلسات وروابط الاجتماعات تلقائياً قبل موعدها. حالة الجلسة تتحول
            تلقائياً من <em>مجدولة</em> إلى <em>مباشر</em> إلى <em>مكتملة</em>.
        </p>
    </div>
</div>

<div class="help-note">
    <i class="ri-information-line help-callout-icon"></i>
    <div>
        <strong>أنت الآن في لوحة مدير الأكاديمية</strong> — هذا هو المكان الصحيح لإدارة جميع ما سبق.
        تدخل إليها دائمًا عبر: <code dir="ltr">your-academy.itqanway.com/panel</code>
    </div>
</div>

@endsection
