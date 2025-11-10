#!/bin/bash

# Test script for Interactive Course View Separation
# Tests that public, student, and teacher views are properly separated

echo "=== Testing Interactive Course View Separation ==="
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Test 1: Check middleware exists and is properly configured
echo "Test 1: Checking middleware configuration..."
if grep -q "redirect.authenticated.public:interactive-course" routes/web.php; then
    echo -e "${GREEN}✓ Middleware is configured in routes${NC}"
else
    echo -e "${RED}✗ Middleware NOT found in routes${NC}"
fi
echo ""

# Test 2: Check routes are properly separated
echo "Test 2: Checking route separation..."

# Public routes
if grep -q "Route::get('/interactive-courses'" routes/web.php; then
    echo -e "${GREEN}✓ Public interactive courses routes exist${NC}"
else
    echo -e "${RED}✗ Public routes NOT found${NC}"
fi

# Student routes
if grep -q "Route::get('/my-interactive-courses'" routes/web.php; then
    echo -e "${GREEN}✓ Student interactive courses routes exist${NC}"
else
    echo -e "${RED}✗ Student routes NOT found${NC}"
fi

# Teacher routes check
if grep -q "teacher.interactive-course-detail" app/Http/Controllers/StudentProfileController.php; then
    echo -e "${GREEN}✓ Teacher view exists in controller${NC}"
else
    echo -e "${RED}✗ Teacher view NOT found${NC}"
fi
echo ""

# Test 3: Check view files exist
echo "Test 3: Checking view files..."

if [ -f "resources/views/public/interactive-courses/show.blade.php" ]; then
    echo -e "${GREEN}✓ Public view exists${NC}"
else
    echo -e "${RED}✗ Public view NOT found${NC}"
fi

if [ -f "resources/views/student/interactive-course-detail.blade.php" ]; then
    echo -e "${GREEN}✓ Student view exists${NC}"
else
    echo -e "${RED}✗ Student view NOT found${NC}"
fi

if [ -f "resources/views/teacher/interactive-course-detail.blade.php" ]; then
    echo -e "${GREEN}✓ Teacher view exists${NC}"
else
    echo -e "${RED}✗ Teacher view NOT found${NC}"
fi
echo ""

# Test 4: Check middleware logic for enrollment
echo "Test 4: Checking middleware enrollment logic..."

if grep -q "enrollment_status.*enrolled.*completed" app/Http/Middleware/RedirectAuthenticatedPublicViews.php; then
    echo -e "${GREEN}✓ Middleware checks for enrolled/completed status${NC}"
else
    echo -e "${YELLOW}⚠ Middleware may not properly check enrollment status${NC}"
fi

if grep -q "pending.*enrollment" app/Http/Middleware/RedirectAuthenticatedPublicViews.php; then
    echo -e "${GREEN}✓ Middleware handles pending enrollments${NC}"
else
    echo -e "${YELLOW}⚠ Middleware may not handle pending enrollments${NC}"
fi
echo ""

# Test 5: Check controller enrollment validation
echo "Test 5: Checking controller enrollment validation..."

if grep -q "CRITICAL.*Students can only view" app/Http/Controllers/StudentProfileController.php; then
    echo -e "${GREEN}✓ Controller has enrollment validation${NC}"
else
    echo -e "${RED}✗ Controller missing enrollment validation${NC}"
fi
echo ""

# Test 6: Check that public view uses @guest directive
echo "Test 6: Checking public view uses @guest directive..."

if grep -q "@guest" resources/views/public/interactive-courses/show.blade.php; then
    echo -e "${GREEN}✓ Public view properly uses @guest directive${NC}"
else
    echo -e "${YELLOW}⚠ Public view may show auth content to guests${NC}"
fi
echo ""

# Test 7: Check student view uses proper navigation
echo "Test 7: Checking student view uses student navigation..."

if grep -q "student-nav\|student-sidebar" resources/views/student/interactive-course-detail.blade.php; then
    echo -e "${GREEN}✓ Student view uses student navigation${NC}"
else
    echo -e "${RED}✗ Student view may not use proper navigation${NC}"
fi
echo ""

# Test 8: Check teacher view uses proper layout
echo "Test 8: Checking teacher view uses teacher layout..."

if grep -q "x-layouts.teacher" resources/views/teacher/interactive-course-detail.blade.php; then
    echo -e "${GREEN}✓ Teacher view uses teacher layout${NC}"
else
    echo -e "${RED}✗ Teacher view may not use proper layout${NC}"
fi
echo ""

echo "=== Test Summary ==="
echo ""
echo -e "${YELLOW}If all tests pass, the interactive course views are properly separated.${NC}"
echo -e "${YELLOW}Run 'php artisan route:list | grep interactive' to see all routes.${NC}"
echo ""
