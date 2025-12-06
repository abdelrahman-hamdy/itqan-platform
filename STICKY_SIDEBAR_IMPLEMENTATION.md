# Sticky Sidebar Implementation

## Overview
Implemented a smart sticky sidebar component that provides an improved user experience across all course pages, circle pages, and academic lesson pages.

## Features

### 1. Top Spacing
- Automatically detects the fixed header/navbar height
- Adds 24px additional spacing to prevent sidebar content from being hidden behind the navbar
- Adjusts dynamically if the header height changes

### 2. Smart Scroll Behavior

**When scrolling down:**
- Sidebar scrolls normally with the page
- When sidebar bottom reaches viewport bottom, it sticks to the bottom
- This ensures the bottom content is always reachable

**When scrolling up:**
- Sidebar scrolls normally with the page
- When sidebar top reaches the top offset (navbar + spacing), it sticks to the top
- This keeps the top content accessible

**For short sidebars:**
- If sidebar fits entirely in viewport, uses simple sticky top positioning
- Always maintains proper spacing from fixed navbar

### 3. Responsive Behavior
- Handles viewport resize events automatically
- Recalculates top offset when window is resized
- Works seamlessly with responsive layouts

### 4. Performance Optimized
- Uses `requestAnimationFrame` for smooth scroll handling
- Passive scroll event listeners for better performance
- Debounced resize events (150ms) to prevent excessive recalculations
- Scroll direction detection to minimize unnecessary updates
- Leverages native browser sticky positioning

## Implementation Details

### JavaScript Component
**File:** `resources/js/components/sticky-sidebar.js`

**Using StickySidebar Library:**
- **Library:** [sticky-sidebar](https://github.com/abouolia/sticky-sidebar) - Pure JavaScript, high performance
- **NPM Package:** `sticky-sidebar-v2`
- **Install:** `npm install sticky-sidebar-v2 --save`

The component:
- **High performance:** Pure JavaScript implementation with resize sensors
- **Smart bi-directional sticking:** Automatically sticks to top when scrolling up, bottom when scrolling down
- **Auto-initializes** on DOM ready
- **Livewire compatible:** Re-initializes after Livewire navigation
- **Globally available** via `window.initStickySidebar()`
- **Data attribute configuration:** Uses `data-sticky-container` and `data-sticky-sidebar`
- **Performance optimized:** Uses efficient scroll and resize handling
- **Responsive:** Automatically recalculates on viewport resize (minWidth: 1024px for desktop only)
- **Clean memory management:** Proper cleanup function destroys instances on navigation
- **Error handling:** Wrapped in try-catch blocks to prevent crashes

**Configuration:**
```javascript
{
    containerSelector: '[data-sticky-container]',  // Container selector
    topSpacing: topGap,                            // Top offset (header height + 24px)
    bottomSpacing: 24,                             // Bottom spacing
    resizeSensor: true,                            // Automatically recalculate on resize
    stickyClass: 'is-affixed',                     // Class added when stuck
    minWidth: 1024                                 // Only enable on desktop (lg breakpoint)
}
```

### HTML Markup
Pages must include two data attributes and proper inner wrapper structure:

1. **Container:** `data-sticky-container`
   - Applied to the grid container that holds main content and sidebar

2. **Sidebar:** `data-sticky-sidebar`
   - Applied to the sidebar element

3. **Inner Wrapper:** Required for proper spacing
   - The sticky-sidebar library wraps content internally
   - Add an inner `<div class="space-y-6">` to maintain widget spacing

**Example:**
```html
<div class="grid grid-cols-1 lg:grid-cols-3 gap-8" data-sticky-container>
    <div class="lg:col-span-2">
        <!-- Main content -->
    </div>
    <div class="lg:col-span-1" data-sticky-sidebar>
        <div class="space-y-6">
            <!-- Sidebar widgets here -->
            <div class="bg-white rounded-xl p-6">Widget 1</div>
            <div class="bg-white rounded-xl p-6">Widget 2</div>
            <div class="bg-white rounded-xl p-6">Widget 3</div>
        </div>
    </div>
</div>
```

**Important:** Do NOT add `space-y-6` directly to the element with `data-sticky-sidebar`. It must be on an inner wrapper div to preserve spacing after the library wraps the content.

## Updated Pages

### Student Pages
1. ✅ Group Quran Circle Detail (`resources/views/student/partials/circle-detail-content.blade.php`)
2. ✅ Interactive Course Detail (`resources/views/student/partials/interactive-course-detail-content.blade.php`)
3. ✅ Academic Subscription Detail (`resources/views/student/academic-subscription-detail.blade.php`)

### Teacher Pages
1. ✅ Group Quran Circle Detail (`resources/views/teacher/group-circles/show.blade.php`)
2. ✅ Interactive Course Detail (`resources/views/teacher/interactive-course-detail.blade.php`)
3. ✅ Academic Lesson Detail (`resources/views/teacher/academic-lessons/show.blade.php`)

## Migration Notes

### Removed Classes
The following classes were removed from sidebars and replaced with `data-sticky-sidebar`:
- `sticky top-6`
- `sticky top-24 self-start`

These manual sticky classes have been replaced with the smart JavaScript-based solution.

## Build Instructions

To compile the new JavaScript component, run:
```bash
npm run build
# or for development
npm run dev
```

## Browser Compatibility
- Modern browsers (Chrome, Firefox, Safari, Edge)
- Uses standard JavaScript APIs (no polyfills required)
- Gracefully degrades if JavaScript is disabled

## Testing Checklist

- [ ] Sidebar stays in its column (no overlap with main content)
- [ ] **Scrolling down**: Sidebar scrolls normally until bottom reaches viewport bottom
- [ ] **Scrolling down**: Sidebar bottom sticks when it reaches viewport bottom
- [ ] **Scrolling up**: Sidebar scrolls normally until top reaches offset position
- [ ] **Scrolling up**: Sidebar top sticks when it reaches top offset
- [ ] **Short sidebars**: If sidebar fits in viewport, sticks to top with proper offset
- [ ] Proper spacing from fixed header/navbar (24px below header)
- [ ] Sidebar respects container boundaries
- [ ] Works correctly on mobile (responsive)
- [ ] Works after Livewire navigation
- [ ] Resize events properly recalculate behavior
- [ ] Scroll direction changes are handled smoothly
- [ ] No JavaScript errors in console
- [ ] Performance is smooth on long pages

## Future Enhancements

Potential improvements:
1. Add configurable offset values via data attributes (e.g., `data-sticky-offset="32"`)
2. Support multiple independent sticky sidebars on the same page
3. Add smooth scroll behavior when sidebar reaches boundaries
4. Make sticky behavior conditional based on sidebar height vs viewport height
5. Add CSS class toggle for advanced styling when sidebar is stuck
