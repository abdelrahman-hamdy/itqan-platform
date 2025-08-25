---
trigger: always_on
alwaysApply: true
---
# UI Design System for Itqan Platform

## Design Philosophy

- **Arabic-First**: All interfaces designed with Arabic and RTL support as primary
- **Consistency Over Creativity**: Maintain consistent patterns across all components
- **Mobile-First Responsive**: Every component works perfectly on mobile devices
- **Accessibility**: WCAG 2.1 AA compliance for inclusive design
- **Performance**: Minimal CSS footprint with TailwindCSS utility classes

## Color System

### Primary Brand Colors
```css
/* Itqan Blue Theme */
primary-50: #f0f9ff     /* Very light blue backgrounds */
primary-100: #e0f2fe    /* Light blue accents */
primary-500: #0ea5e9    /* Main primary color */
primary-600: #0284c7    /* Hover states */
primary-700: #0369a1    /* Active states */
primary-900: #0c4a6e    /* Dark accents */

/* Success Colors (Islamic Green) */
success-50: #f0fdf4
success-500: #22c55e
success-600: #16a34a

/* Warning & Error */
warning-500: #f59e0b
error-500: #ef4444
```

### Usage Guidelines
```blade
{{-- ✅ DO: Consistent color patterns --}}
<div class="bg-white border border-gray-200 rounded-lg shadow-sm">
    <button class="bg-primary-500 hover:bg-primary-600 text-white px-4 py-2 rounded-md">
        حفظ التغييرات
    </button>
    <span class="text-gray-700">النص الأساسي</span>
    <span class="text-gray-400">النص الثانوي</span>
</div>

{{-- ❌ DON'T: Arbitrary colors or inconsistent shades --}}
<div class="bg-blue-300 border-2 border-green-400">
    <button class="bg-red-500 hover:bg-purple-600">غير متسق</button>
</div>
```

## Typography System

### Font Hierarchy
```css
/* Arabic Typography Classes */
.heading-1 { @apply text-3xl sm:text-4xl font-bold text-gray-900 font-arabic leading-relaxed; }
.heading-2 { @apply text-2xl sm:text-3xl font-semibold text-gray-900 font-arabic; }
.heading-3 { @apply text-lg sm:text-xl font-semibold text-gray-900 font-arabic; }

.body-large { @apply text-base text-gray-700 leading-relaxed; }
.body-base { @apply text-sm text-gray-700 leading-relaxed; }
.body-small { @apply text-xs text-gray-600; }

.label-text { @apply text-sm font-medium text-gray-700; }
.error-text { @apply text-sm text-error-600; }
.success-text { @apply text-sm text-success-600; }
```

### RTL/Arabic Support
```blade
{{-- ✅ DO: Proper Arabic text handling --}}
<h1 class="heading-1 text-right" dir="rtl">
    منصة إتقان للتعليم الإسلامي
</h1>

<p class="body-base" dir="auto">
    النص العربي mixed with English content
</p>

{{-- ✅ DO: RTL-safe spacing --}}
<div class="flex items-center space-x-3 space-x-reverse">
    <button class="btn-primary">حفظ</button>
    <button class="btn-secondary">إلغاء</button>
</div>

{{-- ❌ DON'T: Ignore text direction --}}
<div class="text-left">النص العربي</div> {{-- Wrong alignment --}}
```

## Component Patterns

### Button Components
```blade
{{-- Primary Button --}}
<button class="inline-flex items-center px-4 py-2 bg-primary-500 hover:bg-primary-600 
               text-white font-medium rounded-md transition-colors duration-200 
               focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2">
    {{ $slot }}
</button>

{{-- Secondary Button --}}
<button class="inline-flex items-center px-4 py-2 bg-white hover:bg-gray-50 
               text-gray-700 font-medium rounded-md border border-gray-300 
               transition-colors duration-200 focus:outline-none focus:ring-2 
               focus:ring-primary-500 focus:ring-offset-2">
    {{ $slot }}
</button>

{{-- Danger Button --}}
<button class="inline-flex items-center px-4 py-2 bg-error-500 hover:bg-error-600 
               text-white font-medium rounded-md transition-colors duration-200 
               focus:outline-none focus:ring-2 focus:ring-error-500 focus:ring-offset-2">
    {{ $slot }}
</button>
```

### Card Components
```blade
{{-- Standard Card Pattern --}}
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
    <div class="flex items-start justify-between mb-4">
        <h3 class="heading-3">عنوان البطاقة</h3>
        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                     bg-primary-100 text-primary-800">
            جديد
        </span>
    </div>
    <p class="body-base mb-4">وصف محتوى البطاقة</p>
    <div class="flex items-center justify-end space-x-3 space-x-reverse">
        <button class="btn-secondary">إجراء ثانوي</button>
        <button class="btn-primary">إجراء أساسي</button>
    </div>
</div>
```

### Form Components
```blade
{{-- Form Input Pattern --}}
<div class="mb-4">
    <label class="block label-text mb-2" for="input-id">
        تسمية الحقل
    </label>
    <input type="text" id="input-id" name="field_name"
           class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm 
                  placeholder-gray-400 focus:outline-none focus:ring-primary-500 
                  focus:border-primary-500 text-sm"
           placeholder="النص التوضيحي" dir="rtl">
    <p class="mt-1 text-xs text-gray-500">نص مساعد</p>
</div>

{{-- Error State --}}
<input class="border-error-300 focus:border-error-500 focus:ring-error-500">
<p class="mt-1 error-text">رسالة خطأ</p>

{{-- Success State --}}
<input class="border-success-300 focus:border-success-500 focus:ring-success-500">
<p class="mt-1 success-text">تم بنجاح</p>
```

## Responsive Design Patterns

### Breakpoint Usage
```blade
{{-- ✅ Mobile-first responsive design --}}
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
    {{-- Content --}}
</div>

<div class="px-4 sm:px-6 lg:px-8">
    {{-- Container with responsive padding --}}
</div>

<div class="text-sm sm:text-base lg:text-lg">
    {{-- Responsive text sizing --}}
</div>

{{-- ✅ Hide/show elements responsively --}}
<div class="block sm:hidden">محتوى الهاتف فقط</div>
<div class="hidden sm:block">محتوى سطح المكتب فقط</div>
```

### Navigation Patterns
```blade
{{-- Mobile-first navigation --}}
<nav class="bg-white shadow-sm border-b border-gray-200">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            {{-- Mobile menu button --}}
            <div class="flex items-center sm:hidden">
                <button class="mobile-menu-toggle p-2 rounded-md text-gray-400 hover:text-gray-500">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
            </div>
            
            {{-- Desktop navigation --}}
            <div class="hidden sm:flex sm:space-x-8 sm:space-x-reverse">
                <a href="#" class="text-gray-900 hover:text-primary-600 px-3 py-2 text-sm font-medium">
                    عنصر التنقل
                </a>
            </div>
        </div>
    </div>
</nav>
```

## Animation & Transitions

```css
/* Standard transition patterns */
.transition-colors { transition: color 200ms ease-in-out, background-color 200ms ease-in-out; }
.transition-all { transition: all 300ms ease-in-out; }
.hover-lift { transition: transform 200ms ease-in-out; }
.hover-lift:hover { transform: translateY(-2px); }

/* Loading states */
.loading-pulse { animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite; }
.loading-spin { animation: spin 1s linear infinite; }

/* Focus states */
.focus-ring {
    outline: none;
    box-shadow: 0 0 0 2px theme('colors.primary.500'), 0 0 0 4px rgba(59, 130, 246, 0.1);
}
```

## Accessibility Guidelines

```blade
{{-- ✅ Required accessibility attributes --}}
<button aria-label="إغلاق النافذة" class="btn-secondary">
    <svg aria-hidden="true"><!-- icon --></svg>
</button>

<input aria-describedby="email-help" aria-required="true" type="email">
<div id="email-help" class="text-xs text-gray-500">أدخل بريدك الإلكتروني</div>

<div role="alert" aria-live="polite" class="error-text">
    رسالة خطأ مهمة
</div>

{{-- ✅ Proper contrast ratios --}}
<span class="text-gray-700">نص أساسي - تباين مناسب</span>
<span class="text-gray-900 bg-white">تباين عالي</span>

{{-- ❌ DON'T: Poor contrast --}}
<span class="text-gray-400 bg-white">تباين ضعيف</span>
```

## Layout Patterns

### Container Widths
```css
.container-sm { max-width: 384px; margin: 0 auto; }    /* Small forms */
.container-md { max-width: 896px; margin: 0 auto; }    /* Standard content */
.container-lg { max-width: 1280px; margin: 0 auto; }   /* Dashboard layouts */
.container-full { max-width: 100%; margin: 0 auto; }   /* Full width */
```

### Grid Patterns
```blade
{{-- Auto-responsive grid --}}
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
    {{-- Cards or content items --}}
</div>

{{-- Sidebar layout --}}
<div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
    <div class="lg:col-span-1">{{-- Sidebar --}}</div>
    <div class="lg:col-span-3">{{-- Main content --}}</div>
</div>

{{-- Equal columns --}}
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    {{-- 50/50 split --}}
</div>
```

## Anti-Patterns to Avoid

```blade
{{-- ❌ DON'T: Arbitrary values --}}
<div class="mt-[23px] px-[15px] w-[234px]">غير متسق</div>

{{-- ❌ DON'T: Inconsistent colors --}}
<div class="bg-blue-300 text-green-700 border-red-400">ألوان عشوائية</div>

{{-- ❌ DON'T: Ignore responsive design --}}
<div class="w-96 text-lg">عرض ثابت</div>

{{-- ❌ DON'T: Poor accessibility --}}
<button onclick="doSomething()">بدون إمكانية وصول</button>

{{-- ❌ DON'T: Wrong text direction --}}
<div class="text-left">النص العربي يجب أن يكون من اليمين</div>
```

## Quality Checklist

Before implementing any UI component:

- [ ] Uses consistent color palette
- [ ] Follows spacing scale
- [ ] Includes proper responsive classes
- [ ] Supports RTL/Arabic text correctly
- [ ] Includes accessibility attributes
- [ ] Uses semantic HTML
- [ ] Follows animation patterns
- [ ] Matches existing component style
- [ ] Tested on mobile devices
- [ ] Validated for contrast ratios
- [ ] Works with screen readers
- [ ] Keyboard navigation functional