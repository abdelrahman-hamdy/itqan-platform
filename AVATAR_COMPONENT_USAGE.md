# Unified Avatar Component Usage Guide

## Overview
The unified avatar component (`<x-avatar>`) is a flexible, role-aware component that displays user avatars with automatic fallbacks, role-based colors, and customizable styling.

## Features
- ✅ Automatic role detection (academic teacher, quran teacher, student, etc.)
- ✅ Gender-based default avatars
- ✅ Role-specific background colors (violet, yellow, blue)
- ✅ Colored borders for teacher profiles
- ✅ Multiple size options (xs, sm, md, lg, xl, 2xl)
- ✅ Online status indicators
- ✅ Role badges
- ✅ Support for custom uploaded avatars
- ✅ Fallback to default images or initials/icons

## Basic Usage

### Simple Avatar
```blade
<x-avatar :user="$user" />
```

### Different Sizes
```blade
<x-avatar :user="$user" size="xs" />   <!-- 8x8 -->
<x-avatar :user="$user" size="sm" />   <!-- 12x12 -->
<x-avatar :user="$user" size="md" />   <!-- 16x16 (default) -->
<x-avatar :user="$user" size="lg" />   <!-- 24x24 -->
<x-avatar :user="$user" size="xl" />   <!-- 32x32 -->
<x-avatar :user="$user" size="2xl" />  <!-- 40x40 -->
```

### Avatar with Colored Border (for Teacher Profiles)
```blade
<!-- Auto-detect border color based on teacher role -->
<x-avatar :user="$teacher" size="xl" :showBorder="true" />

<!-- Override border color -->
<x-avatar :user="$quranTeacher" size="xl" :showBorder="true" borderColor="yellow" />
<x-avatar :user="$academicTeacher" size="xl" :showBorder="true" borderColor="violet" />
```

### Avatar with Online Status
```blade
<x-avatar :user="$user" :showStatus="true" />
```

### Avatar with Role Badge
```blade
<x-avatar :user="$user" :showBadge="true" />
```

### Avatar with Custom Classes
```blade
<x-avatar :user="$user" class="shadow-lg hover:scale-110 transition-transform" />
```

## Role-Based Colors

### Automatic Color Assignment

| User Type | Background Color | Text Color | Border Color | Default Avatar |
|-----------|-----------------|------------|--------------|----------------|
| Academic Teacher | Violet (bg-violet-50) | text-violet-700 | border-violet-600 | male/female-academic-teacher-avatar.png |
| Quran Teacher | Yellow (bg-yellow-50) | text-yellow-700 | border-yellow-600 | male/female-quran-teacher-avatar.png |
| Student | Blue (bg-blue-50) | text-blue-700 | border-blue-600 | male/female-student-avatar.png |
| Parent | Purple (bg-purple-50) | text-purple-700 | border-purple-600 | Initials/Icon fallback |
| Supervisor | Orange (bg-orange-50) | text-orange-700 | border-orange-600 | Initials/Icon fallback |
| Admin | Red (bg-red-50) | text-red-700 | border-red-600 | Initials/Icon fallback |

## Default Avatar Images

Located in `storage/app/public/app-design-assets/`:
- `male-academic-teacher-avatar.png`
- `female-academic-teacher-avatar.png`
- `male-quran-teacher-avatar.png`
- `female-quran-teacher-avatar.png`
- `male-student-avatar.png`
- `female-student-avatar.png`

## Avatar Priority (Waterfall)

The component uses the following priority when displaying avatars:

1. **User's uploaded avatar** (`$user->avatar`)
2. **Default role/gender avatar** (if applicable)
3. **User's initial letter** (first character of name)
4. **Role icon** (fallback icon based on user type)

## Real-World Examples

### Teacher Profile Header (with colored border)
```blade
<x-teacher.profile-header :teacher="$teacher" :stats="$stats" color="violet">
    <div class="flex-shrink-0">
        <x-avatar :user="$teacher" size="xl" :showBorder="true" borderColor="violet" />
    </div>
</x-teacher.profile-header>
```

### Student List Item
```blade
<div class="flex items-center gap-3">
    <x-avatar :user="$student" size="sm" />
    <div>
        <h4 class="font-medium">{{ $student->full_name }}</h4>
        <p class="text-sm text-gray-500">{{ $student->email }}</p>
    </div>
</div>
```

### Chat Message Avatar
```blade
<div class="flex gap-2">
    <x-avatar :user="$message->sender" size="xs" :showStatus="true" />
    <div class="flex-1">
        <p>{{ $message->content }}</p>
    </div>
</div>
```

### User Dropdown/Menu
```blade
<div class="flex items-center gap-3 p-4">
    <x-avatar :user="auth()->user()" size="md" />
    <div>
        <p class="font-medium">{{ auth()->user()->full_name }}</p>
        <p class="text-xs text-gray-500">{{ auth()->user()->email }}</p>
    </div>
</div>
```

### Teacher Cards Grid
```blade
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    @foreach($teachers as $teacher)
        <div class="bg-white rounded-xl shadow-sm border p-6">
            <div class="flex flex-col items-center text-center">
                <x-avatar :user="$teacher" size="xl" :showBorder="true" />
                <h3 class="mt-4 font-bold">{{ $teacher->full_name }}</h3>
                <p class="text-sm text-gray-500">{{ $teacher->bio }}</p>
            </div>
        </div>
    @endforeach
</div>
```

## Props Reference

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `user` | User\|Model | required | User object or any model with user data |
| `size` | string | 'md' | Avatar size: xs, sm, md, lg, xl, 2xl |
| `showBorder` | boolean | false | Show colored border (for teacher profiles) |
| `showBadge` | boolean | false | Show role badge icon |
| `showStatus` | boolean | false | Show online status indicator |
| `borderColor` | string\|null | null | Override border color: yellow, violet, or auto-detect |

## Component Intelligence

The component automatically:
- Detects user type from `$user->user_type` or `$user->type`
- Extracts gender from various possible locations (`$user->gender`, `$user->studentProfile->gender`, etc.)
- Finds avatar from multiple possible properties (`$user->avatar`, `$user->profile->avatar`, etc.)
- Assigns appropriate colors, icons, and default images based on role
- Handles missing or null user gracefully with sensible defaults

## Migration from Old Components

### Before (using separate components)
```blade
<x-teacher-avatar :teacher="$teacher" size="lg" />
<x-student-avatar :student="$student" size="md" />
<x-user-avatar :user="$user" size="sm" />
```

### After (using unified component)
```blade
<x-avatar :user="$teacher" size="lg" />
<x-avatar :user="$student" size="md" />
<x-avatar :user="$user" size="sm" />
```

## Notes

- The component uses TailwindCSS utility classes
- All sizes are responsive and maintain aspect ratio
- RTL support is built-in (uses space-x-reverse where applicable)
- Component is fully compatible with Livewire and Alpine.js
- Gender detection supports both English ('male'/'female') values
- If gender is not available, defaults to 'male' avatar
