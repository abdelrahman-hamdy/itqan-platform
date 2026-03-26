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

<h2>الفرق بين لوحات التحكم في المنصة</h2>

<p>
    المنصة توفر الآن <strong>واجهات أمامية متكاملة لجميع الأدوار</strong>.
    جميع العمليات اليومية تُنجز من خلال لوحتين أماميتين، بينما تُستخدم لوحة Filament للعمليات المتقدمة وإدارة المنصة فقط:
</p>

<div class="my-5 space-y-3">

    {{-- Tier 1: Frontend Management Dashboard --}}
    <div class="rounded-2xl border-2 border-blue-200 bg-white p-5 shadow-sm">
        <div class="flex items-center gap-2 mb-3">
            <div class="w-9 h-9 rounded-lg bg-blue-600 flex items-center justify-center flex-shrink-0">
                <i class="ri-dashboard-line text-white text-lg"></i>
            </div>
            <div>
                <p class="font-bold text-sm text-gray-900 leading-none">لوحة الإدارة الأمامية</p>
                <p class="text-xs text-gray-500 mt-0.5">للمدير والمشرف والمدير العام — العمليات اليومية</p>
            </div>
        </div>
        <p class="text-gray-600 text-xs leading-relaxed mb-3">
            لوحة إدارة كاملة بواجهة عصرية لجميع العمليات اليومية: إدارة المعلمين والطلاب
            والاشتراكات والجلسات والمدفوعات والتقارير. هذا هو الرابط الرئيسي لعمل المدير والمشرف.
        </p>
        <div class="space-y-1.5">
            <div class="flex items-center gap-1.5 text-xs text-gray-600">
                <i class="ri-checkbox-circle-fill text-blue-500"></i> مدير عام
            </div>
            <div class="flex items-center gap-1.5 text-xs text-gray-600">
                <i class="ri-checkbox-circle-fill text-blue-500"></i> مدير الأكاديمية
            </div>
            <div class="flex items-center gap-1.5 text-xs text-gray-600">
                <i class="ri-checkbox-circle-fill text-blue-500"></i> المشرف
            </div>
        </div>
        <div class="mt-3 pt-3 border-t border-gray-100">
            <p class="text-xs text-gray-400">الرابط الرئيسي</p>
            <p class="text-xs font-mono text-blue-700 mt-0.5" dir="ltr">/manage/dashboard</p>
        </div>
    </div>

    {{-- Tier 2: Frontend Portal (teachers, students, parents) --}}
    <div class="rounded-2xl border-2 border-primary bg-primary/5 p-5">
        <div class="flex items-center gap-2 mb-3">
            <div class="w-9 h-9 rounded-lg bg-primary flex items-center justify-center flex-shrink-0">
                <i class="ri-computer-line text-white text-lg"></i>
            </div>
            <div>
                <p class="font-bold text-sm text-gray-900 leading-none">الموقع الأمامي</p>
                <p class="text-xs text-gray-500 mt-0.5">للمعلمين والطلاب وأولياء الأمور</p>
            </div>
        </div>
        <p class="text-gray-600 text-xs leading-relaxed mb-3">
            الواجهة الرئيسية للمعلمين لإدارة جلساتهم وواجباتهم وطلابهم.
            وللطلاب وأولياء الأمور لمتابعة الدراسة والتقارير.
        </p>
        <div class="space-y-1.5">
            <div class="flex items-center gap-1.5 text-xs text-gray-600">
                <i class="ri-checkbox-circle-fill text-primary"></i> معلمو القرآن والمعلمون الأكاديميون
            </div>
            <div class="flex items-center gap-1.5 text-xs text-gray-600">
                <i class="ri-checkbox-circle-fill text-primary"></i> الطلاب
            </div>
            <div class="flex items-center gap-1.5 text-xs text-gray-600">
                <i class="ri-checkbox-circle-fill text-primary"></i> أولياء الأمور
            </div>
        </div>
        <div class="mt-3 pt-3 border-t border-primary/20">
            <p class="text-xs text-gray-400">الرابط</p>
            <p class="text-xs font-mono text-primary mt-0.5" dir="ltr">/login &nbsp;→&nbsp; /profile &nbsp;أو&nbsp; /teacher/*</p>
        </div>
    </div>

    {{-- Tier 3: Filament (advanced/supplementary) --}}
    <div class="rounded-2xl border-2 border-gray-200 bg-gray-50 p-5">
        <div class="flex items-center gap-2 mb-3">
            <div class="w-9 h-9 rounded-lg bg-gray-600 flex items-center justify-center flex-shrink-0">
                <i class="ri-settings-3-line text-white text-lg"></i>
            </div>
            <div>
                <p class="font-bold text-sm text-gray-900 leading-none">لوحة Filament <span class="text-xs font-normal text-gray-500">(متقدم / اختياري)</span></p>
                <p class="text-xs text-gray-500 mt-0.5">للعمليات المتقدمة وإدارة المنصة</p>
            </div>
        </div>
        <p class="text-gray-600 text-xs leading-relaxed mb-3">
            تُستخدم بشكل رئيسي من المدير العام لإنشاء الأكاديميات وضبط إعدادات المنصة.
            ويمكن للمدير استخدامها للعمليات المتقدمة التي لا تتوفر في لوحة الإدارة الأمامية.
        </p>
        <div class="space-y-1.5">
            <div class="flex items-center gap-1.5 text-xs text-gray-500">
                <i class="ri-checkbox-circle-line text-gray-400"></i> مدير عام (استخدام أساسي)
            </div>
            <div class="flex items-center gap-1.5 text-xs text-gray-500">
                <i class="ri-checkbox-circle-line text-gray-400"></i> مدير الأكاديمية (اختياري — للإعدادات المتقدمة)
            </div>
            <div class="flex items-center gap-1.5 text-xs text-gray-500">
                <i class="ri-checkbox-circle-line text-gray-400"></i> المعلمون (اختياري)
            </div>
        </div>
        <div class="mt-3 pt-3 border-t border-gray-200">
            <p class="text-xs text-gray-400">الروابط</p>
            <p class="text-xs font-mono text-gray-600 mt-0.5" dir="ltr">/admin &nbsp;أو&nbsp; /panel &nbsp;أو&nbsp; /teacher-panel</p>
        </div>
    </div>

</div>

<div class="help-tip">
    <i class="ri-lightbulb-line help-callout-icon"></i>
    <div>
        الطلاب وأولياء الأمور لا يستطيعون الدخول عبر روابط لوحة الإدارة
        (<code dir="ltr">/panel</code> أو <code dir="ltr">/manage/dashboard</code>).
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

{{-- Group 1: Filament only (super admin) --}}
<div class="mt-5 mb-2">
    <div class="flex items-center gap-2 mb-3">
        <span class="inline-flex items-center gap-1.5 bg-amber-100 text-amber-700 text-xs font-semibold px-3 py-1 rounded-full">
            <i class="ri-shield-star-line text-sm"></i>
            لوحة Filament فقط — المدير العام
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

    </div>
</div>

{{-- Group 2: Frontend management dashboard (admin + supervisor) --}}
<div class="mb-2">
    <div class="flex items-center gap-2 mb-3">
        <span class="inline-flex items-center gap-1.5 bg-blue-100 text-blue-700 text-xs font-semibold px-3 py-1 rounded-full">
            <i class="ri-dashboard-line text-sm"></i>
            لوحة الإدارة الأمامية — الرابط الرئيسي للإدارة اليومية
        </span>
    </div>
    <div class="space-y-2">

        <div class="flex items-start gap-3 p-3 bg-blue-50 border border-blue-200 rounded-xl">
            <i class="ri-user-settings-line text-blue-600 text-lg flex-shrink-0 mt-0.5"></i>
            <div class="flex-1 min-w-0">
                <p class="text-xs font-bold text-blue-800 mb-1.5">مدير الأكاديمية</p>
                <div class="space-y-1.5">
                    <div x-data="{ copied: false }" class="flex items-center gap-2">
                        <span class="text-xs text-gray-500 flex-shrink-0">الأساسي:</span>
                        <code class="text-xs bg-blue-100 text-blue-900 px-2 py-0.5 rounded font-mono truncate" dir="ltr">your-academy.itqanway.com/manage/dashboard</code>
                        <button @click="navigator.clipboard.writeText('https://your-academy.itqanway.com/manage/dashboard'); copied = true; setTimeout(() => copied = false, 2000)"
                                class="flex-shrink-0 p-1 rounded hover:bg-blue-200 transition-colors">
                            <i :class="copied ? 'ri-check-line text-green-600' : 'ri-file-copy-line text-blue-400'" class="text-sm"></i>
                        </button>
                    </div>
                    <div x-data="{ copied: false }" class="flex items-center gap-2">
                        <span class="text-xs text-gray-500 flex-shrink-0">Filament (متقدم):</span>
                        <code class="text-xs bg-gray-100 text-gray-700 px-2 py-0.5 rounded font-mono truncate" dir="ltr">your-academy.itqanway.com/panel</code>
                        <button @click="navigator.clipboard.writeText('https://your-academy.itqanway.com/panel'); copied = true; setTimeout(() => copied = false, 2000)"
                                class="flex-shrink-0 p-1 rounded hover:bg-gray-200 transition-colors">
                            <i :class="copied ? 'ri-check-line text-green-600' : 'ri-file-copy-line text-gray-400'" class="text-sm"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-3 p-3 bg-orange-50 border border-orange-200 rounded-xl">
            <i class="ri-eye-line text-orange-600 text-lg flex-shrink-0"></i>
            <div class="flex-1 min-w-0">
                <p class="text-xs font-bold text-orange-800">المشرف</p>
                <div x-data="{ copied: false }" class="flex items-center gap-2 mt-1">
                    <code class="text-xs bg-orange-100 text-orange-900 px-2 py-1 rounded font-mono truncate" dir="ltr">your-academy.itqanway.com/manage/dashboard</code>
                    <button @click="navigator.clipboard.writeText('https://your-academy.itqanway.com/manage/dashboard'); copied = true; setTimeout(() => copied = false, 2000)"
                            class="flex-shrink-0 p-1 rounded hover:bg-orange-200 transition-colors"
                            :title="copied ? 'تم النسخ!' : 'نسخ الرابط'">
                        <i :class="copied ? 'ri-check-line text-green-600' : 'ri-file-copy-line text-orange-500'" class="text-sm"></i>
                    </button>
                </div>
            </div>
        </div>

    </div>
</div>

{{-- Group 3: Frontend primary + optional Filament (2 teacher roles) --}}
<div class="mb-2">
    <div class="flex items-center gap-2 mb-3">
        <span class="inline-flex items-center gap-1.5 bg-purple-100 text-purple-700 text-xs font-semibold px-3 py-1 rounded-full">
            <i class="ri-computer-line text-sm"></i>
            الموقع الأمامي — الرابط الأساسي للمعلمين
        </span>
    </div>
    <div class="space-y-2">

        <div class="flex items-start gap-3 p-3 bg-purple-50 border border-purple-200 rounded-xl">
            <i class="ri-book-2-line text-purple-600 text-lg flex-shrink-0 mt-0.5"></i>
            <div class="flex-1 min-w-0">
                <p class="text-xs font-bold text-purple-800 mb-1.5">معلم القرآن</p>
                <div class="space-y-1.5">
                    <div x-data="{ copied: false }" class="flex items-center gap-2">
                        <span class="text-xs text-gray-500 flex-shrink-0">الأساسي:</span>
                        <code class="text-xs bg-purple-100 text-purple-900 px-2 py-0.5 rounded font-mono truncate" dir="ltr">your-academy.itqanway.com/login</code>
                        <button @click="navigator.clipboard.writeText('https://your-academy.itqanway.com/login'); copied = true; setTimeout(() => copied = false, 2000)"
                                class="flex-shrink-0 p-1 rounded hover:bg-purple-200 transition-colors">
                            <i :class="copied ? 'ri-check-line text-green-600' : 'ri-file-copy-line text-purple-400'" class="text-sm"></i>
                        </button>
                    </div>
                    <div x-data="{ copied: false }" class="flex items-center gap-2">
                        <span class="text-xs text-gray-500 flex-shrink-0">Filament (اختياري):</span>
                        <code class="text-xs bg-gray-100 text-gray-700 px-2 py-0.5 rounded font-mono truncate" dir="ltr">your-academy.itqanway.com/teacher-panel</code>
                        <button @click="navigator.clipboard.writeText('https://your-academy.itqanway.com/teacher-panel'); copied = true; setTimeout(() => copied = false, 2000)"
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
                        <span class="text-xs text-gray-500 flex-shrink-0">الأساسي:</span>
                        <code class="text-xs bg-indigo-100 text-indigo-900 px-2 py-0.5 rounded font-mono truncate" dir="ltr">your-academy.itqanway.com/login</code>
                        <button @click="navigator.clipboard.writeText('https://your-academy.itqanway.com/login'); copied = true; setTimeout(() => copied = false, 2000)"
                                class="flex-shrink-0 p-1 rounded hover:bg-indigo-200 transition-colors">
                            <i :class="copied ? 'ri-check-line text-green-600' : 'ri-file-copy-line text-indigo-400'" class="text-sm"></i>
                        </button>
                    </div>
                    <div x-data="{ copied: false }" class="flex items-center gap-2">
                        <span class="text-xs text-gray-500 flex-shrink-0">Filament (اختياري):</span>
                        <code class="text-xs bg-gray-100 text-gray-700 px-2 py-0.5 rounded font-mono truncate" dir="ltr">your-academy.itqanway.com/academic-teacher-panel</code>
                        <button @click="navigator.clipboard.writeText('https://your-academy.itqanway.com/academic-teacher-panel'); copied = true; setTimeout(() => copied = false, 2000)"
                                class="flex-shrink-0 p-1 rounded hover:bg-gray-200 transition-colors">
                            <i :class="copied ? 'ri-check-line text-green-600' : 'ri-file-copy-line text-gray-400'" class="text-sm"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

{{-- Group 4: Frontend only (student + parent) --}}
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

{{-- ── 5. Two-Tier Dashboard Map ────────────────────────────────────────────── --}}

<h2>لوحات التحكم المتاحة في المنصة</h2>

<p>
    المنصة تعتمد نظاماً من <strong>طبقتين</strong>: لوحات أمامية للعمليات اليومية، ولوحات Filament للعمليات المتقدمة وإدارة المنصة.
</p>

{{-- Tier 1: Frontend Dashboards --}}
<h3 class="text-base font-bold text-gray-800 mt-6 mb-3 flex items-center gap-2">
    <span class="w-6 h-6 rounded-full bg-primary flex items-center justify-center text-white text-xs font-bold flex-shrink-0">١</span>
    لوحات الإدارة الأمامية — للعمليات اليومية
</h3>

<div class="my-3 space-y-2">

    <div class="flex items-center gap-4 p-4 bg-green-50 border border-green-200 rounded-xl">
        <div class="w-12 h-12 rounded-xl bg-green-500 flex items-center justify-center flex-shrink-0 shadow">
            <i class="ri-graduation-cap-line text-white text-xl"></i>
        </div>
        <div class="flex-1 min-w-0">
            <p class="font-bold text-green-800 text-sm">طالب / ولي أمر</p>
            <p class="text-xs text-green-700 mt-0.5">متابعة الدراسة والجلسات — تُدخل عبر: <code class="bg-green-100 px-1 rounded" dir="ltr">/login</code></p>
        </div>
    </div>

    <div class="flex items-center gap-4 p-4 bg-purple-50 border border-purple-200 rounded-xl">
        <div class="w-12 h-12 rounded-xl bg-purple-500 flex items-center justify-center flex-shrink-0 shadow">
            <i class="ri-book-2-line text-white text-xl"></i>
        </div>
        <div class="flex-1 min-w-0">
            <p class="font-bold text-purple-800 text-sm">معلم قرآن / معلم أكاديمي</p>
            <p class="text-xs text-purple-700 mt-0.5">جلسات وطلاب وواجبات — تُدخل عبر: <code class="bg-purple-100 px-1 rounded" dir="ltr">/login</code> ← الملف الشخصي</p>
        </div>
    </div>

    <div class="flex items-center gap-4 p-4 bg-blue-50 border border-blue-200 rounded-xl">
        <div class="w-12 h-12 rounded-xl bg-blue-600 flex items-center justify-center flex-shrink-0 shadow">
            <i class="ri-dashboard-line text-white text-xl"></i>
        </div>
        <div class="flex-1 min-w-0">
            <p class="font-bold text-blue-800 text-sm">مشرف + مدير + مدير عام</p>
            <p class="text-xs text-blue-700 mt-0.5">جميع العمليات الإدارية اليومية — تُدخل عبر: <code class="bg-blue-100 px-1 rounded" dir="ltr">{academy}.itqanway.com/manage/dashboard</code></p>
        </div>
    </div>

</div>

{{-- Tier 2: Filament Panels --}}
<h3 class="text-base font-bold text-gray-800 mt-6 mb-3 flex items-center gap-2">
    <span class="w-6 h-6 rounded-full bg-gray-500 flex items-center justify-center text-white text-xs font-bold flex-shrink-0">٢</span>
    لوحات Filament — للعمليات المتقدمة وإدارة المنصة
</h3>

<div class="my-3 space-y-2">

    <div class="flex items-center gap-4 p-4 bg-amber-50 border border-amber-200 rounded-xl">
        <div class="w-12 h-12 rounded-xl bg-amber-500 flex items-center justify-center flex-shrink-0 shadow">
            <i class="ri-shield-star-line text-white text-xl"></i>
        </div>
        <div class="flex-1 min-w-0">
            <p class="font-bold text-amber-800 text-sm">لوحة المدير العام <span class="text-xs font-normal text-amber-600">(استخدام أساسي)</span></p>
            <p class="text-xs text-amber-700 mt-0.5">إدارة جميع الأكاديميات وإنشاء مستأجرين جدد — تُدخل عبر: <code class="bg-amber-100 px-1 rounded" dir="ltr">itqanway.com/admin</code></p>
        </div>
    </div>

    <div class="flex items-center gap-4 p-4 bg-gray-50 border border-gray-200 rounded-xl">
        <div class="w-12 h-12 rounded-xl bg-gray-500 flex items-center justify-center flex-shrink-0 shadow">
            <i class="ri-building-2-line text-white text-xl"></i>
        </div>
        <div class="flex-1 min-w-0">
            <p class="font-bold text-gray-800 text-sm">لوحة مدير الأكاديمية <span class="text-xs font-normal text-gray-600">(اختياري)</span></p>
            <p class="text-xs text-gray-700 mt-0.5">إعدادات متقدمة وباقات التسعير — تُدخل عبر: <code class="bg-gray-100 px-1 rounded" dir="ltr">{academy}.itqanway.com/panel</code></p>
        </div>
    </div>

    <div class="flex items-center gap-4 p-4 bg-gray-50 border border-gray-200 rounded-xl">
        <div class="w-12 h-12 rounded-xl bg-gray-400 flex items-center justify-center flex-shrink-0 shadow">
            <i class="ri-book-2-line text-white text-xl"></i>
        </div>
        <div class="flex-1 min-w-0">
            <p class="font-bold text-gray-700 text-sm">لوحة معلم القرآن <span class="text-xs font-normal text-gray-500">(اختياري)</span></p>
            <p class="text-xs text-gray-600 mt-0.5">واجهة بديلة للمعلم — تُدخل عبر: <code class="bg-gray-100 px-1 rounded" dir="ltr">{academy}.itqanway.com/teacher-panel</code></p>
        </div>
    </div>

    <div class="flex items-center gap-4 p-4 bg-gray-50 border border-gray-200 rounded-xl">
        <div class="w-12 h-12 rounded-xl bg-gray-400 flex items-center justify-center flex-shrink-0 shadow">
            <i class="ri-pencil-ruler-2-line text-white text-xl"></i>
        </div>
        <div class="flex-1 min-w-0">
            <p class="font-bold text-gray-700 text-sm">لوحة المعلم الأكاديمي <span class="text-xs font-normal text-gray-500">(اختياري)</span></p>
            <p class="text-xs text-gray-600 mt-0.5">واجهة بديلة للمعلم الأكاديمي — تُدخل عبر: <code class="bg-gray-100 px-1 rounded" dir="ltr">{academy}.itqanway.com/academic-teacher-panel</code></p>
        </div>
    </div>

</div>

<div class="help-note">
    <i class="ri-information-line help-callout-icon"></i>
    <div>
        <strong>أنت الآن في لوحة الإدارة الأمامية</strong> — الرابط الرئيسي هو
        <code dir="ltr">/manage/dashboard</code>.
        باقي الأدلة في مركز المساعدة تشرح كل قسم بالتفصيل.
    </div>
</div>

@endsection
