# Circle Components Documentation

This document describes the unified circle components designed to provide consistent UI/UX across all circle-related pages.

## Overview

The circle components are designed with the following principles:
- **Reusability**: One component serves multiple contexts
- **Consistency**: Unified design language across different circle types
- **Flexibility**: Extensive customization options
- **Performance**: Optimized for different use cases

## Components

### 1. Stats Grid Component (`stats-grid.blade.php`)

Displays statistics in a responsive grid layout with consistent styling.

#### Usage
```blade
<x-circle.stats-grid :stats="$statsData" />
```

#### Props
- `stats` (array): Array of statistics data
- `variant` (string): 'default', 'compact', 'minimal'
- `columns` (string): CSS grid classes (default: 'grid-cols-2 lg:grid-cols-4')
- `showIcons` (boolean): Whether to show icons (default: true)
- `showBorders` (boolean): Whether to show borders (default: true)

#### Stats Data Format
```php
$stats = [
    [
        'label' => 'إجمالي الجلسات',
        'value' => 24,
        'icon' => 'ri-book-line',
        'color' => 'blue',
        'subtitle' => 'Optional subtitle' // optional
    ],
    // ... more stats
];
```

#### Available Colors
- `blue`, `green`, `orange`, `purple`, `yellow`, `red`, `indigo`, `pink`

### 2. Sessions Display Component (`sessions-display.blade.php`)

Unified component for displaying sessions across different contexts.

#### Usage
```blade
<x-circle.sessions-display 
    :sessions="$sessions" 
    :circle="$circle" 
    view-type="student" 
    context="individual" />
```

#### Props
- `sessions` (Collection): Sessions collection
- `circle` (Model): Circle model instance
- `viewType` (string): 'student' or 'teacher'
- `context` (string): 'individual', 'group', 'progress'
- `showStats` (boolean): Whether to show stats header (default: true)
- `showFilters` (boolean): Whether to show filter buttons (default: true)
- `showActions` (boolean): Whether to show action buttons (default: true)
- `showProgress` (boolean): Whether to show progress bar (default: true)
- `title` (string): Custom title (default: 'جلسات الحلقة')
- `emptyMessage` (string): Custom empty state message
- `emptyDescription` (string): Custom empty state description
- `variant` (string): 'default', 'compact', 'detailed'

#### Context-Specific Features

**Individual Context:**
- Shows session sequence numbers
- Displays progress tracking
- Individual session management

**Group Context:**
- Group-specific stats
- Student attendance tracking
- Batch session management

**Progress Context:**
- Detailed attendance status
- Performance metrics
- Historical analysis

### 3. Info Card Component (`info-card.blade.php`)

Displays circle information with different layouts for different contexts.

#### Usage
```blade
<x-circle.info-card :circle="$circle" view-type="teacher" context="individual" />
```

#### Props
- `circle` (Model): Circle model instance
- `viewType` (string): 'student' or 'teacher'
- `context` (string): 'individual' or 'group'
- `showActions` (boolean): Whether to show action buttons (default: true)
- `showDetails` (boolean): Whether to show detailed info (default: true)
- `variant` (string): 'default', 'compact', 'banner'

#### Variants

**Default Variant:**
- Standard card layout
- Full information display
- Action buttons for teachers

**Compact Variant:**
- Reduced padding and spacing
- Essential information only
- Suitable for sidebars

**Banner Variant:**
- Full-width display
- Hero-style layout
- Perfect for page headers

### 4. Learning Progress Component (`learning-progress.blade.php`)

Displays Quran memorization progress with different detail levels.

#### Usage
```blade
<x-circle.learning-progress :circle="$circle" variant="detailed" />
```

#### Props
- `circle` (Model): Circle model instance
- `variant` (string): 'default', 'compact', 'detailed'
- `showTitle` (boolean): Whether to show section title (default: true)

#### Variants

**Default Variant:**
- Standard grid layout
- Current progress metrics
- Suitable for most contexts

**Compact Variant:**
- Horizontal layout
- Key metrics only
- Space-efficient display

**Detailed Variant:**
- Enhanced visual design
- Additional progress information
- Perfect for progress pages

## Design System

### Color Palette
- **Primary Colors**: Blue tones for main actions and information
- **Success Colors**: Green tones for completed states and achievements
- **Warning Colors**: Orange/Yellow tones for pending or attention items
- **Info Colors**: Purple tones for special features or highlights

### Typography
- **Headings**: Bold, clear hierarchy
- **Body Text**: Readable, accessible font sizes
- **Labels**: Consistent sizing and spacing

### Spacing
- **Grid Gaps**: Consistent 4-unit spacing system
- **Card Padding**: Responsive padding based on screen size
- **Component Margins**: Standardized vertical rhythm

### Animations
- **Hover Effects**: Subtle transforms and color transitions
- **Loading States**: Smooth progress indicators
- **State Changes**: Clear visual feedback

## Best Practices

### Performance
1. Use `collect()` for empty collections to avoid null errors
2. Implement lazy loading for large session lists
3. Cache computed statistics when possible

### Accessibility
1. Provide meaningful alt text for icons
2. Use proper heading hierarchy
3. Ensure sufficient color contrast
4. Add focus states for interactive elements

### Responsiveness
1. Test on multiple screen sizes
2. Use responsive grid classes
3. Optimize touch targets for mobile

### Maintenance
1. Update component versions consistently
2. Document breaking changes
3. Provide migration guides for major updates

## Migration Guide

### From Old Individual Circle Components

**Before:**
```blade
<x-individual-circle.sessions-list :circle="$circle" :sessions="$sessions" view-type="teacher" />
```

**After:**
```blade
<x-circle.sessions-display :sessions="$sessions" :circle="$circle" view-type="teacher" context="individual" />
```

### From Old Stats Implementations

**Before:**
```blade
<div class="grid grid-cols-4 gap-4">
    <div class="bg-blue-50 p-4 rounded">
        <!-- Manual stat card -->
    </div>
</div>
```

**After:**
```blade
@php
$stats = [
    ['label' => 'المكتملة', 'value' => $count, 'icon' => 'ri-check-line', 'color' => 'green']
];
@endphp
<x-circle.stats-grid :stats="$stats" />
```

## Component Dependencies

### Required Blade Components
- `x-student-avatar` (for student displays)

### Required CSS Classes
- Tailwind CSS utility classes
- RemixIcon icon fonts

### Required JavaScript
- Session filtering functionality
- Modal management for actions

## Future Enhancements

1. **Real-time Updates**: WebSocket integration for live session updates
2. **Advanced Filtering**: Date ranges, multiple criteria
3. **Export Features**: PDF/Excel export for session data
4. **Analytics Integration**: Detailed progress tracking
5. **Mobile App Support**: API endpoints for mobile components
