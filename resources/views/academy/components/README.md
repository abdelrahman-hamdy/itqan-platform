# Academy Components

This directory contains reusable components for academy public pages.

## Topbar Component

The `topbar.blade.php` component provides a modern, responsive navigation bar with the following features:

### Features
- **Responsive Design**: Works on desktop and mobile devices
- **User Authentication**: Shows login button for guests, user dropdown for authenticated users
- **Centered Navigation**: Menu items are centered for better visual balance
- **Academy Branding**: Displays academy logo and name (clickable logo)
- **User Dropdown**: For logged-in users, shows profile and logout options
- **Mobile Menu**: Collapsible menu for mobile devices
- **Full Height Clickable Areas**: All navigation items have full bar height for better accessibility
- **Enhanced User Experience**: Red logout button, overflow hidden on dropdown, removed dashboard link
- **Clean Design**: No hover effects on logo, no focus outlines on menu items
- **Proper Dropdown Positioning**: User dropdown menu correctly positioned below the user button

### Usage

Include the topbar component in any academy page:

```blade
@include('academy.components.topbar', ['academy' => $academy])
```

### Required Data
- `$academy`: Academy model instance with the following properties:
  - `name`: Academy name
  - `logo`: Academy logo URL (optional)
  - `subdomain`: Academy subdomain
  - `quran_enabled`: Whether Quran section is enabled (optional)
  - `academic_enabled`: Whether academic section is enabled (optional)
  - `recorded_courses_enabled`: Whether recorded courses are enabled (optional)

### Dependencies
- Alpine.js for dropdown functionality
- Remix Icons for icons
- Tailwind CSS for styling

### Customization
The component uses academy colors from the `$academy` model:
- `brand_color` or `primary_color` for primary branding
- `secondary_color` for secondary elements

## Hero Section Component

The `hero-section.blade.php` component provides a completely redesigned modern hero section with:

### Features
- **Centered Layout**: Clean, focused single-column design with centered content
- **Single Line Heading**: "تعليم متميز للمستقبل" displayed in one line for better impact
- **Enhanced Typography**: Gradient text effects with improved line height for subheading
- **Enhanced Gradient Background**: More obvious gradient with dynamic academy colors and grid pattern overlay
- **Visible Grid Pattern**: 100px cell size grid pattern with 60% opacity for clear visibility
- **Animated Platform Label**: "منصة تعليمية متطورة" badge with bounce animation
- **Redesigned Feature Cards**: Four main academy sections with enhanced design:
  - 🟢 **حلقات القرآن** (Quran Circles) - "تعلم جماعي مع معلمين متخصصين"
  - 🔵 **تعليم فردي** (Individual Learning) - "حفظ شخصي مع متابعة مباشرة"
  - 🟠 **دروس خاصة** (Private Classes) - "تعليم أكاديمي مع معلمين خبراء"
  - 🟣 **كورسات تفاعلية** (Interactive Courses) - "تعلم متقدم مع تقنيات حديثة"
- **Platform-Style CTA Buttons**: Sophisticated button design matching platform main page with:
  - Gradient backgrounds with blur glow effects
  - Scale hover animations (hover:scale-105)
  - Shimmer effect on hover (sliding white gradient)
  - Enhanced shadows and transitions
- **Enhanced Feature Cards**: 
  - Colored icons with subtle background colors (0.1 opacity)
  - Increased spacing between cards (gap-8)
  - Unified shadow hover effect (subtle gray shadow)
  - Larger icons (56px) and improved typography
  - Elaborate subheadings (5-8 words) for better feature description
- **Simplified Statistics Section**: "إنجازاتنا بالأرقام" with clean design:
  - **English Number Format**: All numbers displayed in English format (e.g., "15,000+" instead of Arabic)
  - **Academy Color Gradient**: Numbers use gradient text with academy primary and secondary colors
  - **Unified Icon Color**: All icons use the same academy primary color for consistency
  - **Clean Layout**: Simple centered design without boxes or hover effects
  - **Large Numbers**: 3rem font size for prominent display
  - **Clear Typography**: Clean hierarchy with proper spacing
- **Testimonials Carousel**: "آراء طلابنا" section with custom JavaScript carousel:
  - **9 Testimonials**: Complete set of student reviews covering all academy services
  - **3 Items Per Slide**: Shows exactly 3 testimonials at once on desktop
  - **1-by-1 Sliding**: Slides move 1 testimonial at a time for smooth navigation
  - **Infinite Loop**: Seamless continuous scrolling with no end
  - **Beautiful Design**: Modern gradient cards with hover effects and academy brand colors
  - **Navigation Controls**: Elegant circular arrow buttons with hover animations
  - **Dots Navigation**: Interactive pagination dots with scaling effects
  - **Auto-Play**: Automatic progression every 5 seconds (pausable on hover)
  - **Responsive Design**: 1 item (mobile), 2 items (tablet), 3 items (desktop)
  - **Smooth Animations**: Professional 300ms transitions with custom JavaScript
  - **Custom Implementation**: No external dependencies, lightweight and fast
- **Responsive Design**: Adapts beautifully to all screen sizes
- **Smooth Animations**: Bounce animation on platform label, hover lift effects on feature cards and statistics

## Testimonials Component

The `testimonials.blade.php` component provides an interactive carousel for displaying student reviews:

### Features
- **Custom JavaScript Implementation**: Lightweight, dependency-free carousel solution
- **9 Student Testimonials**: Comprehensive reviews covering all academy services
- **1-by-1 Sliding**: Shows 3 items per slide and moves 1 at a time for smooth navigation
- **Infinite Loop**: Continuous scrolling with seamless transitions
- **Beautiful Card Design**: 
  - Clean white backgrounds with subtle borders
  - Subtle scale-up hover effects with minimal shadow
  - Academy brand color accents and rounded avatars
  - Modern typography with improved spacing
  - Wider layout for better content display
  - Organized flex layout for consistent heights
- **Enhanced Navigation**: 
  - Responsive circular arrow buttons with adaptive positioning
  - Buttons positioned inside container on mobile/tablet, outside on desktop
  - Swapped button positions for better UX (prev on left, next on right)
  - Interactive pagination dots with scaling effects
  - Academy brand colors throughout
- **Auto-Play**: Automatic progression every 5 seconds (pausable on hover)
- **Responsive Breakpoints**: 1 item (mobile), 2 items (medium), 3 items (desktop)
- **Smooth Animations**: 300ms professional transitions
- **Dynamic Dot Management**: Automatically adjusts dot count based on screen size
- **Star Ratings**: Visual 5-star rating system with drop shadows
- **Student Photos**: Professional profile images with academy brand borders
- **Quote Styling**: Decorative quotation marks with academy colors

### Testimonial Content
The carousel includes reviews from:
1. **أحمد محمد** - Quran Circles student
2. **فاطمة أحمد** - Academic student  
3. **محمد علي** - Parent
4. **سارة حسن** - Private lessons student
5. **خالد عبدالله** - Interactive courses student
6. **نورا سالم** - Quran Circles student
7. **يوسف أحمد** - Individual learning student
8. **مريم خالد** - Academic student
9. **عبدالرحمن محمد** - Parent

### Usage

```blade
@include('academy.components.hero-section', ['academy' => $academy])
@include('academy.components.testimonials', ['academy' => $academy])
```

### Required Data
- `$academy`: Academy model instance

### Customization
The hero section automatically uses academy colors and can be customized through the academy model:
- `brand_color` or `primary_color`: Primary gradient color
- `secondary_color`: Secondary gradient color
- `logo`: Academy logo (optional)
- `name`: Academy name
- `tagline`: Academy tagline (optional)
- `description`: Academy description (optional)

## Quran Section Component

The `quran-section.blade.php` component provides a comprehensive Quran education section with:

### Features
- **Section Header**: Academy icon, title, description, and feature badges
- **Quran Group Circles Grid**: 
  - 4-column responsive grid layout (1 on mobile, 2 on medium, 4 on desktop)
  - Shows 3 Quran circles + 1 "See More" card
  - Student page card components with consistent styling
  - Teacher info, schedule, and enrollment status
  - Default circles when no data available
- **Quran Teachers Grid**:
  - 4-column responsive grid layout matching circles structure
  - Shows 3 teachers + 1 "See More" card
  - Teacher profiles with avatars, ratings, and qualifications
  - Experience and student count information
  - Default teachers when no data available
- **See More Cards**: Navigate to public pages for each resource
- **Responsive Design**: Adapts to different screen sizes
- **Academy Branding**: Uses dynamic primary and secondary colors

### Usage

```blade
@include('academy.components.quran-section', [
  'academy' => $academy,
  'quranCircles' => $quranCircles ?? collect(),
  'quranTeachers' => $quranTeachers ?? collect()
])
```

### Required Data
- `$academy`: Academy model instance
- `$quranCircles`: Collection of Quran circles (optional)
- `$quranTeachers`: Collection of Quran teachers (optional)

## Academic Section Component

The `academic-section.blade.php` component provides a comprehensive academic education section with:

### Features
- **Section Header**: Academy icon, title, description, and feature badges
- **Interactive Courses Grid**: 
  - 4-column responsive grid layout (1 on mobile, 2 on medium, 4 on desktop)
  - Shows 3 courses + 1 "See More" card
  - Student page card components with consistent styling
  - Course thumbnails, pricing, and enrollment info
  - Default courses when no data available
- **Academic Teachers Grid**:
  - 4-column responsive grid layout matching courses structure
  - Shows 3 teachers + 1 "See More" card
  - Teacher profiles with avatars, ratings, and qualifications
  - Experience and student count information
  - Default teachers when no data available
- **See More Cards**: Navigate to public pages for each resource
- **Responsive Design**: Adapts to different screen sizes
- **Academy Branding**: Uses dynamic primary and secondary colors

### Usage

```blade
@include('academy.components.academic-section', [
  'academy' => $academy,
  'interactiveCourses' => $interactiveCourses ?? collect(),
  'academicTeachers' => $academicTeachers ?? collect()
])
```

### Required Data
- `$academy`: Academy model instance
- `$interactiveCourses`: Collection of interactive courses (optional)
- `$academicTeachers`: Collection of academic teachers (optional)
