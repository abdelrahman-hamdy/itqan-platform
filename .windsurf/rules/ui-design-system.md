---
trigger: model_decision
description: Guidline for building and refactoring views and UI components.
globs:
---
# Itqan Platform UI Design System & TailwindCSS Rules

## 🎯 Design Philosophy

- **Arabic-First**: All interfaces are designed with Arabic and RTL support as primary
- **Consistency Over Creativity**: Maintain consistent patterns across all components
- **Mobile-First Responsive**: Every component works perfectly on mobile devices
- **Accessibility**: WCAG 2.1 AA compliance for inclusive design
- **Performance**: Minimal CSS footprint with TailwindCSS utility classes

## 🎨 Color System & Theming

### Primary Color Palette
```css
/* Primary Brand Colors (Itqan Green/Blue Theme) */
primary: {
  50: '#f0f9ff',    // Very light blue
  100: '#e0f2fe',   // Light blue
  500: '#0ea5e9',   // Main primary color
  600: '#0284c7',   // Hover state
  700: '#0369a1',   // Active state
  900: '#0c4a6e'    // Dark accent
}

/* Success Colors (Islamic Green) */
success: {
  50: '#f0fdf4',
  500: '#22c55e',
  600: '#16a34a',
  700: '#15803d'
}

/* Warning & Error Colors */
warning: { 500: '#f59e0b', 600: '#d97706' }
error: { 500: '#ef4444', 600: '#dc2626' }

/* Neutral Colors */
gray: {
  50: '#f9fafb',    // Light backgrounds
  100: '#f3f4f6',   // Card backgrounds
  200: '#e5e7eb',   // Borders
  400: '#9ca3af',   // Muted text
  500: '#6b7280',   // Secondary text
  700: '#374151',   // Primary text
  900: '#111827'    // Headings
}
```

### Color Usage Guidelines
```php
// ✅ DO: Use consistent color patterns
<div class="bg-white border border-gray-200 rounded-lg shadow-sm">
<button class="bg-primary-500 hover:bg-primary-600 text-white">
<span class="text-gray-700">Primary text</span>
<span class="text-gray-400">Secondary text</span>

// ❌ DON'T: Use arbitrary colors or mix inconsistent shades
<div class="bg-blue-300 border-2 border-green-400">
<button class="bg-red-500 hover:bg-purple-600">
```

## 📐 Spacing & Layout System

### Consistent Spacing Scale
```css
/* Standard spacing using TailwindCSS scale */
xs: 0.5rem  (2px)   // Tight spacing
sm: 0.75rem (3px)   // Small spacing  
base: 1rem  (4px)   // Default spacing
md: 1.5rem  (6px)   // Medium spacing
lg: 2rem    (8px)   // Large spacing
xl: 3rem    (12px)  // Extra large spacing
2xl: 4rem   (16px)  // Section spacing
```

### Layout Patterns
```php
// ✅ DO: Use consistent layout patterns
<div class="container mx-auto px-4 sm:px-6 lg:px-8">
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <div class="bg-white rounded-lg shadow-sm p-6">
      <h3 class="text-lg font-semibold text-gray-900 mb-4">Card Title</h3>
      <p class="text-gray-600 text-sm">Card content</p>
    </div>
  </div>
</div>

// ❌ DON'T: Use inconsistent spacing or arbitrary values
<div class="px-7 py-3 ml-13 mt-[23px]">
```

## 🔤 Typography System

### Font Hierarchy
```php
// Headings
h1: "text-3xl sm:text-4xl font-bold text-gray-900"
h2: "text-2xl sm:text-3xl font-semibold text-gray-900"  
h3: "text-lg sm:text-xl font-semibold text-gray-900"
h4: "text-base sm:text-lg font-medium text-gray-900"

// Body Text
body-large: "text-base text-gray-700"
body-base: "text-sm text-gray-700"
body-small: "text-xs text-gray-600"

// Special Text
caption: "text-xs text-gray-400 uppercase tracking-wide"
label: "text-sm font-medium text-gray-700"
error: "text-sm text-error-600"
success: "text-sm text-success-600"
```

### Arabic Typography
```php
// ✅ DO: Use proper Arabic font handling
<h1 class="text-3xl font-bold text-gray-900 
           font-arabic leading-relaxed text-right">
  العنوان الرئيسي
</h1>

// ✅ DO: Handle mixed content properly
<p class="text-sm text-gray-700 leading-relaxed" dir="auto">
  النص العربي mixed with English text
</p>

// ❌ DON'T: Ignore RTL/LTR text direction
<div class="text-left">النص العربي</div>
```

## 🧩 Component Design Patterns

### Card Components
```php
// ✅ Standard Card Pattern
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
  <div class="flex items-start justify-between mb-4">
    <h3 class="text-lg font-semibold text-gray-900">Card Title</h3>
    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary-100 text-primary-800">
      Badge
    </span>
  </div>
  <p class="text-sm text-gray-600 mb-4">Card description</p>
  <div class="flex items-center justify-end space-x-3 space-x-reverse">
    <button class="btn-secondary">Secondary Action</button>
    <button class="btn-primary">Primary Action</button>
  </div>
</div>
```

### Button Components
```php
// Primary Button
btn-primary: "inline-flex items-center px-4 py-2 bg-primary-500 hover:bg-primary-600 
              text-white font-medium rounded-md transition-colors duration-200 
              focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"

// Secondary Button  
btn-secondary: "inline-flex items-center px-4 py-2 bg-white hover:bg-gray-50 
                text-gray-700 font-medium rounded-md border border-gray-300 
                transition-colors duration-200 focus:outline-none focus:ring-2 
                focus:ring-primary-500 focus:ring-offset-2"

// Destructive Button
btn-danger: "inline-flex items-center px-4 py-2 bg-error-500 hover:bg-error-600 
             text-white font-medium rounded-md transition-colors duration-200 
             focus:outline-none focus:ring-2 focus:ring-error-500 focus:ring-offset-2"
```

### Form Components
```php
// ✅ Form Input Pattern
<div class="mb-4">
  <label class="block text-sm font-medium text-gray-700 mb-2" for="input-id">
    Input Label
  </label>
  <input 
    type="text" 
    id="input-id"
    class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm 
           placeholder-gray-400 focus:outline-none focus:ring-primary-500 
           focus:border-primary-500 text-sm"
    placeholder="Placeholder text">
  <p class="mt-1 text-xs text-gray-500">Helper text</p>
</div>

// Error State
<input class="border-error-300 focus:border-error-500 focus:ring-error-500">
<p class="mt-1 text-xs text-error-600">Error message</p>
```

## 📱 Responsive Design Patterns

### Breakpoint Usage
```php
// ✅ Mobile-first responsive design
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
<div class="px-4 sm:px-6 lg:px-8">
<div class="text-sm sm:text-base lg:text-lg">

// ✅ Hide/show elements responsively
<div class="block sm:hidden">Mobile only</div>
<div class="hidden sm:block">Desktop only</div>
<div class="sm:flex sm:items-center sm:justify-between">

// ❌ DON'T: Use desktop-first or arbitrary breakpoints
<div class="lg:grid-cols-3 md:grid-cols-2 grid-cols-1">
```

### Navigation Patterns
```php
// ✅ Mobile-first navigation
<nav class="bg-white shadow-sm border-b border-gray-200">
  <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
    <div class="flex justify-between items-center h-16">
      <!-- Mobile menu button -->
      <div class="flex items-center sm:hidden">
        <button class="mobile-menu-toggle p-2 rounded-md text-gray-400 hover:text-gray-500">
          <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
          </svg>
        </button>
      </div>
      
      <!-- Desktop navigation -->
      <div class="hidden sm:flex sm:space-x-8">
        <a href="#" class="text-gray-900 hover:text-primary-600 px-3 py-2 text-sm font-medium">
          Nav Item
        </a>
      </div>
    </div>
  </div>
</nav>
```

## 🌍 RTL/Arabic Support

### Direction Handling
```php
// ✅ DO: Use proper directional classes
<div class="flex items-center space-x-3 space-x-reverse"> // RTL-safe spacing
<div class="text-right"> // Arabic text alignment
<div class="mr-4 ml-0 rtl:mr-0 rtl:ml-4"> // RTL-responsive margins

// ✅ DO: Use dir="auto" for mixed content
<p dir="auto" class="text-sm text-gray-700">
  Mixed العربي and English content
</p>

// ❌ DON'T: Use fixed directional classes for Arabic
<div class="text-left mr-4"> // Wrong for Arabic
```

### Arabic Font Stack
```css
/* ✅ DO: Define proper Arabic font stack */
.font-arabic {
  font-family: 'Tajawal', 'Cairo', 'Amiri', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

.font-english {
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}
```

## 🎭 Animation & Transitions

### Standard Transitions
```php
// ✅ Use consistent transition patterns
transition-colors: "transition-colors duration-200"
transition-all: "transition-all duration-300 ease-in-out"
hover-lift: "transition-transform duration-200 hover:scale-105"
focus-ring: "focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"

// ✅ Loading states
<div class="animate-pulse bg-gray-200 rounded h-4 w-24"></div>
<div class="animate-spin h-5 w-5 border-2 border-primary-500 border-t-transparent rounded-full"></div>
```

## 🛡️ Accessibility Guidelines

### Required Attributes
```php
// ✅ Always include accessibility attributes
<button aria-label="Close dialog" class="btn-secondary">
<input aria-describedby="email-help" aria-required="true">
<div role="alert" aria-live="polite">Error message</div>

// ✅ Proper contrast ratios
text-gray-700 on bg-white        // ✅ 4.5:1 ratio
text-white on bg-primary-500     // ✅ 4.5:1 ratio  
text-gray-400 on bg-white        // ❌ Below 4.5:1 ratio (use for decorative only)
```

### Keyboard Navigation
```php
// ✅ Ensure keyboard accessibility
<div class="focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2">
<button tabindex="0" role="button">
```

## 📏 Layout & Grid Systems

### Container Patterns
```php
// ✅ Standard container widths
container-sm: "max-w-sm mx-auto"      // 384px
container-md: "max-w-4xl mx-auto"     // 896px  
container-lg: "max-w-7xl mx-auto"     // 1280px
container-full: "max-w-full mx-auto"  // 100%

// ✅ Standard padding
container-padding: "px-4 sm:px-6 lg:px-8"
```

### Grid Patterns
```php
// ✅ Common grid layouts
grid-auto: "grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6"
grid-sidebar: "grid grid-cols-1 lg:grid-cols-4 gap-6" // 1fr + 3fr layout
grid-equal: "grid grid-cols-1 md:grid-cols-2 gap-6"   // 50/50 split
```

## 🎨 Filament Panel Customization

### Panel Theming
```php
// ✅ Consistent Filament panel styles
->colors([
    'primary' => Color::Blue,
    'success' => Color::Green,
    'warning' => Color::Amber,
    'danger' => Color::Red,
])
->font('Tajawal') // Arabic font
->darkMode(false) // Disable dark mode for consistency
->topNavigation() // Use top navigation for better RTL support
```

### Resource Styling
```php
// ✅ Consistent table styling
public static function table(Table $table): Table
{
    return $table
        ->striped()
        ->defaultPaginationPageOption(25)
        ->extremePaginationLinks()
        ->columns([
            TextColumn::make('name')
                ->searchable()
                ->sortable()
                ->toggleable()
                ->wrap(),
        ])
        ->actions([
            Tables\Actions\EditAction::make()
                ->iconSize('sm'),
            Tables\Actions\DeleteAction::make()
                ->iconSize('sm'),
        ]);
}
```

## 🚫 Anti-Patterns to Avoid

### CSS Anti-Patterns
```php
// ❌ DON'T: Use arbitrary values unnecessarily
<div class="mt-[23px] px-[15px] w-[234px]">

// ❌ DON'T: Mix utility classes with custom CSS
<div class="flex custom-weird-class" style="margin-top: 13px;">

// ❌ DON'T: Use inconsistent color shades
<div class="bg-blue-300 text-green-700 border-red-400">

// ❌ DON'T: Ignore responsive design
<div class="w-96 text-lg"> // Fixed width, no responsive consideration
```

### Component Anti-Patterns
```php
// ❌ DON'T: Create one-off component styles
<button class="bg-purple-500 px-7 py-1 text-yellow-300 border-4 border-pink-400">

// ❌ DON'T: Use unclear or inconsistent naming
<div class="my-weird-container special-card-thing">
```

## ✅ Code Quality Checklist

Before implementing any UI component:

- [ ] Uses consistent color palette
- [ ] Follows spacing scale
- [ ] Includes proper responsive classes
- [ ] Supports RTL/Arabic text
- [ ] Includes accessibility attributes
- [ ] Uses semantic HTML
- [ ] Follows animation patterns
- [ ] Matches existing component style
- [ ] Tested on mobile devices
- [ ] Validated for contrast ratios

## 🔄 Component Review Process

1. **Design Review**: All major UI components must be approved before implementation
2. **Code Review**: UI code follows these rules and patterns
3. **Accessibility Review**: Components meet WCAG 2.1 AA standards
4. **Responsive Review**: Components work on all breakpoints
5. **Arabic/RTL Review**: Components support Arabic content properly

## 📋 File Organization

```
resources/
├── css/
│   ├── app.css                 // Main styles
│   ├── components.css          // Component styles
│   └── arabic.css             // Arabic-specific styles
├── views/
│   ├── components/            // Reusable Blade components
│   │   ├── ui/               // Pure UI components
│   │   ├── forms/            // Form components
│   │   └── navigation/       // Navigation components
│   └── layouts/              // Layout templates
└── js/
    ├── components/           // JavaScript components
    └── utils/               // Utility functions
```

Remember: **Consistency over creativity. Every component should feel like part of the same design system.**
description:
globs:
alwaysApply: false
---
