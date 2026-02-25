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
    كثير من المستخدمين يتساءلون عند البداية: لماذا هناك روابط دخول مختلفة؟
    الإجابة بسيطة — المنصة لها <strong>واجهتان مستقلتان تمامًا</strong> تخدمان أغراضًا مختلفة:
</p>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4 my-5">

    {{-- Filament Admin Panel --}}
    <div class="rounded-2xl border-2 border-blue-200 bg-white p-5 shadow-sm">
        <div class="flex items-center gap-2 mb-3">
            <div class="w-9 h-9 rounded-lg bg-blue-600 flex items-center justify-center flex-shrink-0">
                <i class="ri-dashboard-3-line text-white text-lg"></i>
            </div>
            <div>
                <p class="font-bold text-sm text-gray-900 leading-none">لوحة الإدارة</p>
                <p class="text-xs text-gray-500 mt-0.5">للموظفين والمعلمين</p>
            </div>
        </div>
        <p class="text-gray-600 text-xs leading-relaxed mb-3">
            واجهة إدارية متخصصة للمديرين والمعلمين والمشرفين.
            تُستخدم لإدارة البيانات والمحتوى والمستخدمين.
            مظهرها يتغير حسب إعدادات الأكاديمية.
        </p>
        <div class="space-y-1.5">
            <div class="flex items-center gap-1.5 text-xs text-gray-600">
                <i class="ri-checkbox-circle-fill text-blue-500"></i> المدير العام ومدير الأكاديمية
            </div>
            <div class="flex items-center gap-1.5 text-xs text-gray-600">
                <i class="ri-checkbox-circle-fill text-blue-500"></i> معلمو القرآن والمعلمون الأكاديميون
            </div>
            <div class="flex items-center gap-1.5 text-xs text-gray-600">
                <i class="ri-checkbox-circle-fill text-blue-500"></i> المشرفون
            </div>
        </div>
        <div class="mt-3 pt-3 border-t border-gray-100">
            <p class="text-xs text-gray-400">الرابط يحتوي على</p>
            <p class="text-xs font-mono text-blue-700 mt-0.5" dir="ltr">/panel &nbsp;أو&nbsp; /teacher-panel &nbsp;أو&nbsp; /admin</p>
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
                <p class="text-xs text-gray-500 mt-0.5">للطلاب وأولياء الأمور</p>
            </div>
        </div>
        <p class="text-gray-600 text-xs leading-relaxed mb-3">
            الموقع العادي للطلاب وأولياء الأمور لمتابعة الدراسة والجلسات والواجبات.
            تصميمه موجه للاستخدام اليومي وليس للإدارة.
        </p>
        <div class="space-y-1.5">
            <div class="flex items-center gap-1.5 text-xs text-gray-600">
                <i class="ri-checkbox-circle-fill text-primary"></i> الطلاب
            </div>
            <div class="flex items-center gap-1.5 text-xs text-gray-600">
                <i class="ri-checkbox-circle-fill text-primary"></i> أولياء الأمور
            </div>
            <div class="flex items-center gap-1.5 text-xs text-gray-500">
                <i class="ri-checkbox-circle-fill text-gray-300"></i>
                <span class="italic">المعلمون يستطيعون الدخول من هنا أيضًا</span>
            </div>
        </div>
        <div class="mt-3 pt-3 border-t border-primary/20">
            <p class="text-xs text-gray-400">الرابط يحتوي على</p>
            <p class="text-xs font-mono text-primary mt-0.5" dir="ltr">/login &nbsp;أو&nbsp; /dashboard</p>
        </div>
    </div>

</div>

<div class="help-tip">
    <i class="ri-lightbulb-line help-callout-icon"></i>
    <div>
        الطلاب وأولياء الأمور لا يستطيعون الدخول عبر روابط لوحة الإدارة
        (<code dir="ltr">/panel</code>).
        كل واجهة لها رابط دخول مستقل.
    </div>
</div>

{{-- ── 4. Login URLs ──────────────────────────────────────────────────────── --}}

<h2>روابط تسجيل الدخول لكل نوع مستخدم</h2>

<p>
    استبدل <code dir="ltr">your-academy</code> بالاسم الفرعي لأكاديميتك قبل مشاركة أي رابط
    (مثل: <code dir="ltr">alnoor</code>).
    يمكنك نسخ أي رابط بالضغط على أيقونة النسخ.
</p>

{{-- Group 1: Panel-only (3 roles) --}}
<div class="mt-5 mb-2">
    <div class="flex items-center gap-2 mb-3">
        <span class="inline-flex items-center gap-1.5 bg-red-100 text-red-700 text-xs font-semibold px-3 py-1 rounded-full">
            <i class="ri-lock-line text-sm"></i>
            لوحة الإدارة فقط — لا يوجد دخول من الموقع الأمامي
        </span>
    </div>
    <div class="space-y-2">

        <div class="flex items-center gap-3 p-3 bg-amber-50 border border-amber-200 rounded-xl">
            <i class="ri-shield-user-line text-amber-600 text-lg flex-shrink-0"></i>
            <div class="flex-1 min-w-0">
                <p class="text-xs font-bold text-amber-800">المدير العام</p>
                <div x-data="{ copied: false }" class="flex items-center gap-2 mt-1">
                    <code class="text-xs bg-amber-100 text-amber-900 px-2 py-1 rounded font-mono truncate" dir="ltr">itqanway.com/admin</code>
                    <button @click="navigator.clipboard.writeText('https://itqanway.com/admin'); copied = true; setTimeout(() => copied = false, 2000)"
                            class="flex-shrink-0 p-1 rounded hover:bg-amber-200 transition-colors"
                            :title="copied ? 'تم النسخ!' : 'نسخ الرابط'">
                        <i :class="copied ? 'ri-check-line text-green-600' : 'ri-file-copy-line text-amber-500'" class="text-sm"></i>
                    </button>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-3 p-3 bg-blue-50 border border-blue-200 rounded-xl">
            <i class="ri-user-settings-line text-blue-600 text-lg flex-shrink-0"></i>
            <div class="flex-1 min-w-0">
                <p class="text-xs font-bold text-blue-800">مدير الأكاديمية</p>
                <div x-data="{ copied: false }" class="flex items-center gap-2 mt-1">
                    <code class="text-xs bg-blue-100 text-blue-900 px-2 py-1 rounded font-mono truncate" dir="ltr">your-academy.itqanway.com/panel</code>
                    <button @click="navigator.clipboard.writeText('https://your-academy.itqanway.com/panel'); copied = true; setTimeout(() => copied = false, 2000)"
                            class="flex-shrink-0 p-1 rounded hover:bg-blue-200 transition-colors"
                            :title="copied ? 'تم النسخ!' : 'نسخ الرابط'">
                        <i :class="copied ? 'ri-check-line text-green-600' : 'ri-file-copy-line text-blue-500'" class="text-sm"></i>
                    </button>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-3 p-3 bg-orange-50 border border-orange-200 rounded-xl">
            <i class="ri-eye-line text-orange-600 text-lg flex-shrink-0"></i>
            <div class="flex-1 min-w-0">
                <p class="text-xs font-bold text-orange-800">المشرف</p>
                <div x-data="{ copied: false }" class="flex items-center gap-2 mt-1">
                    <code class="text-xs bg-orange-100 text-orange-900 px-2 py-1 rounded font-mono truncate" dir="ltr">your-academy.itqanway.com/supervisor-panel</code>
                    <button @click="navigator.clipboard.writeText('https://your-academy.itqanway.com/supervisor-panel'); copied = true; setTimeout(() => copied = false, 2000)"
                            class="flex-shrink-0 p-1 rounded hover:bg-orange-200 transition-colors"
                            :title="copied ? 'تم النسخ!' : 'نسخ الرابط'">
                        <i :class="copied ? 'ri-check-line text-green-600' : 'ri-file-copy-line text-orange-500'" class="text-sm"></i>
                    </button>
                </div>
            </div>
        </div>

    </div>
</div>

{{-- Group 2: Both access (2 teacher roles) --}}
<div class="mb-2">
    <div class="flex items-center gap-2 mb-3">
        <span class="inline-flex items-center gap-1.5 bg-purple-100 text-purple-700 text-xs font-semibold px-3 py-1 rounded-full">
            <i class="ri-git-merge-line text-sm"></i>
            كلا الرابطين متاح — لوحة الإدارة أو الموقع الأمامي
        </span>
    </div>
    <div class="space-y-2">

        <div class="flex items-start gap-3 p-3 bg-purple-50 border border-purple-200 rounded-xl">
            <i class="ri-book-2-line text-purple-600 text-lg flex-shrink-0 mt-0.5"></i>
            <div class="flex-1 min-w-0">
                <p class="text-xs font-bold text-purple-800 mb-1.5">معلم القرآن</p>
                <div class="space-y-1.5">
                    <div x-data="{ copied: false }" class="flex items-center gap-2">
                        <span class="text-xs text-gray-500 flex-shrink-0">لوحة إدارة:</span>
                        <code class="text-xs bg-purple-100 text-purple-900 px-2 py-0.5 rounded font-mono truncate" dir="ltr">your-academy.itqanway.com/teacher-panel</code>
                        <button @click="navigator.clipboard.writeText('https://your-academy.itqanway.com/teacher-panel'); copied = true; setTimeout(() => copied = false, 2000)"
                                class="flex-shrink-0 p-1 rounded hover:bg-purple-200 transition-colors">
                            <i :class="copied ? 'ri-check-line text-green-600' : 'ri-file-copy-line text-purple-400'" class="text-sm"></i>
                        </button>
                    </div>
                    <div x-data="{ copied: false }" class="flex items-center gap-2">
                        <span class="text-xs text-gray-500 flex-shrink-0">موقع أمامي:</span>
                        <code class="text-xs bg-gray-100 text-gray-700 px-2 py-0.5 rounded font-mono truncate" dir="ltr">your-academy.itqanway.com/login</code>
                        <button @click="navigator.clipboard.writeText('https://your-academy.itqanway.com/login'); copied = true; setTimeout(() => copied = false, 2000)"
                                class="flex-shrink-0 p-1 rounded hover:bg-gray-200 transition-colors">
                            <i :class="copied ? 'ri-check-line text-green-600' : 'ri-file-copy-line text-gray-400'" class="text-sm"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex items-start gap-3 p-3 bg-indigo-50 border border-indigo-200 rounded-xl">
            <i class="ri-pencil-ruler-2-line text-indigo-600 text-lg flex-shrink-0 mt-0.5"></i>
            <div class="flex-1 min-w-0">
                <p class="text-xs font-bold text-indigo-800 mb-1.5">المعلم الأكاديمي</p>
                <div class="space-y-1.5">
                    <div x-data="{ copied: false }" class="flex items-center gap-2">
                        <span class="text-xs text-gray-500 flex-shrink-0">لوحة إدارة:</span>
                        <code class="text-xs bg-indigo-100 text-indigo-900 px-2 py-0.5 rounded font-mono truncate" dir="ltr">your-academy.itqanway.com/academic-teacher-panel</code>
                        <button @click="navigator.clipboard.writeText('https://your-academy.itqanway.com/academic-teacher-panel'); copied = true; setTimeout(() => copied = false, 2000)"
                                class="flex-shrink-0 p-1 rounded hover:bg-indigo-200 transition-colors">
                            <i :class="copied ? 'ri-check-line text-green-600' : 'ri-file-copy-line text-indigo-400'" class="text-sm"></i>
                        </button>
                    </div>
                    <div x-data="{ copied: false }" class="flex items-center gap-2">
                        <span class="text-xs text-gray-500 flex-shrink-0">موقع أمامي:</span>
                        <code class="text-xs bg-gray-100 text-gray-700 px-2 py-0.5 rounded font-mono truncate" dir="ltr">your-academy.itqanway.com/login</code>
                        <button @click="navigator.clipboard.writeText('https://your-academy.itqanway.com/login'); copied = true; setTimeout(() => copied = false, 2000)"
                                class="flex-shrink-0 p-1 rounded hover:bg-gray-200 transition-colors">
                            <i :class="copied ? 'ri-check-line text-green-600' : 'ri-file-copy-line text-gray-400'" class="text-sm"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

{{-- Group 3: Frontend only (student + parent) --}}
<div class="mb-5">
    <div class="flex items-center gap-2 mb-3">
        <span class="inline-flex items-center gap-1.5 bg-green-100 text-green-700 text-xs font-semibold px-3 py-1 rounded-full">
            <i class="ri-computer-line text-sm"></i>
            الموقع الأمامي فقط — لا يوجد لوحة إدارة
        </span>
    </div>
    <div class="space-y-2">

        <div class="flex items-center gap-3 p-3 bg-green-50 border border-green-200 rounded-xl">
            <i class="ri-graduation-cap-line text-green-600 text-lg flex-shrink-0"></i>
            <div class="flex-1 min-w-0">
                <p class="text-xs font-bold text-green-800">الطالب وولي الأمر</p>
                <div x-data="{ copied: false }" class="flex items-center gap-2 mt-1">
                    <code class="text-xs bg-green-100 text-green-900 px-2 py-1 rounded font-mono truncate" dir="ltr">your-academy.itqanway.com/login</code>
                    <button @click="navigator.clipboard.writeText('https://your-academy.itqanway.com/login'); copied = true; setTimeout(() => copied = false, 2000)"
                            class="flex-shrink-0 p-1 rounded hover:bg-green-200 transition-colors"
                            :title="copied ? 'تم النسخ!' : 'نسخ الرابط'">
                        <i :class="copied ? 'ri-check-line text-green-600' : 'ri-file-copy-line text-green-500'" class="text-sm"></i>
                    </button>
                </div>
            </div>
        </div>

    </div>
</div>

<div class="help-warning">
    <i class="ri-error-warning-line help-callout-icon"></i>
    <div>
        <strong>تذكّر:</strong> استبدل <code dir="ltr">your-academy</code> بالنطاق الفرعي الحقيقي لأكاديميتك
        قبل مشاركة أي رابط مع المستخدمين.
    </div>
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

{{-- ── 6. What each role sees ─────────────────────────────────────────────── --}}

<h2>ماذا يرى كل مستخدم في لوحته؟</h2>

<p>
    بعد أن تُنشئ الحسابات وتُوزّع الروابط، إليك ما سيجده كل مستخدم عند دخوله:
</p>

<div class="my-5 overflow-x-auto rounded-xl border border-gray-200 shadow-sm">
    <table class="min-w-full text-sm">
        <thead>
            <tr class="bg-gray-50 border-b border-gray-200">
                <th class="px-4 py-3 text-right font-semibold text-gray-700 w-36">المستخدم</th>
                <th class="px-4 py-3 text-right font-semibold text-gray-700">ماذا يرى ويفعل في لوحته؟</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            <tr class="bg-amber-50">
                <td class="px-4 py-3 align-top">
                    <span class="flex items-center gap-1.5 font-bold text-amber-800 text-xs whitespace-nowrap">
                        <i class="ri-shield-user-line"></i> المدير العام
                    </span>
                </td>
                <td class="px-4 py-3 text-xs text-gray-700 leading-relaxed">
                    جميع الأكاديميات ومستخدميها — إنشاء أكاديميات جديدة، إدارة الإعدادات العامة للمنصة، عرض التقارير الشاملة.
                </td>
            </tr>
            <tr>
                <td class="px-4 py-3 align-top">
                    <span class="flex items-center gap-1.5 font-bold text-blue-800 text-xs whitespace-nowrap">
                        <i class="ri-user-settings-line"></i> مدير الأكاديمية
                    </span>
                </td>
                <td class="px-4 py-3 text-xs text-gray-700 leading-relaxed">
                    <strong>هذا أنت.</strong> ترى كل شيء داخل أكاديميتك: إضافة المعلمين والطلاب، الباقات، الاشتراكات، الجلسات، المدفوعات، التقارير، والإعدادات.
                </td>
            </tr>
            <tr class="bg-purple-50">
                <td class="px-4 py-3 align-top">
                    <span class="flex items-center gap-1.5 font-bold text-purple-800 text-xs whitespace-nowrap">
                        <i class="ri-book-2-line"></i> معلم القرآن
                    </span>
                </td>
                <td class="px-4 py-3 text-xs text-gray-700 leading-relaxed">
                    فقط جلساته وطلابه: يرى جدول جلساته اليومي، ينضم للجلسات المباشرة، يُعيّن واجبات الحفظ والمراجعة، ويسجّل تقدم كل طالب.
                </td>
            </tr>
            <tr>
                <td class="px-4 py-3 align-top">
                    <span class="flex items-center gap-1.5 font-bold text-indigo-800 text-xs whitespace-nowrap">
                        <i class="ri-pencil-ruler-2-line"></i> المعلم الأكاديمي
                    </span>
                </td>
                <td class="px-4 py-3 text-xs text-gray-700 leading-relaxed">
                    فقط دروسه الأكاديمية: يرى قائمة دروسه وطلابه، يرفع محتوى الدروس والواجبات، يتابع الحضور والتسليمات.
                </td>
            </tr>
            <tr class="bg-orange-50">
                <td class="px-4 py-3 align-top">
                    <span class="flex items-center gap-1.5 font-bold text-orange-800 text-xs whitespace-nowrap">
                        <i class="ri-eye-line"></i> المشرف
                    </span>
                </td>
                <td class="px-4 py-3 text-xs text-gray-700 leading-relaxed">
                    عرض شامل بدون تعديل: يتابع الجلسات الجارية والمجدولة، يراقب حضور الطلاب والمعلمين، يرى التقارير.
                </td>
            </tr>
            <tr>
                <td class="px-4 py-3 align-top">
                    <span class="flex items-center gap-1.5 font-bold text-green-800 text-xs whitespace-nowrap">
                        <i class="ri-graduation-cap-line"></i> الطالب
                    </span>
                </td>
                <td class="px-4 py-3 text-xs text-gray-700 leading-relaxed">
                    جلساته فقط: يرى موعد جلسته القادمة، ينضم للجلسة المباشرة، يطّلع على الواجبات ويرسل تسليماته، ويتابع تقدمه.
                </td>
            </tr>
            <tr class="bg-teal-50">
                <td class="px-4 py-3 align-top">
                    <span class="flex items-center gap-1.5 font-bold text-teal-800 text-xs whitespace-nowrap">
                        <i class="ri-parent-line"></i> ولي الأمر
                    </span>
                </td>
                <td class="px-4 py-3 text-xs text-gray-700 leading-relaxed">
                    متابعة أبنائه: يرى جلسات أبنائه ومواعيدها، تقارير الحضور والواجبات، وتقدم الحفظ — دون قدرة على التعديل.
                </td>
            </tr>
        </tbody>
    </table>
</div>

<div class="help-note">
    <i class="ri-information-line help-callout-icon"></i>
    <div>
        <strong>أنت الآن في لوحة مدير الأكاديمية</strong> — الرابط يحتوي على
        <code dir="ltr">/panel</code>.
        باقي الأدلة في مركز المساعدة تشرح كل قسم بالتفصيل.
    </div>
</div>

@endsection
