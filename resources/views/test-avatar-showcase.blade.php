<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Avatar Component Showcase</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 p-8">
    <div class="max-w-7xl mx-auto">
        <h1 class="text-4xl font-bold text-gray-900 mb-8">عرض مكونات الصور الرمزية</h1>

        <!-- Size Variations -->
        <section class="bg-white rounded-xl shadow-sm border p-6 mb-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">الأحجام المختلفة</h2>
            <div class="flex items-end gap-6">
                <div class="text-center">
                    <x-avatar :user="auth()->user()" size="xs" />
                    <p class="mt-2 text-xs text-gray-500">xs (8x8)</p>
                </div>
                <div class="text-center">
                    <x-avatar :user="auth()->user()" size="sm" />
                    <p class="mt-2 text-xs text-gray-500">sm (12x12)</p>
                </div>
                <div class="text-center">
                    <x-avatar :user="auth()->user()" size="md" />
                    <p class="mt-2 text-xs text-gray-500">md (16x16)</p>
                </div>
                <div class="text-center">
                    <x-avatar :user="auth()->user()" size="lg" />
                    <p class="mt-2 text-xs text-gray-500">lg (24x24)</p>
                </div>
                <div class="text-center">
                    <x-avatar :user="auth()->user()" size="xl" />
                    <p class="mt-2 text-xs text-gray-500">xl (32x32)</p>
                </div>
                <div class="text-center">
                    <x-avatar :user="auth()->user()" size="2xl" />
                    <p class="mt-2 text-xs text-gray-500">2xl (40x40)</p>
                </div>
            </div>
        </section>

        <!-- Avatar with Border (Teacher Profiles) -->
        <section class="bg-white rounded-xl shadow-sm border p-6 mb-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">صورة بإطار ملون (صفحات المعلمين)</h2>
            <div class="flex items-center gap-8">
                <div class="text-center">
                    <x-avatar :user="auth()->user()" size="xl" :showBorder="true" borderColor="yellow" />
                    <p class="mt-2 text-sm text-gray-600">معلم قرآن (أصفر)</p>
                </div>
                <div class="text-center">
                    <x-avatar :user="auth()->user()" size="xl" :showBorder="true" borderColor="violet" />
                    <p class="mt-2 text-sm text-gray-600">معلم أكاديمي (بنفسجي)</p>
                </div>
                <div class="text-center">
                    <x-avatar :user="auth()->user()" size="xl" :showBorder="true" borderColor="blue" />
                    <p class="mt-2 text-sm text-gray-600">طالب (أزرق)</p>
                </div>
            </div>
        </section>

        <!-- Avatar with Status -->
        <section class="bg-white rounded-xl shadow-sm border p-6 mb-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">صورة مع حالة الاتصال</h2>
            <div class="flex items-center gap-6">
                <div class="text-center">
                    <x-avatar :user="auth()->user()" size="lg" :showStatus="true" />
                    <p class="mt-2 text-sm text-gray-600">مع حالة الاتصال</p>
                </div>
            </div>
        </section>

        <!-- Avatar with Badge -->
        <section class="bg-white rounded-xl shadow-sm border p-6 mb-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">صورة مع شارة الدور</h2>
            <div class="flex items-center gap-6">
                <div class="text-center">
                    <x-avatar :user="auth()->user()" size="lg" :showBadge="true" />
                    <p class="mt-2 text-sm text-gray-600">مع شارة الدور</p>
                </div>
            </div>
        </section>

        <!-- Combined Features -->
        <section class="bg-white rounded-xl shadow-sm border p-6 mb-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">مزيج من المميزات</h2>
            <div class="flex items-center gap-6">
                <div class="text-center">
                    <x-avatar :user="auth()->user()" size="xl" :showBorder="true" :showStatus="true" :showBadge="true" />
                    <p class="mt-2 text-sm text-gray-600">مع كل المميزات</p>
                </div>
            </div>
        </section>

        <!-- Usage in List Context -->
        <section class="bg-white rounded-xl shadow-sm border p-6">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">مثال في قائمة</h2>
            <div class="space-y-4">
                @for($i = 0; $i < 5; $i++)
                <div class="flex items-center gap-4 p-4 hover:bg-gray-50 rounded-lg transition-colors">
                    <x-avatar :user="auth()->user()" size="md" :showStatus="true" />
                    <div class="flex-1">
                        <h4 class="font-medium text-gray-900">{{ auth()->user()->full_name ?? 'مستخدم تجريبي' }}</h4>
                        <p class="text-sm text-gray-500">{{ auth()->user()->email ?? 'user@example.com' }}</p>
                    </div>
                    <div class="text-sm text-gray-400">
                        منذ {{ rand(1, 60) }} دقيقة
                    </div>
                </div>
                @endfor
            </div>
        </section>

        <!-- Code Examples -->
        <section class="bg-white rounded-xl shadow-sm border p-6 mt-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">أمثلة الاستخدام</h2>
            <div class="space-y-4">
                <div>
                    <h3 class="font-semibold text-gray-700 mb-2">صورة بسيطة</h3>
                    <pre class="bg-gray-900 text-gray-100 p-4 rounded-lg overflow-x-auto"><code>&lt;x-avatar :user="$user" /&gt;</code></pre>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-700 mb-2">صورة بحجم مخصص</h3>
                    <pre class="bg-gray-900 text-gray-100 p-4 rounded-lg overflow-x-auto"><code>&lt;x-avatar :user="$user" size="xl" /&gt;</code></pre>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-700 mb-2">صورة بإطار ملون</h3>
                    <pre class="bg-gray-900 text-gray-100 p-4 rounded-lg overflow-x-auto"><code>&lt;x-avatar :user="$teacher" size="xl" :showBorder="true" borderColor="yellow" /&gt;</code></pre>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-700 mb-2">صورة مع حالة الاتصال</h3>
                    <pre class="bg-gray-900 text-gray-100 p-4 rounded-lg overflow-x-auto"><code>&lt;x-avatar :user="$user" :showStatus="true" /&gt;</code></pre>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-700 mb-2">صورة مع شارة الدور</h3>
                    <pre class="bg-gray-900 text-gray-100 p-4 rounded-lg overflow-x-auto"><code>&lt;x-avatar :user="$user" :showBadge="true" /&gt;</code></pre>
                </div>
            </div>
        </section>
    </div>
</body>
</html>
